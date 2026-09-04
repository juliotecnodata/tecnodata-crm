<?php
namespace Tecnodata\CRM\Services;

use RuntimeException;
use Tecnodata\CRM\Core\DB;

final class SalesOrderService {
    public static function settings(): array {
        $row=DB::fetch("SELECT * FROM sales_order_settings WHERE id=1");
        return $row?:[
            'id'=>1,'default_stage_code'=>null,'default_category_code'=>null,'default_account_code'=>null,
            'installment_code'=>'999','consumer_final'=>'S','send_email'=>'N','freight_mode'=>'9'
        ];
    }

    public static function saveSettings(array $data,int $userId): void {
        $stage=trim((string)($data['default_stage_code']??''));
        $category=trim((string)($data['default_category_code']??''));
        $account=trim((string)($data['default_account_code']??''));
        $installment=trim((string)($data['installment_code']??'999'));
        $consumer=mb_strtoupper((string)($data['consumer_final']??'S'))==='N'?'N':'S';
        $email=mb_strtoupper((string)($data['send_email']??'N'))==='S'?'S':'N';
        $freight=trim((string)($data['freight_mode']??'9'))?:'9';

        if($stage!==''&&!DB::fetch("SELECT 1 FROM order_stage_catalog WHERE stage_code=? AND active=1",[$stage]))
            throw new RuntimeException('Etapa padrão inválida.');
        if($category!==''&&!DB::fetch("SELECT 1 FROM sales_categories WHERE code=? AND active=1",[$category]))
            throw new RuntimeException('Categoria padrão inválida.');
        if($account!==''&&!DB::fetch("SELECT 1 FROM financial_accounts WHERE omie_code=? AND active=1",[$account]))
            throw new RuntimeException('Conta corrente padrão inválida.');

        DB::exec("INSERT INTO sales_order_settings
                  (id,default_stage_code,default_category_code,default_account_code,installment_code,consumer_final,send_email,freight_mode,updated_by,updated_at)
                  VALUES(1,?,?,?,?,?,?,?,?,NOW())
                  ON DUPLICATE KEY UPDATE default_stage_code=VALUES(default_stage_code),default_category_code=VALUES(default_category_code),
                  default_account_code=VALUES(default_account_code),installment_code=VALUES(installment_code),
                  consumer_final=VALUES(consumer_final),send_email=VALUES(send_email),freight_mode=VALUES(freight_mode),
                  updated_by=VALUES(updated_by),updated_at=NOW()",
            [$stage?:null,$category?:null,$account?:null,$installment?:null,$consumer,$email,$freight,$userId]);
    }

    public static function readiness(): array {
        $s=self::settings();
        $issues=[];
        if(empty($s['default_stage_code']))$issues[]='Etapa padrão';
        if(empty($s['default_category_code']))$issues[]='Categoria padrão';
        if(empty($s['default_account_code']))$issues[]='Conta corrente padrão';
        if(!DB::fetch("SELECT 1 FROM products WHERE active=1 LIMIT 1"))$issues[]='Catálogo de produtos';
        return ['ready'=>!$issues,'issues'=>$issues,'settings'=>$s];
    }

