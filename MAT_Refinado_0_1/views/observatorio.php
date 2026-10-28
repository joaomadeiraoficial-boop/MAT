<?php $last=array_slice(all_markers(),-4);$recent=array_reverse($last);$categoryEntries=array_slice($stats['categories'],0,4,true);$maxCat=max(1,...array_values($stats['categories'])); ?>
<div class="opening-grid">
 <section class="hero-intro">
   <div class="section-overline"><span class="overline-square"></span> PROJETO MAT <span class="overline-sep">/</span> UM INVENTÁRIO DA MUDANÇA</div>
   <h1>O tempo deixa<br><em>vestígios.</em><span class="hero-caret">_</span></h1>
   <p class="hero-lead">E se pudéssemos ler a história da tecnologia não apenas como uma sequência de invenções, mas como um fenômeno passível de investigação?</p>
   <div class="hero-actions"><a class="btn btn-dark" href="<?=mat_url('linha')?>">Percorrer a história <span>↗</span></a><a class="btn btn-ghost" href="<?=mat_url('metodo')?>">Conheça a pesquisa <span>↗</span></a></div>
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
<section class="metric-band" aria-label="Indicadores do acervo">
 <div class="metric-cell"><span class="metric-label">01 / MARCOS DOCUMENTADOS</span><strong><?=short_num($stats['total'])?></strong><small>Registros no acervo <?=site_mode()==='demonstracao'?'ilustrativo':'local'?></small></div>
 <div class="metric-cell"><span class="metric-label">02 / AMPLITUDE HISTÓRICA</span><strong class="metric-date"><?=$stats['min_year']===null?'—':yfmt((int)$stats['min_year'])?></strong><small>Primeiro ponto da cronologia</small></div>
 <div class="metric-cell"><span class="metric-label">03 / EVIDÊNCIAS VALIDADAS</span><strong><?=short_num($stats['states']['aprovado'])?></strong><small>Status aprovado; inclusão no índice exige mais critérios</small></div>
 <div class="metric-cell"><span class="metric-label">04 / CAMPOS DE ESTUDO</span><strong><?=count($stats['categories'])?></strong><small>Áreas com registros</small></div>
</section>
<div class="editorial-head"><div><div class="section-overline">LEITURA DE ACERVO <span class="overline-sep">/</span> 02</div><h2>Uma história em <em>camadas.</em></h2></div><a class="text-link" href="<?=mat_url('atlas')?>">Ver atlas completo <span>↗</span></a></div>
<div class="two-column">
 <section class="paper-panel cat-panel"><div class="panel-head"><span>MAPA DE ÁREAS</span><span class="index-sign">FIG. 01 — A</span></div>
    <p class="panel-description">Distribuição dos registros segundo o campo tecnológico. Uma visão do <strong>acervo disponível</strong>, não uma medição da aceleração.</p>
    <div class="cat-bars">
    <?php foreach($categoryEntries as $name=>$num):?><a class="cat-bar-item" href="<?=mat_url('acervo',['categoria'=>$name])?>"><span><?=h($name)?></span><div class="cat-track"><i style="width:<?=max(6,round(100*$num/$maxCat))?>%"></i></div><strong><?=$num?></strong></a><?php endforeach;?>
    </div>
    <div class="panel-foot">FONTE: BASE MAT <span><?=site_mode()==='demonstracao'?'● EXEMPLOS NÃO AUDITADOS':'● BASE LOCAL'?></span></div>
 </section>
 <section class="quote-panel"><div class="quote-mark">“</div><p>Não procuramos provar que a tecnologia acelera. Procuramos reunir evidências para descobrir <em>como ela se comporta.</em></p><div class="quote-author"><span class="ornament-line"></span> PREMISSA METODOLÓGICA · MAT</div><span class="quote-number">02</span></section>
</div>
<div class="editorial-head editorial-head-late"><div><div class="section-overline">REGISTROS EM DESTAQUE <span class="overline-sep">/</span> 03</div><h2>Fragmentos do <em>amanhã.</em></h2></div><a class="text-link" href="<?=mat_url('acervo')?>">Abrir o acervo <span>↗</span></a></div>
<div class="record-grid">
 <?php foreach($recent as $i=>$m):?><a class="record-card" href="<?=mat_url('marco',['id'=>$m['id']])?>"><div class="record-card-head"><span class="record-numeral">REG. <?=str_pad((string)$m['id'],3,'0',STR_PAD_LEFT)?></span><span class="record-arrow">↗</span></div><div class="record-card-year"><?=yfmt((int)$m['ano'])?></div><h3><?=h($m['titulo'])?></h3><p><?=h($m['descricao'])?></p><div class="record-card-tail"><span class="category-dot <?=cat_tone((string)$m['categoria'])?>"></span><?=h($m['categoria'])?><span class="tiny-tag"><?=status_text($m['status'])?></span></div></a><?php endforeach;?>
</div>
<section class="method-ribbon"><div><span>O PRÓXIMO CAPÍTULO</span><h3>Têmporis: quando a história encontra uma hipótese matemática.</h3></div><p>O cálculo é experimental, os dados precisam de crítica e a previsão ainda será construída. Nosso compromisso é tornar cada etapa visível.</p><a class="btn btn-outline-light" href="<?=mat_url('temporis')?>">Abrir laboratório ↗</a></section>
