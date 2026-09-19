<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class Audit
{
    public static function log(string $event, string $entityType = '', ?int $entityId = null, array $data = []): void
    {
        Database::execute(
            "INSERT INTO audit_events(user_id,event_type,entity_type,entity_id,ip_address,user_agent,data_json,created_at)
             VALUES(?,?,?,?,?,?,?,?)",
            [Auth::id(),$event,$entityType,$entityId,$_SERVER['REMOTE_ADDR']??null,substr($_SERVER['HTTP_USER_AGENT']??'',0,500),json_encode($data,JSON_UNESCAPED_UNICODE),Clock::sql()]
        );
    }
}
