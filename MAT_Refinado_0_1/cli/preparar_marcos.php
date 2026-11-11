<?php
declare(strict_types=1);
/** Gera filas privadas, sem IA, SEM atualizacao no MySQL. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/PainelCientifico.php';
try {
    $pdo=db();
    if(!$pdo)throw new RuntimeException('MySQL indisponivel. Ligue o MySQL no XAMPP.');
    $summary=PainelCientifico::consultar($pdo);
    if($summary['estado']!=='OK')throw new RuntimeException('Lote de candidatos indisponivel no banco.');
    $folder=TriagemLoteReal::directory(true);
    // BOM e separador ; para facilitar no Excel em portugues, sem vazamento para htdocs.
    $csv=$folder.DIRECTORY_SEPARATOR.'fila_priorizada_marcos.csv';
    $tmp=$csv.'.tmp-'.getmypid();
    $f=fopen($tmp,'wb');
    if(!$f)throw new RuntimeException('Nao foi possivel abrir o CSV privado');
    fwrite($f,"\xEF\xBB\xBF");
    fputcsv($f,['id_import_item','titulo_candidato','ano','prioridade','proxima_acao',
        'caracteres_trecho','identificacao','fonte_declarada','alertas','possivel_duplicata',
        'situacao_cientifica'],';');
    foreach($summary['fila'] as $row){
        $safe=static function(string $value):string {
            return preg_match('/^\s*[=+@\t\r]/u',$value) ? "'".$value : $value;
        };
        fputcsv($f,[$row['id'],$safe($row['titulo']),$row['ano'],$row['prioridade']+1,
            $row['acao'],$row['trecho_caracteres'],$row['identificacao'],
            $safe($row['fonte_declarada']),implode('|',$row['alertas']),$row['duplicata']?'SIM':'NAO',
            'NAO_VALIDADO'],';');
    }
    fclose($f);
    if(!rename($tmp,$csv)){@unlink($tmp);throw new RuntimeException('Nao concluiu CSV');}
    $jsonFile=$folder.DIRECTORY_SEPARATOR.'resumo_progresso_marcos.json';
    $counts=$summary;
    unset($counts['fila']);
    $counts['gerado_em']=date(DATE_ATOM);
    $content=json_encode($counts,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    if(file_put_contents($jsonFile,$content,LOCK_EX)===false)throw new RuntimeException('Nao gravou resumo');
    echo json_encode(['status'=>'OK','fila_csv'=>$csv,'acervo_anterior'=>'EXCLUIDO_DO_ESCOPO_SEM_EXCLUIR_MYSQL','resumo_json'=>$jsonFile,
        'contagens'=>array_intersect_key($summary,array_flip(['total','trechos_suficientes','trechos_pendentes',
            'eventos_definidos','aptos_pontuacao','propostas_11','duplicatas_suspeitas'])),
        'proximas_acoes'=>$summary['contagem_acoes'],
        'aviso'=>'Somente planejamento; nao houve chamadas Gemini, modificacao do banco nem aprovacoes.'],
        JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,'MAT PREPARACAO: '.$e->getMessage()."\n");exit(1);}
