<?php
namespace Tecnodata\Lms\Services;

use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class MoodleImportService
{
    public function execute(array $inspection, int $importId): int
    {
        if (!$inspection['compatible']) {
            throw new \RuntimeException('Importação bloqueada: existem componentes Moodle sem conversor.');
        }

        $course = $inspection['course'];
        Database::execute(
            "INSERT INTO courses(code,name,shortname,summary,status,source_system,source_id,created_at,updated_at)
             VALUES(?,?,?,?, 'draft','moodle',?,?,?)",
            [
                $course['shortname'] ?: 'MDL-' . $course['moodle_id'],
                $course['fullname'],
                $course['shortname'],
                $course['summary'],
                (string)$course['moodle_id'],
                Clock::sql(),
                Clock::sql()
            ]
        );
        $courseId = Database::id();
        $this->legacy('course', (string)$course['moodle_id'], 'course', $courseId);

        $root = $inspection['root'];
        $sectionMap = [];
        $sectionFiles = glob($root . '/sections/section_*/section.xml') ?: [];
        usort($sectionFiles, fn($a,$b)=>strcmp($a,$b));
        foreach ($sectionFiles as $position => $file) {
            $x = simplexml_load_file($file, \SimpleXMLElement::class, LIBXML_NONET|LIBXML_NOCDATA);
            if (!$x) continue;
            $moodleId = (int)($x['id'] ?? 0);
            $name = trim((string)($x->name ?? '')) ?: ('Seção ' . ($position + 1));
            Database::execute(
                "INSERT INTO course_sections(course_id,parent_id,title,summary,position,visible,availability_json,source_id,created_at,updated_at)
                 VALUES(?,?,?,?,?,?,?,?,?,?)",
                [$courseId,null,$name,(string)($x->summary??''),$position+1,(int)($x->visible??1),(string)($x->availabilityjson??''),(string)$moodleId,Clock::sql(),Clock::sql()]
            );
            $sectionMap[$moodleId] = Database::id();
            $this->legacy('section',(string)$moodleId,'course_section',$sectionMap[$moodleId]);
        }

        foreach ($inspection['activities'] as $position => $a) {
            $sectionId = $sectionMap[$a['sectionid']] ?? null;
            if (!$sectionId) continue;
            [$type,$content,$settings] = $this->activityPayload($root, $a);
            Database::execute(
                "INSERT INTO course_activities(course_id,section_id,type,title,description,position,visible,content_json,settings_json,source_id,created_at,updated_at)
                 VALUES(?,?,?,?,?,?,?,?,?,?,?,?)",
                [$courseId,$sectionId,$type,$a['title'],'',$position+1,1,json_encode($content,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),(string)$a['moduleid'],Clock::sql(),Clock::sql()]
            );
            $this->legacy('cmid',(string)$a['moduleid'],'course_activity',Database::id());
        }

        Database::execute("UPDATE imports SET status='completed', target_course_id=?, completed_at=? WHERE id=?", [$courseId,Clock::sql(),$importId]);
        return $courseId;
    }

    private function activityPayload(string $root, array $a): array
    {
        $dir = $root . '/activities/' . $a['directory'];
        $type = $a['type'];
        $content = [];
        $settings = ['moodle_type'=>$a['type']];

        if ($type === 'page' && is_file($dir.'/page.xml')) {
            $x=simplexml_load_file($dir.'/page.xml',\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
            $content=['html'=>(string)($x->page->content??$x->content??'')];
        } elseif ($type === 'url' && is_file($dir.'/url.xml')) {
            $x=simplexml_load_file($dir.'/url.xml',\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
            $url=(string)($x->url->externalurl??$x->externalurl??'');
            $content=['url'=>$url];
            if (stripos($url,'videofront')!==false || stripos($url,'videoteca')!==false) {
                $type='video';
                $settings['provider']='videofront';
            }
        } elseif ($type === 'quiz' && is_file($dir.'/quiz.xml')) {
            $x=simplexml_load_file($dir.'/quiz.xml',\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
            $content=['intro'=>(string)($x->quiz->intro??$x->intro??'')];
            $settings['grade']=(float)($x->quiz->grade??$x->grade??0);
            $settings['attempts']=(int)($x->quiz->attempts_number??$x->attempts_number??0);
        }
        return [$type,$content,$settings];
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
