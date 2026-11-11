<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/CuradoriaRodadas.php';
require_once dirname(__DIR__).'/app/TemporisFinal.php';
$expect=static function(bool $condition,string $message):void{
    if(!$condition)throw new RuntimeException($message);
    echo "[OK] $message\n";
};
$expect(CuradoriaRodadas::databaseName()==='mat_temporis_curadoria','Curadoria Refinada permanece no banco original');
$expect((string)cfg('db_name')==='mat_observatorio','Banco do acervo Refinado preservado');
$expect((string)cfg('temporis_final_db_name')==='mat_final_curadoria','Snapshot MAT Final configurado separadamente');
$expect(CuradoriaRodadas::databaseName()!==cfg('temporis_final_db_name'),'Nao permite reaproveitar o nome original do banco');
$expect(is_file(dirname(__DIR__).'/instalacao/mat_final_curadoria_snapshot.sql'),'Snapshot incluido');
$index=file_get_contents(dirname(__DIR__).'/index.php');
$expect(str_contains($index,"'temporis_final'"),'Rota do novo laboratorio habilitada');
$header=file_get_contents(dirname(__DIR__).'/views/header.php');
$expect(str_contains($header,"mat_url('temporis')") && str_contains($header,"mat_url('backtesting')"),'Menu para o Têmporis e backtesting integrado instalado');
$view=file_get_contents(dirname(__DIR__).'/views/temporis_final.php');
$expect(str_contains($view,'TemporisFinal::obterDados(') && str_contains(file_get_contents(dirname(__DIR__).'/app/TemporisFinal.php'),'CuradoriaRodadas::connect()'),'Metodo Final prioriza a curadoria ativa em somente leitura');
echo "INTEGRACAO E ORIGEM DE DADOS: testes de configuracao aprovados (sem MySQL real).\n";
