<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaFilaRapida.php';
require_once dirname(__DIR__).'/app/TemporisCuradoria.php';
function provaIntervalo(bool $ok,string $msg):void{if(!$ok)throw new RuntimeException('FALHOU: '.$msg);echo "[OK] $msg\n";}
$input=['intervalo_inicio'=>'750','intervalo_fim'=>'850','intervalo_tipo'=>'processo',
 'intervalo_fonte_id'=>'MUSEU','intervalo_justificativa'=>'As evidências arqueológicas indicam desenvolvimento ao longo de aproximadamente um século.'];
$dat=CuradoriaDatacao::validarEntrada($input,[['fonte_id'=>'MUSEU']]);
provaIntervalo($dat['inicio']===750 && $dat['fim']===850,'Intervalo completo e validado antes de gravar');
provaIntervalo(CuradoriaDatacao::anoRepresentativo($dat)===800,'Ponto médio calculado, não gravado como ano validado');
provaIntervalo(CuradoriaDatacao::anoRepresentativo($dat,'inicio')===750 && CuradoriaDatacao::anoRepresentativo($dat,'fim')===850,'Extremos preservados para sensibilidade');
$notes=[];foreach(CuradoriaRodadas::CRITERIOS as $code=>$nome)$notes[$code]=['codigo'=>$code,'nota'=>2,'justificativa'=>'Evidência documental verificável com fonte previamente cadastrada.','fontes_ids_json'=>'["MUSEU"]'];
$src=[['fonte_id'=>'MUSEU']];
$r=['id_extracao'=>'MCD-10001','titulo_proposto'=>'Processo Hohokam (exemplo de teste)', 'ano_original'=>500,'ano_validado'=>null,
 'analise_historica_json'=>json_encode(['origem_reinterpretacao'=>'pesquisa','datacao_intervalar'=>$dat],JSON_UNESCAPED_UNICODE),
 'estado_revisao_humana'=>'NAO_REVISADO','homologado'=>0,'pendencias_json'=>'[]','duplicatas_json'=>'[]',
 'criterios'=>$notes,'fontes'=>$src,'pendencias'=>[],'duplicatas'=>[]];
provaIntervalo(CuradoriaDatacao::temData($r),'Intervalo justificado satisfaz o requisito temporal');
provaIntervalo(CuradoriaRevisao::impedimentos($r)===[],'Intervalo com fonte permite revisão regular, sem aprovação automática');
provaIntervalo(CuradoriaFilaRapida::tiposProblema($r)===[],'Fila não marca intervalo válido como ano pendente');
provaIntervalo(CuradoriaDatacao::dataParaCalculo($r)['ano']===800 && CuradoriaDatacao::dataParaCalculo($r)['origem']==='processo','Têmporis identifica origem e ponto médio apenas no cálculo');
provaIntervalo(str_contains(CuradoriaDatacao::texto($r),'750 d.C. a 850 d.C.'),'Período explícito apresentado ao pesquisador');
$r2=$r;$r2['analise_historica_json']='{}';$r2['ano_validado']=1800;$r2['id_extracao']='MCD-10002';$r2['titulo_proposto']='Marco pontual';
provaIntervalo(CuradoriaDatacao::temData($r2) && CuradoriaDatacao::dataParaCalculo($r2)['origem']==='validada','Anos pontuais antigos continuam sem alteração');
$r3=$r2;$r3['id_extracao']='MCD-10003';$r3['ano_validado']=null;$r3['ano_original']=2020;
provaIntervalo(CuradoriaDatacao::dataParaCalculo($r3)===null,'Ano extraído NÃO passa por validação científica');
provaIntervalo(CuradoriaDatacao::dataParaCalculo($r3,true)['origem']==='extraida_nao_validada','Ano extraído só entra no ensaio separado');
$rBad=$r;$rBad['fontes']=[['fonte_id'=>'OUTRA']];
provaIntervalo(count(CuradoriaRevisao::impedimentos($rBad))>0,'Fonte desaparecida bloqueia aprovação de intervalo');
$rBad=$r;$rBad['pendencias']=['Conferir inscrição'];
provaIntervalo(count(CuradoriaRevisao::impedimentos($rBad))>0,'Pendência histórica continua bloqueando aprovação');
try{CuradoriaDatacao::validarEntrada($input,['fonte_id'=>'OUTRA']);throw new RuntimeException('Aceitou fonte inexistente');}catch(InvalidArgumentException $e){provaIntervalo(true,'Fonte desconhecida rejeitada antes de salvar');}
try{CuradoriaDatacao::validarEntrada($input+[],[]);throw new RuntimeException('Aceitou fontes vazias');}catch(InvalidArgumentException $e){provaIntervalo(true,'Sem fonte, intervalo não recebe validação');}
try{CuradoriaDatacao::validarEntrada(array_merge($input,['intervalo_inicio'=>'950']),$src);throw new RuntimeException('Aceitou limites invertidos');}catch(InvalidArgumentException $e){provaIntervalo(true,'Limites invertidos rejeitados');}
$all=[$r,$r2,$r3];$cs=[];$ss=[];foreach($all as $row){$cs[$row['id_extracao']]=$notes;$ss[$row['id_extracao']]=$src;}
$result=TemporisCuradoria::calcular($all,$cs,$ss,1920);
provaIntervalo($result['previa']['included']===2 && $result['ensaio']['included']===3,'Ensaio usa um ano extraído, prévia revisada não usa');
provaIntervalo($result['homologada']['included']===0,'Nenhuma homologação criada automaticamente');
provaIntervalo($result['ensaio']['datas_intervalares']===1 && $result['ensaio']['datas_provisorias']===1,'Anos incertos explicitamente contados');
provaIntervalo($result['sensibilidade_beta']['2']['t_ultimo']!==null,'Modelo β=2 produz teste matemático com base anterior a 1920');
provaIntervalo($result['sensibilidade']['cenarios']['inicio']['valores'][800] !== $result['sensibilidade']['cenarios']['fim']['valores'][800],'Sensibilidade distingue início e fim do processo');
provaIntervalo(abs($result['sensibilidade_beta']['2']['k_total']-$result['ensaio']['k_total'])<1e-9,'β=2 conserva pesos da fórmula original');
$withoutBase=TemporisCuradoria::calcular([$r3],[$r3['id_extracao']=>$notes],[$r3['id_extracao']=>$src],1920);
provaIntervalo($withoutBase['ensaio']['k_base']===0. && $withoutBase['ensaio']['t_latest']===null,'Sem base, T permanece matematicamente indefinido');
$view=file_get_contents(dirname(__DIR__).'/views/temporis.php');
provaIntervalo(str_contains($view,'ensaio') && str_contains($view,'sensibilidade_beta') && str_contains($view,'sensibilidade'),'Painel contém testes β e cenários de datação');
$forms=file_get_contents(dirname(__DIR__).'/views/datacao_campos.php');
provaIntervalo(str_contains($forms,'modo_datacao') && str_contains($forms,'intervalo_fonte_id'),'Formulário opcional inclui modo e fonte de datação');
echo "SUCESSO: testes de datas intervalares e ensaio experimental.\n";
