<?php
require_once MAT_ROOT.'/app/TemporisCuradoria.php';
require_once MAT_ROOT.'/app/TemporisGraficos.php';
$anoBase=(int)cfg('temporis_reformulada_ano_base',1920);
$pdoTemp=CuradoriaRodadas::connect();$modelo=null;$erroMetodo=null;
if($pdoTemp){try{$modelo=TemporisCuradoria::consultar($pdoTemp,$anoBase);}catch(Throwable $e){error_log('MAT índice reformulado: '.$e->getMessage());$erroMetodo='Consulta do índice indisponível.';}}
$preview=$modelo['previa']??['series'=>[],'included'=>0,'k_base'=>0,'k_total'=>0,'t_latest'=>null,'datas_provisorias'=>0];
$official=$modelo['homologada']??['series'=>[],'included'=>0,'k_base'=>0,'k_total'=>0,'t_latest'=>null];
$ensaio=$modelo['ensaio']??['series'=>[],'included'=>0,'k_base'=>0,'k_total'=>0,'t_latest'=>null,'datas_provisorias'=>0,'datas_intervalares'=>0];
$modoSerie=(($_GET['serie']??'')==='ensaio')?'ensaio':'previa';
$ativa=$modoSerie==='ensaio'?$ensaio:$preview;
$last=$preview['t_latest'];
$zoom=strtolower((string)($_GET['faixa']??'geral'));
$desdeGrafico=match($zoom){'1500'=>1500,'1800'=>1800,'1900'=>1900,default=>null};
$serie=$ativa['series'];$maxW=0.;$minY=null;$maxY=null;
$usarAcumulado=$ativa['k_base']<=0;
foreach($serie as $point){$maxW=max($maxW,(float)($usarAcumulado?$point['k']:($point['t']??0)));$minY=$minY===null?$point['year']:min($minY,$point['year']);$maxY=$maxY===null?$point['year']:max($maxY,$point['year']);}
$poly=[];
if(count($serie)>1){$range=max(1,(int)$maxY-(int)$minY);$cap=$usarAcumulado?max(1.,$maxW*1.08):max(100.,$maxW*1.06);
 foreach($serie as $point){$x=24+round(752*($point['year']-$minY)/$range,2);$y=222-round(187*(float)($usarAcumulado?$point['k']:($point['t']??0))/$cap,2);$poly[]=$x.','.$y;}}
?>
<header class="page-heading"><div class="section-overline">TÊMPORIS 0.2 / MODELO REFORMULADO</div><h1>Uma medida do <em>tempo tecnológico.</em></h1><p>O índice logarítmico usa as onze notas documentadas das rodadas científicas. Uma coisa é simular hipóteses; outra é aprovar marcos para a série homologada.</p></header>
<div class="mat-final-legacy-note">Atenção: esta é a implementação <strong>anterior</strong>, preservada para comparação. O método adotado no MAT Final usa T(t) = 100 × K(t) / K(1900), sem compressão. <a href="<?=mat_url('temporis_final')?>">Abrir MAT Final →</a></div>
<div class="tempo-version-switch"><a href="<?=mat_url('temporis_reformulado')?>" class="is-active" aria-current="page">✦ Reformulada · logaritmo</a><a href="<?=mat_url('temporis_legado')?>">↗ Original · potência 0,65</a><a href="<?=mat_url('rodadas')?>">◈ Revisar dossiês</a></div>
<?php if(!$modelo): ?><section class="paper-panel"><h2>Sem conexão com o banco de curadoria.</h2><p><?=h($erroMetodo??'Confira o banco mat_temporis_curadoria no phpMyAdmin.')?></p></section>
<?php else: ?>
<div class="tempo-kpi-grid">
 <div class="tempo-kpi tempo-kpi-primary"><span>PRÉVIA · NOTAS PROPOSTAS</span><strong><?=short_num((int)$preview['included'])?></strong><small>11 notas e data pontual revisada ou intervalo documentado</small></div>
 <div class="tempo-kpi"><span>ENSAIO NÃO AUDITADO</span><strong><?=short_num((int)$ensaio['included'])?></strong><small>11 notas completas; aceita ano original extraído, sujeito a erros e duplicatas</small></div>
 <div class="tempo-kpi"><span>HOMOLOGADOS</span><strong><?=short_num((int)$official['included'])?></strong><small>Pesquisador validou evidências e confirmou inclusão</small></div>
 <div class="tempo-kpi"><span>TÊMPORIS PROVISÓRIA</span><strong><?=$last!==null?decimal((float)$last,2):'—'?></strong><small>Escala de referência <?=h((string)$anoBase)?> = 100; não é resultado oficial</small></div>
 <div class="tempo-kpi"><span>TÊMPORIS HOMOLOGADA</span><strong><?=$official['t_latest']!==null?decimal((float)$official['t_latest'],2):'—'?></strong><small>Sem valor até existir base homologada em <?=h((string)$anoBase)?></small></div>
