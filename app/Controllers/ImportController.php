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
    private function admin(): void
    {
        Auth::requireAdmin();
    }

    public function index(): void
    {
        $this->admin();

        $imports=Database::all("SELECT * FROM imports ORDER BY id DESC LIMIT 50");

        View::render('imports/index',[
            'imports'=>$imports,
            'analysis'=>$_SESSION['analysis']??null,
            'message'=>$_SESSION['import_message']??null,
        ]);

        unset($_SESSION['analysis'],$_SESSION['import_message']);
    }

    public function upload(): void
    {
        $this->admin();
        Csrf::verify();

        if(
            empty($_FILES['backup']['tmp_name']) ||
            !is_uploaded_file($_FILES['backup']['tmp_name'])
        ){
            throw new \RuntimeException('Envie um arquivo .mbz.');
        }

        $original=(string)($_FILES['backup']['name']??'backup.mbz');
        if(strtolower(pathinfo($original,PATHINFO_EXTENSION))!=='mbz'){
            throw new \RuntimeException('O arquivo precisa ter extensão .mbz.');
        }

        $dir=base_path('storage/imports');
        if(!is_dir($dir)) mkdir($dir,0775,true);

        $name=bin2hex(random_bytes(8)).'.mbz';
        $dest=$dir.'/'.$name;

        if(!move_uploaded_file($_FILES['backup']['tmp_name'],$dest)){
            throw new \RuntimeException('Falha ao armazenar backup.');
        }

        $hash=hash_file('sha256',$dest);

        $existing=Database::fetch("SELECT * FROM imports WHERE sha256=?",[$hash]);
        if($existing){
            @unlink($dest);
            $_SESSION['import_message']='Este mesmo backup já foi analisado anteriormente (importação #'.$existing['id'].').';
            redirect('/admin/imports');
        }

        Database::execute(
            "INSERT INTO imports(
                source_system,original_name,stored_path,sha256,status,created_at
             ) VALUES('moodle',?,?,?,?,?)",
            [
                $original,
                'storage/imports/'.$name,
                $hash,
                'analyzing',
                Clock::sql()
            ]
        );

        $importId=Database::id();
        $inspector=new MoodleBackupInspector();

        try{
            $analysis=$inspector->inspect($dest);

            Database::execute(
                "UPDATE imports SET status=?,analysis_json=? WHERE id=?",
                [
                    $analysis['compatible']?'ready':'blocked',
                    json_encode($analysis,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                    $importId
                ]
            );

            $_SESSION['analysis']=['id'=>$importId,'data'=>$analysis];
            $inspector->cleanup($analysis['work'] ?? $analysis['root']);

        }catch(\Throwable $e){
            Database::execute(
                "UPDATE imports SET status='error',analysis_json=? WHERE id=?",
                [json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE),$importId]
            );
            throw $e;
        }

        redirect('/admin/imports');
    }

    public function execute(string $id): void
    {
        $this->admin();
        Csrf::verify();

        $import=Database::fetch("SELECT * FROM imports WHERE id=?",[(int)$id]);
        if(!$import || $import['status']!=='ready'){
            throw new \RuntimeException('Importação não está pronta.');
        }

        $path=base_path($import['stored_path']);
        $inspector=new MoodleBackupInspector();
        $analysis=$inspector->inspect($path);

        try{
            $courseId=(new MoodleImportService())->execute($analysis,(int)$id);
        }finally{
            $inspector->cleanup($analysis['work'] ?? $analysis['root']);
        }

        redirect('/admin/courses/'.$courseId);
    }
}
