<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\View;

final class AuthController
{
    public function form(): void
    {
        if (Auth::user()) redirect('/');
        View::render('auth/login', ['error'=>$_SESSION['error']??null], 'auth_layout');
        unset($_SESSION['error']);
    }

    public function login(): void
    {
        Csrf::verify();
        if (Auth::attempt(trim((string)($_POST['identifier']??'')), (string)($_POST['password']??''))) {
            redirect('/');
        }
        $_SESSION['error']='Usuário ou senha inválidos.';
        redirect('/login');
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        redirect('/login');
    }
}
