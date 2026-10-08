<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Revisão de escopo por lote'; $active='escopo';

$batches=$pdo->query('SELECT id,filename,imported_rows,created_at FROM import_batches WHERE imported_rows>0 ORDER BY id DESC LIMIT 50')->fetchAll();
$batch=(int)($_REQUEST['batch']??0);
$category=trim((string)($_REQUEST['categoria']??''));
$suggestion=(string)($_REQUEST['sugestao']??'sim');
$confidence=(string)($_REQUEST['confianca']??'alta');
if(!in_array($suggestion,['sim','nao','revisar'],true)) $suggestion='sim';
if(!in_array($confidence,['alta','media','baixa',''],true)) $confidence='alta';

function scope_batch_where(int $batch,string $category,string $suggestion,string $confidence,array &$params): string {
    $where="m.escopo_revisado=0 AND m.status NOT IN ('rejeitado','duplicado')";
    if($batch>0){ $where.=' AND r.batch_id=?'; $params[]=$batch; }
    if($category!==''){ $where.=' AND m.categoria=?'; $params[]=$category; }
    $where.=' AND m.sugestao_elegibilidade=?'; $params[]=$suggestion;
    if($confidence!==''){ $where.=' AND m.scope_confidence=?'; $params[]=$confidence; }
    return $where;
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    require_csrf();
    try{
        $action=(string)$_POST['action'];
        if($batch<=0) throw new RuntimeException('Por segurança, escolha um lote específico antes de uma decisão em massa.');
        if(!in_array($action,['approve','reject'],true)) throw new RuntimeException('Ação inválida.');
        $expected=$action==='approve'?'APROVAR':'RECUSAR';
        if(trim((string)($_POST['confirm_text']??''))!==$expected || !isset($_POST['reviewed'])) throw new RuntimeException("Marque a revisão e digite {$expected} para confirmar.");
        $params=[]; $where=scope_batch_where($batch,$category,$suggestion,$confidence,$params);
        $countStmt=$pdo->prepare("SELECT COUNT(*) FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE {$where}");
        $countStmt->execute($params); $count=(int)$countStmt->fetchColumn();
        if($count<1) throw new RuntimeException('Nenhum registro pendente corresponde aos filtros atuais.');

        $pdo->beginTransaction();
        if($action==='approve'){
            $sql="UPDATE marcos m JOIN marcos_raw r ON r.id=m.raw_id SET m.elegibilidade_temporis='sim',m.escopo_revisado=1 WHERE {$where}";
        }else{
            $sql="UPDATE marcos m JOIN marcos_raw r ON r.id=m.raw_id SET m.elegibilidade_temporis='nao',m.escopo_revisado=1,m.incluir_temporis=0 WHERE {$where}";
        }
        $stmt=$pdo->prepare($sql); $stmt->execute($params);
        Audit::log($pdo,'scope_batch',$batch,$action==='approve'?'scope_bulk_approve':'scope_bulk_reject',null,[
            'count'=>$count,'batch'=>$batch,'categoria'=>$category,'sugestao'=>$suggestion,'confianca'=>$confidence
        ]);
        $pdo->commit();
        flash('success',"Decisão de escopo aplicada a {$count} registro(s) do lote. A aprovação final dos marcos continua separada.");
        redirect('escopo_lote.php?batch='.$batch.'&sugestao='.$suggestion.'&confianca='.$confidence.'&categoria='.urlencode($category));
    }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); flash('error',$e->getMessage()); }
}

