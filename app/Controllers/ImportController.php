<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\MoodleBackupInspector;
use Tecnodata\Lms\Services\MoodleImportService;

final class ImportController
{
    private function admin(): void { Auth::requireLogin(); if(!Auth::isAdmin()){http_response_code(403);exit('Acesso negado.');} }

    public function index(): void
    {
        $this->admin();
        $imports=Database::all("SELECT * FROM imports ORDER BY id DESC LIMIT 50");
        View::render('imports/index',['imports'=>$imports,'analysis'=>$_SESSION['analysis']??null]);
        unset($_SESSION['analysis']);
    }

    public function upload(): void
    {
        $this->admin(); Csrf::verify();
        if(empty($_FILES['backup']['tmp_name']) || !is_uploaded_file($_FILES['backup']['tmp_name'])) throw new \RuntimeException('Envie um arquivo .mbz.');
        $dir=base_path('storage/imports'); if(!is_dir($dir)) mkdir($dir,0775,true);
        $name=bin2hex(random_bytes(8)).'.mbz'; $dest=$dir.'/'.$name;
        if(!move_uploaded_file($_FILES['backup']['tmp_name'],$dest)) throw new \RuntimeException('Falha ao armazenar backup.');
        $hash=hash_file('sha256',$dest);
        Database::execute("INSERT INTO imports(source_system,original_name,stored_path,sha256,status,created_at) VALUES('moodle',?,?,?,?,?)",[(string)$_FILES['backup']['name'],'storage/imports/'.$name,$hash,'analyzing',Clock::sql()]);
        $importId=Database::id();
        $inspector=new MoodleBackupInspector();
        $analysis=$inspector->inspect($dest);
        Database::execute("UPDATE imports SET status=?,analysis_json=? WHERE id=?",[$analysis['compatible']?'ready':'blocked',json_encode($analysis,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$importId]);
        $inspector->cleanup($analysis['root']);
        $_SESSION['analysis']=['id'=>$importId,'data'=>$analysis];
        redirect('/admin/imports');
    }

    public function execute(string $id): void
    {
        $this->admin(); Csrf::verify();
        $import=Database::fetch("SELECT * FROM imports WHERE id=?",[(int)$id]);
        if(!$import || $import['status']!=='ready') throw new \RuntimeException('Importação não está pronta.');
        $path=base_path($import['stored_path']);
        $inspector=new MoodleBackupInspector();
        $analysis=$inspector->inspect($path);
        $courseId=(new MoodleImportService())->execute($analysis,(int)$id);
        $inspector->cleanup($analysis['root']);
        redirect('/admin/courses/'.$courseId);
    }
}
