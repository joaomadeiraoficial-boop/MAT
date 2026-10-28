<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
function verify(bool $condition,string $message):void {
 if(!$condition){fwrite(STDERR,"FALHA: $message\n");exit(1);}
 echo "OK: $message\n";
}
$guess=scope_preview(['titulo'=>'Transistor','descricao'=>'Circuito eletrônico inventado para amplificar sinais','categoria'=>'Computação']);
verify(isset($guess['sugestao_elegibilidade'],$guess['natureza'],$guess['classificador_versao']),'Classificador de escopo portado');
verify(!isset($guess['aprovado']),'Classificador nao decide aprovacao');
verify(DuplicateDetector::similarity('World Wide Web','WWW')>=.95,'Deteccao de sigla herdada funciona');
verify(DuplicateDetector::similarity('Transistor','Maquina a vapor')<.83,'Eventos distintos nao se confundem');
$result=metrics_preview(['titulo'=>'Transistor','descricao'=>'Sistemas eletrônicos e computação','categoria'=>'Computação'],[]);
verify(count($result['criteria'])===7,'Sete criterios de pre-codificacao carregados');
verify(($result['confidence_support']['score']??null)===null,'Sem fontes, nao ha suporte documental calculado');
$complete=['impacto'=>5,'confiabilidade'=>5,'fator_fundacional'=>1.5,'status'=>'aprovado','escopo_revisado'=>1,'elegibilidade_temporis'=>'sim','fonte_vinculada'=>1,'incluir_temporis'=>1];
verify(abs(marker_score($complete)-150)<.0001,'Formula original preservada sob todas as exigencias');
foreach(['fonte_vinculada','escopo_revisado','incluir_temporis'] as $key){$copy=$complete;$copy[$key]=0;verify(marker_score($copy)===null,"Gate cientifico bloqueia ausencia de $key");}
$copy=$complete;$copy['elegibilidade_temporis']='nao';verify(marker_score($copy)===null,'Marco fora do escopo fica fora do indice');
verify(Normalizer::key('Máquina a vapor')==='maquina a vapor','Normalizacao lexical disponivel');
echo "TESTES CIENTIFICOS CONCLUIDOS\n";
