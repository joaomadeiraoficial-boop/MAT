<?php
require __DIR__ . '/includes/bootstrap.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = $id ? 'Revisar marco' : 'Novo marco'; $active='marcos';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $nome = Normalizer::text($_POST['nome'] ?? '');
    if ($nome === '') { flash('error','O nome do marco é obrigatório.'); redirect($id?'marco.php?id='.$id:'marco.php'); }

    $impacto = ($_POST['impacto'] ?? '') !== '' ? (float)$_POST['impacto'] : null;
    $confiabilidade = ($_POST['confiabilidade'] ?? '') !== '' ? (float)$_POST['confiabilidade'] : null;
    $fator = ($_POST['fator_fundacional'] ?? '') !== '' ? (float)$_POST['fator_fundacional'] : null;
    if ($impacto !== null && ($impacto < 1 || $impacto > 10)) throw new RuntimeException('Impacto deve estar entre 1 e 10.');
    if ($confiabilidade !== null && ($confiabilidade < 1 || $confiabilidade > 10)) throw new RuntimeException('Confiabilidade deve estar entre 1 e 10.');
    if ($fator !== null && ($fator < 1 || $fator > 1.5)) throw new RuntimeException('Fator fundacional deve estar entre 1,0 e 1,5.');

    $score = ($impacto!==null && $confiabilidade!==null && $fator!==null) ? Temporis::score($impacto,$confiabilidade,$fator) : null;
    $natureza = $_POST['natureza'] ?? 'nao_classificado';
    $allowedNature = ['nao_classificado','tecnologico','descoberta_cientifica','metodo_infraestrutura','social_politico','biologico_evolutivo','cosmologico','misto','outro'];
    if (!in_array($natureza,$allowedNature,true)) $natureza='nao_classificado';
    $eligibilidade = $_POST['elegibilidade_temporis'] ?? 'revisar';
    if (!in_array($eligibilidade,['sim','nao','revisar'],true)) $eligibilidade='revisar';
    $fields = [
        'nome'=>$nome,'nome_normalizado'=>Normalizer::key($nome),
        'ano_inicio'=>($_POST['ano_inicio']??'')!==''?(float)$_POST['ano_inicio']:null,
        'ano_fim'=>($_POST['ano_fim']??'')!==''?(float)$_POST['ano_fim']:null,
        'ano_ref'=>($_POST['ano_ref']??'')!==''?(float)$_POST['ano_ref']:null,
        'precisao_data'=>$_POST['precisao_data']??'desconhecida',
        'categoria'=>Normalizer::category($_POST['categoria']??''),
        'subcategoria'=>Normalizer::text($_POST['subcategoria']??''),
        'civilizacao'=>Normalizer::text($_POST['civilizacao']??''),
        'pais'=>Normalizer::text($_POST['pais']??''),'cidade'=>Normalizer::text($_POST['cidade']??''),
        'descricao'=>Normalizer::text($_POST['descricao']??''),'origem'=>Normalizer::text($_POST['origem']??''),
        'inventores'=>Normalizer::text($_POST['inventores']??''),'organizacao'=>Normalizer::text($_POST['organizacao']??''),'ambiente'=>Normalizer::text($_POST['ambiente']??''),
        'natureza'=>$natureza,'elegibilidade_temporis'=>$eligibilidade,
        'motivo_escopo'=>Normalizer::text($_POST['motivo_escopo']??''),'escopo_revisado'=>1,
        'impacto'=>$impacto,'confiabilidade'=>$confiabilidade,'fator_fundacional'=>$fator,'score_pi'=>$score,
        'justificativa_impacto'=>Normalizer::text($_POST['justificativa_impacto']??''),
        'justificativa_confiabilidade'=>Normalizer::text($_POST['justificativa_confiabilidade']??''),
        'justificativa_fundacional'=>Normalizer::text($_POST['justificativa_fundacional']??''),
        'status'=>$_POST['status']??'revisao','incluir_temporis'=>isset($_POST['incluir_temporis'])?1:0,
    ];

    if ($fields['status']==='aprovado') {
        $sourceCount = $id ? (int)$pdo->query('SELECT COUNT(*) FROM marco_fontes WHERE marco_id='.(int)$id)->fetchColumn() : 0;
        if ($sourceCount < 1) $fields['incluir_temporis']=0;
        if ($score === null) $fields['incluir_temporis']=0;
        if ($eligibilidade !== 'sim') $fields['incluir_temporis']=0;
        if ((int)$fields['escopo_revisado'] !== 1) $fields['incluir_temporis']=0;
    } else { $fields['incluir_temporis']=0; }

    if ($id) {
        $stmt=$pdo->prepare('SELECT * FROM marcos WHERE id=?');$stmt->execute([$id]);$before=$stmt->fetch();
        $set=implode(',',array_map(fn($k)=>"$k=?",array_keys($fields)));
        $stmt=$pdo->prepare("UPDATE marcos SET $set WHERE id=?");$stmt->execute([...array_values($fields),$id]);
        Audit::log($pdo,'marco',$id,'update',$before,$fields);
    } else {
        $cols=implode(',',array_keys($fields));$ph=implode(',',array_fill(0,count($fields),'?'));
        $stmt=$pdo->prepare("INSERT INTO marcos ($cols) VALUES ($ph)");$stmt->execute(array_values($fields));
        $id=(int)$pdo->lastInsertId(); Audit::log($pdo,'marco',$id,'create',null,$fields);
    }
    DuplicateDetector::refreshFor($pdo,$id,$app['duplicate_threshold'],$app['duplicate_year_tolerance']);
    flash('success','Marco salvo. A pontuação e os candidatos a duplicata foram recalculados.');
    redirect('marco.php?id='.$id);
}

