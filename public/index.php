<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

use Tecnodata\Lms\Core\Router;

$router=new Router();
require base_path('routes/web.php');
require base_path('routes/api.php');
$router->dispatch();
