<?php
use Tecnodata\Lms\Core\Csrf;

$renderActivities=function(array $section,int $depth=0) use (&$renderActivities,$enrollment):void{
?>
<section class="learning-section <?=$depth?'learning-subsection':''?>">
<div class="learning-section-title"><div><?php if($depth):?><span class="type-pill">Subseção</span><?php endif;?><h2><?=e($section['title'])?></h2></div></div>
<?php foreach($section['activities'] as $a):$data=json_decode($a['content_json']?:'{}',true)?:[];?>
<article class="learning-item <?=$a['completed']?'done':''?>">
<div class="activity-icon"><i class="bi <?=$a['completed']?'bi-check-lg':($a['type']==='video'?'bi-play-fill':($a['type']==='quiz'?'bi-ui-checks':'bi-file-earmark-text'))?>"></i></div>
<div class="flex-grow-1"><strong><?=e($a['title'])?></strong><small><?=e($a['type'])?></small>
<?php if(in_array($a['type'],['video','url'],true)&&!empty($data['url'])):?><a class="btn btn-sm btn-outline-secondary mt-2" target="_blank" rel="noopener" href="<?=e($data['url'])?>"><?=$a['type']==='video'?'Abrir vídeo':'Abrir conteúdo externo'?></a>
<?php elseif($a['type']==='resource'&&!empty($data['file_id'])):?><a class="btn btn-sm btn-outline-secondary mt-2" target="_blank" href="/files/<?=e($data['file_id'])?>">Abrir material</a>
<?php elseif(!empty($data['html'])):?><div class="activity-preview"><?=nl2br(e(mb_strimwidth(strip_tags((string)$data['html']),0,1200,'…')))?></div><?php endif;?>
</div>
<?php if($a['type']==='quiz'):?><a class="btn btn-sm btn-brand" href="/student/enrollment/<?=$enrollment['id']?>/quiz/<?=$a['id']?>"><?=$a['completed']?'Ver / tentar novamente':'Iniciar avaliação'?></a>
<?php elseif(!$a['completed']):?><form method="post" action="/student/activity/<?=$a['id']?>/complete"><?=Csrf::field()?><input type="hidden" name="enrollment_id" value="<?=$enrollment['id']?>"><button class="btn btn-sm btn-brand">Concluir</button></form>
<?php else:?><span class="text-success fw-semibold">Concluído</span><?php endif;?>
</article>
<?php endforeach;?>
<?php foreach($section['children'] as $child)$renderActivities($child,$depth+1);?>
</section>
<?php };?>
<div class="page-heading"><div><span class="eyebrow">CURSO</span><h1><?=e($enrollment['name'])?></h1><div class="progress-track wide"><span style="width:<?=min(100,(float)$enrollment['progress_percent'])?>%"></span></div><small><?=e($enrollment['progress_percent'])?>% concluído · <?=e($enrollment['status'])?></small></div><a class="btn btn-outline-secondary" href="/student">Meus cursos</a></div>
<div class="learning-list"><?php foreach($sections as $s)$renderActivities($s);?></div>
