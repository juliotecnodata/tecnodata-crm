<?php
use Tecnodata\Lms\Controllers\Api\HealthApiController;
use Tecnodata\Lms\Controllers\Api\StudentApiController;
use Tecnodata\Lms\Controllers\Api\EnrollmentApiController;
use Tecnodata\Lms\Controllers\Api\BiometricApiController;

$router->get('/api/v1/health',[HealthApiController::class,'show']);
$router->post('/api/v1/students/upsert',[StudentApiController::class,'upsert']);
$router->post('/api/v1/enrollments/upsert',[EnrollmentApiController::class,'upsert']);
$router->get('/api/v1/enrollments/{id}/progress',[EnrollmentApiController::class,'progress']);
$router->get('/api/v1/enrollments/{id}/biometric-requirement',[BiometricApiController::class,'requirement']);
$router->get('/api/v1/certification/eligibility',[EnrollmentApiController::class,'eligibility']);
