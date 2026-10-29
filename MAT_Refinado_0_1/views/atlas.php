<?php
require_once MAT_ROOT.'/app/AtlasReais.php';
$atlasErro='';$atlas=AtlasReais::blank();$atlasSelected=(string)($_GET['era']??'');
if(!array_key_exists($atlasSelected,AtlasReais::ERAS))$atlasSelected='';
$atlasDetails=[];$atlasPending=[];
if(!db())$atlasErro='MySQL indisponível: nenhum exemplo fictício será mostrado. Inicie o XAMPP.';
else {
    try {
        $atlas=AtlasReais::fetchCounts(db());
        if($atlasSelected!=='' && is_admin()) {
            $atlasDetails=AtlasReais::rows(db(),$atlasSelected,false);
            $atlasPending=AtlasReais::rows(db(),$atlasSelected,true);
        }
    } catch(Throwable $e) {error_log('MAT Atlas: '.$e->getMessage());$atlasErro='Falha ao consultar os registros reais do Atlas.';}
}
$atlasAcervo=array_sum(array_column($atlas,'acervo'));
$atlasAprovados=array_sum(array_column($atlas,'aprovados'));
$atlasImportados=array_sum(array_column($atlas,'preparacao'));
?>
<header class="page-heading"><div class="section-overline">ATLAS CRONOLÓGICO <span class="overline-sep">/</span> 03</div><h1>Sete eras. <em>Registros reais.</em></h1><p>Os acontecimentos são agrupados pelo ano, não pela etiqueta textual do período. <strong>Acervo, aprovações e candidatos em preparação são contagens diferentes.</strong></p></header>
<?php if($atlasErro): ?><div class="annotation-note" role="alert"><?=h($atlasErro)?></div><?php endif; ?>
<div class="annotation-note" style="margin-bottom:24px"><span class="annotation-star">✳</span><div><strong>Acervo: <?=$atlasAcervo?> registros · <?=$atlasAprovados?> aprovados · <?=$atlasImportados?> itens em preparação</strong><p>Os itens em preparação são extrações e menções cronológicas ainda não verificadas; não são marcos aprovados nem integram a Têmporis. Os números são calculados diretamente do MySQL.</p></div></div>
<div class="atlas-grid">
<?php $j=0;foreach($atlas as $key=>$info):$j++; ?>
<a href="<?=mat_url('atlas',['era'=>$key])?>#detalhes-era" class="era-card era-<?=$j?>"><div class="era-card-top"><span>CAPÍTULO <?=h($info['romano'])?></span><span>↗</span></div><div class="era-emblem"><span><?=h($info['romano'])?></span><i></i></div><div class="era-card-bottom"><span class="era-dates"><?=h($info['datas'])?></span><h2><?=h($info['nome'])?></h2><p><?=h($info['descricao'])?></p><div class="era-count"><strong><?=$info['acervo']?></strong><span><?=($info['acervo']===1?'registro':'registros')?> no acervo</span></div><div class="era-count" style="margin-top:6px"><strong><?=$info['preparacao']?></strong><span>candidatos em preparação · <?=$info['aprovados']?> aprovado(s)</span></div></div></a>
<?php endforeach;?></div>
<?php if($atlasSelected!==''): $current=$atlas[$atlasSelected]; ?>
<section id="detalhes-era" class="paper-panel" style="margin-top:28px;padding:24px">
<h2><?=h($current['nome'])?> — registros por ano</h2><p>Acervo: <?=$current['acervo']?> · Aprovados: <?=$current['aprovados']?> · Preparação: <?=$current['preparacao']?></p>
<?php if(!is_admin()): ?><p>Entre como pesquisador para examinar a fila de preparação e os registros individuais.</p>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:24px">
<div><h3>Acervo</h3><?php if(!$atlasDetails):?><p>Nenhum registro do acervo nesta era.</p><?php else:?><ol><?php foreach($atlasDetails as $m):?><li><a href="<?=mat_url('marco',['id'=>(int)$m['id']])?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · <?=h($m['status'])?></li><?php endforeach;?></ol><?php endif;?></div>
<div><h3>Candidatos em preparação</h3><?php if(!$atlasPending):?><p>Nenhum candidato pendente nesta era.</p><?php else:?><ol><?php foreach($atlasPending as $m):?><li><a href="assistente_ia.php?tipo=import&amp;id=<?=(int)$m['id']?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · não validado</li><?php endforeach;?></ol><?php endif;?></div>
</div>
<p class="research-small">São exibidos no máximo 45 exemplos de cada grupo por era. Os totais acima incluem todos os registros daquele grupo.</p>
<?php endif;?></section>
<?php endif; ?>
<div class="annotation-note" style="margin-top:26px"><span class="annotation-star">✳</span><div><strong>Sobre os recortes históricos</strong><p>Os intervalos entre eras são convenções de apresentação, não critérios de pontuação. A classificação por ano corrige a divergência entre nomes históricos do banco e do menu.</p></div></div>
<div class="editorial-head editorial-head-late"><div><div class="section-overline">PIRÂMIDE HISTÓRICA <span class="overline-sep">/</span> DADOS DO ACERVO</div><h2>As camadas <em>do arquivo.</em></h2></div></div>
<section class="pyramid-panel"><div class="pyramid-notes"><span>REGISTROS POR PERÍODO</span><p>A largura é apenas visual. Os números são contagens reais do acervo; os candidatos pendentes aparecem nos cartões acima, separadamente.</p></div><div class="pyramid-stack" aria-label="Pirâmide de períodos históricos">
<?php $pyr=0;foreach(array_reverse($atlas,true) as $index=>$info):$pyr++;$maxwidth=37+($pyr-1)*10;?><a href="<?=mat_url('atlas',['era'=>$index])?>#detalhes-era" class="pyramid-stratum" style="width:<?=$maxwidth?>%"><span><?=h($info['nome'])?></span><strong><?=$info['acervo']?></strong></a><?php endforeach;?></div></section>
