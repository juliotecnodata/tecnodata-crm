<?php
namespace Tecnodata\Lms\Controllers;

use Tecnodata\Lms\Core\Auth;
use Tecnodata\Lms\Core\Csrf;
use Tecnodata\Lms\Core\Database;
use Tecnodata\Lms\Core\View;
use Tecnodata\Lms\Services\Audit;

final class QuizAdminController
{
    public function show(string $activityId): void
    {
        Auth::requireAdmin();
        [$activity,$quiz]=$this->quiz((int)$activityId);

        $categories=Database::all(
            "SELECT qc.*,(SELECT COUNT(*) FROM questions q WHERE q.category_id=qc.id) question_count
               FROM question_categories qc
              WHERE qc.course_id=?
              ORDER BY qc.name",
            [$activity['course_id']]
        );

        $questions=Database::all(
            "SELECT q.id,q.name,q.type,q.question_html,qc.name category_name
               FROM questions q
               JOIN question_categories qc ON qc.id=q.category_id
              WHERE qc.course_id=?
              ORDER BY qc.name,q.id",
            [$activity['course_id']]
        );

        $slots=Database::all(
            "SELECT s.*,q.name question_name,q.question_html,q.type question_type,
                    qc.name category_name
               FROM quiz_slots s
               LEFT JOIN questions q ON q.id=s.question_id
               LEFT JOIN question_categories qc ON qc.id=s.category_id
              WHERE s.quiz_id=?
              ORDER BY s.position,s.id",
            [$quiz['id']]
        );

        View::render('quizzes/admin',compact('activity','quiz','categories','questions','slots'));
    }

    public function settings(string $activityId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        [$activity,$quiz]=$this->quiz((int)$activityId);

        Database::execute(
            "UPDATE quizzes
                SET grade_max=?,grade_pass=?,attempts_allowed=?,settings_json=?
              WHERE id=?",
            [
                max(1,(float)($_POST['grade_max']??100)),
                $_POST['grade_pass']!==''?(float)$_POST['grade_pass']:null,
                max(0,(int)($_POST['attempts_allowed']??0)),
                json_encode([
                    'shuffle_questions'=>isset($_POST['shuffle_questions']),
                    'show_feedback'=>isset($_POST['show_feedback']),
                    'show_correct_answer'=>isset($_POST['show_correct_answer']),
                ],JSON_UNESCAPED_UNICODE),
                $quiz['id']
            ]
        );

        Audit::log('quiz.settings_changed','quiz',(int)$quiz['id']);
        redirect('/admin/activities/'.$activity['id'].'/quiz');
    }

    public function addSlot(string $activityId): void
    {
        Auth::requireAdmin();
        Csrf::verify();
        [$activity,$quiz]=$this->quiz((int)$activityId);

        $mode=(string)($_POST['mode']??'question');
        $position=(int)(Database::fetch(
            "SELECT COALESCE(MAX(position),0)+1 p FROM quiz_slots WHERE quiz_id=?",
            [$quiz['id']]
        )['p']??1);

        if($mode==='question'){
            $qid=(int)($_POST['question_id']??0);
            $valid=Database::fetch(
                "SELECT q.id
                   FROM questions q
                   JOIN question_categories qc ON qc.id=q.category_id
                  WHERE q.id=? AND qc.course_id=?",
                [$qid,$activity['course_id']]
            );
            if(!$valid) throw new \RuntimeException('Questão inválida.');

            Database::execute(
                "INSERT INTO quiz_slots(quiz_id,question_id,category_id,random_count,position)
                 VALUES(?,?,NULL,0,?)",
                [$quiz['id'],$qid,$position]
            );
        }else{
            $cat=(int)($_POST['category_id']??0);
            $count=max(1,(int)($_POST['random_count']??1));
            $valid=Database::fetch(
                "SELECT id FROM question_categories WHERE id=? AND course_id=?",
                [$cat,$activity['course_id']]
            );
            if(!$valid) throw new \RuntimeException('Categoria inválida.');

            Database::execute(
                "INSERT INTO quiz_slots(quiz_id,question_id,category_id,random_count,position)
                 VALUES(?,NULL,?,?,?)",
                [$quiz['id'],$cat,$count,$position]
            );
        }

        Audit::log('quiz.slot_added','quiz',(int)$quiz['id']);
        redirect('/admin/activities/'.$activity['id'].'/quiz');
    }

    public function deleteSlot(string $slotId): void
    {
        Auth::requireAdmin();
        Csrf::verify();

        $slot=Database::fetch(
            "SELECT s.id,q.activity_id
               FROM quiz_slots s
               JOIN quizzes q ON q.id=s.quiz_id
              WHERE s.id=?",
            [(int)$slotId]
        );
        if(!$slot) throw new \RuntimeException('Slot não encontrado.');

        Database::execute("DELETE FROM quiz_slots WHERE id=?",[(int)$slotId]);
        redirect('/admin/activities/'.$slot['activity_id'].'/quiz');
    }

    private function quiz(int $activityId): array
    {
        $activity=Database::fetch(
            "SELECT a.*,c.name course_name
               FROM course_activities a
               JOIN courses c ON c.id=a.course_id
              WHERE a.id=? AND a.type='quiz'",
            [$activityId]
        );
        if(!$activity) throw new \RuntimeException('Atividade de quiz não encontrada.');

        $quiz=Database::fetch("SELECT * FROM quizzes WHERE activity_id=?",[$activityId]);
        if(!$quiz){
            Database::execute(
                "INSERT INTO quizzes(activity_id,grade_max,grade_pass,attempts_allowed,settings_json)
                 VALUES(?,100,70,0,'{}')",
                [$activityId]
            );
            $quiz=Database::fetch("SELECT * FROM quizzes WHERE id=?",[Database::id()]);
        }

        return [$activity,$quiz];
    }
}
