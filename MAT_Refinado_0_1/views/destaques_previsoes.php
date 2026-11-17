<?php
/**
 * MAT · Vitrine de resultados históricos de previsão (50 anos).
 * Os números são registros históricos das bases indicadas, não leituras da curadoria atual.
 * A base de 12/11 consta de um ensaio relatado anteriormente, ainda não reexecutado aqui.
 * O snapshot 07/11 tem reprodução auditável por TemporisFinal no ensaio de 12 cortes.
 * A base 16/11 é o retrato do banco da curadoria, com validação cruzada por ferramenta
 * externa independente (mesmos cortes e WAPE); ver docs/previsoes-50-anos/METODO.md.
 * Os registros históricos são estáticos (a vitrine nunca "quebra"); a seção
 * "Conferência de hoje" recalcula os mesmos cortes ao vivo no banco da curadoria.
 */
require_once MAT_ROOT.'/app/TemporisFinal.php';
require_once MAT_ROOT.'/app/TemporisMetodologia2026.php';
// Registros históricos da base 07/11 (auditados por tests/test_previsoes_50_anos_snapshot.php).
$rpHist=[1850=>['prev'=>'100,74','obs'=>'99,51','erro'=>'5,92%','aumento'=>'26,28%'],
         1875=>['prev'=>'114,64','obs'=>'116,33','erro'=>'6,08%','aumento'=>'31,51%'],
         1900=>['prev'=>'125,67','obs'=>'125,62','erro'=>'0,19%','aumento'=>'26,23%']];
