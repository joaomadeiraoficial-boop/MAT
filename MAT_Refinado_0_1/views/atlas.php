<?php $all=all_markers();$eras=get_periods();$erasInfo=[
'Pré-história'=>['I','Antes de 3500 a.C.','As primeiras ferramentas e os vestígios de modos de vida.'],
'Antiguidade'=>['II','3500 a.C. — 476','Escrita, engenharia, transmissão de conhecimento.'],
'Idade Média'=>['III','476 — 1453','Ofícios, materiais e circulação de técnicas.'],
'Idade Moderna'=>['IV','1453 — 1789','Instrumentação, navegação e mecanização crescente.'],
'Era Industrial'=>['V','1789 — 1945','Energia, fábricas, transportes e indústria.'],
'Era Digital'=>['VI','1945 — 1999','Computação, redes e sistemas eletrônicos.'],
'Era Contemporânea'=>['VII','2000 — presente','Tecnologias em rede e novas fronteiras de pesquisa.']
]; ?>
<header class="page-heading"><div class="section-overline">ATLAS CRONOLÓGICO <span class="overline-sep">/</span> 03</div><h1>Sete eras. <em>Muitas histórias.</em></h1><p>Uma organização para navegar pelos registros, não uma escala de importância ou uma medida de aceleração.</p></header>
<div class="atlas-grid">
<?php foreach($eras as $j=>$name):$info=$erasInfo[$name];$num=$stats['periods'][$name]??0;?>
<a href="<?=mat_url('acervo',['periodo'=>$name])?>" class="era-card era-<?=$j+1?>"><div class="era-card-top"><span>CAPÍTULO <?=$info[0]?></span><span>↗</span></div><div class="era-emblem"><span><?=$info[0]?></span><i></i></div><div class="era-card-bottom"><span class="era-dates"><?=h($info[1])?></span><h2><?=h($name)?></h2><p><?=h($info[2])?></p><div class="era-count"><strong><?=$num?></strong><span><?=($num===1?'registro':'registros')?> no acervo</span></div></div></a>
<?php endforeach;?></div>
<div class="annotation-note"><span class="annotation-star">✳</span><div><strong>Sobre os recortes históricos</strong><p>As fronteiras entre eras são convenções de periodização. A cronologia e a classificação serão documentadas e poderão ser revisadas mediante evidência.</p></div></div>

<div class="editorial-head editorial-head-late"><div><div class="section-overline">PIRÂMIDE HISTÓRICA <span class="overline-sep">/</span> ESTUDO VISUAL</div><h2>As camadas <em>do arquivo.</em></h2></div></div>
<section class="pyramid-panel"><div class="pyramid-notes"><span>ESTUDO Nº 07 / PIRÂMIDE DOS PERÍODOS</span><p>Sete estratos de tempo. A largura indica apenas a ordem visual das eras, não sua duração nem sua relevância.</p><a class="text-link" href="<?=mat_url('galeria')?>">Ver panfletos visuais ↗</a></div><div class="pyramid-stack" aria-label="Pirâmide navegável de períodos históricos">
<?php foreach(array_reverse($eras) as $index=>$name): $maxwidth=37 + $index*10; ?><a href="<?=mat_url('acervo',['periodo'=>$name])?>" class="pyramid-stratum" style="width:<?=$maxwidth?>%"><span><?=h($name)?></span><strong><?=$stats['periods'][$name]??0?></strong></a><?php endforeach;?></div></section>
