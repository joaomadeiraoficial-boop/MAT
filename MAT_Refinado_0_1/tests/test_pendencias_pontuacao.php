<?php
declare(strict_types=1);
/** Casos OFFLINE: sem banco, rede, chave Gemini ou dados do usuario. */
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/CuradoriaMarcos.php';
require_once dirname(__DIR__).'/app/PontuadorGemini.php';
require_once dirname(__DIR__).'/app/PainelCientifico.php';
function mat10assert(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException('FALHOU: '.$description);
    echo "[OK] $description\n";
}
$excerpt='O documento historico de 1991 relata a implementacao de um sistema de hipertexto, adotado em ambiente tecnico e descrito no registro.';
$row=['id'=>5101,'titulo'=>'Sistema de hipertexto','ano'=>1991,'fonte'=>'Arquivo historico p. 32',
    'bruto_json'=>json_encode(['trecho_evidencia'=>$excerpt,'pagina_livro'=>32])];
$c=CuradoriaMarcos::resolve($row,null);
$quote='O documento historico de 1991 relata a implementacao de um sistema de hipertexto';
$raw=[];
foreach(PontuadorGemini::rubric() as $name=>$description){
    $raw[$name]=['nota'=>'1','citacao'=>$quote,'justificativa'=>'Indicio preliminar de contribuicao pequena.'];
}
$good=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($good['criterios_com_citacao']===11 && $good['situacao_geral']==='PROPOSTA_COMPLETA_AGUARDANDO_AUDITORIA','Onze notas de 1 a 3, textualmente sustentadas, geram proposta completa');
mat10assert(is_array($good['componentes_hipoteticos_nao_oficiais']) && !$good['aprovado'] && !$good['pode_entrar_temporis_oficial'], 'Peso calculado pelo PHP continua hipotetico e nao aprovado');
$raw['impacto_difusao']['nota']='0';
$zero=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($zero['criterios']['impacto_difusao']['nota']===null && $zero['criterios']['impacto_difusao']['situacao']==='CRITERIO_NAO_ATENDIDO', 'Zero vira criterio a revisar, nunca uma nota valida');
mat10assert($zero['criterios_nao_atendidos']===1 && $zero['componentes_hipoteticos_nao_oficiais']===null, 'Um criterio nao atendido impede peso provisorio completo');
mat10assert(str_contains($zero['pendencias'][0]['o_que_falta'],'reavaliar o enquadramento'), 'Pendencia informa o que o pesquisador precisa conferir');
$raw['impacto_difusao']['nota']='nao_atende';
$no=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($no['criterios_nao_atendidos']===1 && $no['criterios_com_citacao']===10, 'Resposta nao_atende segue regra de revisao');
$raw['impacto_difusao']['nota']='sem_evidencia';
$unknown=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($unknown['criterios_nao_atendidos']===0 && $unknown['criterios']['impacto_difusao']['situacao']==='EVIDENCIA_INSUFICIENTE', 'Sem evidencia nao equivale a criterio nao atendido');
$raw['impacto_difusao']['nota']='2';
$raw['impacto_difusao']['citacao']='Frase que nao existe no documento';
$bad=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($bad['criterios']['impacto_difusao']['situacao']==='CITACAO_NAO_LOCALIZADA', 'Citacao inventada recebe diagnostico preciso');
$raw['impacto_difusao']['citacao']=$quote;
$raw['conf_independencia']['nota']='3';
$high=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($high['criterios']['conf_independencia']['situacao']==='FONTES_INDEPENDENTES_NAO_VERIFICADAS', 'Uma fonte unica nao comprova independencia 3');
$raw['conf_independencia']['nota']='1';
$raw['conf_datacao']['justificativa']='';
$empty=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
mat10assert($empty['criterios']['conf_datacao']['situacao']==='JUSTIFICATIVA_AUSENTE','Justificativa ausente impede pontuacao completa');
$cache=['proposta'=>['criterios'=>['impacto_difusao'=>['nota'=>0,'situacao'=>'PROPOSTA_COM_CITACAO']]]];
$legacy=PontuadorGemini::pendingFromSaved($cache);
mat10assert($legacy['notas_zero']===1 && $legacy['pendencias'][0]['situacao']==='CRITERIO_NAO_ATENDIDO','Zero em resultado anterior sinaliza revisao sem refazer API');
$folder=sys_get_temp_dir().'/mat10-'.bin2hex(random_bytes(5));
if(!mkdir($folder,0700,true))throw new RuntimeException('Nao conseguiu criar pasta temporaria');
try {
    $completeRaw=[];
    foreach(PontuadorGemini::rubric() as $key=>$guide){
        $completeRaw[$key]=['nota'=>'1','citacao'=>$quote,'justificativa'=>'Contribuicao pequena.'];
    }
    $completeRaw['impacto_difusao']['nota']='0';
    $v=PontuadorGemini::verify(['criterios'=>$completeRaw],$excerpt,$c);
    $model='gemini-3.5-flash-lite';
    $saved=['estado'=>'PROPOSTA_NAO_VALIDADA','modelo'=>$model,
        'fingerprint'=>CuradoriaMarcos::fingerprint($c,$model,RevisedMethodology::VERSION.'|'.PontuadorGemini::VERSION),
        'proposta'=>$v];
    file_put_contents($folder.'/pontuacao_gemini_5101.json',json_encode($saved,JSON_THROW_ON_ERROR));
    $panel=PainelCientifico::resumir([$row],$folder);
    mat10assert($panel['marcos_com_nota_zero']===1 && $panel['propostas_11']===0,'Painel nao conta nota zero como proposta completa');
    mat10assert($panel['fila'][0]['acao']==='REVISAR_CRITERIO_NAO_ATENDIDO' && str_contains($panel['fila'][0]['pendencia_resumo'],'Difus'), 'Painel indica criterio especifico e proximo passo');
} finally {
    foreach(glob($folder.'/*')?:[] as $file)@unlink($file);
    @rmdir($folder);
}
echo "[OK] Pendencias e notas Gemini: 13 verificacoes offline aprovadas.\n";
