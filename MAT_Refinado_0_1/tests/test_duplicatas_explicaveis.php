<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaDuplicatas.php';
function testExp(bool $ok,string $name): void{if(!$ok)throw new RuntimeException('FALHA: '.$name);echo "[OK] $name\n";}
$a=['id_extracao'=>'MCD-00002','titulo_proposto'=>'Debate sobre Stonehenge como observatório ou computador neolítico','categoria'=>'CONTEXTO_HISTORICO','ano_original'=>1960,'ano_validado'=>null,'duplicatas_json'=>'["MCD-00473"]'];
$b=['id_extracao'=>'MCD-00473','titulo_proposto'=>'Roda de oleiro na Mesopotâmia (c. 3500 a.C.)','categoria'=>'manufatura / cerâmica','ano_original'=>-3500,'ano_validado'=>null,'duplicatas_json'=>'[]'];
$d=CuradoriaDuplicatas::diagnostico($a,$b,true,false);
testExp($d['nivel']==='alerta','Caso Stonehenge vs roda de oleiro recebe alerta (não duplicata confirmada)');
testExp($d['sem_justificativa']===true,'A ausência de justificativa na importação aparece em destaque');
testExp(str_contains($d['origem'],'registro A'),'A origem identifica qual registro declarou o vínculo');
testExp(str_contains(implode(' ',$d['pistas']),'provisória'),'Datas extraídas não são tratadas como validação cronológica');
testExp(CuradoriaDuplicatas::semReferencia('["MCD-00473","MCD-00559"]','MCD-00473')==='["MCD-00559"]','Retira uma referência e mantém as demais');
testExp(CuradoriaDuplicatas::semReferencia('[{"id":"MCD-00473","motivo":"teste"},"MCD-00559"]','MCD-00473')==='["MCD-00559"]','Retira objetos de vínculo mantendo outros registros');
$a['duplicatas_json']='[{"id":"MCD-00473","motivo":"mesmo evento alegado pelo importador"}]';
$d2=CuradoriaDuplicatas::diagnostico($a,$b,true,false);
testExp(!$d2['sem_justificativa'] && count($d2['motivos_importados'])===1,'Distingue motivo importado de evidência verificada');
$a['titulo_proposto']='Motor a vapor';$b['titulo_proposto']='Motor a vapor';
$a['categoria']=$b['categoria']='Energia';$a['ano_validado']=1769;$b['ano_validado']=1770;
$d3=CuradoriaDuplicatas::diagnostico($a,$b,true,true);
testExp($d3['nivel']==='indicio','Mesmo título e datas próximas geram indício, nunca certeza');
testExp(CuradoriaRodadas::validId('TEMP-R56-001') && CuradoriaDuplicatas::idsDeJson('["TEMP-R56-001"]')===['TEMP-R56-001'],'IDs dos lotes 56-60 são reconhecidos');
$d4=CuradoriaDuplicatas::diagnostico($a,$b,false,false);
testExp(str_contains($d4['origem'],'encerrado'),'Vínculos encerrados possuem histórico, sem atribuir origem incorreta');
$root=dirname(__DIR__);
$view=file_get_contents($root.'/views/duplicatas.php');$service=file_get_contents($root.'/app/CuradoriaDuplicatas.php');
testExp(str_contains($view,'JUSTIFICATIVA DA IMPORTAÇÃO') && str_contains($view,'O QUE A COMPARAÇÃO MOSTRA'),'Tela explica fonte e indícios');
testExp(str_contains($view,'confirmacao_comparacao') && str_contains($service,'FOR UPDATE') && str_contains($service,"'PARECER_DUPLICATA'"),'Decisão exige confirmação, bloqueio concorrente e trilha de auditoria');
testExp(str_contains($view,'data-dup-risk') && str_contains(file_get_contents($root.'/assets/duplicatas-comparacao.css'),'mat-dup-diagnosis-alerta'),'Aviso recebe destaque visual');
echo "SUCESSO: explicações de duplicidade verificadas.\n";
