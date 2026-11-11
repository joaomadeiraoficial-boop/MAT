<?php
/**
 * MAT Final — caderno comparativo de fórmulas.
 * Página de documentação: nenhuma conexão, gravação, migração ou cálculo de dados do banco.
 * Equações conferidas contra app/TemporisPotenciaAtual.php,
 * app/TemporisCuradoria.php e app/TemporisFinal.php.
 * Atualização: fundamentação matemática/científica adicionada sem alterar fórmulas, cálculos ou resultados.
 */
?>
<header class="page-heading metodo-heading">
  <div class="section-overline">MAT / ÍNDICE TÊMPORIS / EVOLUÇÃO METODOLÓGICA</div>
  <h1>Caderno <em>de fórmulas.</em></h1>
  <p>As seis expressões principais do projeto, organizadas por versão (Legada, Reformulada e Final): para cada uma, o peso do marco e a regra de cálculo do índice. Aqui você encontra <strong>como</strong> cada fórmula funciona e <strong>por que</strong> foi escolhida, com suas bases teóricas e limitações.</p>
</header>

<div class="method-intro metodo-manifesto">
  <span>PRINCÍPIO CENTRAL</span>
  <blockquote>O objetivo não é produzir a curva mais inclinada, mas representar com clareza o avanço registrado e testar se a aceleração existe nos dados.</blockquote>
</div>

<nav class="metodo-jumps" aria-label="Seções deste método">
  <a href="#metodo-pontuacao">01 — As 11 notas</a>
  <a href="#metodo-seis">02 — Seis fórmulas</a>
  <a href="#metodo-versoes">03 — Três versões</a>
  <a href="#metodo-comparar">04 — Comparação</a>
  <a href="#metodo-validacao">05 — Validação</a>
  <a href="#metodo-contexto">06 — Contexto científico</a>
  <a href="#metodo-referencias">07 — Referências</a>
</nav>

<section id="metodo-pontuacao" class="paper-panel metodo-section" aria-labelledby="metodo-pontuacao-titulo">
  <div class="panel-head"><span>01 / BASE MATEMÁTICA</span><span>COMUM ÀS VERSÕES ADAPTADAS</span></div>
  <h2 id="metodo-pontuacao-titulo">Das 11 rubricas ao peso do marco</h2>
  <p>Para cada marco histórico, as notas variam de <strong>0 a 3</strong> e são reunidas em três dimensões. Esta é a pontuação utilizada no snapshot independente do MAT Final; a curadoria do Refinado permanece separada.</p>
  <div class="metodo-dimensoes">
    <div><span>04 RUBRICAS — IMPACTO</span><strong>I = 1 + 9S<sub>I</sub> / 12</strong><small>Intensidade, alcance, difusão e persistência. <b>I ∈ [1, 10]</b>.</small></div>
    <div><span>04 RUBRICAS — CONFIABILIDADE</span><strong>C = S<sub>C</sub> / 12</strong><small>Qualidade da documentação e da datação. <b>C ∈ [0, 1]</b>.</small></div>
    <div><span>03 RUBRICAS — FUNDAÇÃO</span><strong>F = 1 + 0,5S<sub>F</sub> / 9</strong><small>Relevância para outros avanços. <b>F ∈ [1, 1,5]</b>.</small></div>
  </div>
  <details class="tf-inline-details"><summary>Mostrar as 11 rubricas utilizadas na curadoria</summary>
  <p><strong>Impacto (4):</strong> difusão, magnitude, alcance e persistência. <strong>Confiabilidade (4):</strong> rastreabilidade documental, independência das evidências, precisão da datação e consistência histórica. <strong>Fundação (3):</strong> aplicabilidade entre setores, potencial de aprimoramento e tecnologias complementares. Cada rubrica recebe uma nota de 0 a 3; a ausência de nota torna o candidato inelegível ao cálculo. A data também precisa ser utilizável.</p>
  </details>
  <div class="metodo-equacao-principal">
    <span>PESO DO MARCO — PADRÃO ATUAL</span>
    <div class="metodo-equacao">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
    <p>Depois, os pesos são somados até cada ano: <strong>K(t) = Σ w<sub>i</sub>, para t<sub>i</sub> ≤ t</strong>. A potência 2 dá mais destaque aos marcos com maior impacto. A confiabilidade aqui é uma <em>nota de curadoria</em>, não uma probabilidade estatística.</p>
  </div>
