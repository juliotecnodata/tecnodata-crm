<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class QuestionBankController
{
    public function index(string $courseId): void
    {
        Auth::requireAdmin();
        $course=$this->course((int)$courseId);

        $categories=Database::all(
            "SELECT qc.*,
                    (SELECT COUNT(*) FROM questions q WHERE q.category_id=qc.id) question_count
               FROM question_categories qc
              WHERE qc.course_id=?
              ORDER BY qc.name",
            [$course['id']]
        );

        $questions=Database::all(
            "SELECT q.*,qc.name category_name,
                    (SELECT COUNT(*) FROM question_answers qa WHERE qa.question_id=q.id) answer_count
               FROM questions q
               LEFT JOIN question_categories qc ON qc.id=q.category_id
              WHERE qc.course_id=?
              ORDER BY q.id DESC",
            [$course['id']]
        );

        View::render('questions/index',[
            'course'=>$course,
            'categories'=>$categories,
            'questions'=>$questions,
            'message'=>$_SESSION['question_message']??null,
        ]);
        unset($_SESSION['question_message']);
    }

    public function category(string $courseId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $course=$this->course((int)$courseId);

        $name=trim((string)($_POST['name']??''));
        if($name==='') throw new \RuntimeException('Nome da categoria é obrigatório.');

        $parent=(int)($_POST['parent_id']??0);
        Database::execute(
            "INSERT INTO question_categories(course_id,parent_id,name,created_at)
             VALUES(?,?,?,?)",
            [$course['id'],$parent?:null,$name,Clock::sql()]
        );

        Audit::log('question_category.created','question_category',Database::id(),['course_id'=>$course['id']]);
        redirect('/admin/courses/'.$course['id'].'/questions');
    }

    public function store(string $courseId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $course=$this->course((int)$courseId);

        $categoryId=(int)($_POST['category_id']??0);
        $category=Database::fetch(
            "SELECT * FROM question_categories WHERE id=? AND course_id=?",
            [$categoryId,$course['id']]
        );
        if(!$category) throw new \RuntimeException('Categoria inválida.');

        $type=(string)($_POST['type']??'multichoice');
        $allowed=['multichoice','truefalse','shortanswer','numerical','matching'];
        if(!in_array($type,$allowed,true)) throw new \RuntimeException('Tipo de questão inválido.');

        $text=trim((string)($_POST['question_html']??''));
        if($text==='') throw new \RuntimeException('Enunciado é obrigatório.');

        $settings=[
            'shuffle_answers'=>isset($_POST['shuffle_answers']),
            'single'=>isset($_POST['single']),
            'case_sensitive'=>isset($_POST['case_sensitive']),
        ];

        if($type==='numerical'){
            $settings['tolerance']=(float)($_POST['tolerance']??0);
        }

        if($type==='matching'){
            $left=(array)($_POST['match_left']??[]);
            $right=(array)($_POST['match_right']??[]);
            $pairs=[];
            foreach($left as $i=>$l){
                $l=trim((string)$l);
                $r=trim((string)($right[$i]??''));
                if($l!=='' && $r!=='') $pairs[]=['left'=>$l,'right'=>$r];
            }
            if(count($pairs)<2) throw new \RuntimeException('Questão de associação precisa de pelo menos dois pares.');
            $settings['pairs']=$pairs;
        }

        Database::execute(
            "INSERT INTO questions(
                category_id,type,name,question_html,default_mark,settings_json,created_at,updated_at
             ) VALUES(?,?,?,?,?,?,?,?)",
            [
                $categoryId,
                $type,
                trim((string)($_POST['name']??'')),
                $text,
                max(0.01,(float)($_POST['default_mark']??1)),
                json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                Clock::sql(),
                Clock::sql()
            ]
        );
        $qid=Database::id();

        $this->storeAnswers($qid,$type);
        Audit::log('question.created','question',$qid,['course_id'=>$course['id'],'type'=>$type]);

        $_SESSION['question_message']='Questão criada com sucesso.';
        redirect('/admin/courses/'.$course['id'].'/questions');
    }

    public function delete(string $id): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $row=Database::fetch(
            "SELECT q.id,qc.course_id
               FROM questions q
               JOIN question_categories qc ON qc.id=q.category_id
              WHERE q.id=?",
            [(int)$id]
        );
        if(!$row) throw new \RuntimeException('Questão não encontrada.');

        $used=Database::fetch(
            "SELECT 1 FROM quiz_slots WHERE question_id=? LIMIT 1",
            [(int)$id]
        );
        if($used) throw new \RuntimeException('A questão está vinculada a uma avaliação. Remova o slot antes de excluir.');

        Database::execute("DELETE FROM questions WHERE id=?",[(int)$id]);
        Audit::log('question.deleted','question',(int)$id);

        redirect('/admin/courses/'.$row['course_id'].'/questions');
    }

    public function export(string $courseId): void
    {
        Auth::requireAdmin();
        $course=$this->course((int)$courseId);

        $categories=Database::all(
            "SELECT * FROM question_categories WHERE course_id=? ORDER BY id",
            [$course['id']]
        );
        $questions=Database::all(
            "SELECT q.*
               FROM questions q
               JOIN question_categories qc ON qc.id=q.category_id
              WHERE qc.course_id=?
              ORDER BY q.id",
            [$course['id']]
        );

        foreach($questions as &$q){
            $q['answers']=Database::all(
                "SELECT * FROM question_answers WHERE question_id=? ORDER BY position,id",
                [$q['id']]
            );
        }
        unset($q);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="question-bank-'.$course['code'].'.json"');
        echo json_encode([
            'format'=>'tecnodata-question-bank-v1',
            'course'=>['code'=>$course['code'],'name'=>$course['name']],
            'categories'=>$categories,
            'questions'=>$questions,
        ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function import(string $courseId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        $course=$this->course((int)$courseId);

        if(empty($_FILES['bank']['tmp_name']) || !is_uploaded_file($_FILES['bank']['tmp_name'])){
            throw new \RuntimeException('Envie o arquivo JSON do banco.');
        }

        $data=json_decode((string)file_get_contents($_FILES['bank']['tmp_name']),true);
        if(!is_array($data) || ($data['format']??'')!=='tecnodata-question-bank-v1'){
            throw new \RuntimeException('Arquivo de banco de questões inválido.');
        }

        $pdo=Database::pdo();
        $pdo->beginTransaction();
        try{
            $catMap=[];
            foreach((array)($data['categories']??[]) as $cat){
                Database::execute(
                    "INSERT INTO question_categories(course_id,parent_id,name,source_id,created_at)
                     VALUES(?,NULL,?,?,?)",
                    [$course['id'],(string)($cat['name']??'Categoria'),(string)($cat['source_id']??''),Clock::sql()]
                );
                $catMap[(int)($cat['id']??0)]=Database::id();
            }

            $count=0;
            foreach((array)($data['questions']??[]) as $q){
                $catId=$catMap[(int)($q['category_id']??0)]??reset($catMap);
                if(!$catId) continue;

                Database::execute(
                    "INSERT INTO questions(category_id,type,name,question_html,default_mark,settings_json,source_id,created_at,updated_at)
                     VALUES(?,?,?,?,?,?,?,?,?)",
                    [
                        $catId,
                        (string)($q['type']??'multichoice'),
                        (string)($q['name']??''),
                        (string)($q['question_html']??''),
                        (float)($q['default_mark']??1),
                        (string)($q['settings_json']??'{}'),
                        (string)($q['source_id']??''),
                        Clock::sql(),
                        Clock::sql()
                    ]
                );
                $newId=Database::id();

                foreach((array)($q['answers']??[]) as $i=>$a){
                    Database::execute(
                        "INSERT INTO question_answers(question_id,answer_html,fraction,feedback_html,position,source_id)
                         VALUES(?,?,?,?,?,?)",
                        [
                            $newId,
                            (string)($a['answer_html']??''),
                            (float)($a['fraction']??0),
                            (string)($a['feedback_html']??''),
                            $i+1,
                            (string)($a['source_id']??'')
                        ]
                    );
                }
                $count++;
            }

            $pdo->commit();
            $_SESSION['question_message']=$count.' questões importadas.';
        }catch(\Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }

        redirect('/admin/courses/'.$course['id'].'/questions');
    }

    private function storeAnswers(int $qid,string $type): void
    {
        if($type==='matching') return;

        if($type==='truefalse'){
            $correct=(string)($_POST['truefalse_correct']??'true');
            foreach([['Verdadeiro','true'],['Falso','false']] as $i=>$row){
                Database::execute(
                    "INSERT INTO question_answers(question_id,answer_html,fraction,feedback_html,position)
                     VALUES(?,?,?,?,?)",
                    [$qid,$row[0],$correct===$row[1]?1:0,'',$i+1]
                );
            }
            return;
        }

        $texts=(array)($_POST['answer_text']??[]);
        $fractions=(array)($_POST['answer_fraction']??[]);
        $feedbacks=(array)($_POST['answer_feedback']??[]);

        $saved=0;
        foreach($texts as $i=>$answer){
            $answer=trim((string)$answer);
            if($answer==='') continue;
            $fraction=((float)($fractions[$i]??0))/100;
            Database::execute(
                "INSERT INTO question_answers(question_id,answer_html,fraction,feedback_html,position)
                 VALUES(?,?,?,?,?)",
                [$qid,$answer,$fraction,(string)($feedbacks[$i]??''),$saved+1]
            );
            $saved++;
        }

        if($saved===0){
            throw new \RuntimeException('Informe pelo menos uma resposta.');
        }
    }

    private function course(int $id): array
    {
        $course=Database::fetch("SELECT * FROM courses WHERE id=?",[$id]);
        if(!$course) throw new \RuntimeException('Curso não encontrado.');
        return $course;
    }
}
