<?php
declare(strict_types=1);
/** Teste OFFLINE: nao exige MySQL, API ou valores reais do acervo. */
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/CuradoriaMarcos.php';
require_once dirname(__DIR__).'/app/PontuadorGemini.php';
require_once dirname(__DIR__).'/app/TemporisBacktest.php';
function assertLab(bool $yes,string $message):void{
    if(!$yes)throw new RuntimeException('FALHOU: '.$message);
    echo "[OK] $message\n";
}
$excerpt='No ano de 1769 a maquina a vapor passou por uma melhoria importante para usos industriais. ' .
         'O documento cita o processo e sua data de forma explicita.';
$row=['id'=>101,'titulo'=>'Maquina a vapor','ano'=>1769,'fonte'=>'Livro historico, p. 52',
    'descricao'=>'Melhoria tecnologica','bruto_json'=>json_encode(['trecho_evidencia'=>$excerpt,'pagina_livro'=>52])];
$c=CuradoriaMarcos::resolve($row,null);
assertLab($c['apto_pontuacao_assistida'] && !$c['aprovado'],'Curadoria sem IA aceita candidato definido sem aprova-lo');
$bad=$row;$bad['titulo']='A revisar: trecho do livro';
assertLab(!CuradoriaMarcos::resolve($bad,null)['apto_pontuacao_assistida'],'Titulo provisório impede pontuacao automatica');
$historic=['proposta'=>['tipo'=>'evento_tecnologico','titulo_proposto'=>'Maquina a vapor',
  'ano_proposto'=>1769,'citacao_literal'=>'No ano de 1769','citacao_encontrada'=>true,'identificacao_util'=>true]];
assertLab(CuradoriaMarcos::resolve($bad,$historic)['apto_pontuacao_assistida'],
  'Identificacao previamente salva pode ser reutilizada sem nova chamada');
$row2=$row;$row2['id']=102;
$dup=CuradoriaMarcos::duplicateAlerts([$c,CuradoriaMarcos::resolve($row2,null)]);
assertLab($dup[102]===[101],'Duplicata por mesmo evento/ano ou trecho gera alerta e nao altera dados');
$fp=CuradoriaMarcos::fingerprint($c,'gemini-3.5-flash-lite',RevisedMethodology::VERSION);
$changed=$c;$changed['trecho_sha256']=hash('sha256','outro trecho');
assertLab($fp!==CuradoriaMarcos::fingerprint($changed,'gemini-3.5-flash-lite',RevisedMethodology::VERSION),
  'Cache invalida quando fonte muda');
$rubric=PontuadorGemini::rubric();
assertLab(count($rubric)===11 && array_keys($rubric)===array_keys(RevisedMethodology::ALL),
 'As 11 chaves sao as mesmas da metodologia matematica revisada');
$raw=[];
foreach($rubric as $field=>$guide){
 $raw[$field]=['nota'=>'2','citacao'=>'No ano de 1769 a maquina a vapor passou por uma melhoria importante',
   'justificativa'=>'Evidencia textual preliminar; requer confirmacao externa'];
}
$raw['conf_independencia']['nota']='3';
$raw['conf_rastreabilidade']['nota']='3';
$v=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
assertLab($v['criterios']['conf_independencia']['nota']===null && $v['criterios']['conf_rastreabilidade']['nota']===null,
 'Fonte unica nao sustenta independencia 3 nem rastreabilidade primaria 3');
assertLab($v['criterios_com_citacao']===9 && $v['componentes_hipoteticos_nao_oficiais']===null,
 'Avaliacao incompleta NAO gera peso matematico');
$raw['conf_independencia']['nota']='1';$raw['conf_rastreabilidade']['nota']='2';
$v=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
$calc=$v['componentes_hipoteticos_nao_oficiais'];
assertLab($v['criterios_com_citacao']===11 && is_array($calc) && $calc['w']>0 && $v['aprovado']===false,
 '11 evidencias validas geram APENAS peso hipotetico pelo PHP');
$raw['impacto_difusao']['citacao']='Citacao inventada';
$v=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
assertLab($v['criterios']['impacto_difusao']['nota']===null && $v['criterios_com_citacao']===10,
 'Citação inexistente nunca vira nota validada');
$raw['impacto_difusao']['nota']='sem_evidencia';
$v=PontuadorGemini::verify(['criterios'=>$raw],$excerpt,$c);
assertLab($v['criterios']['impacto_difusao']['nota']===null,
 'Sem evidencia nao e nota zero');
$markers=[['id'=>101,'ano'=>1769,'status'=>'pendente','incluir_temporis'=>0,
 'escopo_revisado'=>0,'fonte_vinculada'=>0]];
$official=TemporisBacktest::approvedPoints($markers,[],2020);
assertLab($official['included']===0,'Pontuacao Gemini nunca ingressa no calculo oficial automaticamente');
$sim=TemporisBacktest::experiment(TemporisBacktest::simulatedPoints(),1920,2020);
assertLab(($sim['status']??'')==='comparavel','Motor de estresse sintetico continua funcional');
echo "[OK] Laboratorio cientifico: 13 verificacoes offline aprovadas.\n";
