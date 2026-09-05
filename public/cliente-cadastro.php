<?php
require dirname(__DIR__).'/app/bootstrap.php';
require APP_ROOT.'/app/Support/helpers.php';

use Tecnodata\CRM\Core\Auth;
use Tecnodata\CRM\Core\DB;
use Tecnodata\CRM\Core\Security;
use Tecnodata\CRM\Services\ClientCrudService;

Auth::requireLogin();
if(!Auth::can('supervisor','admin')){http_response_code(403);exit('Sem acesso');}

$id=(int)($_GET['id']??0);
$editing=$id>0;
$client=$editing?DB::fetch("SELECT * FROM clients WHERE id=?",[$id]):null;
if($editing&&!$client){http_response_code(404);exit('Cliente não encontrado.');}
$sellers=DB::all("SELECT omie_code,name FROM sellers WHERE active=1 AND is_virtual=0 ORDER BY name");
$service=new ClientCrudService();
$remote=[];
if($editing){
    try{$remote=$service->consult((string)$client['omie_code']);}
    catch(Throwable){$remote=json_decode((string)($client['raw_json']??''),true)?:[];}
}
$existingTags=[];
if($editing)$existingTags=array_column(DB::all("SELECT tag FROM client_tags WHERE client_id=? ORDER BY tag",[$id]),'tag');
if(!$existingTags&&!empty($remote['tags']))$existingTags=array_values(array_filter(array_map(static fn($x)=>strtoupper((string)($x['tag']??'')),(array)$remote['tags'])));
if(!$editing)$existingTags=['CLIENTE'];

$msg='';$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!Security::verifyCsrf($_POST['_token']??null))$err='Sua sessão expirou. Recarregue a página.';
    else{
        try{
            if(($_POST['action']??'save')==='delete'){
                if(!$editing)throw new RuntimeException('Cliente inválido para exclusão.');
                $service->delete((string)$client['omie_code']);
                header('Location: '.APP_URL.'/clientes.php?deleted=1');exit;
            }
            if($editing){
                $service->update((string)$client['omie_code'],$_POST);
                $msg='Cliente atualizado no Omie e no CRM.';
            }else{
                $result=$service->create($_POST);
                $code=(string)($result['codigo_cliente_omie']??'');
                $saved=$code!==''?DB::fetch("SELECT id FROM clients WHERE omie_code=?",[$code]):null;
                header('Location: '.APP_URL.'/cliente-cadastro.php?id='.(int)($saved['id']??0).'&created=1');exit;
            }
            $client=DB::fetch("SELECT * FROM clients WHERE id=?",[$id]);
            try{$remote=$service->consult((string)$client['omie_code']);}catch(Throwable){$remote=json_decode((string)($client['raw_json']??''),true)?:[];}
            $existingTags=array_column(DB::all("SELECT tag FROM client_tags WHERE client_id=? ORDER BY tag",[$id]),'tag');
        }catch(Throwable $e){$err=$e->getMessage();}
    }
}
if(isset($_GET['created']))$msg='Cliente cadastrado com sucesso no Omie e no CRM.';

$v=static function(string $key,string $fallback='') use($remote,$client):string{
    if(array_key_exists($key,$remote))return (string)$remote[$key];
    return $fallback;
};
$rec=(array)($remote['recomendacoes']??[]);
$selectedSeller=(string)($rec['codigo_vendedor']??'');
include '_layout.php';?>
<div class="page-heading client-form-heading">
 <div><a class="back-link" href="<?=APP_URL?>/clientes.php"><i class="fa-solid fa-arrow-left"></i> Clientes</a><div class="eyebrow mt-3"><?=$editing?'CADASTRO OMIE • '.e($client['omie_code']):'NOVO CADASTRO'?></div><h1><?=$editing?'Editar cliente':'Cadastrar novo cliente'?></h1><p>Cadastro operacional sincronizado diretamente com a Omie.</p></div>
 <div class="client-form-head-actions"><?php if($editing):?><a class="btn btn-outline-secondary" href="<?=APP_URL?>/cliente.php?id=<?=$id?>"><i class="fa-regular fa-eye"></i>Ver cliente</a><?php endif;?><button class="btn btn-primary" form="clientForm"><i class="fa-solid fa-floppy-disk"></i><?=$editing?'Salvar alterações':'Cadastrar cliente'?></button></div>
