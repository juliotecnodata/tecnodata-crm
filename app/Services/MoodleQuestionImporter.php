<?php
namespace Tecnodata\Lms\Services;

use SimpleXMLElement;
use Tecnodata\Lms\Core\Clock;
use Tecnodata\Lms\Core\Database;

final class MoodleQuestionImporter
{
    private array $supported=['multichoice','truefalse','shortanswer','numerical','matching'];

    public function import(string $root,int $courseId): array
    {
        $file=$root.'/questions.xml';
        if(!is_file($file)){
            return ['categories'=>0,'questions'=>0,'skipped'=>[],'question_map'=>[],'entry_map'=>[],'category_map'=>[]];
        }

        $xml=$this->xml($file);
        $categories=$xml->xpath('//question_category')?:[];

        $categoryMap=[];
        $questionMap=[];
        $entryMap=[];
        $parents=[];
        $skipped=[];
        $categoryCount=0;
        $questionCount=0;

        foreach($categories as $cat){
            $sourceCat=$this->sourceId($cat);
            if(!$sourceCat)continue;

            $name=$this->nodeText($cat->name??null);
            if($name==='')$name='Categoria Moodle '.$sourceCat;

            $existing=Database::fetch(
                "SELECT id FROM question_categories WHERE course_id=? AND source_id=?",
                [$courseId,(string)$sourceCat]
            );
            if($existing){
                $targetCat=(int)$existing['id'];
            }else{
                Database::execute(
                    "INSERT INTO question_categories(course_id,parent_id,name,source_id,created_at)
                     VALUES(?,NULL,?,?,?)",
                    [$courseId,$name,(string)$sourceCat,Clock::sql()]
                );
                $targetCat=Database::id();
                $categoryCount++;
            }

            $categoryMap[$sourceCat]=$targetCat;
            $parentSource=(int)($cat->parent??0);
            if($parentSource>0)$parents[$targetCat]=$parentSource;

            // Moodle antigo: <questions><question>.
            foreach(($cat->xpath('./questions/question')?:[]) as $q){
                $result=$this->importQuestionNode($q,$targetCat,null);
                if($result['target']){
                    $questionMap[$result['source']]=$result['target'];
                    $questionCount+=$result['created']?1:0;
                }elseif($result['type']){
                    $skipped[]=$result['type'];
                }
            }

            // Moodle 4/5: question_bank_entries > versions > question.
            foreach(($cat->xpath('./question_bank_entries/question_bank_entry')?:[]) as $entry){
                $entryId=$this->sourceId($entry);
                $versions=$entry->xpath('./question_versions/question_version')?:[];
                if(!$versions)continue;

                $chosen=end($versions);
                $qNodes=$chosen->xpath('./question')?:[];
                if(!$qNodes)continue;
                $q=$qNodes[0];

                $result=$this->importQuestionNode($q,$targetCat,$entryId?:null);
                if($result['target']){
                    $questionMap[$result['source']]=$result['target'];
                    if($entryId)$entryMap[$entryId]=$result['target'];
                    $questionCount+=$result['created']?1:0;
                }elseif($result['type']){
                    $skipped[]=$result['type'];
                }
            }
        }

        foreach($parents as $target=>$sourceParent){
            if(isset($categoryMap[$sourceParent])){
                Database::execute(
                    "UPDATE question_categories SET parent_id=? WHERE id=?",
                    [$categoryMap[$sourceParent],$target]
                );
            }
        }

        return [
            'categories'=>$categoryCount,
            'questions'=>$questionCount,
            'skipped'=>array_values(array_unique($skipped)),
            'question_map'=>$questionMap,
            'entry_map'=>$entryMap,
            'category_map'=>$categoryMap,
        ];
    }

    public function importQuizSlots(
        string $quizXml,
        int $quizId,
        array $maps
    ): array {
        if(!is_file($quizXml))return ['fixed'=>0,'random'=>0,'unresolved'=>0];

        $x=$this->xml($quizXml);
        $slots=$x->xpath('//slot')?:[];
        if(!$slots){
            $slots=$x->xpath('//question_instance')?:[];
        }

        $position=0;$fixed=0;$random=0;$unresolved=0;

        foreach($slots as $slot){
            $position++;
            $questionSource=(int)($slot->questionid??0);
            $entrySource=(int)($slot->questionbankentryid??0);

            if(!$entrySource){
                $refs=$slot->xpath('.//questionbankentryid')?:[];
                if($refs)$entrySource=(int)$refs[0];
            }

            $targetQuestion=null;
            if($questionSource&&isset($maps['question_map'][$questionSource])){
                $targetQuestion=$maps['question_map'][$questionSource];
            }elseif($entrySource&&isset($maps['entry_map'][$entrySource])){
                $targetQuestion=$maps['entry_map'][$entrySource];
            }

            if($targetQuestion){
                Database::execute(
                    "INSERT INTO quiz_slots(quiz_id,question_id,category_id,random_count,position)
                     VALUES(?,?,NULL,0,?)",
                    [$quizId,$targetQuestion,$position]
                );
                $fixed++;
                continue;
            }

            $categorySource=(int)($slot->questioncategoryid??$slot->categoryid??0);
            if(!$categorySource){
                $filterNodes=$slot->xpath('.//filtercondition')?:[];
                if($filterNodes){
                    $filter=(string)$filterNodes[0];
                    foreach(array_keys($maps['category_map']) as $srcCat){
                        if(preg_match('/(?<!\d)'.preg_quote((string)$srcCat,'/').'(?!\d)/',$filter)){
                            $categorySource=(int)$srcCat;
                            break;
                        }
                    }
                }
            }

            if($categorySource&&isset($maps['category_map'][$categorySource])){
                Database::execute(
                    "INSERT INTO quiz_slots(quiz_id,question_id,category_id,random_count,position)
                     VALUES(?,NULL,?,1,?)",
                    [$quizId,$maps['category_map'][$categorySource],$position]
                );
                $random++;
            }else{
                $unresolved++;
            }
        }

        return compact('fixed','random','unresolved');
    }

