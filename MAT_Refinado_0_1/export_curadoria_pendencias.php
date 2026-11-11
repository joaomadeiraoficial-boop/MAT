<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
if(!is_admin()){http_response_code(403);exit('Acesso restrito ao pesquisador.');}
require_once __DIR__.'/app/CuradoriaAuditoriaAutomatica.php';
try {
    $pdo=CuradoriaRodadas::connect();
    if(!$pdo)throw new RuntimeException('Banco científico indisponível.');
    $data=CuradoriaAuditoriaAutomatica::carregar($pdo);
} catch(Throwable $e){
    error_log('MAT exportação de pendências: '.$e->getMessage());
    http_response_code(503);exit('Não foi possível consultar a curadoria.');
}
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="mat_curadoria_pendencias_'.gmdate('Ymd_His').'.csv"');
header('Cache-Control: private, no-store');
$out=fopen('php://output','wb');
fwrite($out,"\xEF\xBB\xBF");
fputcsv($out,['id_extracao','rodada','titulo','ano_original','ano_validado','estado','categoria_auditoria',
    'notas_validas_11','fontes','pendencias','duplicatas','bloqueios','proxima_acao','amostra'], ';','"','');
require_once __DIR__.'/app/CuradoriaFilaRapida.php';
foreach($data['itens'] as $r){
    $row=[$r['id_extracao'],(int)$r['rodada'],$r['titulo_proposto'], $r['ano_original']??'',
        $r['ano_validado']??'', $r['estado_revisao_humana']??'', $r['categoria_auditoria'],
        (int)$r['notas_validas'], count($r['fontes']??[]), count($r['pendencias']??[]),
        count($r['duplicatas']??[]),count($r['impedimentos']??[]),
        CuradoriaFilaRapida::proximaAcao($r),!empty($r['amostra'])?'SIM':'NAO'];
    // Evita formula injection em softwares de planilha.
    foreach($row as &$v){if(is_string($v) && preg_match('/^[\s]*[=+@\-]/u',$v))$v="'".$v;}unset($v);
    fputcsv($out,$row,';','"','');
}
fclose($out);
