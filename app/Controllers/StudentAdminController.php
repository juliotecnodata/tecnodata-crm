<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class StudentAdminController
{
    public function index(): void
    {
        Auth::requireAdmin();

        $students=Database::all(
            "SELECT u.*,COUNT(e.id) enrollments,
                    SUM(e.status='active') active_enrollments,
                    MAX(e.updated_at) last_activity
               FROM users u
               LEFT JOIN enrollments e ON e.user_id=u.id
              WHERE u.user_type='student'
              GROUP BY u.id
              ORDER BY u.id DESC
              LIMIT 2000"
        );

        View::render('students/index',[
            'students'=>$students,
            'message'=>$_SESSION['student_message']??null,
        ]);
        unset($_SESSION['student_message']);
    }


    public function show(string $id): void
    {
        Auth::requireAdmin();

        $student=Database::fetch(
            "SELECT * FROM users WHERE id=? AND user_type='student'",
            [(int)$id]
        );
        if(!$student) throw new \RuntimeException('Aluno não encontrado.');

        $enrollments=Database::all(
            "SELECT e.*,c.name course,c.code course_code
               FROM enrollments e
               JOIN courses c ON c.id=e.course_id
              WHERE e.user_id=?
              ORDER BY e.id DESC",
            [$student['id']]
        );

        $events=Database::all(
            "SELECT se.*,c.name course
               FROM study_events se
               JOIN enrollments e ON e.id=se.enrollment_id
               JOIN courses c ON c.id=e.course_id
              WHERE e.user_id=?
              ORDER BY se.id DESC
              LIMIT 50",
            [$student['id']]
        );

        View::render('students/show',[
            'student'=>$student,
            'enrollments'=>$enrollments,
            'events'=>$events,
            'message'=>$_SESSION['student_message']??null,
        ]);
        unset($_SESSION['student_message']);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $cpf=preg_replace('/\D+/','',(string)($_POST['cpf']??''));
        $name=trim((string)($_POST['name']??''));
        $email=trim((string)($_POST['email']??''));

        if(strlen($cpf)!==11 || $name===''){
            throw new \RuntimeException('Informe CPF com 11 dígitos e nome.');
        }

        if(Database::fetch("SELECT 1 FROM users WHERE cpf=?",[$cpf])){
            throw new \RuntimeException('CPF já cadastrado.');
        }

        Database::execute(
            "INSERT INTO users(
                user_type,cpf,username,name,email,password_hash,status,created_at,updated_at
             ) VALUES('student',?,?,?,?,?,'active',?,?)",
            [
                $cpf,
                $cpf,
                $name,
                $email!==''?$email:null,
                password_hash($cpf,PASSWORD_DEFAULT),
                Clock::sql(),
                Clock::sql()
            ]
        );

        $id=Database::id();
        Audit::log('student.created','user',$id);
        $_SESSION['student_message']='Aluno criado. Senha inicial: CPF.';
        redirect('/admin/students');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $student=Database::fetch(
            "SELECT * FROM users WHERE id=? AND user_type='student'",
            [(int)$id]
        );
        if(!$student) throw new \RuntimeException('Aluno não encontrado.');

        $name=trim((string)($_POST['name']??$student['name']));
        $email=trim((string)($_POST['email']??''));
        $status=(string)($_POST['status']??$student['status']);

        if($name==='') throw new \RuntimeException('Nome obrigatório.');
        if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)){
            throw new \RuntimeException('E-mail inválido.');
        }
        if(!in_array($status,['active','inactive'],true)){
            throw new \RuntimeException('Status inválido.');
        }

        Database::execute(
            "UPDATE users SET name=?,email=?,status=?,updated_at=? WHERE id=?",
            [$name,$email!==''?$email:null,$status,Clock::sql(),(int)$id]
        );

        Audit::log('student.updated','user',(int)$id);
        $_SESSION['student_message']='Aluno atualizado.';
        $_SESSION['student_message']='Aluno atualizado.';
        redirect('/admin/students/'.$id);
    }

    public function resetPassword(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $student=Database::fetch(
            "SELECT * FROM users WHERE id=? AND user_type='student'",
            [(int)$id]
        );
        if(!$student || !$student['cpf']) throw new \RuntimeException('Aluno/CPF inválido.');

        Database::execute(
            "UPDATE users SET password_hash=?,updated_at=? WHERE id=?",
            [password_hash($student['cpf'],PASSWORD_DEFAULT),Clock::sql(),(int)$id]
        );

        Audit::log('student.password_reset','user',(int)$id);
        $_SESSION['student_message']='Senha redefinida para o CPF.';
        redirect('/admin/students/'.$id);
    }
}
