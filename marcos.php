<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Base de marcos'; $active='marcos';
$q=trim($_GET['q']??''); $status=trim($_GET['status']??''); $elig=trim($_GET['elig']??''); $nature=trim($_GET['natureza']??'');
$sql='SELECT m.*, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.marco_id=m.id) fontes_count FROM marcos m WHERE 1'; $params=[];
if($q!==''){ $sql.=' AND (m.nome LIKE ? OR m.descricao LIKE ? OR m.inventores LIKE ?)'; $params[]='%'.$q.'%';$params[]='%'.$q.'%';$params[]='%'.$q.'%'; }
if($status!==''){ $sql.=' AND m.status=?'; $params[]=$status; }
if(in_array($elig,['sim','nao','revisar'],true)){ $sql.=' AND m.elegibilidade_temporis=?'; $params[]=$elig; }
if($nature!==''){ $sql.=' AND m.natureza=?'; $params[]=$nature; }
$sql.=' ORDER BY COALESCE(m.ano_ref,999999),m.nome LIMIT 700';
$stmt=$pdo->prepare($sql);$stmt->execute($params);$marcoItems=$stmt->fetchAll();
$natures=$pdo->query('SELECT natureza,COUNT(*) n FROM marcos GROUP BY natureza ORDER BY n DESC')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="toolbar panel"><form method="get" class="filters"><input name="q" value="<?=e($q)?>" placeholder="Nome ou descrição"><select name="status"><option value="">Todos os status</option><?php foreach(['revisao','aprovado','rejeitado','duplicado','rascunho'] as $s):?><option value="<?=$s?>" <?=$status===$s?'selected':''?>><?=status_label($s)?></option><?php endforeach;?></select><select name="elig"><option value="">Todo escopo</option><option value="sim" <?=$elig==='sim'?'selected':''?>>Elegível</option><option value="nao" <?=$elig==='nao'?'selected':''?>>Fora do escopo</option><option value="revisar" <?=$elig==='revisar'?'selected':''?>>Escopo pendente</option></select><select name="natureza"><option value="">Todas as naturezas</option><?php foreach($natures as $n):?><option value="<?=e($n['natureza'])?>" <?=$nature===$n['natureza']?'selected':''?>><?=e(scope_nature_label($n['natureza']))?></option><?php endforeach;?></select><button class="btn">Filtrar</button></form><a class="btn primary" href="marco.php">+ Novo marco</a></div>
<div class="panel mt-lg"><div class="table-wrap"><table><thead><tr><th>#</th><th>Marco</th><th>Ano</th><th>Natureza</th><th>Escopo</th><th>Fontes</th><th>Pᵢ</th><th>Status</th><th></th></tr></thead><tbody>
<?php if(!$marcoItems):?><tr><td colspan="9" class="empty">Nenhum marco encontrado.</td></tr><?php endif;?>
<?php foreach($marcoItems as $m):?><tr><td><?=$m['id']?></td><td><strong><?=e($m['nome'])?></strong><small class="subline"><?=e($m['origem']??'')?></small></td><td><?=e(format_year_value($m['ano_ref']))?></td><td><?=e(scope_nature_label($m['natureza']))?></td><td><span class="badge <?=eligibility_class($m['elegibilidade_temporis'])?>"><?=e(eligibility_label($m['elegibilidade_temporis']))?></span></td><td><?=$m['fontes_count']?></td><td><?=$m['score_pi']!==null?number_format((float)$m['score_pi'],3,',','.'): '—'?></td><td><span class="badge <?=status_class($m['status'])?>"><?=status_label($m['status'])?></span></td><td><a class="link" href="marco.php?id=<?=$m['id']?>">Editar</a></td></tr><?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
