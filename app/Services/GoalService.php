<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\DB;

final class GoalService {
    public static function sellerMonth(string $sellerCode, string $month): array {
        $active=DB::fetch("SELECT id,goal_mode FROM sellers WHERE omie_code=? AND active=1",[$sellerCode]);
        $mode=(string)($active['goal_mode']??'collection');
        $hasSales=self::hasSales($mode);$hasCollection=self::hasCollection($mode);
        $goal=$active?DB::fetch("SELECT * FROM seller_goals WHERE seller_omie_code=? AND month_ref=?",[$sellerCode,$month]):null;
        $start=$month.'-01'; $end=date('Y-m-t',strtotime($start));
        $orders=$active&&$hasSales?DB::all("SELECT total,status,raw_json FROM orders WHERE seller_omie_code=? AND order_date BETWEEN ? AND ?",[$sellerCode,$start,$end]):[];
        $productRealized=0;
        foreach($orders as $o) if(self::isOrderCounted($o)) $productRealized+=(float)$o['total'];
        $services=$active&&$hasSales?DB::all("SELECT total,status,stage_code FROM service_orders WHERE seller_omie_code=? AND inclusion_date BETWEEN ? AND ?",[$sellerCode,$start,$end]):[];
        $serviceRealized=0;
        foreach($services as $service) if(self::isServiceOrderCounted($service)) $serviceRealized+=(float)$service['total'];
        $realized=$productRealized+$serviceRealized;
        $days=self::workingDaysRemaining($month);
        $m1=(float)($goal['goal1']??0);
        $m2=(float)($goal['goal2']??0);
        $m3=$mode==='sales'?(float)($goal['goal3']??0):0.0;
        $current=$m1;
        if($m1>0 && $realized >= $m1) $current=$m2 ?: $m1;
        if($mode==='sales'&&$m2>0&&$realized >= $m2) $current=$m3 ?: $m2;
        $missing=max(0,$current-$realized);
        $collection=self::collectionMonth($sellerCode,$month,$goal,$active!==null&&$hasCollection);
        return compact('goal','realized','days','m1','m2','m3','current','missing')+[
            'goal_mode'=>$mode,'has_sales'=>$hasSales,'has_collection'=>$hasCollection,
            'product_realized'=>$productRealized,
            'service_realized'=>$serviceRealized,
            'daily_need'=>$days?($missing/$days):$missing,
            'percent'=>$current?($realized/$current*100):0,
        ]+$collection;
    }
    public static function generalMonth(string $month): array {
        $g=DB::fetch("SELECT * FROM monthly_goals WHERE month_ref=?",[$month]);
        $start=$month.'-01'; $end=date('Y-m-t',strtotime($start));
        $orders=DB::all("SELECT o.total,o.status,o.raw_json FROM orders o INNER JOIN sellers s ON s.omie_code=o.seller_omie_code AND s.active=1 AND s.goal_mode IN('sales','sales_collection') WHERE o.order_date BETWEEN ? AND ?",[$start,$end]);
        $productRealized=0;
        foreach($orders as $o) if(self::isOrderCounted($o)) $productRealized+=(float)$o['total'];
        $services=DB::all("SELECT so.total,so.status,so.stage_code FROM service_orders so INNER JOIN sellers s ON s.omie_code=so.seller_omie_code AND s.active=1 AND s.goal_mode IN('sales','sales_collection') WHERE so.inclusion_date BETWEEN ? AND ?",[$start,$end]);
        $serviceRealized=0;
        foreach($services as $service) if(self::isServiceOrderCounted($service)) $serviceRealized+=(float)$service['total'];
        $collectionRealized=CollectionService::recoveredMonth($month);
        $salesRealized=$productRealized+$serviceRealized;
        // Na gestão Tecnodata, valores recuperados pela cobrança também compõem o atingimento geral.
        $realized=$salesRealized+$collectionRealized;
        $goal=(float)($g['general_goal']??0);
        $missing=max(0,$goal-$realized);
        $days=self::workingDaysRemaining($month);
        return ['goal'=>$goal,'realized'=>$realized,'sales_realized'=>$salesRealized,
            'product_realized'=>$productRealized,'service_realized'=>$serviceRealized,
            'collection_realized'=>$collectionRealized,'missing'=>$missing,'days'=>$days,
            'daily_need'=>$days?$missing/$days:$missing,'percent'=>$goal?$realized/$goal*100:0];
    }

