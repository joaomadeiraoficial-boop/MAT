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
<script defer src="assets/app.js?v=1.2"></script>
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
 <div class="rail-caption">CARTOGRAFIA DA INVENÇÃO <span>26—</span></div>
 <nav class="nav-list" aria-label="Navegação principal">
   <a href="<?=mat_url('observatorio')?>" class="nav-item <?=$navPage==='observatorio'?'active':''?>"><span class="nav-number">01</span> <span>O Observatório</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('linha')?>" class="nav-item <?=$navPage==='linha'?'active':''?>"><span class="nav-number">02</span> <span>Linha do tempo</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('atlas')?>" class="nav-item <?=$navPage==='atlas'?'active':''?>"><span class="nav-number">03</span> <span>Atlas das eras</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('acervo')?>" class="nav-item <?=$navPage==='acervo'?'active':''?>"><span class="nav-number">04</span> <span>Acervo de marcos</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('galeria')?>" class="nav-item <?=$navPage==='galeria'?'active':''?>"><span class="nav-number">05</span> <span>Panfletos do acervo</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('temporis')?>" class="nav-item <?=$navPage==='temporis'?'active':''?>"><span class="nav-number">06</span> <span>Índice Têmporis</span><span class="nav-symbol">↗</span></a>
   <a href="<?=mat_url('metodo')?>" class="nav-item <?=$navPage==='metodo'?'active':''?>"><span class="nav-number">07</span> <span>Caderno de método</span><span class="nav-symbol">↗</span></a>
 </nav>
 <div class="rail-divider"></div>
 <div class="rail-label">ESTAÇÃO DE TRABALHO</div>
 <nav class="nav-list nav-small" aria-label="Administração">
   <a href="<?=mat_url('laboratorio')?>" class="nav-item <?=$navPage==='laboratorio'?'active':''?>"><span class="nav-glyph">⌬</span> <span>Laboratório científico</span></a>
   <a href="<?=mat_url('importar')?>" class="nav-item <?=$navPage==='importar'?'active':''?>"><span class="nav-glyph">⇣</span> <span>Importar cronologias</span></a>
   <a href="<?=mat_url('triagem')?>" class="nav-item <?=$navPage==='triagem'?'active':''?>"><span class="nav-glyph">◈</span> <span>Triagem de escopo</span></a>
   <a href="<?=mat_url('fontes')?>" class="nav-item <?=$navPage==='fontes'?'active':''?>"><span class="nav-glyph">✣</span> <span>Fontes &amp; vínculos</span></a>
   <a href="<?=mat_url('duplicatas')?>" class="nav-item <?=$navPage==='duplicatas'?'active':''?>"><span class="nav-glyph">≋</span> <span>Conferir duplicatas</span></a>
   <a href="<?=mat_url('auditoria')?>" class="nav-item <?=$navPage==='auditoria'?'active':''?>"><span class="nav-glyph">☷</span> <span>Auditoria</span></a>
   <?php if(is_admin()): ?>
   <a href="<?=mat_url('editar')?>" class="nav-item"><span class="nav-glyph">＋</span> <span>Novo marco</span></a>
   <form class="nav-form" action="actions.php" method="post"><?=csrf_field()?><input type="hidden" name="action" value="logout"><button class="nav-item" type="submit"><span class="nav-glyph">↪</span> <span>Sair · <?=h(current_user()['nome'])?></span></button></form>
   <?php else: ?>
   <a href="<?=mat_url('login')?>" class="nav-item <?=$navPage==='login'?'active':''?>"><span class="nav-glyph">⌑</span> <span>Acesso do pesquisador</span></a>
   <?php endif; ?>
 </nav>
 <div class="rail-bottom">
    <div class="rail-quote">“A história da humanidade é também a história das ferramentas que inventa.”</div>
    <div class="rail-foot"><span class="live-dot"></span> <?=site_mode()==='mysql'?'ACERVO CONECTADO':'MODO DE LEITURA / DEMO'?><span>Refinada 0.1 · núcleo 6.2.1</span></div>
 </div>
</aside>
<div class="main-column">
<header class="topbar">
  <button class="menu-toggle" id="navOpen" type="button" aria-label="Abrir menu">☰</button>
  <div class="breadcrumbs">MAT <span class="slash">/</span> <?=h($pageLabels[$page]??'Observatório')?></div>
  <div class="topbar-right"><span class="edition">EDIÇÃO DE PESQUISA <strong>MMXXVI</strong></span><span class="topbar-rule"></span><a class="topbar-action" href="<?=mat_url('acervo')?>">Explorar acervo <span>↗</span></a></div>
</header>
<main id="conteudo" class="main-content">
<?php if($flash):?><div class="flash <?=h($flash['type'])?>" role="status"><?=h($flash['message'])?></div><?php endif;?>
<?php if(site_mode()==='demonstracao'):?><div class="data-notice"><span class="data-notice-dot">!</span><span><strong>Acervo ilustrativo.</strong> Banco MySQL ainda não conectado. Você pode explorar a interface, mas alterações exigem instalação. Nenhum marco de exemplo é tratado como evidência validada.</span><a href="<?=mat_url('metodo')?>">Entenda o estado do projeto ↗</a></div><?php endif;?>
