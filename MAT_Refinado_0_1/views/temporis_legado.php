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
 'homologada'=>'Somente marcos validados'
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
<div class="mat-ed" id="mat-temporis-legado">

  <header class="mat-ed-hero">
    <div class="mat-ed-hero-top">
      <span class="mat-ed-eyebrow"><span class="mat-ed-dot" aria-hidden="true"></span>MAT / ÍNDICE TÊMPORIS / MODELO ORIGINAL</span>
      <span class="mat-ed-year">FORMULAÇÃO ANTERIOR · MMXXVI</span>
    </div>
    <div class="mat-ed-hero-body">
      <div>
        <h1>A Têmporis <em>original.</em></h1>
        <p>A transformação em potência 0,65 agora é calculada a partir dos <strong>mesmos dossiês da curadoria atual</strong>, sem consultar a tabela antiga com 54 registros. A comparação mostra <strong>duas transformações</strong> aplicadas à curadoria atual: a adaptação exibida <strong>não é o índice legado oficial</strong> calculado com as pontuações históricas antigas.</p>
      </div>
      <div class="mat-ed-hero-mark" aria-hidden="true"><span><?=$ultimoPotencia!==null?decimal($ultimoPotencia,2):'—'?></span><small>T(t) · POTÊNCIA 0,65</small></div>
    </div>
<?php if($erroLegado===null): ?>
    <div class="mat-ed-hero-bottom">
      <span>Fonte: curadoria atual</span>
      <span>Banco <code>mat_temporis_curadoria</code></span>
      <span><?=h($nomesAmostra[$amostra])?></span>
    </div>
<?php endif; ?>
  </header>

  <nav class="mat-ed-nav" aria-label="Formulações e ferramentas">
    <a href="<?=mat_url('temporis_reformulado')?>">✦ Reformulada · logaritmo</a>
    <a href="<?=mat_url('temporis_legado')?>" aria-current="page" style="background:#183e32;color:#fff;border-color:#183e32">↗ Original · potência 0,65</a>
    <a href="experimento_temporis.php">◈ Backtesting</a>
    <a href="resultado_temporis_piloto.php">◇ Ensaio didático</a>
  </nav>

  <div class="mat-ed-note" role="note" style="margin-top:18px">
    <h3>MESMO BANCO, DUAS FÓRMULAS · DIFERENÇA ENTRE TESTE E RESULTADO CIENTÍFICO</h3>
    <p>Fonte: <code>mat_temporis_curadoria</code> (`cur_candidatos`, `cur_criterios` e `cur_fontes`). A fórmula de potência é aplicada aos valores I, C e F obtidos das <strong>11 rubricas atuais</strong>. Isso é uma <strong>adaptação experimental</strong>, não a reconstrução das antigas notas de 1 a 5 nem um resultado validado.</p>
  </div>

<?php if($erroLegado!==null): ?>
  <div class="mat-ed-warning" role="alert" style="margin-top:16px"><strong>Não foi possível consultar a curadoria:</strong> <?=h($erroLegado)?></div>