    private static function collectionMonth(string $sellerCode,string $month,?array $goal,bool $active): array {
        $collectionGoal=(float)($goal['collection_goal']??0);
        $contactGoal=(int)($goal['debtor_contact_goal']??0);
        if(!$active){
            return ['collection_goal'=>$collectionGoal,'collection_realized'=>0.0,'collection_missing'=>$collectionGoal,
                'collection_percent'=>0.0,'contact_goal'=>$contactGoal,'debtors_worked'=>0,'contact_percent'=>0.0,
                'debtor_count'=>0,'debtor_amount'=>0.0];
        }
        $start=$month.'-01';$end=date('Y-m-t',strtotime($start));
        $actions=DB::fetch("SELECT COALESCE(SUM(CASE WHEN action_type='payment' THEN amount ELSE 0 END),0) recovered,
                           COUNT(DISTINCT client_id) debtors_worked
                           FROM collection_actions
                           WHERE seller_omie_code=? AND deleted_at IS NULL AND DATE(created_at) BETWEEN ? AND ?",
            [$sellerCode,$start,$end])??[];
        $portfolio=DB::fetch("SELECT COUNT(DISTINCT fm.client_omie_code) debtor_count,COALESCE(SUM(fm.amount),0) debtor_amount
                             FROM financial_movements fm INNER JOIN financial_accounts fa ON fa.omie_code=fm.account_omie_code AND fa.selected=1
                             WHERE UPPER(fm.status) IN('ATRASADO','PAGTO_PARCIAL')")??[];
        $recovered=(float)($actions['recovered']??0);
        $worked=(int)($actions['debtors_worked']??0);
        return [
            'collection_goal'=>$collectionGoal,
            'collection_realized'=>$recovered,
            'collection_missing'=>max(0,$collectionGoal-$recovered),
            'collection_percent'=>$collectionGoal?($recovered/$collectionGoal*100):0,
            'contact_goal'=>$contactGoal,
            'debtors_worked'=>$worked,
            'contact_percent'=>$contactGoal?($worked/$contactGoal*100):0,
            'debtor_count'=>(int)($portfolio['debtor_count']??0),
            'debtor_amount'=>(float)($portfolio['debtor_amount']??0),
        ];
    }

    public static function orderStageCode(array $order): string {
        $raw=is_array($order['raw_json']??null)?$order['raw_json']:json_decode((string)($order['raw_json']??''),true);
        if(!is_array($raw))return '';$code=(string)($raw['cabecalho']['etapa']??'');
        return $code===''?'':str_pad($code,2,'0',STR_PAD_LEFT);
    }

    public static function isOrderCounted(array $order): bool {
        $ignored=array_map('mb_strtoupper',$GLOBALS['config']['omie']['ignored_order_statuses']??[]);
        if(in_array(mb_strtoupper((string)($order['status']??'')),$ignored,true))return false;
        $budgetStages=array_map(fn($code)=>str_pad((string)$code,2,'0',STR_PAD_LEFT),$GLOBALS['config']['omie']['order_budget_stage_codes']??['00']);
        return !in_array(self::orderStageCode($order),$budgetStages,true);
    }
    public static function isServiceOrderCounted(array $service): bool {
        $status=mb_strtoupper((string)($service['status']??''));
        if(in_array($status,['CANCELADO','CANCELADA','DEVOLVIDO','DENEGADO'],true))return false;
        $stage=str_pad((string)($service['stage_code']??''),2,'0',STR_PAD_LEFT);
        return $stage!=='00';
    }
    public static function hasSales(string $mode): bool {
        return in_array($mode,['sales','sales_collection'],true);
    }
    public static function hasCollection(string $mode): bool {
        return in_array($mode,['collection','sales_collection'],true);
    }
    private static function workingDaysRemaining(string $month): int {
        $today=new \DateTime('today');$start=new \DateTime($month.'-01');$end=(clone $start)->modify('last day of this month');
        if($end<$today)return 0;$d=$start>$today?$start:$today;$n=0;
        for($x=(clone $d);$x<=$end;$x->modify('+1 day')) if((int)$x->format('N')<=5)$n++;
        return max(0,$n);
    }
}
