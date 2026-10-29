<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/AssistenteCientifico.php';
if (!is_admin()) { header('Location: index.php?page=login'); exit; }
$pdo=db();
if (!$pdo) { http_response_code(503); exit('Banco MySQL nao esta conectado.'); }
$kind=(string)($_POST['tipo']??$_GET['tipo']??'');
$id=(int)($_POST['id']??$_GET['id']??0);
$row=null;$sources=[];$ai=null;$error=null;
if(in_array($kind,['import','marco'],true) && $id>0){
 if($kind==='import') {
   $q=$pdo->prepare('SELECT * FROM import_itens WHERE id=? LIMIT 1');
 } else {
   $q=$pdo->prepare('SELECT * FROM marcos WHERE id=? LIMIT 1');
 }
 $q->execute([$id]);$row=$q->fetch()?:null;
 if ($row && $kind==='marco') $sources=linked_sources($id);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!hash_equals(csrf_token(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Token CSRF invalido.');}
 if(!$row) $error='Registro nao localizado.';
 else {try {set_time_limit(115);$ai=AssistenteCientifico::queryOllama($row,$sources,$kind);}catch(Throwable $e){$error=$e->getMessage();}}
}
$lim=30;
$pg=max(1,min(1000,(int)($_GET['pagina']??1)));
$offset=($pg-1)*$lim;
$importRows=$pdo->query("SELECT id,titulo,ano,categoria,status,descricao FROM import_itens WHERE status='pendente' ORDER BY id ASC LIMIT $lim OFFSET $offset")->fetchAll();
$markers=$pdo->query("SELECT m.id,m.titulo,m.ano,m.categoria,m.status,m.descricao,m.escopo_revisado, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.marco_id=m.id) AS fontes FROM marcos m WHERE m.status <> 'rejeitado' ORDER BY m.escopo_revisado ASC, m.id DESC LIMIT $lim")->fetchAll();
$nImport=(int)$pdo->query("SELECT COUNT(*) FROM import_itens WHERE status='pendente'")->fetchColumn();
$nMarkers=(int)$pdo->query('SELECT COUNT(*) FROM marcos')->fetchColumn();
$nReviewed=(int) $pdo->query("SELECT COUNT(*) FROM marco_avaliacoes_revisadas WHERE estado='confirmada'")->fetchColumn();
function hd(string $s): string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><link rel="icon" href="assets/insignia.svg" type="image/svg+xml"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>MAT | Assistente de triagem experimental</title><link rel="stylesheet" href="assets/style.css"><style>
.mat-a{max-width:1100px;margin:0 auto;padding:40px 20px 80px;font-family:Georgia,serif;color:#172a2d}.mat-a h1{font-size:clamp(36px,5vw,68px);font-weight:400;margin:10px 0}.mat-a h2{font-size:28px;margin-top:28px}.mat-a p,.mat-a li{line-height:1.6}.mat-a small{font-family:monospace}.mat-a a{color:#935e3e;text-decoration:underline}.mat-a .box{border:1px solid #d6cbb9;background:#fffcf7;padding:22px;margin:18px 0}.mat-a .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:12px}.mat-a .stat{border:1px solid #d6cbb9;padding:18px;background:#f7efe3}.mat-a .stat strong{font-size:28px;display:block}.mat-a table{width:100%;border-collapse:collapse;font-size:14px}.mat-a th,.mat-a td{padding:12px 8px;border-bottom:1px solid #d6cbb9;text-align:left;vertical-align:top}.mat-a .wrap{overflow:auto}.mat-a button{padding:14px 22px;border:0;background:#122426;color:white;cursor:pointer}.mat-a .warn{background:#fff2df;border-left:4px solid #a66b47;padding:14px}.mat-a .muted{color:#666}.mat-a code{overflow-wrap:anywhere}.mat-a .score{font-size:20px;font-weight:bold}.mat-a details{margin:8px 0}
</style></head><body style="background:#f5f0e8"><main class="mat-a">
<a href="index.php?page=laboratorio">← Voltar ao Laboratório científico</a>
<h1>Assistente de <em>triagem.</em></h1>
<p>O MAT sugere a próxima ação sem aprovar marcos nem preencher as notas por você. É um apoio à pesquisa, não um parecer científico definitivo.</p>
<div class="stats"><div class="stat"><small>ITENS EM PREPARAÇÃO</small><strong><?=$nImport?></strong></div><div class="stat"><small>MARCOS DO ACERVO</small><strong><?=$nMarkers?></strong></div><div class="stat"><small>RUBRICAS CONFIRMADAS</small><strong><?=$nReviewed?></strong></div></div>
<div class="warn"><strong>Privacidade e validade</strong>: a análise com IA só envia pequenos trechos documentais ao servidor Ollama em <code>127.0.0.1</code>, nunca a uma API externa. Os dados do livro não devem ser publicados no GitHub. Notas sugeridas não são gravadas, nem consideradas aprovadas. Uma citação localizada pode continuar sendo mal interpretada.</div>
<?php if($row): ?>
<section class="box"><small>FICHA #<?=$id?> — <?=hd($kind==='import'?'ITEM IMPORTADO':'MARCO DO ACERVO')?></small><h2><?=hd((string)$row['titulo'])?></h2><p><?=hd((string)($row['descricao']??''))?></p>
<p>Ano detectado: <?=hd((string)($row['ano']??'—'))?> · Categoria: <?=hd((string)($row['categoria']??'—'))?> · Fontes vinculadas: <?=count($sources)?></p>
<h3>Próximas ações recomendadas pelas regras</h3><ol><?php foreach(AssistenteCientifico::localSuggestions($row,$kind,count($sources)) as $s): ?><li><?=hd($s)?></li><?php endforeach;?></ol>
<?php if($kind==='import'):
$raw=json_decode((string)($row['bruto_json']??''),true)?:[]; ?>
<details><summary>Trecho disponível e metadados (dados brutos)</summary><p>Fonte: <?=hd((string)($raw['fonte']??$row['fonte']??''))?> · Página <?=hd((string)($raw['pagina_livro']??'não indicada'))?></p><p><?=hd((string)($raw['trecho_evidencia']??'Não cadastrado'))?></p></details><p><a href="index.php?page=importar&amp;item=<?=$id?>">Examinar importação no MAT</a></p>
<?php else: ?><p><a href="index.php?page=triagem&amp;id=<?=$id?>">Ver classificação por regras já existente</a> · <a href="index.php?page=avaliacao&amp;id=<?=$id?>">Preencher 11 critérios</a> · <a href="index.php?page=fontes&amp;marco_id=<?=$id?>">Vincular fontes</a></p><?php endif;?>
<?php if($error): ?><div class="warn"><?=hd($error)?></div><?php endif;?>
<?php if($ai): ?><h3>Pré-avaliação da IA local — NÃO VALIDADA</h3><p><strong>Natureza sugerida:</strong> <?=hd($ai['natureza'])?></p><p><strong>Ação sugerida:</strong> <?=hd($ai['acao_sugerida'])?></p><div class="wrap"><table><thead><tr><th>Critério</th><th>Sugestão</th><th>Citação conferida</th><th>Ressalva</th></tr></thead><tbody><?php foreach(AssistenteCientifico::FIELDS as $k):$v=$ai['criterios'][$k];?><tr><td><?=hd(AssistenteCientifico::rubricLabels()[$k]??$k)?></td><td class="score"><?=$v['nota']===null?'—':$v['nota'].'/3'?></td><td><?=hd($v['citacao'])?></td><td><?=hd($v['status'])?>. <?=hd($v['justificativa'])?></td></tr><?php endforeach;?></tbody></table></div>
<?php if($ai['riscos']):?><h3>Riscos apontados</h3><ul><?php foreach($ai['riscos'] as $r):?><li><?=hd(substr($r,0,500))?></li><?php endforeach;?></ul><?php endif;?>
<p class="muted">O validador descarta toda nota sem citação textual encontrada no trecho fornecido. Isso não confirma que a interpretação esteja correta; avaliação humana permanece obrigatória.</p>
<?php endif;?>
<form method="post" action="assistente_ia.php"><input type="hidden" name="csrf" value="<?=hd(csrf_token())?>"><input type="hidden" name="tipo" value="<?=hd($kind)?>"><input type="hidden" name="id" value="<?=$id?>"><button type="submit">Analisar esta ficha com Ollama local</button><p class="muted"><small>Modelo: <?=hd(AssistenteCientifico::MODEL)?>. Requer Ollama instalado, cURL habilitado e trechos documentais.</small></p></form>
</section>
<?php endif;?>
<h2>Fila de candidatos importados <small>(página <?=$pg?>, até 30 por página)</small></h2><div class="box wrap"><table><thead><tr><th>Identificação</th><th>Ano</th><th>Próxima ação</th></tr></thead><tbody>
<?php foreach($importRows as $r):?><tr><td><a href="assistente_ia.php?tipo=import&amp;id=<?=$r['id']?>"><?=hd(substr((string)$r['titulo'],0,105))?></a></td><td><?=hd((string)$r['ano'])?></td><td>Revisar cronologia, confirmar fonte, corrigir categoria e duplicatas</td></tr><?php endforeach;?>
<?php if(!$importRows):?><tr><td colspan="3">Nenhum item pendente em preparação.</td></tr><?php endif;?></tbody></table>
<p><?php if($pg>1): ?><a href="assistente_ia.php?pagina=<?=$pg-1?>">← Página anterior</a><?php endif;?>
<?php if($pg*$lim<$nImport): ?> &nbsp; <a href="assistente_ia.php?pagina=<?=$pg+1?>">Próxima página →</a><?php endif;?></p>
</div>
<h2>Acervo <small>(até 30 registros, escopo pendente primeiro)</small></h2><div class="box wrap"><table><thead><tr><th>Marco</th><th>Ano</th><th>Próxima ação</th></tr></thead><tbody><?php foreach($markers as $m):?><tr><td><a href="assistente_ia.php?tipo=marco&amp;id=<?=$m['id']?>"><?=hd(substr((string)$m['titulo'],0,105))?></a></td><td><?=hd((string)$m['ano'])?></td><td><?=empty($m['escopo_revisado'])?'Conferir escopo · ':''?><?=(int)$m['fontes']===0?'Vincular fonte':'Avaliar 11 critérios com fonte e justificativa'?></td></tr><?php endforeach;?></tbody></table></div>
<p><a href="index.php?page=laboratorio">Voltar ao Laboratório científico</a></p>
</main></body></html>