</div>
<div class="tempo-nuance" role="note"><strong>Atenção às notas 1 provisórias:</strong> <?=short_num((int)($preview['marcos_notas_provisorias']??0))?> marco(s) na prévia e <?=short_num((int)($ensaio['marcos_notas_provisorias']??0))?> no ensaio utilizam pelo menos uma nota mínima por insuficiência de evidência. Essas notas afetam o peso experimental, mas nunca autorizam homologação sem revisão. <strong>Como interpretar:</strong> a prévia permite testar o método sobre avaliações preliminares. A série homologada considera somente marcos com as 11 rubricas documentadas, data pontual revisada ou intervalo fundamentado, fontes válidas, pendências resolvidas e homologação humana explícita. Se não houver marcos homologados até <?=h((string)$anoBase)?>, não existe denominador para calcular a série oficial.</div>
<div class="tempo-math-grid">
 <section class="paper-panel tempo-math"><div class="panel-head"><span>01 / ELEVAÇÃO DE NOTAS</span><span>11 CRITÉRIOS</span></div><h2>Do dossiê ao peso</h2><div class="tempo-formula">I = 1 + 9 × Sᵢ / 12</div><div class="tempo-formula">C = S𝚌 / 12</div><div class="tempo-formula">F = 1 + 0,5 × S𝒇 / 9</div><div class="tempo-formula">wᵢ = (I / 10)<sup>2</sup> × C × F</div><p>Impacto: 4 rubricas; confiabilidade: 4; fator fundacional: 3. O peso é experimental, com β = 2. Nas séries exploratórias, a nota 1 pode ser uma convenção provisória sem comprovação de baixo impacto; a série oficial exige evidências individuais auditadas. <strong>Ausência de evidência não é evidência de impacto mínimo.</strong></p></section>
 <section class="paper-panel tempo-math"><div class="panel-head"><span>02 / NORMALIZAÇÃO</span><span>LOGARÍTMICA</span></div><h2>Do peso à série</h2><div class="tempo-formula">K(t) = Σ wᵢ, para tᵢ ≤ t</div><div class="tempo-formula tempo-main-eq">T(t) = 100 × ln(1 + K(t)) / ln(1 + K(t₀))</div><p>Usamos <?=h((string)$anoBase)?> como referência metodológica nesta versão, configurável por <code>temporis_reformulada_ano_base</code>. O logaritmo comprime a escala: <strong>não comprova aceleração histórica</strong>.</p><a href="<?=mat_url('temporis_legado')?>" class="tempo-text-link">Comparar com a formulação original ↗</a></section>
</div>
<section class="paper-panel tempo-chart-panel"><div class="panel-head"><span>CURVAS DA CURADORIA</span><span><?=h($modoSerie==='ensaio'?'LABORATÓRIO · SEM VALIDAÇÃO':'PRÉVIA · DATAS DOCUMENTADAS')?></span></div>
<div class="tempo-series-switch"><a class="<?=$modoSerie==='previa'?'active':''?>" href="<?=mat_url('temporis_reformulado')?>">Curva provisória revisada (<?=short_num($preview['included'])?>)</a><a class="<?=$modoSerie==='ensaio'?'active':''?>" href="<?=mat_url('temporis_reformulado',['serie'=>'ensaio'])?>">Ensaio com datas extraídas (<?=short_num($ensaio['included'])?>)</a></div>
<div class="tempo-chart-heading"><div><h2><?=h($modoSerie==='ensaio'?'Ensaio matemático com dados não auditados':'Trajetória provisória com datas documentadas')?></h2>
<p><?=h($modoSerie==='ensaio'?'As 11 notas são provisórias. Anos extraídos podem ser aproximações erradas e os dossiês podem conter duplicatas. Use apenas para verificar o comportamento da fórmula, nunca para concluir aceleração real.':'Considera somente dossiês com onze notas e data pontual ou intervalo documentado. Notas não homologadas continuam provisórias.')?></p>
</div><span><?=short_num((int)$modelo['sem_data'])?> dossiês completos sem data revisada · <?=short_num((int)($ativa['datas_intervalares']??0))?> com intervalos · <?=short_num((int)($ativa['datas_provisorias']??0))?> com ano extraído</span></div>
<link rel="stylesheet" href="assets/temporis-graficos.css?v=2">
<div class="mat-grafo-etiqueta">Visualização cronológica · não altera notas nem aprovações</div>
<div class="tempo-series-switch" aria-label="Recorte horizontal da cronologia">
 <?php foreach(['geral'=>'Toda a cronologia','1500'=>'Desde 1500','1800'=>'Desde 1800','1900'=>'Desde 1900'] as $faixa=>$rotulo): ?>
 <a class="<?=$zoom===$faixa?'active':''?>" href="<?=mat_url('temporis_reformulado',['serie'=>$modoSerie,'faixa'=>$faixa])?>"><?=h($rotulo)?></a>
 <?php endforeach; ?>
