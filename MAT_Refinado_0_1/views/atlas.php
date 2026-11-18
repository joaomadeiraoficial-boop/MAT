<?php
require_once MAT_ROOT.'/app/AtlasReais.php';
require_once MAT_ROOT.'/app/PontosInflexao.php';
$atlasErro='';$atlas=AtlasReais::blank();$atlasResume=['cur_total'=>0,'cur_com_ano'=>0,'cur_sem_ano'=>0,'cur_aprovados'=>0,'cur_homologados'=>0,'cur_ano_validado'=>0,'cur_ano_extraido'=>0,'legado_total'=>0,'legado_aprovados'=>0];$atlasSelected=(string)($_GET['era']??'');
if(!array_key_exists($atlasSelected,AtlasReais::ERAS))$atlasSelected='';
$atlasSub=(string)($_GET['sub']??'');
if($atlasSub!=='' && !isset(AtlasReais::SUBERAS[$atlasSelected][$atlasSub]))$atlasSub='';
$atlasRangeKey=$atlasSelected!==''&&$atlasSub!==''?$atlasSelected.'::'.$atlasSub:$atlasSelected;
$atlasDetails=[];$atlasPending=[];$atlasSubCounts=[];
{
    try {
        $overview=AtlasReais::fetchOverview(db());
        $atlas=$overview['eras'];$atlasResume=$overview['resumo'];
        $atlasSubCounts=AtlasReais::fetchSubCounts();
        if($atlasSelected!=='' && is_admin()) {
            $atlasDetails=AtlasReais::rows(db(),$atlasRangeKey,false);
            $atlasPending=AtlasReais::rows(db(),$atlasRangeKey,true);
        }
    } catch(Throwable $e) {error_log('MAT Atlas: '.$e->getMessage());$atlasErro='Falha ao consultar os registros reais do Atlas.';}
}
$atlasImportados=(int)$atlasResume['cur_total'];
$inflexSelected=(string)($_GET['inflexao']??'');
if(!array_key_exists($inflexSelected,PontosInflexao::PONTOS))$inflexSelected='';
$inflexoes=[];$inflexResumo=null;$inflexErro='';$inflexDetails=[];$inflexPending=[];
{
    try {
        $inflexOver=PontosInflexao::fetchOverview(db());
        $inflexoes=$inflexOver['pontos'];$inflexResumo=$inflexOver['resumo'];
        if($inflexSelected!=='' && is_admin()) {
            $inflexDetails=PontosInflexao::rows(db(),$inflexSelected,false);
            $inflexPending=PontosInflexao::rows(db(),$inflexSelected,true);
        }
    } catch(Throwable $e) {error_log('MAT Atlas inflexoes: '.$e->getMessage());$inflexErro='Falha ao consultar os pontos de inflexão tecnológica.';}
}
?>
<header class="page-heading atlas-hero">
  <div class="section-overline">ATLAS CRONOLÓGICO <span class="overline-sep">/</span> 03</div>
  <h1>Sete eras. <em>Registros reais.</em></h1>
  <p>Cada dossiê da curadoria aparece na contagem geral, mesmo quando ainda não tem data classificável. Os candidatos sem ano ficam no resumo geral, nunca fora do painel.</p>
  <nav class="atlas-jump" aria-label="Seções do Atlas">
    <a href="#sete-eras"><span>01</span> As sete eras</a>
    <a href="#pontos-inflexao"><span>02</span> Pontos de inflexão</a>
    <a href="#piramide"><span>03</span> Pirâmide do arquivo</a>
    <a class="atlas-jump-out" href="<?=mat_url('linha')?>"><span>04</span> Linha do tempo <b>↗</b></a>
  </nav>
