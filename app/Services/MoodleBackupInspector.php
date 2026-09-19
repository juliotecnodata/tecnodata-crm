<?php
namespace Tecnodata\Lms\Services;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;
use SimpleXMLElement;

final class MoodleBackupInspector
{
    private array $supported = ['page','url','quiz','subsection','resource','book','lesson','h5pactivity','scorm','label','folder'];

    public function inspect(string $mbz): array
    {
        $root = $this->extract($mbz);
        $manifest = $this->xml($root . '/moodle_backup.xml');
        $courseXml = $this->xml($root . '/course/course.xml');

        $activities = [];
        if (isset($manifest->information->contents->activities->activity)) {
            foreach ($manifest->information->contents->activities->activity as $a) {
                $type = (string) $a->modulename;
                $activities[] = [
                    'moduleid' => (int) $a->moduleid,
                    'sectionid' => (int) $a->sectionid,
                    'type' => $type,
                    'title' => (string) $a->title,
                    'directory' => (string) $a->directory,
                    'supported' => in_array($type, $this->supported, true),
                ];
            }
        }

        $sections = glob($root . '/sections/section_*/section.xml') ?: [];
        $unknown = array_values(array_unique(array_column(array_filter($activities, fn($a)=>!$a['supported']), 'type')));

        $questions = 0;
        $qfile = $root . '/questions.xml';
        if (is_file($qfile)) {
            $qxml = $this->xml($qfile);
            if (isset($qxml->question_bank->question_category)) {
                foreach ($qxml->question_bank->question_category as $cat) {
                    if (isset($cat->questions->question)) $questions += count($cat->questions->question);
                }
            }
        }

        return [
            'root' => $root,
            'course' => [
                'moodle_id' => (int) ($courseXml['id'] ?? 0),
                'fullname' => (string) ($courseXml->fullname ?? 'Curso importado'),
                'shortname' => (string) ($courseXml->shortname ?? ''),
                'summary' => (string) ($courseXml->summary ?? ''),
                'format' => (string) ($courseXml->format ?? ''),
            ],
            'sections_count' => count($sections),
            'activities' => $activities,
            'activity_counts' => array_count_values(array_column($activities, 'type')),
            'questions_count' => $questions,
            'unknown_types' => $unknown,
            'compatible' => $unknown === [],
        ];
    }

    public function cleanup(string $root): void
    {
        if (!is_dir($root) || !str_contains($root, '/storage/import_tmp/')) return;
        $it = new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        @rmdir($root);
    }

    private function extract(string $mbz): string
    {
        if (!is_file($mbz)) throw new RuntimeException('Backup não encontrado.');
        $work = base_path('storage/import_tmp/' . bin2hex(random_bytes(8)));
        if (!is_dir($work) && !mkdir($work, 0775, true) && !is_dir($work)) throw new RuntimeException('Falha ao criar diretório temporário.');
        $magic = file_get_contents($mbz, false, null, 0, 2);
        $archive = $work . (($magic === "\x1f\x8b") ? '/archive.tar.gz' : '/archive.tar');
        copy($mbz, $archive);
        if (str_ends_with($archive, '.gz')) {
            $gz = new PharData($archive);
            $gz->decompress();
            $archive = substr($archive, 0, -3);
        }
        $phar = new PharData($archive);
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $name = str_replace('\\', '/', $file->getPathName());
            $inside = preg_replace('#^phar://[^/]+/#', '', $name);
            if ($inside === null || str_contains($inside, '../') || str_starts_with($inside, '/')) {
                throw new RuntimeException('Caminho inseguro detectado no backup.');
            }
        }
        $dest = $work . '/extracted';
        mkdir($dest, 0775, true);
        $phar->extractTo($dest, null, true);
        return $dest;
    }

    private function xml(string $file): SimpleXMLElement
    {
        if (!is_file($file)) throw new RuntimeException('XML obrigatório ausente: ' . basename($file));
        $xml = simplexml_load_file($file, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
        if (!$xml) throw new RuntimeException('XML inválido: ' . basename($file));
        return $xml;
    }
}
