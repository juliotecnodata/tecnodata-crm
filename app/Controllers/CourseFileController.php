<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Services\Audit;

final class CourseFileController
{
    public function upload(string $courseId): void
    {
        $user=Auth::requireAdmin();
        Csrf::verify();

        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[(int)$courseId]);
        if(!$course) throw new \RuntimeException('Curso não encontrado.');

        if(empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])){
            throw new \RuntimeException('Selecione um arquivo.');
        }

        $original=basename((string)($_FILES['file']['name']??'arquivo'));
        $tmp=(string)$_FILES['file']['tmp_name'];
        $size=(int)($_FILES['file']['size']??0);
        $max=100*1024*1024;
        if($size<=0 || $size>$max) throw new \RuntimeException('Arquivo inválido ou maior que 100 MB.');

        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($tmp)?:'application/octet-stream';
        $hash=hash_file('sha256',$tmp);

        $existing=Database::fetch(
            "SELECT * FROM course_files WHERE course_id=? AND sha256=?",
            [(int)$courseId,$hash]
        );
        if($existing){
            redirect('/admin/courses/'.$courseId.'/settings');
        }

        $ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
        $stored=bin2hex(random_bytes(16)).($ext!==''?'.'.$ext:'');
        $rel='storage/course_files/'.$courseId.'/'.$stored;
        $dest=base_path($rel);
        if(!is_dir(dirname($dest))) mkdir(dirname($dest),0775,true);

        if(!move_uploaded_file($tmp,$dest)){
            throw new \RuntimeException('Falha ao armazenar arquivo.');
        }

        Database::execute(
            "INSERT INTO course_files(
                course_id,uploaded_by,original_name,stored_name,disk_path,mime_type,size_bytes,sha256,visibility,created_at
             ) VALUES(?,?,?,?,?,?,?,?,?,?)",
            [(int)$courseId,$user['id'],$original,$stored,$rel,$mime,$size,$hash,'enrolled',Clock::sql()]
        );

        Audit::log('course_file.uploaded','course_file',Database::id(),['course_id'=>(int)$courseId]);
        redirect('/admin/courses/'.$courseId.'/settings');
    }

    public function download(string $id): void
    {
        $user=Auth::requireLogin();

        $file=Database::fetch("SELECT * FROM course_files WHERE id=?",[(int)$id]);
        if(!$file) throw new \RuntimeException('Arquivo não encontrado.');

        $allowed=Auth::isAdmin((int)$user['id']) || (bool)Database::fetch(
            "SELECT 1 FROM enrollments WHERE user_id=? AND course_id=? AND status IN('active','completed') LIMIT 1",
            [$user['id'],$file['course_id']]
        );

        if(!$allowed){
            http_response_code(403);
            exit('Acesso negado.');
        }

        $path=base_path($file['disk_path']);
        if(!is_file($path)) throw new \RuntimeException('Arquivo físico ausente.');

        header('Content-Type: '.($file['mime_type']?:'application/octet-stream'));
        header('Content-Length: '.filesize($path));
        header('Content-Disposition: inline; filename="'.rawurlencode($file['original_name']).'"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $file=Database::fetch("SELECT * FROM course_files WHERE id=?",[(int)$id]);
        if(!$file) throw new \RuntimeException('Arquivo não encontrado.');

        $used=Database::fetch(
            "SELECT 1 FROM course_activities WHERE JSON_UNQUOTE(JSON_EXTRACT(content_json,'$.file_id'))=? LIMIT 1",
            [(string)$id]
        );
        if($used) throw new \RuntimeException('Arquivo está vinculado a uma atividade.');

        $path=base_path($file['disk_path']);
        if(is_file($path)) @unlink($path);

        Database::execute("DELETE FROM course_files WHERE id=?",[(int)$id]);
        Audit::log('course_file.deleted','course_file',(int)$id);
        redirect('/admin/courses/'.$file['course_id'].'/settings');
    }
}
