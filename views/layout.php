<?php
use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Csrf;

$user = Auth::user();
$isAdmin = Auth::isAdmin();
$localSafe = safe_mode();
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e(envv('APP_NAME','Tecnodata LMS'))?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/2.3.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php if ($localSafe): ?>
<div class="environment-banner">
    <strong>AMBIENTE LOCAL</strong>
    <span>Banco compartilhado com produção · disparos externos bloqueados</span>
</div>
<?php endif; ?>
<div class="app-shell <?=$localSafe?'with-env-banner':''?>">
<aside class="sidebar" id="sidebar">
    <a class="brand" href="/">
        <span class="brand-mark">T</span>
        <span><strong>Tecnodata</strong><small>LMS</small></span>
    </a>
    <nav>
        <?php if ($isAdmin): ?>
            <a href="/"><i class="bi bi-grid"></i> Visão geral</a>
            <a href="/admin/courses"><i class="bi bi-journal-richtext"></i> Cursos</a>
            <a href="/admin/students"><i class="bi bi-people"></i> Alunos</a>
            <a href="/admin/enrollments"><i class="bi bi-person-check"></i> Matrículas</a>
            <a href="/admin/imports"><i class="bi bi-cloud-arrow-up"></i> Importar Moodle</a>
            <a href="/admin/api-clients"><i class="bi bi-plug"></i> Integrações API</a>
        <?php endif; ?>
        <a href="/student"><i class="bi bi-mortarboard"></i> Meus cursos</a>
    </nav>
</aside>

<div class="app-main">
<header class="topbar">
    <button class="btn btn-light d-lg-none" id="menuToggle" type="button">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-context">
        <span><?=e(envv('APP_ENV','production')==='local'?'Desenvolvimento':'Produção')?></span>
    </div>
    <div class="user-chip">
        <span><?=e($user['name']??'')?></span>
        <form method="post" action="/logout">
            <?=Csrf::field()?>
            <button class="btn btn-sm btn-outline-secondary">Sair</button>
        </form>
    </div>
</header>

<main class="page-content"><?=$content?></main>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.4/js/dataTables.bootstrap5.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
