<?php
declare(strict_types=1);
/** Gera relatorio privado da auditoria documental, sem escrever no MySQL. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/AuditoriaAmbiguidade.php';
$pdo=db();
if(!$pdo){fwrite(STDERR,"MySQL indisponivel. Ative MySQL no XAMPP.\n");exit(2);}
try {
    $rows=TriagemLoteReal::fetch300($pdo);
    if(!$rows)throw new RuntimeException('Lote dos 300 nao encontrado; importe o CSV antes.');
    $folder=TriagemLoteReal::directory(true);
    $path=$folder.DIRECTORY_SEPARATOR.'auditoria_ambiguidades.csv';
    $fp=fopen($path,'wb');if(!$fp)throw new RuntimeException('Nao foi possivel criar CSV privado.');
    fputcsv($fp,['id','ano','titulo','situacao','qtd_riscos','riscos_altos','codigos','mensagens'],';');
    $blocked=0;$allRisks=[];
    foreach($rows as $row){
        $result=AuditoriaAmbiguidade::evaluate($row);
        if($result['situacao']==='BLOQUEAR_PONTUACAO')$blocked++;
        $codes=array_column($result['riscos'],'codigo');
        foreach($codes as $code)$allRisks[$code]=($allRisks[$code]??0)+1;
        fputcsv($fp,[$row['id'],$row['ano'],$row['titulo'],$result['situacao'],
            $result['quantidade_riscos'],$result['riscos_altos'],implode(' | ',$codes),
            implode(' | ',array_column($result['riscos'],'mensagem'))],';');
    }
    fclose($fp);
    $summary=['versao'=>AuditoriaAmbiguidade::VERSION,'total'=>count($rows),
        'bloqueados_preliminarmente'=>$blocked,'revisao_documental'=>count($rows)-$blocked,
        'contagem_alertas'=>$allRisks,'avaliados_cientificamente'=>0,
        'aviso'=>'Este resultado NAO certifica candidatos nem aprova notas.'];
    file_put_contents($folder.DIRECTORY_SEPARATOR.'auditoria_ambiguidades_resumo.json',
        json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),LOCK_EX);
    echo json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
    echo "CSV privado: $path\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
