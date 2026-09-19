<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class ApiClientController
{
    private function admin(): void { Auth::requireLogin(); if(!Auth::isAdmin()){http_response_code(403);exit('Acesso negado.');} }

    public function index(): void
    {
        $this->admin();
        View::render('api_clients/index',['clients'=>Database::all("SELECT id,name,scopes_json,enabled,last_used_at,created_at FROM api_clients ORDER BY id DESC"),'token'=>$_SESSION['new_api_token']??null]);
        unset($_SESSION['new_api_token']);
    }

    public function store(): void
    {
        $this->admin(); Csrf::verify();
        $plain='tdlms_'.bin2hex(random_bytes(24));
        $scopes=array_values(array_filter(array_map('trim',explode(',',(string)($_POST['scopes']??'')))));
        Database::execute("INSERT INTO api_clients(name,token_hash,scopes_json,enabled,created_at) VALUES(?,?,?,1,?)",[trim($_POST['name']??'Integração'),hash('sha256',$plain),json_encode($scopes),Clock::sql()]);
        $_SESSION['new_api_token']=$plain;
        redirect('/admin/api-clients');
    }
}
