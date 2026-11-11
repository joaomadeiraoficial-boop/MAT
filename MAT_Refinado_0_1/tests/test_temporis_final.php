<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TemporisFinal.php';
function eq($got,$expected,string $name,float $tol=1e-6):void{
 if($got===null||abs($got-$expected)>$tol)throw new RuntimeException("$name: esperado $expected, obtido ".var_export($got,true));
 echo "OK: $name\n";
}
$one=['year'=>1900,'pesos'=>['quadratica'=>2.,'linear'=>3.,'geometrica'=>4.,'media'=>5.], 'aprovado'=>true,'homologado'=>false,'ano_validado'=>true,'ano_extraido'=>false,'sem_evidencia'=>false,'provisorio'=>false,'contexto'=>false];
$two=$one;$two['year']=2000;$two['pesos']['quadratica']=4.;$two['ano_validado']=false;$two['ano_extraido']=true;$two['sem_evidencia']=true;
$three=$one;$three['year']=2010;$three['pesos']['quadratica']=1.;$three['provisorio']=true;
$l=TemporisFinal::linha([$one,$two,$three],'quadratica');
eq(TemporisFinal::k($l,1850),0,'K antes');eq(TemporisFinal::k($l,1900),2,'K base');eq(TemporisFinal::k($l,2000),6,'K 2000');
eq(TemporisFinal::indice(6,2,'empirica'),300,'sem compressao');
eq(TemporisFinal::indice(6,2,'pot065'),100*pow(3,.65),'potencia original');
eq(TemporisFinal::indice(6,2,'log'),100*log1p(6)/log1p(2),'log antiga');
if(TemporisFinal::indice(4,0) !== null)throw new RuntimeException('base zero precisa ser indefinida');
if(count(TemporisFinal::filtrar([$one,$two,$three],'sem_sem_evidencia'))!==2)throw new RuntimeException('filtro SEM_EVIDENCIA');
if(count(TemporisFinal::filtrar([$one,$two,$three],'sem_provisorias'))!==2)throw new RuntimeException('filtro notas provisórias');
$p=TemporisFinal::periodo([$one,$two,$three],'quadratica',1900,2000);eq($p['incremento'],2,'periodo sem incluir 2000');
// Série sintética crescente, para todas as fórmulas de previsão.
$rows=[];for($y=1300;$y<=2026;$y+=10){$p=$one;$p['year']=$y;$p['pesos']['quadratica']=1.+($y-1300)/1000;$rows[]=$p;}
$line=TemporisFinal::linha($rows,'quadratica');
foreach(TemporisFinal::MODELOS as $modelo=>$label){
 $v=TemporisFinal::projetar($line,1900,25,$modelo);
 if($v!==null && (!is_finite($v)||$v<TemporisFinal::k($line,1900)))throw new RuntimeException("modelo invalido $modelo");
 $b=TemporisFinal::validar($line,$modelo,25,2026);
 if($b['n']>0&&($b['wape']===null||!is_finite($b['wape'])))throw new RuntimeException("backtest invalido $modelo");
 echo "OK: $modelo · previsao=".var_export($v,true)." · testes={$b['n']}\n";
}
echo 'TODOS OS TESTES MAT FINAL OK'.PHP_EOL;
