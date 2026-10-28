<?php
declare(strict_types=1);
/** O indice e experimental. Conversao 1..5 para 1..10 e 0..100 ainda nao validada. */
function marker_score(array $m): ?float {
    if(($m['status'] ?? '') !== 'aprovado')return null;
    if(($m['incluir_temporis'] ?? 0) != 1 || ($m['elegibilidade_temporis'] ?? '') !== 'sim' || ($m['escopo_revisado'] ?? 0) != 1 || ($m['fonte_vinculada'] ?? 0) < 1) return null;
    if(!is_numeric($m['impacto']??null) || !is_numeric($m['confiabilidade']??null) || !is_numeric($m['fator_fundacional']??null))return null;
    $i5=(float)$m['impacto'];$c5=(float)$m['confiabilidade'];$f=(float)$m['fator_fundacional'];
    if($i5<1||$i5>5||$c5<1||$c5>5||$f<1||$f>1.5) return null;
    $i=$i5*2; $c=$c5*20;
    return $i*$i*($c/100)*$f;
}
/**
 * Gera a série em ordem cronológica, independente da ordem da consulta ou importação.
 * K_base acumula SOMENTE marcos com ano <= referenceYear.
 * O cálculo de P e o expoente original 0,65 não foram alterados.
 */
function temporis_series(array $rows, int $referenceYear): array {
    $points = [];
    foreach ($rows as $m) {
        if (!is_array($m)) continue;
        $score = marker_score($m);
        if ($score === null) continue;

        // Não converter silenciosamente datas ausentes/inválidas para o ano zero.
        $rawYear = $m['ano'] ?? null;
        if (is_int($rawYear)) {
            $year = $rawYear;
        } elseif (is_string($rawYear) && preg_match('/^-?[0-9]+$/D', trim($rawYear))) {
            $year = filter_var(trim($rawYear), FILTER_VALIDATE_INT);
            if ($year === false) continue;
        } else {
            continue;
        }

        $points[] = [
            'year' => $year,
            'score' => $score,
            'title' => (string)($m['titulo'] ?? ''),
            // Desempates estáveis: ID do banco, título e pontuação.
            '_sort_id' => isset($m['id']) && is_numeric($m['id']) ? (int)$m['id'] : PHP_INT_MAX,
        ];
    }

    usort($points, static function (array $a, array $b): int {
        return ($a['year'] <=> $b['year'])
            ?: ($a['_sort_id'] <=> $b['_sort_id'])
            ?: strcmp($a['title'], $b['title'])
            ?: ($a['score'] <=> $b['score']);
    });

    $sum = 0.0;
    $kBase = 0.0;
    foreach ($points as &$p) {
        $sum += $p['score'];
        $p['k'] = $sum;
        if ($p['year'] <= $referenceYear) $kBase = $sum;
        unset($p['_sort_id']);
    }
    unset($p);

    foreach ($points as &$p) {
        $p['t'] = $kBase > 0.0 ? 100.0 * pow($p['k'] / $kBase, 0.65) : null;
    }
    unset($p);

    return [
        'series' => $points,
        'k_base' => $kBase,
        'reference_year' => $referenceYear,
        'k_total' => $sum,
        't_latest' => $points ? (end($points)['t'] ?? null) : null,
    ];
}
