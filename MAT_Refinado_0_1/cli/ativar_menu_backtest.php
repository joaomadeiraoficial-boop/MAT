<?php
declare(strict_types=1);
/** Adiciona atalhos idempotentes e cria backup ANTES de alterar arquivos de interface. */
if (PHP_SAPI!=='cli')exit('Somente CLI');
$root=dirname(__DIR__);
$header=$root.'/views/header.php';
$home=$root.'/views/mat_acessos_inicio.php';
$targets=[];
if(is_file($header)) {
    $s=file_get_contents($header);
    if(!str_contains($s,'experimento_temporis.php')) {
        $link='   <?php if (is_admin() && is_file(MAT_ROOT . "/experimento_temporis.php")): ?><a href="experimento_temporis.php" class="nav-item"><span class="nav-glyph">∑</span><span>Teste histórico Têmporis</span></a><?php endif; ?>' . "\n";
        $anchor = '<div class="rail-bottom">';
        if(str_contains($s,$anchor)) {
            $targets[$header]=str_replace($anchor, $link.$anchor, $s);
        } else {echo "Menu lateral não reconhecido; nenhum ajuste feito nele.\n";}
    }
}
if(is_file($home)) {
    $s=file_get_contents($home);
    if (!str_contains($s,'experimento_temporis.php')) {
        $anchor="    \$matHomeActions[] = ['index.php?page=importar'";
        $line="    if (is_file(MAT_ROOT . '/experimento_temporis.php')) \$matHomeActions[] = ['experimento_temporis.php', 'Teste histórico Têmporis', 'Previsão 1920 → 2020, comparação e erros'];\n";
        if(str_contains($s,$anchor)) $targets[$home]=str_replace($anchor,$line.$anchor,$s);
        else echo "Atalhos da home não reconhecidos; mantidos intactos.\n";
    }
}
if(!$targets){echo "MAT BACKTEST MENU: nada a alterar (talvez já instalado).\n";exit(0);}
$dir=dirname($root).'/.mat-backups-menu';
if(!is_dir($dir) && !mkdir($dir,0750,true) && !is_dir($dir))throw new RuntimeException('Não foi possível criar pasta de backups');
file_put_contents($dir.'/.htaccess',"Require all denied\nDeny from all\n");
$suffix=date('Ymd-His').'-'.getmypid();
foreach($targets as $file=>$text){
    $backup=$dir.'/'.basename($file).'-'.$suffix.'-backtest.bak';
    if(!copy($file,$backup))throw new RuntimeException('Backup falhou: '.$file);
    if(file_put_contents($file,$text,LOCK_EX)===false){copy($backup,$file);throw new RuntimeException('Falha na escrita');}
    echo '[OK] '.basename($file).' atualizado, backup: '.basename($backup)."\n";
}
echo "MAT BACKTEST MENU: acesso instalado.\n";
