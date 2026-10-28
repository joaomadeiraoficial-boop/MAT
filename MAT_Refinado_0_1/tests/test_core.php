<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
function check_test(bool $condition,string $message): void {if(!$condition){fwrite(STDERR,"FALHOU: $message\n");exit(1);}echo "OK: $message\n";}
check_test(count(sample_markers())===10,'Acervo ilustrativo tem 10 registros');
check_test(period_from_year(1450)==='Idade Média','Limite de Gutenberg categorizado corretamente');
check_test(period_from_year(2000)==='Era Contemporânea','Limite da era contemporânea');
check_test(yfmt(-3200)==='3.200 a.C.','Formatacao de data BCE');
check_test(marker_score(sample_markers()[0])===null,'Marco ilustrativo nao entra no indice');
$m=['impacto'=>5,'confiabilidade'=>5,'fator_fundacional'=>1.5,'status'=>'aprovado','incluir_temporis'=>1,'elegibilidade_temporis'=>'sim','escopo_revisado'=>1,'fonte_vinculada'=>1];
check_test(abs((marker_score($m)??0)-150.0)<.00001,'Formula P: I=10, C=100, F=1.5 -> 150');
$m['status']='pendente';check_test(marker_score($m)===null,'Marco pendente nao pode entrar na formula');
$set=[['titulo'=>'A','ano'=>1800,'impacto'=>2,'confiabilidade'=>4,'fator_fundacional'=>1,'status'=>'aprovado','incluir_temporis'=>1,'elegibilidade_temporis'=>'sim','escopo_revisado'=>1,'fonte_vinculada'=>1],['titulo'=>'B','ano'=>1900,'impacto'=>3,'confiabilidade'=>5,'fator_fundacional'=>1.2,'status'=>'aprovado','incluir_temporis'=>1,'elegibilidade_temporis'=>'sim','escopo_revisado'=>1,'fonte_vinculada'=>1]];
$series=temporis_series($set,1900);
check_test(count($series['series'])===2,'Duas observacoes no indice');
check_test(abs((float)$series['t_latest']-100)<0.000001,'Indice no ano base = 100');
check_test($series['k_base']>0,'K_base positivo com amostra elegivel');
check_test(get_markers(['q'=>'penicilina'])['total']>=1 || db()!==null,'Filtro de busca no modo demonstracao');
check_test(hash_equals(csrf_token(),csrf_token()),'Protecao CSRF estavel por sessao');
echo "TESTES DO NUCLEO CONCLUIDOS\n";
