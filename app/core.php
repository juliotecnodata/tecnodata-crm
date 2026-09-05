<?php
use PDO;
use RuntimeException;

final class DB {
 private static ?PDO $pdo=null;
 public static function conn(): PDO {
  if(self::$pdo)return self::$pdo;
  $host=$_SERVER['HTTP_HOST']??'localhost';
  $local=str_contains($host,'localhost')||str_contains($host,'127.0.0.1');
  $d=$GLOBALS['config']['database'][$local?'local':'production'];
  try{
   self::$pdo=new PDO(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',$d['host'],$d['port'],$d['database'],$d['charset']??'utf8mb4'),
    $d['username'],$d['password'],
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]
   );
   return self::$pdo;
  }catch(Throwable $e){throw new RuntimeException('Falha ao conectar ao banco: '.$e->getMessage(),0,$e);}
 }
 public static function all(string $sql,array $p=[]): array{$s=self::conn()->prepare($sql);$s->execute($p);return $s->fetchAll();}
 public static function one(string $sql,array $p=[]): ?array{$s=self::conn()->prepare($sql);$s->execute($p);$r=$s->fetch();return $r?:null;}
 public static function exec(string $sql,array $p=[]): int{$s=self::conn()->prepare($sql);$s->execute($p);return $s->rowCount();}
 public static function scalar(string $sql,array $p=[]): mixed{$s=self::conn()->prepare($sql);$s->execute($p);return $s->fetchColumn();}
}

final class Auth {
 public static function user(): ?array{return $_SESSION['user']??null;}
 public static function id(): int{return (int)(self::user()['id']??0);}
 public static function check(): bool{return self::id()>0;}
 public static function can(string ...$roles): bool{$u=self::user();return $u&&in_array((string)$u['role'],$roles,true);}
 public static function attempt(string $email,string $password): bool{
  $u=DB::one("SELECT id,name,email,password_hash,role,seller_omie_code,active FROM users WHERE email=? LIMIT 1",[mb_strtolower(trim($email))]);
  if(!$u||!(int)$u['active']||!password_verify($password,(string)$u['password_hash']))return false;
  unset($u['password_hash']);$_SESSION['user']=$u;session_regenerate_id(true);
  DB::exec("UPDATE users SET last_login_at=NOW() WHERE id=?",[(int)$u['id']]);return true;
 }
 public static function requireLogin(): void{if(!self::check())redirect('/login');}
 public static function requireRole(string ...$roles): void{self::requireLogin();if(!self::can(...$roles)){http_response_code(403);exit('Sem permissão.');}}
 public static function logout(): void{$_SESSION=[];session_destroy();}
}

final class CSRF {
 public static function token(): string{if(empty($_SESSION['_csrf']))$_SESSION['_csrf']=bin2hex(random_bytes(32));return (string)$_SESSION['_csrf'];}
 public static function verify(?string $token): bool{return is_string($token)&&isset($_SESSION['_csrf'])&&hash_equals((string)$_SESSION['_csrf'],$token);}
 public static function require(?string $token): void{if(!self::verify($token)){http_response_code(419);exit('Sessão expirada. Recarregue a página.');}}
}

final class Router {
 private array $routes=[];
 public function get(string $p,callable $h): void{$this->add('GET',$p,$h);}
 public function post(string $p,callable $h): void{$this->add('POST',$p,$h);}
 private function add(string $m,string $p,callable $h): void{
  $rx=preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#','(?P<$1>[^/]+)',$p);
  $this->routes[]=[$m,'#^'.$rx.'$#',$h];
 }
 public function dispatch(string $method,string $path): void{
  foreach($this->routes as [$m,$rx,$h]){
   if($m!==$method||!preg_match($rx,$path,$match))continue;
   $p=[];foreach($match as $k=>$v)if(is_string($k))$p[$k]=$v;$h($p);return;
  }
  http_response_code(404);echo '<h1>404</h1><p>Página não encontrada.</p>';
 }
}

function e(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function money(mixed $v): string{return 'R$ '.number_format((float)$v,2,',','.');}
function brdate(?string $v): string{if(!$v)return '—';$t=strtotime($v);return $t?date('d/m/Y',$t):'—';}
function redirect(string $path): never{header('Location: '.(str_starts_with($path,'http')?$path:APP_URL.$path));exit;}
function json_response(array $d,int $status=200): never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
