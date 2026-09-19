<?php
namespace Tecnodata\Lms\Services;

use SimpleXMLElement;
use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class MoodleFileImporter
{
    public function import(string $root,int $courseId): array
    {
        $xmlFile=$root.'/files.xml';
        if(!is_file($xmlFile)){
            return ['imported'=>0,'reused'=>0,'missing'=>0,'by_source'=>[],'meta'=>[]];
        }

        $xml=$this->xml($xmlFile);
        $nodes=$xml->xpath('//file')?:[];
        $bySource=[];
        $meta=[];
        $byHash=[];
        $imported=0;$reused=0;$missing=0;

        $targetDir=base_path('storage/course_files/'.$courseId);
        if(!is_dir($targetDir)&&!mkdir($targetDir,0775,true)&&!is_dir($targetDir)){
            throw new \RuntimeException('Não foi possível criar a pasta de arquivos do curso.');
        }

        foreach($nodes as $f){
            $sourceId=(int)($f['id']??$f->id??0);
            $filename=trim((string)($f->filename??''));
            $contenthash=trim((string)($f->contenthash??''));
            $filepath=(string)($f->filepath??'/');

            if(!$sourceId||$filename===''||$filename==='.'||$contenthash==='')continue;

            $physical=$root.'/files/'.substr($contenthash,0,2).'/'.$contenthash;
            if(!is_file($physical)){
                $missing++;
                continue;
            }

            if(isset($byHash[$contenthash])){
                $targetId=$byHash[$contenthash];
                $reused++;
            }else{
                $sha=hash_file('sha256',$physical);
                $existing=Database::fetch(
                    "SELECT id FROM course_files WHERE course_id=? AND sha256=?",
                    [$courseId,$sha]
                );

                if($existing){
                    $targetId=(int)$existing['id'];
                    $reused++;
                }else{
                    $ext=strtolower(pathinfo($filename,PATHINFO_EXTENSION));
                    $stored=$contenthash.($ext!==''?'.'.$ext:'');
                    $dest=$targetDir.'/'.$stored;

                    if(!is_file($dest)&&!copy($physical,$dest)){
                        throw new \RuntimeException('Falha ao copiar arquivo Moodle: '.$filename);
                    }

                    $mime=trim((string)($f->mimetype??''));
                    if($mime===''){
                        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($physical)?:'application/octet-stream';
                    }

                    Database::execute(
                        "INSERT INTO course_files(
                            course_id,uploaded_by,original_name,stored_name,disk_path,mime_type,
                            size_bytes,sha256,visibility,source_system,source_id,created_at
                         ) VALUES(?,?,?,?,?,?,?,?,?,'moodle',?,?)",
                        [
                            $courseId,Auth::id(),$filename,$stored,
                            'storage/course_files/'.$courseId.'/'.$stored,
                            $mime,(int)filesize($physical),$sha,'enrolled',
                            (string)$sourceId,Clock::sql()
                        ]
                    );
                    $targetId=Database::id();
                    $imported++;
                }

                $byHash[$contenthash]=$targetId;
            }

            $bySource[$sourceId]=$targetId;
            $meta[$sourceId]=[
                'target_id'=>$targetId,
                'filename'=>$filename,
                'filepath'=>$filepath,
                'contenthash'=>$contenthash,
                'component'=>(string)($f->component??''),
                'filearea'=>(string)($f->filearea??''),
                'itemid'=>(int)($f->itemid??0),
            ];
        }

        return compact('imported','reused','missing','bySource','meta')+[
            'by_source'=>$bySource,
        ];
    }

    public function activityFileIds(string $root,string $activityDirectory): array
    {
        $file=$root.'/activities/'.$activityDirectory.'/inforef.xml';
        if(!is_file($file))return [];

        $x=$this->xml($file);
        $ids=[];

        foreach(['//fileref/file/id','//fileref/file','//files/file/id'] as $xpath){
            foreach($x->xpath($xpath)?:[] as $node){
                $id=(int)$node;
                if(!$id&&isset($node['id']))$id=(int)$node['id'];
                if($id)$ids[$id]=true;
            }
        }

        return array_keys($ids);
    }

    public function rewritePluginFileUrls(
        string $root,
        string $activityDirectory,
        string $html,
        array $fileImport
    ): array {
        if($html===''||!str_contains($html,'@@PLUGINFILE@@')){
            return ['html'=>$html,'rewritten'=>0,'unresolved'=>0];
        }

        $ids=$this->activityFileIds($root,$activityDirectory);
        $rewritten=0;

        foreach($ids as $sourceId){
            $targetId=$fileImport['by_source'][$sourceId]??null;
            $m=$fileImport['meta'][$sourceId]??null;
            if(!$targetId||!$m)continue;

            $path='/'.ltrim((string)$m['filepath'],'/');
            if($path==='/')$path='';
            $relative=$path.'/'.ltrim((string)$m['filename'],'/');
            $relative=preg_replace('#/+#','/',$relative)?:$relative;

            $replacements=[
                '@@PLUGINFILE@@'.$relative,
                '@@PLUGINFILE@@'.rawurlencode($relative),
                '@@PLUGINFILE@@/'.rawurlencode((string)$m['filename']),
            ];

            foreach(array_unique($replacements) as $from){
                if(str_contains($html,$from)){
                    $html=str_replace($from,'/files/'.$targetId,$html,$count);
                    $rewritten+=$count;
                }
            }
        }

        return [
            'html'=>$html,
            'rewritten'=>$rewritten,
            'unresolved'=>substr_count($html,'@@PLUGINFILE@@'),
        ];
    }

    public function firstActivityFileId(string $root,string $activityDirectory,array $fileImport): ?int
    {
        foreach($this->activityFileIds($root,$activityDirectory) as $sourceId){
            if(isset($fileImport['by_source'][$sourceId])){
                return (int)$fileImport['by_source'][$sourceId];
            }
        }
        return null;
    }

    private function xml(string $file): SimpleXMLElement
    {
        $x=simplexml_load_file($file,SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
        if(!$x)throw new \RuntimeException('XML inválido: '.basename($file));
        return $x;
    }
}
