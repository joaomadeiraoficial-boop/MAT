<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Fontes'; $active='fontes'; $marcoId=(int)($_GET['marco']??$_POST['marco_id']??0);
if($_SERVER['REQUEST_METHOD']==='POST'){
  require_csrf();
  $nome=Normalizer::text($_POST['nome']??'');
  if($nome===''){flash('error','Nome da fonte é obrigatório.');redirect('fontes.php'.($marcoId?'?marco='.$marcoId:''));}
  $stmt=$pdo->prepare('INSERT INTO fontes (nome,tipo,autores,ano_publicacao,doi,url,observacao,verificada) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE tipo=VALUES(tipo),autores=VALUES(autores),ano_publicacao=VALUES(ano_publicacao),doi=VALUES(doi),url=VALUES(url),observacao=VALUES(observacao),verificada=VALUES(verificada),id=LAST_INSERT_ID(id)');
  $stmt->execute([$nome,$_POST['tipo']??'outra',Normalizer::text($_POST['autores']??''),($_POST['ano_publicacao']??'')!==''?(int)$_POST['ano_publicacao']:null,Normalizer::text($_POST['doi']??''),Normalizer::text($_POST['url']??''),Normalizer::text($_POST['observacao']??''),isset($_POST['verificada'])?1:0]);
  $sourceId=(int)$pdo->lastInsertId();
  if($marcoId){$pdo->prepare('INSERT INTO marco_fontes (marco_id,fonte_id,papel) VALUES (?,?,?) ON DUPLICATE KEY UPDATE papel=VALUES(papel)')->execute([$marcoId,$sourceId,$_POST['papel']??'confirmacao']);}
  Audit::log($pdo,'fonte',$sourceId,'upsert',null,['nome'=>$nome]);flash('success','Fonte salva'.($marcoId?' e vinculada ao marco.':'.'));redirect('fontes.php'.($marcoId?'?marco='.$marcoId:''));
}
$fontes=$pdo->query('SELECT f.*,COUNT(mf.marco_id) usos FROM fontes f LEFT JOIN marco_fontes mf ON mf.fonte_id=f.id GROUP BY f.id ORDER BY f.verificada DESC,f.nome')->fetchAll();
$marco=null;if($marcoId){$stmt=$pdo->prepare('SELECT id,nome FROM marcos WHERE id=?');$stmt->execute([$marcoId]);$marco=$stmt->fetch();}
require __DIR__ . '/includes/header.php';
?>
<?php if($marco):?><div class="context-banner">Vinculando fonte ao marco <b>#<?=$marco['id']?> <?=e($marco['nome'])?></b> <a href="marco.php?id=<?=$marco['id']?>">voltar ao marco</a></div><?php endif;?>
<div class="grid cols-2 gap-lg">
<div class="panel"><div class="panel-head"><div><h2><?= $marco?'Adicionar e vincular fonte':'Cadastrar fonte' ?></h2><p>Referências são separadas dos marcos para permitir reuso e auditoria.</p></div></div><form method="post" class="stack"><?=csrf_field()?><input type="hidden" name="marco_id" value="<?=$marcoId?>"><div class="field"><label>Nome / referência *</label><input name="nome" required placeholder="Ex.: Modis (2002) — Forecasting the growth of complexity"></div><div class="form-grid cols-2"><div class="field"><label>Tipo</label><select name="tipo"><?php foreach(['artigo','livro','base_dados','site_institucional','importada','outra'] as $t):?><option value="<?=$t?>"><?=$t?></option><?php endforeach;?></select></div><div class="field"><label>Ano da publicação</label><input name="ano_publicacao" type="number"></div></div><div class="field"><label>Autores</label><input name="autores"></div><div class="field"><label>DOI</label><input name="doi"></div><div class="field"><label>URL</label><input name="url" type="url"></div><?php if($marco):?><div class="field"><label>Papel desta fonte</label><select name="papel"><option value="origem">Origem do marco</option><option value="confirmacao">Confirmação independente</option><option value="data">Evidência da data</option><option value="impacto">Evidência do impacto</option><option value="contexto">Contexto</option></select></div><?php endif;?><div class="field"><label>Observação</label><textarea name="observacao" rows="3"></textarea></div><label class="check"><input type="checkbox" name="verificada"> Fonte verificada manualmente</label><button class="btn primary" type="submit">Salvar fonte</button></form></div>
<div class="panel"><div class="panel-head"><div><h2>Fontes cadastradas</h2><p><?=count($fontes)?> referência(s)</p></div></div><div class="source-list"><?php if(!$fontes):?><div class="empty">Nenhuma fonte cadastrada.</div><?php endif;?><?php foreach($fontes as $f):?><div class="source-row"><div><b><?=e($f['nome'])?></b><small><?=e($f['tipo'])?> · <?=$f['usos']?> marco(s)<?= $f['ano_publicacao']?' · '.$f['ano_publicacao']:''?></small></div><span class="badge <?=$f['verificada']?'badge-success':'badge-muted'?>"><?=$f['verificada']?'Verificada':'Pendente'?></span></div><?php endforeach;?></div></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
