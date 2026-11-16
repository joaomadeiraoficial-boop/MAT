<?php
declare(strict_types=1);
/**
 * Limpeza auditável de vínculos de duplicidade vindos da importação.
 *
 * Remove SOMENTE as referências cruzadas diagnosticadas como 'alerta'
 * (nenhuma palavra histórica em comum + categoria diferente ou diferença
 * de mais de 100 anos). Não apaga candidatos, não altera notas, não
 * registra parecer humano; cada remoção fica em cur_log com o motivo.
 *
 * Uso: php cli/limpar_vinculos_duplicatas.php [--dry-run]
 */
define('MAT_ROOT', dirname(__DIR__));
require MAT_ROOT.'/app/bootstrap.php';
require MAT_ROOT.'/app/CuradoriaRodadas.php';
require MAT_ROOT.'/app/CuradoriaDuplicatas.php';

$dryRun=in_array('--dry-run',$argv,true);
$pdo=CuradoriaRodadas::connect();
if(!$pdo){fwrite(STDERR,"Banco de curadoria indisponível.\n");exit(1);}

$pairs=CuradoriaDuplicatas::listar($pdo);
$alvo=[];
foreach($pairs as $p){
    $dec=(string)($p['decisao']['decisao']??'');
    if(in_array($dec,['manter_a','manter_b','nao_duplicado'],true))continue; // já decidido por humano
    if(!$p['vinculo_ativo'] || !$p['ambos_presentes'])continue;              // vínculo já baixado ou incompleto
    if(($p['diagnostico']['nivel']??'')!=='alerta')continue;                 // só os incompatíveis
    $alvo[$p['chave']]=$p;
}
echo 'vínculos incompatíveis a limpar: ',count($alvo),"\n";
if($dryRun){
    foreach($alvo as $key=>$p){
        echo "  [dry-run] $key: ",mb_substr((string)$p['a']['titulo_proposto'],0,60)," <=> ",mb_substr((string)$p['b']['titulo_proposto'],0,60),"\n";
    }
    exit(0);
}
$pdo->beginTransaction();
try{
    // Cada candidato pode participar de varios pares alerta; o JSON precisa ser
    // relido do banco dentro da transacao (com bloqueio de linha) para que as
    // remocoes se acumulem em vez de se sobrescreverem com valores antigos.
    $sel=$pdo->prepare('SELECT duplicatas_json FROM cur_candidatos WHERE id_extracao=? FOR UPDATE');
    $up=$pdo->prepare('UPDATE cur_candidatos SET duplicatas_json=? WHERE id_extracao=?');
    $log=$pdo->prepare("INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (?,'LIMPEZA_VINCULO_IMPORTACAO',?)");
    $alterados=[];
    foreach($alvo as $key=>$p){
        [$ida,$idb]=explode('|',$key);
        foreach([[$ida,$idb],[$idb,$ida]] as [$id,$other]){
            $sel->execute([$id]);
            $antes=$sel->fetchColumn();
            $sel->closeCursor();
            if($antes===false)continue; // registro sumiu entre listar e aqui
            $antes=(string)$antes;
            $novo=CuradoriaDuplicatas::semReferencia($antes,$other);
            if($novo===$antes)continue;
            $up->execute([$novo,$id]);
            $alterados[$id]=true;
            $log->execute([$id,json_encode([
                'par'=>$key,
                'referencia_removida'=>$other,
                'antes'=>$antes,'depois'=>$novo,
                'criterio'=>'diagnostico nivel=alerta: nenhuma palavra historica em comum e categoria distinta ou diferenca de mais de 100 anos',
                'executor'=>'cli/limpar_vinculos_duplicatas.php',
                'natureza'=>'limpeza de vinculo de importacao sem semelhanca; NAO e parecer historico; candidatos preservados',
            ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
        }
    }
    $pdo->commit();
    echo 'vínculos limpos: ',count($alvo),"\n";
    echo 'candidatos atualizados: ',count($alterados),"\n";
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    fwrite(STDERR,'Falha: '.$e->getMessage()."\n");
    exit(1);
}
