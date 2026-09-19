<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\BiometricDatabase;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\MigrationService;

final class SystemController
{
    public function index(): void
    {
        Auth::requireAdmin();
        $m=new MigrationService();

        View::render('system/index',[
            'pending'=>$m->pending(),
            'settings'=>Database::all("SELECT * FROM system_settings ORDER BY setting_key"),
            'bioConfigured'=>BiometricDatabase::configured(),
            'bioReady'=>BiometricDatabase::configured() && BiometricDatabase::ping(),
            'message'=>$_SESSION['system_message']??null,
        ]);
        unset($_SESSION['system_message']);
    }

    public function update(): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $done=(new MigrationService())->runPending();
        $_SESSION['system_message']=$done
            ? 'Atualizações aplicadas: '.implode(', ',$done)
            : 'Nenhuma atualização pendente.';
        redirect('/admin/system');
    }

    public function saveSettings(): void
    {
        $u=Auth::requireAdmin();
        Csrf::verify();

        $allowed=[
            'support_url'=>'string',
            'support_email'=>'string',
            'default_enrollment_days'=>'int',
            'allow_student_profile_edit'=>'bool',
            'maintenance_message'=>'string',
        ];

        foreach($allowed as $key=>$type){
            $value=(string)($_POST[$key]??'');
            Database::execute(
                "INSERT INTO system_settings(setting_key,setting_value,value_type,updated_by,updated_at)
                 VALUES(?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                    setting_value=VALUES(setting_value),
                    value_type=VALUES(value_type),
                    updated_by=VALUES(updated_by),
                    updated_at=VALUES(updated_at)",
                [$key,$value,$type,$u['id'],Clock::sql()]
            );
        }

        $_SESSION['system_message']='Configurações salvas.';
        redirect('/admin/system');
    }
}
