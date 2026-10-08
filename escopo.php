<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Escopo científico'; $active='escopo';
$stats=[
 'sim'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE elegibilidade_temporis='sim' AND escopo_revisado=1")->fetchColumn(),
 'nao'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE elegibilidade_temporis='nao' AND escopo_revisado=1")->fetchColumn(),
 'revisar'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE elegibilidade_temporis='revisar'")->fetchColumn(),
 'auto_sim'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE sugestao_elegibilidade='sim' AND escopo_revisado=0")->fetchColumn(),
 'auto_nao'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE sugestao_elegibilidade='nao' AND escopo_revisado=0")->fetchColumn(),
 'high'=>(int)$pdo->query("SELECT COUNT(*) FROM marcos WHERE scope_confidence='alta' AND escopo_revisado=0")->fetchColumn(),
];
$natures=$pdo->query("SELECT natureza,COUNT(*) n,SUM(escopo_revisado=1) revisados FROM marcos GROUP BY natureza ORDER BY n DESC,natureza")->fetchAll();
$pending=$pdo->query("SELECT id,nome,ano_ref,natureza,sugestao_elegibilidade,motivo_escopo,scope_score,scope_confidence FROM marcos WHERE escopo_revisado=0 ORDER BY FIELD(sugestao_elegibilidade,'revisar','sim','nao'),COALESCE(ano_ref,999999),id LIMIT 30")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="grid cards-5">
 <div class="stat-card success"><span>Elegíveis confirmados</span><strong><?=$stats['sim']?></strong><small>decisão humana</small></div>
 <div class="stat-card warning"><span>Escopo pendente</span><strong><?=$stats['revisar']?></strong><small>ainda sem decisão final</small></div>
 <div class="stat-card"><span>Fora do escopo</span><strong><?=$stats['nao']?></strong><small>mantidos como contexto</small></div>
 <div class="stat-card accent"><span>Sugestão automática SIM</span><strong><?=$stats['auto_sim']?></strong><small>aguarda revisão</small></div>
 <div class="stat-card"><span>Sugestão automática NÃO</span><strong><?=$stats['auto_nao']?></strong><small>aguarda revisão</small></div>
</div>
<div class="grid cols-2 gap-lg mt-lg">
 <div class="panel">
   <div class="panel-head"><div><h2>Regra de escopo</h2><p>O MAT organiza mais dados do que a Têmporis utiliza.</p></div></div>
   <div class="scope-rule"><strong>Entra no índice</strong><p>Evento que representa aquisição ou expansão relevante de capacidade técnica, científica ou de produção de conhecimento humano.</p></div>
   <div class="scope-rule off"><strong>Fica como contexto</strong><p>Eventos sociais, políticos, biológicos ou cosmológicos podem ser preservados no banco sem alterar a Têmporis.</p></div>
   <div class="note warning"><b>Importante:</b> a classificação automática é apenas triagem. Ela nunca transforma um registro em elegível sem revisão humana.</div>
   <button class="btn primary full reclassify-scope">Recalcular sugestões automáticas</button>
   <a class="btn ghost full mt-lg" href="regras_escopo.php">Abrir dicionário de palavras-chave</a>
   <a class="btn ghost full mt-lg" href="escopo_lote.php">Revisar escopo por lote</a>
 </div>
 <div class="panel">
   <div class="panel-head"><div><h2>Distribuição por natureza</h2><p>Ajuda a enxergar o que está entrando no banco.</p></div></div>
   <?php if(!$natures):?><div class="empty">Sem dados.</div><?php endif;?>
   <?php foreach($natures as $n): $pct=$n['n']?round($n['revisados']*100/$n['n']):0;?>
     <div class="quality-row"><div><b><?=e(scope_nature_label($n['natureza']))?></b><small><?=(int)$n['revisados']?>/<?=(int)$n['n']?> revisados</small></div><div class="bar"><span style="width:<?=$pct?>%"></span></div><em><?=$pct?>%</em></div>
   <?php endforeach;?>
 </div>
</div>
<div class="panel mt-lg">
 <div class="panel-head"><div><h2>Próximos para revisar</h2><p>Registros ainda sem decisão humana de escopo.</p></div><a href="revisar.php">Abrir fila completa →</a></div>
 <div class="table-wrap"><table><thead><tr><th>Marco</th><th>Ano</th><th>Natureza sugerida</th><th>Sugestão</th><th>Motivo automático</th><th></th></tr></thead><tbody>
 <?php if(!$pending):?><tr><td colspan="6" class="empty">Todos os registros tiveram o escopo revisado.</td></tr><?php endif;?>
 <?php foreach($pending as $m):?><tr>
 <td><strong><?=e($m['nome'])?></strong></td>
 <td><?=e(format_year_value($m['ano_ref']))?></td>
 <td><?=e(scope_nature_label($m['natureza']))?></td>
 <td><span class="badge <?=eligibility_class($m['sugestao_elegibilidade'])?>"><?=e(eligibility_label($m['sugestao_elegibilidade']))?></span></td>
 <td><small class="subline"><b><?=e(strtoupper($m['scope_confidence']))?></b> · score <?=e((string)$m['scope_score'])?><br><?=e($m['motivo_escopo'])?></small></td>
 <td>
   <div class="scope-actions">
     <button class="btn scope-action approve-scope" data-id="<?=$m['id']?>" data-action="approve" title="Confirmar que o marco está dentro do escopo científico-tecnológico">Aprovar escopo</button>
     <button class="btn danger ghost scope-action" data-id="<?=$m['id']?>" data-action="reject" title="Manter o registro no MAT, mas fora do cálculo da Têmporis">Recusar escopo</button>
     <a class="btn ghost" href="marco.php?id=<?=$m['id']?>">Revisar detalhes</a>
   </div>
 </td>
</tr><?php endforeach;?>
 </tbody></table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
