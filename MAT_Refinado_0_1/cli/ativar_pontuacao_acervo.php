<?php
declare(strict_types=1);
/** Ajuste de links do site. Sem acesso ao MySQL. Idempotente. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente CLI');}
$root=dirname(__DIR__);
require_once $root.'/app/PontuacaoAcervoIA.php';
if(!defined('MAT_ROOT'))define('MAT_ROOT',$root);
$backupDir=PontuacaoAcervoIA::directory(true).DIRECTORY_SEPARATOR.'backup_integracao';
if(!is_dir($backupDir))mkdir($backupDir,0700,true);
function patchFile(string $file,string $new,string $backupDir): bool {
    if(!is_file($file))return false;
    $previous=file_get_contents($file);
    if($previous===false || $new===$previous)return false;
    $backup=$backupDir.DIRECTORY_SEPARATOR.basename($file).'.'.substr(hash('sha256',$previous),0,12).'.bak';
    if(!file_exists($backup) && file_put_contents($backup,$previous,LOCK_EX)===false)throw new RuntimeException('Falha ao criar backup '.$backup);
    if(file_put_contents($file,$new,LOCK_EX)===false)throw new RuntimeException('Falha ao gravar arquivo '.$file);
    return true;
}
try{
    $favicon='<link rel="icon" href="assets/insignia.svg" type="image/svg+xml">';
    foreach(['experimento_temporis.php','triagem_300_reais.php','assistente_ia.php','resultado_temporis_piloto.php'] as $name){
        $file=$root.DIRECTORY_SEPARATOR.$name;
        if(!is_file($file)){echo "[INFO] $name nao esta instalado.\n";continue;}
        $html=(string)file_get_contents($file);
        if(preg_match('/<link\s+[^>]*rel=["\x27]icon["\x27]/i',$html)){echo "[OK] $name ja tem favicon.\n";continue;}
        $new=preg_replace('/(<head\b[^>]*>)/i','$1'.$favicon,$html,1,$count);
        if(!$count){echo "[AVISO] $name nao possui tag head; sem alteracao.\n";continue;}
        if(patchFile($file,$new,$backupDir))echo "[OK] Favicon MAT configurado em $name.\n";
    }
    $header=$root.'/views/header.php';
    if(is_file($header)){
        $html=(string)file_get_contents($header);
        if(strpos($html,'href="fila_pontuacao.php"')===false){
            $found=preg_match('/^.*mat_url\(\x27avaliacao\x27\).*$/m',$html,$hits);
            if($found){
                $menuLine='   <a href="fila_pontuacao.php" class="nav-item"><span class="nav-glyph">✍</span> <span>Pontuação assistida IA</span></a>';
                $new=str_replace($hits[0],$hits[0]."\n".$menuLine,$html);
                if(patchFile($header,$new,$backupDir))echo "[OK] Novo acesso adicionado ao menu.\n";
            }else echo "[AVISO] Menu personalizado: acesse fila_pontuacao.php diretamente.\n";
        }else echo "[OK] Menu ja possui o acesso, sem duplicacao.\n";
    }
    echo "Pronto! Abra fila_pontuacao.php no navegador.\n";
    echo "Backup dos arquivos alterados: $backupDir\n";
}catch(Throwable $e){fwrite(STDERR,'ERRO: '.$e->getMessage()."\n");exit(1);}
