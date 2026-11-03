<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#101c1e">
<title><?=h($pageLabels[$page]??'MAT')?> · MAT — Versão Refinada 0.1</title>
<meta name="description" content="MAT: acervo histórico e laboratório para estudar a evolução tecnológica e o Índice Têmporis.">
<link rel="icon" href="assets/insignia.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/style.css?v=1.2">
<?php if(is_file(MAT_ROOT . '/assets/temporis-0-2.css')): ?><link rel="stylesheet" href="assets/temporis-0-2.css?v=1"><?php endif; ?>
<link rel="stylesheet" href="assets/progresso-marcos.css?v=1">
<link rel="stylesheet" href="assets/navegacao-grupos.css?v=1">
<link rel="stylesheet" href="assets/rodadas.css?v=1">
<script defer src="assets/app.js?v=1.3"></script>
</head>
<body class="page-<?=h($page)?>">
<a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
<div class="site-shell">
<aside class="rail" id="rail">
 <div class="rail-top">
  <a class="brand" href="index.php" aria-label="MAT — início">
   <span class="brand-sigil"><img src="assets/insignia.svg" alt="" width="40" height="40"></span>
   <span class="brand-wordmark"><strong>MAT<span class="brand-period">.</span></strong><small>OBSERVATÓRIO DO TEMPO</small></span>
  </a>
  <button class="mobile-close" id="navClose" type="button" aria-label="Fechar menu">×</button>
 </div>
 <?php
 require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
 $matRailCount=null;
 $matRailDb=CuradoriaRodadas::connect();
 if($matRailDb){try{
   $matRailCount=(int)$matRailDb->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
 }catch(Throwable $e){error_log('MAT: contador do menu indisponível');}}
 ?>
 <div class="rail-caption">DOSSIÊS EM CURADORIA <span><?=$matRailCount===null?'—':short_num($matRailCount)?></span></div>
 <?php
 // A navegação apresenta o mesmo acervo e as mesmas ferramentas, apenas agrupados.
 // A página ativa abre automaticamente o seu grupo (inclusive sem JavaScript).
 $matScript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
 $grupoHistoria = in_array($navPage, ['linha','atlas','acervo','galeria'], true);
 $grupoTemporis = in_array($navPage, ['temporis','metodo','laboratorio'], true)
     || in_array($matScript, ['experimento_temporis.php','resultado_temporis_piloto.php'], true);
 $grupoPesquisa = in_array($navPage, ['rodadas','andamento','importar','triagem','fontes','duplicatas','auditoria','editar'], true)
     || in_array($matScript, ['assistente_ia.php','triagem_300_reais.php'], true);
 ?>
 <nav class="mat-nav" aria-label="Menu principal">
  <a href="<?=mat_url('observatorio')?>" class="nav-item mat-nav-home <?=$navPage==='observatorio'?'active':''?>" <?=$navPage==='observatorio'?'aria-current="page"':''?>>
    <span class="nav-number">01</span><span>O Observatório</span><span class="nav-symbol" aria-hidden="true">↗</span>
  </a>
  <details class="mat-nav-group" data-nav-group="historia" <?=$grupoHistoria?'open':''?>>
   <summary><span class="nav-number">02</span><span>Exploração histórica</span><span class="mat-nav-chevron" aria-hidden="true">⌄</span></summary>
   <div class="mat-nav-submenu">
    <a href="<?=mat_url('linha')?>" class="nav-item <?=$navPage==='linha'?'active':''?>" <?=$navPage==='linha'?'aria-current="page"':''?>><span class="nav-number">02.1</span><span>Linha do tempo</span></a>
    <a href="<?=mat_url('atlas')?>" class="nav-item <?=$navPage==='atlas'?'active':''?>" <?=$navPage==='atlas'?'aria-current="page"':''?>><span class="nav-number">02.2</span><span>Atlas das eras</span></a>
    <a href="<?=mat_url('acervo')?>" class="nav-item <?=$navPage==='acervo'?'active':''?>" <?=$navPage==='acervo'?'aria-current="page"':''?>><span class="nav-number">02.3</span><span>Acervo de marcos</span></a>
    <a href="<?=mat_url('galeria')?>" class="nav-item <?=$navPage==='galeria'?'active':''?>" <?=$navPage==='galeria'?'aria-current="page"':''?>><span class="nav-number">02.4</span><span>Panfletos do acervo</span></a>
   </div>
  </details>
  <details class="mat-nav-group" data-nav-group="temporis" <?=$grupoTemporis?'open':''?>>
   <summary><span class="nav-number">03</span><span>Índice Têmporis</span><span class="mat-nav-chevron" aria-hidden="true">⌄</span></summary>
   <div class="mat-nav-submenu">
    <a href="<?=mat_url('temporis')?>" class="nav-item <?=$navPage==='temporis'?'active':''?>" <?=$navPage==='temporis'?'aria-current="page"':''?>><span class="nav-number">03.1</span><span>Têmporis · reformulada</span></a>
    <a href="<?=mat_url('metodo')?>" class="nav-item <?=$navPage==='metodo'?'active':''?>" <?=$navPage==='metodo'?'aria-current="page"':''?>><span class="nav-number">03.2</span><span>Caderno de método</span></a>
    <a href="<?=mat_url('laboratorio')?>" class="nav-item <?=$navPage==='laboratorio'?'active':''?>" <?=$navPage==='laboratorio'?'aria-current="page"':''?>><span class="nav-number">03.3</span><span>Laboratório científico</span></a>
    <a href="mat_proj/" class="nav-item"><span class="nav-number">03.4</span><span>MAT-Proj · Projeções</span></a>
    <?php if (is_admin() && is_file(MAT_ROOT . '/experimento_temporis.php')): ?>
     <a href="experimento_temporis.php" class="nav-item"><span class="nav-number">03.5</span><span>Backtesting · Têmporis 0.2</span></a>
    <?php endif; ?>
    <?php if (is_admin() && is_file(MAT_ROOT . '/resultado_temporis_piloto.php')): ?>
     <a href="resultado_temporis_piloto.php" class="nav-item"><span class="nav-number">03.6</span><span>Ensaio didático · 0.2</span></a>
    <?php endif; ?>
   </div>
  </details>
  <details class="mat-nav-group" data-nav-group="pesquisa" <?=$grupoPesquisa?'open':''?>>
   <summary><span class="nav-number">04</span><span>Pesquisa e curadoria</span><span class="mat-nav-chevron" aria-hidden="true">⌄</span></summary>
   <div class="mat-nav-submenu">
    <span class="mat-nav-subheading">ACOMPANHAMENTO</span>
    <a href="<?=mat_url('andamento')?>" class="nav-item <?=$navPage==='andamento'?'active':''?>" <?=$navPage==='andamento'?'aria-current="page"':''?>><span class="nav-glyph">◈</span><span>Progresso científico</span></a>
    <?php if(is_admin()): ?>
     <a href="<?=mat_url('rodadas')?>" class="nav-item <?=$navPage==='rodadas'?'active':''?>" <?=$navPage==='rodadas'?'aria-current="page"':''?>><span class="nav-glyph">◷</span><span>Rodadas de pesquisa</span></a>
    <?php endif; ?>
    <span class="mat-nav-subheading">INVESTIGAÇÃO</span>
    <a href="<?=mat_url('importar')?>" class="nav-item <?=$navPage==='importar'?'active':''?>" <?=$navPage==='importar'?'aria-current="page"':''?>><span class="nav-glyph">⇣</span><span>Importar cronologias</span></a>
    <a href="<?=mat_url('triagem')?>" class="nav-item <?=$navPage==='triagem'?'active':''?>" <?=$navPage==='triagem'?'aria-current="page"':''?>><span class="nav-glyph">◈</span><span>Triagem de escopo</span></a>
    <?php if (is_admin() && is_file(MAT_ROOT . '/triagem_300_reais.php')): ?>
      <a href="<?=mat_url('rodadas')?>" class="nav-item"><span class="nav-glyph">◎</span><span>Triagem dos candidatos</span></a>
    <?php endif; ?>
    <span class="mat-nav-subheading">DOCUMENTAÇÃO E REVISÃO</span>
    <a href="<?=mat_url('fontes')?>" class="nav-item <?=$navPage==='fontes'?'active':''?>" <?=$navPage==='fontes'?'aria-current="page"':''?>><span class="nav-glyph">✣</span><span>Fontes &amp; vínculos</span></a>
    <a href="<?=mat_url('duplicatas')?>" class="nav-item <?=$navPage==='duplicatas'?'active':''?>" <?=$navPage==='duplicatas'?'aria-current="page"':''?>><span class="nav-glyph">≋</span><span>Conferir duplicatas</span></a>
    <a href="<?=mat_url('auditoria')?>" class="nav-item <?=$navPage==='auditoria'?'active':''?>" <?=$navPage==='auditoria'?'aria-current="page"':''?>><span class="nav-glyph">☷</span><span>Auditoria</span></a>
    <?php if(is_admin()): ?>
     <a href="<?=mat_url('editar')?>" class="nav-item <?=$navPage==='editar'?'active':''?>" <?=$navPage==='editar'?'aria-current="page"':''?>><span class="nav-glyph">＋</span><span>Novo marco</span></a>
    <?php endif; ?>
   </div>
  </details>
  <!-- MAT_NAV_EXTENSIONS_V07_START -->
  <!-- Funções científicas de V07 mantidas nos grupos Índice Têmporis e Pesquisa e curadoria. -->
  <!-- MAT_NAV_EXTENSIONS_V07_END -->
 </nav>
 <div class="rail-divider mat-rail-account-divider"></div>
 <div class="rail-label">ACESSO AO OBSERVATÓRIO</div>
 <div class="nav-list nav-small mat-account-nav">
  <?php if(is_admin()): ?>
    <form class="nav-form" action="actions.php" method="post"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button class="nav-item" type="submit"><span class="nav-glyph">↪</span><span>Sair · <?=h(current_user()['nome'])?></span></button></form>
  <?php else: ?>
    <a href="<?=mat_url('login')?>" class="nav-item <?=$navPage==='login'?'active':''?>"><span class="nav-glyph">⌑</span><span>Acesso do pesquisador</span></a>
  <?php endif; ?>
 </div>
