<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class DashboardController
{
    public function index(): void
    {
        $user=Auth::requireLogin();
        if (!Auth::isAdmin()) {
            redirect('/student');
        }
        $stats=[
            'students'=>(int)(Database::fetch("SELECT COUNT(*) c FROM users WHERE user_type='student'")['c']??0),
            'courses'=>(int)(Database::fetch("SELECT COUNT(*) c FROM courses WHERE status<>'archived'")['c']??0),
            'enrollments'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE status='active'")['c']??0),
            'completed_today'=>(int)(Database::fetch("SELECT COUNT(*) c FROM enrollments WHERE DATE(completed_at)=CURDATE()")['c']??0),
        ];
        $recent=Database::all("SELECT e.*,u.name student,c.name course FROM enrollments e JOIN users u ON u.id=e.user_id JOIN courses c ON c.id=e.course_id ORDER BY e.id DESC LIMIT 8");
        View::render('dashboard',['user'=>$user,'stats'=>$stats,'recent'=>$recent]);
    }
}
