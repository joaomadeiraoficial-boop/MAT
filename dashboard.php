<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle = 'Visão geral'; $active='dashboard';
$stats = [
 'total'=>(int)$pdo->query('SELECT COUNT(*) FROM marcos')->fetchColumn(),
 'review'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='revisao'")->fetchColumn(),
 'approved'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='aprovado'")->fetchColumn(),
 'duplicates'=>(int)$pdo->query("SELECT COUNT(*) FROM duplicate_candidates WHERE status='pendente'")->fetchColumn(),
 'sources'=>(int)$pdo->query('SELECT COUNT(*) FROM fontes')->fetchColumn(),
 'scope_pending'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE escopo_revisado=0")->fetchColumn(),
 'eligible'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE elegibilidade_temporis='sim' AND escopo_revisado=1")->fetchColumn(),
];
$recent = $pdo->query('SELECT id,nome,ano_ref,categoria,status,natureza,elegibilidade_temporis,updated_at FROM marcos ORDER BY updated_at DESC LIMIT 8')->fetchAll();
$coverage = $pdo->query("SELECT categoria,COUNT(*) total,SUM(status='aprovado') aprovados FROM marcos GROUP BY categoria ORDER BY total DESC LIMIT 8")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="grid cards-5">
  <div class="stat-card"><span>Total de marcos</span><strong><?= $stats['total'] ?></strong><small>base consolidada</small></div>
  <div class="stat-card accent"><span>Em revisão</span><strong><?= $stats['review'] ?></strong><small>aguardando decisão</small></div>
  <div class="stat-card success"><span>Elegíveis revisados</span><strong><?= $stats['eligible'] ?></strong><small>escopo científico confirmado</small></div>
  <div class="stat-card warning"><span>Duplicatas</span><strong><?= $stats['duplicates'] ?></strong><small>candidatas pendentes</small></div>
  <div class="stat-card"><span>Fontes</span><strong><?= $stats['sources'] ?></strong><small>referências cadastradas</small></div>
</div>

<div class="grid cols-2 gap-lg mt-lg">
  <div class="panel">
    <div class="panel-head"><div><h2>Fluxo científico</h2><p>Da importação ao cálculo do índice.</p></div></div>
    <div class="pipeline">
      <div><b>1</b><span>Importar</span><small>CSV / base externa</small></div><i>→</i>
      <div><b>2</b><span>Normalizar</span><small>datas, nomes, campos</small></div><i>→</i>
      <div><b>3</b><span>Filtrar escopo</span><small>ciência/tecnologia x contexto</small></div><i>→</i>
      <div><b>4</b><span>Revisar</span><small>fontes, duplicatas e decisão humana</small></div><i>→</i>
      <div><b>5</b><span>Pontuar</span><small>I, C e F</small></div><i>→</i>
      <div><b>6</b><span>Validar</span><small>Têmporis e testes</small></div>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><div><h2>Qualidade da base</h2><p>Distribuição por categoria.</p></div></div>
    <?php if (!$coverage): ?><div class="empty">Importe sua primeira base para ver a cobertura.</div><?php endif; ?>
    <?php foreach ($coverage as $c): $pct=$c['total']?round($c['aprovados']*100/$c['total']):0; ?>
      <div class="quality-row"><div><b><?= e($c['categoria']) ?></b><small><?= (int)$c['aprovados'] ?>/<?= (int)$c['total'] ?> aprovados</small></div><div class="bar"><span style="width:<?= $pct ?>%"></span></div><em><?= $pct ?>%</em></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Últimos registros</h2><p>Alterações mais recentes na base.</p></div><a href="marcos.php">Ver todos →</a></div>
  <div class="table-wrap"><table><thead><tr><th>Marco</th><th>Ano</th><th>Natureza</th><th>Escopo</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if (!$recent): ?><tr><td colspan="6" class="empty">A base ainda está vazia.</td></tr><?php endif; ?>
  <?php foreach ($recent as $m): ?><tr><td><strong><?= e($m['nome']) ?></strong></td><td><?= e(format_year_value($m['ano_ref'])) ?></td><td><?= e(scope_nature_label($m['natureza'])) ?></td><td><span class="badge <?=eligibility_class($m['elegibilidade_temporis'])?>"><?=e(eligibility_label($m['elegibilidade_temporis']))?></span></td><td><span class="badge <?= status_class($m['status']) ?>"><?= status_label($m['status']) ?></span></td><td><a class="link" href="marco.php?id=<?= $m['id'] ?>">Abrir</a></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
