<?php
declare(strict_types=1);
/** Use: C:\xampp\php\php.exe cli\diagnosticar_temporis.php */
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TemporisFinal.php';
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Apenas linha de comando.');}
echo "DIAGNOSTICO DE ORIGEM / INDICE TEMPORIS\n";
echo "Curadoria do MAT Refinado: ".CuradoriaRodadas::databaseName()."\n";
echo "Banco isolado MAT Final: ".(string)cfg('temporis_final_db_name','mat_final_curadoria')."\n";
echo "Driver PDO MySQL: ".(extension_loaded('pdo_mysql')?'disponivel':'INDISPONIVEL')."\n\n";
foreach(['curadoria','final','snapshot','auto'] as $src){
    try {
        $r=TemporisFinal::obterDados($src,2026);
        $a=$r['dados']['audit'];
        echo strtoupper($src)." => ORIGEM ".$r['fonte']." (".$r['database'].")\n";
        echo "  Total: {$a['total']} | Elegiveis: {$a['elegiveis']} | Rejeitados: {$a['rejeitados']} | Sem 11 notas: {$a['sem_11_notas']} | Sem data: {$a['sem_data']} | Futuros: {$a['futuros']}\n";
    } catch(Throwable $e){echo strtoupper($src)." => INDISPONIVEL: ".$e->getMessage()."\n";}
}
echo "\nSomente leitura: nenhum registro foi gravado ou homologado.\n";
