<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;

$lock = base_path('storage/installed.lock');

$databaseAlreadyInstalled = false;
try {
    $databaseAlreadyInstalled = (bool) Database::fetch(
        "SELECT 1
           FROM users u
           JOIN user_role_assignments ura ON ura.user_id=u.id
           JOIN roles r ON r.id=ura.role_id
          WHERE r.slug='super_admin'
          LIMIT 1"
    );
} catch (Throwable) {
    $databaseAlreadyInstalled = false;
}

if (is_file($lock) || $databaseAlreadyInstalled) {
    http_response_code(409);
    exit(
        '<!doctype html><html lang="pt-BR"><meta charset="utf-8">' .
        '<body style="font-family:system-ui;padding:40px">' .
        '<h1>Tecnodata LMS já instalado</h1>' .
        '<p>O banco compartilhado já possui um Super Administrador. Não execute o instalador novamente.</p>' .
        '<p><a href="/login">Ir para o login</a></p>' .
        '</body></html>'
    );
}

$error = null;
$requirements = [
    'PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'SimpleXML' => extension_loaded('simplexml'),
    'Phar' => extension_loaded('phar'),
    'JSON' => extension_loaded('json'),
    'Storage gravável' => is_dir(base_path('storage')) && is_writable(base_path('storage')),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::verify();

        foreach ($requirements as $label => $ok) {
            if (!$ok) throw new RuntimeException('Requisito não atendido: ' . $label);
        }

        $sqlFiles = glob(base_path('database/*.sql')) ?: [];
        sort($sqlFiles, SORT_NATURAL);

        foreach ($sqlFiles as $sqlFile) {
            $sql = (string) file_get_contents($sqlFile);
            $lines = preg_split('/\R/', $sql) ?: [];
            $clean = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*--/', $line)) continue;
                $clean[] = $line;
            }
            $sql = implode("\n", $clean);

            foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [] as $statement) {
                $statement = trim($statement);
                if ($statement !== '') {
                    Database::pdo()->exec($statement);
                }
            }
        }

        $name = trim((string)($_POST['name'] ?? 'Administrador'));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe nome e e-mail válidos.');
        }

        if (strlen($password) < 10) {
            throw new RuntimeException('A senha inicial deve ter pelo menos 10 caracteres.');
        }

        Database::execute(
            "INSERT INTO organizations(name,code,status,created_at,updated_at)
             VALUES('Tecnodata Educacional','TECNODATA','active',?,?)",
            [Clock::sql(),Clock::sql()]
        );
        $orgId = Database::id();

        Database::execute(
            "INSERT INTO users(
                organization_id,user_type,username,name,email,password_hash,status,created_at,updated_at
             ) VALUES(?, 'staff', ?, ?, ?, ?, 'active', ?, ?)",
            [
                $orgId,
                $email,
                $name,
                $email,
                password_hash($password,PASSWORD_DEFAULT),
                Clock::sql(),
                Clock::sql(),
            ]
        );

        $uid = Database::id();
        $role = Database::fetch("SELECT id FROM roles WHERE slug='super_admin'");

        Database::execute(
            "INSERT INTO user_role_assignments(
                user_id,role_id,context_type,context_id,created_at
             ) VALUES(?,?,'system',NULL,?)",
            [$uid,$role['id'],Clock::sql()]
        );

        file_put_contents($lock, "installed " . Clock::sql());
        redirect('/login');

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalar Tecnodata LMS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
<main class="auth-card install-card">
<div class="brand-mark mb-3">T</div>
<h1>Instalar Tecnodata LMS</h1>
<p class="text-secondary">Produção: lms.tecnodataeducacional.com.br · Horário: America/Sao_Paulo.</p>

<?php if($error): ?>
<div class="alert alert-danger"><?=e($error)?></div>
<?php endif; ?>

<div class="requirement-list mb-4">
<?php foreach($requirements as $label=>$ok): ?>
<div class="requirement <?=$ok?'ok':'bad'?>">
    <span><?=$ok?'✓':'×'?></span> <?=e($label)?>
</div>
<?php endforeach; ?>
</div>

<form method="post">
<?=Csrf::field()?>
<div class="mb-3">
<label class="form-label">Nome do Super Administrador</label>
<input class="form-control" name="name" required>
</div>
<div class="mb-3">
<label class="form-label">E-mail</label>
<input class="form-control" type="email" name="email" required>
</div>
<div class="mb-4">
<label class="form-label">Senha</label>
<input class="form-control" type="password" name="password" minlength="10" required>
<div class="form-text">Mínimo de 10 caracteres.</div>
</div>
<button class="btn btn-brand w-100">Instalar e criar administrador</button>
</form>
</main>
</body>
</html>
