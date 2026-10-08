<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Importar base'; $active='importar';
$service = new ImportService($pdo,$app);
$preview = null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $action = $_POST['action'] ?? 'preview';
    try {
        if ($action === 'cancel_preview') {
            $old = $_SESSION['smart_import_preview'] ?? null;
            if (is_array($old) && !empty($old['path']) && is_file($old['path'])) @unlink($old['path']);
            unset($_SESSION['smart_import_preview']);
            flash('success','Prévia descartada. Nada foi importado.');
            redirect('importar.php');
        }

        if ($action === 'preview') {
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Selecione um arquivo CSV, TXT ou TSV válido.');
            if ($_FILES['arquivo']['size'] > $app['max_upload_bytes']) throw new RuntimeException('Arquivo maior que o limite configurado.');
            $originalName = basename((string)$_FILES['arquivo']['name']);
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($ext,['csv','txt','tsv'],true)) throw new RuntimeException('Formato não suportado. Use CSV, TXT ou TSV.');

            $uploadDir = $app['upload_dir'];
            if (!is_dir($uploadDir) && !mkdir($uploadDir,0775,true) && !is_dir($uploadDir)) throw new RuntimeException('Não foi possível criar a pasta temporária de importação.');
            $token = bin2hex(random_bytes(16));
            $savedPath = $uploadDir . '/' . $token . '.' . $ext;
            if (!move_uploaded_file($_FILES['arquivo']['tmp_name'],$savedPath)) throw new RuntimeException('Não foi possível preparar o arquivo para a prévia.');

            $delimiterChoice = $_POST['delimiter'] ?? 'auto';
            $forcedDelimiter = match($delimiterChoice) {
                'tab' => "\t",
                ';' => ';',
                ',' => ',',
                '|' => '|',
                default => null,
            };
            $preview = $service->previewDelimited($savedPath,$originalName,$forcedDelimiter,12);
            $_SESSION['smart_import_preview'] = [
                'token'=>$token,'path'=>$savedPath,'filename'=>$originalName,'ext'=>$ext,
                'delimiter'=>$preview['delimiter'],'created_at'=>time(),
                'header_original'=>$preview['header_original'],'map'=>$preview['map'],
            ];
            flash('success','Prévia criada. Nenhum dado foi gravado no banco. Confira o mapeamento antes de importar.');
        }

        if ($action === 'import') {
            $saved = $_SESSION['smart_import_preview'] ?? null;
            if (!is_array($saved) || empty($saved['path']) || !is_file($saved['path'])) throw new RuntimeException('Prévia não encontrada. Gere uma nova prévia antes de importar.');
            if (time() - (int)($saved['created_at'] ?? 0) > 15*60) throw new RuntimeException('A prévia expirou após 15 minutos. Gere uma nova.');
            if (($_POST['confirm_text'] ?? '') !== 'IMPORTAR' || !isset($_POST['reviewed'])) throw new RuntimeException('Confirme que revisou o mapeamento e digite IMPORTAR.');

            $fieldMap = [];
            foreach (($_POST['map'] ?? []) as $field=>$idx) {
                if ($idx === '') continue;
                $fieldMap[$field] = (int)$idx;
            }
            if (!isset($fieldMap['nome'])) throw new RuntimeException('A coluna "Nome do marco" é obrigatória.');

            $sourceType = in_array(($saved['ext'] ?? ''),['txt','tsv'],true) ? 'txt_tsv' : 'csv';
            $result = $service->importDelimited($saved['path'],$saved['filename'],$saved['delimiter'],$fieldMap,$sourceType);
            @unlink($saved['path']);
            unset($_SESSION['smart_import_preview']);
            flash('success',"Importação concluída: {$result['imported']} registros e {$result['duplicates']} possíveis duplicatas. Todos entraram como candidatos em revisão.");
            redirect('revisar.php?batch=' . $result['batch_id']);
        }
    } catch (Throwable $ex) {
        flash('error',$ex->getMessage());
    }
}

if ($preview === null && !empty($_SESSION['smart_import_preview'])) {
    $saved = $_SESSION['smart_import_preview'];
    if (is_array($saved) && !empty($saved['path']) && is_file($saved['path']) && time()-(int)$saved['created_at'] <= 15*60) {
        try { $preview = $service->previewDelimited($saved['path'],$saved['filename'],$saved['delimiter'],12); } catch(Throwable $e) { $preview=null; }
    }
}

