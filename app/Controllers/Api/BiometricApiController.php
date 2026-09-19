<?php
namespace Tecnodata\Lms\Controllers\Api;

use Tecnodata\Lms\Core\ApiAuth;
use Tecnodata\Lms\Services\BiometricPolicyService;

final class BiometricApiController
{
    public function requirement(string $id): void
    {
        ApiAuth::client('enrollments:read');

        $checkpoint=trim((string)($_GET['checkpoint']??'course_entry'));
        $allowed=['first_access','course_entry','before_quiz','periodic','random'];

        if(!in_array($checkpoint,$allowed,true)){
            ApiAuth::deny(
                422,
                'INVALID_CHECKPOINT',
                'Checkpoint biométrico inválido.',
                ['allowed'=>$allowed]
            );
        }

        $result=(new BiometricPolicyService())->requirementForEnrollment((int)$id,$checkpoint);

        ApiAuth::json([
            'success'=>true,
            'biometric'=>$result,
        ]);
    }
}
