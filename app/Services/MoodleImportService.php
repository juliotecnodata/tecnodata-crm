<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class MoodleImportService
{
    public function execute(array $inspection,int $importId): int
    {
        if(!$inspection['compatible']){
            throw new \RuntimeException('Importação bloqueada: existem componentes Moodle sem conversor estrutural.');
        }

        $course=$inspection['course'];
        $existing=Database::fetch(
            "SELECT target_id FROM legacy_mappings WHERE source_system='moodle' AND source_type='course' AND source_id=?",
            [(string)$course['moodle_id']]
        );
        if($existing)throw new \RuntimeException('Este curso Moodle já foi importado. Curso Tecnodata ID '.$existing['target_id'].'.');

        $code=trim((string)$course['shortname']);
        if($code==='')$code='MDL-'.$course['moodle_id'];
        if(Database::fetch("SELECT 1 FROM courses WHERE code=?",[$code]))$code.='-MDL'.$course['moodle_id'];

        $pdo=Database::pdo();$pdo->beginTransaction();

        try{
            Database::execute(
                "INSERT INTO courses(code,name,shortname,summary,status,navigation_mode,source_system,source_id,created_at,updated_at)
                 VALUES(?,?,?,?, 'draft','linear','moodle',?,?,?)",
                [$code,$course['fullname'],$course['shortname'],$course['summary'],(string)$course['moodle_id'],Clock::sql(),Clock::sql()]
            );
            $courseId=Database::id();
            $this->legacy('course',(string)$course['moodle_id'],'course',$courseId);

            Database::execute(
                "INSERT IGNORE INTO course_policies(course_id,min_days,max_days,require_sequential,completion_percent,allow_retake,settings_json,created_at,updated_at)
                 VALUES(?,0,NULL,0,100,1,'{}',?,?)",
                [$courseId,Clock::sql(),Clock::sql()]
            );
            (new BiometricPolicyService())->ensureCoursePolicy($courseId);

            $root=$inspection['root'];
            $fileImporter=new MoodleFileImporter();
            $fileImport=$fileImporter->import($root,$courseId);
            $questionMaps=(new MoodleQuestionImporter())->import($root,$courseId);

            $sectionMap=[];
            $sectionFiles=glob($root.'/sections/section_*/section.xml')?:[];
            usort($sectionFiles,'strnatcmp');

            foreach($sectionFiles as $position=>$file){
                $x=$this->loadXml($file);
                $moodleId=(int)($x['id']??0);
                $name=trim((string)($x->name??''))?:('Seção '.($position+1));
                $availability=trim((string)($x->availabilityjson??''));

                Database::execute(
                    "INSERT INTO course_sections(course_id,parent_id,title,summary,position,visible,availability_json,source_id,created_at,updated_at)
                     VALUES(?,?,?,?,?,?,?,?,?,?)",
                    [$courseId,null,$name,(string)($x->summary??''),$position+1,(int)($x->visible??1),$availability!==''?$availability:null,(string)$moodleId,Clock::sql(),Clock::sql()]
                );
                $sectionMap[$moodleId]=Database::id();
                $this->legacy('section',(string)$moodleId,'course_section',$sectionMap[$moodleId]);
            }

            foreach($inspection['activities'] as $a){
                if($a['type']!=='subsection')continue;
                $parentSection=$sectionMap[$a['sectionid']]??null;
                if(!$parentSection)continue;

                $xmlFile=$root.'/activities/'.$a['directory'].'/subsection.xml';
                if(!is_file($xmlFile))continue;
                $x=$this->loadXml($xmlFile);

                $candidate=(int)($x->subsection->section??$x->section??$x->subsection->sectionid??$x->sectionid??0);
                if($candidate&&isset($sectionMap[$candidate])){
                    Database::execute(
                        "UPDATE course_sections SET parent_id=?,updated_at=? WHERE id=?",
                        [$parentSection,Clock::sql(),$sectionMap[$candidate]]
                    );
                }
            }

            $positions=[];
            $quizImportSummary=['fixed'=>0,'random'=>0,'unresolved'=>0];

            foreach($inspection['activities'] as $a){
                if($a['type']==='subsection')continue;
                $sectionId=$sectionMap[$a['sectionid']]??null;
                if(!$sectionId)continue;

                $positions[$sectionId]=($positions[$sectionId]??0)+1;
                [$type,$content,$settings]=$this->activityPayload($root,$a,$fileImport,$fileImporter);

                Database::execute(
                    "INSERT INTO course_activities(
                        course_id,section_id,type,title,description,position,visible,completion_mode,
                        content_json,settings_json,source_id,created_at,updated_at
                     ) VALUES(?,?,?,?,?,?,1,'manual',?,?,?,?,?)",
                    [
                        $courseId,$sectionId,$type,$a['title'],'',$positions[$sectionId],
                        json_encode($content,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                        json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                        (string)$a['moduleid'],Clock::sql(),Clock::sql()
                    ]
                );

                $activityId=Database::id();
                $this->legacy('cmid',(string)$a['moduleid'],'course_activity',$activityId);

                if($type==='quiz'){
                    Database::execute(
                        "INSERT INTO quizzes(activity_id,grade_max,grade_pass,attempts_allowed,settings_json)
                         VALUES(?,?,?,?,?)",
                        [
                            $activityId,(float)($settings['grade']??100),
                            isset($settings['grade_pass'])?(float)$settings['grade_pass']:null,
                            (int)($settings['attempts']??0),
                            json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
                        ]
                    );
                    $quizId=Database::id();
                    $slotSummary=(new MoodleQuestionImporter())->importQuizSlots(
                        $root.'/activities/'.$a['directory'].'/quiz.xml',
                        $quizId,
                        $questionMaps
                    );
                    foreach($quizImportSummary as $k=>$v)$quizImportSummary[$k]+=$slotSummary[$k]??0;
                }
            }

            $analysis=$inspection;
            unset($analysis['root'],$analysis['work']);
            $analysis['file_import']=[
                'imported'=>$fileImport['imported'],
                'reused'=>$fileImport['reused'],
                'missing'=>$fileImport['missing'],
            ];
            $analysis['question_import']=[
                'categories'=>$questionMaps['categories'],
                'questions'=>$questionMaps['questions'],
                'skipped_types'=>$questionMaps['skipped'],
                'quiz_slots'=>$quizImportSummary,
            ];

            Database::execute(
                "UPDATE imports SET status='completed',target_course_id=?,analysis_json=?,completed_at=? WHERE id=?",
                [
                    $courseId,
                    json_encode($analysis,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                    Clock::sql(),
                    $importId
                ]
            );

            $pdo->commit();
            return $courseId;

        }catch(\Throwable $e){
            if($pdo->inTransaction())$pdo->rollBack();
            throw $e;
        }
    }

    private function activityPayload(string $root,array $a,array $fileImport,MoodleFileImporter $fileImporter): array
    {
        $dir=$root.'/activities/'.$a['directory'];
        $type=$a['type'];
        $content=['source_directory'=>$a['directory']];
        $settings=['moodle_type'=>$a['type']];

        if($type==='page'&&is_file($dir.'/page.xml')){
            $x=$this->loadXml($dir.'/page.xml');
            $raw=(string)($x->page->content??$x->content??'');
            $rewrite=$fileImporter->rewritePluginFileUrls($root,$a['directory'],$raw,$fileImport);
            $content['html']=$rewrite['html'];
            $content['pluginfile_unresolved']=$rewrite['unresolved'];

        }elseif($type==='url'&&is_file($dir.'/url.xml')){
            $x=$this->loadXml($dir.'/url.xml');
            $url=(string)($x->url->externalurl??$x->externalurl??'');
            $content=['url'=>$url,'source_directory'=>$a['directory']];
            if(stripos($url,'videofront')!==false||stripos($url,'videoteca')!==false){
                $type='video';$settings['provider']='videofront';
            }

        }elseif($type==='quiz'&&is_file($dir.'/quiz.xml')){
            $x=$this->loadXml($dir.'/quiz.xml');
            $content['intro']=(string)($x->quiz->intro??$x->intro??'');
            $settings['grade']=(float)($x->quiz->grade??$x->grade??100);
            $settings['attempts']=(int)($x->quiz->attempts_number??$x->attempts_number??0);

        }else{
            $candidate=$dir.'/'.$a['type'].'.xml';
            if(is_file($candidate)){
                $x=$this->loadXml($candidate);
                $node=$x->{$a['type']}??$x;
                foreach(['intro','content','externalurl','reference','name'] as $field){
                    $value=trim((string)($node->{$field}??''));
                    if($value!=='')$content[$field]=$value;
                }
            }
            if($type==='resource'){
                $content['file_id']=$fileImporter->firstActivityFileId($root,$a['directory'],$fileImport);
                $settings['file_migration_pending']=$content['file_id']===null;
            }elseif(in_array($type,['book','lesson','h5pactivity','scorm','folder'],true)){
                $settings['file_migration_pending']=true;
            }

            foreach(['intro','content'] as $field){
                if(!empty($content[$field])&&str_contains((string)$content[$field],'@@PLUGINFILE@@')){
                    $rewrite=$fileImporter->rewritePluginFileUrls($root,$a['directory'],(string)$content[$field],$fileImport);
                    $content[$field]=$rewrite['html'];
                    $content[$field.'_pluginfile_unresolved']=$rewrite['unresolved'];
                }
            }
        }
        return [$type,$content,$settings];
    }

    private function loadXml(string $file): \SimpleXMLElement
    {
        $x=simplexml_load_file($file,\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
        if(!$x)throw new \RuntimeException('XML inválido: '.basename($file));
        return $x;
    }

    private function legacy(string $sourceType,string $sourceId,string $targetType,int $targetId): void
    {
        Database::execute(
            "INSERT INTO legacy_mappings(source_system,source_type,source_id,target_type,target_id,created_at)
             VALUES('moodle',?,?,?,?,?)",
            [$sourceType,$sourceId,$targetType,$targetId,Clock::sql()]
        );
    }
}
