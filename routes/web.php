<?php
use Tecnodata\Lms\Controllers\AuthController;
use Tecnodata\Lms\Controllers\DashboardController;
use Tecnodata\Lms\Controllers\CourseController;
use Tecnodata\Lms\Controllers\StudentAdminController;
use Tecnodata\Lms\Controllers\EnrollmentAdminController;
use Tecnodata\Lms\Controllers\StudentAreaController;
use Tecnodata\Lms\Controllers\ImportController;
use Tecnodata\Lms\Controllers\ApiClientController;

$router->get('/login',[AuthController::class,'form']);
$router->post('/login',[AuthController::class,'login']);
$router->post('/logout',[AuthController::class,'logout']);

$router->get('/',[DashboardController::class,'index']);

$router->get('/admin/courses',[CourseController::class,'index']);
$router->get('/admin/courses/create',[CourseController::class,'create']);
$router->post('/admin/courses',[CourseController::class,'store']);
$router->get('/admin/courses/{id}',[CourseController::class,'show']);
$router->post('/admin/courses/{id}/sections',[CourseController::class,'section']);
$router->post('/admin/courses/{id}/activities',[CourseController::class,'activity']);

$router->get('/admin/students',[StudentAdminController::class,'index']);

$router->get('/admin/enrollments',[EnrollmentAdminController::class,'index']);
$router->post('/admin/enrollments',[EnrollmentAdminController::class,'store']);
$router->post('/admin/enrollments/{id}/status',[EnrollmentAdminController::class,'status']);

$router->get('/admin/imports',[ImportController::class,'index']);
$router->post('/admin/imports',[ImportController::class,'upload']);
$router->post('/admin/imports/{id}/execute',[ImportController::class,'execute']);

$router->get('/admin/api-clients',[ApiClientController::class,'index']);
$router->post('/admin/api-clients',[ApiClientController::class,'store']);

$router->get('/student',[StudentAreaController::class,'home']);
$router->get('/student/course/{id}',[StudentAreaController::class,'course']);
$router->post('/student/activity/{id}/complete',[StudentAreaController::class,'complete']);