// Conferência ao vivo: mesma métrica, banco da curadoria (fonte auto → MySQL). Sem gravacao.
$rpLive=null;$rpLiveErro=null;
try{
    $rpDados=TemporisFinal::obterDados('auto',2026);
    $rpPts=TemporisFinal::filtrar($rpDados['dados']['pontos'],'ampla');
    $rpLine=TemporisFinal::linha($rpPts,'quadratica');
    $rpLive=['fonte'=>$rpDados['fonte'],'database'=>$rpDados['database'],
        'n'=>count($rpPts),'total'=>(int)($rpDados['dados']['audit']['total']??0),'cortes'=>[],'todos'=>[]];
    foreach([1850,1875,1900] as $rpC){
        $rpLive['cortes'][$rpC]=TemporisMetodologia2026::confronto($rpLine,$rpC,50,'exponencial50',2026);
    }
    foreach(range(1700,1975,25) as $rpC){
        $rpLive['todos'][$rpC]=TemporisMetodologia2026::confronto($rpLine,$rpC,50,'exponencial50',2026);
    }
    $rpPioresHoje=[];
    foreach($rpLive['todos'] as $rpC=>$rpR){
        if($rpR['erro_pct_delta']===null)continue;
        $rpPioresHoje[]=['corte'=>$rpC,'alvo'=>$rpC+50,'erro'=>(float)$rpR['erro_pct_delta'],'aumento'=>$rpR['delta_observado']>0&&$rpR['k_corte']>0?100*(float)$rpR['delta_observado']/(float)$rpR['k_corte']:null];
    }
    usort($rpPioresHoje,static fn($a,$b)=>$b['erro']<=>$a['erro']);
    $rpPioresHoje=array_slice($rpPioresHoje,0,3);
    $rpLive['piores']=$rpPioresHoje;
    // Ranking geral dos 11 modelos no banco de hoje (WAPE dos mesmos 12 cortes).
    $rpRanking=[];
    foreach(TemporisFinal::MODELOS as $rpId=>$rpLabel){
        $rpSt=TemporisFinal::validar($rpLine,$rpId,50,2026);
        if($rpSt['wape']===null)continue;
        $rpRanking[]=['id'=>$rpId,'label'=>$rpLabel,'wape'=>(float)$rpSt['wape'],'n'=>(int)$rpSt['n']];
    }
    usort($rpRanking,static fn($a,$b)=>$a['wape']<=>$b['wape']);
    $rpLive['ranking']=$rpRanking;
}catch(Throwable $e){$rpLiveErro=$e->getMessage();}
$rpNum=static fn(?float $n):string=>$n===null?'—':number_format($n,2,',','.');
?>
<div class="mat-rp" id="mat-melhores-previsoes">
  <header class="mat-rp-hero">
    <div class="mat-rp-hero-top">
      <span class="mat-rp-eyebrow"><span class="mat-rp-dot"></span> ÍNDICE TÊMPORIS <span aria-hidden="true">/</span> ESTUDO RETROSPECTIVO</span>
      <span class="mat-rp-year">HORIZONTE FIXO · 50 ANOS</span>
    </div>
    <div class="mat-rp-hero-body">
      <div>
        <span class="mat-rp-side-title">DEMONSTRAÇÃO DOS RESULTADOS</span>
        <h1>Quando a previsão se aproxima <em>da história.</em></h1>
        <p>Os melhores acertos individuais do MAT, organizados por época e por versão da curadoria. Compare o valor previsto ao que foi registrado cinquenta anos depois.</p>
      </div>
      <div class="mat-rp-hero-mark" aria-hidden="true"><span>50</span><small>ANOS</small></div>
    </div>
    <div class="mat-rp-hero-bottom"><span>MODELO EXPONENCIAL LOCAL</span><span>PREVISTO × OBSERVADO</span><span>RESULTADOS HISTÓRICOS</span></div>
  </header>

  <div class="mat-rp-nav" aria-label="Ir para os conjuntos de resultados">
    <a href="#mat-rp-mais-precisos">Destaques</a>
    <a href="#mat-rp-2026-11-12">Base 12/11</a>
    <a href="#mat-rp-2026-11-07">Base 07/11</a>
    <a href="#mat-rp-2026-11-16">Base 16/11</a>
    <a href="#mat-rp-hoje">Conferência de hoje</a>
    <a href="#mat-rp-metodo">Método</a>
    <a href="#mat-rp-piores" class="mat-rp-nav-worst">Piores</a>
  </div>

  <section id="mat-rp-mais-precisos" class="mat-rp-section" aria-labelledby="mat-rp-intro-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">01 / EXEMPLOS DE DESTAQUE</span><h2 id="mat-rp-intro-title">Os acertos em evidência</h2></div><p>Cada cartão corresponde a uma previsão de 50 anos.</p></div>
    <div class="mat-rp-cards">
      <article class="mat-rp-card mat-rp-card-lead">
        <span class="mat-rp-card-label">ACERTO REGISTRADO · BASE 12/11/2026</span>
        <div class="mat-rp-card-number">2,79<span>%</span></div>
        <strong>1850 <span aria-hidden="true">→</span> 1900</strong>
        <p>Previsto <b>150,02</b> <span class="mat-rp-divider-small">/</span> Observado <b>148,64</b></p>
        <div class="mat-rp-card-bottom">Resultado anterior · reexecução pendente</div>
      </article>
      <article class="mat-rp-card">
        <span class="mat-rp-card-label">MENOR ERRO · BASE 07/11/2026</span>
        <div class="mat-rp-card-number">0,19<span>%</span></div>
        <strong>1900 <span aria-hidden="true">→</span> 1950</strong>
        <p>Previsto <b>125,67</b> <span class="mat-rp-divider-small">/</span> Observado <b>125,62</b></p>
        <div class="mat-rp-card-bottom">Teste reproduzido · 667 marcos</div>
      </article>
      <article class="mat-rp-card">
        <span class="mat-rp-card-label">ACERTO REPRODUZIDO · BASE 07/11/2026</span>
        <div class="mat-rp-card-number">5,92<span>%</span></div>
        <strong>1850 <span aria-hidden="true">→</span> 1900</strong>
        <p>Previsto <b>100,74</b> <span class="mat-rp-divider-small">/</span> Observado <b>99,51</b></p>
        <div class="mat-rp-card-bottom">Aumento real do índice: +26,28%</div>
      </article>
    </div>
    <div class="mat-rp-emphasis"><span class="mat-rp-emphasis-icon">↗</span><p><strong>Outro acerto importante:</strong> entre 1875 e 1925, o MAT registrou <strong>6,08% de erro</strong>, enquanto o índice Têmporis cresceu <strong>31,51%</strong> no período — base de 07/11.</p></div>
    <div class="mat-rp-verdict" role="note">
      <strong>QUAL MODELO FOI O MELHOR?</strong>
      <ul>
        <li><b>Modelo desta aba:</b> <span class="mat-rp-verdict-badge">Exponencial · últimos 50 anos</span> — dele são os melhores cortes individuais exibidos acima (0,19% · 5,92% · 6,08%).</li>
        <li><b>Base 07/11 · melhor no WAPE geral dos 12 cortes:</b> Aceleração local (<b>26,0%</b>), com o Exponencial50 em 31,1%. Os melhores cortes individuais e o melhor modelo médio <strong>não são a mesma coisa</strong>. <a href="index.php?page=backtesting&fonte=snapshot&coorte=ampla&horizonte=50&referencia=exponencial50">Conferir ↗</a></li>
        <?php if($rpLive!==null && !empty($rpLive['ranking'])):
          $rpWin=$rpLive['ranking'][0];$rpPosExp=0;
          foreach($rpLive['ranking'] as $i=>$r){if($r['id']==='exponencial50'){$rpPosExp=$i+1;break;}}
          $rpExp=$rpLive['ranking'][$rpPosExp-1]??null; ?>
        <li><b>Banco de hoje · melhor no WAPE geral:</b> <?=h($rpWin['label'])?> (<b><?=$rpNum($rpWin['wape'])?>%</b>, 1º de <?=count($rpLive['ranking'])?> calculáveis)<?php if($rpExp && $rpWin['id']!=='exponencial50'):?>; o Exponencial50 ficou <?=$rpPosExp?>º (<?=$rpNum($rpExp['wape'])?>%)<?php elseif($rpWin['id']==='exponencial50'):?> — o mesmo modelo desta aba<?php endif; ?>.</li>
        <?php else: ?>
        <li><b>Banco de hoje:</b> ranking indisponível agora (MySQL) — conferir na <a href="index.php?page=backtesting">Backtesting ↗</a>.</li>
        <?php endif; ?>
      </ul>
    </div>
  </section>

  <section id="mat-rp-2026-11-12" class="mat-rp-section" aria-labelledby="mat-rp-old-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">02 / RESULTADOS DA CURADORIA DE 12/11/2026</span><h2 id="mat-rp-old-title">O teste que registrou 2,79%</h2></div><span class="mat-rp-source-pill">1.257 marcos informados</span></div>
    <p class="mat-rp-intro">Estes números foram registrados em um teste anterior com a fórmula Final. São preservados para a apresentação, mas <strong>a base de 12/11 ainda precisa ser reexecutada para nova validação</strong>.</p>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table">
      <thead><tr><th>Período de 50 anos</th><th>K previsto</th><th>K observado</th><th>Erro no crescimento</th></tr></thead>
      <tbody>
        <tr class="mat-rp-featured-row"><td><b>1850–1900</b> <span class="mat-rp-row-mark">Destaque</span></td><td>150,02</td><td>148,64</td><td><span class="mat-rp-result">2,79%</span></td></tr>
        <tr><td>1875–1925</td><td>188,92</td><td>192,20</td><td><span class="mat-rp-result">4,59%</span></td></tr>
        <tr><td>1950–2000</td><td>392,11</td><td>410,66</td><td><span class="mat-rp-result">10,96%</span></td></tr>
        <tr><td>1925–1975</td><td>305,72</td><td>324,61</td><td><span class="mat-rp-result">14,27%</span></td></tr>
        <tr><td>1900–1950</td><td>222,84</td><td>241,42</td><td><span class="mat-rp-result">20,02%</span></td></tr>
      </tbody>
    </table></div>
    <p class="mat-rp-table-note">Os valores do K estão arredondados. Erros extraídos do registro do teste anterior, sem novo recálculo. WAPE informado para os 12 cortes dessa versão: <strong>29,20%</strong>.</p>
  </section>

  <section id="mat-rp-2026-11-07" class="mat-rp-section" aria-labelledby="mat-rp-new-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">03 / RESULTADOS REPRODUZIDOS · SNAPSHOT 07/11/2026</span><h2 id="mat-rp-new-title">Três previsões quase exatas</h2></div><span class="mat-rp-source-pill">667 marcos elegíveis</span></div>
    <p class="mat-rp-intro">Modelo exponencial local, pesos da fórmula Final e horizonte fixo de 50 anos. Os três períodos abaixo foram recalculados no snapshot fornecido com o seu MAT.</p>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table">
      <thead><tr><th>Período de 50 anos</th><th>K previsto</th><th>K observado</th><th>Erro no crescimento</th><th>Aumento real de K</th></tr></thead>
      <tbody>
        <tr><td><b>1850–1900</b></td><td>100,74</td><td>99,51</td><td><span class="mat-rp-result">5,92%</span></td><td><span class="mat-rp-growth">+26,28%</span></td></tr>
        <tr><td><b>1875–1925</b></td><td>114,64</td><td>116,33</td><td><span class="mat-rp-result">6,08%</span></td><td><span class="mat-rp-growth">+31,51%</span></td></tr>
        <tr class="mat-rp-featured-row"><td><b>1900–1950</b> <span class="mat-rp-row-mark">Menor erro</span></td><td>125,67</td><td>125,62</td><td><span class="mat-rp-result">0,19%</span></td><td><span class="mat-rp-growth">+26,23%</span></td></tr>
      </tbody>
    </table></div>
    <div class="mat-rp-summary-strip"><div><small>Menor erro individual</small><b>0,19%</b></div><div><small>Aumento máximo entre os três</small><b>+31,51%</b></div><div><small>WAPE · todos os 12 cortes</small><b>31,13%</b></div></div>
    <p class="mat-rp-table-note">O observado de 1900 no snapshot é 99,514531; com arredondamento direto para duas casas fica <b>99,51</b>. O erro de 5,92% é calculado com valores completos, não os exibidos arredondados.</p>
  </section>

  <section id="mat-rp-2026-11-16" class="mat-rp-section" aria-labelledby="mat-rp-16-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">04 / REGISTRO DA CURADORIA DE 16/11/2026</span><h2 id="mat-rp-16-title">Dois cortes abaixo de 2% na base atual</h2></div><span class="mat-rp-source-pill">1.292 elegíveis · validação cruzada</span></div>
    <p class="mat-rp-intro">Registro fixo do dia 16/11/2026, calculado no banco <code>mat_temporis_curadoria</code> com o mesmo motor (exponencial local · 50 anos, coorte ampla). É a mesma origem da “Conferência de hoje” — esta seção preserva o retrato do dia, enquanto a conferência continua acompanhando o banco.</p>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table">
      <thead><tr><th>Período de 50 anos</th><th>K previsto</th><th>K observado</th><th>Erro no crescimento</th><th>Aumento real de K</th></tr></thead>
      <tbody>
        <tr><td><b>1850–1900</b></td><td>153,16</td><td>152,16</td><td><span class="mat-rp-result">1,94%</span></td><td><span class="mat-rp-growth">+51,89%</span></td></tr>
        <tr class="mat-rp-featured-row"><td><b>1875–1925</b> <span class="mat-rp-row-mark">Menor erro</span></td><td>197,19</td><td>196,26</td><td><span class="mat-rp-result">1,28%</span></td><td><span class="mat-rp-growth">+58,43%</span></td></tr>
        <tr><td><b>1900–1950</b></td><td>231,10</td><td>246,88</td><td><span class="mat-rp-result">16,65%</span></td><td><span class="mat-rp-growth">+62,25%</span></td></tr>
      </tbody>
    </table></div>
    <div class="mat-rp-summary-strip"><div><small>Menor erro individual</small><b>1,28%</b></div><div><small>WAPE · exponencial50 · 12 cortes</small><b>29,16%</b></div><div><small>Registros de critérios lidos</small><b>16.918</b></div></div>
    <div class="mat-rp-emphasis"><span class="mat-rp-emphasis-icon">✓</span><p><strong>Validação cruzada independente:</strong> a mesma base de 16/11 (tabelas <code>cur_candidatos</code> e <code>cur_criterios</code>) foi reprocessada por <strong>ferramenta externa, com o motor matemático original, sem alterar pontuações nem selecionar resultados</strong> — e produziu os mesmos três erros (1,94% · 1,28% · 16,65%) e o mesmo WAPE (29,16%). Nenhuma divergência encontrada entre as duas implementações.</p></div>
    <p class="mat-rp-table-note">O erro de 0,19% para 1900–1950 (base 07/11) <strong>não se repetiu</strong> nesta base: hoje o mesmo corte dá 16,65%. As bases são distintas e seguem registradas separadamente, sem mistura. <a href="index.php?page=backtesting&fonte=auto&coorte=ampla&horizonte=50&referencia=exponencial50">Conferir ao vivo na Backtesting ↗</a></p>
  </section>

  <section id="mat-rp-hoje" class="mat-rp-section" aria-labelledby="mat-rp-hoje-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">05 / CONFERÊNCIA DE HOJE · BANCO DA CURADORIA</span><h2 id="mat-rp-hoje-title">Os mesmos cortes, recalculados <em>agora.</em></h2></div><span class="mat-rp-source-pill"><?=$rpLive!==null?h($rpLive['database']):'MySQL indisponível'?></span></div>
