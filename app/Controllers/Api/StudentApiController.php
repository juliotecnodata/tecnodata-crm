<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\ApiAuth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class StudentApiController
{
    public function upsert(): void
    {
        ApiAuth::client('students:write');
        $in = ApiAuth::input();

        $cpf = preg_replace('/\D+/', '', (string) ($in['cpf'] ?? ''));
        if (strlen($cpf) !== 11) {
            ApiAuth::deny(422, 'INVALID_CPF', 'CPF deve conter 11 dígitos.');
        }

        $name = trim((string) ($in['name'] ?? ''));
        $email = trim((string) ($in['email'] ?? ''));

        $user = Database::fetch("SELECT * FROM users WHERE cpf=?", [$cpf]);
        $created = false;

        if ($user) {
            Database::execute(
                "UPDATE users
                    SET name=COALESCE(NULLIF(?,''),name),
                        email=CASE WHEN ?<>'' THEN ? ELSE email END,
                        updated_at=?
                  WHERE id=?",
                [$name, $email, $email, Clock::sql(), $user['id']]
            );
            $id = (int) $user['id'];
        } else {
            if ($name === '') {
                ApiAuth::deny(422, 'NAME_REQUIRED', 'Nome é obrigatório para criar o aluno.');
            }

            $password = (string) ($in['initial_password'] ?? $cpf);

            Database::execute(
                "INSERT INTO users(
                    user_type,cpf,username,name,email,password_hash,status,created_at,updated_at
                 ) VALUES('student',?,?,?,?,?,'active',?,?)",
                [
                    $cpf,
                    $cpf,
                    $name,
                    $email !== '' ? $email : null,
                    password_hash($password, PASSWORD_DEFAULT),
                    Clock::sql(),
                    Clock::sql(),
                ]
            );

            $id = Database::id();
            $created = true;
        }

        $fresh = Database::fetch(
            "SELECT id,cpf,name,email,status FROM users WHERE id=?",
            [$id]
        );

        ApiAuth::json([
            'success' => true,
            'student' => [
                'id' => (int) $fresh['id'],
                'created' => $created,
                'cpf' => $fresh['cpf'],
                'name' => $fresh['name'],
                'email' => $fresh['email'],
                'status' => $fresh['status'],
            ],
        ]);
    }
}
