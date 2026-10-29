<?php
declare(strict_types=1);
/** Testa o carregamento dos modulos sem tocar no MySQL ou chamar Ollama. */
$root=dirname(__DIR__);
require_once $root.'/app/PontuacaoCompat.php';
PontuacaoCompat::initialize();
function testOk(bool $ok,string $msg):void{
    if(!$ok){fwrite(STDERR,'[FALHOU] '.$msg."\n");exit(1);}
    echo '[OK] '.$msg."\n";
}
testOk(is_file($root.'/fila_pontuacao.php'),'Pagina da pontuacao presente');
testOk(is_file($root.'/cli/pontuar_acervo_ia.php'),'Comando de pontuacao presente');
testOk(function_exists('revised_all_evaluations')===is_file($root.'/app/revised_repository.php'), 'Repositorio revisado carregado quando instalado');
testOk(PontuacaoCompat::methodReady()===is_file($root.'/app/RevisedMethodology.php'), 'Metodologia revisada reconhecida quando instalada');
if(PontuacaoCompat::methodReady())testOk(count(RevisedMethodology::ALL)===11,'Os 11 criterios oficiais disponiveis');
$page=(string)file_get_contents($root.'/fila_pontuacao.php');
testOk(str_contains($page,'PontuacaoCompat::allEvaluations()') && !str_contains($page,'$evaluations=revised_all_evaluations();'),'Pagina nao chama funcao ausente diretamente');
$cli=(string)file_get_contents($root.'/cli/pontuar_acervo_ia.php');
testOk(str_contains($cli,'PontuacaoCompat::evaluation($id)'), 'CLI usa o mesmo adaptador');
echo "MAT PONTUACAO: TESTE DE COMPATIBILIDADE APROVADO. NAO TESTA MYSQL OU OLLAMA.\n";