<div class="rail-bottom">
    <div class="rail-quote">“A história da humanidade é também a história das ferramentas que inventa.”</div>
    <div class="rail-foot"><span class="live-dot"></span> <?=site_mode()==='mysql'?'ACERVO CONECTADO':'BANCO DESCONECTADO'?><span>Refinada 0.1 · núcleo 6.2.1</span></div>
 </div>
</aside>
<div class="main-column">
<header class="topbar">
  <button class="menu-toggle" id="navOpen" type="button" aria-label="Abrir menu">☰</button>
  <?php
    $matBreadcrumbGroup = match($navPage) {
        'linha','atlas','acervo','galeria' => 'Exploração histórica',
        'temporis','metodo','laboratorio' => 'Índice Têmporis',
        'rodadas','andamento','importar','triagem','fontes','duplicatas','auditoria','editar','login' => 'Pesquisa e curadoria',
        default => null,
    };
  ?>
  <div class="breadcrumbs">MAT <span class="slash">/</span> <?php if($matBreadcrumbGroup): ?><?=h($matBreadcrumbGroup)?> <span class="slash">/</span><?php endif; ?><?=h($pageLabels[$page]??'Observatório')?></div>
  <div class="topbar-right"><span class="edition">EDIÇÃO DE PESQUISA <strong>MMXXVI</strong></span><span class="topbar-rule"></span><a class="topbar-action" href="<?=mat_url('acervo')?>">Explorar acervo <span>↗</span></a></div>
</header>
<main id="conteudo" class="main-content">
<?php if($flash):?><div class="flash <?=h($flash['type'])?>" role="status"><?=h($flash['message'])?></div><?php endif;?>
<?php if(site_mode()==='demonstracao'):?><div class="data-notice"><span class="data-notice-dot">!</span><span><strong>Banco MySQL desconectado.</strong> O MAT não exibirá exemplos fictícios como registros reais. Inicie o MySQL para consultar o acervo.</span><a href="<?=mat_url('metodo')?>">Entenda o estado do projeto ↗</a></div><?php endif;?>
