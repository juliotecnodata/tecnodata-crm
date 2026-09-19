<?php
namespace Tecnodata\Lms\Controllers;

use DateTimeImmutable;
use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;

final class StudyController
{
    public function heartbeat(string $enrollmentId): void
    {
        $u=Auth::requireLogin();
        Csrf::verify();

        $en=Database::fetch(
            "SELECT * FROM enrollments WHERE id=? AND user_id=? AND status IN('active','completed')",
            [(int)$enrollmentId,$u['id']]
        );
        if(!$en){
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success'=>false]);
            exit;
        }

        $key='study_session_'.(int)$enrollmentId;
        $token=(string)($_SESSION[$key]??'');
        $session=$token!==''?Database::fetch("SELECT * FROM study_sessions WHERE session_token=?",[$token]):null;
        $now=Clock::sql();

        if($session){
            $last=new DateTimeImmutable($session['last_seen_at']);
            $raw=max(0,Clock::now()->getTimestamp()-$last->getTimestamp());

            if($raw>900){
                Database::execute("UPDATE study_sessions SET ended_at=? WHERE id=?",[$now,$session['id']]);
                $session=null;
            }else{
                $delta=min(90,$raw);
                Database::execute(
                    "UPDATE study_sessions SET active_seconds=active_seconds+?,last_seen_at=? WHERE id=?",
                    [$delta,$now,$session['id']]
                );
            }
        }

        if(!$session){
            $token=bin2hex(random_bytes(32));
            $_SESSION[$key]=$token;
            Database::execute(
                "INSERT INTO study_sessions(
                    enrollment_id,session_token,ip_address,user_agent,started_at,last_seen_at,active_seconds
                 ) VALUES(?,?,?,?,?,?,0)",
                [
                    $en['id'],$token,(string)($_SERVER['REMOTE_ADDR']??''),
                    substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500),$now,$now
                ]
            );
        }

        $total=(int)(Database::fetch(
            "SELECT COALESCE(SUM(active_seconds),0) s FROM study_sessions WHERE enrollment_id=?",
            [$en['id']]
        )['s']??0);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>true,'active_seconds'=>$total]);
        exit;
    }
}
