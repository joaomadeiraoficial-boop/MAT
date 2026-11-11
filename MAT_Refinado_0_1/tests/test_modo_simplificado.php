<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaPolitica.php';
require_once dirname(__DIR__).'/app/CuradoriaRevisao.php';
require_once dirname(__DIR__).'/app/CuradoriaNormalizacaoMinima.php';
function testM(bool $v,string $msg):void {if(!$v)throw new RuntimeException($msg);echo "[OK] $msg\n";}
$fontes=[['fonte_id'=>'LIVRO','titulo'=>'Obra-base']];
$criterios=[];foreach(CuradoriaRodadas::CRITERIOS as $code=>$nome){$criterios[$code]=['nota'=>1,'situacao'=>CuradoriaPolitica::SITUACAO_MINIMA,'justificativa'=>'Nota mínima provisória sem evidência específica suficiente; precisa pesquisa.','fontes_ids_json'=>'[]'];}
$r=['titulo_proposto'=>'Inovação histórica teste','ano_original'=>1830,'ano_validado'=>null,'fontes'=>$fontes,'criterios'=>$criterios,'pendencias'=>['Revisar impacto'], 'duplicatas'=>['MCD-90001'],'situacao_identificacao'=>'IDENTIFICADO'];
testM(CuradoriaRevisao::impedimentos($r,CuradoriaPolitica::SIMPLIFICADO)===[],'Sem impedimentos preliminares com fonte, 11 notas e data extraída');
testM(count(CuradoriaRevisao::impedimentos($r,CuradoriaPolitica::RIGOROSO))>=3,'Rigoroso preserva pendências, data não validada e notas provisórias');
testM(count(CuradoriaPolitica::avisos($r))>=3,'Avisos distinguem duplicidade, pendência, ano e notas provisórias');
$mem=CuradoriaRodadas::memory($criterios,$fontes);
testM($mem['completa']===true && $mem['notas_provisorias']===11,'Prévia calcula 11 notas mínimas, mas identifica 11 provisórias');
$r2=$r;$r2['fontes']=[];
testM(CuradoriaRevisao::impedimentos($r2,CuradoriaPolitica::SIMPLIFICADO)!==[],'Sem fonte geral não há aprovação preliminar');
$r2=$r;$r2['ano_original']=null;
testM(CuradoriaRevisao::impedimentos($r2,CuradoriaPolitica::SIMPLIFICADO)!==[],'Sem data não há aprovação preliminar');
$r2=$r;$r2['criterios']['impacto_difusao']['nota']=0;
testM(CuradoriaRevisao::impedimentos($r2,CuradoriaPolitica::SIMPLIFICADO)!==[],'Zero deve ser corrigido para mínimo 1');
$r2=$r;$r2['situacao_identificacao']='CONTEXTO_NAO_MARCO';
testM(CuradoriaRevisao::impedimentos($r2,CuradoriaPolitica::SIMPLIFICADO)!==[],'Contexto não é aprovado automaticamente');
$raw=file_get_contents(dirname(__DIR__).'/app/TemporisCuradoria.php');
testM(str_contains($raw,'CuradoriaPolitica::RIGOROSO') && str_contains($raw,'nota,situacao,justificativa'),'Série oficial preserva análise rigorosa e situação da nota');
$actions=file_get_contents(dirname(__DIR__).'/actions.php');
testM(str_contains($actions,"$".'action' . "==='curadoria_minimos_lote'") || str_contains($actions,'curadoria_minimos_lote'),'Operação de lote configurada');
$view=file_get_contents(dirname(__DIR__).'/views/fila_rapida.php');
testM(str_contains($view,'curadoria_modo') && str_contains($view,'curadoria_minimos_lote') && str_contains($view,'csrf_field()'),'Controles do novo modo protegidos por CSRF');
echo "SUCESSO: modo simplificado, salvaguardas e lote.\n";