</header>
<?php if($atlasErro): ?><div class="annotation-note" role="alert"><?=h($atlasErro)?></div><?php endif; ?>
<section class="atlas-summary" aria-label="Resumo da curadoria no Atlas">
  <div class="atlas-stat"><span>01 / CANDIDATOS</span><strong><?=number_format($atlasImportados,0,',','.')?></strong><small>Dossiês catalogados na curadoria</small></div>
  <div class="atlas-stat"><span>02 / COM ANO</span><strong><?=number_format((int)$atlasResume['cur_com_ano'],0,',','.')?></strong><small><?=number_format((int)$atlasResume['cur_ano_validado'],0,',','.')?> validados · <?=number_format((int)$atlasResume['cur_ano_extraido'],0,',','.')?> extraídos</small></div>
  <div class="atlas-stat"><span>03 / APROVADOS</span><strong><?=number_format((int)$atlasResume['cur_aprovados'],0,',','.')?></strong><small>Decisão humana registrada e auditável</small></div>
  <div class="atlas-stat"><span>04 / SEM ANO</span><strong><?=number_format((int)$atlasResume['cur_sem_ano'],0,',','.')?></strong><small>Fora da sequência, dentro do acervo</small></div>
  <p class="atlas-summary-note">Os intervalos das eras são convenções de apresentação por ano; a contagem usa sempre o ano classificável do candidato. <b>Ano extraído não equivale a ano validado</b> — apenas o segundo foi confirmado pelo pesquisador.</p>
</section>
<div class="editorial-head atlas-section-head" id="sete-eras">
  <div><div class="section-overline">CAMADA 01 <span class="overline-sep">/</span> CRONOLOGIA</div><h2>As sete eras <em>do arquivo.</em></h2></div>
  <span class="atlas-section-meta">Clique numa era para abrir os registros</span>
</div>
<div class="atlas-grid">
<?php $j=0;foreach($atlas as $key=>$info):$j++; $eraImg=AtlasReais::imagem($key); $eraSubs=AtlasReais::SUBERAS[$key]??[]; ?>
<div class="era-card era-<?=$j?><?=$eraImg?' has-img':''?>"><?php if($eraImg): ?><img class="era-media" src="<?=h($eraImg)?>" alt=""><?php endif; ?><a href="<?=mat_url('atlas',['era'=>$key])?>#detalhes-era" class="era-card-main"><div class="era-card-top"><span>CAPÍTULO <?=h($info['romano'])?></span><span>↗</span></div><div class="era-emblem"><span><?=h($info['romano'])?></span><i></i></div><div class="era-card-bottom"><span class="era-dates"><?=h($info['datas'])?></span><h2><?=h($info['nome'])?></h2><p><?=h($info['descricao'])?></p><div class="era-count" style="margin-top:0"><strong><?=$info['preparacao']?></strong><span>candidatos da curadoria nesta era · <?=$info['cur_aprovados']?> aprovado(s)</span></div></div></a><?php if($eraSubs): ?><details class="era-subs"><summary>+ Sub-períodos (<?=count($eraSubs)?>)</summary><ul><?php foreach($eraSubs as $sk=>$sub):$subImg=AtlasReais::subImagem($key,$sk);?><li><a href="<?=mat_url('atlas',['era'=>$key,'sub'=>$sk])?>#detalhes-era"><?php if($subImg): ?><img class="era-sub-thumb" src="<?=h($subImg)?>" alt="" width="150" height="27"><?php endif; ?><span class="era-sub-text"><b><?=h($sub['nome'])?></b><small><?=h($sub['datas'])?> · <?=($atlasSubCounts[$key]['subs'][$sk]['total']??0)?> registros</small></span></a></li><?php endforeach;?></ul></details><?php endif; ?></div>
<?php endforeach;?></div>
<?php if($atlasSelected!==''): $current=$atlas[$atlasSelected]; $currentSub=$atlasSub!==''?AtlasReais::SUBERAS[$atlasSelected][$atlasSub]:null; ?>
<section id="detalhes-era" class="paper-panel atlas-detail">
  <div class="atlas-detail-top">
    <div>
      <div class="section-overline">DETALHE DA ERA <span class="overline-sep">/</span> CAPÍTULO <?=h($current['romano'])?></div>
      <h2><?=h($currentSub['nome']??$current['nome'])?> — registros por ano</h2>
    </div>
    <a class="atlas-detail-close" href="<?=mat_url('atlas')?>#sete-eras">Fechar detalhe ✕</a>
  </div>