    public static function create(array $input,int $userId,?string $forcedSellerCode=null): array {
        $ready=self::readiness();
        if(!$ready['ready'])throw new RuntimeException('Pedidos ainda não configurados: '.implode(', ',$ready['issues']).'.');

        $clientId=(int)($input['client_id']??0);
        $client=DB::fetch("SELECT * FROM clients WHERE id=? AND active=1",[$clientId]);
        if(!$client)throw new RuntimeException('Cliente inválido.');

        if($forcedSellerCode!==null && $forcedSellerCode!==''){
            $month=date('Y-m');
            $owner=DB::fetch("SELECT CASE WHEN pa.id IS NOT NULL THEN pa.seller_omie_code ELSE m.seller_omie_code END seller_code
                              FROM clients c
                              LEFT JOIN client_metrics m ON m.client_id=c.id
                              LEFT JOIN client_portfolio_assignments pa ON pa.client_id=c.id AND pa.month_ref=?
                              WHERE c.id=?",[$month,$clientId]);
            if((string)($owner['seller_code']??'')!==$forcedSellerCode)
                throw new RuntimeException('Este cliente não pertence à sua carteira comercial do mês.');
        }

        $items=is_array($input['items']??null)?$input['items']:[];
        if(!$items)throw new RuntimeException('Inclua ao menos um produto.');

        $sellerCode=trim((string)($forcedSellerCode??$input['seller_omie_code']??''));
        if($sellerCode!==''&&!DB::fetch("SELECT 1 FROM sellers WHERE omie_code=? AND active=1",[$sellerCode]))
            throw new RuntimeException('Vendedor inválido.');

        $settings=$ready['settings'];
        $stage=trim((string)($input['stage_code']??$settings['default_stage_code']));
        $category=trim((string)($input['category_code']??$settings['default_category_code']));
        $account=trim((string)($input['account_code']??$settings['default_account_code']));
        $forecast=trim((string)($input['forecast_date']??date('Y-m-d')));
        $forecastTs=strtotime($forecast);
        if(!$forecastTs)throw new RuntimeException('Data de previsão inválida.');

        $integration='TDCRM-'.date('Ymd-His').'-'.strtoupper(substr(bin2hex(random_bytes(4)),0,8));
        $det=[];$total=0.0;$line=0;
        foreach($items as $item){
            $productId=(int)($item['product_id']??0);
            $product=DB::fetch("SELECT * FROM products WHERE id=? AND active=1",[$productId]);
            if(!$product)throw new RuntimeException('Um dos produtos selecionados não é válido.');

            $qty=(float)str_replace(',','.',(string)($item['quantity']??0));
            // Vendedor usa preço-base e desconto zero definidos pela empresa.
            // Supervisor/Admin podem ajustar no pedido quando necessário.
            if($forcedSellerCode!==null){
                $price=(float)$product['unit_price'];
                $discount=0.0;
            }else{
                $price=(float)str_replace(',','.',(string)($item['unit_price']??$product['unit_price']));
                $discount=(float)str_replace(',','.',(string)($item['discount']??0));
            }
            if($qty<=0)throw new RuntimeException('Quantidade inválida para '.$product['description'].'.');
            if($price<0)throw new RuntimeException('Preço inválido para '.$product['description'].'.');
            if($discount<0)$discount=0;
            $lineTotal=max(0,$qty*$price-$discount);
            $total+=$lineTotal;
            $line++;

            $prod=[
                'codigo_produto'=>is_numeric($product['omie_code'])?(int)$product['omie_code']:$product['omie_code'],
                'descricao'=>(string)$product['description'],
                'quantidade'=>$qty,
                'unidade'=>(string)($product['unit']?:'UN'),
                'valor_unitario'=>$price,
                'tipo_desconto'=>'V',
                'valor_desconto'=>$discount,
            ];
            if(!empty($product['ncm']))$prod['ncm']=(string)$product['ncm'];
            if(!empty($product['cfop']))$prod['cfop']=(string)$product['cfop'];

            $det[]=[
                'ide'=>['codigo_item_integracao'=>$integration.'-'.$line],
                'produto'=>$prod,
            ];
        }
        if($total<=0)throw new RuntimeException('O valor total do pedido precisa ser maior que zero.');

        $cab=[
            'codigo_pedido_integracao'=>$integration,
            'data_previsao'=>date('d/m/Y',$forecastTs),
            'etapa'=>$stage,
            'quantidade_itens'=>count($det),
        ];
        if(is_numeric($client['omie_code']))$cab['codigo_cliente']=(int)$client['omie_code'];
        else $cab['codigo_cliente_integracao']=(string)$client['omie_code'];
        if(!empty($settings['installment_code']))$cab['codigo_parcela']=(string)$settings['installment_code'];

        $additional=[
            'codigo_categoria'=>$category,
            'codigo_conta_corrente'=>is_numeric($account)?(int)$account:$account,
            'consumidor_final'=>(string)$settings['consumer_final'],
            'enviar_email'=>(string)$settings['send_email'],
        ];
        if($sellerCode!=='')$additional['codVend']=is_numeric($sellerCode)?(int)$sellerCode:$sellerCode;

        $payload=[
            'cabecalho'=>$cab,
            'det'=>$det,
            'frete'=>['modalidade'=>(string)$settings['freight_mode']],
            'informacoes_adicionais'=>$additional,
        ];
        $notes=trim((string)($input['notes']??''));
        if($notes!=='')$payload['observacoes']=['obs_venda'=>$notes];

        $clientApi=new OmieClient();
        try{
            $response=$clientApi->call('pedidos','IncluirPedido',$payload);
            $omieCode=(string)($response['codigo_pedido']??$response['nCodPed']??'');
            $number=(string)($response['numero_pedido']??'');
            DB::exec("INSERT INTO order_creation_logs
                      (integration_code,omie_order_code,omie_order_number,client_id,seller_omie_code,created_by,total,status,request_json,response_json,created_at)
                      VALUES(?,?,?,?,?,?,?,'success',?,?,NOW())",
                [$integration,$omieCode?:null,$number?:null,$clientId,$sellerCode?:null,$userId,$total,
                 json_encode($payload,JSON_UNESCAPED_UNICODE),json_encode($response,JSON_UNESCAPED_UNICODE)]);

            if($omieCode!==''){
                $stageName=DB::fetch("SELECT stage_name FROM order_stage_catalog WHERE stage_code=?",[$stage]);
                DB::exec("INSERT INTO orders
                          (omie_order_code,client_omie_code,seller_omie_code,order_date,total,status,stage_code,stage_name,raw_json,updated_at)
                          VALUES(?,?,?,?,?,'ATIVO',?,?,?,NOW())
                          ON DUPLICATE KEY UPDATE client_omie_code=VALUES(client_omie_code),seller_omie_code=VALUES(seller_omie_code),
                          order_date=VALUES(order_date),total=VALUES(total),stage_code=VALUES(stage_code),stage_name=VALUES(stage_name),
                          raw_json=VALUES(raw_json),updated_at=NOW()",
                    [$omieCode,(string)$client['omie_code'],$sellerCode?:null,date('Y-m-d'),$total,$stage,
                     $stageName['stage_name']??('Etapa '.$stage),
                     json_encode(['request'=>$payload,'response'=>$response],JSON_UNESCAPED_UNICODE)]);
            }

            return [
                'integration_code'=>$integration,
                'omie_order_code'=>$omieCode,
                'order_number'=>$number,
                'total'=>$total,
                'response'=>$response,
            ];
        }catch(\Throwable $e){
            DB::exec("INSERT INTO order_creation_logs
                      (integration_code,client_id,seller_omie_code,created_by,total,status,request_json,error_message,created_at)
                      VALUES(?,?,?,?,?,'error',?,?,NOW())",
                [$integration,$clientId,$sellerCode?:null,$userId,$total,json_encode($payload,JSON_UNESCAPED_UNICODE),mb_substr($e->getMessage(),0,4000)]);
            throw $e;
        }
    }
}