<?php if($rpLive!==null): ?>
    <p class="mat-rp-intro">A vitrine acima preserva os registros históricos; esta conferência recalcula <strong>os mesmos três cortes</strong> (modelo exponencial local · 50 anos, coorte ampla) no banco atual da curadoria. Os dois lados usam a <strong>mesma métrica</strong>; eles diferem porque o banco evolui desde o snapshot de 07/11. Consulta somente leitura — nada é alterado.</p>
    <div class="mat-rp-summary-strip" style="grid-template-columns:repeat(4,minmax(0,1fr))">
      <div><small>Banco da conferência</small><b style="font-size:1rem"><?=h($rpLive['database'])?></b></div>
      <div><small>Marcos elegíveis hoje</small><b><?=number_format($rpLive['n'],0,',','.')?></b></div>
      <div><small>De</small><b><?=number_format($rpLive['total'],0,',','.')?> candidatos</b></div>
      <div><small>Origem efetiva</small><b style="font-size:1rem"><?=$rpLive['fonte']==='curadoria'?'curadoria (MySQL)':h($rpLive['fonte'])?></b></div>
    </div>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table">
      <thead><tr><th rowspan="2">Corte de 50 anos</th><th colspan="3">Registro histórico · 07/11</th><th colspan="3">Banco de hoje</th><th rowspan="2">Variação do erro</th></tr>
      <tr><th>K previsto</th><th>K observado</th><th>Erro</th><th>K previsto</th><th>K observado</th><th>Erro</th></tr></thead>
      <tbody>
      <?php foreach($rpHist as $rpC=>$rpH): $rpR=$rpLive['cortes'][$rpC]??null;
        $rpErroHoje=($rpR&&$rpR['erro_pct_delta']!==null)?(float)$rpR['erro_pct_delta']:null;
        $rpDelta=($rpErroHoje!==null)?abs((float)str_replace(',','.',$rpH['erro'])-$rpErroHoje):null; ?>
      <tr><td><b><?= $rpC ?> → <?= $rpC+50 ?></b></td>
        <td><?=$rpH['prev']?></td><td><?=$rpH['obs']?></td><td><span class="mat-rp-result"><?=$rpH['erro']?></span></td>
        <td><?=$rpNum($rpR['previsto']??null)?></td><td><?=$rpNum($rpR['observado']??null)?></td><td><span class="mat-rp-result"><?=$rpNum($rpErroHoje)?><?=$rpErroHoje===null?'':'%'?></span></td>
        <td><?php $rpHistErro=(float)str_replace(',','.',$rpH['erro']);
          if($rpDelta===null){echo '—';}
          elseif($rpDelta<=1){echo '<span class="mat-rp-growth">≈ o mesmo erro</span>';}
          else{echo ($rpErroHoje<$rpHistErro?'melhorou ':'piorou ').$rpNum($rpDelta).' p.p.';} ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="mat-rp-table-note">p.p. = pontos percentuais de erro. “≈ o mesmo erro” para diferenças até 1 p.p. Nenhum dos dois lados valida ou rejeita marcos; são leituras retrospectivas. Conferir corte a corte (todos os 12) na <a href="index.php?page=backtesting&fonte=auto&coorte=ampla&horizonte=50&referencia=exponencial50">Backtesting ligada ao banco da curadoria ↗</a>.</p>
    <h3 class="mat-rp-rank-title">Ranking dos modelos · banco de hoje · WAPE dos 12 cortes</h3>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table mat-rp-rank-table">
      <thead><tr><th>Posição</th><th>Modelo</th><th>WAPE</th><th>Cortes válidos</th></tr></thead>
      <tbody>
      <?php foreach($rpLive['ranking'] as $rpI=>$rpR): ?>
        <tr <?=$rpI===0?'class="mat-rp-rank-first"':''?><?=$rpR['id']==='exponencial50'?' data-ab-model="1"':''?>><td><?=$rpI+1?>º<?=$rpI===0?' <small>melhor no geral</small>':''?></td><td><?=h($rpR['label'])?><?=$rpR['id']==='exponencial50'?' <small>modelo desta aba</small>':''?></td><td><strong><?=$rpNum($rpR['wape'])?>%</strong></td><td><?=$rpR['n']?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <p class="mat-rp-table-note">“Melhor no geral” (WAPE) avalia o desempenho médio em todos os cortes; os destaques da seção 01 são os melhores cortes <strong>individuais</strong>. Duas medidas diferentes — por isso o modelo desta aba nem sempre é o 1º do ranking.</p>
