<?php
namespace Tecnodata\CRM\Services;

use RuntimeException;
use Tecnodata\CRM\Core\DB;

final class SalesOrderService {
    public static function settings(): array {
        $row=DB::fetch("SELECT * FROM sales_order_settings WHERE id=1");
        return $row?:[
            'id'=>1,
            'default_stage_code'=>null,
            'default_category_code'=>null,
            'default_account_code'=>null,
            'default_payment_term_code'=>null,
            'default_payment_method_code'=>null,
            'default_document_type_code'=>null,
            'default_tax_scenario_code'=>null,
            'default_stock_location_code'=>null,
            'consumer_final'=>'S',
            'send_email'=>'N',
            'freight_mode'=>'9',
            'allow_seller_freight'=>0,
        ];
    }

    public static function saveSettings(array $data,int $userId): void {
        $stage=trim((string)($data['default_stage_code']??''));
        $category=trim((string)($data['default_category_code']??''));
        $account=trim((string)($data['default_account_code']??''));
        $paymentTerm=trim((string)($data['default_payment_term_code']??''));
        $paymentMethod=trim((string)($data['default_payment_method_code']??''));
        $documentType=trim((string)($data['default_document_type_code']??''));
        $taxScenario=trim((string)($data['default_tax_scenario_code']??''));
        $stockLocation=trim((string)($data['default_stock_location_code']??''));
        $consumer=mb_strtoupper((string)($data['consumer_final']??'S'))==='N'?'N':'S';
        $email=mb_strtoupper((string)($data['send_email']??'N'))==='S'?'S':'N';
        $freight=trim((string)($data['freight_mode']??'9'))?:'9';
        $allowSellerFreight=!empty($data['allow_seller_freight'])?1:0;

        if($stage===''||!DB::fetch("SELECT 1 FROM order_stage_catalog WHERE stage_code=? AND active=1",[$stage]))
            throw new RuntimeException('Selecione uma etapa padrão válida.');
        if($category===''||!DB::fetch("SELECT 1 FROM sales_categories WHERE code=? AND active=1",[$category]))
            throw new RuntimeException('Selecione uma categoria padrão válida.');
        if($account===''||!DB::fetch("SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1",[$account]))
            throw new RuntimeException('Selecione uma conta corrente padrão válida.');
        if($paymentTerm===''||$paymentTerm==='999'||!DB::fetch("SELECT 1 FROM sales_payment_terms WHERE code=? AND active=1",[$paymentTerm]))
            throw new RuntimeException('Selecione uma condição de pagamento cadastrada na Omie. A condição 999 customizada não é aceita como padrão.');
        if($paymentMethod!==''&&!DB::fetch("SELECT 1 FROM payment_methods WHERE code=?",[$paymentMethod]))
            throw new RuntimeException('Meio de pagamento inválido.');
        if($documentType!==''&&!DB::fetch("SELECT 1 FROM document_types WHERE code=?",[$documentType]))
            throw new RuntimeException('Tipo de documento inválido.');
        if($taxScenario!==''&&!DB::fetch("SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1",[$taxScenario]))
            throw new RuntimeException('Cenário fiscal inválido.');
        if($stockLocation!==''&&!DB::fetch("SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1 AND sale_enabled=1",[$stockLocation]))
            throw new RuntimeException('Local de estoque inválido para vendas.');
        if(!in_array($freight,['0','1','2','3','4','9'],true))
            throw new RuntimeException('Modalidade de frete inválida.');

        DB::exec("INSERT INTO sales_order_settings
                  (id,default_stage_code,default_category_code,default_account_code,
                   default_payment_term_code,default_payment_method_code,default_document_type_code,
                   default_tax_scenario_code,default_stock_location_code,consumer_final,send_email,
                   freight_mode,allow_seller_freight,updated_by,updated_at)
                  VALUES(1,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
                  ON DUPLICATE KEY UPDATE
                   default_stage_code=VALUES(default_stage_code),
                   default_category_code=VALUES(default_category_code),
                   default_account_code=VALUES(default_account_code),
                   default_payment_term_code=VALUES(default_payment_term_code),
                   default_payment_method_code=VALUES(default_payment_method_code),
                   default_document_type_code=VALUES(default_document_type_code),
                   default_tax_scenario_code=VALUES(default_tax_scenario_code),
                   default_stock_location_code=VALUES(default_stock_location_code),
                   consumer_final=VALUES(consumer_final),
                   send_email=VALUES(send_email),
                   freight_mode=VALUES(freight_mode),
                   allow_seller_freight=VALUES(allow_seller_freight),
                   updated_by=VALUES(updated_by),updated_at=NOW()",
            [$stage,$category,$account,$paymentTerm,$paymentMethod?:null,$documentType?:null,
             $taxScenario?:null,$stockLocation?:null,$consumer,$email,$freight,$allowSellerFreight,$userId]);
    }

    public static function readiness(): array {
        $s=self::settings();
        $issues=[];
        if(empty($s['default_stage_code']))$issues[]='Etapa padrão';
        if(empty($s['default_category_code']))$issues[]='Categoria padrão';
        if(empty($s['default_account_code']))$issues[]='Conta corrente padrão';
        if(empty($s['default_payment_term_code'])||$s['default_payment_term_code']==='999')$issues[]='Condição de pagamento padrão';
        if(!DB::fetch("SELECT 1 FROM products WHERE active=1 LIMIT 1"))$issues[]='Catálogo de produtos';
        if(!DB::fetch("SELECT 1 FROM sales_payment_terms WHERE active=1 AND code<>'999' LIMIT 1"))$issues[]='Condições de pagamento Omie';
        return ['ready'=>!$issues,'issues'=>$issues,'settings'=>$s];
    }

    public static function create(array $input,int $userId,?string $forcedSellerCode=null): array {
        $ready=self::readiness();
        if(!$ready['ready'])throw new RuntimeException('Pedidos ainda não configurados: '.implode(', ',$ready['issues']).'.');

        $settings=$ready['settings'];
        $clientId=(int)($input['client_id']??0);
        $client=DB::fetch("SELECT * FROM clients WHERE id=? AND active=1",[$clientId]);
        if(!$client)throw new RuntimeException('Cliente inválido.');

        if($forcedSellerCode!==null && $forcedSellerCode!==''){
            $owner=DB::fetch("SELECT CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END seller_code
                              FROM clients c
                              LEFT JOIN client_metrics m ON m.client_id=c.id
                              LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
                              WHERE c.id=?", [date('Y-m'),$clientId]);
            if((string)($owner['seller_code']??'')!==$forcedSellerCode)
                throw new RuntimeException('Este cliente não pertence à sua carteira comercial do mês.');
        }

        $sellerCode=trim((string)($forcedSellerCode??$input['seller_omie_code']??''));
        if($sellerCode===''||!DB::fetch("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$sellerCode]))
            throw new RuntimeException('Vendedor inválido.');

        $stage=$forcedSellerCode!==null?(string)$settings['default_stage_code']:trim((string)($input['stage_code']??$settings['default_stage_code']));
        $category=$forcedSellerCode!==null?(string)$settings['default_category_code']:trim((string)($input['category_code']??$settings['default_category_code']));
        $account=$forcedSellerCode!==null?(string)$settings['default_account_code']:trim((string)($input['account_code']??$settings['default_account_code']));
        $taxScenario=$forcedSellerCode!==null?(string)($settings['default_tax_scenario_code']??''):trim((string)($input['tax_scenario_code']??$settings['default_tax_scenario_code']??''));
        $stockLocation=$forcedSellerCode!==null?(string)($settings['default_stock_location_code']??''):trim((string)($input['stock_location_code']??$settings['default_stock_location_code']??''));

        if(!DB::fetch("SELECT 1 FROM order_stage_catalog WHERE stage_code=? AND active=1",[$stage]))throw new RuntimeException('Etapa inválida.');
        if(!DB::fetch("SELECT 1 FROM sales_categories WHERE code=? AND active=1",[$category]))throw new RuntimeException('Categoria inválida.');
        if(!DB::fetch("SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1",[$account]))throw new RuntimeException('Conta corrente inválida.');
        if($taxScenario!==''&&!DB::fetch("SELECT 1 FROM tax_scenarios WHERE omie_code=? AND active=1",[$taxScenario]))throw new RuntimeException('Cenário fiscal inválido.');
        if($stockLocation!==''&&!DB::fetch("SELECT 1 FROM stock_locations WHERE omie_code=? AND active=1 AND sale_enabled=1",[$stockLocation]))throw new RuntimeException('Local de estoque inválido.');

        $paymentTerm=trim((string)($input['payment_term_code']??$settings['default_payment_term_code']??''));
        if($paymentTerm===''||$paymentTerm==='999'||!DB::fetch("SELECT 1 FROM sales_payment_terms WHERE code=? AND active=1",[$paymentTerm]))
            throw new RuntimeException('Escolha uma condição de pagamento válida da Omie. Condição customizada 999 exige parcelas explícitas e não é liberada neste fluxo.');

        $paymentMethod=trim((string)($input['payment_method_code']??$settings['default_payment_method_code']??''));
        if($paymentMethod!==''&&!DB::fetch("SELECT 1 FROM payment_methods WHERE code=?",[$paymentMethod]))
            throw new RuntimeException('Meio de pagamento inválido.');

        $documentType=trim((string)($settings['default_document_type_code']??''));
        if($documentType!==''&&!DB::fetch("SELECT 1 FROM document_types WHERE code=?",[$documentType]))
            throw new RuntimeException('Tipo de documento padrão inválido.');

        $forecast=trim((string)($input['forecast_date']??date('Y-m-d')));
        $forecastTs=strtotime($forecast);
        if(!$forecastTs||date('Y-m-d',$forecastTs)<date('Y-m-d'))
            throw new RuntimeException('A previsão de faturamento deve ser hoje ou uma data futura.');

        $items=is_array($input['items']??null)?$input['items']:[];
        if(!$items)throw new RuntimeException('Inclua ao menos um produto.');

        $requestToken=preg_replace('/[^A-Za-z0-9_-]/','',trim((string)($input['request_token']??'')));
        if($requestToken==='')$requestToken=date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(5)),0,10));
        $integration=substr('TDCRM-'.$requestToken,0,60);

        $existing=DB::fetch("SELECT * FROM order_creation_logs WHERE integration_code=? AND status='success' LIMIT 1",[$integration]);
        if($existing){
            return [
                'integration_code'=>$integration,
                'omie_order_code'=>(string)($existing['omie_order_code']??''),
                'order_number'=>(string)($existing['omie_order_number']??''),
                'total'=>(float)$existing['total'],
                'reused'=>true,
            ];
        }

        $det=[];$total=0.0;$line=0;
        foreach($items as $item){
            $productId=(int)($item['product_id']??0);
            $product=DB::fetch("SELECT * FROM products WHERE id=? AND active=1",[$productId]);
            if(!$product)throw new RuntimeException('Um dos produtos selecionados não é válido.');

            $qty=(float)str_replace(',','.',(string)($item['quantity']??0));
            if($forcedSellerCode!==null){
                $price=(float)$product['unit_price'];
                $discount=0.0;
            }else{
                $price=(float)str_replace(',','.',(string)($item['unit_price']??$product['unit_price']));
                $discount=max(0,(float)str_replace(',','.',(string)($item['discount']??0)));
            }
            if($qty<=0)throw new RuntimeException('Quantidade inválida para '.$product['description'].'.');
            if($price<=0)throw new RuntimeException('O produto '.$product['description'].' está sem preço válido.');

            $lineTotal=max(0,$qty*$price-$discount);
            if($lineTotal<=0)throw new RuntimeException('O total do item '.$product['description'].' precisa ser maior que zero.');
            $total+=$lineTotal;$line++;

            $prod=[
                'codigo_produto'=>is_numeric($product['omie_code'])?(int)$product['omie_code']:$product['omie_code'],
                'descricao'=>(string)$product['description'],
                'quantidade'=>$qty,
                'unidade'=>(string)($product['unit']?:'UN'),
                'valor_unitario'=>$price,
            ];
            if($discount>0){$prod['tipo_desconto']='V';$prod['valor_desconto']=$discount;}
            if(!empty($product['ncm']))$prod['ncm']=(string)$product['ncm'];
            if(!empty($product['cfop']))$prod['cfop']=(string)$product['cfop'];

            $detail=['ide'=>['codigo_item_integracao'=>(string)$line],'produto'=>$prod];
            if($stockLocation!==''){
                $detail['inf_adic']=['codigo_local_estoque'=>is_numeric($stockLocation)?(int)$stockLocation:$stockLocation];
            }
            $det[]=$detail;
        }

        $cab=[
            'codigo_pedido_integracao'=>$integration,
            'data_previsao'=>date('d/m/Y',$forecastTs),
            'etapa'=>$stage,
            'codigo_parcela'=>$paymentTerm,
        ];
        if(is_numeric($client['omie_code']))$cab['codigo_cliente']=(int)$client['omie_code'];
        else $cab['codigo_cliente_integracao']=(string)$client['omie_code'];
        if($taxScenario!=='')$cab['codigo_cenario_impostos']=is_numeric($taxScenario)?(int)$taxScenario:$taxScenario;

        $additional=[
            'codigo_categoria'=>$category,
            'codigo_conta_corrente'=>is_numeric($account)?(int)$account:$account,
            'consumidor_final'=>(string)$settings['consumer_final'],
            'enviar_email'=>(string)$settings['send_email'],
            'codVend'=>is_numeric($sellerCode)?(int)$sellerCode:$sellerCode,
        ];
        if(!empty($client['email']))$additional['utilizar_emails']=(string)$client['email'];
        if($paymentMethod!=='')$additional['meio_pagamento']=$paymentMethod;
        if($documentType!=='')$additional['tipo_documento']=$documentType;

        $optionalMap=[
            'customer_order_number'=>['numero_pedido_cliente',30],
            'contract_number'=>['numero_contrato',20],
            'contact'=>['contato',100],
        ];
        foreach($optionalMap as $inputKey=>[$omieKey,$max]){
            $value=trim((string)($input[$inputKey]??''));
            if($value!=='')$additional[$omieKey]=mb_substr($value,0,$max);
        }

        $freightMode=(string)$settings['freight_mode'];
        if($forcedSellerCode===null || !empty($settings['allow_seller_freight'])){
            $candidate=trim((string)($input['freight_mode']??$freightMode));
            if(in_array($candidate,['0','1','2','3','4','9'],true))$freightMode=$candidate;
        }
        if(!in_array($freightMode,['0','1','2','3','4','9'],true))$freightMode='9';
        $freight=['modalidade'=>$freightMode];
        if($freightMode!=='9'){
            $freightValue=max(0,(float)str_replace(',','.',(string)($input['freight_value']??0)));
            if($freightValue>0)$freight['valor_frete']=$freightValue;
            $volumes=max(0,(int)($input['freight_volumes']??0));
            if($volumes>0)$freight['quantidade_volumes']=$volumes;
            $gross=max(0,(float)str_replace(',','.',(string)($input['gross_weight']??0)));
            if($gross>0)$freight['peso_bruto']=$gross;
            $delivery=trim((string)($input['delivery_date']??''));
            if($delivery!==''&&strtotime($delivery))$freight['previsao_entrega']=date('d/m/Y',strtotime($delivery));
        }

        $payload=[
            'cabecalho'=>$cab,
            'det'=>$det,
            'frete'=>$freight,
            'informacoes_adicionais'=>$additional,
        ];
        $notes=trim((string)($input['notes']??''));
        if($notes!=='')$payload['observacoes']=['obs_venda'=>$notes];

        $clientApi=new OmieClient();
        try{
            $response=$clientApi->call('pedidos','IncluirPedido',$payload);
            if(isset($response['codigo_status']) && (string)$response['codigo_status']!=='0'){
                throw new RuntimeException((string)($response['descricao_status']??'A Omie recusou a inclusão do pedido.'));
            }

            $omieCode=(string)($response['codigo_pedido']??'');
            $number=(string)($response['numero_pedido']??'');
            $verification=null;

            try{
                $lookup=$omieCode!==''?['codigo_pedido'=>(int)$omieCode]:['codigo_pedido_integracao'=>$integration];
                $verification=$clientApi->call('pedidos','ConsultarPedido',$lookup);
                $stored=$verification['pedido_venda_produto']??$verification;
                $storedCab=$stored['cabecalho']??[];
                if($omieCode==='')$omieCode=(string)($storedCab['codigo_pedido']??'');
                if($number==='')$number=(string)($storedCab['numero_pedido']??'');
            }catch(\Throwable $verifyError){
                $verification=['warning'=>'Pedido incluído, mas a consulta de confirmação falhou: '.$verifyError->getMessage()];
            }

            DB::exec("INSERT INTO order_creation_logs
                      (integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,created_by,total,status,request_json,response_json,created_at)
                      VALUES(?,?,?,?,?,?,?,'success',?,?,NOW())
                      ON DUPLICATE KEY UPDATE omie_order_code=VALUES(omie_order_code),omie_order_number=VALUES(omie_order_number),
                      total=VALUES(total),status='success',request_json=VALUES(request_json),response_json=VALUES(response_json),error_message=NULL",
                [$integration,$omieCode?:null,$number?:null,$clientId,$sellerCode,$userId,$total,
                 json_encode($payload,JSON_UNESCAPED_UNICODE),
                 json_encode(['include'=>$response,'verify'=>$verification],JSON_UNESCAPED_UNICODE)]);

            if($omieCode!==''){
                $stageName=DB::fetch("SELECT stage_name FROM order_stage_catalog WHERE stage_code=?",[$stage]);
                DB::exec("INSERT INTO orders
                          (omie_order_code,client_omie_code,seller_omie_code,order_date,total,status,stage_code,stage_name,raw_json,updated_at)
                          VALUES(?,?,?,?,?,'ATIVO',?,?,?,NOW())
                          ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
                          order_date=VALUES(order_date),total=VALUES(total),stage_code=VALUES(stage_code),stage_name=VALUES(stage_name),
                          raw_json=VALUES(raw_json),updated_at=NOW()",
                    [$omieCode,(string)$client['omie_code'],$sellerCode,date('Y-m-d'),$total,$stage,
                     $stageName['stage_name']??('Etapa '.$stage),
                     json_encode(['request'=>$payload,'response'=>$response,'verify'=>$verification],JSON_UNESCAPED_UNICODE)]);
            }

            return ['integration_code'=>$integration,'omie_order_code'=>$omieCode,'order_number'=>$number,'total'=>$total,'response'=>$response];
        }catch(\Throwable $e){
            // Se a inclusão foi processada pela Omie mas a resposta se perdeu, recupera pelo código de integração
            // para evitar que um clique repetido crie um segundo pedido.
            try{
                $verification=$clientApi->call('pedidos','ConsultarPedido',['codigo_pedido_integracao'=>$integration]);
                $stored=$verification['pedido_venda_produto']??$verification;
                $storedCab=$stored['cabecalho']??[];
                $recoveredCode=(string)($storedCab['codigo_pedido']??'');
                if($recoveredCode!==''){
                    $number=(string)($storedCab['numero_pedido']??'');
                    DB::exec("INSERT INTO order_creation_logs
                              (integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,created_by,total,status,request_json,response_json,created_at)
                              VALUES(?,?,?,?,?,?,?,'success',?,?,NOW())
                              ON DUPLICATE KEY UPDATE omie_order_code=VALUES(omie_order_code),omie_order_number=VALUES(omie_order_number),
                              status='success',response_json=VALUES(response_json),error_message=NULL",
                        [$integration,$recoveredCode,$number?:null,$clientId,$sellerCode,$userId,$total,
                         json_encode($payload,JSON_UNESCAPED_UNICODE),json_encode(['recovered'=>$verification],JSON_UNESCAPED_UNICODE)]);
                    return ['integration_code'=>$integration,'omie_order_code'=>$recoveredCode,'order_number'=>$number,'total'=>$total,'recovered'=>true];
                }
            }catch(\Throwable){}

            DB::exec("INSERT INTO order_creation_logs
                      (integration_code,client_id,seller_omie_code,created_by,total,status,request_json,error_message,created_at)
                      VALUES(?,?,?,?,?,'error',?,?,NOW())
                      ON DUPLICATE KEY UPDATE status='error',request_json=VALUES(request_json),error_message=VALUES(error_message)",
                [$integration,$clientId,$sellerCode,$userId,$total,json_encode($payload,JSON_UNESCAPED_UNICODE),mb_substr($e->getMessage(),0,4000)]);
            throw $e;
        }
    }
}
