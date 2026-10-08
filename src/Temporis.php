<?php
final class Temporis {
    public static function score(float $impact, float $confidence, float $foundational): float {
        return (($impact ** 2) * ($confidence / 100.0)) * $foundational;
    }

    public static function index(float $k, float $kBase, float $alpha = 0.65, float $baseValue = 100.0): ?float {
        if ($kBase <= 0) return null;
        return $baseValue * (($k / $kBase) ** $alpha);
    }
}
