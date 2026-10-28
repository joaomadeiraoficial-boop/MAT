<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

function assert_mat(bool $condition, string $label): void {
    if (!$condition) {
        fwrite(STDERR, "FALHOU: {$label}\n");
        exit(1);
    }
    echo "OK: {$label}\n";
}
function approx_mat(?float $actual, float $expected, float $eps=0.000001): bool {
    return $actual !== null && abs($actual - $expected) <= $eps;
}
function expect_invalid_criterion(mixed $value): void {
    try {
        Methodology::normalizeCriterion($value);
    } catch (InvalidArgumentException) {
        return;
    }
    throw new RuntimeException('Nota inválida aceita: ' . var_export($value, true));
}

$valid = [
    'status'=>'aprovado', 'incluir_temporis'=>1,
    'elegibilidade_temporis'=>'sim', 'escopo_revisado'=>1,
    'fonte_vinculada'=>1, 'impacto'=>3, 'confiabilidade'=>5,
    'fator_fundacional'=>1.1,
];
$events = [
    array_merge($valid, ['id'=>30,'titulo'=>'Posterior','ano'=>2100]),
    array_merge($valid, ['id'=>20,'titulo'=>'Medieval','ano'=>1450]),
    array_merge($valid, ['id'=>10,'titulo'=>'Antigo','ano'=>-3000]),
];
$a=temporis_series($events, 1900);
$b=temporis_series(array_reverse($events), 1900);
$c=temporis_series([$events[1], $events[2], $events[0]], 1900);
assert_mat(approx_mat($a['k_base'], 79.2),'K_base inclui apenas os dois eventos até o ano-base');
assert_mat($a === $b && $a === $c,'Série e K_base invariantes à permutação dos registros');
assert_mat(array_column($a['series'],'year') === [-3000,1450,2100],'Eventos BCE e futuros ordenados corretamente');
assert_mat(approx_mat($a['series'][1]['t'], 100.0),'T(t) no último evento do ano-base = 100');
assert_mat(approx_mat($a['k_total'], 118.8),'K_total inclui também eventos posteriores ao ano-base');
assert_mat(temporis_series($events, -3500)['k_base'] === 0.0,'Ano-base anterior a todos os marcos fica sem normalização');
assert_mat(temporis_series([], 2025)['series'] === [],'Conjunto vazio preserva série vazia');

$invalidYear = array_merge($valid,['titulo'=>'Sem data']);
$badYear = array_merge($valid,['titulo'=>'Data inválida','ano'=>'ano 1900']);
$goodYear = array_merge($valid,['titulo'=>'Data como texto','ano'=>' -100 ']);
$result=temporis_series([$invalidYear,$badYear,$goodYear],2025);
assert_mat(count($result['series'])===1 && $result['series'][0]['year']===-100,'Ano inválido não vira ano zero');

$equalYear = [
    array_merge($valid,['titulo'=>'B','ano'=>2025]),
    array_merge($valid,['titulo'=>'A','ano'=>2025]),
];
assert_mat(temporis_series($equalYear,2025)===temporis_series(array_reverse($equalYear),2025),'Desempate por título estável no mesmo ano');

assert_mat(Methodology::normalizeCriterion(null)===null,'Campo sem critério permanece ausente');
foreach ([0,1,2,3,'0','1','2','3',2.0,' 2 '] as $value) {
    assert_mat(Methodology::normalizeCriterion($value)===(int)$value,'Nota válida: '.var_export($value,true));
}
foreach ([-1,4,'2.5',2.9,'3.1','texto','',true,false,[],INF,NAN] as $value) {
    if ($value === '') continue; // Vazio representa nota ainda não preenchida.
    expect_invalid_criterion($value);
}
assert_mat(true,'Notas não inteiras, booleanos e valores fora da escala são rejeitados');

assert_mat(approx_mat(marker_score(array_merge($valid,['impacto'=>5,'fator_fundacional'=>1.5])),150.0),'P_i original permanece inalterado');

// A metodologia B permanece disponível para experimento, sem substituir o método A.
$rubric = array_fill_keys(array_keys(Methodology::IMPACT_FIELDS + Methodology::FOUNDATION_FIELDS),3);
assert_mat(approx_mat(Methodology::impact($rubric),10.0),'Rubrica B continua produzindo I=10');
assert_mat(approx_mat(Methodology::foundational($rubric),1.5),'Rubrica B continua produzindo F=1.5');
assert_mat(approx_mat(Methodology::score(10.0,80.0,1.5,2.0),120.0),'Fórmula parametrizada B preservada');

echo "REGRESSÕES MAT REFINADO 0.1.1: CONCLUÍDAS\n";
