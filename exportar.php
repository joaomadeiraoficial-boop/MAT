<?php
require __DIR__ . '/includes/bootstrap.php';
$scope = $_GET['scope'] ?? 'page';
if ($scope !== 'page') {
    $where = match($scope) {
        'approved' => "WHERE m.status='aprovado' AND m.incluir_temporis=1 AND m.elegibilidade_temporis='sim' AND m.escopo_revisado=1",
        'review' => "WHERE m.status='revisao'",
        default => '',
    };
    $stmt=$pdo->query("SELECT m.id,m.nome,m.ano_inicio,m.ano_fim,m.ano_ref,m.precisao_data,m.categoria,m.subcategoria,m.civilizacao,m.pais,m.cidade,m.origem,m.inventores,m.organizacao,m.ambiente,m.natureza,m.elegibilidade_temporis,m.sugestao_elegibilidade,m.motivo_escopo,m.escopo_revisado,m.impacto,m.confiabilidade,m.fator_fundacional,m.score_pi,m.status,m.incluir_temporis,m.descricao FROM marcos m $where ORDER BY COALESCE(m.ano_ref,999999),m.id");
    $rows=$stmt->fetchAll();
    $filename='mat_'.preg_replace('/[^a-z_]/','',$scope).'_'.date('Ymd_His').'.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    $out=fopen('php://output','wb');
    fwrite($out,"\xEF\xBB\xBF");
    $headers=['id','nome','ano_inicio','ano_fim','ano_ref','precisao_data','categoria','subcategoria','civilizacao','pais','cidade','origem','inventores','organizacao','ambiente','natureza','elegibilidade_temporis','sugestao_elegibilidade','motivo_escopo','escopo_revisado','impacto','confiabilidade','fator_fundacional','score_pi','status','incluir_temporis','descricao'];
    fputcsv($out,$headers,';');
    foreach($rows as $r) fputcsv($out,array_values($r),';');
    fclose($out); exit;
}
$pageTitle='Exportar dados';$active='exportar';
$counts=[
 'all'=>(int)$pdo->query('SELECT COUNT(*) FROM marcos')->fetchColumn(),
 'approved'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='aprovado' AND incluir_temporis=1 AND elegibilidade_temporis='sim' AND escopo_revisado=1")->fetchColumn(),
 'review'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='revisao'")->fetchColumn(),
];
require __DIR__ . '/includes/header.php';
?>
<div class="grid cols-3 gap-lg">
 <div class="panel export-card"><h2>Base completa</h2><p>Todos os registros consolidados, inclusive revisão e rejeitados.</p><strong><?=$counts['all']?></strong><a class="btn primary full" href="exportar.php?scope=all">Baixar CSV</a></div>
 <div class="panel export-card"><h2>Base Têmporis</h2><p>Somente aprovados e explicitamente incluídos no índice.</p><strong><?=$counts['approved']?></strong><a class="btn primary full" href="exportar.php?scope=approved">Baixar CSV científico</a></div>
 <div class="panel export-card"><h2>Fila de revisão</h2><p>Registros que ainda precisam de decisão humana.</p><strong><?=$counts['review']?></strong><a class="btn full" href="exportar.php?scope=review">Baixar pendências</a></div>
</div>
<div class="panel mt-lg"><h2>Princípio de reprodutibilidade</h2><p>A base usada para qualquer análise deve ser exportada e guardada junto com a data do experimento. Assim o mesmo cálculo pode ser reproduzido mesmo após futuras correções no MAT.</p></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
