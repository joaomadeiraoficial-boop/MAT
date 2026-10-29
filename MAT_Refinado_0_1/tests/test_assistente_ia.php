<?php
require dirname(__DIR__).'/app/AssistenteCientifico.php';
function ok(bool $v, string $name): void {if(!$v){echo '[FALHOU] '.$name."\n";exit(1);} echo '[OK] '.$name."\n";}
$base=['titulo'=>'A revisar: discovery','ano'=>1991,'descricao'=>'Data encontrada automaticamente','categoria'=>'Transporte'];
$s=AssistenteCientifico::localSuggestions($base,'import');
ok(count($s)>=4,'Fila sugere revisao humana e duplicatas');
ok(str_contains(implode(' ',$s),'título de extração'),'Identifica rotulo automatico');
$mock=['natureza'=>'contexto','acao_sugerida'=>'verificar','riscos'=>['data biografica'],'criterios'=>[]];
foreach(AssistenteCientifico::FIELDS as $f)$mock['criterios'][$f]=['nota'=>'3','citacao'=>'trecho inventado que nao existe','justificativa'=>'estimativa'];
$checked=AssistenteCientifico::verifyOutput($mock,'EVIDENCIA VERDADEIRA: um pequeno trecho historico sem notas de impacto.');
ok(count($checked['criterios'])===11,'Onze criterios presentes');
ok(count(array_filter($checked['criterios'],fn($c)=>$c['nota']!==null))===0,'Rejeita notas sem citacao literal');
$mock['criterios']['impacto_difusao']=['nota'=>'2','citacao'=>'um pequeno trecho historico sem notas','justificativa'=>'testando apenas correspondencia'];
$checked=AssistenteCientifico::verifyOutput($mock,'EVIDENCIA VERDADEIRA: um pequeno trecho historico sem notas de impacto.');
ok($checked['criterios']['impacto_difusao']['nota']===2,'Mantem sugestao com citacao presente (nao validada)');
ok($checked['criterios']['impacto_alcance']['nota']===null,'Nao cria nota para os demais criterios');
$labels=AssistenteCientifico::rubricLabels();
$guidance=AssistenteCientifico::rubricGuidance();
ok(count($labels)===11 && count($guidance)===11,'Rubricas acessiveis com ou sem bootstrap revisado');
ok(isset($labels['impacto_difusao']) && isset($guidance['fund_complementaridade']),'Rotulos e escalas dos 11 criterios carregados');
$payload=AssistenteCientifico::ollamaPayload('sistema','pergunta');
ok($payload['model']==='qwen3.5:4b','Mantem o Qwen 3.5 4B');
ok(($payload['think']??null)===false,'Desativa Thinking na requisicao');
ok(($payload['options']['num_ctx']??0)===4096,'Reduz contexto para 4096 tokens');
ok(($payload['options']['num_predict']??0)===1500,'Limita tamanho da resposta');
ok(count($payload['format']['properties']['criterios']['properties']??[])===11,'Mantem o JSON dos 11 criterios');
echo "ASSISTENTE MAT: TESTES LOCAIS APROVADOS (SEM TESTAR O MODELO OLLAMA).\n";
