<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Auditoria'; $active='auditoria';
$rows=$pdo->query('SELECT * FROM audit_log ORDER BY id DESC LIMIT 300')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="panel"><div class="panel-head"><div><h2>Trilha de auditoria</h2><p>Alterações críticas deixam rastro para reprodutibilidade.</p></div></div><div class="table-wrap"><table><thead><tr><th>Data</th><th>Entidade</th><th>ID</th><th>Ação</th><th>Ator</th><th>Detalhes</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="6" class="empty">Nenhuma ação registrada.</td></tr><?php endif;?><?php foreach($rows as $r):?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['entity_type'])?></td><td><?=e((string)$r['entity_id'])?></td><td><span class="badge badge-info"><?=e($r['action'])?></span></td><td><?=e($r['actor'])?></td><td><details><summary>ver</summary><pre><?=e($r['after_json']??$r['before_json']??'')?></pre></details></td></tr><?php endforeach;?></tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
