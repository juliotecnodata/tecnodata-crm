<?php
use Tecnodata\Lms\Controllers\AuthController;
use Tecnodata\Lms\Controllers\DashboardController;
use Tecnodata\Lms\Controllers\CourseController;
use Tecnodata\Lms\Controllers\CourseSettingsController;
use Tecnodata\Lms\Controllers\CourseFileController;
use Tecnodata\Lms\Controllers\CourseParticipantController;
use Tecnodata\Lms\Controllers\QuestionBankController;
use Tecnodata\Lms\Controllers\QuizAdminController;
use Tecnodata\Lms\Controllers\StudentQuizController;
use Tecnodata\Lms\Controllers\StudentAdminController;
use Tecnodata\Lms\Controllers\EnrollmentAdminController;
use Tecnodata\Lms\Controllers\StudentAreaController;
use Tecnodata\Lms\Controllers\StudyController;
use Tecnodata\Lms\Controllers\TeachingController;
use Tecnodata\Lms\Controllers\ImportController;
use Tecnodata\Lms\Controllers\ApiClientController;
use Tecnodata\Lms\Controllers\BiometricProfileController;
use Tecnodata\Lms\Controllers\StaffController;
use Tecnodata\Lms\Controllers\RoleController;
use Tecnodata\Lms\Controllers\ReportController;
use Tecnodata\Lms\Controllers\AuditController;
use Tecnodata\Lms\Controllers\SystemController;

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
$router->post('/admin/sections/{id}',[CourseController::class,'updateSection']);
$router->post('/admin/sections/{id}/delete',[CourseController::class,'deleteSection']);
$router->post('/admin/activities/{id}',[CourseController::class,'updateActivity']);
$router->post('/admin/activities/{id}/delete',[CourseController::class,'deleteActivity']);
$router->get('/admin/courses/{id}/settings',[CourseSettingsController::class,'show']);
$router->post('/admin/courses/{id}/settings',[CourseSettingsController::class,'save']);
$router->get('/admin/courses/{id}/participants',[CourseParticipantController::class,'index']);
$router->post('/admin/courses/{id}/participants',[CourseParticipantController::class,'store']);
$router->post('/admin/course-participants/{id}/delete',[CourseParticipantController::class,'delete']);

$router->post('/admin/courses/{id}/files',[CourseFileController::class,'upload']);
$router->post('/admin/files/{id}/delete',[CourseFileController::class,'delete']);
$router->get('/files/{id}',[CourseFileController::class,'download']);

$router->get('/admin/courses/{id}/questions',[QuestionBankController::class,'index']);
$router->post('/admin/courses/{id}/question-categories',[QuestionBankController::class,'category']);
$router->post('/admin/courses/{id}/questions',[QuestionBankController::class,'store']);
$router->get('/admin/courses/{id}/questions/export',[QuestionBankController::class,'export']);
$router->post('/admin/courses/{id}/questions/import',[QuestionBankController::class,'import']);
$router->get('/admin/questions/{id}/edit',[QuestionBankController::class,'edit']);
$router->post('/admin/questions/{id}',[QuestionBankController::class,'update']);
$router->post('/admin/questions/{id}/delete',[QuestionBankController::class,'delete']);

$router->get('/admin/activities/{id}/quiz',[QuizAdminController::class,'show']);
$router->post('/admin/activities/{id}/quiz/settings',[QuizAdminController::class,'settings']);
$router->post('/admin/activities/{id}/quiz/slots',[QuizAdminController::class,'addSlot']);
$router->post('/admin/quiz-slots/{id}/delete',[QuizAdminController::class,'deleteSlot']);

$router->get('/admin/students',[StudentAdminController::class,'index']);
$router->post('/admin/students',[StudentAdminController::class,'store']);
$router->post('/admin/students/{id}',[StudentAdminController::class,'update']);
$router->post('/admin/students/{id}/reset-password',[StudentAdminController::class,'resetPassword']);
$router->get('/admin/enrollments',[EnrollmentAdminController::class,'index']);
$router->post('/admin/enrollments',[EnrollmentAdminController::class,'store']);
$router->post('/admin/enrollments/{id}/status',[EnrollmentAdminController::class,'status']);

$router->get('/admin/staff',[StaffController::class,'index']);
$router->post('/admin/staff',[StaffController::class,'store']);
$router->post('/admin/staff/{id}/status',[StaffController::class,'status']);
$router->post('/admin/staff/{id}/role',[StaffController::class,'role']);
$router->get('/admin/roles',[RoleController::class,'index']);
$router->post('/admin/roles/{id}',[RoleController::class,'save']);
$router->get('/admin/reports',[ReportController::class,'index']);
$router->get('/admin/audit',[AuditController::class,'index']);

$router->get('/admin/biometric-profiles',[BiometricProfileController::class,'index']);
$router->post('/admin/biometric-profiles/{id}',[BiometricProfileController::class,'updateProfile']);
$router->post('/admin/biometric-profiles/install-database',[BiometricProfileController::class,'installDatabase']);

$router->get('/admin/imports',[ImportController::class,'index']);
$router->post('/admin/imports',[ImportController::class,'upload']);
$router->post('/admin/imports/{id}/execute',[ImportController::class,'execute']);

$router->get('/admin/api-clients',[ApiClientController::class,'index']);
$router->post('/admin/api-clients',[ApiClientController::class,'store']);
$router->post('/admin/api-clients/{id}',[ApiClientController::class,'update']);
$router->post('/admin/api-clients/{id}/status',[ApiClientController::class,'status']);
$router->post('/admin/api-clients/{id}/rotate',[ApiClientController::class,'rotate']);
$router->get('/admin/system',[SystemController::class,'index']);
$router->post('/admin/system/update',[SystemController::class,'update']);
$router->post('/admin/system/settings',[SystemController::class,'saveSettings']);

$router->get('/teaching',[TeachingController::class,'index']);
$router->get('/teaching/course/{id}',[TeachingController::class,'course']);

$router->get('/student',[StudentAreaController::class,'home']);
$router->get('/student/course/{id}',[StudentAreaController::class,'course']);
$router->post('/student/activity/{id}/complete',[StudentAreaController::class,'complete']);
$router->post('/student/enrollment/{id}/heartbeat',[StudyController::class,'heartbeat']);
$router->get('/student/enrollment/{enrollmentId}/quiz/{activityId}',[StudentQuizController::class,'show']);
$router->post('/student/enrollment/{enrollmentId}/quiz/{activityId}/submit',[StudentQuizController::class,'submit']);
$router->get('/student/enrollment/{enrollmentId}/quiz/{activityId}/result/{attemptId}',[StudentQuizController::class,'result']);
