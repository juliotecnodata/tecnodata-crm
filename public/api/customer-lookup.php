<?php
require dirname(__DIR__,2).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;

Auth::requireLogin();
header('Content-Type: application/json; charset=utf-8');

$type=(string)($_GET['type']??'');
$value=preg_replace('/\D+/','',(string)($_GET['value']??''));

function failJson(string $message,int $status=400): never {
    http_response_code($status);
    echo json_encode(['ok'=>false,'message'=>$message],JSON_UNESCAPED_UNICODE);
    exit;
}
function getJson(string $url): array {
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>15,
        CURLOPT_CONNECTTIMEOUT=>8,
        CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: TecnodataCRM/1.0'],
        CURLOPT_ENCODING=>'',
    ]);
    $raw=curl_exec($ch);
    $http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
    $err=curl_error($ch);
    curl_close($ch);
    if($raw===false||$err||$http>=400) throw new RuntimeException($err?:'Serviço externo indisponível.');
    $data=json_decode($raw,true);
    if(!is_array($data)) throw new RuntimeException('Resposta externa inválida.');
    return $data;
}

try{
    if($type==='cep'){
        if(strlen($value)!==8) failJson('CEP inválido.');
        $d=getJson('https://viacep.com.br/ws/'.$value.'/json/');
        if(!empty($d['erro'])) failJson('CEP não encontrado.',404);
        echo json_encode(['ok'=>true,'data'=>[
            'cep'=>$d['cep']??$value,'endereco'=>$d['logradouro']??'','bairro'=>$d['bairro']??'',
            'cidade'=>$d['localidade']??'','estado'=>$d['uf']??'','ibge'=>$d['ibge']??''
        ]],JSON_UNESCAPED_UNICODE);exit;
    }
    if($type==='cnpj'){
        if(strlen($value)!==14) failJson('CNPJ inválido.');
        $d=getJson('https://brasilapi.com.br/api/cnpj/v1/'.$value);
        $phone=preg_replace('/\D+/','',(string)($d['ddd_telefone_1']??''));
        $ddd=strlen($phone)>=10?substr($phone,0,2):'';
        $num=strlen($phone)>=10?substr($phone,2):'';
        echo json_encode(['ok'=>true,'data'=>[
            'cnpj'=>$value,
            'razao_social'=>$d['razao_social']??'',
            'nome_fantasia'=>$d['nome_fantasia']??($d['razao_social']??''),
            'email'=>strtolower((string)($d['email']??'')),
            'ddd'=>$ddd,'telefone'=>$num,
            'cep'=>preg_replace('/\D+/','',(string)($d['cep']??'')),
            'endereco'=>trim((string)($d['descricao_tipo_de_logradouro']??'').' '.(string)($d['logradouro']??'')),
            'numero'=>(string)($d['numero']??''),
            'complemento'=>(string)($d['complemento']??''),
            'bairro'=>(string)($d['bairro']??''),
            'cidade'=>(string)($d['municipio']??''),
            'estado'=>(string)($d['uf']??'')
        ]],JSON_UNESCAPED_UNICODE);exit;
    }
    failJson('Consulta inválida.');
}catch(Throwable $e){ failJson($e->getMessage(),502); }