$marco = [
 'id'=>0,'nome'=>'','ano_inicio'=>null,'ano_fim'=>null,'ano_ref'=>null,'precisao_data'=>'desconhecida','categoria'=>'Não classificado','subcategoria'=>'','civilizacao'=>'','pais'=>'','cidade'=>'','descricao'=>'','origem'=>'','inventores'=>'','organizacao'=>'','ambiente'=>'','natureza'=>'nao_classificado','elegibilidade_temporis'=>'revisar','sugestao_elegibilidade'=>'revisar','motivo_escopo'=>'','escopo_revisado'=>0,'classificador_versao'=>null,'scope_score'=>0,'scope_confidence'=>'baixa','impacto'=>null,'confiabilidade'=>null,'fator_fundacional'=>null,'score_pi'=>null,'justificativa_impacto'=>'','justificativa_confiabilidade'=>'','justificativa_fundacional'=>'','status'=>'revisao','incluir_temporis'=>0,'raw_id'=>null
];
$sourceRows=[];$dups=[];$history=[];$raw=null;
if ($id) {
    $stmt=$pdo->prepare('SELECT * FROM marcos WHERE id=?');$stmt->execute([$id]);$found=$stmt->fetch();
    if(!$found){http_response_code(404);exit('Marco não encontrado.');}$marco=$found;
    $stmt=$pdo->prepare('SELECT f.*,mf.papel,mf.pagina,mf.trecho FROM marco_fontes mf JOIN fontes f ON f.id=mf.fonte_id WHERE mf.marco_id=? ORDER BY f.nome');$stmt->execute([$id]);$sourceRows=$stmt->fetchAll();
    $stmt=$pdo->prepare('SELECT d.*, a.nome nome_a,b.nome nome_b FROM duplicate_candidates d JOIN marcos a ON a.id=d.marco_a JOIN marcos b ON b.id=d.marco_b WHERE (d.marco_a=? OR d.marco_b=?) AND d.status="pendente" ORDER BY d.similarity DESC');$stmt->execute([$id,$id]);$dups=$stmt->fetchAll();
    $stmt=$pdo->prepare('SELECT * FROM revisoes_marco WHERE marco_id=? ORDER BY id DESC LIMIT 20');$stmt->execute([$id]);$history=$stmt->fetchAll();
    if($marco['raw_id']){$stmt=$pdo->prepare('SELECT * FROM marcos_raw WHERE id=?');$stmt->execute([$marco['raw_id']]);$raw=$stmt->fetch();}
}
require __DIR__ . '/includes/header.php';
?>
<form method="post" class="editor-layout"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
  <div class="editor-main stack">
    <div class="panel">
      <div class="panel-head"><div><h2>Identificação</h2><p>O registro limpo. O dado bruto fica preservado para rastreabilidade.</p></div><span class="badge <?=status_class($marco['status'])?>"><?=status_label($marco['status'])?></span></div>
      <div class="field"><label>Nome do marco *</label><input name="nome" required value="<?=e($marco['nome'])?>" placeholder="Ex.: Transistor"></div>
      <div class="form-grid cols-3">
        <div class="field"><label>Ano início</label><input type="number" step="0.01" name="ano_inicio" value="<?=e((string)$marco['ano_inicio'])?>"></div>
        <div class="field"><label>Ano fim</label><input type="number" step="0.01" name="ano_fim" value="<?=e((string)$marco['ano_fim'])?>"></div>
        <div class="field"><label>Ano de referência</label><input type="number" step="0.01" name="ano_ref" value="<?=e((string)$marco['ano_ref'])?>"></div>
      </div>
      <div class="form-grid cols-3"><div class="field"><label>Precisão da data</label><select name="precisao_data"><?php foreach(['ano'=>'Ano exato','intervalo'=>'Intervalo','texto'=>'Texto/incerta','desconhecida'=>'Desconhecida'] as $k=>$v):?><option value="<?=$k?>" <?=$marco['precisao_data']===$k?'selected':''?>><?=$v?></option><?php endforeach;?></select></div><div class="field"><label>Categoria</label><input name="categoria" value="<?=e($marco['categoria'])?>"></div><div class="field"><label>Subcategoria</label><input name="subcategoria" value="<?=e($marco['subcategoria'])?>"></div></div>
      <div class="form-grid cols-3"><div class="field"><label>Civilização / sociedade</label><input name="civilizacao" value="<?=e($marco['civilizacao'])?>"></div><div class="field"><label>País</label><input name="pais" value="<?=e($marco['pais'])?>"></div><div class="field"><label>Cidade / localidade</label><input name="cidade" value="<?=e($marco['cidade'])?>"></div></div>
      <div class="field"><label>Origem do registro</label><input name="origem" value="<?=e($marco['origem'])?>" placeholder="Modis, Huebner, PCDB..."></div>
      <div class="form-grid cols-2"><div class="field"><label>Inventor(es) / descobridor(es)</label><input name="inventores" value="<?=e($marco['inventores'] ?? '')?>"></div><div class="field"><label>Organização / instituição</label><input name="organizacao" value="<?=e($marco['organizacao'] ?? '')?>"></div></div>
      <div class="field"><label>Ambiente / contexto original</label><input name="ambiente" value="<?=e($marco['ambiente'] ?? '')?>"></div>
      <div class="field"><label>Descrição</label><textarea name="descricao" rows="5"><?=e($marco['descricao'])?></textarea></div>
    </div>

    <div class="panel scope-panel">
      <div class="panel-head"><div><h2>Escopo científico da Têmporis</h2><p>O sistema pode sugerir; a decisão final de inclusão é sua e fica registrada.</p></div><span class="badge <?=eligibility_class($marco['sugestao_elegibilidade'] ?? 'revisar')?>">Sugestão: <?=e(eligibility_label($marco['sugestao_elegibilidade'] ?? 'revisar'))?></span></div>
      <div class="form-grid cols-2">
        <div class="field"><label>Natureza do marco</label><select name="natureza"><?php foreach(['nao_classificado','tecnologico','descoberta_cientifica','metodo_infraestrutura','social_politico','biologico_evolutivo','cosmologico','misto','outro'] as $n):?><option value="<?=$n?>" <?=$marco['natureza']===$n?'selected':''?>><?=e(scope_nature_label($n))?></option><?php endforeach;?></select></div>
        <div class="field"><label>Elegibilidade para o índice</label><select name="elegibilidade_temporis"><option value="revisar" <?=$marco['elegibilidade_temporis']==='revisar'?'selected':''?>>Revisar / ainda não decidido</option><option value="sim" <?=$marco['elegibilidade_temporis']==='sim'?'selected':''?>>SIM — representa capacidade científico-tecnológica</option><option value="nao" <?=$marco['elegibilidade_temporis']==='nao'?'selected':''?>>NÃO — contexto ou fora do escopo</option></select></div>
      </div>
      <div class="field"><label>Justificativa de escopo</label><textarea name="motivo_escopo" rows="4" placeholder="Explique por que este marco entra ou não entra na Têmporis."><?=e($marco['motivo_escopo'])?></textarea></div>
      <div class="scope-hint"><b>Triagem automática:</b> confiança <?=e($marco['scope_confidence']??'baixa')?> · score <?=e((string)($marco['scope_score']??0))?> · versão <?=e($marco['classificador_versao']??'—')?>.<br><b>Regra de trabalho:</b> entra quando representa aquisição/expansão de capacidade técnica, científica ou de produção de conhecimento. Eventos políticos e sociais podem permanecer no MAT como contexto sem compor o índice.</div>
    </div>

    <div class="panel score-panel">
      <div class="panel-head"><div><h2>Pontuação Têmporis</h2><p>As notas devem ser justificadas. O sistema calcula Pᵢ automaticamente ao salvar.</p></div><div class="score-big"><small>Pᵢ</small><strong><?=$marco['score_pi']!==null?number_format((float)$marco['score_pi'],3,',','.'): '—'?></strong></div></div>
      <div class="form-grid cols-3">
        <div class="field"><label>Impacto I <span>1–10</span></label><input type="number" min="1" max="10" step="0.1" name="impacto" value="<?=e((string)$marco['impacto'])?>"><textarea name="justificativa_impacto" rows="3" placeholder="Por que esta nota?"><?=e($marco['justificativa_impacto'])?></textarea></div>
        <div class="field"><label>Confiabilidade C <span>1–10</span></label><input type="number" min="1" max="10" step="0.1" name="confiabilidade" value="<?=e((string)$marco['confiabilidade'])?>"><textarea name="justificativa_confiabilidade" rows="3" placeholder="Fontes, precisão da data..."><?=e($marco['justificativa_confiabilidade'])?></textarea></div>
        <div class="field"><label>Fundacional F <span>1,0–1,5</span></label><input type="number" min="1" max="1.5" step="0.01" name="fator_fundacional" value="<?=e((string)$marco['fator_fundacional'])?>"><textarea name="justificativa_fundacional" rows="3" placeholder="Dependências e tecnologias derivadas..."><?=e($marco['justificativa_fundacional'])?></textarea></div>
      </div>
      <div class="formula">Pᵢ = (I² × C/100) × F</div>
    </div>
  </div>

  <aside class="editor-side stack">
    <div class="panel sticky-card"><h3>Decisão</h3><div class="field"><label>Status</label><select name="status"><?php foreach(['revisao','aprovado','rejeitado','duplicado','rascunho'] as $s):?><option value="<?=$s?>" <?=$marco['status']===$s?'selected':''?>><?=status_label($s)?></option><?php endforeach;?></select></div><label class="check"><input type="checkbox" name="incluir_temporis" value="1" <?=$marco['incluir_temporis']?'checked':''?>> Incluir no cálculo Têmporis</label><small class="muted">O sistema só inclui no índice se houver fonte, pontuação completa e escopo revisado como ELEGÍVEL.</small><button class="btn primary large" type="submit">Salvar alterações</button><?php if($id):?><a class="btn ghost" href="revisar.php">Voltar à fila</a><?php endif;?></div>

    <?php if($id):?>
    <div class="panel"><div class="panel-head"><div><h3>Fontes</h3><p><?=count($sourceRows)?> vinculada(s)</p></div></div><?php if(!$sourceRows):?><div class="empty mini">Nenhuma fonte vinculada.</div><?php endif;?><?php foreach($sourceRows as $s):?><div class="source-chip"><b><?=e($s['nome'])?></b><small><?=e($s['papel'])?><?= $s['verificada']?' · verificada':'' ?></small></div><?php endforeach;?><a class="btn ghost full" href="fontes.php?marco=<?=$id?>">Gerenciar fontes</a></div>
    <div class="panel"><div class="panel-head"><div><h3>Possíveis duplicatas</h3><p><?=count($dups)?> pendente(s)</p></div></div><?php if(!$dups):?><div class="empty mini">Nada suspeito.</div><?php endif;?><?php foreach($dups as $d): $other=$d['marco_a']==$id?$d['nome_b']:$d['nome_a'];?><a class="dup-mini" href="duplicatas.php#dup-<?=$d['id']?>"><span><?=round($d['similarity']*100)?>%</span><b><?=e($other)?></b></a><?php endforeach;?></div>
    <?php if($raw):?><details class="panel raw-card"><summary>Dado bruto original</summary><dl><dt>Nome</dt><dd><?=e($raw['raw_name'])?></dd><dt>Ano</dt><dd><?=e($raw['raw_year'])?></dd><dt>Categoria</dt><dd><?=e($raw['raw_category'])?></dd><dt>Fonte</dt><dd><?=e($raw['raw_source'])?></dd></dl></details><?php endif;?>
    <?php endif;?>
  </aside>
</form>
<?php require __DIR__ . '/includes/footer.php'; ?>