$batches=$pdo->query('SELECT * FROM import_batches ORDER BY id DESC LIMIT 10')->fetchAll();
require __DIR__ . '/includes/header.php';

$fieldLabels = [
  'nome'=>'Nome do marco *','ano'=>'Ano/data','categoria'=>'Categoria/área','inventores'=>'Inventor(es)/descobridor(es)',
  'organizacao'=>'Organização/instituição','ambiente'=>'Ambiente/contexto','pais'=>'País','cidade'=>'Cidade/local',
  'civilizacao'=>'Civilização/sociedade','fonte'=>'Fonte/referência','descricao'=>'Descrição/observação'
];
?>
<div class="grid cols-2 gap-lg">
  <div class="panel upload-panel">
    <div class="panel-head"><div><h2>Importador científico inteligente</h2><p>O MAT detecta CSV, TXT e TSV, identifica o separador e tenta reconhecer as colunas sozinho.</p></div><span class="badge badge-info">v6.4</span></div>
    <form method="post" enctype="multipart/form-data" class="stack"><?= csrf_field() ?><input type="hidden" name="action" value="preview">
      <label class="dropzone"><input type="file" name="arquivo" accept=".csv,.txt,.tsv,text/csv,text/plain,text/tab-separated-values" required><span class="drop-icon">⇩</span><strong>Arraste CSV, TXT ou TSV</strong><small>O arquivo é analisado primeiro. Nada entra no banco sem sua confirmação.</small></label>
      <div class="field"><label>Separador</label><select name="delimiter"><option value="auto">Detectar automaticamente (recomendado)</option><option value="tab">TAB / tabulação</option><option value=";">Ponto e vírgula (;)</option><option value=",">Vírgula (,)</option><option value="|">Barra vertical (|)</option></select></div>
      <button class="btn primary large" type="submit">1. Analisar arquivo e gerar prévia</button>
    </form>
    <div class="note mt-lg"><b>Para o DataS1.txt:</b> o MAT já reconhece automaticamente <code>Invention → Nome</code>, <code>Year → Ano</code>, <code>InnovationType → Categoria</code>, <code>Inventors → Inventores</code>, <code>Organization → Organização</code>, <code>Country → País</code> e <code>Source → Fonte</code>.</div>
  </div>
  <div class="panel">
    <div class="panel-head"><div><h2>O que acontece automaticamente</h2><p>A conversão deixa de ser uma tarefa externa.</p></div></div>
    <div class="schema-list">
      <span><b>1. Detectar</b> formato e separador do arquivo</span>
      <span><b>2. Mapear</b> nomes de colunas para os campos do MAT</span>
      <span><b>3. Preservar</b> a linha original completa em marcos_raw</span>
      <span><b>4. Normalizar</b> espaços, datas e nomes básicos</span>
      <span><b>5. Separar</b> múltiplas referências quando possível</span>
      <span><b>6. Classificar</b> escopo com dicionário PT/EN, pesos por categoria/nome/descrição e procurar duplicatas</span>
    </div>
    <div class="note warning"><b>Trava de segurança:</b> primeiro há uma prévia e um mapeamento. Para gravar, você precisa marcar “Revisei” e digitar <b>IMPORTAR</b>.</div>
  </div>
</div>

