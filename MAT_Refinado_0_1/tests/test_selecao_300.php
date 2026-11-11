<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/CuradoriaMarcos.php';
require_once dirname(__DIR__).'/app/SelecaoLote300.php';
function matTestSelecao(bool $ok,string $label):void{if(!$ok)throw new RuntimeException($label);echo "[OK] $label\n";}
function matLinha(string $id,string $titulo,int $ano,string $texto):array{
 return ['id'=>abs(crc32($id))%1000000+1,'titulo'=>'A revisar: '.$titulo,'ano'=>$ano,
 'fonte'=>'McClellan III & Dorn (2006), p. 283',
 'bruto_json'=>json_encode(['id_extracao'=>$id,'pagina_livro'=>'283','trecho_evidencia'=>$texto],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)];
}
$t='In 1712 an obscure English ironmonger, Thomas Newcomen, invented the first practical steam engine.';
$orig=matLinha('MCD-00191',$t,1712,$t);
$a=CuradoriaMarcos::resolve($orig,null);
matTestSelecao($a['apto_pontuacao_assistida']===true,'Evento explito de 1712 pode ser enviado para pedir notas');
matTestSelecao($a['identificacao']==='curadoria_inicial_documental' && $a['aprovado']===false,
 'Curadoria inicial NAO transforma registro em marco aprovado');
$dup=matLinha('MCD-00194',$t,1712,'The problem was initially solved by Thomas Newcomen’s invention of the atmospheric steam engine in 1712.');
$b=CuradoriaMarcos::resolve($dup,null);
matTestSelecao(!$b['apto_pontuacao_assistida'] && in_array('POSSIVEL_DUPLICATA',$b['alertas'],true),
 'Duplicata fica em revisao e nao e pontuada duas vezes');
$links=CuradoriaMarcos::duplicateAlerts([$b,$a]);
matTestSelecao(empty($links[$a['id']]),'Duplicata mais antiga nao invalida principal');
$data=matLinha('MCD-00034','As a result of that increase, the Song began issuing paper money in 1024',1201,
 'As a result of that increase, the Song began issuing paper money in 1024, and paper money became the dominant currency.');
$c=CuradoriaMarcos::resolve($data,null);
matTestSelecao(!$c['apto_pontuacao_assistida'] && in_array('ANO_EXTRAIDO_DIVERGENTE',$c['alertas'],true),
 'Data suspeita nao e corrigida silenciosamente');
$unknown=matLinha('MCD-XXXX','texto contextual sem evento tecnologico',1701,str_repeat('contexto ',20));
matTestSelecao(!CuradoriaMarcos::resolve($unknown,null)['apto_pontuacao_assistida'],
 'Trecho nao identificado continua pendente em vez de receber pontuacao artificial');
matTestSelecao(SelecaoLote300::totalCurados()>=40,'Curadoria inicial oferece dezenas de eventos documentais identificaveis');
echo "[OK] Selecionados para TRIAGEM; pontuacoes ainda dependem de Gemini e auditoria.\n";
