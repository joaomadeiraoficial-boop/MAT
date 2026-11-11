<?php
/** Têmporis original: fórmula de potência aplicada explicitamente à curadoria atual. */
require_once MAT_ROOT.'/app/TemporisCuradoria.php';
require_once MAT_ROOT.'/app/TemporisGraficos.php';
require_once MAT_ROOT.'/app/TemporisPotenciaAtual.php';

$anoPadrao=(int)cfg('temporis_reformulada_ano_base',1920);
$anoPedido=filter_var($_GET['base']??$anoPadrao,FILTER_VALIDATE_INT);
$anoBase=($anoPedido===false || $anoPedido < -10000 || $anoPedido > 2100) ? $anoPadrao : (int)$anoPedido;
$amostra=(string)($_GET['amostra']??'previa');
if(!in_array($amostra,['previa','ensaio','homologada'],true))$amostra='previa';
$nomesAmostra=[
 'previa'=>'Prévia com data validada e 11 critérios completos',
 'ensaio'=>'Ensaio com data extraída (não validada)',
 'homologada'=>'Somente marcos homologados'
];
$erroLegado=null;$dadosLegado=null;$totalCandidatos=0;
$curvaOriginal=['series'=>[],'included'=>0,'k_base'=>0.,'k_total'=>0.,'normalizavel'=>false];
$curvaReformulada=$curvaOriginal;
$selecionados=[];$ptsPotencia=[];$ptsReformulada=[];
try {
    $pdoCuradoria=CuradoriaRodadas::connect();
    if(!$pdoCuradoria)throw new RuntimeException('Conexão com mat_temporis_curadoria indisponível. Confira config.local.php e o MySQL.');
    $totalCandidatos=(int)$pdoCuradoria->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
    $dadosLegado=TemporisCuradoria::consultar($pdoCuradoria,$anoBase);
    $selecionados=$dadosLegado[$amostra]['series']??[];
    $ptsReformulada=$selecionados;
    $ptsPotencia=TemporisPotenciaAtual::preparar($selecionados);
    $curvaOriginal=TemporisGraficos::curvas($ptsPotencia,$anoBase);
    $curvaReformulada=TemporisGraficos::curvas($ptsReformulada,$anoBase);
} catch(Throwable $e) {
    error_log('MAT Têmporis original / curadoria: '.$e->getMessage());
    $erroLegado=$e->getMessage();
}
$comData=count($dadosLegado['previa']['series']??[]);
$comAnoExtraido=count($dadosLegado['ensaio']['series']??[]);
$homologados=count($dadosLegado['homologada']['series']??[]);
$ultimoPotencia=(!empty($curvaOriginal['normalizavel']) && $curvaOriginal['series']) ? (float)end($curvaOriginal['series'])['potencia'] : null;
$ultimoLog=(!empty($curvaReformulada['normalizavel']) && $curvaReformulada['series']) ? (float)end($curvaReformulada['series'])['log'] : null;
$serieOriginal=$curvaOriginal['series'];
?>
<link rel="stylesheet" href="assets/temporis-graficos.css?v=2">
<link rel="stylesheet" href="assets/temporis-integracao.css?v=1">
<header class="page-heading">
 <div class="section-overline">MAT / ÍNDICE TÊMPORIS / MODELO ORIGINAL</div>
 <h1>A Têmporis <em>original.</em></h1>
 <p>A transformação em potência 0,65 agora é calculada a partir dos <strong>mesmos dossiês da curadoria atual</strong>, sem consultar a tabela antiga com 54 registros. A comparação mostra <strong>duas transformações</strong> aplicadas à curadoria atual: a adaptação exibida <strong>não é o índice legado oficial</strong> calculado com as pontuações históricas antigas.</p>
</header>
<div class="tempo-version-switch">
 <a href="<?=mat_url('temporis_reformulado')?>">✦ Reformulada · logaritmo</a>
 <a href="<?=mat_url('temporis_legado')?>" class="is-active" aria-current="page">↗ Original · potência 0,65</a>
 <a href="experimento_temporis.php">◈ Backtesting</a>
 <a href="resultado_temporis_piloto.php">◇ Ensaio didático</a>
</div>
<div class="lab-alert"><span class="lab-symbol">※</span><div>
 <strong>MESMO BANCO, DUAS FÓRMULAS · DIFERENÇA ENTRE TESTE E RESULTADO CIENTÍFICO</strong>
 <p>Fonte: <code>mat_temporis_curadoria</code> (`cur_candidatos`, `cur_criterios` e `cur_fontes`). A fórmula de potência é aplicada aos valores I, C e F obtidos das <strong>11 rubricas atuais</strong>. Isso é uma <strong>adaptação experimental</strong>, não a reconstrução das antigas notas de 1 a 5 nem um resultado homologado.</p>
