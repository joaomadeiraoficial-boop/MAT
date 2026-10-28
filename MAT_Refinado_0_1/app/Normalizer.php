<?php
final class Normalizer {
    public static function text(?string $value): string {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    }

    public static function key(?string $value): string {
        $value = self::text($value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($trans !== false) $value = $trans;
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    public static function parseYearRange(?string $raw): array {
        $raw = self::text($raw);
        if ($raw === '') return [null, null, null, 'desconhecida'];

        $bc = preg_match('/\b(a\.?\s*c\.?|bc|bce)\b/i', $raw) === 1;
        preg_match_all('/\d{1,6}/', str_replace(['.', ','], '', $raw), $m);
        $nums = array_map('intval', $m[0] ?? []);
        if (!$bc && count($nums) === 1 && str_starts_with(ltrim($raw), '-')) $nums[0] = -abs($nums[0]);
        if (!$nums) return [null, null, null, 'texto'];
        if ($bc) $nums = array_map(fn($n) => -abs($n), $nums);

        $start = $nums[0];
        $end = $nums[1] ?? $start;
        if ($start > $end) [$start, $end] = [$end, $start];
        $ref = ($start + $end) / 2;
        $precision = $start === $end ? 'ano' : 'intervalo';
        return [$start, $end, $ref, $precision];
    }

    public static function category(?string $value): string {
        $v = self::text($value);
        return $v !== '' ? $v : 'Não classificado';
    }
}
