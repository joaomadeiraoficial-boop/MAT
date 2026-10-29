<?php
declare(strict_types=1);
$path=dirname(__DIR__).'/views/header.php';
if(!is_file($path)){fwrite(STDERR,"Menu nao encontrado.\n");exit(1);}
$source=file_get_contents($path);
if(str_contains($source,'assistente_ia.php')){echo "Acesso ao assistente ja existe no menu.\n";exit(0);}
$needle='   <a href="<?=mat_url(\'triagem\')?>"';
if(!str_contains($source,$needle)){
    fwrite(STDERR,"Estrutura do menu diferente da esperada. Nenhum arquivo foi alterado. Use /assistente_ia.php diretamente.\n"); exit(1);
}
$add='   <a href="assistente_ia.php" class="nav-item"><span class="nav-glyph">✦</span> <span>Assistente de triagem IA</span></a>' . "\n";
$backup=$path.'.antes-assistente-'.date('Ymd-His').'.bak';
if(!copy($path,$backup)){fwrite(STDERR,"Falha ao fazer backup do menu.\n");exit(1);}
if(file_put_contents($path,str_replace($needle,$add.$needle,$source))===false){fwrite(STDERR,"Falha ao gravar menu. Backup: $backup\n");exit(1);}
echo "Acesso adicionado ao menu sem substituir outras integracoes. Backup: ".basename($backup)."\n";
