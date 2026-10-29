<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$header=(string)@file_get_contents($root.'/views/header.php');
$home=(string)@file_get_contents($root.'/views/observatorio.php');
$shortcuts=(string)@file_get_contents($root.'/views/mat_acessos_inicio.php');
$err=0;
function assertion(bool $ok, string $text): void {global $err;echo ($ok?'[OK] ':'[ERRO] ').$text.PHP_EOL;if(!$ok)$err++;}
assertion(str_contains($header,'assistente_ia.php'),'Assistente de IA vinculado no menu');
assertion(str_contains($header,'resultado_temporis_piloto.php'),'Resultado Têmporis vinculado no menu');
assertion(str_contains($home,'mat_acessos_inicio.php'),'Atalhos da pagina inicial carregados');
assertion(str_contains($shortcuts,'mat-home-access-grid'),'Atalhos visuais preparados');
assertion(substr_count($header,'MAT_NAV_EXTENSIONS_V07_START')===1,'Menu nao duplicado');
assertion(substr_count($home,'mat_acessos_inicio.php')===1,'Atalhos nao duplicados');
assertion(str_contains($header,'Caderno de método') && str_contains($header,'Acervo de marcos'),'Menu original preservado');
if($err){echo 'MAT MENU: FALHA NO TESTE'.PHP_EOL;exit(1);}
echo 'MAT MENU: TESTES APROVADOS.' . PHP_EOL;
