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
<div class="mat-ed" id="mat-temporis-reformulado">
<link rel="stylesheet" href="assets/temporis-graficos.css?v=2">

  <header class="mat-ed-hero">
    <div class="mat-ed-hero-top">
      <span class="mat-ed-eyebrow"><span class="mat-ed-dot" aria-hidden="true"></span>TÊMPORIS 0.2 / MODELO REFORMULADO</span>
      <span class="mat-ed-year">FORMULAÇÃO ANTERIOR · MMXXVI</span>
    </div>
    <div class="mat-ed-hero-body">
      <div>
        <h1>Uma medida do <em>tempo tecnológico.</em></h1>
        <p>O índice logarítmico usa as onze notas documentadas das rodadas científicas. Uma coisa é simular hipóteses; outra é aprovar marcos para a série validada.</p>
      </div>
      <div class="mat-ed-hero-mark" aria-hidden="true"><span><?=$last!==null?decimal((float)$last,2):'—'?></span><small>TÊMPORIS PROVISÓRIA</small></div>
    </div>
<?php if($modelo): ?>
    <div class="mat-ed-hero-bottom">
      <span>Fonte: curadoria atual</span>
      <span>Banco <code>mat_temporis_curadoria</code></span>
      <span>Referência <?=h((string)$anoBase)?> = 100</span>
    </div>
<?php endif; ?>
  </header>

  <div class="mat-ed-note" role="note" style="margin-top:18px">
    <p><strong>Atenção:</strong> esta é a implementação <strong>anterior</strong>, preservada para comparação. O método adotado no MAT Final usa K(t) = soma dos pesos brutos, sem base obrigatória. A escala 1900 = 100 é opcional. <a href="<?=mat_url('temporis_final')?>">Abrir MAT Final →</a></p>
  </div>

  <nav class="mat-ed-nav" aria-label="Formulações e ferramentas">
    <a href="<?=mat_url('temporis_reformulado')?>" aria-current="page" style="background:#183e32;color:#fff;border-color:#183e32">✦ Reformulada · logaritmo</a>
    <a href="<?=mat_url('temporis_legado')?>">↗ Original · potência 0,65</a>
    <a href="<?=mat_url('rodadas')?>">◈ Revisar dossiês</a>
  </nav>

<?php if(!$modelo): ?>
  <div class="mat-ed-warning" role="alert" style="margin-top:18px"><strong>Sem conexão com o banco de curadoria.</strong> <?=h($erroMetodo??'Confira o banco mat_temporis_curadoria no phpMyAdmin.')?></div>