</div></div>
<?php if($erroLegado!==null): ?>
 <div class="mat-grafo-crit" role="alert"><strong>Não foi possível consultar a curadoria:</strong> <?=h($erroLegado)?></div>
<?php else: ?>
<div class="exp-boxes">
 <div class="exp-box">Candidatos no banco atual<strong><?=number_format($totalCandidatos,0,',','.')?></strong><small>Total independente de pontuação</small></div>
 <div class="exp-box">Prévia documentada<strong><?=$comData?></strong><small>11 critérios completos, fontes e data utilizável</small></div>
 <div class="exp-box">Ensaio cronológico<strong><?=$comAnoExtraido?></strong><small>Pode conter anos não confirmados</small></div>
 <div class="exp-box">Homologados elegíveis<strong><?=$homologados?></strong><small>Somente estes compõem o índice definitivo</small></div>
</div>
<form method="get" class="exp-form" style="margin:20px 0 18px">
 <input type="hidden" name="page" value="temporis_legado">
 <label>Conjunto de dados
 <select name="amostra">
 <?php foreach($nomesAmostra as $valor=>$nome): ?><option value="<?=h($valor)?>" <?=$amostra===$valor?'selected':''?>><?=h($nome)?></option><?php endforeach; ?>
 </select></label>
 <label>Ano-base (T=100 nesse corte)
 <input type="number" name="base" min="-10000" max="2100" value="<?=$anoBase?>">
 </label>
 <button type="submit" class="btn btn-dark">Recalcular</button>
</form>
<div class="lab-tiles">
 <div class="lab-tile"><span>MARCOS NA AMOSTRA</span><strong><?=count($ptsPotencia)?></strong><small><?=h($nomesAmostra[$amostra])?></small></div>
 <div class="lab-tile"><span>K ORIGINAL · ATÉ <?=$anoBase?></span><strong><?=decimal((float)$curvaOriginal['k_base'],3)?></strong><small>Soma dos pesos I² × C × F até o ano-base</small></div>
 <div class="lab-tile"><span>K ORIGINAL · TOTAL</span><strong><?=decimal((float)$curvaOriginal['k_total'],3)?></strong><small>Somatório dos pesos disponíveis</small></div>
 <div class="lab-tile"><span>T(t) · POTÊNCIA 0,65</span><strong><?=$ultimoPotencia!==null?decimal($ultimoPotencia,2):'—'?></strong><small><?=$ultimoPotencia!==null?'Valor experimental no último marco':'Sem base positiva: índice indefinido'?></small></div>
</div>
<section class="paper-panel" style="margin-top:25px">
 <div class="panel-head"><span>01 / EQUAÇÃO ORIGINAL</span><span>ESPECIFICAÇÃO REPRODUZÍVEL</span></div>
 <div class="formula-grid">
  <div class="formula-sheet"><div class="panel-head"><span>PESO DO MARCO</span><span>I · C · F</span></div><div class="equation">P<sub>i</sub> = I<sub>i</sub><sup>2</sup> × (C<sub>i</sub> / 100) × F<sub>i</sub></div><p>I = 1 + 9 × (soma das 4 notas de impacto / 12). C = 100 × (soma das 4 notas de confiança / 12). F = 1 + 0,5 × (soma das 3 notas fundacionais / 9).</p></div>
  <div class="formula-sheet"><div class="panel-head"><span>ACÚMULO</span><span>K(t)</span></div><div class="equation">K(t) = Σ P<sub>i</sub>, com t<sub>i</sub> ≤ t</div><p>As datas são ordenadas cronologicamente. As regras de seleção acima são aplicadas antes do somatório.</p></div>
  <div class="formula-sheet formula-sheet-wide"><div class="panel-head"><span>TRANSFORMAÇÃO ORIGINAL</span><span>EXPONENTE 0,65</span></div><div class="equation">T(t) = 100 × (K(t) / K<sub>base</sub>)<sup>0,65</sup></div><p>K<sub>base</sub> é a soma dos pesos até <?=$anoBase?>. Quando K<sub>base</sub> = 0, a transformação é indefinida e não gera valores artificiais.</p></div>
 </div>
 <p class="mat-grafo-nota">O peso original adaptado equivale a 100 vezes o peso derivado das 11 rubricas da reformulada. Esse fator constante <strong>se cancela na razão</strong> K(t)/K(base); não se cancela na transformação logarítmica. Esta equivalência algébrica <strong>não valida</strong> a equivalência científica entre as duas metodologias.</p>
