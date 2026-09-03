<?php
namespace Tecnodata\CRM\Services;

use Tecnodata\CRM\Core\DB;

final class NotificationService {
    public static function settings(int $userId): array {
        $defaults=[
            'user_id'=>$userId,
            'browser_enabled'=>!empty($GLOBALS['config']['alerts']['default_browser_enabled'])?1:0,
            'sound_enabled'=>!empty($GLOBALS['config']['alerts']['default_sound_enabled'])?1:0,
            'volume'=>(int)($GLOBALS['config']['alerts']['default_volume']??70),
            'pre_alert_minutes'=>(int)($GLOBALS['config']['alerts']['default_pre_alert_minutes']??10),
            'repeat_after_minutes'=>(int)($GLOBALS['config']['alerts']['default_repeat_after_minutes']??15),
        ];
        $row=DB::fetch("SELECT * FROM user_notification_settings WHERE user_id=?",[$userId]);
        return $row?array_merge($defaults,$row):$defaults;
    }

    public static function saveSettings(int $userId,array $input): array {
        $browser=!empty($input['browser_enabled'])?1:0;
        $sound=!empty($input['sound_enabled'])?1:0;
        $volume=max(0,min(100,(int)($input['volume']??70)));
        $pre=(int)($input['pre_alert_minutes']??10);
        $repeat=(int)($input['repeat_after_minutes']??15);
        if(!in_array($pre,[0,5,10,15,30],true))$pre=10;
        if(!in_array($repeat,[0,15,30,60],true))$repeat=15;

        DB::exec("INSERT INTO user_notification_settings
          (user_id,browser_enabled,sound_enabled,volume,pre_alert_minutes,repeat_after_minutes,updated_at)
          VALUES(?,?,?,?,?,?,NOW())
          ON DUPLICATE KEY UPDATE browser_enabled=VALUES(browser_enabled),sound_enabled=VALUES(sound_enabled),
          volume=VALUES(volume),pre_alert_minutes=VALUES(pre_alert_minutes),
          repeat_after_minutes=VALUES(repeat_after_minutes),updated_at=NOW()",
          [$userId,$browser,$sound,$volume,$pre,$repeat]);
        return self::settings($userId);
    }

    public static function poll(int $userId): array {
        $settings=self::settings($userId);
        $now=new \DateTimeImmutable('now');
        $pre=max(0,(int)$settings['pre_alert_minutes']);
        $repeat=max(0,(int)$settings['repeat_after_minutes']);
        $max=max(1,min(25,(int)($GLOBALS['config']['alerts']['max_events_per_poll']??10)));

        $candidates=DB::all("SELECT t.*,c.name client_name,c.uf,c.omie_code
          FROM tasks t JOIN clients c ON c.id=t.client_id
          WHERE t.user_id=? AND t.status='pending'
            AND t.due_at<=DATE_ADD(NOW(),INTERVAL ? MINUTE)
          ORDER BY t.due_at ASC LIMIT 40",[$userId,$pre]);

        $events=[];
        foreach($candidates as $task){
            if(count($events)>=$max)break;
            $due=new \DateTimeImmutable($task['due_at']);
            $stage=null;$column=null;

            if($due<=$now && empty($task['due_notified_at'])){
                $stage='due';$column='due_notified_at';
            }elseif($due<=$now && $repeat>0 && $due<=$now->modify("-{$repeat} minutes") && empty($task['reminder_notified_at'])){
                $stage='reminder';$column='reminder_notified_at';
            }elseif($pre>0 && $due>$now && $due<=$now->modify("+{$pre} minutes") && empty($task['pre_notified_at'])){
                $stage='pre';$column='pre_notified_at';
            }
            if(!$stage)continue;

            $claimed=DB::exec("UPDATE tasks SET {$column}=NOW() WHERE id=? AND {$column} IS NULL AND status='pending'",[$task['id']]);
            if(!$claimed)continue;

            $collection=str_starts_with((string)$task['title'],'Cobrança:');
            $url=APP_URL.'/'.($collection?'cobranca-cliente.php':'cliente.php').'?id='.(int)$task['client_id'];
            $title=match($stage){
                'pre'=>'Retorno em '.$pre.' minutos',
                'due'=>'Retorno agendado agora',
                default=>'Retorno ainda pendente'
            };
            $events[]=[
                'task_id'=>(int)$task['id'],'stage'=>$stage,'title'=>$title,
                'body'=>$task['client_name'].' • '.$task['title'],
                'client'=>$task['client_name'],'task_title'=>$task['title'],
                'due_at'=>$task['due_at'],'url'=>$url,'collection'=>$collection
            ];
        }

        $dueCount=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at<=NOW()",[$userId])['n']??0);
        $todayCount=(int)(DB::fetch("SELECT COUNT(*) n FROM tasks WHERE user_id=? AND status='pending' AND due_at>=CURDATE() AND due_at<DATE_ADD(CURDATE(),INTERVAL 1 DAY)",[$userId])['n']??0);

        $items=DB::all("SELECT t.id,t.client_id,t.title,t.due_at,c.name client_name,c.uf,c.omie_code
          FROM tasks t JOIN clients c ON c.id=t.client_id
          WHERE t.user_id=? AND t.status='pending'
          ORDER BY (t.due_at<=NOW()) DESC,t.due_at ASC LIMIT 8",[$userId]);
        foreach($items as &$item){
            $item['is_late']=strtotime($item['due_at'])<=time();
            $item['collection']=str_starts_with((string)$item['title'],'Cobrança:');
            $item['url']=APP_URL.'/'.($item['collection']?'cobranca-cliente.php':'cliente.php').'?id='.(int)$item['client_id'];
            $item['due_label']=date('d/m H:i',strtotime($item['due_at']));
        }unset($item);

        return [
            'events'=>$events,'items'=>$items,
            'counts'=>['due'=>$dueCount,'today'=>$todayCount,'pending'=>count($items)],
            'settings'=>[
                'browser_enabled'=>(bool)$settings['browser_enabled'],
                'sound_enabled'=>(bool)$settings['sound_enabled'],
                'volume'=>(int)$settings['volume'],
                'pre_alert_minutes'=>(int)$settings['pre_alert_minutes'],
                'repeat_after_minutes'=>(int)$settings['repeat_after_minutes'],
            ]
        ];
    }
}
