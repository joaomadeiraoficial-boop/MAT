<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Duplicatas'; $active='duplicatas';
$stmt=$pdo->query('SELECT d.*,a.nome a_nome,a.ano_ref a_ano,a.categoria a_cat,a.status a_status,b.nome b_nome,b.ano_ref b_ano,b.categoria b_cat,b.status b_status FROM duplicate_candidates d JOIN marcos a ON a.id=d.marco_a JOIN marcos b ON b.id=d.marco_b WHERE d.status="pendente" ORDER BY d.similarity DESC,d.id DESC LIMIT 200');
$dups=$stmt->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="panel intro-panel"><div><h2>Central de deduplicação</h2><p>O algoritmo apenas sugere pares semelhantes. A decisão final é humana, fica registrada na auditoria e não reaparece em novas reanálises.</p><button class="btn ghost rebuild-duplicates" type="button">Reanalisar duplicatas</button></div><span class="metric"><strong><?=count($dups)?></strong><small>pares pendentes</small></span></div>
<div class="stack mt-lg">
<?php if(!$dups):?><div class="panel empty">Nenhuma possível duplicata pendente.</div><?php endif;?>
<?php foreach($dups as $d):?>
<div class="panel dup-card" id="dup-<?=$d['id']?>">
  <div class="similarity"><strong><?=number_format($d['similarity']*100,1,',','.')?>%</strong><small>similaridade</small></div>
  <div class="compare"><div><span class="eyebrow">REGISTRO A · #<?=$d['marco_a']?></span><h3><?=e($d['a_nome'])?></h3><p><?=e((string)$d['a_ano'])?> · <?=e($d['a_cat'])?></p><a class="link" href="marco.php?id=<?=$d['marco_a']?>">Abrir A</a></div><div class="vs">↔</div><div><span class="eyebrow">REGISTRO B · #<?=$d['marco_b']?></span><h3><?=e($d['b_nome'])?></h3><p><?=e((string)$d['b_ano'])?> · <?=e($d['b_cat'])?></p><a class="link" href="marco.php?id=<?=$d['marco_b']?>">Abrir B</a></div></div>
  <div class="dup-actions"><button class="btn primary dup-action" data-id="<?=$d['id']?>" data-action="merge_a">Mesclar mantendo A</button><button class="btn dup-action" data-id="<?=$d['id']?>" data-action="merge_b">Mesclar mantendo B</button><button class="btn ghost dup-action" data-id="<?=$d['id']?>" data-action="not_duplicate">Não são duplicatas</button></div>
</div>
<?php endforeach;?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
