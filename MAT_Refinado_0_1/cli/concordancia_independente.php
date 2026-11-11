<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/ConcordanciaAvaliadores.php';
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente CLI');}
if(count($argv)!==3){fwrite(STDERR,"Uso: php cli/concordancia_independente.php avaliador_A.csv avaliador_B.csv\n");exit(2);}
try {
    $a=ConcordanciaAvaliadores::carregarCsv($argv[1]);
    $b=ConcordanciaAvaliadores::carregarCsv($argv[2]);
    echo json_encode(ConcordanciaAvaliadores::analisar($a,$b),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
} catch(Throwable $e){fwrite(STDERR,'ERRO: '.$e->getMessage()."\n");exit(1);}
