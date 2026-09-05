<?php
namespace Tecnodata\CRM\Services;

use InvalidArgumentException;
use RuntimeException;
use Tecnodata\CRM\Core\DB;

final class ClientCrudService {
    private OmieClient $omie;

    public function __construct() {
        $this->omie = new OmieClient();
    }

    public function consult(int|string $omieCode): array {
        $code=(int)$omieCode;
        if($code<=0) throw new InvalidArgumentException('Código Omie do cliente inválido.');
        return $this->omie->call('clientes','ConsultarCliente',['codigo_cliente_omie'=>$code]);
    }

    public function create(array $input): array {
        $payload=$this->payload($input, false);
        if(empty($payload['codigo_cliente_integracao'])){
            $payload['codigo_cliente_integracao']='TDCRM-'.date('YmdHis').'-'.random_int(1000,9999);
        }
        $response=$this->omie->call('clientes','IncluirCliente',$payload);
        $code=(string)($response['codigo_cliente_omie']??'');
        if($code==='') throw new RuntimeException('A Omie não retornou o código do novo cliente.');
        $remote=$this->safeConsult($code) ?: array_merge($payload,['codigo_cliente_omie'=>$code]);
        $this->persistLocal($remote,$input);
        return $response;
    }

    public function update(int|string $omieCode, array $input): array {
        $code=(int)$omieCode;
        if($code<=0) throw new InvalidArgumentException('Código Omie do cliente inválido.');
        $payload=$this->payload($input, true);
        $payload['codigo_cliente_omie']=$code;
        $response=$this->omie->call('clientes','AlterarCliente',$payload);
        $remote=$this->safeConsult($code) ?: array_merge($payload,['codigo_cliente_omie'=>$code]);
        $this->persistLocal($remote,$input);
        return $response;
    }

    public function delete(int|string $omieCode): array {
        $code=(int)$omieCode;
        if($code<=0) throw new InvalidArgumentException('Código Omie do cliente inválido.');
        $response=$this->omie->call('clientes','ExcluirCliente',['codigo_cliente_omie'=>$code]);
        DB::exec("UPDATE clients SET active=0,updated_at=NOW() WHERE omie_code=?",[(string)$code]);
        return $response;
    }

    private function safeConsult(int|string $omieCode): ?array {
        try { return $this->consult($omieCode); } catch(\Throwable) { return null; }
    }

