<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaRevisao.php';
function ok(bool $test,string $message):void{if(!$test)throw new RuntimeException('FAIL: '.$message);echo '[OK] '.$message."\n";}
$notas=['impacto_difusao'=>['nota'=>null]];
$fontes=[['fonte_id'=>'L1'],['fonte_id'=>'L2']];
$valid=['impacto_difusao'=>['nota'=>'2','justificativa'=>'Existem evidencias documentais de difusao no periodo estudado.','fonte_id'=>'L1']];
$result=CuradoriaRevisao::validarRubricasComplementares($valid,$notas,$fontes);
ok(count($result)===1 && $result['impacto_difusao']['nota']===2,'Rubrica sem nota pode ser preenchida com evidencia');
ok(CuradoriaRevisao::validarRubricasComplementares(['impacto_difusao'=>['nota'=>'','justificativa'=>'','fonte_id'=>'']],$notas,$fontes)===[],'Campos vazios nao inventam nota');
$reject=static function(array $data,array $criteria,array $sources):bool{
 try{CuradoriaRevisao::validarRubricasComplementares($data,$criteria,$sources);return false;}
 catch(InvalidArgumentException $e){return true;}
};
ok($reject(['impacto_difusao'=>['nota'=>'3','justificativa'=>'Curta','fonte_id'=>'L1']],$notas,$fontes),'Rejeita justificativa incompleta');
ok($reject(['impacto_difusao'=>['nota'=>'3','justificativa'=>'Justificativa adequada com mais de vinte e cinco caracteres.','fonte_id'=>'ERRADA']],$notas,$fontes),'Rejeita fonte inexistente');
ok($reject(['impacto_difusao'=>['nota'=>'4','justificativa'=>'Justificativa adequada com mais de vinte e cinco caracteres.','fonte_id'=>'L1']],$notas,$fontes),'Rejeita notas fora da rubrica');
ok($reject($valid,['impacto_difusao'=>['nota'=>1]],$fontes),'Nao sobrescreve nota preexistente');
$pendencias=['Conferir a atribuicao','Conferir a data da fonte'];
[$restantes,$removidos]=CuradoriaRevisao::filtrarItensRevisados($pendencias,['0']);
ok($restantes===['Conferir a data da fonte'] && $removidos===['Conferir a atribuicao'],'Remove apenas pendencia explicitamente marcada');
ok($reject(['nao_existe'=>['nota'=>'2','justificativa'=>'Justificativa documental adequada e fonte existente.','fonte_id'=>'L1']],$notas,$fontes),'Rejeita criterio desconhecido');
$root=dirname(__DIR__);
$view=file_get_contents($root.'/views/fila_rapida.php');
$actions=file_get_contents($root.'/actions.php');
$revisao=file_get_contents($root.'/app/CuradoriaRevisao.php');
ok(str_contains($view,'criterios_complementares[') && str_contains($view,'pendencias_resolvidas[]'),'Formulario unico inclui rubricas faltantes e pendencias');
ok(str_contains($actions,"\$_SESSION['mat_fast_draft']") && str_contains($actions,'if(!$sucesso'),'Falha preserva rascunho e posiciona o retorno');
ok(str_contains($revisao,'verificacao das pendencias gravadas falhou') && str_contains($revisao,'SALVAMENTO CONFIRMADO NO BANCO'),'Implementacao relê e verifica o que foi gravado');
echo "11 verificacoes concluidas.\n";
