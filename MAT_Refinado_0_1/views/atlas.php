<?php
require_once MAT_ROOT.'/app/AtlasReais.php';
$atlasErro='';$atlas=AtlasReais::blank();$atlasResume=['cur_total'=>0,'cur_com_ano'=>0,'cur_sem_ano'=>0,'cur_aprovados'=>0,'cur_homologados'=>0,'cur_ano_validado'=>0,'cur_ano_extraido'=>0,'legado_total'=>0,'legado_aprovados'=>0];$atlasSelected=(string)($_GET['era']??'');
if(!array_key_exists($atlasSelected,AtlasReais::ERAS))$atlasSelected='';
$atlasDetails=[];$atlasPending=[];
{
    try {
        $overview=AtlasReais::fetchOverview(db());
        $atlas=$overview['eras'];$atlasResume=$overview['resumo'];
        if($atlasSelected!=='' && is_admin()) {
            $atlasDetails=AtlasReais::rows(db(),$atlasSelected,false);
            $atlasPending=AtlasReais::rows(db(),$atlasSelected,true);
        }
    } catch(Throwable $e) {error_log('MAT Atlas: '.$e->getMessage());$atlasErro='Falha ao consultar os registros reais do Atlas.';}
}
$atlasAcervo=array_sum(array_column($atlas,'acervo'));
$atlasAprovados=array_sum(array_column($atlas,'aprovados'));
$atlasImportados=(int)$atlasResume['cur_total'];
?>
<header class="page-heading"><div class="section-overline">ATLAS CRONOLÓGICO <span class="overline-sep">/</span> 03</div><h1>Sete eras. <em>Registros reais.</em></h1><p>Cada dossiê da curadoria aparece na contagem geral, mesmo quando ainda não tem data classificável. Os registros do acervo legado e os candidatos científicos são grupos diferentes.</p></header>
<?php if($atlasErro): ?><div class="annotation-note" role="alert"><?=h($atlasErro)?></div><?php endif; ?>
<div class="annotation-note" style="margin-bottom:24px"><span class="annotation-star">✳</span><div><strong>Curadoria: <?=$atlasImportados?> candidatos · <?=$atlasResume['cur_aprovados']?> aprovados pelo pesquisador · <?=$atlasResume['cur_homologados']?> homologados</strong><p><strong><?=$atlasResume['cur_com_ano']?> com ano classificável (<?=$atlasResume['cur_ano_validado']?> validados e <?=$atlasResume['cur_ano_extraido']?> apenas extraídos) · <?=$atlasResume['cur_sem_ano']?> sem ano classificável.</strong> Separadamente, o acervo legado possui <?=$atlasAcervo?> registros com ano (<?=$atlasAprovados?> aprovados no legado). Os dois bancos não são somados nem confundidos.</p></div></div>
<div class="atlas-grid">
<?php $j=0;foreach($atlas as $key=>$info):$j++; ?>
<a href="<?=mat_url('atlas',['era'=>$key])?>#detalhes-era" class="era-card era-<?=$j?>"><div class="era-card-top"><span>CAPÍTULO <?=h($info['romano'])?></span><span>↗</span></div><div class="era-emblem"><span><?=h($info['romano'])?></span><i></i></div><div class="era-card-bottom"><span class="era-dates"><?=h($info['datas'])?></span><h2><?=h($info['nome'])?></h2><p><?=h($info['descricao'])?></p><div class="era-count"><strong><?=$info['acervo']?></strong><span><?=($info['acervo']===1?'registro':'registros')?> no acervo</span></div><div class="era-count" style="margin-top:6px"><strong><?=$info['preparacao']?></strong><span>candidatos da curadoria nesta era · <?=$info['cur_aprovados']?> aprovado(s) · <?=$info['cur_homologados']?> homologado(s)</span></div></div></a>
<?php endforeach;?></div>
<?php if($atlasSelected!==''): $current=$atlas[$atlasSelected]; ?>
<section id="detalhes-era" class="paper-panel" style="margin-top:28px;padding:24px">
<h2><?=h($current['nome'])?> — registros por ano</h2><p>Acervo legado: <?=$current['acervo']?> (<?=$current['aprovados']?> aprovados) · Curadoria: <?=$current['preparacao']?> (<?=$current['cur_aprovados']?> aprovados e <?=$current['cur_homologados']?> homologados)</p>
<?php if(!is_admin()): ?><p>Entre como pesquisador para examinar a fila de preparação e os registros individuais.</p>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:24px">
<div><h3>Acervo</h3><?php if(!$atlasDetails):?><p>Nenhum registro do acervo nesta era.</p><?php else:?><ol><?php foreach($atlasDetails as $m):?><li><a href="<?=mat_url('marco',['id'=>(int)$m['id']])?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · <?=h($m['status'])?></li><?php endforeach;?></ol><?php endif;?></div>
<div><h3>Candidatos em curadoria</h3><?php if(!$atlasPending):?><p>Nenhum candidato pendente nesta era.</p><?php else:?><ol><?php foreach($atlasPending as $m):?><li><a href="<?=mat_url('rodadas',['id'=>(string)($m['id_extracao']??'')])?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · estado: <?=h((string)$m['status'])?></li><?php endforeach;?></ol><?php endif;?></div>
</div>
<p class="research-small">São exibidos no máximo 45 exemplos de cada grupo por era. Os totais por era excluem candidatos sem ano classificável; esses estão contados no resumo geral.</p>
<?php endif;?></section>
<?php endif; ?>
<div class="annotation-note" style="margin-top:26px"><span class="annotation-star">✳</span><div><strong>Sobre os recortes históricos</strong><p>Os intervalos entre eras são convenções de apresentação, não critérios de pontuação. A classificação por ano corrige a divergência entre nomes históricos do banco e do menu.</p></div></div>
<div class="editorial-head editorial-head-late"><div><div class="section-overline">PIRÂMIDE HISTÓRICA <span class="overline-sep">/</span> DADOS DO ACERVO</div><h2>As camadas <em>do arquivo.</em></h2></div></div>
<section class="pyramid-panel"><div class="pyramid-notes"><span>REGISTROS POR PERÍODO</span><p>A largura é apenas visual. Os números são contagens do acervo legado; a curadoria está descrita nos cartões e no resumo geral.</p></div><div class="pyramid-stack" aria-label="Pirâmide de períodos históricos">
<?php $pyr=0;foreach(array_reverse($atlas,true) as $index=>$info):$pyr++;$maxwidth=37+($pyr-1)*10;?><a href="<?=mat_url('atlas',['era'=>$index])?>#detalhes-era" class="pyramid-stratum" style="width:<?=$maxwidth?>%"><span><?=h($info['nome'])?></span><strong><?=$info['acervo']?></strong></a><?php endforeach;?></div></section>
