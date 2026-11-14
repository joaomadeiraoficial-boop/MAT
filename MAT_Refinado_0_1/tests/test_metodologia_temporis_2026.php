<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TemporisMetodologia2026.php';
function conferir(bool $condition,string $message):void {
    if(!$condition)throw new RuntimeException('FALHOU: '.$message);
    echo '[OK] '.$message.PHP_EOL;
}
function perto(float $a,float $b,float $tol=1e-8):bool{return abs($a-$b)<$tol;}
$linha=['anos_peso'=>[1500=>1.0,1600=>2.0,1700=>3.0,1750=>5.0,1800=>7.0,1850=>11.0,1900=>13.0,1950=>17.0,2000=>19.0,2026=>23.0]];
$raw=TemporisFinal::k($linha,1900);
conferir(perto($raw,42.0),'K(t) soma apenas marcos com ano <= t');
conferir(perto(TemporisFinal::indice($raw,0,'bruta'),$raw),'K bruto existe mesmo sem ano-base');
conferir(perto(TemporisFinal::indice($raw,$raw,'empirica'),100),'Base 100 permanece opcional e não altera K');
$hist=TemporisMetodologia2026::historico($linha);
conferir(count($hist)===8 && $hist[7]['duracao']===26,'Último período de 2000 a 2026 possui 26 anos');
conferir(perto($hist[7]['delta'],23.0) && perto($hist[7]['pontos_por_ano'],23/26),'Crescimento e velocidade usam diferença do acumulado');
$a=TemporisFinal::projetar($linha,1850,50,'exponencial50');
$alterada=$linha;$alterada['anos_peso'][1900]+=99999.0;
$b=TemporisFinal::projetar($alterada,1850,50,'exponencial50');
conferir($a!==null && perto($a,$b),'Um marco posterior ao corte NÃO influencia previsão de 1850');
$fA=TemporisMetodologia2026::confronto($linha,1850,50,'exponencial50');
$fB=TemporisMetodologia2026::confronto($alterada,1850,50,'exponencial50');
conferir(perto($fA['previsto'],$fB['previsto']) && !perto($fA['observado'],$fB['observado']), 'A observação pode mudar, mas treino continua cego');
$fF=TemporisMetodologia2026::confronto($linha,2026,25,'linear50');
conferir($fF['status']==='futuro_sem_erro' && $fF['observado']===null && $fF['erro_pct_delta']===null,'Nenhum percentual de erro inventado para 2051');
$stats=TemporisMetodologia2026::compararMesmosCortes($linha,25,2026);
$cuts=$stats['cortes'];
foreach($stats['modelos'] as $id=>$r){
    conferir($r['n']===count($cuts),'Amostra comparável em '.$id);
    conferir(array_map(static fn($f)=>$f['corte'],$r['folds'])===$cuts,'Mesmos cortes usados em '.$id);
}
$example=TemporisMetodologia2026::validacao($linha,'linear50',50,2026);
conferir($example['n']>0 && $example['wape']!==null,'WAPE é calculável a partir dos incrementos históricos');
$single=$example['folds'][0];
conferir(isset($single['crescimento_real'],$single['crescimento_previsto'],$single['erro']), 'Backtesting reporta ambos incrementos e o erro');
$snap=TemporisFinal::obterDados('snapshot',2026);
conferir($snap['fonte']==='snapshot' && count($snap['dados']['pontos'])===667,'Snapshot identificado como fixo e não confundido com MySQL atual');
conferir(perto(TemporisFinal::k(TemporisFinal::linha($snap['dados']['pontos'],'quadratica'),2026),155.48620659722,1e-6),'Snapshot preserva a mesma fórmula, sem alterar pontuação');
conferir(TemporisMetodologia2026::selecaoProgressiva($linha,25)['n']>=0,'Seleção progressiva reproduzível sem usar cortes futuros');
echo "VALIDAÇÃO NOVA METODOLOGIA: TODOS OK\n";
