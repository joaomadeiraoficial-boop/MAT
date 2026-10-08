<?php
require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Normalizer.php';
require_once __DIR__ . '/../src/Temporis.php';
require_once __DIR__ . '/../src/ScopeClassifier.php';
require_once __DIR__ . '/../src/DuplicateDetector.php';
require_once __DIR__ . '/../src/ImportService.php';

$tests = [];
$tests['intervalo moderno'] = Normalizer::parseYearRange('1698-1769') === [1698,1769,1733.5,'intervalo'];
$r = Normalizer::parseYearRange('3500-3200 a.C.');
$tests['intervalo a.C.'] = ($r[0] == -3500 && $r[1] == -3200 && $r[2] == -3350 && $r[3] === 'intervalo');
$tests['score exemplo internet'] = abs(Temporis::score(10,10,1.5)-15) < 1e-9;
$tests['base 100'] = abs(Temporis::index(15,15,.65,100)-100) < 1e-9;
$tests['acronimo WWW'] = DuplicateDetector::similarity('World Wide Web','WWW') > .95;
$c = ScopeClassifier::classify('Internet e sequenciamento do genoma humano','','Modis');
$tests['escopo tecnologia'] = $c['sugestao_elegibilidade'] === 'sim';
$c = ScopeClassifier::classify('Queda do Muro de Berlim','','História');
$tests['escopo político'] = $c['sugestao_elegibilidade'] === 'nao';
$c = ScopeClassifier::classify('Big Bang','','Cosmologia');
$tests['escopo cosmológico'] = $c['sugestao_elegibilidade'] === 'nao';
$c = ScopeClassifier::classify('Steam pump','','Industrial');
$tests['prior categoria Industrial'] = $c['sugestao_elegibilidade'] === 'sim' && $c['scope_confidence'] !== 'baixa';
$c = ScopeClassifier::classify('Integrated circuit','','Communication');
$tests['prior categoria Communication'] = $c['sugestao_elegibilidade'] === 'sim';
$c = ScopeClassifier::classify('Theory of evolution','','Biology');
$tests['teoria da evolução é ciência'] = $c['sugestao_elegibilidade'] === 'sim';

$tmp = tempnam(sys_get_temp_dir(), 'mat_imp_');
file_put_contents($tmp, "InnovationType\tInventors\tInvention\tYear\tOrganization\tCountry\tSource\nIndustrial\tSavery, Thomas\tSteam pump\t1698\t\tUK\tReference X\n");
$ref = new ReflectionClass(ImportService::class);
$importService = $ref->newInstanceWithoutConstructor();
$preview = $importService->previewDelimited($tmp, 'teste.txt', null, 2);
$tests['importador detecta TAB'] = $preview['delimiter'] === "\t";
$tests['importador mapeia Invention'] = ($preview['map']['nome'] ?? null) === 2;
$tests['importador mapeia Year'] = ($preview['map']['ano'] ?? null) === 3;
$tests['importador mapeia Inventors'] = ($preview['map']['inventores'] ?? null) === 1;
@unlink($tmp);

require_once __DIR__ . '/../src/ExternalCollector.php';
$q = ExternalCollector::buildWikidataQuery(['preset'=>'with_inventor','year_start'=>1900,'year_end'=>2000,'limit'=>100,'offset'=>0]);
$tests['collector P575'] = str_contains($q, 'wdt:P575');
$tests['collector inventor P61'] = str_contains($q, 'wdt:P61');
$tests['collector limit'] = str_contains($q, 'LIMIT 100');

foreach ($tests as $name=>$ok) echo $name . ': ' . ($ok?'OK':'FALHOU') . PHP_EOL;
exit(in_array(false,$tests,true)?1:0);