</div>
<?php return; endif; ?>

  <section class="mat-ed-section" aria-label="Resumo das séries">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">SÉRIES DA CURADORIA · LEITURA SOMENTE-CONSULTA</span><h2>Três conjuntos, <em>um método</em></h2></div><span class="mat-ed-source-pill">Sem alterar dados</span></div>
    <div class="mat-ed-cards">
      <div class="mat-ed-card mat-ed-card-lead"><span class="mat-ed-card-label">Prévia · notas propostas</span><div class="mat-ed-card-number"><?=short_num((int)$preview['included'])?></div><p>11 notas e data pontual revisada ou intervalo documentado.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Ensaio não auditado</span><div class="mat-ed-card-number"><?=short_num((int)$ensaio['included'])?></div><p>11 notas completas; aceita ano original extraído, sujeito a erros e duplicatas.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Validados</span><div class="mat-ed-card-number"><?=short_num((int)$official['included'])?></div><p>Pesquisador validou evidências e confirmou inclusão.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Têmporis provisória</span><div class="mat-ed-card-number"><?=$last!==null?decimal((float)$last,2):'—'?></div><p>Escala de referência <?=h((string)$anoBase)?> = 100; não é resultado oficial.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Têmporis validada</span><div class="mat-ed-card-number"><?=$official['t_latest']!==null?decimal((float)$official['t_latest'],2):'—'?></div><p>Sem valor até existir base validada em <?=h((string)$anoBase)?>.</p></div>
    </div>
    <div class="mat-ed-note" role="note" style="margin-top:15px">
      <p><strong>Atenção às notas 1 provisórias:</strong> <?=short_num((int)($preview['marcos_notas_provisorias']??0))?> marco(s) na prévia e <?=short_num((int)($ensaio['marcos_notas_provisorias']??0))?> no ensaio utilizam pelo menos uma nota mínima por insuficiência de evidência. Essas notas afetam o peso experimental, mas nunca autorizam validação sem revisão. <strong>Como interpretar:</strong> a prévia permite testar o método sobre avaliações preliminares. A série validada considera somente marcos com as 11 rubricas documentadas, data pontual revisada ou intervalo fundamentado, fontes válidas, pendências resolvidas e validação humana explícita. Se não houver marcos validados até <?=h((string)$anoBase)?>, não existe denominador para calcular a série oficial.</p>
    </div>
  </section>

  <section class="mat-ed-section" aria-labelledby="reform-eq-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">01–02 / DO DOSSIÊ AO PESO · DO PESO À SÉRIE</span><h2 id="reform-eq-titulo">As expressões desta <em>versão</em></h2></div><span class="mat-ed-source-pill">11 critérios · β = 2</span></div>
    <div class="mat-ed-steps">
      <div class="mat-ed-step"><span class="mat-ed-step-k">01 · Elevação de notas</span><h4>Do dossiê ao peso</h4>
        <div class="mat-ed-formula">I = 1 + 9 × Sᵢ / 12<br>C = S𝚌 / 12<br>F = 1 + 0,5 × S𝒇 / 9<br>wᵢ = (I / 10)<sup>2</sup> × C × F</div>
        <p>Impacto: 4 rubricas; confiabilidade: 4; fator fundacional: 3. O peso é experimental, com β = 2. Nas séries exploratórias, a nota 1 pode ser uma convenção provisória sem comprovação de baixo impacto; a série oficial exige evidências individuais auditadas. <strong>Ausência de evidência não é evidência de impacto mínimo.</strong></p>
      </div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">02 · Normalização logarítmica</span><h4>Do peso à série</h4>
        <div class="mat-ed-formula">K(t) = Σ wᵢ, para tᵢ ≤ t<br><strong>T(t) = 100 × ln(1 + K(t)) / ln(1 + K(t₀))</strong></div>
        <p>Usamos <?=h((string)$anoBase)?> como referência metodológica nesta versão, configurável por <code>temporis_reformulada_ano_base</code>. O logaritmo comprime a escala: <strong>não comprova aceleração histórica</strong>. <a href="<?=mat_url('temporis_legado')?>">Comparar com a formulação original ↗</a></p>
      </div>
    </div>
  </section>

  <section class="mat-ed-section" aria-labelledby="reform-curva-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">CURVAS DA CURADORIA · <?=h($modoSerie==='ensaio'?'LABORATÓRIO · SEM VALIDAÇÃO':'PRÉVIA · DATAS DOCUMENTADAS')?></span><h2 id="reform-curva-titulo">A trajetória <em>desta seleção</em></h2></div><span class="mat-ed-source-pill">Ano real no eixo</span></div>
    <div class="mat-ed-nav" style="padding-top:0">
      <a class="<?=$modoSerie==='previa'?'':''?>" href="<?=mat_url('temporis_reformulado')?>">Curva provisória revisada (<?=short_num($preview['included'])?>)</a>
      <a href="<?=mat_url('temporis_reformulado',['serie'=>'ensaio'])?>">Ensaio com datas extraídas (<?=short_num($ensaio['included'])?>)</a>
    </div>
    <div class="mat-ed-details" style="margin-top:12px">
      <div class="mat-ed-details-body" style="border-top:none;padding:0">
        <h3 style="margin-top:0"><?=h($modoSerie==='ensaio'?'Ensaio matemático com dados não auditados':'Trajetória provisória com datas documentadas')?></h3>
        <p><?=h($modoSerie==='ensaio'?'As 11 notas são provisórias. Anos extraídos podem ser aproximações erradas e os dossiês podem conter duplicatas. Use apenas para verificar o comportamento da fórmula, nunca para concluir aceleração real.':'Considera somente dossiês com onze notas e data pontual ou intervalo documentado. Notas não validadas continuam provisórias.')?></p>
        <p><?=short_num((int)$modelo['sem_data'])?> dossiês completos sem data revisada · <?=short_num((int)($ativa['datas_intervalares']??0))?> com intervalos · <?=short_num((int)($ativa['datas_provisorias']??0))?> com ano extraído</p>
      </div>
    </div>
    <div class="mat-ed-nav" style="padding-top:0;margin-top:6px" aria-label="Recorte horizontal da cronologia">
      <?php foreach(['geral'=>'Toda a cronologia','1500'=>'Desde 1500','1800'=>'Desde 1800','1900'=>'Desde 1900'] as $faixa=>$rotulo): ?>
      <a href="<?=mat_url('temporis_reformulado',['serie'=>$modoSerie,'faixa'=>$faixa])?>" <?=$zoom===$faixa?'style="background:#183e32;color:#fff;border-color:#183e32"':''?>><?=h($rotulo)?></a>
      <?php endforeach; ?>
    </div>
    <div class="mat-grafo-etiqueta">Visualização cronológica · não altera notas nem aprovações</div>
    <div class="mat-grafo-bloco">
      <?=TemporisGraficos::svgCronologico($serie,$anoBase,'log',true,$desdeGrafico)?>
    </div>
    <p class="mat-ed-table-note" style="margin-top:12px">Cada ponto está localizado pelo seu <strong>ano real</strong>, e não pela posição na lista. A barra horizontal indica um intervalo de datação documentado, não uma margem de erro estatística. O recorte temporal altera somente a visualização; o cálculo de K e o denominador do índice permanecem baseados em toda a amostra.</p>