</div>
<div class="mat-grafo-bloco">
 <?=TemporisGraficos::svgCronologico($serie,$anoBase,'log',true,$desdeGrafico)?>
</div>
<p class="mat-grafo-nota">Cada ponto está localizado pelo seu <strong>ano real</strong>, e não pela posição na lista. A barra horizontal indica um intervalo de datação documentado, não uma margem de erro estatística. O recorte temporal altera somente a visualização; o cálculo de K e o denominador do índice permanecem baseados em toda a amostra.</p>
<?php
$confirmadosGrafico=array_values(array_filter($serie,static fn($p)=>in_array((string)($p['estado_revisao_humana']??''),['APROVADO','HOMOLOGADO'],true)));
?>
<h3>Decisões humanas na linha do tempo</h3>
<p class="mat-grafo-nota">Esta régua localiza os marcos aprovados ou homologados em seus anos validados, <strong>mesmo que ainda não tenham pesos suficientes</strong> para a curva. Uma aprovação não é convertida automaticamente em homologação.</p>
<div class="mat-grafo-bloco"><?=TemporisGraficos::svgDecisoes($modelo['decisoes_humanas']??[])?></div>
<?php if(!empty($modelo['decisoes_humanas'])): ?>
<div class="mat-grafo-table"><table><thead><tr><th>Marco</th><th>Ano validado</th><th>Decisão humana</th><th>11 rubricas completas</th></tr></thead><tbody>
<?php foreach($modelo['decisoes_humanas'] as $ap):?><tr>
<td><?=h((string)$ap['id'])?> · <?=h((string)$ap['title'])?></td>
<td><?=$ap['year']===null?'Sem data confirmada':h(yfmt((int)$ap['year']))?></td>
<td><?=h((string)$ap['estado'])?></td><td><?=$ap['notas_completas']?'Sim':'Não'?></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php endif;?>
<div class="mat-grafo-cards">
 <div class="mat-grafo-card"><span>Marcos na curva selecionada</span><strong><?=count($serie)?></strong></div>
 <div class="mat-grafo-card"><span>Aprovados/homologados presentes na curva</span><strong><?=count($confirmadosGrafico)?></strong></div>
 <div class="mat-grafo-card"><span>Base K(<?=h((string)$anoBase)?>)</span><strong><?=decimal((float)$ativa['k_base'],3)?></strong></div>
