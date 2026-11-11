<?php
require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
$matCurDb=CuradoriaRodadas::connect();
$matProgress=null;
if($matCurDb){try{$matProgress=CuradoriaRodadas::overview($matCurDb);}catch(Throwable $e){error_log('MAT: leitura da origem no Observatório: '.$e->getMessage());}}
$matPanelOk=$matProgress!==null;
?>
<div class="opening-grid">
 <section class="hero-intro">
   <div class="section-overline"><span class="overline-square"></span> PROJETO MAT <span class="overline-sep">/</span> UM INVENTÁRIO DA MUDANÇA</div>
   <h1>O tempo deixa<br><em>vestígios.</em><span class="hero-caret">_</span></h1>
   <p class="hero-lead">E se pudéssemos ler a história da tecnologia não apenas como uma sequência de invenções, mas como um fenômeno passível de investigação?</p>
   <div class="hero-actions"><a class="btn btn-dark" href="<?=mat_url('linha')?>">Percorrer a história <span>↗</span></a><a class="btn btn-ghost" href="<?=mat_url('metodo')?>">Conheça a pesquisa <span>↗</span></a><a class="btn btn-ghost" href="<?=mat_url('temporis')?>">★ Têmporis · método novo <span>↗</span></a><a class="btn btn-ghost" href="mat_proj/">MAT-Proj · Projeções <span>↗</span></a></div>
   <div class="hero-footnote"><span class="ornament-line"></span> ENTRE A CRONOLOGIA E A MATEMÁTICA <span class="roman">I</span></div>
 </section>
 <section class="hero-art" aria-label="Ilustração abstrata da evolução temporal">
    <div class="art-grid"></div><div class="art-vertical">TECHNOLOGIA · MEMORIA · TEMPUS</div>
    <svg class="hero-orbits" viewBox="0 0 550 580" aria-hidden="true"><defs><linearGradient id="linefade" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#c69a6f"/><stop offset="1" stop-color="#efdfbb" stop-opacity=".18"/></linearGradient></defs>
       <circle cx="286" cy="273" r="206" fill="none" stroke="url(#linefade)" stroke-width="1"/><circle cx="286" cy="273" r="164" fill="none" stroke="#c5ae8a" stroke-opacity=".42"/><circle cx="286" cy="273" r="112" fill="none" stroke="#d0b58c" stroke-opacity=".6"/><circle cx="286" cy="273" r="58" fill="none" stroke="#edc394" stroke-opacity=".55"/>
       <path d="M74 376L482 165 M78 285H487 M285 48V500" stroke="#c69a6f" stroke-opacity=".4" stroke-dasharray="3 8"/>
       <path d="M94 390 C170 440 206 225 276 265 S402 50 478 142" fill="none" stroke="#dfae79" stroke-width="3" stroke-linecap="round"/>
       <path d="M112 428 C182 385 198 250 262 330 S425 228 480 100" fill="none" stroke="#a2ad8e" stroke-width="1.5" stroke-linecap="round"/>
       <circle cx="94" cy="390" r="6" fill="#e4b27f"/><circle cx="276" cy="265" r="5" fill="#e4b27f"/><circle cx="478" cy="142" r="7" fill="#f1d1a9"/><circle cx="286" cy="273" r="14" fill="#f4d6ac" stroke="#394746" stroke-width="8"/>
       <path d="M286 22L297 56 286 48 275 56Z" fill="#e4b27f"/><text x="298" y="68" font-family="Georgia" fill="#e9ccb0" font-size="17">N</text>
       <text x="80" y="500" font-family="Georgia" font-size="15" fill="#cdb995">I / ORIGEM</text><text x="392" y="503" font-family="Georgia" font-size="15" fill="#cdb995">II / FUTURO</text>
    </svg>
    <div class="art-title"><span>MAT</span><small>ESTUDO Nº 001<br>O MAPA NÃO É O TERRITÓRIO</small></div>
 </section>