</section>
<section class="paper-panel" style="margin-top:25px">
 <div class="panel-head"><span>02 / CURVA ORIGINAL · ANO HISTÓRICO</span><span><?=h($nomesAmostra[$amostra])?></span></div>
 <h2>Potência 0,65 sobre os dossiês atuais</h2>
 <div class="mat-grafo-bloco"><?=TemporisGraficos::svgCronologico($ptsPotencia,$anoBase,'potencia',false)?></div>
 <p class="mat-grafo-nota">Posição horizontal = ano histórico real. Faixas de datação não são margens de erro estatísticas. Pontos sem aprovação permanecem identificados como provisórios.</p>
</section>
<section class="paper-panel" style="margin-top:25px">
 <div class="panel-head"><span>03 / COMPARAÇÃO</span><span>MESMOS CRITÉRIOS · MESMO ANO-BASE</span></div>
 <h2>Potência original versus logaritmo reformulado</h2>
 <div class="exp-boxes">
 <div class="exp-box">Potência 0,65<strong><?=$ultimoPotencia!==null?decimal($ultimoPotencia,2):'—'?></strong><small>Pesos originais adaptados</small></div>
 <div class="exp-box">Logaritmo reformulado<strong><?=$ultimoLog!==null?decimal($ultimoLog,2):'—'?></strong><small>Pesos atuais de 11 rubricas</small></div>
 </div>
 <div class="mat-grafo-bloco"><?=TemporisGraficos::svgCronologico($ptsReformulada,$anoBase,'log',true)?></div>
 <p class="mat-grafo-nota">Ambos os métodos partem dos mesmos critérios e anos. O gráfico de comparação normaliza os pesos para a escala reformulada; a curva de potência permanece matematicamente idêntica porque uma constante multiplicativa se cancela em K(t)/K(base).</p>
</section>
<?php if($serieOriginal): ?>
<section class="paper-panel" style="margin-top:25px">
 <div class="panel-head"><span>04 / EXEMPLOS REPRODUZÍVEIS</span><span>VALORES REAIS DO BANCO</span></div>
 <h2>Como os pesos foram calculados</h2>
 <div class="mat-lab-table-wrap"><table class="mat-lab-table"><thead><tr><th>Identificador</th><th>Ano</th><th>I</th><th>C (%)</th><th>F</th><th>P original adaptado</th><th>K acumulado</th><th>T potência</th></tr></thead><tbody>
 <?php
 $exemplos=array_slice($serieOriginal,0,4);
 if(count($serieOriginal)>8)$exemplos=array_merge($exemplos,array_slice($serieOriginal,-4));
 elseif(count($serieOriginal)>4)$exemplos=$serieOriginal;
 foreach($exemplos as $item): ?>
 <tr><td><?=h((string)($item['id']??''))?></td><td><?=h(yfmt((int)$item['year']))?></td><td><?=decimal((float)$item['I'],2)?></td><td><?=decimal(100*(float)$item['C'],2)?></td><td><?=decimal((float)$item['F'],3)?></td><td><?=decimal((float)$item['score'],3)?></td><td><?=decimal((float)$item['k'],3)?></td><td><?=$item['potencia']!==null?decimal((float)$item['potencia'],2):'—'?></td></tr>
 <?php endforeach; ?>
 </tbody></table></div>
 <p class="mat-grafo-nota">Tabela mostra uma amostra do começo e do fim da série; os somatórios e gráficos incluem <strong>todos os marcos elegíveis da seleção</strong>. Nenhum candidato é aprovado pela consulta.</p>
</section>
<?php endif; ?>
<section class="paper-panel" style="margin-top:25px">
 <div class="panel-head"><span>05 / ESTADO CIENTÍFICO</span><span>SEM APROVAÇÃO AUTOMÁTICA</span></div>
 <?php if($homologados===0): ?>
 <h2>A série homologada ainda não começou</h2>
 <p>Há <?=$totalCandidatos?> candidatos no banco de curadoria, mas nenhum deles reúne atualmente os requisitos para integrar a curva oficial. Os cálculos acima usam as notas existentes em caráter de <strong>prévia</strong> ou <strong>ensaio</strong>, conforme o filtro. A ausência de homologação é uma condição do acervo, <strong>não um erro da fórmula</strong>.</p>
 <?php else: ?>
 <h2>Homologações identificadas: <?=$homologados?></h2>
 <p>Escolha “Somente marcos homologados” no filtro para calcular a curva formal, desde que haja peso positivo até o ano-base.</p>
 <?php endif; ?>
 <p><a class="text-link" href="experimento_temporis.php">Backtesting: medir previsão e erro ↗</a>　<a class="text-link" href="resultado_temporis_piloto.php">Ensaio didático: entender as fórmulas ↗</a></p>
</section>
<?php endif; ?>
