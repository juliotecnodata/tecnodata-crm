<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\DB;

final class OrderIntegrationHealthService {
    public static function local(): array {
        $checks=[];
        $tables=[
            'products','sales_categories','sales_order_settings','order_creation_logs',
            'sales_payment_terms','tax_scenarios','stock_locations','payment_methods','document_types'
        ];
        $db=(string)DB::conn()->query('SELECT DATABASE()')->fetchColumn();

        foreach($tables as $table){
            $ok=(bool)DB::fetch("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1",[$db,$table]);
            $checks[]=['key'=>'table_'.$table,'label'=>'Tabela '.$table,'ok'=>$ok,'detail'=>$ok?'OK':'Ausente'];
        }

        $counts=[
            'products'=>'Produtos',
            'sales_categories'=>'Categorias',
            'sales_payment_terms'=>'Condições de pagamento',
            'tax_scenarios'=>'Cenários fiscais',
            'stock_locations'=>'Locais de estoque',
            'payment_methods'=>'Meios de pagamento',
            'document_types'=>'Tipos de documento',
        ];
        foreach($counts as $table=>$label){
            try{
                $n=(int)(DB::fetch("SELECT COUNT(*) n FROM {$table}")['n']??0);
                $checks[]=['key'=>'count_'.$table,'label'=>$label,'ok'=>$n>0,'detail'=>$n.' registro(s)'];
            }catch(\Throwable $e){
                $checks[]=['key'=>'count_'.$table,'label'=>$label,'ok'=>false,'detail'=>'Tabela indisponível'];
            }
        }

        try{
            $ready=SalesOrderService::readiness();
            $checks[]=[
                'key'=>'settings',
                'label'=>'Configuração do pedido',
                'ok'=>(bool)$ready['ready'],
                'detail'=>$ready['ready']?'Pronta':implode(', ',$ready['issues'])
            ];
        }catch(\Throwable $e){
            $checks[]=['key'=>'settings','label'=>'Configuração do pedido','ok'=>false,'detail'=>$e->getMessage()];
        }

        $ok=!array_filter($checks,fn($c)=>!$c['ok']);
        return ['ok'=>$ok,'checks'=>$checks];
    }

    public static function api(): array {
        $omie=new OmieClient();
        $tests=[
            'products'=>['Produtos','produtos','ListarProdutos',[
                'pagina'=>1,'registros_por_pagina'=>1,'apenas_importado_api'=>'N','filtrar_apenas_omiepdv'=>'N'
            ]],
            'payment_terms'=>['Condições de pagamento','sales_payment_terms','ListarFormasPagVendas',[
                'pagina'=>1,'registros_por_pagina'=>1
            ]],
            'tax_scenarios'=>['Cenários fiscais','tax_scenarios','ListarCenarios',[
                'nPagina'=>1,'nRegPorPagina'=>1,'cNome'=>''
            ]],
            'stock_locations'=>['Locais de estoque','stock_locations','ListarLocaisEstoque',[
                'nPagina'=>1,'nRegPorPagina'=>1
            ]],
            'payment_methods'=>['Meios de pagamento','payment_methods','ListarMeiosPagamento',[
                'codigo'=>''
            ]],
            'document_types'=>['Tipos de documento','document_types','PesquisarTipoDocumento',[
                'codigo'=>''
            ]],
        ];

        $result=[];
        foreach($tests as $key=>[$label,$endpoint,$call,$param]){
            try{
                $data=$omie->call($endpoint,$call,$param);
                $result[]=['key'=>$key,'label'=>$label,'ok'=>true,'detail'=>'API respondeu','sample'=>array_keys($data)];
            }catch(\Throwable $e){
                $result[]=['key'=>$key,'label'=>$label,'ok'=>false,'detail'=>$e->getMessage(),'sample'=>[]];
            }
        }
        return ['ok'=>!array_filter($result,fn($c)=>!$c['ok']),'checks'=>$result];
    }
}