<?php if($currentSub!==null && isset($atlasSubCounts[$atlasSelected]['subs'][$atlasSub])): $sc=$atlasSubCounts[$atlasSelected]['subs'][$atlasSub]; ?>
  <div class="atlas-detail-stats">
    <span><strong><?=number_format((int)$sc['total'],0,',','.')?></strong> candidatos com ano</span>
    <span><strong><?=number_format((int)$sc['aprovados'],0,',','.')?></strong> aprovados</span>
    <span><strong><?=count(AtlasReais::SUBERAS[$atlasSelected]??[])?></strong> sub-períodos na era</span>
  </div>
<?php else: ?>
  <div class="atlas-detail-stats">
    <span><strong><?=number_format((int)$current['preparacao'],0,',','.')?></strong> candidatos com ano</span>
    <span><strong><?=number_format((int)$current['cur_aprovados'],0,',','.')?></strong> aprovados</span>
    <span><strong><?=count(AtlasReais::SUBERAS[$atlasSelected]??[])?></strong> sub-períodos na era</span>
  </div>
<?php endif; ?>
<?php $detalheImg=$currentSub!==null?AtlasReais::subImagem($atlasSelected,$atlasSub):AtlasReais::imagem($atlasSelected); ?><?php if($detalheImg): ?><img class="era-sub-banner" src="<?=h($detalheImg)?>" alt=""><?php endif; ?>
<?php if(AtlasReais::SUBERAS[$atlasSelected]??[]): ?><div class="era-sub-chips"><a href="<?=mat_url('atlas',['era'=>$atlasSelected])?>#detalhes-era" class="<?=$atlasSub===''?'active':''?>">Período inteiro · <?=$current['preparacao']?></a><?php foreach(AtlasReais::SUBERAS[$atlasSelected] as $sk=>$sub):?><a href="<?=mat_url('atlas',['era'=>$atlasSelected,'sub'=>$sk])?>#detalhes-era" class="<?=$atlasSub===$sk?'active':''?>"><?=h($sub['nome'])?> · <?=($atlasSubCounts[$atlasSelected]['subs'][$sk]['total']??0)?></a><?php endforeach;?></div><?php endif; ?>
<?php if(!is_admin()): ?><p class="atlas-detail-locked">Entre como pesquisador para examinar a fila de preparação e os registros individuais.</p>
<?php else: ?>
<div class="atlas-detail-list">
<div><h3>Candidatos em curadoria</h3><?php if(!$atlasPending):?><p>Nenhum candidato nesta era.</p><?php else:?><ol><?php foreach($atlasPending as $m):?><li><a href="<?=mat_url('linha')?>#mat-linha-cand-<?=h((string)($m['id_extracao']??''))?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · estado: <?=h((string)$m['status'])?></li><?php endforeach;?></ol><?php endif;?></div>
</div>
<p class="research-small">São exibidos no máximo 45 exemplos de cada grupo por era. Os totais por era excluem candidatos sem ano classificável; esses estão contados no resumo geral.</p>
<?php endif;?></section>
<?php endif; ?>
<?php if($inflexErro): ?><div class="annotation-note" role="alert" style="margin-top:24px"><?=h($inflexErro)?></div><?php endif; ?>
<?php if($inflexoes): ?>
<div class="editorial-head atlas-section-head" id="pontos-inflexao">
  <div><div class="section-overline">PONTOS DE INFLEXÃO TECNOLÓGICA <span class="overline-sep">/</span> CAMADA 02</div><h2>Os motores <em>da transformação.</em></h2></div>
  <span class="atlas-section-meta"><?=count($inflexoes)?> pontos candidatos · intervalos podem se sobrepor</span>
