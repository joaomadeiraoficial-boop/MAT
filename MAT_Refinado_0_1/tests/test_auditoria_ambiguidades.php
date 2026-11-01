<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/AuditoriaAmbiguidade.php';
function assertMat(bool $pass,string $message):void{if(!$pass)throw new RuntimeException($message);}
$bad=['titulo'=>'A revisar: em algum momento nasceu um inventor', 'ano'=>'2040',
 'fonte'=>'','descricao'=>'','bruto_json'=>json_encode(['trecho_evidencia'=>'born'])];
$r=AuditoriaAmbiguidade::evaluate($bad);
assertMat($r['situacao']==='BLOQUEAR_PONTUACAO','Nao bloqueou candidato mal definido');
assertMat($r['pode_pontuar_automaticamente']===false,'Nao deve permitir pontuacao automatica');
$codes=array_column($r['riscos'],'codigo');
foreach(['TITULO_NAO_EVENTO','DATA_INVALIDA','EVIDENCIA_CURTA','FONTE_AUSENTE','CONTEXTO_BIOGRAFICO'] as $code)
  assertMat(in_array($code,$codes,true),'Faltou alerta '.$code);
$good=['titulo'=>'Primeira demonstracao publica de tecnologia', 'ano'=>'1900','fonte'=>'Livro catalogado',
 'descricao'=>'Demonstracao com contexto de invento',
 'bruto_json'=>json_encode(['pagina_livro'=>5,'trecho_evidencia'=>str_repeat('Evidencia documental com contexto historico. ',3)])];
$r2=AuditoriaAmbiguidade::evaluate($good);
assertMat($r2['situacao']==='REVISAR_EVIDENCIAS','Precisa de revisao humana mesmo sem riscos altos');
assertMat($r2['fontes_independentes_verificadas']===0,'Nao inventar verificacoes externas');
echo "OK: auditoria de ambiguidade (2 cenarios)\n";
