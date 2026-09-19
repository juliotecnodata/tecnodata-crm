<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class StudentAdminController
{
    public function index(): void
    {
        Auth::requireLogin();
        if(!Auth::isAdmin()){http_response_code(403);exit('Acesso negado.');}
        $students=Database::all("SELECT u.*,COUNT(e.id) enrollments FROM users u LEFT JOIN enrollments e ON e.user_id=u.id WHERE u.user_type='student' GROUP BY u.id ORDER BY u.id DESC LIMIT 500");
        View::render('students/index',['students'=>$students]);
    }
}