    private function importQuestionNode(SimpleXMLElement $q,int $targetCategory,?int $entryId): array
    {
        $source=$this->sourceId($q);
        if(!$source)return ['target'=>null,'source'=>0,'type'=>'','created'=>false];

        $type=trim((string)($q['qtype']??$q->qtype??''));
        if($type==='')$type=trim((string)($q->type??''));

        if(!in_array($type,$this->supported,true)){
            return ['target'=>null,'source'=>$source,'type'=>$type?:'unknown','created'=>false];
        }

        $existing=Database::fetch(
            "SELECT id FROM questions WHERE source_id=? AND category_id=?",
            [(string)$source,$targetCategory]
        );
        if($existing){
            return ['target'=>(int)$existing['id'],'source'=>$source,'type'=>$type,'created'=>false];
        }

        $name=$this->nodeText($q->name??null);
        $questionText=$this->nodeText($q->questiontext??null);
        if($questionText==='')$questionText=$this->nodeText($q->question_text??null);
        $default=(float)($q->defaultmark??1);
        if($default<=0)$default=1;

        $settings=[
            'source'=>'moodle',
            'moodle_entry_id'=>$entryId,
        ];

        $single=$this->deepFirst($q,'single');
        if($single!==null)$settings['single']=in_array(strtolower($single),['1','true'],true);

        $shuffle=$this->deepFirst($q,'shuffleanswers');
        if($shuffle!==null)$settings['shuffle_answers']=in_array(strtolower($shuffle),['1','true'],true);

        Database::execute(
            "INSERT INTO questions(
                category_id,type,name,question_html,default_mark,settings_json,source_id,created_at,updated_at
             ) VALUES(?,?,?,?,?,?,?,?,?)",
            [
                $targetCategory,$type,$name,$questionText,$default,
                json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                (string)$source,Clock::sql(),Clock::sql()
            ]
        );
        $target=Database::id();

        $answers=$q->xpath('.//answers/answer')?:[];
        if(!$answers)$answers=$q->xpath('.//answer')?:[];

        $seen=[];
        $pos=0;
        foreach($answers as $answer){
            $answerSource=$this->sourceId($answer);
            $text=$this->nodeText($answer->answertext??$answer->text??$answer);
            if($text==='')continue;

            $dedupe=md5($text.'|'.(string)($answer['fraction']??$answer->fraction??''));
            if(isset($seen[$dedupe]))continue;
            $seen[$dedupe]=true;

            $fraction=(float)($answer['fraction']??$answer->fraction??0);
            if(abs($fraction)>1)$fraction/=100;

            $feedback=$this->nodeText($answer->feedback??null);
            Database::execute(
                "INSERT INTO question_answers(question_id,answer_html,fraction,feedback_html,position,source_id)
                 VALUES(?,?,?,?,?,?)",
                [$target,$text,$fraction,$feedback,++$pos,$answerSource?(string)$answerSource:null]
            );
        }

        // Algumas questões V/F podem vir sem bloco answers reconhecível.
        if($type==='truefalse'&&$pos===0){
            Database::execute(
                "INSERT INTO question_answers(question_id,answer_html,fraction,feedback_html,position)
                 VALUES(?, 'Verdadeiro',1,'',1),(?, 'Falso',0,'',2)",
                [$target,$target]
            );
        }

        return ['target'=>$target,'source'=>$source,'type'=>$type,'created'=>true];
    }

    private function sourceId(SimpleXMLElement $node): int
    {
        $id=(int)($node['id']??0);
        if(!$id)$id=(int)($node->id??0);
        return $id;
    }

    private function nodeText(mixed $node): string
    {
        if($node===null)return '';
        if($node instanceof SimpleXMLElement){
            if(isset($node->text))return trim((string)$node->text);
            return trim((string)$node);
        }
        return trim((string)$node);
    }

    private function deepFirst(SimpleXMLElement $node,string $name): ?string
    {
        $found=$node->xpath('.//'.$name)?:[];
        return $found?trim((string)$found[0]):null;
    }

    private function xml(string $file): SimpleXMLElement
    {
        $x=simplexml_load_file($file,SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);
        if(!$x)throw new \RuntimeException('XML inválido: '.basename($file));
        return $x;
    }
}
