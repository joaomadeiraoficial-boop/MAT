<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaFilaRapida.php';
function checkFast(bool $cond,string $message):void{if(!$cond)throw new RuntimeException('FALHA: '.$message);echo "[OK] $message\n";}
$fixture=[
 ['id_extracao'=>'MCD-01001','titulo_proposto'=>'Transistor', 'estado_revisao_humana'=>'NAO_REVISADO','bloqueios'=>[]],
 ['id_extracao'=>'MCD-01002','titulo_proposto'=>'Telecomunicacoes', 'estado_revisao_humana'=>'EM_REVISAO','bloqueios'=>['Falta fonte independente']],
 ['id_extracao'=>'MCD-01003','titulo_proposto'=>'Computador', 'estado_revisao_humana'=>'APROVADO','bloqueios'=>[]],
 ['id_extracao'=>'MCD-01004','titulo_proposto'=>'Radio', 'estado_revisao_humana'=>'HOMOLOGADO','bloqueios'=>[]],
 ['id_extracao'=>'MCD-01005','titulo_proposto'=>'Patente repetida', 'estado_revisao_humana'=>'REJEITADO','bloqueios'=>['Duplicidade']],
];
$all=CuradoriaFilaRapida::organizar($fixture,'todos','','');
checkFast($all['counts']['todos']===5,'A fila contabiliza os cinco registros, inclusive os rejeitados para auditoria');
checkFast($all['counts']['pendentes']===2,'Pendentes excluem aprovados, homologados e rejeitados');
checkFast($all['counts']['aptos']===1 && $all['counts']['bloqueados']===1,'Aptos e bloqueados são grupos separados');
checkFast($all['counts']['aprovados']===1 && $all['counts']['homologados']===1,'Aprovação não equivale a homologação');
$first=CuradoriaFilaRapida::organizar($fixture,'pendentes','','');
checkFast($first['total']===2 && $first['selected']['id_extracao']==='MCD-01001','Seleciona primeiro candidato pendente');
checkFast($first['next']==='MCD-01002' && $first['prev']===null,'Próximo e anterior funcionam sem alterar o banco');
$next=CuradoriaFilaRapida::organizar($fixture,'pendentes','','MCD-01002');
checkFast($next['prev']==='MCD-01001' && $next['next']===null,'Último candidato preserva navegação anterior');
$blocked=CuradoriaFilaRapida::organizar($fixture,'bloqueados','','');
checkFast($blocked['total']===1 && $blocked['selected']['id_extracao']==='MCD-01002','Bloqueados retornam apenas pendentes de investigação');
$search=CuradoriaFilaRapida::organizar($fixture,'todos','Transistor','');
checkFast($search['total']===1,'Busca por título funciona');
$search=CuradoriaFilaRapida::organizar($fixture,'todos','MCD-01003','');
checkFast($search['total']===1,'Busca por identificador funciona');
$empty=CuradoriaFilaRapida::organizar($fixture,'aprovados','inexistente','');
checkFast($empty['total']===0 && $empty['selected']===null,'Filtro vazio não gera candidato fictício');
checkFast(CuradoriaFilaRapida::filtro('outro')==='pendentes','Filtros não autorizados são normalizados');
$root=dirname(__DIR__);
$index=file_get_contents($root.'/index.php');
$view=file_get_contents($root.'/views/fila_rapida.php');
$actions=file_get_contents($root.'/actions.php');
checkFast(str_contains($index,"'fila_rapida'"),'Rota da fila está cadastrada');
checkFast(str_contains($view,'csrf_field()') && str_contains($view,'confirmacao_humana'),'Formulário mantém CSRF e confirmação humana');
checkFast(str_contains($actions,"redirect_to('fila_rapida'") && str_contains($actions,"CuradoriaRevisao::executar"),'Decisões continuam passando pelo serviço auditável');
checkFast(!str_contains($view,'UPDATE cur_candidatos'),'A visualização não executa atualização de dados');
echo "SUCESSO: 16 verificações da fila rápida.\n";