</div>
<?php if($msg):?><div class="alert alert-success alert-modern"><i class="fa-solid fa-circle-check"></i><?=e($msg)?></div><?php endif;?>
<?php if($err):?><div class="alert alert-danger alert-modern"><i class="fa-solid fa-triangle-exclamation"></i><?=e($err)?></div><?php endif;?>

<form method="post" id="clientForm" class="client-editor" novalidate>
<input type="hidden" name="_token" value="<?=Security::csrf()?>">
<div class="client-editor-main">
 <fieldset class="client-form-section">
  <legend><span class="legend-icon is-blue"><i class="fa-solid fa-building"></i></span><span><strong>Identificação</strong><small>Dados fiscais e comerciais principais</small></span></legend>
  <div class="row g-3">
   <div class="col-lg-4"><label class="form-label">CPF / CNPJ <b>*</b></label><div class="input-action"><input class="form-control" id="clientDocument" name="cnpj_cpf" required inputmode="numeric" value="<?=e($v('cnpj_cpf',$client['document']??''))?>" placeholder="00.000.000/0000-00"><button class="btn btn-outline-secondary" type="button" id="lookupCnpj"><i class="fa-solid fa-magnifying-glass"></i><span>Buscar CNPJ</span></button></div><small class="field-help" id="cnpjStatus">Ao informar um CNPJ, podemos preencher os dados públicos automaticamente.</small></div>
   <div class="col-lg-4"><label class="form-label">Razão social / Nome <b>*</b></label><input class="form-control" name="razao_social" id="legalName" required maxlength="60" value="<?=e($v('razao_social',$client['legal_name']??''))?>"></div>
   <div class="col-lg-4"><label class="form-label">Nome fantasia <b>*</b></label><input class="form-control" name="nome_fantasia" id="tradeName" required maxlength="100" value="<?=e($v('nome_fantasia',$client['name']??''))?>"></div>
  </div>
 </fieldset>

 <fieldset class="client-form-section">
  <legend><span class="legend-icon is-green"><i class="fa-solid fa-address-card"></i></span><span><strong>Contato</strong><small>Responsável, telefone e e-mail</small></span></legend>
  <div class="row g-3 align-items-end">
   <div class="col-lg-5"><label class="form-label">Nome do contato <b>*</b></label><input class="form-control" name="contato" id="contactName" required maxlength="100" value="<?=e($v('contato'))?>"></div>
   <div class="col-4 col-lg-1"><label class="form-label">DDD <b>*</b></label><input class="form-control text-center" name="telefone1_ddd" id="phoneDdd" required inputmode="numeric" maxlength="2" value="<?=e($v('telefone1_ddd'))?>" placeholder="41"></div>
   <div class="col-8 col-lg-2"><label class="form-label">Telefone <b>*</b></label><input class="form-control" name="telefone1_numero" id="phoneNumber" required inputmode="numeric" maxlength="10" value="<?=e($v('telefone1_numero'))?>" placeholder="99999-9999"></div>
   <div class="col-lg-4"><label class="form-label">E-mail <b>*</b></label><input class="form-control" type="email" name="email" id="clientEmail" required value="<?=e($v('email',$client['email']??''))?>"></div>
  </div>
 </fieldset>

 <fieldset class="client-form-section">
  <legend><span class="legend-icon is-orange"><i class="fa-solid fa-location-dot"></i></span><span><strong>Endereço</strong><small>CEP integrado e distribuição dos campos no padrão Omie</small></span></legend>
  <div class="row g-3">
   <div class="col-md-3"><label class="form-label">CEP <b>*</b></label><div class="input-action compact"><input class="form-control" name="cep" id="clientCep" required inputmode="numeric" value="<?=e($v('cep'))?>" placeholder="00000-000"><button class="btn btn-outline-secondary" type="button" id="lookupCep" title="Buscar CEP"><i class="fa-solid fa-magnifying-glass"></i></button></div></div>
   <div class="col-md-7"><label class="form-label">Endereço <b>*</b></label><input class="form-control" name="endereco" id="addressStreet" required maxlength="60" value="<?=e($v('endereco'))?>"></div>
   <div class="col-md-2"><label class="form-label">Número <b>*</b></label><input class="form-control" name="endereco_numero" id="addressNumber" required maxlength="60" value="<?=e($v('endereco_numero'))?>"></div>
   <div class="col-md-4"><label class="form-label">Bairro <b>*</b></label><input class="form-control" name="bairro" id="addressDistrict" required maxlength="60" value="<?=e($v('bairro'))?>"></div>
   <div class="col-md-4"><label class="form-label">Cidade <b>*</b></label><input class="form-control" name="cidade" id="addressCity" required maxlength="40" value="<?=e($v('cidade',$client['city']??''))?>"></div>
   <div class="col-md-2"><label class="form-label">UF <b>*</b></label><input class="form-control text-uppercase" name="estado" id="addressState" required maxlength="2" value="<?=e($v('estado',$client['uf']??''))?>"></div>
   <div class="col-md-2"><label class="form-label">Complemento</label><input class="form-control" name="complemento" id="addressExtra" maxlength="60" value="<?=e($v('complemento'))?>"></div>
  </div>
 </fieldset>

 <fieldset class="client-form-section">
  <legend><span class="legend-icon is-lime"><i class="fa-solid fa-tags"></i></span><span><strong>Classificação</strong><small>Tags preparadas para o padrão comercial Tecnodata</small></span></legend>
  <div class="client-tag-options">
   <?php foreach(['CLIENTE'=>['fa-user-check','Cliente'],'CFC'=>['fa-car','CFC']] as $tag=>$meta):?><label><input type="checkbox" name="tags[]" value="<?=$tag?>" <?=in_array($tag,$existingTags,true)?'checked':''?>><span><i class="fa-solid <?=$meta[0]?>"></i><strong><?=$tag?></strong><small><?=$meta[1]?></small></span></label><?php endforeach;?>
  </div>
 </fieldset>
