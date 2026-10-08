<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Coletor de dados'; $active='coletor';
$pageSubtitle='Coleta assistida de candidatos científicos e tecnológicos em bases externas.';

$options = ExternalCollector::sanitizeOptions($_POST ?: $_GET);
$preview = null;
$previewToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? 'preview';
    try {
        $collector = new ExternalCollector($pdo,$app);
        if ($action === 'preview') {
            $preview = $collector->previewWikidata($options);
            $previewToken = bin2hex(random_bytes(24));
            $_SESSION['collector_preview'] = [
                'token' => $previewToken,
                'options_hash' => hash('sha256', json_encode($options, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
                'expires_at' => time() + 15 * 60,
                'count' => count($preview['rows']),
            ];
            flash('success','Prévia concluída: ' . count($preview['rows']) . ' candidatos recebidos do Wikidata. Nada foi importado ainda.');
        } elseif ($action === 'import') {
            $gate = $_SESSION['collector_preview'] ?? null;
            $token = (string)($_POST['preview_token'] ?? '');
            $hash = hash('sha256', json_encode($options, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $confirmed = isset($_POST['confirm_review']) && $_POST['confirm_review'] === '1';
            $phrase = strtoupper(trim((string)($_POST['confirm_phrase'] ?? '')));

            if (!$gate || empty($gate['token']) || !hash_equals((string)$gate['token'], $token)) {
                throw new RuntimeException('Importação bloqueada: gere uma nova prévia antes de importar.');
            }
            if (($gate['expires_at'] ?? 0) < time()) {
                unset($_SESSION['collector_preview']);
                throw new RuntimeException('A prévia expirou. Gere uma nova prévia antes de importar.');
            }
            if (!hash_equals((string)($gate['options_hash'] ?? ''), $hash)) {
                throw new RuntimeException('Os filtros mudaram depois da prévia. Gere a prévia novamente.');
            }
            if (!$confirmed || $phrase !== 'IMPORTAR') {
                throw new RuntimeException('Confirmação incompleta. Marque a revisão e digite IMPORTAR para liberar a gravação.');
            }

            $result = $collector->importWikidata($options);
            unset($_SESSION['collector_preview']);
            flash('success',"Coleta concluída: {$result['received']} recebidos, {$result['imported']} novos candidatos e {$result['skipped']} já existentes/ignorados.");
            redirect('revisar.php?batch=' . $result['batch_id']);
        }
    } catch (Throwable $e) {
        flash('error',$e->getMessage());
    }
}

$total = (int)$pdo->query('SELECT COUNT(*) FROM marcos')->fetchColumn();
$external = (int)$pdo->query("SELECT COUNT(*) FROM marcos_raw WHERE external_source IS NOT NULL")->fetchColumn();
$wikidata = (int)$pdo->query("SELECT COUNT(*) FROM marcos_raw WHERE external_source='wikidata'")->fetchColumn();
$pending = (int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='revisao'")->fetchColumn();
$batches = $pdo->query("SELECT id,filename,source_type,total_rows,imported_rows,duplicate_candidates,status,created_at FROM import_batches ORDER BY id DESC LIMIT 12")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="grid cards-4">
  <div class="stat-card"><span>Banco consolidado</span><strong><?= $total ?></strong><small>registros no MAT</small></div>
  <div class="stat-card accent"><span>Coleta externa</span><strong><?= $external ?></strong><small>registros com ID externo</small></div>
  <div class="stat-card success"><span>Wikidata</span><strong><?= $wikidata ?></strong><small>candidatos coletados</small></div>
  <div class="stat-card warning"><span>Em revisão</span><strong><?= $pending ?></strong><small>aguardando decisão humana</small></div>
</div>

<div class="grid cols-2 gap-lg mt-lg">
  <div class="panel collector-card">
    <div class="panel-head"><div><h2>Wikidata Query Service</h2><p>Coleta registros que possuem data de descoberta/invenção (P575). O resultado entra apenas como candidato.</p></div><span class="badge badge-success">Ao vivo</span></div>
    <form method="post" class="stack"><?= csrf_field() ?>
      <div class="field"><label>Conjunto de busca</label><select name="preset">
        <option value="with_inventor" <?= $options['preset']==='with_inventor'?'selected':'' ?>>P575 + inventor/descobridor (mais seletivo)</option>
        <option value="all_p575" <?= $options['preset']==='all_p575'?'selected':'' ?>>Todos com P575 (mais amplo)</option>
        <option value="inventions" <?= $options['preset']==='inventions'?'selected':'' ?>>Itens classificados como invenção</option>
      </select></div>
      <div class="form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
        <div class="field"><label>Ano inicial</label><input type="number" name="year_start" min="1" max="9999" value="<?= e((string)$options['year_start']) ?>"></div>
        <div class="field"><label>Ano final</label><input type="number" name="year_end" min="1" max="9999" value="<?= e((string)$options['year_end']) ?>"></div>
        <div class="field"><label>Tamanho do lote <span>(10–1000)</span></label><input type="number" name="limit" min="10" max="1000" value="<?= e((string)$options['limit']) ?>"></div>
        <div class="field"><label>Offset <span>(paginação)</span></label><input type="number" name="offset" min="0" max="100000" value="<?= e((string)$options['offset']) ?>"></div>
      </div>
      <div class="note warning"><b>Para 1.000–2.000 marcos:</b> prefira vários lotes por período (ex.: 1500–1799, 1800–1899, 1900–1949, 1950–1999, 2000–2026). Isso reduz timeout e facilita auditoria.</div>
      <div class="note warning"><b>Trava de segurança:</b> esta tela não permite mais importar diretamente. Primeiro é obrigatório gerar a prévia; depois você confirma em uma segunda etapa.</div>
      <div class="card-actions">
        <button class="btn primary large" type="submit" name="action" value="preview">1. Gerar prévia segura</button>
      </div>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head"><div><h2>Política de evidência</h2><p>O coletor resolve escala; não substitui validação científica.</p></div></div>
    <div class="schema-list">
      <span><b>Coletado</b> candidato bruto com QID e URL preservados</span>
      <span><b>Triado</b> classificação automática de escopo</span>
      <span><b>Deduplicado</b> registros equivalentes são mesclados</span>
      <span><b>Confirmado</b> fonte acadêmica/institucional independente</span>
      <span><b>Pontuado</b> I, C e F com justificativas</span>
      <span><b>Aprovado</b> somente então pode entrar na Têmporis</span>
    </div>
    <div class="note"><b>Importante:</b> o Wikidata é excelente para descoberta e metadados, mas não deve ser a única evidência de um marco. O MAT mantém essa fonte como origem e permite adicionar confirmações depois.</div>
  </div>
</div>

<?php if ($preview): ?>
<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Prévia da coleta</h2><p><b><?= count($preview['rows']) ?></b> registros retornados. Mostrando até 50 nesta tela. <b>Nada foi gravado ainda.</b></p></div><span class="badge badge-warning">Somente prévia</span></div>
  <div class="table-wrap"><table><thead><tr><th>QID</th><th>Ano</th><th>Nome</th><th>Descrição</th></tr></thead><tbody>
  <?php foreach(array_slice($preview['rows'],0,50) as $r): ?>
    <tr><td><a class="link" target="_blank" rel="noopener" href="https://www.wikidata.org/wiki/<?= e($r['qid']) ?>"><?= e($r['qid']) ?></a></td><td><?= e((string)$r['year']) ?></td><td><b><?= e($r['label']) ?></b></td><td><?= e($r['description']) ?></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>

  <?php if (count($preview['rows']) > 0): ?>
  <div class="note warning mt-lg">
    <b>2. Confirmação obrigatória</b><br>
    Você está prestes a gravar até <b><?= count($preview['rows']) ?> candidato(s)</b> com os filtros desta prévia. Confira nomes, período e tipo de busca antes de continuar.
  </div>
  <form method="post" class="stack mt-lg" onsubmit="return confirm('CONFIRMAÇÃO FINAL: importar os candidatos exibidos nesta prévia para o banco do MAT?');">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="import">
    <input type="hidden" name="preview_token" value="<?= e($previewToken) ?>">
    <input type="hidden" name="preset" value="<?= e($options['preset']) ?>">
    <input type="hidden" name="year_start" value="<?= e((string)$options['year_start']) ?>">
    <input type="hidden" name="year_end" value="<?= e((string)$options['year_end']) ?>">
    <input type="hidden" name="limit" value="<?= e((string)$options['limit']) ?>">
    <input type="hidden" name="offset" value="<?= e((string)$options['offset']) ?>">
    <label class="check"><input type="checkbox" name="confirm_review" value="1" required> Revisei a prévia e confirmo que o período e o conjunto de busca estão corretos.</label>
    <div class="field"><label>Digite <b>IMPORTAR</b> para liberar a gravação</label><input name="confirm_phrase" autocomplete="off" required placeholder="IMPORTAR"></div>
    <div class="card-actions">
      <button class="btn danger large" type="submit">2. Confirmar e importar <?= count($preview['rows']) ?> candidato(s)</button>
      <a class="btn ghost large" href="coletor.php">Cancelar / limpar prévia</a>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Lotes recentes</h2><p>CSV e coletas externas entram no mesmo histórico auditável.</p></div></div>
  <div class="table-wrap"><table><thead><tr><th>ID</th><th>Origem</th><th>Arquivo/lote</th><th>Recebidos</th><th>Importados</th><th>Status</th><th>Data</th><th>Ação</th></tr></thead><tbody>
  <?php if(!$batches): ?><tr><td colspan="8" class="empty">Nenhum lote ainda.</td></tr><?php endif; ?>
  <?php foreach($batches as $b): ?><tr><td>#<?= (int)$b['id'] ?></td><td><span class="badge badge-info"><?= e($b['source_type'] ?: 'csv') ?></span></td><td><?= e($b['filename']) ?></td><td><?= (int)$b['total_rows'] ?></td><td><?= (int)$b['imported_rows'] ?></td><td><?= e($b['status']) ?></td><td><?= e($b['created_at']) ?></td><td><form method="post" action="api/delete_batch.php" onsubmit="return confirm('ATENÇÃO: excluir o lote #<?= (int)$b['id'] ?> e todos os <?= (int)$b['imported_rows'] ?> marcos importados por ele? Esta ação não pode ser desfeita.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><input type="hidden" name="return_to" value="coletor.php"><button class="btn danger" type="submit">Excluir lote</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
