<?php
/** Teste offline — não conecta ao banco e não modifica o MAT. */
declare(strict_types=1);
if (!function_exists('h')) {
    function h(string $s):string {return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
if (!function_exists('mat_url')) {
    function mat_url(string $page,array $params=[]):string {
        return 'index.php?'.http_build_query(['page'=>$page]+$params);
    }
}
ob_start();
require dirname(__DIR__).'/views/metodo.php';
$html=(string)ob_get_clean();
$expected=['metodo-pontuacao','metodo-versoes','metodo-comparar','metodo-validacao',
  'Legada','Reformulada','MAT Final',
  'K<sub>L</sub>','ln(1 + K(t))','T<sub>F</sub>(t)',
  'temporis_legado','temporis_reformulado','SEM_EVIDENCIA','metodo-seis'];
foreach($expected as $needle) {
    if(!str_contains($html,$needle)) {fwrite(STDERR,'FALHA: '.$needle.PHP_EOL);exit(1);}
}
if(substr_count($html,'href="index.php?page=temporis')<3){fwrite(STDERR,'FALHA: páginas das versões'.PHP_EOL);exit(1);}
if(str_contains($html,'INSERT INTO')||str_contains($html,'UPDATE cur_')){fwrite(STDERR,'FALHA: escrita SQL'.PHP_EOL);exit(1);}
require_once dirname(__DIR__).'/app/TemporisPotenciaAtual.php';
require_once dirname(__DIR__).'/app/TemporisFinal.php';
$I=8.5;$C=0.75;$F=1.3;
$pLegacy=TemporisPotenciaAtual::peso(['I'=>$I,'C'=>$C,'F'=>$F]);
$pFinal=($I/10)**2*$C*$F;
if(abs($pLegacy-100*$pFinal)>1e-10){fwrite(STDERR,'FALHA: peso original vs final'.PHP_EOL);exit(1);}
$base=10.0;$k=15.0;
if(abs(TemporisFinal::indice($k,$base,'empirica')-150)>1e-10){fwrite(STDERR,'FALHA: empirica'.PHP_EOL);exit(1);}
if(abs(TemporisFinal::indice($k,$base,'pot065')-100*pow(1.5,.65))>1e-10){fwrite(STDERR,'FALHA: potencia'.PHP_EOL);exit(1);}
if(abs(TemporisFinal::indice($k,$base,'log')-100*log1p($k)/log1p($base))>1e-10){fwrite(STDERR,'FALHA: log'.PHP_EOL);exit(1);}
if(TemporisFinal::indice($k,0.0)!==null){fwrite(STDERR,'FALHA: base nula'.PHP_EOL);exit(1);}
if (substr_count($html,'class="tf-six-current"')!==2 || substr_count($html,'<article><small>0')!==4) throw new RuntimeException("As seis expressões devem estar identificadas");
echo "OK — caderno com seis fórmulas (três pares), três versões e equivalência dos pesos; sem banco.\n";
