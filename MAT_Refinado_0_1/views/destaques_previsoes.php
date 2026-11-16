<?php
/**
 * MAT · Vitrine de resultados históricos de previsão (50 anos).
 * Os números são registros históricos das bases indicadas, não leituras da curadoria atual.
 * A base de 12/11 consta de um ensaio relatado anteriormente, ainda não reexecutado aqui.
 * O snapshot 07/11 tem reprodução auditável por TemporisFinal no ensaio de 12 cortes.
 * Tela propositalmente independente de banco para que o pesquisador sempre veja os resultados.
 */
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

  <footer class="mat-rp-footnote"><span class="mat-rp-overline">COMO LER</span><p><strong>Erro da previsão</strong> compara o aumento previsto de K ao aumento histórico registrado; <strong>aumento real</strong> é a variação percentual de K entre os dois anos. São medidas diferentes. Os três melhores casos foram destacados para apresentação, mas o desempenho geral deve ser avaliado pelos 12 cortes e seu WAPE. As bases de 07/11 e 12/11 não são intercambiáveis.</p><div class="mat-rp-actions"><a href="index.php?page=projecoes" class="mat-rp-button">Abrir previsões completas <span aria-hidden="true">↗</span></a><a href="index.php?page=backtesting" class="mat-rp-textlink">Consultar todos os testes</a></div></footer>
</div>
