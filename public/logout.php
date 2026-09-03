<?php
require dirname(__DIR__) . '/app/bootstrap.php';
use Tecnodata\CRM\Core\Auth;
Auth::logout();
header('Location: '.APP_URL.'/login.php');
