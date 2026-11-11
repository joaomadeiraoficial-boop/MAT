<?php
declare(strict_types=1);
/** MAT - Curadoria sem IA, Gemini APENAS pontuador, exportacao provisoria e estresse isolado. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once dirname(__DIR__).'/app/bootstrap.php';
foreach(['TriagemLoteReal','CuradoriaMarcos','PontuadorGemini','TemporisBacktest'] as $class){
    require_once dirname(__DIR__).'/app/'.$class.'.php';
}
$args=getopt('',['modo:','limite:','modelo:','refazer','ajuda']);
$mode=(string)($args['modo']??'diagnostico');
if(isset($args['ajuda'])||!in_array($mode,['diagnostico','pontuar','exportar','estresse-sintetico','estresse-provisorio'],true)){
    echo "Uso: php cli/laboratorio_cientifico.php --modo=diagnostico|pontuar|exportar|estresse-sintetico|estresse-provisorio [--limite=1] [--modelo=gemini-3.5-flash-lite] [--refazer]\n";
    exit(isset($args['ajuda'])?0:2);
}
$model=(string)($args['modelo']??'gemini-3.5-flash-lite');
$limit=max(1,min(10,(int)($args['limite']??1)));
if($mode==='pontuar'){
    if(!preg_match('/^gemini-[a-zA-Z0-9._-]{1,90}$/D',$model)){
        fwrite(STDERR,"Modelo Gemini invalido\n");exit(2);
    }
    if(trim((string)getenv('MAT_GEMINI_API_KEY'))===''){
        fwrite(STDERR,"MAT_GEMINI_API_KEY ausente. Nenhuma chamada realizada.\n");exit(2);
    }
    if(getenv('MAT_CONFIRMADO_FREE_TIER')!=='SIM'){
        fwrite(STDERR,"Antes de fazer chamadas, confirme Free Tier/sem faturamento no AI Studio. Entao defina MAT_CONFIRMADO_FREE_TIER=SIM nesta sessao.\n");exit(2);
    }
}
function matLabRead(string $path):?array{
    if(!is_file($path))return null;
    $decoded=json_decode((string)file_get_contents($path),true);
    return is_array($decoded)?$decoded:null;
}
function matLabWrite(string $path,array $data):void{
    $raw=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
    $tmp=$path.'.tmp-'.getmypid();
    if(file_put_contents($tmp,$raw,LOCK_EX)===false)throw new RuntimeException('Falha escrita JSON privado');
    if(!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Falha ao finalizar arquivo privado');}
}
function matLabCsv(string $path,array $columns,array $rows):void{
    $tmp=$path.'.tmp-'.getmypid();$fp=fopen($tmp,'wb');
    if(!$fp)throw new RuntimeException('Nao abriu CSV');
    fputcsv($fp,$columns,';');foreach($rows as $row)fputcsv($fp,$row,';');fclose($fp);
    if(!rename($tmp,$path))throw new RuntimeException('Nao gravou CSV');
}
function matLabExperiment(array $rows, bool $synthetic):array {
    if(!$synthetic && count($rows)<10)return ['estado'=>'AMOSTRA_INSUFICIENTE','n'=>count($rows),
        'mensagem'=>'Menos de 10 candidatos com 11 notas textualmente sustentadas; nao e validacao historica.'];
    $experiment=TemporisBacktest::experiment($rows,1920,2020);
    $drop=[];
    foreach($rows as $i=>$p){
        $key=(string)($p['id']??$i);
        if(hexdec(substr(hash('sha256',$key),0,2))%5!==0)$drop[]=$p;
    }
    $experimentDrop=TemporisBacktest::experiment($drop,1920,2020);
    $betas=[];
    foreach([1.0,1.5,2.0,2.5,3.0] as $beta){
        $scenario=[];
        foreach($rows as $p){
            // Simulacao: para dados reais usar componentes I, C, F salvos, sem pedir contas a IA.
            // Dados sinteticos existentes usam I=7.0, C=2/3, F=4/3.
            $i=(float)($p['componentes']['I']??7.0);
            $adjusted=$p;
            $adjusted['weight']=(float)$p['weight']*pow($i/10.0,$beta-2.0);
            $scenario[]=$adjusted;
        }
        $test=TemporisBacktest::experiment($scenario,1920,2020);
        $betas[]=['beta'=>$beta,'status'=>$test['status'],
            'k_1920'=>$test['k_origin'],'t_observado_2020'=>$test['t_observed'],
            'erro_modelo_media50'=>$test['models']['media50']['absolute_error']??null];
    }
    return ['estado'=>$synthetic?'SIMULACAO_SINTETICA':'EXPLORATORIO_NAO_VALIDADO',
        'n'=>count($rows),'base'=>$experiment,'retirada_aproximada_20pct_por_hash'=>$experimentDrop,
        'n_apos_retirada'=>count($drop),'analise_sensibilidade_beta'=>$betas,
        'aviso'=>'Nenhum resultado e confirmacao cientifica, e nao foi incluido no indice oficial.'];
}

try {
    $folder=TriagemLoteReal::directory(true);
    if($mode==='estresse-sintetico'){
        $result=matLabExperiment(TemporisBacktest::simulatedPoints(),true);
        matLabWrite($folder.DIRECTORY_SEPARATOR.'estresse_sintetico.json',$result);
        echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";exit;
    }
    $pdo=db();if(!$pdo)throw new RuntimeException('MySQL indisponivel. Inicie MySQL no XAMPP.');
    $rows=TriagemLoteReal::fetch300($pdo);
    if(!$rows)throw new RuntimeException('Lote 300 nao encontrado na tabela import_itens');
    $candidates=[];
    foreach($rows as $row){
        $id=(int)$row['id'];
        $historic=matLabRead($folder.DIRECTORY_SEPARATOR.'normalizacao_'.$id.'.json');
        $candidates[$id]=CuradoriaMarcos::resolve($row,$historic);
    }
    $duplicateLinks=CuradoriaMarcos::duplicateAlerts(array_values($candidates));
    $stats=['total'=>count($candidates),'eventos_definidos'=>0,'pontuacao_elegivel'=>0,'eventos_pendentes'=>0,
        'duplicatas_suspeitas'=>0,'pedidos_api_nesta_execucao'=>0,'notas_com_citacao_nesta_execucao'=>0,
        'propostas_completas_salvas'=>0,'falhas_nesta_execucao'=>0,
        'marcos_com_pendencias_de_pontuacao'=>0,'criterios_pendentes'=>0,'marcos_com_nota_zero_para_revisar'=>0];
    $manifest=[];$scores=[];$exploratory=[];$groups=[];$pendingRows=[];
    foreach($candidates as $id=>$c){
        $dups=$duplicateLinks[$id]??[];
        if($dups){$c['alertas'][]='POSSIVEL_DUPLICATA';$stats['duplicatas_suspeitas']++;}
        $c['possiveis_duplicatas_ids']=$dups;
        $good=$c['apto_pontuacao_assistida'] && !$dups;
        if($c['identificacao']!=='pendente')$stats['eventos_definidos']++;
        else $stats['eventos_pendentes']++;
        if($good)$stats['pontuacao_elegivel']++;
        $fingerprint=CuradoriaMarcos::fingerprint($c,$model,RevisedMethodology::VERSION.'|'.PontuadorGemini::VERSION);
        $path=$folder.DIRECTORY_SEPARATOR.'pontuacao_gemini_'.$id.'.json';
        $saved=matLabRead($path);
        if(is_array($saved) && ($saved['fingerprint']??'')!==$fingerprint)$saved=null;
        if($mode==='pontuar' && $good && $stats['pedidos_api_nesta_execucao']<$limit &&
            (isset($args['refazer']) || $saved===null || ($saved['estado']??'')==='ERRO')){
            $stats['pedidos_api_nesta_execucao']++;
            try{
                $answer=PontuadorGemini::request($c,$model);
                $saved=['versao'=>PontuadorGemini::VERSION,'id'=>$id,'estado'=>'PROPOSTA_NAO_VALIDADA',
                    'fingerprint'=>$fingerprint,'modelo'=>$answer['modelo'],'rubrica'=>RevisedMethodology::VERSION,
                    'uso'=>$answer['uso'],'avaliado_em'=>date(DATE_ATOM),'proposta'=>$answer['proposta'],
                    'origem'=>'gemini_somente_pontuacao','aprovado'=>false];
                $stats['notas_com_citacao_nesta_execucao']+=(int)$answer['proposta']['criterios_com_citacao'];
            }catch(Throwable $e){
                $stats['falhas_nesta_execucao']++;
                $err=$e->getMessage();
                $saved=['id'=>$id,'estado'=>'ERRO','erro'=>$err,'fingerprint'=>$fingerprint,'modelo'=>$model,'aprovado'=>false];
                fwrite(STDERR,"Falha candidato #$id: $err\n");
                matLabWrite($path,$saved);
                if(preg_match('/Gemini HTTP (400|401|402|403|404|429)\b/',$err)){
                    fwrite(STDERR,"Parando o lote para respeitar limites/erros do Gemini.\n");
                    // Anota o erro e interrompe sem varrer toda a fila. A exportacao pode ser executada depois.
                    break;
                }
            }
            matLabWrite($path,$saved);
        }
        $count=(int)($saved['proposta']['criterios_com_citacao']??0);
        $pendingInfo=is_array($saved) && ($saved['estado']??'')!=='ERRO'
            ? PontuadorGemini::pendingFromSaved($saved) : ['pendencias'=>[],'notas_zero'=>0];
        $hasZero=($pendingInfo['notas_zero']??0)>0;
        $full=$count===11 && !$hasZero
            && ($saved['proposta']['componentes_hipoteticos_nao_oficiais']??null)!==null;
        if(!empty($pendingInfo['pendencias'])){
            $stats['marcos_com_pendencias_de_pontuacao']++;
            $stats['criterios_pendentes']+=count($pendingInfo['pendencias']);
            foreach($pendingInfo['pendencias'] as $issue){
                $pendingRows[]=[$id,$c['titulo_candidato'],$issue['criterio'], $issue['rotulo'],
                    $issue['situacao'],$issue['o_que_falta'],'NAO_VALIDADO'];
            }
        }
        if($hasZero)$stats['marcos_com_nota_zero_para_revisar']++;
        if($full)$stats['propostas_completas_salvas']++;
        $c['modelo']=$model;
        if(is_string($c['chave_evento'])&&$c['chave_evento']!==''){
            $groups[$c['chave_evento']]['titulo']??=$c['titulo_candidato'];
            $groups[$c['chave_evento']]['ano']??=$c['ano'];
            $groups[$c['chave_evento']]['ids'][]=$id;
        }
        $c['pontuacao_proposta']=$full?'COMPLETA_TEXTUAL':
            ($hasZero?'REVISAR_CRITERIO_NAO_ATENDIDO':($saved!==null?'INCOMPLETA_OU_ERRO':'PENDENTE'));
        $manifest[]=[$id,$c['titulo_original'],$c['titulo_candidato'],$c['ano'],
            $c['identificacao'],$c['fonte_declarada'],$c['referencia_pagina'],
            $c['trecho_sha256'],implode('|',$c['alertas']),implode('|',$dups),
            $good?'SIM':'NAO',$c['pontuacao_proposta'],$count,'NAO_VALIDADO'];
        if($full){
            $p=$saved['proposta']['componentes_hipoteticos_nao_oficiais'];
            $scores[]=[$id,$c['titulo_candidato'],$c['ano'],$p['I'],$p['C'],$p['F'],$p['w'],
                $c['fonte_declarada'],$c['referencia_pagina'],$c['trecho_sha256'],
                $c['identificacao'],$c['pontuacao_proposta'],'NAO_VERIFICADO'];
            if($good && $c['ano']!==null)$exploratory[]=['year'=>$c['ano'],'weight'=>$p['w'],'id'=>$id,'componentes'=>['I'=>$p['I'],'C'=>$p['C'],'F'=>$p['F']]];
        }
    }
    $manifestFile=$folder.DIRECTORY_SEPARATOR.'curadoria_manifesto.csv';
    matLabCsv($manifestFile,['id','titulo_original','titulo_candidato','ano','tipo_identificacao',
        'fonte_declarada','pagina','sha256_trecho','alertas','duplicatas_ids','apto_pontuacao',
        'situacao_pontuacao','criterios_com_citacao','status_cientifico'],$manifest);
    $groupRows=[];
    foreach($groups as $key=>$g){
        $groupRows[]=[$key,$g['titulo'],$g['ano'],implode('|',$g['ids']),count($g['ids']),
            count($g['ids'])>1?'REVISAR_POSSIVEL_DUPLICATA':'CANDIDATO_UNICO', 'NAO_VALIDADO'];
    }
    matLabCsv($folder.DIRECTORY_SEPARATOR.'grupos_eventos_suspeitos.csv',
        ['chave_normalizada','titulo','ano','ids_importados','quantidade','estado_grupo','validacao'], $groupRows);
    $pendingFile=$folder.DIRECTORY_SEPARATOR.'pendencias_criterios_gemini.csv';
    matLabCsv($pendingFile,['id','titulo','criterio_chave','criterio_nome','situacao','o_que_falta','validacao'], $pendingRows);
    $scoreFile=$folder.DIRECTORY_SEPARATOR.'propostas_completas_para_revisao.csv';
    matLabCsv($scoreFile,['id','titulo','ano','I','C','F','peso_beta2','fonte_declarada','pagina',
        'sha256_trecho','identificacao','situacao','fonte_externa_verificada'],$scores);
    if($mode==='estresse-provisorio'){
        $result=matLabExperiment($exploratory,false);
        matLabWrite($folder.DIRECTORY_SEPARATOR.'estresse_provisorio.json',$result);
        echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";exit;
    }
    echo json_encode(['estado'=>'OK','versao'=>'laboratorio-cientifico-1.0','modo'=>$mode,
        'curadoria'=>$manifestFile,'propostas'=>$scoreFile,'pendencias'=>$pendingFile,'contagens'=>$stats,
        'aviso'=>'Gemini apenas pontua, nunca identifica nem calcula. Sem fontes externas verificadas; zero inclusoes oficiais.'],
        JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n";
}catch(Throwable $e){fwrite(STDERR,"LABORATORIO: ".$e->getMessage()."\n");exit(1);}
