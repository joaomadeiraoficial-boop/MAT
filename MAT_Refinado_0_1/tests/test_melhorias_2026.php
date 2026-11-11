<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaFilaRapida.php';
require_once dirname(__DIR__).'/app/MarcoRevisao.php';
require_once dirname(__DIR__).'/app/TemporisCuradoria.php';
require_once dirname(__DIR__).'/app/ConcordanciaAvaliadores.php';
function verifyFix(bool $v,string $msg):void{if(!$v)throw new RuntimeException('FALHA: '.$msg);echo "[OK] $msg\n";}
verifyFix(CuradoriaDatacao::anoValido(-698000) && CuradoriaDatacao::anoValido(-3300000),'Pré-história aceita datas anteriores a 20 mil anos');
verifyFix(!CuradoriaDatacao::anoValido(0) && !CuradoriaDatacao::anoValido(-6000000),'Ano zero e limite exterior bloqueados');
verifyFix(CuradoriaDatacao::dataParaCalculo(['ano_original'=>-698000],true)['ano']===-698000,'Ano histórico bruto antigo entra apenas no ensaio');
verifyFix(CuradoriaDatacao::dataParaCalculo(['ano_original'=>-698000],false)===null,'Ano extraído não é promovido a validado');
$notes=[];foreach(CuradoriaRodadas::CRITERIOS as $code=>$label)$notes[$code]=['nota'=>2,'justificativa'=>'Justificativa completa com documentação suficiente.','fontes_ids_json'=>'["VALIDA"]'];
$bad=$notes;$bad['impacto_difusao']['fontes_ids_json']='["FANTASMA"]';
verifyFix(!CuradoriaRodadas::memory($bad,[['fonte_id'=>'VALIDA']])['completa'],'Fonte fantasma bloqueia memória de cálculo');
verifyFix(CuradoriaRodadas::memory($notes,[['fonte_id'=>'VALIDA']])['completa'],'Fontes correspondentes produzem memória válida');
verifyFix(!CuradoriaRodadas::memory($notes,[])['completa'],'Falta de fontes bloqueia cálculo');
$orig=['Fonte do registro incorreta','Outra data incerta'];
try{CuradoriaRevisao::pareceresIndividuais($orig,['0'],[],[['fonte_id'=>'LIVRO']],[0=>'LIVRO']);throw new RuntimeException('Aceitou parecer em branco');}
catch(InvalidArgumentException $e){verifyFix(true,'Pendência não pode ser resolvida sem parecer individual');}
$parecer='A data foi verificada na página 54 da obra original com bibliografia específica.';
verifyFix(count(CuradoriaRevisao::pareceresIndividuais($orig,['0'],[0=>$parecer],[['fonte_id'=>'LIVRO']],[0=>'LIVRO']))===1,'Parecer individual é preservado por índice');
try{CuradoriaRevisao::pareceresIndividuais($orig,['0'],[0=>$parecer],[['fonte_id'=>'LIVRO']],[0=>'FANTASMA']);throw new RuntimeException('Aceitou fonte inexistente para pendência');}
catch(InvalidArgumentException $e){verifyFix(true,'Resolução individual bloqueada quando a fonte não existe');}

$older=['status'=>'aprovado','titulo'=>'Uma invenção','ano'=>1900,'impacto'=>'3.0'];
$new=['status'=>'aprovado','titulo'=>'Uma invenção','ano'=>1900,'impacto'=>'3'];
verifyFix(MarcoRevisao::invalidarAprovacao($older,$new)===[] && $new['status']==='aprovado','Salvar sem alteração científica não invalida aprovação');
$new['ano']=1901;MarcoRevisao::invalidarAprovacao($older,$new);
verifyFix($new['status']==='pendente' && $new['incluir_temporis']===0 && $new['escopo_revisado']===0,'Edição científica reabre revisão e escopo');
$c=['titulo_proposto'=>'A','fontes'=>[],'criterios'=>[],'ano_validado'=>null,'pendencias'=>[],'duplicatas'=>[]];
verifyFix(str_contains(CuradoriaFilaRapida::proximaAcao($c),'fonte'),'Fila indica fonte antes de exigir 11 notas');
$c['quantidade_notas']=0;$c['estado_revisao_humana']='EM_REVISAO';$c['id_extracao']='MCD-00001';$c['bloqueios']=['sem notas'];
verifyFix(CuradoriaFilaRapida::organizar([$c],'incompletos','','')['total']===1,'Fila de 0–10 inclui candidato sem nota');
$p=[['id'=>'MCD-00001','year'=>1800,'score'=>2.,'I'=>6.,'C'=>.8,'F'=>1.],['id'=>'MCD-00002','year'=>2000,'score'=>1.,'I'=>5.,'C'=>.7,'F'=>1.]];
$loo=TemporisCuradoria::sensibilidadeExclusao($p,1900);
verifyFix($loo['calculavel']===true && $loo['indefinidos']===1,'Sensibilidade leave-one-out informa perda de referência histórica');
verifyFix(ConcordanciaAvaliadores::compararNotas([[0,0],[3,3]])['kappa_quadratico']===1.0,'Dois avaliadores perfeitamente concordantes: kappa 1');
verifyFix(ConcordanciaAvaliadores::compararNotas([[0,0],[0,0]])['kappa_quadratico']===null,'Sem variabilidade kappa é indefinido, não 100%');
$raw=file_get_contents(dirname(__DIR__).'/app/CuradoriaRevisao.php');
verifyFix(!str_contains($raw,"if(!empty(\$dados['resolver_pendencias'])){\$pendRemovidas="),'Fechamento global não esvazia pendências');
$approvals=file_get_contents(dirname(__DIR__).'/app/CuradoriaAuditoriaAutomatica.php');
verifyFix(str_contains($approvals,'Aprovação/rejeição em lote desativada'),'Aprovação/rejeição em lote explicitamente bloqueada');
echo "SUCESSO: melhorias de robustez e integridade científica.\n";
