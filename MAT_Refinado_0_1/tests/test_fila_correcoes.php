<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaFilaRapida.php';
function prova(bool $ok,string $msg): void{if(!$ok)throw new RuntimeException('FALHOU: '.$msg);echo "[OK] $msg\n";}
prova(CuradoriaRodadas::validId('MCD-00019'),'IDs históricos MCD mantidos');
prova(CuradoriaRodadas::validId('TEMP-R56-001') && CuradoriaRodadas::validId('TEMP-R60-010'),'IDs dos lotes 56–60 reconhecidos');
prova(!CuradoriaRodadas::validId('MCD-00019;DROP TABLE x') && !CuradoriaRodadas::validId('TEMP-R56-001/'),'IDs malformados recusados');
prova(CuradoriaFilaRapida::problema('rubricas')==='rubricas' && CuradoriaFilaRapida::problema('invalido')==='','Filtros de problema são validados');
$all=['A','B','C'];
[$rest,$resolved]=CuradoriaRevisao::filtrarItensRevisados($all,['0','2']);
prova($rest===['B'] && $resolved===['A','C'],'Pendências são resolvidas individualmente');
[$rest,$resolved]=CuradoriaRevisao::filtrarItensRevisados($all,[]);
prova($rest===$all && $resolved===[],'Nenhum item se resolve sem seleção');
prova(CuradoriaRevisao::hashItens(['pendencias'=>['A'],'duplicatas'=>[],'ano_validado'=>1800])!==CuradoriaRevisao::hashItens(['pendencias'=>['B'],'duplicatas'=>[],'ano_validado'=>1800]),'Alterações concorrentes são detectáveis pelo hash de dados');
try{CuradoriaRevisao::filtrarItensRevisados($all,['3']);throw new RuntimeException('aceitou índice inválido');}
catch(InvalidArgumentException $ex){prova(true,'Índices de itens desatualizados são recusados');}
$notes=[];
foreach(CuradoriaRodadas::CRITERIOS as $code=>$nome)
    $notes[$code]=['codigo'=>$code,'nota'=>2,'justificativa'=>'Justificativa conferida por fonte documental','fontes_ids_json'=>'["LIVRO"]'];
$base=['id_extracao'=>'MCD-01001','titulo_proposto'=>'Dado A','ano_validado'=>1910,'rodada'=>1,
 'estado_revisao_humana'=>'NAO_REVISADO','pendencias'=>[],'duplicatas'=>[],
 'fontes'=>[['fonte_id'=>'LIVRO']], 'criterios'=>$notes];
prova(CuradoriaFilaRapida::tiposProblema($base)===[],'Dossiê regular sem problemas de estrutura');
$bloqueado=$base;$bloqueado['id_extracao']='MCD-01002';$bloqueado['ano_validado']=null;
$bloqueado['pendencias']=['verificar escopo'];$bloqueado['duplicatas']=['MCD-01003'];
$bloqueado['criterios']['impacto_difusao']['fontes_ids_json']='["FANTASMA"]';
$tipos=CuradoriaFilaRapida::tiposProblema($bloqueado);
prova($tipos===['ano','pendencias','duplicatas','rubricas'],'Problemas específicos classificados corretamente');
$a=$base;$a['bloqueios']=[];$a['tipos_problema']=[];
$b=$bloqueado;$b['bloqueios']=CuradoriaRevisao::impedimentos($bloqueado);$b['tipos_problema']=$tipos;
$fila=CuradoriaFilaRapida::organizar([$a,$b],'bloqueados','','','ano');
prova($fila['total']===1 && $fila['selected']['id_extracao']==='MCD-01002','Filtro por problema mostra só candidatos relevantes');
prova($fila['problema_counts']['duplicatas']===1 && $fila['problema_counts']['ano']===1,'Contadores cruzados por problema são reais');
$root=dirname(__DIR__);
$view=file_get_contents($root.'/views/fila_rapida.php');
$actions=file_get_contents($root.'/actions.php');
$service=file_get_contents($root.'/app/CuradoriaRevisao.php');
prova(str_contains($view,'pendencias_resolvidas[]') && str_contains($view,'duplicatas_resolvidas[]'),'Página não oferece remoção cega na correção rápida');
prova(str_contains($actions,"'curadoria_fonte'") && str_contains($actions,"redirect_to('fila_rapida'"),'Cadastro de fonte e retorno à fila presentes');
prova(str_contains($service,'confirmacao_documental') && str_contains($service,'strlen($motivo)<60'),'Resolução exige confirmação e parecer com evidências');
prova(str_contains($view,'csrf_field()') && str_contains($service,'beginTransaction()'),'Revisão mantém proteção CSRF e operação transacional');
echo "SUCESSO: novos testes da fila documental e controle humano.\n";
