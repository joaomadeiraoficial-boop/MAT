<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/AtlasReais.php';
require_once dirname(__DIR__).'/app/TemporisGraficos.php';
function ok(bool $yes,string $label):void{if(!$yes){fwrite(STDERR,"[FALHOU] $label\n");exit(1);}echo "[OK] $label\n";}
$a=AtlasReais::fromSources([
  ['ano'=>1800,'status'=>'pendente'],['ano'=>2000,'status'=>'aprovado']],
  [['ano_original'=>1800,'ano_validado'=>null,'estado_revisao_humana'=>'APROVADO','homologado'=>0],
   ['ano_original'=>null,'ano_validado'=>null,'estado_revisao_humana'=>'PENDENTE','homologado'=>0],
   ['ano_original'=>2000,'ano_validado'=>2000,'estado_revisao_humana'=>'HOMOLOGADO','homologado'=>1]]);
ok($a['resumo']['cur_total']===3 && $a['resumo']['cur_com_ano']===2 && $a['resumo']['cur_sem_ano']===1,'Atlas nao perde dossies sem ano');
ok($a['resumo']['cur_ano_validado']===1 && $a['resumo']['cur_ano_extraido']===1,'Atlas distingue ano validado e ano extraido');
ok($a['resumo']['cur_aprovados']===2 && $a['resumo']['cur_homologados']===1,'Atlas separa aprovacao de homologacao');
ok($a['eras']['industrial']['preparacao']===1 && $a['eras']['contemporanea']['cur_homologados']===1,'Contagem das eras por data com origem distinta');
$calc=TemporisGraficos::curvas([
 ['id'=>'a','year'=>1950,'score'=>3],['id'=>'b','year'=>1900,'score'=>1],['id'=>'c','year'=>1920,'score'=>2]
],1920);
ok($calc['normalizavel'] && abs($calc['k_base']-3.0)<1e-10,'Soma de base calculada ate corte');
ok(abs($calc['series'][1]['log']-100.0)<1e-10 && abs($calc['series'][1]['potencia']-100.0)<1e-10,'Ambas formulas normalizam o mesmo corte');
$g=TemporisGraficos::svgDecisoes([['id'=>'MCD-1','year'=>1800,'title'=>'Teste','estado'=>'APROVADO','homologado'=>0,'notas_completas'=>false],['id'=>'MCD-2','year'=>1900,'title'=>'Teste2','estado'=>'HOMOLOGADO','homologado'=>1,'notas_completas'=>true]]);
ok(str_contains($g,'MCD-1') && str_contains($g,'MCD-2') && str_contains($g,'ano exato'),'Regua historica com tooltip e sem etiquetas por ponto');
$root=dirname(__DIR__);
$matProj=file_get_contents($root.'/mat_proj/index.php');
ok(str_contains($matProj,"page']='projecoes'") && !str_contains($matProj,'EXEMPLO_SINTETICO.csv'),'MAT-Proj usa pagina cientifica integrada');
$proj=file_get_contents($root.'/views/projecoes.php');
ok(str_contains($proj,'TemporisCuradoria::consultar') && str_contains($proj,'TemporisBacktest::experiment'),'MAT-Proj analisa rubricas e pesos da Têmporis');
$did=file_get_contents($root.'/resultado_temporis_piloto.php');
ok(!str_contains($did,'Location: experimento_temporis.php'),'Ensaio didatico e backtest nao sao mesmo destino');
$nav=file_get_contents($root.'/views/header.php');
ok(str_contains($nav,'temporis-integracao.css'),'Tema editorial aplicado aos experimentos');
echo "MAT INTEGRACAO: NOVOS TESTES APROVADOS.\n";