$cats=[];
if($batch>0){
    $st=$pdo->prepare('SELECT DISTINCT m.categoria FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE r.batch_id=? ORDER BY m.categoria');
    $st->execute([$batch]); $cats=$st->fetchAll(PDO::FETCH_COLUMN);
}
$params=[]; $where=scope_batch_where($batch,$category,$suggestion,$confidence,$params);
$preview=[]; $count=0;
if($batch>0){
    $st=$pdo->prepare("SELECT COUNT(*) FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE {$where}"); $st->execute($params); $count=(int)$st->fetchColumn();
    $st=$pdo->prepare("SELECT m.id,m.nome,m.ano_ref,m.categoria,m.natureza,m.scope_score,m.scope_confidence,m.motivo_escopo FROM marcos m JOIN marcos_raw r ON r.id=m.raw_id WHERE {$where} ORDER BY COALESCE(m.ano_ref,999999),m.id LIMIT 50");
    $st->execute($params); $preview=$st->fetchAll();
}
require __DIR__ . '/includes/header.php';
?>
<div class="panel">
  <div class="panel-head"><div><h2>Filtrar antes de decidir</h2><p>Use um lote específico e revise uma amostra antes de aplicar uma decisão humana em massa.</p></div><span class="badge badge-warning">não aprova o marco final</span></div>
  <form method="get" class="filters">
    <select name="batch" required><option value="">Escolha o lote</option><?php foreach($batches as $b):?><option value="<?=$b['id']?>" <?=$batch===(int)$b['id']?'selected':''?>>#<?=$b['id']?> · <?=e($b['filename'])?> · <?=$b['imported_rows']?> registros</option><?php endforeach;?></select>
    <select name="categoria"><option value="">Todas as categorias do lote</option><?php foreach($cats as $c):?><option value="<?=e($c)?>" <?=$category===$c?'selected':''?>><?=e($c)?></option><?php endforeach;?></select>
    <select name="sugestao"><option value="sim" <?=$suggestion==='sim'?'selected':''?>>Sugestão SIM</option><option value="nao" <?=$suggestion==='nao'?'selected':''?>>Sugestão NÃO</option><option value="revisar" <?=$suggestion==='revisar'?'selected':''?>>Sugestão REVISAR</option></select>
    <select name="confianca"><option value="alta" <?=$confidence==='alta'?'selected':''?>>Confiança alta</option><option value="media" <?=$confidence==='media'?'selected':''?>>Confiança média</option><option value="baixa" <?=$confidence==='baixa'?'selected':''?>>Confiança baixa</option><option value="" <?=$confidence===''?'selected':''?>>Qualquer confiança</option></select>
    <button class="btn primary">Gerar prévia</button>
  </form>
</div>

<?php if($batch>0):?>
<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Prévia da decisão</h2><p><b><?=$count?></b> registro(s) ainda pendentes correspondem exatamente aos filtros. A tabela mostra no máximo 50 para inspeção.</p></div></div>
  <div class="note warning"><b>Regra de segurança:</b> uma decisão por lote só afeta o escopo científico. Ela não define impacto, confiabilidade, fator fundacional, status final nem inclusão automática no cálculo da Têmporis.</div>
  <div class="table-wrap mt-lg"><table><thead><tr><th>Marco</th><th>Ano</th><th>Categoria</th><th>Natureza</th><th>Confiança</th><th>Score</th></tr></thead><tbody>
    <?php if(!$preview):?><tr><td colspan="6" class="empty">Nenhum registro pendente com esses filtros.</td></tr><?php endif;?>
    <?php foreach($preview as $m):?><tr><td><strong><?=e($m['nome'])?></strong></td><td><?=e(format_year_value($m['ano_ref']))?></td><td><?=e($m['categoria'])?></td><td><?=e(scope_nature_label($m['natureza']))?></td><td><?=e($m['scope_confidence'])?></td><td><?=e((string)$m['scope_score'])?></td></tr><?php endforeach;?>
  </tbody></table></div>

  <?php if($count>0):?>
  <div class="grid cols-2 gap-lg mt-lg">
    <form method="post" class="panel" onsubmit="return confirm('Confirmar APROVAÇÃO DE ESCOPO para todos os registros filtrados deste lote?');"><?=csrf_field()?>
      <input type="hidden" name="action" value="approve"><input type="hidden" name="batch" value="<?=$batch?>"><input type="hidden" name="categoria" value="<?=e($category)?>"><input type="hidden" name="sugestao" value="<?=e($suggestion)?>"><input type="hidden" name="confianca" value="<?=e($confidence)?>">
      <h3>Aprovar escopo do grupo</h3><label class="check"><input type="checkbox" name="reviewed" value="1" required> Revisei a amostra e os filtros acima.</label><div class="field"><label>Digite APROVAR</label><input name="confirm_text" required autocomplete="off"></div><button class="btn primary full">Aprovar escopo de <?=$count?> registro(s)</button>
    </form>
    <form method="post" class="panel" onsubmit="return confirm('Confirmar RECUSA DE ESCOPO para todos os registros filtrados deste lote?');"><?=csrf_field()?>
      <input type="hidden" name="action" value="reject"><input type="hidden" name="batch" value="<?=$batch?>"><input type="hidden" name="categoria" value="<?=e($category)?>"><input type="hidden" name="sugestao" value="<?=e($suggestion)?>"><input type="hidden" name="confianca" value="<?=e($confidence)?>">
      <h3>Recusar escopo do grupo</h3><label class="check"><input type="checkbox" name="reviewed" value="1" required> Revisei a amostra e os filtros acima.</label><div class="field"><label>Digite RECUSAR</label><input name="confirm_text" required autocomplete="off"></div><button class="btn danger full">Recusar escopo de <?=$count?> registro(s)</button>
    </form>
  </div>
  <?php endif;?>
</div>
<?php endif;?>
<?php require __DIR__ . '/includes/footer.php'; ?>