<?php else: ?>
    <p class="mat-rp-intro">A conferência ao vivo precisa do MySQL acessível <strong>agora</strong> e não pôde ser executada neste momento. <strong>Os registros históricos das seções acima continuam válidos</strong> — a vitrine é propositalmente estática para nunca perder os números.</p>
    <div class="mat-rp-emphasis"><span class="mat-rp-emphasis-icon">※</span><p><strong>Motivo:</strong> <?=h($rpLiveErro)?></p></div>
<?php endif; ?>
  </section>

  <section id="mat-rp-metodo" class="mat-rp-section" aria-labelledby="mat-rp-metodo-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">06 / MÉTODO E FÓRMULA</span><h2 id="mat-rp-metodo-title">De onde vêm os números</h2></div><span class="mat-rp-source-pill">Tudo conferível no código</span></div>
    <details class="mat-rp-method" open>
      <summary>Abrir o método e a fórmula utilizada</summary>
      <div class="mat-rp-method-body">
        <h3>1 · Peso de cada marco</h3>
        <p><code class="mat-rp-formula">w = (I / 10)² × C × F</code></p>
        <p>Impacto (I), confiabilidade histórica (C) e relevância fundacional (F) vêm das 11 rubricas documentadas em cada dossiê.</p>
        <h3>2 · Índice</h3>
        <p><code class="mat-rp-formula">K(t) = Σ wᵢ &nbsp;(marcos datados até o ano t)</code></p>
        <p>Soma dos pesos, sem ano-base e sem normalização obrigatória.</p>
        <h3>3 · Previsão exponencial local · horizonte de 50 anos</h3>
        <p><code class="mat-rp-formula">K̂(t + 50) = K(t)² / K(t − 50)</code></p>
        <p>Extrapolado da taxa dos últimos 50 anos observados: <code>K̂(t + h) = K(t) × (K(t)/K(t−50))^(h/50)</code>.</p>
        <h3>4 · Erro no crescimento e aumento real</h3>
        <p><code class="mat-rp-formula">erro = |g_prev − g_obs| / g_obs</code> &nbsp;com&nbsp; <code>g_obs = K(t+50)/K(t) − 1</code></p>
        <p>O “aumento real” é <code>g_obs</code> em %. O WAPE resume os 12 cortes; não se confunde com o melhor erro individual.</p>
        <h3>5 · Origem dos dados</h3>
        <p><strong>Base 07/11/2026:</strong> exportação fixa (snapshot) de 800 marcos, 667 elegíveis — <strong>reproduzida</strong> por <code>tests/test_previsoes_50_anos_snapshot.php</code>.</p>
        <p><strong>Base 12/11/2026:</strong> ensaio anterior com 1.257 marcos informados — registro preservado, <strong>ainda não reexecutado</strong> neste ambiente.</p>
        <p><strong>Base 16/11/2026:</strong> banco da curadoria (1.292 elegíveis de 1.538) — reproduzido ao vivo e validado por ferramenta externa independente (mesmos cortes e WAPE 29,16%).</p>
        <h3>6 · Os modelos de previsão disponíveis</h3>
        <p>Esta aba usa o <strong>Exponencial · últimos 50 anos</strong>. Os 11 modelos do MAT Final, resumidos:</p>
        <ul class="mat-rp-modelos">
          <li><b>Sem crescimento</b> — controle, sem avanço previsto; régua de comparação.</li>
          <li><b>Linear 50 / 100</b> — extrapola a velocidade média dos últimos 50 ou 100 anos.</li>
          <li><b>Exponencial 50 / 100</b> — extrapola o crescimento composto dos últimos 50 ou 100 anos (50 é o da aba).</li>
          <li><b>Aceleração local</b> — usa a variação da velocidade (acelerando ou desacelerando).</li>
          <li><b>Tendência amortecida</b> — crescimento linear que perde metade da força a cada 50 anos.</li>
          <li><b>Mediana de 3</b> — valor do meio entre linear50, linear100 e exponencial50.</li>
          <li><b>Ímpeto dK/dt = aK^β</b> — crescimento dependente do tamanho do estoque K.</li>
          <li><b>Logístico / Gompertz</b> — crescimento com saturação (teto).</li>
        </ul>
        <p class="mat-rp-table-note">Comparação justa de todos no mesmo conjunto de cortes: <a href="index.php?page=backtesting&fonte=auto&coorte=ampla&horizonte=50&mostrar_todos=1">Backtesting com todos os modelos ↗</a>.</p>
        <p class="mat-rp-table-note">Implementação real: <code>app/TemporisFinal.php</code> (função <code>projetar</code>, modelo <code>exponencial50</code>). Documentação completa: <a href="docs/previsoes-50-anos/METODO.md">docs/previsoes-50-anos/METODO.md</a> · <a href="index.php?page=metodo">Caderno de fórmulas</a> · <a href="index.php?page=backtesting">Backtesting</a></p>
      </div>
    </details>
  </section>

  <section id="mat-rp-piores" class="mat-rp-section mat-rp-worst" aria-labelledby="mat-rp-piores-title">
    <div class="mat-rp-section-heading"><div><span class="mat-rp-overline">07 / OS CORTES MAIS DIFÍCEIS</span><h2 id="mat-rp-piores-title">Onde o modelo <em>errou mais.</em></h2></div><span class="mat-rp-worst-pill">Registro completo · nada escondido</span></div>
    <p class="mat-rp-intro">Os piores cortes ficam registrados <strong>em vermelho</strong>, na mesma métrica das seções acima. Eles se concentram nos <strong>anos mais recentes</strong> (cobertura do catálogo ainda em construção) e nos <strong>períodos antigos</strong> (poucos marcos na base). Erros altos não invalidam os acertos — e os acertos não apagam estes números.</p>
    <div class="mat-rp-table-wrap"><table class="mat-rp-table mat-rp-worst-table">
      <thead><tr><th>Base</th><th>Corte → alvo</th><th>Erro no crescimento</th><th>Aumento real de K</th></tr></thead>
      <tbody>
      <?php
      // Piores da base 07/11 — registro da reprodução auditável (mesmo snapshot do teste).
      $rpPioresHist=[['corte'=>1975,'erro'=>'104,78%','aumento'=>'10,30%'],
                     ['corte'=>1700,'erro'=>'52,36%','aumento'=>'14,08%'],
                     ['corte'=>1925,'erro'=>'49,33%','aumento'=>'21,10%']];
      foreach($rpPioresHist as $rpP): ?>
        <tr class="mat-rp-worst-row"><td><b>Snapshot 07/11</b> <small>registro reproduzível</small></td><td><b><?= $rpP['corte'] ?> → <?= $rpP['corte']+50 ?></b></td><td><span class="mat-rp-worst-erro"><?=$rpP['erro']?></span></td><td>+<?=$rpP['aumento']?></td></tr>
      <?php endforeach; ?>
      <?php if($rpLive!==null && !empty($rpLive['piores'])): foreach($rpLive['piores'] as $rpP): ?>
        <tr class="mat-rp-worst-row"><td><b>Banco de hoje</b> <small><?=h($rpLive['database'])?></small></td><td><b><?= $rpP['corte'] ?> → <?= $rpP['alvo'] ?></b></td><td><span class="mat-rp-worst-erro"><?=$rpNum($rpP['erro'])?>%</span></td><td><?=($rpP['aumento']===null?'—':'+'.$rpNum($rpP['aumento']).'%')?></td></tr>
      <?php endforeach; else: ?>
        <tr><td><b>Banco de hoje</b></td><td colspan="3" class="mat-rp-worst-off">Conferência ao vivo indisponível neste momento — os piores de hoje ficam visíveis na <a href="index.php?page=backtesting&fonte=auto&coorte=ampla&horizonte=50&referencia=exponencial50">Backtesting ↗</a> (fim da tabela de cortes).</td></tr>
      <?php endif; ?>
      </tbody>
    </table></div>
    <p class="mat-rp-table-note">Interpretação conservadora: nenhum dos piores cortes, isoladamente, comprova ou refuta o modelo; o desempenho geral segue avaliável pelo WAPE dos 12 cortes (29,20% na base 12/11 · 31,13% na base 07/11 · 29,16% na base 16/11). Conferir todos os cortes na <a href="index.php?page=backtesting&fonte=auto&coorte=ampla&horizonte=50&referencia=exponencial50">Backtesting ligada ao banco da curadoria ↗</a>.</p>
  </section>

  <footer class="mat-rp-footnote"><span class="mat-rp-overline">COMO LER</span><p><strong>Erro da previsão</strong> compara o aumento previsto de K ao aumento histórico registrado; <strong>aumento real</strong> é a variação percentual de K entre os dois anos. São medidas diferentes. Os três melhores casos foram destacados para apresentação, mas o desempenho geral deve ser avaliado pelos 12 cortes e seu WAPE. As bases de 07/11, 12/11 e 16/11 não são intercambiáveis.</p><div class="mat-rp-actions"><a href="index.php?page=backtesting" class="mat-rp-button">Abrir o teste completo dos modelos <span aria-hidden="true">↗</span></a><a href="index.php?page=backtesting" class="mat-rp-textlink">Consultar todos os testes</a></div></footer>
</div>