</div>

<aside class="client-editor-side">
 <div class="client-side-card">
  <div class="side-card-title"><span class="legend-icon is-purple"><i class="fa-solid fa-user-tie"></i></span><div><strong>Vendedor</strong><small>Opcional</small></div></div>
  <select class="form-select" name="seller_omie_code"><option value="">Sem vendedor padrão</option><?php foreach($sellers as $s):?><option value="<?=e($s['omie_code'])?>" <?=$selectedSeller===(string)$s['omie_code']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select>
 </div>
 <div class="client-side-card">
  <div class="side-card-title"><span class="legend-icon is-slate"><i class="fa-solid fa-note-sticky"></i></span><div><strong>Observações</strong><small>Opcional</small></div></div>
  <textarea class="form-control" rows="7" name="observacao" placeholder="Informações internas úteis sobre este cliente"><?=e($v('observacao'))?></textarea>
 </div>
 <div class="client-required-note"><i class="fa-solid fa-asterisk"></i><span>Todos os campos marcados são obrigatórios. Apenas complemento, vendedor e observações podem ficar vazios.</span></div>
 <button class="btn btn-primary w-100 client-save-button"><i class="fa-solid fa-floppy-disk"></i><?=$editing?'Salvar alterações':'Cadastrar na Omie'?></button>
 <?php if($editing):?><button type="button" class="btn btn-outline-danger w-100" id="deleteClient"><i class="fa-regular fa-trash-can"></i>Excluir cliente</button><?php endif;?>
</aside>
</form>

<?php if($editing):?><form method="post" id="deleteClientForm" class="d-none"><input type="hidden" name="_token" value="<?=Security::csrf()?>"><input type="hidden" name="action" value="delete"></form><?php endif;?>