<?php
$confirmadosGrafico=array_values(array_filter($serie,static fn($p)=>in_array((string)($p['estado_revisao_humana']??''),['APROVADO','HOMOLOGADO'],true)));
?>
    <div class="mat-ed-section-heading" style="margin-top:22px"><div><span class="mat-ed-overline">DECISÕES HUMANAS</span><h3 style="margin:7px 0 0;font-size:clamp(1.1rem,1.8vw,1.5rem);color:#183e32;font-weight:650;letter-spacing:-.03em">Na linha do tempo</h3></div><span class="mat-ed-source-pill">Aprovação ≠ validação</span></div>
    <p class="mat-ed-intro">Esta régua localiza os marcos aprovados ou validados em seus anos confirmados, <strong>mesmo que ainda não tenham pesos suficientes</strong> para a curva. Uma aprovação não é convertida automaticamente em validação.</p>
    <div class="mat-grafo-bloco"><?=TemporisGraficos::svgDecisoes($modelo['decisoes_humanas']??[])?></div>
<?php if(!empty($modelo['decisoes_humanas'])): ?>
    <div class="mat-ed-table-wrap" style="margin-top:13px"><table class="mat-ed-table"><thead><tr><th>Marco</th><th>Ano validado</th><th>Decisão humana</th><th>11 rubricas completas</th></tr></thead><tbody>
<?php foreach($modelo['decisoes_humanas'] as $ap):?><tr>
<td><strong><?=h((string)$ap['id'])?></strong> · <?=h((string)$ap['title'])?></td>
<td><?=$ap['year']===null?'Sem data confirmada':h(yfmt((int)$ap['year']))?></td>
<td><?=h((string)$ap['estado'])?></td><td><?=$ap['notas_completas']?'Sim':'Não'?></td>
</tr><?php endforeach; ?>
    </tbody></table></div>
<?php endif;?>
    <div class="mat-ed-strip" style="margin-top:15px">
      <div><small>Marcos na curva selecionada</small><b><?=count($serie)?></b></div>
      <div><small>Aprovados/validados presentes na curva</small><b><?=count($confirmadosGrafico)?></b></div>
      <div><small>Base K(<?=h((string)$anoBase)?>)</small><b><?=decimal((float)$ativa['k_base'],3)?></b></div>
    </div>