</div>
<?php if($confirmadosGrafico): ?>
<div class="mat-grafo-table"><table><thead><tr><th>ID</th><th>Marco com decisão humana</th><th>Ano de cálculo</th><th>Estado</th></tr></thead><tbody>
<?php foreach($confirmadosGrafico as $cp): ?><tr><td><?=h((string)$cp['id'])?></td><td><?=h((string)$cp['title'])?></td><td><?=h(yfmt((int)$cp['year']))?></td><td><?=h((string)$cp['estado_revisao_humana'])?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php else: ?><p class="mat-grafo-nota">Ainda não há marcos com decisão humana de aprovação dentro da curva selecionada. Pontos provisórios são exibidos apenas como ensaio.</p><?php endif; ?>
</section>
<section class="paper-panel tempo-lab">
 <div class="panel-head"><span>03 / ENSAIOS REPRODUZÍVEIS</span><span>EM MEMÓRIA · NÃO ALTERA NOTAS OU DADOS</span></div>
 <h2>Teste rápido da fórmula Têmporis</h2>
 <p>Com <strong><?=short_num((int)$ensaio['included'])?> candidatos</strong> de 11 notas, a simulação utiliza a melhor data disponível. <strong><?=short_num((int)$ensaio['datas_provisorias'])?> usam anos extraídos não revisados</strong> e <?=short_num((int)$ensaio['datas_intervalares'])?> usam intervalos registrados. Recalcular o modelo não constitui aprovação.</p>
 <p><a class="btn btn-dark" href="exportar_ensaio_temporis.php?serie=ensaio">Exportar candidatos e valores do ensaio em CSV ↗</a> <a class="btn" href="exportar_ensaio_temporis.php?serie=previa">Exportar a prévia com datas documentadas ↗</a></p>
 <h3>Sensibilidade temporal — β = 2</h3>
 <p>Quando um marco tem intervalo, a mesma contribuição é posicionada uma única vez no início, meio ou fim da faixa. A tabela mede o efeito no índice; não é intervalo de confiança estatístico.</p>
 <div class="mat-lab-table-wrap"><table class="mat-lab-table"><thead><tr><th>Cenário</th><?php foreach($modelo['sensibilidade']['anos'] as $yr):?><th><?=h((string)$yr)?></th><?php endforeach;?></tr></thead><tbody>
 <?php foreach($modelo['sensibilidade']['cenarios'] as $cen=>$values):?><tr><th><?=h(match($cen){'inicio'=>'Início','fim'=>'Fim',default=>'Ponto médio'})?></th><?php foreach($modelo['sensibilidade']['anos'] as $yr):?><td><?=$values['valores'][$yr]===null?'—':decimal((float)$values['valores'][$yr],2)?></td><?php endforeach;?></tr><?php endforeach;?>
 </tbody></table></div>
 <h3>Sensibilidade de β — ano representativo</h3>
 <p>O método atual usa β = 2; β = 1 e 3 são somente comparações matemáticas, sem mudar a configuração oficial.</p>
 <div class="mat-lab-table-wrap"><table class="mat-lab-table"><thead><tr><th>β</th><th>K até <?=h((string)$anoBase)?></th><th>K total</th><th>T no último ponto</th></tr></thead><tbody><?php foreach($modelo['sensibilidade_beta'] as $beta=>$resultado):?><tr><th><?=h((string)$beta)?></th><td><?=decimal((float)$resultado['k_base'],3)?></td><td><?=decimal((float)$resultado['k_total'],3)?></td><td><?=$resultado['t_ultimo']===null?'Indefinido':decimal((float)$resultado['t_ultimo'],2)?></td></tr><?php endforeach;?></tbody></table></div>
 <h3>Sensibilidade à retirada de um marco por vez</h3>
 <p>Este ensaio mostra como o índice final muda se cada candidato for excluído da série, sem alterar os dados. É útil para identificar dependência de eventos específicos, mas não valida a verdade histórica das notas.</p>
 <?php $loo=$modelo['sensibilidade_exclusao']??[]; if(!empty($loo['calculavel'])): ?>
 <p><strong>Maior variação absoluta:</strong> <?=$loo['maior_variacao']===null?'Indefinida':decimal((float)$loo['maior_variacao'],3)?> ponto(s) · <strong><?=h((string)($loo['indefinidos']??0))?></strong> retirada(s) inviabilizam a normalização no ano-base.</p>
 <div class="mat-lab-table-wrap"><table class="mat-lab-table"><thead><tr><th>Marco</th><th>Ano</th><th>Sem o marco</th><th>Δ do índice</th></tr></thead><tbody>
 <?php foreach($loo['marcos'] as $item): ?><tr><th><?=h($item['id'])?></th><td><?=h((string)$item['ano'])?></td><td><?=$item['indice_sem_marco']===null?'Indefinido':decimal((float)$item['indice_sem_marco'],3)?></td><td><?=$item['variacao']===null?'Indefinido':decimal((float)$item['variacao'],3)?></td></tr><?php endforeach; ?></tbody></table></div>
 <?php else: ?><p>Dados insuficientes ou ausência de base histórica para calcular a influência individual. Não substituímos valores indefinidos.</p><?php endif; ?>
 <p><strong>Limitação:</strong> se K(<?=h((string)$anoBase)?>) = 0, T(t) é indefinido; não substituímos esse denominador por um valor artificial. O ensaio pode conter anos extraídos errados, processos de longa duração e duplicatas. Os resultados são para depuração e preparação da defesa, não para afirmar que a aceleração histórica foi demonstrada.</p>
</section>
<div class="tempo-end-grid"><section class="paper-panel"><div class="panel-head"><span>O QUE FALTA</span><span>AUDITORIA HUMANA</span></div><h2>Da nota proposta à homologação</h2><p>O pesquisador precisa confrontar as fontes, revisar cada critério, corrigir datas, resolver duplicatas e pendências, aprovar o dossiê e só então homologá-lo.</p><a class="btn btn-dark" href="<?=mat_url('rodadas')?>">Abrir a fila de revisão ↗</a></section>
<section class="paper-panel"><div class="panel-head"><span>INTEGRIDADE METODOLÓGICA</span><span>SEM CONVERSÃO DE ESCALAS</span></div><h2>Duas séries, duas bases</h2><p>A Têmporis original permanece com o motor de pontuação legado e a transformação de potência 0,65; aqui só entram as 11 rubricas da curadoria. Não há equivalência automática entre os valores.</p><p><strong>Próximos testes:</strong> concordância dos avaliadores independentes, variação de pesos, comparação linear/exponencial/logística, resíduos e erro preditivo.</p></section></div>
<?php endif; ?>
