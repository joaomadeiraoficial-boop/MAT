<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/AtlasReais.php';
require_once __DIR__.'/app/TriagemLoteReal.php';
if(!is_admin()){header('Location: index.php?page=login');exit;}
$pdo=db();$error=null;$rows=[];
if(!$pdo)$error='MySQL não conectado. Nenhuma informação fictícia será exibida.';
else try {$rows=TriagemLoteReal::fetch300($pdo);}catch(Throwable $e){error_log($e->getMessage());$error='Não foi possível consultar o lote real.';}
$stats=TriagemLoteReal::state($rows);
$page=max(1,min(1000,(int)($_GET['pagina']??1)));
$per=25;$maxPage=max(1,(int)ceil(count($rows)/$per));$page=min($page,$maxPage);
$items=array_slice($rows,($page-1)*$per,$per);
$selected=(int)($_GET['item']??0);
$sel=null;$progress=null;
foreach($rows as $r){if((int)$r['id']===$selected){$sel=$r;$progress=TriagemLoteReal::load($selected);break;}}
$e=static fn($s)=>htmlspecialchars((string)($s??''),ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$fmt=static fn($n,$d=4)=>number_format((float)$n,$d,',','.');
?>
<!doctype html><html lang="pt-BR"><head><link rel="icon" href="assets/insignia.svg" type="image/svg+xml"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MAT — Triagem real dos 300</title><link rel="stylesheet" href="assets/style.css"><style>
.mat-real{max-width:1120px;margin:auto;padding:42px 20px 75px;color:#162a2c;font-family:Georgia,serif}.mat-real h1{font-size:clamp(34px,5vw,60px);font-weight:400}.mat-real h2{font-size:26px;margin-top:30px}.mat-real a{color:#9c603f}.mat-real .numbers{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:12px}.mat-real .number{padding:20px;background:#fffaf2;border:1px solid #d6cbbb}.mat-real .number strong{display:block;font-size:30px;margin:8px 0}.mat-real .warn{background:#fff1dd;border-left:4px solid #af7549;padding:17px;margin:20px 0;line-height:1.6}.mat-real .box{background:#fffcf8;border:1px solid #d8cbba;padding:24px;margin-top:18px}.mat-real table{width:100%;border-collapse:collapse;font-size:14px}.mat-real td,.mat-real th{padding:11px 8px;border-bottom:1px solid #e2d8cd;text-align:left;vertical-align:top}.mat-real .overflow{overflow:auto}.mat-real code{overflow-wrap:anywhere}.mat-real .muted{color:#655b54;font-size:13px}.mat-real ol{line-height:1.7}
</style></head><body style="background:#f5f0e8"><main class="mat-real">
<a href="index.php?page=atlas">← Atlas das eras</a> · <a href="index.php?page=importar">Importação</a> · <a href="experimento_temporis.php">Backtest real</a>
<p class="muted">MAT / DADOS DOCUMENTAIS / ETAPA DE TRIAGEM</p><h1>300 candidatos <em>reais.</em></h1>
<p>Registros extraídos de uma obra histórica. São <strong>menções para conferir</strong>, não 300 acontecimentos já validados. A IA local ajuda a produzir propostas auditáveis, mas não aprova notas.</p>
<?php if($error):?><div class="warn"><?=$e($error)?></div><?php elseif(!$rows):?><div class="warn">Ainda não foi localizado o lote <code><?=TriagemLoteReal::SOURCE_FILE?></code>. Importe o CSV antes de iniciar o processamento.</div><?php endif;?>
<div class="numbers"><div class="number">Itens do lote<strong><?=$stats['total_lote']?></strong></div><div class="number">Preparados por regras<strong><?=$stats['preparados']?></strong></div><div class="number">Analisados pelo Qwen<strong><?=$stats['avaliados_ia']?></strong></div><div class="number">Com 11 notas citadas<strong><?=$stats['ia_11_notas']?></strong></div><div class="number">Erros do Qwen<strong><?=$stats['erros_ia']?></strong></div></div>
<div class="warn"><strong>Estado científico: NÃO VALIDADO.</strong> As propostas e possíveis pesos ficam num diretório privado fora do Git e do htdocs. Uma citação textual localizada é apenas um primeiro filtro: não garante interpretação histórica, independência das fontes ou data correta. A Têmporis oficial só utiliza marcos aprovados e avaliações confirmadas. Este lote termina em 2002, portanto sozinho não permite estimar erro observado em 2020.</div>
<section class="box"><h2>Como trabalhar em lote</h2><p>Abra o PowerShell na pasta do projeto. Execute os comandos abaixo. A primeira etapa é rápida; a segunda consulta o Ollama local e pode demorar na GTX 1050.</p>
<ol><li>Organizar os 300 sem IA: <code>php .\MAT_Refinado_0_1\cli\triagem_300_reais.php --modo=preparar</code></li>
<li>Solicitar sugestões para cinco registros: <code>php .\MAT_Refinado_0_1\cli\triagem_300_reais.php --modo=ia --limite=5</code></li>
<li>Repetir o comando de IA para continuar, ou usar <code>--limite=30</code> se preferir um lote maior. Cada proposta é salva antes de passar à próxima.</li>
<li>Conferir o andamento: <code>php .\MAT_Refinado_0_1\cli\triagem_300_reais.php --modo=resumo</code></li></ol>
<p class="muted">Se o PHP não estiver no PATH, execute com o caminho completo de <code>D:\Faculdade nero\xampp\php\php.exe</code>. Os arquivos privados ficam em <code><?=$e(TriagemLoteReal::directory())?></code>.</p></section>
<?php if($sel): $notes=$progress['proposta']??null;?><section class="box" id="ficha"><a href="triagem_300_reais.php?pagina=<?=$page?>">← Voltar à fila</a><h2><?=$e($sel['titulo'])?></h2><p>Ano detectado: <?=$e($sel['ano'])?> · Fonte: <?=$e($sel['fonte'])?> · Página: <?=$e($progress['pagina_livro']??'não informada')?></p><p><strong>Situação:</strong> <?=$e($progress['ia_status']??'não preparado')?></p>
<?php foreach($progress['triagem_regra']['flags']??[] as $flag):?><p class="muted">• <?=$e($flag)?></p><?php endforeach;?>
<?php if($notes):?><p><strong>Notas com citação localizada: <?=$e($notes['notas_com_citacao'])?>/11</strong>. <?= $notes['peso_provisorio_nao_validado']!==null ? 'Peso provisório (SEM aprovação): '.$fmt($notes['peso_provisorio_nao_validado']['w'],6):'Peso indefinido: não há evidências para todos os 11 critérios.'?></p><div class="overflow"><table><thead><tr><th>Critério</th><th>Nota proposta</th><th>Trecho literal</th><th>Justificativa</th></tr></thead><tbody><?php foreach(TriagemLoteReal::CRITERIA as $field):$entry=$notes['criterios'][$field]??[];?><tr><td><?=$e(RevisedMethodology::ALL[$field]??$field)?></td><td><?=$entry['nota']===null?'—':$e($entry['nota']).'/3'?></td><td><?=$e($entry['citacao']??'')?></td><td><?=$e($entry['justificativa']??'')?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
<?php if(!empty($progress['erro'])):?><p class="warn">Falha na chamada ao Qwen: <?=$e($progress['erro'])?></p><?php endif;?>
<p><a href="assistente_ia.php?tipo=import&amp;id=<?=(int)$sel['id']?>">Examinar trecho completo no assistente</a></p></section><?php endif;?>
<h2>Distribuição histórica do lote</h2><div class="overflow box"><table><thead><tr><th>Era</th><th>Candidatos (não validados)</th></tr></thead><tbody><?php foreach(AtlasReais::ERAS as $key=>$era):?><tr><td><?=$e($era['nome'])?></td><td><?=$stats['contagem_por_era'][$key]??0?></td></tr><?php endforeach;?></tbody></table></div>
<h2>Fila do lote — página <?=$page?>/<?=$maxPage?></h2><div class="overflow box"><table><thead><tr><th>ID</th><th>Ano</th><th>Trecho</th><th>Estado da IA</th><th>Candidato</th></tr></thead><tbody>
<?php foreach($items as $r):$entry=TriagemLoteReal::load((int)$r['id']);?><tr><td><?=$r['id']?></td><td><?=$e($r['ano'])?></td><td><?=$entry['triagem_regra']['caracteres_trecho']??strlen(TriagemLoteReal::evidence($r))?> caracteres</td><td><?=$e($entry['ia_status']??'não preparado')?></td><td><a href="triagem_300_reais.php?pagina=<?=$page?>&amp;item=<?=$r['id']?>#ficha"><?=$e($r['titulo'])?></a></td></tr><?php endforeach;?></tbody></table>
<p><?php if($page>1):?><a href="?pagina=<?=$page-1?>">← Anterior</a><?php endif;?> <?php if($page<$maxPage):?><a href="?pagina=<?=$page+1?>">Próxima →</a><?php endif;?></p></div>
<p class="muted">Nenhuma nota foi incorporada ao MySQL pelo assistente. Para publicar um resultado científico do índice e calcular seu erro de previsão, será necessário promover e auditar os marcos, completar fontes e confirmar as rubricas.</p>
</main></body></html>