</section>

<section id="metodo-seis" class="paper-panel metodo-section" aria-labelledby="metodo-seis-titulo">
<div class="panel-head"><span>02 / CADERNO DE FÓRMULAS</span><span>TRÊS VERSÕES — DUAS EXPRESSÕES CADA</span></div>
<h2 id="metodo-seis-titulo">As seis fórmulas, uma por vez</h2>
<p>São <strong>seis expressões centrais</strong>, não seis evidências independentes de aceleração. A primeira de cada par atribui peso ao marco; a segunda transforma o acúmulo em índice. As fórmulas da Legada foram adaptadas ao sistema atual de 11 rubricas apenas para permitir comparação matemática.</p>

<style>
  .tf-fund{margin:.55rem 0 0;padding:.55rem .65rem;background:#f8f6ef;border:1px solid #e6dfcf;border-radius:8px}
  .tf-fund h4{margin:0 0 .25rem;font-size:.86rem;letter-spacing:.02em;color:#3f4b46;text-transform:uppercase}
  .tf-fund p{margin:.15rem 0;font-size:.92rem;line-height:1.4;color:#34413c}
  .tf-fund details{margin-top:.35rem}
  .tf-fund summary{cursor:pointer;font-weight:600;color:#234b42}
  .tf-fund .refs-mini{margin:.35rem 0 0;padding-left:1.1rem}
  .tf-fund .refs-mini li{margin:.2rem 0;font-size:.9rem}
  .metodo-note-wide{background:#fbfaf6;border:1px solid #e7dfd0;border-radius:10px;padding:.8rem .95rem;margin-top:.8rem}
</style>

<div class="tf-six-grid">
 <article>
   <small>01 — LEGADA / PESO</small>
   <h3>Potência do impacto</h3>
   <div class="tf-formula">P<sub>i</sub> = I<sub>i</sub><sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
   <p>O impacto I é elevado ao quadrado. Na adaptação às rubricas atuais, P = 100 · w da formulação Final; isso não reconstrói as notas legadas de 1 a 5.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>Combina dimensões (impacto, confiabilidade e fundação) por <strong>ponderação multiplicativa</strong>, abordagem comum em avaliação multicritério/indicadores compostos, em que critérios interagem no resultado (OECD/JRC, 2008). Elevar o impacto ao quadrado atribui peso maior a marcos de alto impacto relativo, uma escolha de sensibilidade do modelo.</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>Indicadores compostos frequentemente agregam dimensões por soma ou produto conforme o grau desejado de compensação entre critérios; a multiplicação reduz compensação total e exige desempenho em múltiplas dimensões (OECD/JRC, 2008). A potência aplicada a um componente funciona como transformação de ênfase/escala dentro da modelagem (Box &amp; Cox, 1964, no sentido geral de transformações paramétricas).</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
     </details>
   </div>
 </article>

 <article>
   <small>02 — LEGADA / ÍNDICE</small>
   <h3>Compressão por potência</h3>
   <div class="tf-formula">T<sub>L</sub>(t) = 100 · (K<sub>L</sub>(t) / K<sub>L</sub>(t₀))<sup>0,65</sup></div>
   <p>A elevação a 0,65 atenua visualmente as diferenças do estoque acumulado. Não prova que o avanço tenha desacelerado.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>Usa uma <strong>transformação de potência sublinear</strong> (expoente &lt; 1) para comprimir o crescimento numérico e facilitar a visualização de variações em grandes amplitudes, análoga em propósito a famílias de transformações de escala (Box &amp; Cox, 1964). A construção e comunicação de índices também envolvem escolhas de normalização/escala (OECD/JRC, 2008).</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>Transformações de potência podem reduzir assimetria e comprimir magnitudes elevadas; no MAT, isso afeta a leitura gráfica do acúmulo. <strong>Limitação:</strong> o expoente <strong>0,65 é uma escolha metodológica do MAT</strong>, não uma constante científica comprovada nem um resultado derivado diretamente das referências.</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
     </details>
   </div>
 </article>

 <article>
   <small>03 — REFORMULADA / PESO</small>
   <h3>Normalização de I</h3>
   <div class="tf-formula">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
   <p>Produz uma contribuição não negativa com as mesmas dimensões de impacto, confiabilidade e relevância fundacional.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>A divisão de <em>I</em> por 10 <strong>padroniza/normaliza</strong> a escala do impacto para um intervalo comparável, prática recomendada na construção de indicadores compostos para tornar dimensões comensuráveis (OECD/JRC, 2008). Mantém a agregação multicritério e a ênfase em impactos maiores via potência.</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>A normalização é etapa central para agregação coerente de componentes e para transparência do indicador (OECD/JRC, 2008). Transformações de escala/potência são ferramentas gerais de modelagem (Box &amp; Cox, 1964), aqui aplicadas como decisão metodológica explícita do projeto.</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
     </details>
   </div>
 </article>

 <article>
   <small>04 — REFORMULADA / ÍNDICE</small>
   <h3>Transformação logarítmica</h3>
   <div class="tf-formula">T<sub>R</sub>(t) = 100 · ln(1 + K(t)) / ln(1 + K(t₀))</div>
   <p>O logaritmo comprime os valores mais altos de K. A razão depende da escala absoluta do estoque, não apenas da proporção entre anos.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>O <strong>logaritmo</strong> é uma transformação clássica para comprimir grandes magnitudes e reduzir assimetrias de escala, facilitando comparações visuais em séries acumuladas (Box &amp; Cox, 1964). Em indicadores, transformações e normalizações alteram a leitura e devem ser justificadas e transparentes (OECD/JRC, 2008).</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>A forma <code>ln(1+K)</code> evita problema em K=0 e comprime progressivamente valores altos. <strong>Limitação:</strong> a transformação logarítmica diminui a interpretação proporcional direta do crescimento histórico (incrementos iguais em K não geram incrementos iguais em T).</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
     </details>
   </div>
 </article>

 <article class="tf-six-current">
   <small>05 — FINAL / PESO ADOTADO</small>
   <h3>Contribuição do marco</h3>
   <div class="tf-formula">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
   <p>Repete a regra de pesos da Reformulada: a mudança do método Final ocorre principalmente na transformação de K em índice.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>Mantém a <strong>avaliação multicritério normalizada</strong> da versão Reformulada: dimensões ponderadas/normalizadas e agregadas de forma explícita, alinhada a boas práticas de construção e documentação de indicadores compostos (OECD/JRC, 2008). A estabilidade do peso favorece comparabilidade entre versões do índice.</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>A separação entre (a) mensuração/ponderação dos componentes e (b) transformação do agregado em índice torna o método mais rastreável e auditável. A escolha dos expoentes e da forma multiplicativa permanece uma decisão metodológica do MAT, declarada e documentada.</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
     </details>
   </div>
 </article>

 <article class="tf-six-current">
   <small>06 — FINAL / ÍNDICE ADOTADO</small>
   <h3>Acumulação proporcional</h3>
   <div class="tf-formula">T<sub>F</sub>(t) = 100 · K(t) / K(t₀)</div>
   <p>Se K(t) dobrar mantendo a mesma base, T também dobra. K(t) = Σ w<sub>i</sub> dos marcos datados até t; T fica indefinido quando K(t₀) = 0.</p>
   <div class="tf-fund">
     <h4>Por que essa fórmula? — Fundamentação científica</h4>
     <p>Aplica <strong>normalização de base fixa</strong> (número-índice): o ano-base vale 100 e valores posteriores expressam proporção direta do estoque ponderado em relação à base. Isso segue princípios de mensuração proporcional e transparência na construção de indicadores (OECD/JRC, 2008). <strong>Vantagens:</strong> maior transparência, interpretabilidade e rastreabilidade matemática.</p>
     <details><summary>Ver fundamentação e referências</summary>
       <p>A razão <code>K(t)/K(t₀)</code> preserva proporcionalidade: variações relativas do acúmulo são refletidas linearmente no índice. <strong>Limitação:</strong> o crescimento do índice não comprova, isoladamente, aceleração tecnológica; ele descreve o acúmulo ponderado segundo as regras e dados adotados.</p>
       <ul class="refs-mini"><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
     </details>
   </div>
 </article>
</div>

<div class="metodo-note-wide" style="margin-top:.9rem">
  <h3 style="margin:0 0 .45rem">Limitações identificadas na Formulação Reformulada</h3>
  <p>A Formulação Reformulada representou uma etapa importante na evolução do Índice Têmporis, ao introduzir uma transformação logarítmica destinada a controlar a escala do crescimento histórico.</p>
  <p>Entretanto, identificou-se uma limitação relevante: à medida que o estoque ponderado de marcos tecnológicos aumentava, a transformação reduzia progressivamente a variação da pontuação. Consequentemente, avanços acumulados ao longo de décadas ou séculos poderiam resultar em diferenças relativamente pequenas no índice.</p>
  <p>Essa característica comprometia um dos objetivos centrais do Têmporis: <strong>representar numericamente, de maneira gradual e interpretável, a evolução tecnológica ao longo do tempo</strong>, sem impor uma compressão excessiva à sua representação.</p>
  <p>Embora os testes anteriores tenham indicado erros percentuais próximos de 0,5% em determinadas configurações, esses resultados precisam ser interpretados com cautela. Um erro reduzido não demonstra, por si só, capacidade preditiva superior, especialmente quando a transformação utilizada diminui a amplitude das variações avaliadas.</p>
  <p>Diante dessa limitação, desenvolveu-se a Formulação Final, substituindo a transformação logarítmica por uma normalização proporcional. O objetivo foi preservar a relação entre o estoque histórico ponderado e a pontuação do índice, favorecendo a interpretação de seu crescimento.</p>
  <p><strong>A proposta final busca conciliar dois objetivos: representar adequadamente a evolução tecnológica acumulada e permitir a construção de estimativas futuras, para horizontes de 10 a 100 anos, cuja confiabilidade deverá ser avaliada por meio de backtesting e métricas de erro.</strong></p>
  <p>Assim, a transição da Reformulada para a Final não foi motivada exclusivamente pela redução do erro estatístico, mas também pela necessidade de preservar o significado histórico e quantitativo da pontuação apresentada.</p>
</div>

<details class="tf-inline-details" style="margin-top:.8rem"><summary><strong>Ver fundamentação e referências (visão geral das três versões)</strong></summary>
  <div class="metodo-note-wide">
    <p><strong>Legada:</strong> agrega critérios por produto com ênfase no impacto (I²) e aplica compressão por potência sublinear (expoente 0,65). Baseia-se em ideias gerais de avaliação multicritério/indicadores compostos e transformações de escala (OECD/JRC, 2008; Box &amp; Cox, 1964). <em>Limitação:</em> 0,65 é escolha metodológica do MAT, não constante científica comprovada.</p>
    <p><strong>Reformulada:</strong> normaliza o impacto (I/10) e adota índice logarítmico para comprimir magnitudes (OECD/JRC, 2008; Box &amp; Cox, 1964). <em>Limitação:</em> reduz a interpretação proporcional direta do crescimento histórico.</p>
    <p><strong>Final:</strong> mantém o peso normalizado multicritério e usa índice proporcional de base fixa (OECD/JRC, 2008; princípios de números-índice/mensuração proporcional). <em>Vantagens:</em> transparência, interpretabilidade e rastreabilidade. <em>Limitação:</em> o crescimento do índice não comprova, isoladamente, aceleração tecnológica.</p>
  </div>
</details>

<p class="metodo-rodape">As rubricas usam 4 notas de impacto, 4 de confiabilidade e 3 de fundamento. Fórmulas alternativas de sensibilidade (impacto linear, geométrica, média; potência 1,25) são experimentos adicionais — não substituem estas seis.</p>
</section>

<section id="metodo-versoes" class="metodo-section" aria-labelledby="metodo-versoes-titulo">
 <div class="metodo-section-top"><div class="section-overline">03 / CONSULTAR VERSÕES</div><h2 id="metodo-versoes-titulo">Onde cada fórmula é aplicada?</h2><p>As páginas históricas continuam disponíveis para consulta. O método adotado é o <strong>Final</strong>, e as demais versões não foram apagadas.</p></div>
 <div class="tf-version-links">
 <a href="<?=mat_url('temporis_legado')?>"><small>VERSÃO 01</small><strong>Legada</strong><span>Potência 0,65 — implementação histórica →</span></a>
 <a href="<?=mat_url('temporis_reformulado')?>"><small>VERSÃO 02</small><strong>Reformulada</strong><span>Transformação logarítmica — versão anterior →</span></a>
 <a class="tf-version-current" href="<?=mat_url('temporis')?>"><small>VERSÃO 03 — ATUAL</small><strong>MAT Final</strong><span>Acúmulo proporcional — análise em destaque →</span></a>
 </div>
 <p class="metodo-rodape">O índice só é normalizável com K(t₀) positivo. Para comparar versões, mantenha constantes o conjunto de marcos, as datas, a pontuação e o ano-base. As telas históricas do sistema podem usar bases de dados diferentes; resultados de bases distintas não devem ser comparados diretamente.</p>
</section>

<section id="metodo-comparar" class="paper-panel metodo-section" aria-labelledby="metodo-comparar-titulo">
  <div class="panel-head"><span>04 / LEITURA COMPARATIVA</span><span>MESMO CONJUNTO — DIFERENTES ESCALAS</span></div>
  <h2 id="metodo-comparar-titulo">O que cada versão permite concluir?</h2>
  <p>A tabela abaixo resume o impacto de cada formulação na <strong>representação da evolução tecnológica</strong> (pontuação do índice ao longo do tempo) e na <strong>capacidade/comportamento de previsão</strong>.</p>
  <div class="metodo-table-wrap"><table class="metodo-table">
    <thead>
      <tr>
        <th>Formulação</th>
        <th>Representação da evolução tecnológica (pontuação)</th>
        <th>Comportamento em previsões / erro observado</th>
        <th>Leitura principal</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">Legada</th>
        <td>Primeira tentativa de representar o crescimento; ainda apresentava limitações na forma como a pontuação acompanhava a evolução tecnológica acumulada.</td>
        <td>Capacidade preditiva limitada pelo comportamento da própria representação (sem equilíbrio consolidado entre escala e variação).</td>
        <td>Base inicial, com restrições na expressão do avanço ao longo do tempo.</td>
      </tr>
      <tr>
        <th scope="row">Reformulada</th>
        <td>A transformação logarítmica comprimia excessivamente o crescimento: mesmo com décadas/séculos e novos avanços, a pontuação aumentava muito pouco, prejudicando a representação do avanço tecnológico.</td>
        <td>Em determinados testes, erros de previsão muito baixos (aprox. 0,5%) em parte porque os valores variavam pouco — aparente precisão, mas pouco informativa sobre a variação real do crescimento.</td>
        <td>Boa aparência estatística em alguns cenários, às custas da representatividade histórica.</td>
      </tr>
      <tr class="metodo-table-destaque">
        <th scope="row">Final</th>
        <td>Normalização proporcional que busca representar de forma mais fiel e proporcional o crescimento tecnológico acumulado na pontuação do Têmporis.</td>
        <td>Permite previsões mais significativas; dependendo do cenário, pode apresentar margens de erro maiores (ex.: próximas de 30%), sem sacrificar a representação do crescimento.</td>
        <td>Melhor equilíbrio entre representatividade do avanço e avaliação preditiva (a ser verificada por backtesting e métricas de erro).</td>
      </tr>
    </tbody>
  </table></div>
  <p style="margin:.6rem 0 .2rem"><strong>Conclusão:</strong> uma fórmula não deve ser considerada melhor apenas porque apresenta o menor erro de previsão, mas também pela sua capacidade de representar adequadamente a evolução tecnológica ao longo do tempo.</p>

  <div class="metodo-table-wrap"><table class="metodo-table">
    <thead><tr><th>Versão</th><th>Transformação do índice</th><th>Peso do marco</th><th>Como representa o avanço?</th><th>Vantagem / observação</th><th>Limitação principal</th><th>Situação</th></tr></thead>
    <tbody>
      <tr>
        <th scope="row">Legada</th>
        <td>Potência 0,65: 100·(K/K<sub>t₀</sub>)<sup>0,65</sup></td>
        <td>P<sub>i</sub>=I<sub>i</sub><sup>2</sup>·C<sub>i</sub>·F<sub>i</sub> (adaptação comparativa)</td>
        <td>Comprime a variação do acúmulo para leitura em escala atenuada.</td>
        <td>Reduz dominância visual de valores muito altos.</td>
        <td>Expoente 0,65 é escolha metodológica do MAT (não constante comprovada); menor proporcionalidade direta.</td>
        <td>Comparação histórica</td>
      </tr>
      <tr>
        <th scope="row">Reformulada</th>
        <td>Logarítmica: 100·ln(1+K)/ln(1+K<sub>t₀</sub>)</td>
        <td>w<sub>i</sub>=(I<sub>i</sub>/10)<sup>2</sup>·C<sub>i</sub>·F<sub>i</sub></td>
        <td>Comprime progressivamente valores altos do acúmulo.</td>
        <td>Facilita visualização quando K cresce muito.</td>
        <td>Diminui a interpretação proporcional direta do crescimento histórico.</td>
        <td>Comparação histórica</td>
      </tr>
      <tr class="metodo-table-destaque">
        <th scope="row">MAT Final</th>
        <td>Razão direta: 100·K/K<sub>t₀</sub></td>
        <td>w<sub>i</sub>=(I<sub>i</sub>/10)<sup>2</sup>·C<sub>i</sub>·F<sub>i</sub> (normalizado)</td>
        <td>Mostra o aumento proporcional registrado em relação à base.</td>
        <td>Maior transparência, interpretabilidade e rastreabilidade matemática.</td>
        <td>Crescimento do índice não comprova, isoladamente, aceleração tecnológica.</td>
        <td>Padrão experimental</td>
      </tr>
    </tbody>
  </table></div>
  <div class="metodo-note-wide" style="margin-top:.8rem">
    <h3 style="margin:0 0 .45rem">Impacto das formulações na representação e na previsão</h3>
    <p>Além da forma matemática, cada formulação afeta <strong>como a evolução tecnológica é representada pela pontuação do Índice Têmporis</strong> e <strong>como as previsões se comportam</strong>. Avaliar uma fórmula exige considerar esses dois aspectos, não apenas uma métrica isolada de erro.</p>
    <p><strong>Formulação Legada:</strong> foi a primeira tentativa de representar esse crescimento por meio da pontuação, mas ainda apresentava limitações na forma como o índice acompanhava a evolução tecnológica acumulada.</p>
    <p><strong>Formulação Reformulada:</strong> buscou aprimorar esse comportamento e a precisão das previsões. Contudo, sua transformação logarítmica comprimia excessivamente o crescimento do índice: mesmo com o passar de décadas ou séculos e a inclusão de novos avanços, a pontuação aumentava muito pouco. Em determinados testes, isso levou a erros de previsão muito baixos (aproximadamente 0,5%), em parte porque os valores variavam pouco — criando uma aparente precisão, mas prejudicando a representação do avanço tecnológico.</p>
    <p><strong>Formulação Final:</strong> procura equilibrar os dois objetivos — representar de maneira mais fiel e proporcional o crescimento tecnológico no Têmporis e, ao mesmo tempo, permitir previsões mais sofisticadas e significativas. Dependendo do cenário analisado, seus modelos podem apresentar margens de erro maiores (por exemplo, próximas de 30%), o que pode ser preferível quando a representação do crescimento não é sacrificada apenas para reduzir o erro estatístico.</p>
    <p><strong>Conclusão:</strong> uma fórmula não deve ser considerada melhor apenas porque apresenta o menor erro de previsão, mas também pela sua capacidade de representar adequadamente a evolução tecnológica ao longo do tempo.</p>
  </div>

  <div class="tf-method-note"><strong>Comparação com dados atualizados:</strong> os valores variam conforme a fonte escolhida, as datas e as notas revisadas. Consulte a <a href="<?=h(mat_url('temporis'))?>">série atual do Têmporis</a>. Evitamos exibir contagens fixas como se descrevessem qualquer banco conectado.</div>
</section>

<section id="metodo-validacao" class="paper-panel metodo-section" aria-labelledby="metodo-validacao-titulo">
  <div class="panel-head"><span>05 / COMO TESTAMOS A HIPÓTESE</span><span>MENSURAÇÃO ≠ PREVISÃO</span></div>
  <h2 id="metodo-validacao-titulo">Medir, investigar e prever são etapas diferentes</h2>
  <div class="metodo-steps">
    <div><span>01 / MEDIR</span><h3>Índice acumulado</h3><p>T(t) registra os pesos dos marcos até cada época. Por construção, acumula contribuições não negativas.</p></div>
    <div><span>02 / INVESTIGAR</span><h3>Velocidade e aceleração</h3><p>V = ΔT / Δt. Comparamos as velocidades entre períodos para verificar mudança no ritmo, sem impor uma curva acelerada.</p></div>
    <div><span>03 / PREVER</span><h3>Modelos concorrentes</h3><p>Linear, exponencial, aceleração local, ímpeto, logístico e Gompertz tentam estimar K futuro e são avaliados separadamente.</p></div>
    <div><span>04 / VALIDAR</span><h3>Robustez histórica</h3><p>Testes temporais, anos validados, notas provisórias, duplicatas e fontes independentes. Um erro baixo não valida o índice por si só.</p></div>
  </div>
  <div class="metodo-aviso" role="note"><strong>Limite científico atual.</strong> O conjunto amplo pode incluir marcos aprovados e candidatos ainda com <code>SEM_EVIDENCIA</code>, notas mínimas provisórias ou anos extraídos. <strong>Aprovação de um dossiê não é homologação científica.</strong> A aceleração tecnológica mundial permanece uma hipótese em avaliação, não uma conclusão estabelecida.</div>
</section>

<section id="metodo-contexto" class="paper-panel metodo-section" aria-labelledby="metodo-contexto-titulo">
  <div class="panel-head"><span>06 / CONTEXTUALIZAÇÃO CIENTÍFICA</span><span>FUNDAMENTOS, CONTRAPONTOS E LIMITES</span></div>
  <h2 id="metodo-contexto-titulo">Em que debate o Têmporis se insere</h2>
  <div class="metodo-note-wide">
    <p>O <strong>Têmporis</strong> dialoga com trabalhos que discutem marcos históricos, padrões de evolução e mensuração da inovação. <strong>Modis (2002)</strong> analisa marcos históricos e padrões na evolução da complexidade/mudança ao longo do tempo, oferecendo contexto para o estudo de séries históricas de eventos relevantes. Em contraponto, <strong>Huebner (2005)</strong> apresenta uma interpretação sobre possível tendência de declínio na taxa de inovação mundial, lembrando que diferentes leituras dos dados são possíveis.</p>
    <p>Do ponto de vista metodológico, o <strong>OECD/JRC (2008)</strong> fornece diretrizes para construção, ponderação, normalização e validação de indicadores compostos — base para explicitar escolhas, transparência e limitações do índice. <strong>Box &amp; Cox (1964)</strong> fundamentam, de forma geral, o uso de transformações (como potência e logaritmo) para alterar escala/comportamento de variáveis; no MAT, tais transformações são empregadas como decisões de modelagem.</p>
    <p><strong>Importante:</strong> o Têmporis é um <strong>índice composto experimental</strong>, e não uma lei universal comprovada do progresso tecnológico. As referências citadas oferecem suporte conceitual/metodológico ao debate e às escolhas de modelagem, mas <strong>não validam diretamente</strong> o Índice Têmporis nem seus resultados específicos.</p>
  </div>
  <div class="reference-grid metodo-referencias" style="margin-top:.8rem">
    <div class="reference-card"><div class="panel-head"><span>CONTEXTO</span><span>MODIS (2002)</span></div><h3>Marcos históricos e padrões de evolução</h3><p>Inspiração para analisar marcos e padrões temporais de mudança/complexidade. Não valida a seleção, as notas ou os resultados do MAT.</p></div>
    <div class="reference-card"><div class="panel-head"><span>CONTRAPONTO</span><span>HUEBNER (2005)</span></div><h3>Hipótese de possível desaceleração</h3><p>Interpretação alternativa sobre a inovação mundial, útil para evitar conclusões únicas e manter hipóteses concorrentes em avaliação.</p></div>
    <div class="reference-card"><div class="panel-head"><span>METODOLOGIA</span><span>OECD/JRC (2008)</span></div><h3>Indicadores compostos</h3><p>Referência para construção, ponderação, normalização e validação de indicadores — sustenta a documentação das escolhas do Têmporis, sem validar seus achados.</p></div>
  </div>
</section>

<section id="metodo-referencias" class="paper-panel metodo-section" aria-labelledby="metodo-referencias-titulo">
  <div class="panel-head"><span>07 / REFERÊNCIAS BIBLIOGRÁFICAS</span><span>LINKS CLICÁVEIS</span></div>
  <h2 id="metodo-referencias-titulo">Referências científicas</h2>
  <p>As referências abaixo sustentam aspectos conceituais e metodológicos citados nesta página (indicadores compostos, normalização, transformações e debate sobre inovação). Elas não constituem validação direta do Índice Têmporis.</p>
  <ol class="metodo-refs-list" style="margin:.6rem 0 0; padding-left:1.2rem; line-height:1.55">
    <li style="margin:.45rem 0">Modis, T. (2002). <em>Forecasting the growth of complexity and change</em>. Technological Forecasting and Social Change. <br><a href="https://doi.org/10.1016/S0040-1625(01)00172-X" target="_blank" rel="noopener">https://doi.org/10.1016/S0040-1625(01)00172-X</a></li>
    <li style="margin:.45rem 0">Huebner, J. (2005). <em>A possible declining trend for worldwide innovation</em>. Technological Forecasting and Social Change. <br><a href="https://doi.org/10.1016/j.techfore.2005.01.003" target="_blank" rel="noopener">https://doi.org/10.1016/j.techfore.2005.01.003</a></li>
    <li style="margin:.45rem 0">OECD/JRC (2008). <em>Handbook on Constructing Composite Indicators: Methodology and User Guide</em>. <br><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">https://doi.org/10.1787/9789264043466-en</a></li>
    <li style="margin:.45rem 0">Box, G. E. P.; Cox, D. R. (1964). <em>An Analysis of Transformations</em>. Journal of the Royal Statistical Society: Series B (Methodological). <br><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">https://doi.org/10.1111/j.2517-6161.1964.tb00553.x</a></li>
    <li style="margin:.45rem 0">OECD/Eurostat (2018). <em>Oslo Manual 2018: Guidelines for Collecting, Reporting and Using Data on Innovation</em> (4th ed.). <br><a href="https://doi.org/10.1787/9789264304604-en" target="_blank" rel="noopener">https://doi.org/10.1787/9789264304604-en</a></li>
  </ol>
  <div class="metodo-aviso" role="note" style="margin-top:.8rem"><strong>Nota de uso das referências.</strong> As citações foram incluídas para embasar escolhas de modelagem (multicritério, normalização, transformações e base fixa) e contextualizar o debate científico. Nenhuma delas comprova isoladamente a validade empírica do Índice Têmporis ou a existência de aceleração tecnológica universal.</div>
</section>

<div class="reference-grid metodo-referencias">
  <div class="reference-card"><div class="panel-head"><span>BASE METODOLÓGICA</span><span>REF. A</span></div><h3>Theodore Modis — 2002</h3><p>Inspiração para investigar marcos históricos, intervalos e curvas matemáticas; não substitui a validação da seleção utilizada no MAT.</p></div>
  <div class="reference-card"><div class="panel-head"><span>CONTRAPONTO</span><span>REF. B</span></div><h3>Jonathan Huebner — 2005</h3><p>Uma interpretação crítica da evolução das inovações, usada para manter alternativas à hipótese de aceleração contínua.</p></div>
  <div class="reference-card"><div class="panel-head"><span>EXPERIMENTO ATUAL</span><span>MAT FINAL</span></div><h3>Análise atual e backtesting</h3><p><a href="<?=h(mat_url('temporis'))?>">Abrir o índice atualizado</a> com a fonte identificada. <a href="<?=h(mat_url('backtesting'))?>">Conferir o backtesting</a> dos modelos de previsão em uma página separada.</p></div>
</div>
