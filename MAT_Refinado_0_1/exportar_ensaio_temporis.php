<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/TemporisCuradoria.php';
if(!is_admin()){http_response_code(403);exit('Área restrita ao pesquisador.');}
$pdo=CuradoriaRodadas::connect();
if(!$pdo){http_response_code(503);exit('Banco científico indisponível.');}
$base=(int)cfg('temporis_reformulada_ano_base',1920);
$dados=TemporisCuradoria::consultar($pdo,$base);
$tipo=(($_GET['serie']??'')==='previa')?'previa':'ensaio';
$series=$dados[$tipo]['series'];
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="temporis_'.$tipo.'_'.date('Ymd').'.csv"');
header('Cache-Control: no-store, private');
$f=fopen('php://output','wb');
fwrite($f,"\xEF\xBB\xBF");
fputcsv($f,['id_extracao','titulo','ano_usado_no_calculo','inicio_intervalo','fim_intervalo','origem_da_data','ano_extraido_nao_validado','impacto_I','confiabilidade_C','fator_F','peso_beta2','K_acumulado','T_ano_base_configurado','homologacao_automatica'], ';');
foreach($series as $r){
    // Impede fórmulas de planilha contidas nos títulos importados, sem alterar banco.
    $titulo=(string)$r['title'];
    if(preg_match('/^[=+@\-\t\r]/u',$titulo))$titulo="'".$titulo;
    fputcsv($f,[$r['id'],$titulo,$r['year'],$r['year_inicio'],$r['year_fim'],$r['origem_data'],
        $r['provisorio_ano']?'SIM':'NAO',$r['I'],$r['C'],$r['F'],$r['score'],$r['k'],
        $r['t']===null?'INDEFINIDO':$r['t'],'NAO'], ';');
}
fclose($f);
