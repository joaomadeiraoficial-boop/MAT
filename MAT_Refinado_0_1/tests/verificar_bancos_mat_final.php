<?php
/** Execute apenas via CLI: php tests/verificar_bancos_mat_final.php. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente CLI');}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/CuradoriaRodadas.php';
require_once dirname(__DIR__).'/app/TemporisFinal.php';
try{
 if(TemporisFinal::conectar()===null)throw new RuntimeException('O snapshot mat_final_curadoria nao esta disponivel ou esta configurado incorretamente.');
 $pdo=TemporisFinal::conectar();if(!$pdo)throw new RuntimeException('Não foi possível conectar à curadoria final.');
 // O acervo principal do MAT Refinado nao e requisito do laboratorio e permanece separado.
 $count=(int)$pdo->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
 $data=TemporisFinal::carregar($pdo,2026);$audit=$data['audit'];
 $linha=TemporisFinal::linha($data['pontos'],'quadratica');
 $base=TemporisFinal::k($linha,1900);$t=TemporisFinal::indice(TemporisFinal::k($linha,2026),$base);
 if($count!==800||count($data['pontos'])!==667)throw new RuntimeException('A cópia do banco não corresponde ao snapshot de 7/11/2026: '.$count.' candidatos e '.count($data['pontos']).' datados.');
 if($t===null||abs($t-156.244725915)>0.0001)throw new RuntimeException('O índice não confere com o snapshot. Obtido: '.var_export($t,true));
 foreach([10,25,50,100] as $h){
     $p=TemporisFinal::projetar($linha,2026,$h,'aceleracao');
     if($p===null||!is_finite($p)||$p<TemporisFinal::k($linha,2026))throw new RuntimeException('Falha de previsão: '.$h.' anos');
 }
 echo 'MAT REFINADO + FINAL — SNAPSHOT ISOLADO E MOTOR TESTADOS'.PHP_EOL;
 echo 'Candidatos: '.$count.' | Curva exploratoria: '.count($data['pontos']).PHP_EOL;
 echo 'Anos extraidos: '.$audit['datas_extraidas'].' | Marcacoes de nota minima: '.$audit['notas_provisorias'].PHP_EOL;
 echo 'K(1900): '.$base.' | T(2026): '.number_format($t,6,'.','').PHP_EOL;
 exit(0);
}catch(Throwable $e){fwrite(STDERR,'FALHA MAT FINAL: '.$e->getMessage().PHP_EOL);exit(1);}