</div>
<?php require __DIR__ . '/mat_acessos_inicio.php'; ?>
<section class="metric-band" aria-label="Indicadores do lote importado no MySQL">
 <div class="metric-cell"><span class="metric-label">01 / CANDIDATOS DO ESTUDO</span><strong><?=$matPanelOk?short_num($matProgress['total']):'—'?></strong><small>Fragmentos historicos para triagem, nao marcos aprovados</small></div>
 <div class="metric-cell"><span class="metric-label">02 / PESQUISADOS</span><strong><?=$matPanelOk?short_num($matProgress['pesquisados']):'—'?></strong><small>Dossiês documentais da rodada atual</small></div>
 <div class="metric-cell"><span class="metric-label">03 / PRÓXIMA RODADA</span><strong><?=$matPanelOk?short_num($matProgress['planejados']):'—'?></strong><small>Registros ainda não pesquisados, reservados na fila</small></div>
 <div class="metric-cell"><span class="metric-label">04 / 11 NOTAS PROPOSTAS</span><strong><?=$matPanelOk?short_num($matProgress['propostas_11']):'—'?></strong><small>11 criterios, ainda sem aprovacao</small></div>
</section>
<section class="mat-progress-preview" aria-label="Andamento científico do MAT">
 <div class="section-overline">ESTAÇÃO DE PESQUISA / ESTADO DAS FONTES</div>
 <div class="mat-progress-heading"><h2>Da coleta à <em>Têmporis.</em></h2><?php if(is_admin()):?><a href="<?=mat_url('andamento')?>">Abrir acompanhamento completo ↗</a><?php else:?><a href="<?=mat_url('laboratorio')?>">Conhecer o laboratório ↗</a><?php endif;?></div>
 <div class="mat-progress-grid">
  <div><span>Candidatos pesquisados</span><strong><?=$matPanelOk?short_num($matProgress['pesquisados']):'—'?></strong><small>Dossiês da rodada de pesquisa; sujeitos à auditoria.</small></div>
  <div><span>Candidatos na triagem</span><strong><?=$matPanelOk?short_num($matProgress['total']):'—'?></strong><small>Importados, ainda sem aprovação científica.</small></div>
  <div><span>Critérios com notas propostas</span><strong><?=$matPanelOk?short_num($matProgress['notas_propostas']):'—'?></strong><small>Notas com justificativas armazenadas no MySQL.</small></div>
  <div><span>Próxima rodada</span><strong><?=$matPanelOk?short_num($matProgress['planejados']):'—'?></strong><small>Fila registrada no banco, ainda sem pesquisa documental.</small></div>
 </div>
 <p class="mat-progress-note"><?php if($matPanelOk):?><?=short_num(($matProgress['total']-$matProgress['pesquisados']))?> candidatos ainda não foram pesquisados; <?=short_num($matProgress['propostas_11'])?> dossiês com 11 notas propostas aguardam auditoria.<?php else:?>A fila não está disponível. Confira MySQL e lote importado; não exibimos estimativas como dados reais.<?php endif;?> Nenhuma proposta de IA entra automaticamente na Têmporis oficial.</p>
</section>
<section class="paper-panel" style="margin-top:2rem;padding:2rem">
 <div class="section-overline">O OBJETIVO / CURADORIA DOCUMENTAL</div>
 <h2>Do fragmento a <em>uma nota explicável.</em></h2>
 <p>O lote documental registrado neste banco é a base de trabalho desta etapa. Os candidatos são documentados com onze rubricas, notas de 1 a 3 e indicação explícita de lacunas, sem pontuação automática por falta de evidência. O acervo anterior não participa desta fila.</p>
 <?php if(is_admin()): ?><a class="btn btn-dark" href="<?=mat_url('andamento')?>">Ver fila dos candidatos ↗</a><?php else: ?><a class="btn btn-dark" href="<?=mat_url('laboratorio')?>">Conhecer as rubricas ↗</a><?php endif; ?>
</section>
<section class="method-ribbon"><div><span>PESQUISA CIENTÍFICA</span><h3>Índice Têmporis · método final.</h3></div><p>Novo método: w = (I/10)² × C × F; K(t) soma os pesos até o ano e T(t) = 100 × K(t)/K(1900). A curadoria e as limitações científicas continuam explícitas.</p><a class="btn btn-outline-light" href="<?=mat_url('temporis')?>">Abrir método atual ↗</a></section>