<?php if($confirmadosGrafico): ?>
    <div class="mat-ed-table-wrap" style="margin-top:15px"><table class="mat-ed-table"><thead><tr><th>ID</th><th>Marco com decisão humana</th><th>Ano de cálculo</th><th>Estado</th></tr></thead><tbody>
<?php foreach($confirmadosGrafico as $cp): ?><tr><th><?=h((string)$cp['id'])?></th><td><?=h((string)$cp['title'])?></td><td><?=h(yfmt((int)$cp['year']))?></td><td><?=h((string)$cp['estado_revisao_humana'])?></td></tr><?php endforeach; ?>
    </tbody></table></div>
<?php else: ?><p class="mat-ed-table-note" style="margin-top:12px">Ainda não há marcos com decisão humana de aprovação dentro da curva selecionada. Pontos provisórios são exibidos apenas como ensaio.</p><?php endif; ?>
  </section>

  <section class="mat-ed-section" aria-labelledby="reform-lab-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">03 / ENSAIOS REPRODUZÍVEIS · EM MEMÓRIA · NÃO ALTERA NOTAS OU DADOS</span><h2 id="reform-lab-titulo">Teste rápido da <em>fórmula Têmporis</em></h2></div><span class="mat-ed-source-pill">Somente leitura</span></div>
    <p class="mat-ed-intro">Com <strong><?=short_num((int)$ensaio['included'])?> candidatos</strong> de 11 notas, a simulação utiliza a melhor data disponível. <strong><?=short_num((int)$ensaio['datas_provisorias'])?> usam anos extraídos não revisados</strong> e <?=short_num((int)$ensaio['datas_intervalares'])?> usam intervalos registrados. Recalcular o modelo não constitui aprovação.</p>
    <div class="mat-ed-actions" style="margin-top:0;margin-bottom:16px">
      <a class="mat-ed-button" href="exportar_ensaio_temporis.php?serie=ensaio">Exportar candidatos e valores do ensaio em CSV ↗</a>
      <a class="mat-ed-textlink" href="exportar_ensaio_temporis.php?serie=previa">Exportar a prévia com datas documentadas ↗</a>
    </div>
    <details class="mat-ed-details" open><summary>Sensibilidade temporal — β = 2</summary>
      <div class="mat-ed-details-body">
        <p>Quando um marco tem intervalo, a mesma contribuição é posicionada uma única vez no início, meio ou fim da faixa. A tabela mede o efeito no índice; não é intervalo de confiança estatístico.</p>
        <div class="mat-ed-table-wrap"><table class="mat-ed-table"><thead><tr><th>Cenário</th><?php foreach($modelo['sensibilidade']['anos'] as $yr):?><th><?=h((string)$yr)?></th><?php endforeach;?></tr></thead><tbody>
        <?php foreach($modelo['sensibilidade']['cenarios'] as $cen=>$values):?><tr><th><?=h(match($cen){'inicio'=>'Início','fim'=>'Fim',default=>'Ponto médio'})?></th><?php foreach($modelo['sensibilidade']['anos'] as $yr):?><td><?=$values['valores'][$yr]===null?'—':decimal((float)$values['valores'][$yr],2)?></td><?php endforeach;?></tr><?php endforeach;?>
        </tbody></table></div>
      </div>
    </details>
    <details class="mat-ed-details" style="margin-top:11px"><summary>Sensibilidade de β — ano representativo</summary>
      <div class="mat-ed-details-body">
        <p>O método atual usa β = 2; β = 1 e 3 são somente comparações matemáticas, sem mudar a configuração oficial.</p>
        <div class="mat-ed-table-wrap"><table class="mat-ed-table"><thead><tr><th>β</th><th>K até <?=h((string)$anoBase)?></th><th>K total</th><th>T no último ponto</th></tr></thead><tbody><?php foreach($modelo['sensibilidade_beta'] as $beta=>$resultado):?><tr><th><?=h((string)$beta)?></th><td><?=decimal((float)$resultado['k_base'],3)?></td><td><?=decimal((float)$resultado['k_total'],3)?></td><td><?=$resultado['t_ultimo']===null?'Indefinido':decimal((float)$resultado['t_ultimo'],2)?></td></tr><?php endforeach;?></tbody></table></div>
      </div>
    </details>
    <details class="mat-ed-details" style="margin-top:11px"><summary>Sensibilidade à retirada de um marco por vez</summary>
      <div class="mat-ed-details-body">
        <p>Este ensaio mostra como o índice final muda se cada candidato for excluído da série, sem alterar os dados. É útil para identificar dependência de eventos específicos, mas não valida a verdade histórica das notas.</p>
        <?php $loo=$modelo['sensibilidade_exclusao']??[]; if(!empty($loo['calculavel'])): ?>
        <p><strong>Maior variação absoluta:</strong> <?=$loo['maior_variacao']===null?'Indefinida':decimal((float)$loo['maior_variacao'],3)?> ponto(s) · <strong><?=h((string)($loo['indefinidos']??0))?></strong> retirada(s) inviabilizam a normalização no ano-base.</p>
        <div class="mat-ed-table-wrap"><table class="mat-ed-table"><thead><tr><th>Marco</th><th>Ano</th><th>Sem o marco</th><th>Δ do índice</th></tr></thead><tbody>
        <?php foreach($loo['marcos'] as $item): ?><tr><th><?=h($item['id'])?></th><td><?=h((string)$item['ano'])?></td><td><?=$item['indice_sem_marco']===null?'Indefinido':decimal((float)$item['indice_sem_marco'],3)?></td><td><?=$item['variacao']===null?'Indefinido':decimal((float)$item['variacao'],3)?></td></tr><?php endforeach; ?></tbody></table></div>
        <?php else: ?><p>Dados insuficientes ou ausência de base histórica para calcular a influência individual. Não substituímos valores indefinidos.</p><?php endif; ?>
        <p><strong>Limitação:</strong> se K(<?=h((string)$anoBase)?>) = 0, T(t) é indefinido; não substituímos esse denominador por um valor artificial. O ensaio pode conter anos extraídos errados, processos de longa duração e duplicatas. Os resultados são para depuração e preparação da defesa, não para afirmar que a aceleração histórica foi demonstrada.</p>
      </div>
    </details>
  </section>

  <section class="mat-ed-section" aria-label="Encaminhamento">
    <div class="mat-ed-cards">
      <div class="mat-ed-card mat-ed-card-lead"><span class="mat-ed-card-label">O QUE FALTA · AUDITORIA HUMANA</span><strong>Da nota proposta à validação</strong><p>O pesquisador precisa confrontar as fontes, revisar cada critério, corrigir datas, resolver duplicatas e pendências, aprovar o dossiê e só então validá-lo.</p><p><a href="<?=mat_url('rodadas')?>">Abrir a fila de revisão ↗</a></p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">INTEGRIDADE METODOLÓGICA · SEM CONVERSÃO DE ESCALAS</span><strong>Duas séries, duas bases</strong><p>A Têmporis original permanece com o motor de pontuação legado e a transformação de potência 0,65; aqui só entram as 11 rubricas da curadoria. Não há equivalência automática entre os valores.</p><p><strong>Próximos testes:</strong> concordância dos avaliadores independentes, variação de pesos, comparação linear/exponencial/logística, resíduos e erro preditivo.</p></div>
    </div>
  </section>

  <footer class="mat-ed-footnote">
    <p>Versão histórica preservada para comparação. O método adotado é o MAT Final; consulte também o backtesting dos modelos.</p>
    <div class="mat-ed-actions">
      <a class="mat-ed-button" href="<?=mat_url('temporis_final')?>">Abrir o método atual →</a>
      <a class="mat-ed-textlink" href="<?=mat_url('temporis_legado')?>">Ver a formulação original</a>
    </div>
  </footer>
</div>
