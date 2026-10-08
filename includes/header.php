<?php
$pageTitle = $pageTitle ?? $app['app_name'];
$active = $active ?? '';
$flashes = pull_flashes();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="dashboard.php"><span class="brand-mark">M</span><span><strong>MAT</strong><small>Laboratório Têmporis</small></span></a>
    <nav>
      <?php
      $navItems = [
        'dashboard'=>['dashboard.php','Visão geral','⌂'],
        'importar'=>['importar.php','Importar base','⇩'],
        'coletor'=>['coletor.php','Coletor de dados','⌁'],
        'revisar'=>['revisar.php','Fila de revisão','✓'],
        'escopo'=>['escopo.php','Escopo científico','◎'],
        'regras_escopo'=>['regras_escopo.php','Dicionário de triagem','⌕'],
        'duplicatas'=>['duplicatas.php','Duplicatas','⧉'],
        'marcos'=>['marcos.php','Base de marcos','◫'],
        'fontes'=>['fontes.php','Fontes','⌘'],
        'temporis'=>['temporis.php','Têmporis','∑'],
        'auditoria'=>['auditoria.php','Auditoria','◷'],
        'exportar'=>['exportar.php','Exportar','⇧'],
      ];
      foreach ($navItems as $key=>$it): ?>
        <a class="nav-link <?= $active===$key?'active':'' ?>" href="<?= e($it[0]) ?>"><span><?= e($it[2]) ?></span><?= e($it[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer"><span class="dot"></span> v<?= e($app['version']) ?> · local</div>
  </aside>
  <main class="main">
    <header class="topbar">
      <div><h1><?= e($pageTitle) ?></h1><p><?= e($pageSubtitle ?? 'Organização, revisão e validação científica de marcos tecnológicos.') ?></p></div>
      <div class="top-actions"><button class="btn ghost" data-theme-toggle>◐ Tema</button><a class="btn primary" href="importar.php">+ Importar dados</a></div>
    </header>
    <?php foreach ($flashes as $flash): ?><div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endforeach; ?>
    <section class="content">
