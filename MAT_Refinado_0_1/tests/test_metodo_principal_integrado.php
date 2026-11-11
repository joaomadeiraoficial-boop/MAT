<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TemporisFinal.php';
$root=dirname(__DIR__);
$index=file_get_contents($root.'/index.php');
$nav=file_get_contents($root.'/views/header.php');
$home=file_get_contents($root.'/views/mat_acessos_inicio.php');
if (!str_contains($index,"'temporis' => 'temporis_final'") || !str_contains($index,"'temporis_reformulado' => 'temporis'")) {
    throw new RuntimeException('Falha: roteamento novo/antigo');
}
if (!str_contains($nav,'Têmporis · resultado atual') || !str_contains($home,'Têmporis <em>final.</em>')) {
    throw new RuntimeException('Falha: método novo não aparece no menu e na home');
}
$data=TemporisFinal::carregarSnapshot(2026);
if (($data['audit']['total'] ?? null)!==800 || count($data['pontos'])!==667) {
    throw new RuntimeException('Falha: exportação de 07/11/2026 não produziu os 667 elegíveis');
}
$l=TemporisFinal::linha($data['pontos'],'quadratica');
$t=TemporisFinal::indice(TemporisFinal::k($l,2026),TemporisFinal::k($l,1900),'empirica');
if($t===null || abs($t-156.24472591506)>0.0001){throw new RuntimeException('Falha: reprodução numérica do MAT Final');}
echo 'OK: método final como padrão; legadas acessíveis; exportação local 800/667; T(2026)='.number_format($t,2,'.','').PHP_EOL;
