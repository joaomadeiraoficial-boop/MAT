<?php
/**
 * MAT Final — caderno comparativo de fórmulas.
 * Página de documentação: nenhuma conexão, gravação, migração ou cálculo de dados do banco.
 * Equações conferidas contra app/TemporisPotenciaAtual.php,
 * app/TemporisCuradoria.php e app/TemporisFinal.php.
 * Atualização: fundamentação matemática/científica adicionada sem alterar fórmulas, cálculos ou resultados.
 */
?>
<div class="mat-ed" id="metodo-caderno">

  <header class="mat-ed-hero">
    <div class="mat-ed-hero-top">
      <span class="mat-ed-eyebrow"><span class="mat-ed-dot" aria-hidden="true"></span>MAT / ÍNDICE TÊMPORIS / EVOLUÇÃO METODOLÓGICA</span>
      <span class="mat-ed-year">EDIÇÃO DE PESQUISA · MMXXVI</span>
    </div>
    <div class="mat-ed-hero-body">
      <div>
        <h1>Caderno <em>de fórmulas.</em></h1>
        <p>As seis expressões principais do projeto, organizadas por versão (Legada, Reformulada e Final): para cada uma, o peso do marco e a regra de cálculo do índice. Aqui você encontra <strong>como</strong> cada fórmula funciona e <strong>por que</strong> foi escolhida, com suas bases teóricas e limitações.</p>
      </div>
      <div class="mat-ed-hero-mark" aria-hidden="true"><span>6</span><small>FÓRMULAS À VISTA</small></div>
    </div>
    <div class="mat-ed-hero-bottom">
      <span>Documentação · sem conexão ao banco</span>
      <span>Conferida contra <code>app/TemporisFinal.php</code></span>
      <span>Legada · Reformulada · Final</span>
    </div>
  </header>

  <div class="mat-ed-quote">
    <span>PRINCÍPIO CENTRAL</span>
    <blockquote>O objetivo não é produzir a curva mais inclinada, mas representar com clareza o avanço registrado e testar se a aceleração existe nos dados.</blockquote>
  </div>

  <nav class="mat-ed-nav" aria-label="Seções deste método">
    <a href="#metodo-pontuacao">01 — As 11 notas</a>
    <a href="#metodo-seis">02 — Seis fórmulas</a>
    <a href="#metodo-versoes">03 — Três versões</a>
    <a href="#metodo-comparar">04 — Comparação</a>
    <a href="#metodo-validacao">05 — Validação</a>
    <a href="#metodo-contexto">06 — Contexto científico</a>
    <a href="#metodo-referencias">07 — Referências</a>
  </nav>

  <section id="metodo-pontuacao" class="mat-ed-section" aria-labelledby="metodo-pontuacao-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">01 / BASE MATEMÁTICA · COMUM ÀS VERSÕES ADAPTADAS</span><h2 id="metodo-pontuacao-titulo">Das 11 rubricas ao <em>peso do marco</em></h2></div><span class="mat-ed-source-pill">Notas de 0 a 3</span></div>
    <p class="mat-ed-intro">Para cada marco histórico, as notas variam de <strong>0 a 3</strong> e são reunidas em três dimensões. Esta é a pontuação utilizada no snapshot independente do MAT Final; a curadoria do Refinado permanece separada.</p>
    <div class="mat-ed-steps">
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">04 rubricas — Impacto</span>
        <h4>I = 1 + 9S<sub>I</sub> / 12</h4>
        <p>Intensidade, alcance, difusão e persistência. <b>I ∈ [1, 10]</b>.</p>
      </div>
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">04 rubricas — Confiabilidade</span>
        <h4>C = S<sub>C</sub> / 12</h4>
        <p>Qualidade da documentação e da datação. <b>C ∈ [0, 1]</b>.</p>
      </div>
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">03 rubricas — Fundação</span>
        <h4>F = 1 + 0,5S<sub>F</sub> / 9</h4>
        <p>Relevância para outros avanços. <b>F ∈ [1, 1,5]</b>.</p>
      </div>
    </div>
    <details class="mat-ed-details" style="margin-top:13px"><summary>Mostrar as 11 rubricas utilizadas na curadoria</summary>
      <div class="mat-ed-details-body">
        <p><strong>Impacto (4):</strong> difusão, magnitude, alcance e persistência. <strong>Confiabilidade (4):</strong> rastreabilidade documental, independência das evidências, precisão da datação e consistência histórica. <strong>Fundação (3):</strong> aplicabilidade entre setores, potencial de aprimoramento e tecnologias complementares. Cada rubrica recebe uma nota de 0 a 3; a ausência de nota torna o candidato inelegível ao cálculo. A data também precisa ser utilizável.</p>
      </div>
    </details>
    <div class="mat-ed-note" style="margin-top:13px">
      <h3>Peso do marco — padrão atual</h3>
      <div class="mat-ed-formula">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
      <p>Depois, os pesos são somados até cada ano: <strong>K(t) = Σ w<sub>i</sub>, para t<sub>i</sub> ≤ t</strong>. A potência 2 dá mais destaque aos marcos com maior impacto. A confiabilidade aqui é uma <em>nota de curadoria</em>, não uma probabilidade estatística.</p>
    </div>
  </section>

  <section id="metodo-seis" class="mat-ed-section" aria-labelledby="metodo-seis-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">02 / CADERNO DE FÓRMULAS · TRÊS VERSÕES — DUAS EXPRESSÕES CADA</span><h2 id="metodo-seis-titulo">As seis fórmulas, <em>uma por vez</em></h2></div><span class="mat-ed-source-pill">3 pares × 2 expressões</span></div>
    <p class="mat-ed-intro">São <strong>seis expressões centrais</strong>, não seis evidências independentes de aceleração. A primeira de cada par atribui peso ao marco; a segunda transforma o acúmulo em índice. As fórmulas da Legada foram adaptadas ao sistema atual de 11 rubricas apenas para permitir comparação matemática.</p>

    <div class="mat-ed-cards mat-ed-six">
     <article class="mat-ed-card">
       <span class="mat-ed-card-label">01 — LEGADA / PESO</span>
       <strong>Potência do impacto</strong>
       <div class="mat-ed-formula">P<sub>i</sub> = I<sub>i</sub><sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
       <p>O impacto I é elevado ao quadrado. Na adaptação às rubricas atuais, P = 100 · w da formulação Final; isso não reconstrói as notas legadas de 1 a 5.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>Combina dimensões (impacto, confiabilidade e fundação) por <strong>ponderação multiplicativa</strong>, abordagem comum em avaliação multicritério/indicadores compostos, em que critérios interagem no resultado (OECD/JRC, 2008). Elevar o impacto ao quadrado atribui peso maior a marcos de alto impacto relativo, uma escolha de sensibilidade do modelo.</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>Indicadores compostos frequentemente agregam dimensões por soma ou produto conforme o grau desejado de compensação entre critérios; a multiplicação reduz compensação total e exige desempenho em múltiplas dimensões (OECD/JRC, 2008). A potência aplicada a um componente funciona como transformação de ênfase/escala dentro da modelagem (Box &amp; Cox, 1964, no sentido geral de transformações paramétricas).</p>
           <ul><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
         </details>
       </div>
     </article>

     <article class="mat-ed-card">
       <span class="mat-ed-card-label">02 — LEGADA / ÍNDICE</span>
       <strong>Compressão por potência</strong>
       <div class="mat-ed-formula">T<sub>L</sub>(t) = 100 · (K<sub>L</sub>(t) / K<sub>L</sub>(t₀))<sup>0,65</sup></div>
       <p>A elevação a 0,65 atenua visualmente as diferenças do estoque acumulado. Não prova que o avanço tenha desacelerado.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>Usa uma <strong>transformação de potência sublinear</strong> (expoente &lt; 1) para comprimir o crescimento numérico e facilitar a visualização de variações em grandes amplitudes, análoga em propósito a famílias de transformações de escala (Box &amp; Cox, 1964). A construção e comunicação de índices também envolvem escolhas de normalização/escala (OECD/JRC, 2008).</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>Transformações de potência podem reduzir assimetria e comprimir magnitudes elevadas; no MAT, isso afeta a leitura gráfica do acúmulo. <strong>Limitação:</strong> o expoente <strong>0,65 é uma escolha metodológica do MAT</strong>, não uma constante científica comprovada nem um resultado derivado diretamente das referências.</p>
           <ul><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
         </details>
       </div>
     </article>

     <article class="mat-ed-card">
       <span class="mat-ed-card-label">03 — REFORMULADA / PESO</span>
       <strong>Normalização de I</strong>
       <div class="mat-ed-formula">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
       <p>Produz uma contribuição não negativa com as mesmas dimensões de impacto, confiabilidade e relevância fundacional.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>A divisão de <em>I</em> por 10 <strong>padroniza/normaliza</strong> a escala do impacto para um intervalo comparável, prática recomendada na construção de indicadores compostos para tornar dimensões comensuráveis (OECD/JRC, 2008). Mantém a agregação multicritério e a ênfase em impactos maiores via potência.</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>A normalização é etapa central para agregação coerente de componentes e para transparência do indicador (OECD/JRC, 2008). Transformações de escala/potência são ferramentas gerais de modelagem (Box &amp; Cox, 1964), aqui aplicadas como decisão metodológica explícita do projeto.</p>
           <ul><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
         </details>
       </div>
     </article>

     <article class="mat-ed-card">
       <span class="mat-ed-card-label">04 — REFORMULADA / ÍNDICE</span>
       <strong>Transformação logarítmica</strong>
       <div class="mat-ed-formula">T<sub>R</sub>(t) = 100 · ln(1 + K(t)) / ln(1 + K(t₀))</div>
       <p>O logaritmo comprime os valores mais altos de K. A razão depende da escala absoluta do estoque, não apenas da proporção entre anos.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>O <strong>logaritmo</strong> é uma transformação clássica para comprimir grandes magnitudes e reduzir assimetrias de escala, facilitando comparações visuais em séries acumuladas (Box &amp; Cox, 1964). Em indicadores, transformações e normalizações alteram a leitura e devem ser justificadas e transparentes (OECD/JRC, 2008).</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>A forma <code>ln(1+K)</code> evita problema em K=0 e comprime progressivamente valores altos. <strong>Limitação:</strong> a transformação logarítmica diminui a interpretação proporcional direta do crescimento histórico (incrementos iguais em K não geram incrementos iguais em T).</p>
           <ul><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li><li><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">Box &amp; Cox (1964)</a></li></ul>
         </details>
       </div>
     </article>

     <article class="mat-ed-card tf-six-current">
       <span class="mat-ed-card-label">05 — FINAL / PESO ADOTADO</span>
       <strong>Contribuição do marco</strong>
       <div class="mat-ed-formula">w<sub>i</sub> = (I<sub>i</sub> / 10)<sup>2</sup> · C<sub>i</sub> · F<sub>i</sub></div>
       <p>Repete a regra de pesos da Reformulada: a mudança do método Final ocorre principalmente na transformação de K em índice.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>Mantém a <strong>avaliação multicritério normalizada</strong> da versão Reformulada: dimensões ponderadas/normalizadas e agregadas de forma explícita, alinhada a boas práticas de construção e documentação de indicadores compostos (OECD/JRC, 2008). A estabilidade do peso favorece comparabilidade entre versões do índice.</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>A separação entre (a) mensuração/ponderação dos componentes e (b) transformação do agregado em índice torna o método mais rastreável e auditável. A escolha dos expoentes e da forma multiplicativa permanece uma decisão metodológica do MAT, declarada e documentada.</p>
           <ul><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
         </details>
       </div>
     </article>

     <article class="mat-ed-card tf-six-current">
       <span class="mat-ed-card-label">06 — FINAL / ÍNDICE ADOTADO</span>
       <strong>Acúmulo bruto K(t)</strong>
       <div class="mat-ed-formula">K<sub>F</sub>(t) = Σ w<sub>i</sub>, com ano<sub>i</sub> ≤ t</div>
       <p>O índice principal é a soma dos pesos dos marcos até cada ano, sem precisar de ano-base. <strong>Opcional:</strong> N<sub>b</sub>(t) = 100·K(t)/K(b) apenas para comparação em escala relativa, se K(b)&gt;0.</p>
       <div class="mat-ed-fund">
         <span>Por que essa fórmula? — Fundamentação científica</span>
         <p>A soma dos pesos é determinística para um catálogo congelado: não depende do ano de referência. A normalização por 1900 = 100 é apenas uma apresentação opcional (número-índice), e não controla nem melhora o crescimento. Essa distinção explicita o que foi medido e facilita auditoria dos marcos.</p>
         <details><summary>Ver fundamentação e referências</summary>
           <p>No indicador bruto, cada novo marco de peso positivo aumenta K(t) exatamente pelo seu peso. A razão <code>K(t)/K(b)</code> preserva proporções somente quando solicitada como escala relativa. <strong>Limitação:</strong> acúmulo crescente não comprova aceleração e pode variar com o grau de cobertura da curadoria.</p>
           <ul><li><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">OECD/JRC (2008)</a></li></ul>
         </details>
       </div>
     </article>
    </div>

    <div class="mat-ed-note" style="margin-top:14px">
      <h3>Limitações identificadas na Formulação Reformulada</h3>
      <p>A Formulação Reformulada representou uma etapa importante na evolução do Índice Têmporis, ao introduzir uma transformação logarítmica destinada a controlar a escala do crescimento histórico.</p>
      <p>Entretanto, identificou-se uma limitação relevante: à medida que o estoque ponderado de marcos tecnológicos aumentava, a transformação reduzia progressivamente a variação da pontuação. Consequentemente, avanços acumulados ao longo de décadas ou séculos poderiam resultar em diferenças relativamente pequenas no índice.</p>
      <p>Essa característica comprometia um dos objetivos centrais do Têmporis: <strong>representar numericamente, de maneira gradual e interpretável, a evolução tecnológica ao longo do tempo</strong>, sem impor uma compressão excessiva à sua representação.</p>
      <p>Embora os testes anteriores tenham indicado erros percentuais próximos de 0,5% em determinadas configurações, esses resultados precisam ser interpretados com cautela. Um erro reduzido não demonstra, por si só, capacidade preditiva superior, especialmente quando a transformação utilizada diminui a amplitude das variações avaliadas.</p>
      <p>Diante dessa limitação, a metodologia atual adota diretamente o acumulado bruto K(t) como medida principal, mantendo a escala proporcional 1900 = 100 apenas para consulta comparativa opcional. A interpretação da aceleração exige observar diferenças temporais e velocidades V25/V50, não o nível acumulado isolado.</p>
      <p><strong>A proposta final busca conciliar dois objetivos: representar adequadamente a evolução tecnológica acumulada e permitir a construção de estimativas futuras, para horizontes de 10 a 100 anos, cuja confiabilidade deverá ser avaliada por meio de backtesting e métricas de erro.</strong></p>
      <p>A transição de uma transformação comprimida para o estoque bruto não garante previsão mais exata; permite medir incrementos na unidade original do catálogo e validar os modelos sem esconder erros em diferenças de escala.</p>
    </div>

    <details class="mat-ed-details" style="margin-top:12px"><summary><strong>Ver fundamentação e referências (visão geral das três versões)</strong></summary>
      <div class="mat-ed-details-body">
        <p><strong>Legada:</strong> agrega critérios por produto com ênfase no impacto (I²) e aplica compressão por potência sublinear (expoente 0,65). Baseia-se em ideias gerais de avaliação multicritério/indicadores compostos e transformações de escala (OECD/JRC, 2008; Box &amp; Cox, 1964). <em>Limitação:</em> 0,65 é escolha metodológica do MAT, não constante científica comprovada.</p>
        <p><strong>Reformulada:</strong> normaliza o impacto (I/10) e adota índice logarítmico para comprimir magnitudes (OECD/JRC, 2008; Box &amp; Cox, 1964). <em>Limitação:</em> reduz a interpretação proporcional direta do crescimento histórico.</p>
        <p><strong>Final:</strong> mantém o peso normalizado multicritério e adota K(t) bruto como índice principal; a escala de base fixa é opcional (OECD/JRC, 2008; princípios de números-índice/mensuração proporcional). <em>Vantagens:</em> transparência, interpretabilidade e rastreabilidade. <em>Limitação:</em> o crescimento do índice não comprova, isoladamente, aceleração tecnológica.</p>
      </div>
    </details>

    <p class="mat-ed-table-note" style="margin-top:12px">As rubricas usam 4 notas de impacto, 4 de confiabilidade e 3 de fundamento. Fórmulas alternativas de sensibilidade (impacto linear, geométrica, média; potência 1,25) são experimentos adicionais — não substituem estas seis.</p>
  </section>

  <section id="metodo-versoes" class="mat-ed-section" aria-labelledby="metodo-versoes-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">03 / CONSULTAR VERSÕES</span><h2 id="metodo-versoes-titulo">Onde cada fórmula é <em>aplicada?</em></h2></div><span class="mat-ed-source-pill">Páginas históricas preservadas</span></div>
    <p class="mat-ed-intro">As páginas históricas continuam disponíveis para consulta. O método adotado é o <strong>Final</strong>, e as demais versões não foram apagadas.</p>
    <div class="mat-ed-version-links">
      <a class="mat-ed-version" href="<?=mat_url('temporis_legado')?>"><small>Versão 01</small><strong>Legada</strong><span>Potência 0,65 — implementação histórica →</span></a>
      <a class="mat-ed-version" href="<?=mat_url('temporis_reformulado')?>"><small>Versão 02</small><strong>Reformulada</strong><span>Transformação logarítmica — versão anterior →</span></a>
      <a class="mat-ed-version mat-ed-version-current" href="<?=mat_url('temporis')?>"><small>Versão 03 — Atual</small><strong>MAT Final</strong><span>Acumulado bruto K(t) — análise em destaque →</span></a>
    </div>
    <p class="mat-ed-table-note" style="margin-top:12px">A medida bruta K(t) não precisa de ano-base; as versões relativas exigem K(t₀) positivo. Para comparar versões, mantenha constantes o conjunto de marcos, as datas, a pontuação e o ano-base. As telas históricas do sistema podem usar bases de dados diferentes; resultados de bases distintas não devem ser comparados diretamente.</p>
  </section>

  <section id="metodo-comparar" class="mat-ed-section" aria-labelledby="metodo-comparar-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">04 / LEITURA COMPARATIVA · MESMO CONJUNTO — DIFERENTES ESCALAS</span><h2 id="metodo-comparar-titulo">O que cada versão permite <em>concluir?</em></h2></div><span class="mat-ed-source-pill">2 tabelas comparativas</span></div>
    <p class="mat-ed-intro">A tabela abaixo resume o impacto de cada formulação na <strong>representação da evolução tecnológica</strong> (pontuação do índice ao longo do tempo) e na <strong>capacidade/comportamento de previsão</strong>.</p>
    <div class="mat-ed-table-wrap"><table class="mat-ed-table">
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
        <tr class="mat-ed-featured-row">
          <th scope="row">Final</th>
          <td>Acúmulo bruto K(t) = soma dos pesos dos marcos; escala relativa opcional apenas para exibição.</td>
          <td>Os modelos projetam K(t) bruto e são avaliados em cortes históricos cegos por WAPE dos incrementos. Não há percentual de acerto futuro garantido.</td>
          <td>Mensuração bruta, taxa de crescimento e predição são operações independentes, com rastreabilidade dos marcos.</td>
        </tr>
      </tbody>
    </table></div>
    <div class="mat-ed-emphasis" style="margin-top:14px"><span class="mat-ed-emphasis-icon" aria-hidden="true">✓</span><p><strong>Conclusão:</strong> uma fórmula não deve ser considerada melhor apenas porque apresenta o menor erro de previsão, mas também pela sua capacidade de representar adequadamente a evolução tecnológica ao longo do tempo.</p></div>

    <div class="mat-ed-table-wrap" style="margin-top:18px"><table class="mat-ed-table">
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
        <tr class="mat-ed-featured-row">
          <th scope="row">MAT Final</th>
          <td><strong>K(t) = Σw</strong>; base 100 opcional</td>
          <td>w<sub>i</sub>=(I<sub>i</sub>/10)<sup>2</sup>·C<sub>i</sub>·F<sub>i</sub> (normalizado)</td>
          <td>Soma ponderada por data, independente da escolha de ano-base.</td>
          <td>Maior transparência, interpretabilidade e rastreabilidade matemática.</td>
          <td>Crescimento do índice não comprova, isoladamente, aceleração tecnológica.</td>
          <td>Padrão experimental</td>
        </tr>
      </tbody>
    </table></div>
    <div class="mat-ed-note" style="margin-top:14px">
      <h3>Impacto das formulações na representação e na previsão</h3>
      <p>Além da forma matemática, cada formulação afeta <strong>como a evolução tecnológica é representada pela pontuação do Índice Têmporis</strong> e <strong>como as previsões se comportam</strong>. Avaliar uma fórmula exige considerar esses dois aspectos, não apenas uma métrica isolada de erro.</p>
      <p><strong>Formulação Legada:</strong> foi a primeira tentativa de representar esse crescimento por meio da pontuação, mas ainda apresentava limitações na forma como o índice acompanhava a evolução tecnológica acumulada.</p>
      <p><strong>Formulação Reformulada:</strong> buscou aprimorar esse comportamento e a precisão das previsões. Contudo, sua transformação logarítmica comprimia excessivamente o crescimento do índice: mesmo com o passar de décadas ou séculos e a inclusão de novos avanços, a pontuação aumentava muito pouco. Em determinados testes, isso levou a erros de previsão muito baixos (aproximadamente 0,5%), em parte porque os valores variavam pouco — criando uma aparente precisão, mas prejudicando a representação do avanço tecnológico.</p>
      <p><strong>Formulação Final:</strong> procura equilibrar os dois objetivos — representar de maneira mais fiel e proporcional o crescimento tecnológico no Têmporis e, ao mesmo tempo, permitir previsões mais sofisticadas e significativas. O erro deve ser medido nos incrementos históricos com cortes temporais reproduzíveis; valores como 30% de WAPE são retrospectivos e não constituem margem garantida para previsões futuras.</p>
      <p><strong>Conclusão:</strong> uma fórmula não deve ser considerada melhor apenas porque apresenta o menor erro de previsão, mas também pela sua capacidade de representar adequadamente a evolução tecnológica ao longo do tempo.</p>
    </div>
    <div class="mat-ed-emphasis" style="margin-top:14px"><span class="mat-ed-emphasis-icon" aria-hidden="true">↗</span><p><strong>Comparação com dados atualizados:</strong> os valores variam conforme a fonte escolhida, as datas e as notas revisadas. Consulte a <a href="<?=h(mat_url('temporis'))?>">série atual do Têmporis</a>. Evitamos exibir contagens fixas como se descrevessem qualquer banco conectado.</p></div>
  </section>

  <section id="metodo-validacao" class="mat-ed-section" aria-labelledby="metodo-validacao-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">05 / COMO TESTAMOS A HIPÓTESE · MENSURAÇÃO ≠ PREVISÃO</span><h2 id="metodo-validacao-titulo">Medir, investigar e prever são <em>etapas diferentes</em></h2></div><span class="mat-ed-source-pill">4 etapas</span></div>
    <div class="mat-ed-steps">
      <div class="mat-ed-step"><span class="mat-ed-step-k">01 / Medir</span><h3>Índice acumulado</h3><p>T(t) registra os pesos dos marcos até cada época. Por construção, acumula contribuições não negativas.</p></div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">02 / Investigar</span><h3>Velocidade e aceleração</h3><p>V = ΔT / Δt. Comparamos as velocidades entre períodos para verificar mudança no ritmo, sem impor uma curva acelerada.</p></div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">03 / Prever</span><h3>Modelos concorrentes</h3><p>Linear, exponencial, aceleração local, ímpeto, logístico e Gompertz tentam estimar K futuro e são avaliados separadamente.</p></div>
      <div class="mat-ed-step"><span class="mat-ed-step-k">04 / Validar</span><h3>Robustez histórica</h3><p>Testes temporais, anos validados, notas provisórias, duplicatas e fontes independentes. Um erro baixo não valida o índice por si só.</p></div>
    </div>
    <div class="mat-ed-note" style="margin-top:14px" role="note"><p><strong>Limite científico atual.</strong> O conjunto amplo pode incluir marcos aprovados e candidatos ainda com <code>SEM_EVIDENCIA</code>, notas mínimas provisórias ou anos extraídos. <strong>Aprovação de um dossiê não é validação científica.</strong> A aceleração tecnológica mundial permanece uma hipótese em avaliação, não uma conclusão estabelecida.</p></div>
  </section>

  <section id="metodo-contexto" class="mat-ed-section" aria-labelledby="metodo-contexto-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">06 / CONTEXTUALIZAÇÃO CIENTÍFICA · FUNDAMENTOS, CONTRAPONTOS E LIMITES</span><h2 id="metodo-contexto-titulo">Em que debate o Têmporis <em>se insere</em></h2></div><span class="mat-ed-source-pill">Conceitual, não validação</span></div>
    <div class="mat-ed-note" style="margin-top:0">
      <p>O <strong>Têmporis</strong> dialoga com trabalhos que discutem marcos históricos, padrões de evolução e mensuração da inovação. <strong>Modis (2002)</strong> analisa marcos históricos e padrões na evolução da complexidade/mudança ao longo do tempo, oferecendo contexto para o estudo de séries históricas de eventos relevantes. Em contraponto, <strong>Huebner (2005)</strong> apresenta uma interpretação sobre possível tendência de declínio na taxa de inovação mundial, lembrando que diferentes leituras dos dados são possíveis.</p>
      <p>Do ponto de vista metodológico, o <strong>OECD/JRC (2008)</strong> fornece diretrizes para construção, ponderação, normalização e validação de indicadores compostos — base para explicitar escolhas, transparência e limitações do índice. <strong>Box &amp; Cox (1964)</strong> fundamentam, de forma geral, o uso de transformações (como potência e logaritmo) para alterar escala/comportamento de variáveis; no MAT, tais transformações são empregadas como decisões de modelagem.</p>
      <p><strong>Importante:</strong> o Têmporis é um <strong>índice composto experimental</strong>, e não uma lei universal comprovada do progresso tecnológico. As referências citadas oferecem suporte conceitual/metodológico ao debate e às escolhas de modelagem, mas <strong>não validam diretamente</strong> o Índice Têmporis nem seus resultados específicos.</p>
    </div>
    <div class="mat-ed-cards" style="margin-top:14px">
      <div class="mat-ed-card"><span class="mat-ed-card-label">Contexto · Modis (2002)</span><strong>Marcos históricos e padrões de evolução</strong><p>Inspiração para analisar marcos e padrões temporais de mudança/complexidade. Não valida a seleção, as notas ou os resultados do MAT.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Contraponto · Huebner (2005)</span><strong>Hipótese de possível desaceleração</strong><p>Interpretação alternativa sobre a inovação mundial, útil para evitar conclusões únicas e manter hipóteses concorrentes em avaliação.</p></div>
      <div class="mat-ed-card"><span class="mat-ed-card-label">Metodologia · OECD/JRC (2008)</span><strong>Indicadores compostos</strong><p>Referência para construção, ponderação, normalização e validação de indicadores — sustenta a documentação das escolhas do Têmporis, sem validar seus achados.</p></div>
    </div>
  </section>

  <section id="metodo-referencias" class="mat-ed-section" aria-labelledby="metodo-referencias-titulo">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">07 / REFERÊNCIAS BIBLIOGRÁFICAS · LINKS CLICÁVEIS</span><h2 id="metodo-referencias-titulo">Referências <em>científicas</em></h2></div><span class="mat-ed-source-pill">5 obras</span></div>
    <p class="mat-ed-intro">As referências abaixo sustentam aspectos conceituais e metodológicos citados nesta página (indicadores compostos, normalização, transformações e debate sobre inovação). Elas não constituem validação direta do Índice Têmporis.</p>
    <ol class="mat-ed-refs" style="border:1px solid #e9eee7;border-radius:12px;overflow:hidden">
      <li>Modis, T. (2002). <em>Forecasting the growth of complexity and change</em>. Technological Forecasting and Social Change. <br><a href="https://doi.org/10.1016/S0040-1625(01)00172-X" target="_blank" rel="noopener">https://doi.org/10.1016/S0040-1625(01)00172-X</a></li>
      <li>Huebner, J. (2005). <em>A possible declining trend for worldwide innovation</em>. Technological Forecasting and Social Change. <br><a href="https://doi.org/10.1016/j.techfore.2005.01.003" target="_blank" rel="noopener">https://doi.org/10.1016/j.techfore.2005.01.003</a></li>
      <li>OECD/JRC (2008). <em>Handbook on Constructing Composite Indicators: Methodology and User Guide</em>. <br><a href="https://doi.org/10.1787/9789264043466-en" target="_blank" rel="noopener">https://doi.org/10.1787/9789264043466-en</a></li>
      <li>Box, G. E. P.; Cox, D. R. (1964). <em>An Analysis of Transformations</em>. Journal of the Royal Statistical Society: Series B (Methodological). <br><a href="https://doi.org/10.1111/j.2517-6161.1964.tb00553.x" target="_blank" rel="noopener">https://doi.org/10.1111/j.2517-6161.1964.tb00553.x</a></li>
      <li>OECD/Eurostat (2018). <em>Oslo Manual 2018: Guidelines for Collecting, Reporting and Using Data on Innovation</em> (4th ed.). <br><a href="https://doi.org/10.1787/9789264304604-en" target="_blank" rel="noopener">https://doi.org/10.1787/9789264304604-en</a></li>
    </ol>
    <div class="mat-ed-note" style="margin-top:14px" role="note"><p><strong>Nota de uso das referências.</strong> As citações foram incluídas para embasar escolhas de modelagem (multicritério, normalização, transformações e base fixa) e contextualizar o debate científico. Nenhuma delas comprova isoladamente a validade empírica do Índice Têmporis ou a existência de aceleração tecnológica universal.</p></div>
  </section>

  <footer class="mat-ed-footnote">
    <p><strong>Encaminhamento:</strong> <a href="<?=h(mat_url('temporis'))?>">Abrir o índice atualizado</a> com a fonte identificada. <a href="<?=h(mat_url('backtesting'))?>">Conferir o backtesting</a> dos modelos de previsão em uma página separada. Este caderno é documentação estática: não conecta, grava ou altera dados.</p>
    <div class="mat-ed-actions">
      <a class="mat-ed-button" href="<?=mat_url('demonstracao_formulas')?>">Demonstrativo das fórmulas →</a>
      <a class="mat-ed-button" href="<?=mat_url('temporis')?>">Índice Têmporis atual →</a>
      <a class="mat-ed-textlink" href="<?=mat_url('backtesting')?>">Teste das previsões</a>
    </div>
  </footer>
</div>
