<?php
final class Normalizer {
    public static function text(?string $value): string {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    }

    /** Mapa UTF-8 explícito: iconv('ASCII//TRANSLIT') corrompe acentos no Windows sem locale. */
    private const ACENTOS = [
        'á'=>'a','à'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ā'=>'a','ă'=>'a','ą'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ĕ'=>'e','ę'=>'e','ě'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ĩ'=>'i','ī'=>'i','ĭ'=>'i','į'=>'i',
        'ó'=>'o','ò'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ō'=>'o','ŏ'=>'o','ő'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ũ'=>'u','ū'=>'u','ŭ'=>'u','ů'=>'u','ű'=>'u','ų'=>'u',
        'ç'=>'c','ć'=>'c','ĉ'=>'c','č'=>'c','ñ'=>'n','ń'=>'n','ņ'=>'n','ň'=>'n',
        'ý'=>'y','ÿ'=>'y','š'=>'s','ś'=>'s','ŝ'=>'s','ş'=>'s','ž'=>'z','ź'=>'z','ż'=>'z',
        'ř'=>'r','ŕ'=>'r','ŗ'=>'r','ĺ'=>'l','ļ'=>'l','ľ'=>'l','ł'=>'l','ť'=>'t','ţ'=>'t','ť'=>'t',
        'ď'=>'d','đ'=>'d','ğ'=>'g','ġ'=>'g','ģ'=>'g','æ'=>'ae','œ'=>'oe','ß'=>'ss',
    ];

    public static function key(?string $value): string {
        $value = self::text($value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = strtr($value, self::ACENTOS);
        // Remove o que sobrar fora do ASCII seguro (símbolos e outros alfabetos viram espaço).
        $value = preg_replace('/[^\x20-\x7E]+/', ' ', $value) ?? $value;
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
