<?php
namespace Tecnodata\CRM\Services;

final class PurchaseCycleService {
    public static function analyze(?string $lastPurchase, mixed $avgInterval, ?int $daysWithout=null): array {
        $interval=(float)$avgInterval;
        $days=$daysWithout;
        if($days===null && $lastPurchase){
            $ts=strtotime($lastPurchase);
            if($ts)$days=max(0,(int)floor((time()-$ts)/86400));
        }

        if(!$lastPurchase || $interval<=0){
            return [
                'status'=>'unknown',
                'label'=>'Sem ciclo definido',
                'tone'=>'muted',
                'days'=>$days,
                'interval'=>null,
                'expected_date'=>null,
                'delta'=>null,
                'progress'=>0,
                'hint'=>'Histórico insuficiente para estimar a próxima compra.'
            ];
        }

        $interval=max(1,(int)round($interval));
        $expected=date('Y-m-d',strtotime($lastPurchase.' +'.$interval.' days'));
        $delta=(int)floor((strtotime($expected)-strtotime(date('Y-m-d')))/86400);
        $ratio=$days!==null?$days/$interval:0;

        if($delta>max(7,(int)round($interval*.2))){
            $status='on_track';$label='Dentro do ciclo';$tone='success';
            $hint='Ainda está dentro do intervalo normal de recompra.';
        }elseif($delta>=0){
            $status='due_soon';$label='Hora de aproximar';$tone='info';
            $hint='A janela provável de recompra está próxima.';
        }elseif(abs($delta)<=max(10,(int)round($interval*.25))){
            $status='due';$label='Janela de compra';$tone='warning';
            $hint='Cliente está na janela estimada de recompra.';
        }else{
            $status='overdue';$label='Ciclo vencido';$tone='danger';
            $hint='A recompra esperada já passou; priorize o contato.';
        }

        return [
            'status'=>$status,
            'label'=>$label,
            'tone'=>$tone,
            'days'=>$days,
            'interval'=>$interval,
            'expected_date'=>$expected,
            'delta'=>$delta,
            'progress'=>min(100,max(0,(int)round($ratio*100))),
            'hint'=>$hint
        ];
    }

    public static function priorityScore(array $metric): int {
        $cycle=self::analyze(
            $metric['last_purchase_at']??null,
            $metric['avg_interval_days']??null,
            isset($metric['days_without_purchase'])?(int)$metric['days_without_purchase']:null
        );
        $score=0;
        $score+=match($cycle['status']){
            'overdue'=>45,
            'due'=>35,
            'due_soon'=>20,
            'on_track'=>5,
            default=>10,
        };
        $score+=match((string)($metric['commercial_status']??'')){
            'reactivate'=>35,
            'attention'=>20,
            default=>0,
        };
        if((float)($metric['overdue_amount']??0)>0)$score+=15;
        if(empty($metric['last_contact']))$score+=10;
        return $score;
    }
}
