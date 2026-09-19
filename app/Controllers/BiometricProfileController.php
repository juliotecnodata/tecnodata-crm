<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\BiometricDatabase;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;

final class BiometricProfileController
{
    private function admin(): array
    {
        return Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->admin();

        $profiles=Database::all("SELECT * FROM biometric_profiles ORDER BY id");
        $courses=Database::all(
            "SELECT c.id,c.code,c.name,p.code profile_code,p.name profile_name,
                    COALESCE(s.enabled,0) biometric_enabled
               FROM courses c
               LEFT JOIN course_biometric_settings s ON s.course_id=c.id
               LEFT JOIN biometric_profiles p ON p.id=s.profile_id
              WHERE c.status<>'archived'
              ORDER BY c.name"
        );

        View::render('biometrics/index',[
            'profiles'=>$profiles,
            'courses'=>$courses,
            'dbConfigured'=>BiometricDatabase::configured(),
            'dbReady'=>BiometricDatabase::configured() && BiometricDatabase::ping(),
            'message'=>$_SESSION['bio_message']??null,
        ]);

        unset($_SESSION['bio_message']);
    }

    public function updateProfile(string $id): void
    {
        $this->admin();
        Csrf::verify();

        $profile=Database::fetch("SELECT * FROM biometric_profiles WHERE id=?",[(int)$id]);
        if(!$profile) throw new \RuntimeException('Perfil não encontrado.');

        $method=(string)($_POST['verification_method']??'face_match_liveness');
        $allowed=['none','face_match','face_match_liveness'];
        if(!in_array($method,$allowed,true)) throw new \RuntimeException('Método inválido.');

        $periodic=$this->nullablePositiveInt($_POST['periodic_minutes']??null);
        $randomMin=$this->nullablePositiveInt($_POST['random_min_minutes']??null);
        $randomMax=$this->nullablePositiveInt($_POST['random_max_minutes']??null);

        if($randomMin!==null && $randomMax!==null && $randomMax<$randomMin){
            throw new \RuntimeException('Intervalo aleatório máximo deve ser maior ou igual ao mínimo.');
        }

        Database::execute(
            "UPDATE biometric_profiles
                SET name=?,description=?,verification_method=?,
                    first_access_required=?,course_entry_required=?,before_quiz_required=?,
                    periodic_minutes=?,random_min_minutes=?,random_max_minutes=?,
                    max_failures=?,liveness_required=?,enabled=?,updated_at=?
              WHERE id=?",
            [
                trim((string)($_POST['name']??$profile['name'])),
                trim((string)($_POST['description']??'')),
                $method,
                isset($_POST['first_access_required'])?1:0,
                isset($_POST['course_entry_required'])?1:0,
                isset($_POST['before_quiz_required'])?1:0,
                $periodic,
                $randomMin,
                $randomMax,
                max(1,(int)($_POST['max_failures']??3)),
                isset($_POST['liveness_required'])?1:0,
                isset($_POST['enabled'])?1:0,
                Clock::sql(),
                (int)$id
            ]
        );

        $_SESSION['bio_message']='Perfil atualizado.';
        redirect('/admin/biometric-profiles');
    }

    public function installDatabase(): void
    {
        $this->admin();
        Csrf::verify();

        if(!BiometricDatabase::configured()){
            throw new \RuntimeException('Preencha BIO_DB_* no .env antes de inicializar.');
        }

        BiometricDatabase::installSchema();
        $_SESSION['bio_message']='Banco biométrico inicializado com sucesso.';
        redirect('/admin/biometric-profiles');
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $v=trim((string)$value);
        if($v==='') return null;
        $n=(int)$v;
        return $n>0?$n:null;
    }
}
