<?php
final class Methodology {
    public const VERSION = 'temporis-1.1';

    public const IMPACT_FIELDS = [
        'impacto_difusao' => 'Difusão',
        'impacto_magnitude' => 'Magnitude',
        'impacto_alcance' => 'Alcance',
        'impacto_persistencia' => 'Persistência',
    ];

    public const FOUNDATION_FIELDS = [
        'fund_pervasividade' => 'Pervasividade',
        'fund_aprimoramento' => 'Potencial de aprimoramento',
        'fund_complementaridade' => 'Complementaridade inovadora',
    ];

    public static function normalizeCriterion(mixed $value): ?int {
        if ($value === null || $value === '') return null;
        // A rubrica tem quatro valores inteiros; cast implícito truncava 2.9 para 2.
        if (is_int($value)) {
            $v = $value;
        } elseif (is_string($value) && preg_match('/^[0-3]$/D', trim($value))) {
            $v = (int)trim($value);
        } elseif (is_float($value) && is_finite($value) && floor($value) === $value) {
            $v = (int)$value;
        } else {
            throw new InvalidArgumentException('Critérios metodológicos devem ser números inteiros de 0 a 3.');
        }
        if ($v < 0 || $v > 3) {
            throw new InvalidArgumentException('Critérios metodológicos devem ser números inteiros de 0 a 3.');
        }
        return $v;
    }

    public static function impact(array $row, ?array $weights = null): ?float {
        $weights ??= array_fill_keys(array_keys(self::IMPACT_FIELDS), 1.0);
        return self::weightedScale($row, self::IMPACT_FIELDS, $weights, 1.0, 10.0);
    }

    public static function foundational(array $row, ?array $weights = null): ?float {
        $weights ??= array_fill_keys(array_keys(self::FOUNDATION_FIELDS), 1.0);
        return self::weightedScale($row, self::FOUNDATION_FIELDS, $weights, 1.0, 1.5);
    }

    private static function weightedScale(array $row, array $fields, array $weights, float $min, float $max): ?float {
        $sum = 0.0; $maxScore = 0.0;
        foreach ($fields as $field => $_label) {
            if (!array_key_exists($field, $row) || $row[$field] === '' || $row[$field] === null) return null;
            $value = self::normalizeCriterion($row[$field]);
            $weight = max(0.0, (float)($weights[$field] ?? 1.0));
            $sum += $value * $weight;
            $maxScore += 3.0 * $weight;
        }
        if ($maxScore <= 0) return null;
        return round($min + ($max - $min) * ($sum / $maxScore), 4);
    }

    public static function score(float $impact, float $confidencePct, float $foundational, float $beta = 2.0): float {
        $confidencePct = max(0.0, min(100.0, $confidencePct));
        return round((($impact ** $beta) * ($confidencePct / 100.0)) * $foundational, 6);
    }

    public static function confidencePct(array $row): ?float {
        if (($row['confiabilidade_pct'] ?? '') !== '' && $row['confiabilidade_pct'] !== null) {
            return max(0.0, min(100.0, (float)$row['confiabilidade_pct']));
        }
        if (($row['confiabilidade'] ?? '') !== '' && $row['confiabilidade'] !== null) {
            $legacy = (float)$row['confiabilidade'];
            return $legacy <= 10.0 ? $legacy * 10.0 : min(100.0, $legacy);
        }
        return null;
    }

    public static function evidenceField(string $criterionField): string {
        return 'evidencia_' . $criterionField;
    }

    public static function completeness(array $row, int $sourceCount = 0): array {
        $missing = [];
        foreach (self::IMPACT_FIELDS + self::FOUNDATION_FIELDS as $field => $label) {
            if (($row[$field] ?? '') === '' || $row[$field] === null) $missing[] = $label . ' sem nota';
            $ev = trim((string)($row[self::evidenceField($field)] ?? ''));
            if ($ev === '') $missing[] = $label . ' sem evidência';
        }
        if (self::confidencePct($row) === null) $missing[] = 'Confiabilidade sem percentual';
        if (trim((string)($row['justificativa_confiabilidade'] ?? '')) === '') $missing[] = 'Confiabilidade sem justificativa';
        if ($sourceCount < 1) $missing[] = 'Nenhuma fonte vinculada';
        return [
            'complete' => count($missing) === 0,
            'missing' => $missing,
            'percent' => self::completenessPercent($row, $sourceCount),
        ];
    }

    public static function completenessPercent(array $row, int $sourceCount = 0): int {
        $checks = [];
        foreach (self::IMPACT_FIELDS + self::FOUNDATION_FIELDS as $field => $_label) {
            $checks[] = (($row[$field] ?? '') !== '' && $row[$field] !== null);
            $checks[] = trim((string)($row[self::evidenceField($field)] ?? '')) !== '';
        }
        $checks[] = self::confidencePct($row) !== null;
        $checks[] = trim((string)($row['justificativa_confiabilidade'] ?? '')) !== '';
        $checks[] = $sourceCount > 0;
        $done = count(array_filter($checks));
        return (int)round(100 * $done / max(1, count($checks)));
    }

    public static function rubric(): array {
        return [
            0 => 'Sem evidência / efeito desprezível',
            1 => 'Limitado / localizado',
            2 => 'Relevante / abrangente',
            3 => 'Amplo / transformador e bem documentado',
        ];
    }
}