<script>
(()=>{
 const base=<?=json_encode(APP_URL,JSON_UNESCAPED_SLASHES)?>;
 const digits=v=>(v||'').replace(/\D/g,'');
 const doc=document.getElementById('clientDocument'),cep=document.getElementById('clientCep'),ddd=document.getElementById('phoneDdd'),phone=document.getElementById('phoneNumber');
 function maskDoc(v){v=digits(v).slice(0,14);if(v.length<=11)return v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');return v.replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d{1,2})$/,'$1-$2')}
 function maskCep(v){v=digits(v).slice(0,8);return v.replace(/(\d{5})(\d)/,'$1-$2')}
 function maskPhone(v){v=digits(v).slice(0,9);return v.length>8?v.replace(/(\d{5})(\d{1,4})/,'$1-$2'):v.replace(/(\d{4})(\d{1,4})/,'$1-$2')}
 doc.addEventListener('input',()=>doc.value=maskDoc(doc.value));cep.addEventListener('input',()=>cep.value=maskCep(cep.value));ddd.addEventListener('input',()=>ddd.value=digits(ddd.value).slice(0,2));phone.addEventListener('input',()=>phone.value=maskPhone(phone.value));
 doc.value=maskDoc(doc.value);cep.value=maskCep(cep.value);phone.value=maskPhone(phone.value);

 async function lookup(type,value){
  const r=await fetch(base+'/api/customer-lookup.php?type='+encodeURIComponent(type)+'&value='+encodeURIComponent(digits(value)),{headers:{'Accept':'application/json'}});
  const j=await r.json();if(!r.ok||!j.ok)throw new Error(j.message||'Consulta indisponível.');return j.data;
 }
 const set=(id,v,onlyBlank=false)=>{const el=document.getElementById(id);if(el&&v!==undefined&&v!==null&&(!onlyBlank||!el.value.trim()))el.value=v};
 document.getElementById('lookupCep').addEventListener('click',async e=>{const b=e.currentTarget;try{b.disabled=true;const d=await lookup('cep',cep.value);set('clientCep',maskCep(d.cep));set('addressStreet',d.endereco);set('addressDistrict',d.bairro);set('addressCity',d.cidade);set('addressState',d.estado);document.getElementById('addressNumber').focus()}catch(err){alert(err.message)}finally{b.disabled=false}});
 document.getElementById('lookupCnpj').addEventListener('click',async e=>{const b=e.currentTarget,status=document.getElementById('cnpjStatus');if(digits(doc.value).length!==14){status.textContent='A busca automática está disponível para CNPJ com 14 dígitos.';return}try{b.disabled=true;status.textContent='Consultando dados públicos do CNPJ...';const d=await lookup('cnpj',doc.value);set('legalName',d.razao_social);set('tradeName',d.nome_fantasia);set('clientEmail',d.email,true);set('phoneDdd',d.ddd,true);set('phoneNumber',maskPhone(d.telefone),true);set('clientCep',maskCep(d.cep));set('addressStreet',d.endereco);set('addressNumber',d.numero,true);set('addressExtra',d.complemento,true);set('addressDistrict',d.bairro);set('addressCity',d.cidade);set('addressState',d.estado);status.textContent='Dados encontrados. Revise antes de salvar.'}catch(err){status.textContent=err.message}finally{b.disabled=false}});
 document.getElementById('clientForm').addEventListener('submit',e=>{const f=e.currentTarget;if(!f.checkValidity()){e.preventDefault();f.classList.add('was-validated');f.querySelector(':invalid')?.focus();return}if(!f.querySelector('input[name="tags[]"]:checked')){e.preventDefault();alert('Selecione ao menos uma tag: CLIENTE ou CFC.')}});
 <?php if($editing):?>document.getElementById('deleteClient').addEventListener('click',()=>{if(confirm('Excluir este cliente da Omie? Esta ação só continuará se a Omie aceitar a exclusão.'))document.getElementById('deleteClientForm').submit()});<?php endif;?>
})();
</script>
<?php include '_footer.php';?>