</div>
<div class="annotation-note atlas-note" style="margin-bottom:24px"><span class="annotation-star">✳</span><div><strong>Candidatos a ponto de inflexão — não singularidades comprovadas.</strong><p>Cada ponto propõe uma ruptura tecnológica documentável: o que a impulsionou, quais tecnologias foram transformadas e o que se seguiu. O termo é distinto da singularidade tecnológica associada à inteligência artificial. Os intervalos são convenções de apresentação e podem se sobrepor, de modo que um dossiê pode apoiar mais de um ponto. Elevar um candidato a ponto consolidado permanece decisão humana, sujeita a auditoria.</p></div></div>
<div class="atlas-grid inflex-grid">
<?php $k=0;foreach($inflexoes as $key=>$info):$k++; $inflexImg=PontosInflexao::imagem($key); ?>
<a href="<?=mat_url('atlas',['inflexao'=>$key])?>#detalhes-inflexao" class="era-card inflex-card<?=$inflexImg?' has-img':''?>"><?php if($inflexImg): ?><span class="inflex-media" style="background-image:url('<?=h($inflexImg)?>')"></span><div class="inflex-body"><?php endif; ?><div class="era-card-top"><span>PONTO <?=str_pad((string)$k,2,'0',STR_PAD_LEFT)?></span><span>↗</span></div><div class="era-card-bottom"><span class="era-dates"><?=h($info['datas'])?></span><h2><?=h($info['nome'])?></h2><p><?=h($info['descricao'])?></p><div class="inflex-fatores"><div><strong>Motor</strong><span><?=h($info['motor'])?></span></div><div><strong>Tecnologias transformadas</strong><span><?=h($info['tecnologias'])?></span></div><div><strong>Consequências</strong><span><?=h($info['consequencias'])?></span></div></div><div class="era-count" style="margin-top:0"><strong><?=$info['preparacao']?></strong><span>candidatos da curadoria · <?=$info['cur_aprovados']?> aprovado(s)</span></div></div><?php if($inflexImg):?></div><?php endif;?></a>
<?php endforeach;?></div>
<?php if($inflexSelected!==''): $current=$inflexoes[$inflexSelected]; ?>
<section id="detalhes-inflexao" class="paper-panel atlas-detail atlas-detail-inflex">
  <div class="atlas-detail-top">
    <div>
      <div class="section-overline">DETALHE DO PONTO <span class="overline-sep">/</span> CAMADA 02</div>
      <h2><?=h($current['nome'])?> — registros vinculados</h2>
    </div>
    <a class="atlas-detail-close" href="<?=mat_url('atlas')?>#pontos-inflexao">Fechar detalhe ✕</a>
  </div>
  <div class="atlas-detail-stats">
    <span><strong><?=number_format((int)$current['preparacao'],0,',','.')?></strong> candidatos com ano</span>
    <span><strong><?=number_format((int)$current['cur_aprovados'],0,',','.')?></strong> aprovados</span>
    <span><strong><?=h($current['datas'])?></strong> intervalo do ponto</span>
  </div>
<?php if(!is_admin()): ?><p class="atlas-detail-locked">Entre como pesquisador para examinar os registros individuais vinculados ao ponto.</p>
<?php else: ?>
<div class="atlas-detail-list">
<div><h3>Candidatos em curadoria</h3><?php if(!$inflexPending):?><p>Nenhum candidato da curadoria no intervalo deste ponto.</p><?php else:?><ol><?php foreach($inflexPending as $m):?><li><a href="<?=mat_url('linha')?>#mat-linha-cand-<?=h((string)($m['id_extracao']??''))?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>) · estado: <?=h((string)$m['status'])?></li><?php endforeach;?></ol><?php endif;?></div>
</div>
<p class="research-small">São exibidos no máximo 45 exemplos de cada grupo. Como os intervalos podem se sobrepor, o mesmo dossiê pode figurar também em outro ponto de inflexão.</p>
<?php endif;?></section>
<?php endif; ?>
<?php endif; ?>
<div class="annotation-note atlas-note" style="margin-top:26px"><span class="annotation-star">✳</span><div><strong>Sobre os recortes históricos</strong><p>Os intervalos entre eras são convenções de apresentação, não critérios de pontuação. A classificação por ano corrige a divergência entre nomes históricos do banco e do menu.</p></div></div>
<div class="editorial-head editorial-head-late atlas-section-head" id="piramide">
  <div><div class="section-overline">PIRÂMIDE HISTÓRICA <span class="overline-sep">/</span> CURADORIA</div><h2>As camadas <em>do arquivo.</em></h2></div>
  <span class="atlas-section-meta"><?=number_format((int)$atlasResume['cur_com_ano'],0,',','.')?> candidatos com ano · faixa escura = aprovados</span>