</div>
<?php return; endif; ?>

  <section class="mat-ed-section" aria-label="Contagem da fonte e amostras">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">FONTE E AMOSTRAS · LEITURA SOMENTE-CONSULTA</span><h2>Quem entra em cada <em>conjunto</em></h2></div><span class="mat-ed-source-pill">Sem alterar dados</span></div>
    <div class="mat-ed-cards">
      <div class="mat-ed-card mat-ed-card-lead"><span class="mat-ed-card-label">Candidatos no banco atual</span><div class="mat-ed-card-number"><?=number_format($totalCandidatos,0,',','.')?></div><p>Total independente de pontuação.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Prévia documentada</span><div class="mat-ed-card-number"><?=$comData?></div><p>11 critérios completos, fontes e data utilizável.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Ensaio cronológico</span><div class="mat-ed-card-number"><?=$comAnoExtraido?></div><p>Pode conter anos não confirmados.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Validados elegíveis</span><div class="mat-ed-card-number"><?=$homologados?></div><p>Somente estes compõem o índice definitivo.</p></div>
    </div>
    <form method="get" action="index.php" class="mat-ed-console" style="margin-top:16px">
      <input type="hidden" name="page" value="temporis_legado">
      <div class="mat-ed-console-head"><strong>Console da formulação original</strong><span>O ano-base define T = 100 nesse corte</span></div>
      <div class="mat-ed-console-fields">
        <label><span>Conjunto de dados</span><select name="amostra"><?php foreach($nomesAmostra as $valor=>$nome): ?><option value="<?=h($valor)?>" <?=$amostra===$valor?'selected':''?>><?=h($nome)?></option><?php endforeach; ?></select></label>
        <label><span>Ano-base (T=100 nesse corte)</span><input type="number" name="base" min="-10000" max="2100" value="<?=$anoBase?>"></label>
        <button type="submit" class="mat-ed-run">Recalcular →</button>
      </div>
    </form>
    <div class="mat-ed-strip" style="margin-top:15px">
      <div><small>Marcos na amostra</small><b><?=count($ptsPotencia)?></b></div>
      <div><small>K original · até <?=$anoBase?></small><b><?=decimal((float)$curvaOriginal['k_base'],3)?></b></div>
      <div><small>K original · total</small><b><?=decimal((float)$curvaOriginal['k_total'],3)?></b></div>
      <div><small>T(t) · potência 0,65</small><b><?=$ultimoPotencia!==null?decimal($ultimoPotencia,2):'—'?></b></div>
    </div>
  </section>

  <section class="mat-ed-section" aria-labelledby="legado-eq-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">01 / EQUAÇÃO ORIGINAL · ESPECIFICAÇÃO REPRODUZÍVEL</span><h2 id="legado-eq-titulo">As três <em>expressões</em></h2></div><span class="mat-ed-source-pill">I · C · F</span></div>
    <div class="mat-ed-steps">
      <div class="mat-ed-step"><span class="mat-ed-step-k">Peso do marco · I · C · F</span><h4>Contribuição original</h4><div class="mat-ed-formula">P<sub>i</sub> = I<sub>i</sub><sup>2</sup> × (C<sub>i</sub> / 100) × F<sub>i</sub></div><p>I = 1 + 9 × (soma das 4 notas de impacto / 12). C = 100 × (soma das 4 notas de confiança / 12). F = 1 + 0,5 × (soma das 3 notas fundacionais / 9).</p></div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">Acúmulo · K(t)</span><h4>Soma cronológica</h4><div class="mat-ed-formula">K(t) = Σ P<sub>i</sub>, com t<sub>i</sub> ≤ t</div><p>As datas são ordenadas cronologicamente. As regras de seleção acima são aplicadas antes do somatório.</p></div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">Transformação original · Expoente 0,65</span><h4>Índice em potência</h4><div class="mat-ed-formula">T(t) = 100 × (K(t) / K<sub>base</sub>)<sup>0,65</sup></div><p>K<sub>base</sub> é a soma dos pesos até <?=$anoBase?>. Quando K<sub>base</sub> = 0, a transformação é indefinida e não gera valores artificiais.</p></div>
    </div>
    <div class="mat-ed-note" style="margin-top:14px"><p>O peso original adaptado equivale a 100 vezes o peso derivado das 11 rubricas da reformulada. Esse fator constante <strong>se cancela na razão</strong> K(t)/K(base); não se cancela na transformação logarítmica. Esta equivalência algébrica <strong>não valida</strong> a equivalência científica entre as duas metodologias.</p></div>
  </section>

  <section class="mat-ed-section" aria-labelledby="legado-curva-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">02 / CURVA ORIGINAL · ANO HISTÓRICO · <?=h($nomesAmostra[$amostra])?></span><h2 id="legado-curva-titulo">Potência 0,65 sobre os <em>dossiês atuais</em></h2></div><span class="mat-ed-source-pill">Ano real no eixo</span></div>
    <div class="mat-grafo-bloco"><?=TemporisGraficos::svgCronologico($ptsPotencia,$anoBase,'potencia',false)?></div>
    <p class="mat-ed-table-note" style="margin-top:12px">Posição horizontal = ano histórico real. Faixas de datação não são margens de erro estatísticas. Pontos sem aprovação permanecem identificados como provisórios.</p>
  </section>

  <section class="mat-ed-section" aria-labelledby="legado-compara-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">03 / COMPARAÇÃO · MESMOS CRITÉRIOS · MESMO ANO-BASE</span><h2 id="legado-compara-titulo">Potência original versus <em>logaritmo reformulado</em></h2></div><span class="mat-ed-source-pill">Duas transformações</span></div>
    <div class="mat-ed-cards" style="margin-bottom:15px">
      <div class="mat-ed-card mat-ed-card-lead"><span class="mat-ed-card-label">Potência 0,65</span><div class="mat-ed-card-number"><?=$ultimoPotencia!==null?decimal($ultimoPotencia,2):'—'?></div><p>Pesos originais adaptados.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Logaritmo reformulado</span><div class="mat-ed-card-number"><?=$ultimoLog!==null?decimal($ultimoLog,2):'—'?></div><p>Pesos atuais de 11 rubricas.</p></div>
    </div>
    <div class="mat-grafo-bloco"><?=TemporisGraficos::svgCronologico($ptsReformulada,$anoBase,'log',true)?></div>
    <p class="mat-ed-table-note" style="margin-top:12px">Ambos os métodos partem dos mesmos critérios e anos. O gráfico de comparação normaliza os pesos para a escala reformulada; a curva de potência permanece matematicamente idêntica porque uma constante multiplicativa se cancela em K(t)/K(base).</p>
  </section>

