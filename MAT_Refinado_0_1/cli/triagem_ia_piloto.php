<?php
declare(strict_types=1);
/**
 * Experimento assistido por Qwen local com no maximo 3 candidatos por chamada.
 * SOMENTE leitura no MySQL; sem insercoes/atualizacoes/aprovacoes.
 * Salve a saida privada FORA de htdocs e NAO publique no GitHub.
 */
if(PHP_SAPI!=='cli'){http_response_code(403);exit("CLI only\n");}
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/TemporisPiloto.php';
require_once __DIR__.'/../app/AssistenteCientifico.php';
$limit=1;
foreach(array_slice($argv,1) as $arg) {
    if(preg_match('/^--limite=(\d+)$/',$arg,$m))$limit=max(1,min(3,(int)$m[1]));
    elseif($arg==='--ajuda') {fwrite(STDERR,"Uso: php cli/triagem_ia_piloto.php --limite=1 (max. 3)\n");exit(0);}
    else {fwrite(STDERR,"Opcao desconhecida: $arg\n");exit(2);}
}
$pdo=db();
if(!$pdo){fwrite(STDERR,"ERRO: conecte MySQL e confira config.local.php antes do experimento.\n");exit(2);}
if(!function_exists('curl_init')){fwrite(STDERR,"ERRO: PHP cURL precisa estar habilitado no php.ini do XAMPP.\n");exit(2);}
// Busca somente registros na fila de preparacao, sem depender de novos campos ou gravacoes.
$q=$pdo->query("SELECT id, titulo, ano, categoria, descricao, fonte, bruto_json FROM import_itens WHERE status='pendente' ORDER BY id DESC LIMIT 180");
$chosen=[];
foreach($q as $row) {
    $raw=json_decode((string)$row['bruto_json'],true);
    $text=trim((string)($raw['trecho_evidencia']??''));
    if(strlen($text)<70)continue;
    if(str_starts_with((string)$row['titulo'],'A revisar:')){ $chosen[]=$row; }
    if(count($chosen)>=$limit)break;
}
if(!$chosen){fwrite(STDERR,"Nenhum candidato com trecho documental minimo na amostra mais recente.\n");exit(2);}
$results=[];
foreach($chosen as $i=>$row){
    $id=(int)$row['id'];
    fwrite(STDERR,"[".($i+1)."/".count($chosen)."] Qwen analisando candidato ID $id; pode ser demorado na GTX 1050...\n");
    $out=['id'=>$id,'titulo'=>(string)$row['titulo'],'ano'=>$row['ano'],
        'estado'=>'PROPOSTA_NAO_VALIDADA','completo'=>false,'peso_provisorio'=>null,'criterios'=>[]];
    try {
        $answer=AssistenteCientifico::queryOllama($row,[],'import');
        $scores=[];$completo=true;
        foreach(TemporisPiloto::FIELDS as $field) {
            $entry=$answer['criterios'][$field]??[];
            $out['criterios'][$field]=$entry;
            if(($entry['nota']??null)===null){$completo=false;}
            else {$scores[]=$entry['nota'];}
        }
        $out['natureza_sugerida']=$answer['natureza'];
        $out['acao_sugerida']=$answer['acao_sugerida'];
        $out['riscos']=$answer['riscos'];
        if($completo && count($scores)===11){
            $out['completo']=true;
            $out['componentes_matematicos']=TemporisPiloto::calc($scores);
            $out['peso_provisorio']=$out['componentes_matematicos']['w'];
        } else {
            $out['motivo']='Uma ou mais das 11 notas nao possui evidencia literal localizada; nenhuma nota foi inventada.';
        }
    } catch(Throwable $e) {$out['erro']=$e->getMessage();}
    $results[]=$out;
}
$json=['rotulo'=>'EXPERIMENTO_IA_NAO_VALIDADO','modelo'=>AssistenteCientifico::MODEL,
 'criterios'=>TemporisPiloto::FIELDS,
 'avisos'=>[
  'Nao escreve no banco de dados nem altera o indice cientifico oficial.',
  'Citar texto presente no trecho nao confirma sua interpretacao ou independência documental.',
  'Nunca normalizar uma Temporis historica a partir de candidatos automaticamente selecionados sem revisao humana.',
  'As notas ausentes permanecem null, NUNCA zero.'
 ],'resultado'=>$results];
echo json_encode($json,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
