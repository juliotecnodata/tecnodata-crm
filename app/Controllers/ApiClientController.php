<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class ApiClientController
{
    private array $allowedScopes=[
        'students:write',
        'enrollments:write',
        'enrollments:read',
        'certification:read',
        '*',
    ];

    private function admin(): void
    {
        Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->admin();

        View::render('api_clients/index',[
            'clients'=>Database::all(
                "SELECT id,name,scopes_json,enabled,last_used_at,created_at
                   FROM api_clients ORDER BY id DESC"
            ),
            'token'=>$_SESSION['new_api_token']??null,
            'allowedScopes'=>$this->allowedScopes,
            'message'=>$_SESSION['api_message']??null,
        ]);

        unset($_SESSION['new_api_token'],$_SESSION['api_message']);
    }

    public function store(): void
    {
        $this->admin();
        Csrf::verify();

        $name=trim((string)($_POST['name']??''));
        if($name==='') throw new \RuntimeException('Nome da integração é obrigatório.');

        $scopes=$this->scopesFromPost();
        if(!$scopes) throw new \RuntimeException('Selecione pelo menos um escopo.');

        $plain=$this->newToken();

        Database::execute(
            "INSERT INTO api_clients(name,token_hash,scopes_json,enabled,created_at)
             VALUES(?,?,?,1,?)",
            [
                $name,
                hash('sha256',$plain),
                json_encode($scopes,JSON_UNESCAPED_UNICODE),
                Clock::sql()
            ]
        );

        $id=Database::id();
        Audit::log('api_client.created','api_client',$id,['scopes'=>$scopes]);
        $_SESSION['new_api_token']=$plain;
        redirect('/admin/api-clients');
    }

    public function update(string $id): void
    {
        $this->admin();
        Csrf::verify();

        $client=$this->client((int)$id);
        $name=trim((string)($_POST['name']??$client['name']));
        $scopes=$this->scopesFromPost();

        if($name===''||!$scopes) throw new \RuntimeException('Nome e escopos são obrigatórios.');

        Database::execute(
            "UPDATE api_clients SET name=?,scopes_json=? WHERE id=?",
            [$name,json_encode($scopes,JSON_UNESCAPED_UNICODE),(int)$id]
        );

        Audit::log('api_client.updated','api_client',(int)$id,['scopes'=>$scopes]);
        $_SESSION['api_message']='Integração atualizada.';
        redirect('/admin/api-clients');
    }

    public function status(string $id): void
    {
        $this->admin();
        Csrf::verify();

        $client=$this->client((int)$id);
        $enabled=(int)($_POST['enabled']??0)?1:0;

        Database::execute("UPDATE api_clients SET enabled=? WHERE id=?",[$enabled,(int)$id]);
        Audit::log('api_client.status_changed','api_client',(int)$id,['enabled'=>$enabled]);

        $_SESSION['api_message']=$enabled?'Integração ativada.':'Integração desativada.';
        redirect('/admin/api-clients');
    }

    public function rotate(string $id): void
    {
        $this->admin();
        Csrf::verify();

        $this->client((int)$id);
        $plain=$this->newToken();

        Database::execute(
            "UPDATE api_clients SET token_hash=?,enabled=1 WHERE id=?",
            [hash('sha256',$plain),(int)$id]
        );

        Audit::log('api_client.token_rotated','api_client',(int)$id);
        $_SESSION['new_api_token']=$plain;
        $_SESSION['api_message']='Token substituído. O token anterior deixou de funcionar imediatamente.';
        redirect('/admin/api-clients');
    }

    private function scopesFromPost(): array
    {
        $raw=(array)($_POST['scopes']??[]);
        $out=[];
        foreach($raw as $scope){
            $scope=trim((string)$scope);
            if(in_array($scope,$this->allowedScopes,true))$out[$scope]=true;
        }
        return array_keys($out);
    }

    private function client(int $id): array
    {
        $c=Database::fetch("SELECT * FROM api_clients WHERE id=?",[$id]);
        if(!$c) throw new \RuntimeException('Integração não encontrada.');
        return $c;
    }

    private function newToken(): string
    {
        return 'tdlms_'.bin2hex(random_bytes(32));
    }
}