<?php if($serieOriginal): ?>
  <section class="mat-ed-section" aria-labelledby="legado-exemplos-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">04 / EXEMPLOS REPRODUZÍVEIS · VALORES REAIS DO BANCO</span><h2 id="legado-exemplos-titulo">Como os pesos foram <em>calculados</em></h2></div><span class="mat-ed-source-pill">Amostra início + fim</span></div>
    <div class="mat-ed-table-wrap"><table class="mat-ed-table"><thead><tr><th>Identificador</th><th>Ano</th><th>I</th><th>C (%)</th><th>F</th><th>P original adaptado</th><th>K acumulado</th><th>T potência</th></tr></thead><tbody>
    <?php
    $exemplos=array_slice($serieOriginal,0,4);
    if(count($serieOriginal)>8)$exemplos=array_merge($exemplos,array_slice($serieOriginal,-4));
    elseif(count($serieOriginal)>4)$exemplos=$serieOriginal;
    foreach($exemplos as $item): ?>
    <tr><th><?=h((string)($item['id']??''))?></th><td><?=h(yfmt((int)$item['year']))?></td><td><?=decimal((float)$item['I'],2)?></td><td><?=decimal(100*(float)$item['C'],2)?></td><td><?=decimal((float)$item['F'],3)?></td><td><?=decimal((float)$item['score'],3)?></td><td><?=decimal((float)$item['k'],3)?></td><td class="mat-ed-result"><?=$item['potencia']!==null?decimal((float)$item['potencia'],2):'—'?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <p class="mat-ed-table-note" style="margin-top:12px">Tabela mostra uma amostra do começo e do fim da série; os somatórios e gráficos incluem <strong>todos os marcos elegíveis da seleção</strong>. Nenhum candidato é aprovado pela consulta.</p>
  </section>
<?php endif; ?>

  <section class="mat-ed-section" aria-labelledby="legado-estado-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">05 / ESTADO CIENTÍFICO · SEM APROVAÇÃO AUTOMÁTICA</span><h2 id="legado-estado-titulo">Onde esta versão <em>para</em></h2></div><span class="mat-ed-source-pill">Condição do acervo</span></div>
<?php if($homologados===0): ?>
    <div class="mat-ed-note" style="margin-top:0"><h3>A série validada ainda não começou</h3><p>Há <?=number_format($totalCandidatos,0,',','.')?> candidatos no banco de curadoria, mas nenhum deles reúne atualmente os requisitos para integrar a curva oficial. Os cálculos acima usam as notas existentes em caráter de <strong>prévia</strong> ou <strong>ensaio</strong>, conforme o filtro. A ausência de validação é uma condição do acervo, <strong>não um erro da fórmula</strong>.</p></div>
<?php else: ?>
    <div class="mat-ed-emphasis" style="margin-top:0"><span class="mat-ed-emphasis-icon" aria-hidden="true">✓</span><p><strong>Validações identificadas: <?=$homologados?></strong>. Escolha “Somente marcos validados” no filtro para calcular a curva formal, desde que haja peso positivo até o ano-base.</p></div>
<?php endif; ?>
  </section>

  <footer class="mat-ed-footnote">
    <p>Esta página é histórica e experimental: aplica a fórmula de potência às rubricas atuais apenas para comparação matemática. O método adotado é o <strong>MAT Final</strong>, com acumulado bruto K(t).</p>
    <div class="mat-ed-actions">
      <a class="mat-ed-button" href="<?=mat_url('temporis')?>">Abrir o método atual →</a>
      <a class="mat-ed-textlink" href="experimento_temporis.php">Backtesting: medir previsão e erro</a>
      <a class="mat-ed-textlink" href="resultado_temporis_piloto.php">Ensaio didático: entender as fórmulas</a>
    </div>
  </footer>
</div>
