<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\BiometricDatabase;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\MigrationService;

final class DashboardController
{
    public function index(): void
    {
        $user=Auth::requireLogin();

        if(!Auth::isAdmin((int)$user['id'])){
            $teaching=Database::fetch(
                "SELECT 1 FROM user_role_assignments ura
                 JOIN roles r ON r.id=ura.role_id
                 WHERE ura.user_id=? AND ura.context_type='course'
                   AND r.slug IN('manager','coordinator','teacher_editor','teacher','tutor','support')
                 LIMIT 1",
                [$user['id']]
            );
            redirect($teaching?'/teaching':'/student');
        }

        $stats=[
            'students'=>(int)(Database::fetch("SELECT COUNT(*) c FROM users WHERE user_type='student'")['c']??0),
            'courses'=>(int)(Database::fetch("SELECT COUNT(*) c FROM courses WHERE status<>'archived'")['c']??0),
            'enrollments'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE status='active'")['c']??0),
            'studying_today'=>(int)(Database::fetch("SELECT COUNT(DISTINCT enrollment_id) c FROM study_events WHERE DATE(created_at)=CURDATE()")['c']??0),
            'completed_today'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE DATE(completed_at)=CURDATE()")['c']??0),
            'imports_pending'=>(int)(Database::fetch("SELECT COUNT(*) c FROM imports WHERE status IN('analyzing','ready','blocked')")['c']??0),
            'api_today'=>(int)(Database::fetch("SELECT COUNT(*) c FROM api_requests WHERE DATE(created_at)=CURDATE()")['c']??0),
        ];

        $recent=Database::all(
            "SELECT e.*,u.name student,c.name course
               FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id
              ORDER BY e.id DESC LIMIT 10"
        );

        $courseStats=Database::all(
            "SELECT c.name,c.code,COUNT(e.id) enrollments,SUM(e.status='completed') completed,
                    ROUND(AVG(e.progress_percent),1) progress
               FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id
              WHERE c.status<>'archived' GROUP BY c.id ORDER BY enrollments DESC LIMIT 8"
        );

        $health=[
            'db'=>true,
            'biometric'=>BiometricDatabase::configured()?(BiometricDatabase::ping()?'ok':'error'):'not_configured',
            'migrations'=>count((new MigrationService())->pending()),
            'environment'=>(string)envv('APP_ENV','production'),
        ];

        View::render('dashboard',compact('user','stats','recent','courseStats','health'));
    }
}