<?php if($preview): ?>
<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Prévia — <?=e($preview['filename'])?></h2><p><?= (int)$preview['total_rows'] ?> linha(s) de dados · separador detectado: <b><?=e($preview['delimiter_label'])?></b>. Nada foi importado ainda.</p></div><span class="badge badge-success"><?=count($preview['recognized_fields'])?> campos reconhecidos</span></div>

  <form method="post" class="stack"><?=csrf_field()?><input type="hidden" name="action" value="import">
    <div class="grid cols-2 gap-lg">
      <div>
        <h3>Mapeamento de colunas</h3>
        <p class="muted">Confira principalmente Nome, Ano, Categoria e Fonte. Você pode corrigir qualquer seleção.</p>
        <div class="stack">
          <?php foreach($fieldLabels as $field=>$label): ?>
          <div class="field"><label><?=e($label)?></label><select name="map[<?=e($field)?>]" <?= $field==='nome'?'required':'' ?>>
            <option value="">— não importar este campo —</option>
            <?php foreach($preview['header_original'] as $i=>$h): ?><option value="<?=$i?>" <?=isset($preview['map'][$field]) && (int)$preview['map'][$field]===$i?'selected':''?>><?=e($h)?> [coluna <?=($i+1)?>]</option><?php endforeach;?>
          </select></div>
          <?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3>Exemplo do que o MAT entendeu</h3>
        <?php $auto=$preview['map']; foreach(array_slice($preview['rows'],0,5) as $r): ?>
          <div class="source-chip" style="display:block;margin-bottom:10px">
            <b><?=e(isset($auto['nome'])?($r[$auto['nome']]??'—'):'—')?></b>
            <small><?=e(isset($auto['ano'])?($r[$auto['ano']]??'sem ano'):'sem ano')?> · <?=e(isset($auto['categoria'])?($r[$auto['categoria']]??'sem categoria'):'sem categoria')?></small>
            <?php if(isset($auto['inventores']) && !empty($r[$auto['inventores']])):?><small>Inventor(es): <?=e(mb_strimwidth($r[$auto['inventores']],0,110,'…','UTF-8'))?></small><?php endif;?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="note warning"><b>Antes de importar:</b> este processo não “valida” cientificamente os eventos. Ele apenas converte e organiza o banco. Todos os registros continuam passando por Escopo científico, Duplicatas e Revisão humana.</div>
    <label class="check"><input type="checkbox" name="reviewed" value="1" required> Revisei o mapeamento das colunas e a prévia.</label>
    <div class="field"><label>Confirmação</label><input name="confirm_text" autocomplete="off" placeholder="Digite IMPORTAR" required></div>
    <button class="btn primary large" type="submit" onclick="return confirm('Confirma a importação deste arquivo com o mapeamento exibido?');">2. Importar <?= (int)$preview['total_rows'] ?> candidato(s)</button>
  </form>
  <form method="post" class="mt-lg"><?=csrf_field()?><input type="hidden" name="action" value="cancel_preview"><button class="btn danger ghost" type="submit">Cancelar e descartar prévia</button></form>
</div>

<div class="panel mt-lg"><div class="panel-head"><div><h2>Linhas brutas da prévia</h2><p>Útil para conferir arquivos científicos com muitas colunas.</p></div></div><div class="table-wrap"><table><thead><tr><?php foreach($preview['header_original'] as $h):?><th><?=e($h)?></th><?php endforeach;?></tr></thead><tbody><?php foreach($preview['rows'] as $r):?><tr><?php foreach($preview['header_original'] as $i=>$h):?><td><?=e(mb_strimwidth((string)($r[$i]??''),0,100,'…','UTF-8'))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div></div>
<?php endif; ?>

<div class="panel mt-lg"><div class="panel-head"><div><h2>Histórico de importações</h2><p>Você pode remover um lote inteiro sem entrar no phpMyAdmin.</p></div><a class="btn ghost" href="coletor.php">Abrir coletor externo</a></div><div class="table-wrap"><table><thead><tr><th>Origem</th><th>Arquivo</th><th>Linhas</th><th>Importadas</th><th>Duplicatas</th><th>Status</th><th>Data</th><th></th></tr></thead><tbody>
<?php if(!$batches):?><tr><td colspan="8" class="empty">Nenhuma importação realizada.</td></tr><?php endif;?>
<?php foreach($batches as $b):?><tr><td><span class="badge badge-info"><?=e($b['source_type'] ?? 'arquivo')?></span></td><td><?=e($b['filename'])?></td><td><?=$b['total_rows']?></td><td><?=$b['imported_rows']?></td><td><?=$b['duplicate_candidates']?></td><td><span class="badge badge-success"><?=e($b['status'])?></span></td><td><?=e($b['created_at'])?></td><td><form method="post" action="api/delete_batch.php" onsubmit="return confirm('ATENÇÃO: excluir este lote e TODOS os marcos importados por ele? Esta ação não pode ser desfeita.');"><?=csrf_field()?><input type="hidden" name="id" value="<?=$b['id']?>"><input type="hidden" name="return_to" value="importar.php"><button class="btn danger" type="submit">Excluir lote</button></form></td></tr><?php endforeach;?>
</tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
