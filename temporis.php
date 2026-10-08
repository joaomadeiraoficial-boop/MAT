<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Têmporis'; $active='temporis';
$alpha=(float)($_GET['alpha']??$app['temporis']['alpha']);
$baseYear=(int)($_GET['base_year']??$app['temporis']['base_year']);
$baseValue=(float)$app['temporis']['base_value'];
$stmt=$pdo->prepare("SELECT id,nome,ano_ref,score_pi FROM marcos WHERE status='aprovado' AND incluir_temporis=1 AND elegibilidade_temporis='sim' AND escopo_revisado=1 AND score_pi IS NOT NULL AND ano_ref IS NOT NULL ORDER BY ano_ref,id");$stmt->execute();$rows=$stmt->fetchAll();
$kBase=0.0;foreach($rows as $r){if((float)$r['ano_ref']<=$baseYear)$kBase+=(float)$r['score_pi'];}
$series=[];$k=0.0;foreach($rows as $r){$k+=(float)$r['score_pi'];$t=Temporis::index($k,$kBase,$alpha,$baseValue);$series[]=['year'=>(float)$r['ano_ref'],'name'=>$r['nome'],'k'=>$k,'t'=>$t];}
// one point per year, last cumulative value of the year
$byYear=[];foreach($series as $p){$byYear[(string)$p['year']]=$p;}$points=array_values($byYear);
$svg='';if(count($points)>1){$w=900;$h=280;$pad=36;$years=array_column($points,'year');$vals=array_column($points,'t');$minX=min($years);$maxX=max($years);$maxY=max(array_filter($vals,fn($v)=>$v!==null)?:[1]);$coords=[];foreach($points as $p){if($p['t']===null)continue;$x=$pad+(($p['year']-$minX)/max(1,$maxX-$minX))*($w-2*$pad);$y=$h-$pad-($p['t']/max(1,$maxY))*($h-2*$pad);$coords[]="$x,$y";}$svg=implode(' ',$coords);}
$coverage=['approved'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE status='aprovado'")->fetchColumn(),'included'=>count($rows),'scope_pending'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE escopo_revisado=0")->fetchColumn(),'scored'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE score_pi IS NOT NULL")->fetchColumn()];
require __DIR__ . '/includes/header.php';
?>
<div class="grid cards-4">
 <div class="stat-card"><span>Marcos aprovados</span><strong><?=$coverage['approved']?></strong><small>revisão concluída</small></div>
 <div class="stat-card success"><span>Incluídos no índice</span><strong><?=$coverage['included']?></strong><small>escopo + fonte + Pᵢ válidos</small></div>
 <div class="stat-card"><span>K base (<?=$baseYear?>)</span><strong><?=number_format($kBase,3,',','.')?></strong><small>soma acumulada</small></div>
 <div class="stat-card accent"><span>α atual</span><strong><?=number_format($alpha,2,',','.')?></strong><small>expoente de normalização</small></div>
</div>
<div class="grid cols-2 gap-lg mt-lg">
 <div class="panel">
  <div class="panel-head"><div><h2>Curva histórica</h2><p>T(t)=100×(K(t)/Kbase)^α · base <?=$baseYear?> = 100</p></div></div>
  <?php if(count($points)>1):?><div class="chart-shell"><svg viewBox="0 0 900 280" role="img" aria-label="Curva histórica Têmporis"><line x1="36" y1="244" x2="864" y2="244" class="axis"/><line x1="36" y1="36" x2="36" y2="244" class="axis"/><polyline points="<?=e($svg)?>" class="line-chart"/></svg></div><?php else:?><div class="empty">A curva aparecerá quando houver pelo menos dois anos com marcos aprovados e pontuados.</div><?php endif;?>
 </div>
 <div class="panel">
  <div class="panel-head"><div><h2>Parâmetros</h2><p>Use esta tela para experimentos; mudanças não alteram os dados-base.</p></div></div>
  <form method="get" class="stack"><div class="field"><label>Expoente α</label><input type="number" step="0.01" min="0.1" max="2" name="alpha" value="<?=e((string)$alpha)?>"></div><div class="field"><label>Ano-base</label><input type="number" name="base_year" value="<?=$baseYear?>"></div><button class="btn primary">Recalcular cenário</button></form>
  <div class="note"><b>Filtro científico:</b> somente marcos com escopo revisado como elegível entram nesta curva.</div><div class="note warning"><b>Atenção:</b> o próprio ano-base será 100 por definição. Testes de sensibilidade devem olhar principalmente anos anteriores e validações fora da amostra.</div>
 </div>
</div>
<div class="panel mt-lg"><div class="panel-head"><div><h2>Série calculada</h2><p>Último valor acumulado de cada ano.</p></div></div><div class="table-wrap"><table><thead><tr><th>Ano</th><th>Último marco</th><th>K(t)</th><th>T(t)</th></tr></thead><tbody><?php if(!$points):?><tr><td colspan="4" class="empty">Sem dados calculáveis.</td></tr><?php endif;?><?php foreach(array_slice($points,-100) as $p):?><tr><td><?=e((string)$p['year'])?></td><td><?=e($p['name'])?></td><td><?=number_format($p['k'],4,',','.')?></td><td><?=$p['t']!==null?number_format($p['t'],3,',','.'):'—'?></td></tr><?php endforeach;?></tbody></table></div></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
