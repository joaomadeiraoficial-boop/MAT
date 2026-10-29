<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente CLI');}
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/PontuacaoAcervoIA.php';
require_once dirname(__DIR__).'/app/PontuacaoCompat.php';
PontuacaoCompat::initialize();
$opts=getopt('', ['modo:','limite::','refazer','ajuda']);
$mode=(string)($opts['modo']??'resumo');
if(isset($opts['ajuda'])){echo "Uso: --modo=resumo|ia [--limite=2] [--refazer]\n";exit(0);}
if(!in_array($mode,['resumo','ia'],true)){fwrite(STDERR,"Modo invalido\n");exit(2);}
$pdo=db();
if(!$pdo){fwrite(STDERR,"MySQL indisponivel. Ligue XAMPP e confira a configuracao.\n");exit(2);}
$markers=all_markers();
if(!$markers){echo "Acervo sem marcos cadastrados.\n";exit(0);}
$max=max(1,min(200,(int)($opts['limite']??2)));
$count=['total'=>count($markers),'com_trecho'=>0,'sem_trecho'=>0,'confirmados'=>0,'propostas_salvas'=>0,'avaliados_nesta_execucao'=>0,'erros'=>0];
try {
    if($mode==='ia'){
        if(!PontuacaoCompat::methodReady())throw new RuntimeException('Metodologia revisada indisponivel. Instale o componente MAT 0.2 antes de pontuar.');
        if(!PontuacaoCompat::schemaReady())throw new RuntimeException('Repositorio ou tabela de rubricas revisadas nao disponivel. Corrija a integracao antes de solicitar propostas de IA.');
        require_once dirname(__DIR__).'/app/AssistenteCientifico.php';
        if(!function_exists('curl_init'))throw new RuntimeException('cURL nao habilitado no PHP.');
        fwrite(STDERR,"MAT / Pontuacao assistida para acervo. A IA nao altera nem aprova marcos.\n");
    }
    if(!PontuacaoCompat::schemaReady())fwrite(STDERR,"[AVISO] Nao foi possivel consultar avaliacoes revisadas; nenhuma avaliacao sera alterada.\n");
    foreach($markers as $m){
        $id=(int)$m['id'];
        if(($m['status']??'')==='rejeitado' || ($m['status']??'')==='demonstracao')continue;
        $sources=linked_sources($id);
        $usable=PontuacaoAcervoIA::usableSources($sources);
        $old=PontuacaoCompat::evaluation($id);
        if(($old['estado']??'')==='confirmada')$count['confirmados']++;
        if($usable)$count['com_trecho']++;else $count['sem_trecho']++;
        $prior=PontuacaoAcervoIA::load($id);
        if($prior && ($prior['status']??'')==='concluido')$count['propostas_salvas']++;
        if($mode!=='ia' || $count['avaliados_nesta_execucao'] >= $max || !$usable
            || ($old['estado']??'')==='confirmada'
            || ($prior && in_array($prior['status']??'', ['concluido','erro'],true) && !isset($opts['refazer'])))continue;
        $count['avaliados_nesta_execucao']++;
        fwrite(STDERR,"[{$count['avaliados_nesta_execucao']}/{$max}] #$id ".substr((string)$m['titulo'],0,65)."\n");
        $record=['versao'=>PontuacaoAcervoIA::VERSION,'marco_id'=>$id,'titulo'=>$m['titulo'],'ano'=>$m['ano'],
            'fontes_vinculadas'=>count($sources),'fontes_com_trecho'=>count($usable),
            'avaliado_em'=>date(DATE_ATOM)];
        try {
            $raw=AssistenteCientifico::queryOllama($m,$usable,'marco');
            $record['proposta']=PontuacaoAcervoIA::proposal($raw,count($sources));
            $record['status']='concluido';
            fwrite(STDERR,"  [OK] {$record['proposta']['notas_citadas']}/11 notas com citacao literal; revisao humana obrigatoria.\n");
        } catch(Throwable $e){$record['status']='erro';$record['erro']=$e->getMessage();$count['erros']++;fwrite(STDERR,"  [ERRO] ".$e->getMessage()."\n");}
        PontuacaoAcervoIA::save($id,$record);
    }
    echo json_encode($count,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
    echo 'Propostas privadas: '.PontuacaoAcervoIA::directory()."\n";
    echo "Nenhuma nota foi escrita no banco ou aprovada automaticamente.\n";
} catch(Throwable $e){fwrite(STDERR,'FALHA: '.$e->getMessage()."\n");exit(1);}
