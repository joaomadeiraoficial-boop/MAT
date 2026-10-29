<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/AtlasReais.php';
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
if(is_file(dirname(__DIR__).'/app/RevisedMethodology.php')) require_once dirname(__DIR__).'/app/RevisedMethodology.php';
function check(bool $condition,string $description): void {
    if(!$condition){fwrite(STDERR,"[ERRO] $description\n");exit(1);}
    echo "[OK] $description\n";
}
$expected=[-3501=>'prehistoria',-3500=>'antiguidade',475=>'antiguidade',476=>'medieval',1452=>'medieval',1453=>'moderna',1788=>'moderna',1789=>'industrial',1944=>'industrial',1945=>'digital',1999=>'digital',2000=>'contemporanea'];
foreach($expected as $y=>$name)check(AtlasReais::keyForYear($y)===$name,'Era correta para '.$y);
check(AtlasReais::keyForYear('texto')===null,'Data invalida nao vira ano zero');
$test=AtlasReais::counts([
    ['ano'=>1850,'status'=>'pendente'],['ano'=>1850,'status'=>'aprovado'],['ano'=>1850,'status'=>'demonstracao'],
    ['ano'=>2001,'status'=>'importado']],
    [['ano'=>1850,'status'=>'pendente'],['ano'=>2001,'status'=>'convertido']]);
check($test['industrial']['acervo']===2 && $test['industrial']['preparacao']===1 && $test['industrial']['aprovados']===1,'Atlas separa acervo, aprovados e candidatos');
check($test['contemporanea']['acervo']===1 && $test['contemporanea']['preparacao']===0,'Nao exibe dados demonstrativos');
$raw=['bruto_json'=>json_encode(['trecho_evidencia'=>'In the 1960s, however, controversy erupted over claims regarding Stonehenge as a sophisticated observatory.','pagina_livro'=>'27']), 'titulo'=>'A revisar: Stonehenge','ano'=>1960,'status'=>'pendente'];
$r=TriagemLoteReal::rules($raw);
check($r['pode_enviar_ia']===true && count($r['flags'])>=2,'Triagem por regras funciona com contexto marcado');
$proposal=TriagemLoteReal::proposal(['criterios'=>[]]);
check($proposal['notas_com_citacao']===0 && $proposal['peso_provisorio_nao_validado']===null,'Sem evidencia => nao cria pontuacoes');
$ids=TriagemLoteReal::CRITERIA;
check(count($ids)===11 && (!class_exists('RevisedMethodology') || count(RevisedMethodology::ALL)===11),'Mesmos onze criterios da formula revisada');
$backtest=file_get_contents(dirname(__DIR__).'/experimento_temporis.php');
check(!str_contains($backtest,'simulatedPoints(') && !str_contains($backtest,'Demonstração fictícia'),'Backtest publico sem simulacoes');
$pilot=file_get_contents(dirname(__DIR__).'/resultado_temporis_piloto.php');
check(str_contains($pilot,'Location: experimento_temporis.php'),'Ensaio antigo redirecionado');
echo "MAT REAL: TESTES DE CODIGO APROVADOS (SEM OLLAMA OU MYSQL).\n";