</div>
<section class="pyramid-panel atlas-pyramid"><div class="pyramid-notes"><span>CAMADAS DO ARQUIVO · CURADORIA POR PERÍODO</span><p>Cada camada da pirâmide reúne os candidatos da curadoria com ano classificável no período; a faixa central mais escura marca os aprovados pelo pesquisador. A decisão de aprovar permanece humana e sujeita a auditoria.</p>
<div class="pyr-legend" aria-label="Legenda da pirâmide"><span><i class="pyr-key" aria-hidden="true"></i>Candidatos com ano</span><span><i class="pyr-key pyr-key-fill" aria-hidden="true"></i>Aprovados pelo pesquisador</span></div>
<a class="text-link" href="<?=mat_url('linha')?>">Ver a sequência completa <span>↗</span></a></div><div>
<div class="pyramid-stack" aria-label="Candidatos da curadoria por período">
<?php $pyrMax=max(1,max(array_column($atlas,'preparacao')));foreach(array_reverse($atlas,true) as $index=>$info):$pyrAp=(int)$info['cur_aprovados'];$pyrTot=(int)$info['preparacao'];$pyrW=$pyrTot>0?(int)min(100,round(24+76*$pyrTot/$pyrMax)):24;$pyrFill=$pyrTot>0?(int)round(100*$pyrAp/$pyrTot):0;$pyrLeft=(int)(($pyrFill>0?(100-$pyrFill):100)/2);?><a href="<?=mat_url('atlas',['era'=>$index])?>#detalhes-era" class="pyramid-stratum<?=$pyrTot===0?' pyr-empty':''?>" style="width:<?=$pyrW?>%"><span class="pyr-label"><strong><?=h($info['nome'])?></strong><small><?=$pyrTot===0?'Nenhum candidato com ano ainda':h((string)$pyrAp).' aprovado(s) · '.h((string)$pyrTot).' candidato(s) com ano'?></small></span><?php if($pyrFill>0):?><span class="pyr-fill" style="width:<?=$pyrFill?>%;left:<?=$pyrLeft?>%"></span><?php endif; ?></a><?php endforeach;?></div>
<?php if(is_admin()): $pyrAprov=[];foreach($atlas as $key=>$info){$pyrAprov[$key]=array_values(array_filter(AtlasReais::rows(db(),$key,true),static fn(array $r): bool=>in_array((string)$r['status'],['APROVADO','HOMOLOGADO'],true)));} ?>
<div class="pyramid-approved">
<?php foreach($pyrAprov as $key=>$itens): $info=$atlas[$key]; ?>
<div class="pyr-approved-era"><h3><?=h($info['nome'])?> · <?=count($itens)?> aprovado(s)</h3><?php if(!$itens):?><p class="pyr-none">Nenhum aprovado neste período ainda.</p><?php else:?><ol><?php foreach($itens as $m):?><li><a href="<?=mat_url('linha')?>#mat-linha-cand-<?=h((string)($m['id_extracao']??''))?>"><?=h($m['titulo'])?></a> (<?=h((string)$m['ano'])?>)</li><?php endforeach;?></ol><?php endif;?></div>
<?php endforeach;?></div>
<?php endif;?></div></section>
<footer class="atlas-foot">
  <div>
    <div class="section-overline">CONTINUAR A LEITURA</div>
    <p>O Atlas organiza os dossiês por era; a Linha do tempo mostra a mesma curadoria em sequência cronológica, com busca e filtros. Cada base permanece identificada e nunca é misturada à outra.</p>
  </div>
  <div class="atlas-foot-links">
    <a class="atlas-btn" href="<?=mat_url('linha')?>">Percorrer a linha do tempo ↗</a>
    <a class="atlas-textlink" href="<?=mat_url('metodo')?>">Entender as bases ↗</a>
    <a class="atlas-textlink" href="#conteudo">Voltar ao topo ↑</a>
  </div>
</footer>
