<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Fila de revisão'; $active='revisar';
$status='revisao';
$category=trim($_GET['categoria']??'');
$q=trim($_GET['q']??'');
$scope=trim($_GET['escopo']??'');
$nature=trim($_GET['natureza']??'');
$confidence=trim($_GET['confianca']??'');
$sql='SELECT m.*, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.marco_id=m.id) fontes_count, (SELECT COUNT(*) FROM duplicate_candidates d WHERE (d.marco_a=m.id OR d.marco_b=m.id) AND d.status="pendente") dup_count FROM marcos m WHERE m.status=?';
$params=[$status];
if($category!==''){ $sql.=' AND m.categoria=?'; $params[]=$category; }
if($q!==''){ $sql.=' AND (m.nome LIKE ? OR m.descricao LIKE ? OR m.inventores LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
if(in_array($scope,['sim','nao','revisar'],true)){ $sql.=' AND m.sugestao_elegibilidade=?'; $params[]=$scope; }
if($nature!==''){ $sql.=' AND m.natureza=?'; $params[]=$nature; }
if(in_array($confidence,['alta','media','baixa'],true)){ $sql.=' AND m.scope_confidence=?'; $params[]=$confidence; }
$sql.=' ORDER BY m.escopo_revisado ASC, FIELD(m.sugestao_elegibilidade,"revisar","sim","nao"), COALESCE(m.ano_ref,999999),m.id LIMIT 300';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $reviewItems=$stmt->fetchAll();
$cats=$pdo->query('SELECT categoria,COUNT(*) n FROM marcos GROUP BY categoria ORDER BY categoria')->fetchAll();
$natures=$pdo->query('SELECT natureza,COUNT(*) n FROM marcos GROUP BY natureza ORDER BY n DESC')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar panel"><form method="get" class="filters">
<input name="q" value="<?=e($q)?>" placeholder="Buscar marco...">
<select name="categoria"><option value="">Todas as categorias</option><?php foreach($cats as $c):?><option value="<?=e($c['categoria'])?>" <?=$category===$c['categoria']?'selected':''?>><?=e($c['categoria'])?> (<?=$c['n']?>)</option><?php endforeach;?></select>
<select name="escopo"><option value="">Toda triagem</option><option value="revisar" <?=$scope==='revisar'?'selected':''?>>Sugestão: revisar</option><option value="sim" <?=$scope==='sim'?'selected':''?>>Sugestão: elegível</option><option value="nao" <?=$scope==='nao'?'selected':''?>>Sugestão: fora do escopo</option></select>
<select name="natureza"><option value="">Todas as naturezas</option><?php foreach($natures as $n):?><option value="<?=e($n['natureza'])?>" <?=$nature===$n['natureza']?'selected':''?>><?=e(scope_nature_label($n['natureza']))?> (<?=$n['n']?>)</option><?php endforeach;?></select>
<select name="confianca"><option value="">Toda confiança</option><option value="alta" <?=$confidence==='alta'?'selected':''?>>Confiança alta</option><option value="media" <?=$confidence==='media'?'selected':''?>>Confiança média</option><option value="baixa" <?=$confidence==='baixa'?'selected':''?>>Confiança baixa</option></select>
<button class="btn">Filtrar</button></form><div><b><?=count($reviewItems)?></b> pendentes mostrados</div></div>
<div class="review-grid mt-lg">
<?php if(!$reviewItems):?><div class="panel empty">Nenhum registro pendente com esses filtros.</div><?php endif;?>
<?php foreach($reviewItems as $m):?>
  <article class="review-card">
    <div class="review-card-top"><span class="eyebrow">#<?=$m['id']?> · <?=e($m['origem']?:'Sem origem')?></span><span class="badge <?=$m['dup_count']?'badge-warning':'badge-muted'?>"><?=$m['dup_count']?> duplicata(s)</span></div>
    <h3><?=e($m['nome'])?></h3>
    <div class="scope-line"><span class="badge <?=eligibility_class($m['sugestao_elegibilidade'])?>">Sugestão: <?=e(eligibility_label($m['sugestao_elegibilidade']))?></span><span class="badge badge-muted"><?=e(scope_nature_label($m['natureza']))?></span><span class="badge badge-muted">Conf. <?=e($m['scope_confidence']??'baixa')?> · <?=e((string)($m['scope_score']??0))?></span><?php if($m['escopo_revisado']):?><span class="badge <?=eligibility_class($m['elegibilidade_temporis'])?>">Decisão: <?=e(eligibility_label($m['elegibilidade_temporis']))?></span><?php endif;?></div>
    <div class="meta-grid"><span><small>Ano</small><b><?=e(format_year_value($m['ano_ref']))?></b></span><span><small>Categoria</small><b><?=e($m['categoria'])?></b></span><span><small>Fontes</small><b><?=$m['fontes_count']?></b></span></div>
    <?php if(!empty($m['inventores'])):?><small class="subline">Inventor(es): <?=e(mb_strimwidth((string)$m['inventores'],0,120,'…','UTF-8'))?></small><?php endif;?>
    <p><?=e(mb_strimwidth((string)$m['descricao'],0,180,'…','UTF-8'))?></p>
    <div class="card-actions"><a class="btn primary" href="marco.php?id=<?=$m['id']?>">Revisar</a><button class="btn ghost quick-action" data-id="<?=$m['id']?>" data-action="approve">Aprovar</button><button class="btn danger ghost quick-action" data-id="<?=$m['id']?>" data-action="reject">Rejeitar</button></div>
  </article>
<?php endforeach;?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