    private function payload(array $input, bool $editing): array {
        $digits=static fn($v)=>preg_replace('/\D+/','',(string)$v);
        $required=[
            'razao_social'=>'Razão social / nome',
            'nome_fantasia'=>'Nome fantasia',
            'cnpj_cpf'=>'CPF/CNPJ',
            'contato'=>'Nome do contato',
            'email'=>'E-mail',
            'telefone1_ddd'=>'DDD',
            'telefone1_numero'=>'Telefone',
            'cep'=>'CEP',
            'endereco'=>'Endereço',
            'endereco_numero'=>'Número',
            'bairro'=>'Bairro',
            'cidade'=>'Cidade',
            'estado'=>'UF',
        ];
        foreach($required as $key=>$label){
            if(trim((string)($input[$key]??''))==='') throw new InvalidArgumentException($label.' é obrigatório.');
        }
        $document=$digits($input['cnpj_cpf']??'');
        if(!in_array(strlen($document),[11,14],true)) throw new InvalidArgumentException('Informe um CPF ou CNPJ válido.');
        $ddd=$digits($input['telefone1_ddd']??'');
        $phone=$digits($input['telefone1_numero']??'');
        if(strlen($ddd)!==2) throw new InvalidArgumentException('DDD deve possuir 2 dígitos.');
        if(!in_array(strlen($phone),[8,9],true)) throw new InvalidArgumentException('Telefone deve possuir 8 ou 9 dígitos.');
        $email=trim((string)$input['email']);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');

        $allowedTags=['CLIENTE','CFC'];
        $tags=array_values(array_unique(array_intersect($allowedTags,array_map('strtoupper',(array)($input['tags']??[])))));
        if(!$tags) throw new InvalidArgumentException('Selecione ao menos uma tag: CLIENTE ou CFC.');

        $payload=[
            'razao_social'=>mb_substr(trim((string)$input['razao_social']),0,60),
            'nome_fantasia'=>mb_substr(trim((string)$input['nome_fantasia']),0,100),
            'cnpj_cpf'=>$document,
            'contato'=>mb_substr(trim((string)$input['contato']),0,100),
            'email'=>$email,
            'telefone1_ddd'=>$ddd,
            'telefone1_numero'=>$phone,
            'cep'=>$digits($input['cep']??''),
            'endereco'=>mb_substr(trim((string)$input['endereco']),0,60),
            'endereco_numero'=>mb_substr(trim((string)$input['endereco_numero']),0,60),
            'bairro'=>mb_substr(trim((string)$input['bairro']),0,60),
            'complemento'=>mb_substr(trim((string)($input['complemento']??'')),0,60),
            'cidade'=>mb_substr(trim((string)$input['cidade']),0,40),
            'estado'=>strtoupper(mb_substr(trim((string)$input['estado']),0,2)),
            'codigo_pais'=>'1058',
            'separar_endereco'=>'S',
            'pesquisar_cep'=>'N',
            'observacao'=>trim((string)($input['observacao']??'')),
            'tags'=>array_map(static fn($tag)=>['tag'=>$tag],$tags),
        ];

        $seller=trim((string)($input['seller_omie_code']??''));
        if($seller!=='') $payload['recomendacoes']=['codigo_vendedor'=>(int)$seller];

        return $payload;
    }

    private function persistLocal(array $remote, array $input): void {
        $code=(string)($remote['codigo_cliente_omie']??'');
        if($code==='') return;
        $name=(string)($remote['nome_fantasia']??$input['nome_fantasia']??$remote['razao_social']??'Cliente');
        $legal=(string)($remote['razao_social']??$input['razao_social']??$name);
        $uf=(string)($remote['estado']??$input['estado']??'');
        $city=(string)($remote['cidade']??$input['cidade']??'');
        $ddd=preg_replace('/\D+/','',(string)($remote['telefone1_ddd']??$input['telefone1_ddd']??''));
        $phone=preg_replace('/\D+/','',(string)($remote['telefone1_numero']??$input['telefone1_numero']??''));
        $phoneFull=trim($ddd.' '.$phone);
        $email=(string)($remote['email']??$input['email']??'');
        $document=(string)($remote['cnpj_cpf']??$input['cnpj_cpf']??'');
        $raw=json_encode($remote,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        DB::exec("INSERT INTO clients(omie_code,name,legal_name,uf,city,phone,email,document,active,raw_json,updated_at)
                  VALUES(?,?,?,?,?,?,?,?,1,?,NOW())
                  ON DUPLICATE KEY UPDATE name=VALUES(name),legal_name=VALUES(legal_name),uf=VALUES(uf),city=VALUES(city),
                  phone=VALUES(phone),email=VALUES(email),document=VALUES(document),active=1,raw_json=VALUES(raw_json),updated_at=NOW()",
                  [$code,$name,$legal,$uf,$city,$phoneFull,$email,$document,$raw]);

        $client=DB::fetch("SELECT id FROM clients WHERE omie_code=?",[$code]);
        if(!$client) return;
        $clientId=(int)$client['id'];
        DB::exec("DELETE FROM client_tags WHERE client_id=?",[$clientId]);
        $tags=array_values(array_unique(array_intersect(['CLIENTE','CFC'],array_map('strtoupper',(array)($input['tags']??[])))));
        foreach($tags as $tag){
            DB::exec("INSERT IGNORE INTO client_tags(client_id,tag,created_at) VALUES(?,?,NOW())",[$clientId,$tag]);
        }
    }
}
