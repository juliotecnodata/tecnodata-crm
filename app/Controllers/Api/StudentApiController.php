<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\ApiAuth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class StudentApiController
{
    public function upsert(): void
    {
        $client=ApiAuth::client('students:write');
        $in=ApiAuth::input();
        $cpf=preg_replace('/\D+/','',(string)($in['cpf']??''));
        if(strlen($cpf)!==11) ApiAuth::deny(422,'INVALID_CPF','CPF deve conter 11 dígitos.');
        $user=Database::fetch("SELECT * FROM users WHERE cpf=?",[$cpf]);
        $created=false;
        if($user){
            Database::execute("UPDATE users SET name=?,email=?,updated_at=? WHERE id=?",[(string)($in['name']??$user['name']),(string)($in['email']??$user['email']),Clock::sql(),$user['id']]);
            $id=(int)$user['id'];
        } else {
            $password=(string)($in['initial_password']??($cpf));
            Database::execute("INSERT INTO users(user_type,cpf,username,name,email,password_hash,status,created_at,updated_at) VALUES('student',?,?,?,?,?,'active',?,?)",[$cpf,$cpf,(string)($in['name']??'Aluno'),(string)($in['email']??''),password_hash($password,PASSWORD_DEFAULT),Clock::sql(),Clock::sql()]);
            $id=Database::id(); $created=true;
        }
        ApiAuth::json(['success'=>true,'student'=>['id'=>$id,'created'=>$created,'cpf'=>$cpf]]);
    }
}
