<?php
final class DuplicateDetector {
    private static function acronym(string $key): string {
        $parts = array_values(array_filter(explode(' ', $key), fn($p) => $p !== ''));
        if (count($parts) < 2) return '';
        return implode('', array_map(fn($p) => $p[0] ?? '', $parts));
    }

    private static function tokenJaccard(string $a, string $b): float {
        $ta = array_values(array_unique(array_filter(explode(' ', $a))));
        $tb = array_values(array_unique(array_filter(explode(' ', $b))));
        if (!$ta || !$tb) return 0.0;
        $inter = count(array_intersect($ta, $tb));
        $union = count(array_unique(array_merge($ta, $tb)));
        return $union > 0 ? $inter / $union : 0.0;
    }

    public static function similarity(string $a, string $b): float {
        $a = Normalizer::key($a);
        $b = Normalizer::key($b);
        if ($a === '' || $b === '') return 0.0;
        if ($a === $b) return 1.0;

        // Acrônimos são comuns em tecnologia: "World Wide Web" <-> "WWW".
        $aa = self::acronym($a);
        $ab = self::acronym($b);
        $aCompact = str_replace(' ', '', $a);
        $bCompact = str_replace(' ', '', $b);
        if (($aa !== '' && $aa === $bCompact) || ($ab !== '' && $ab === $aCompact)) {
            return 0.98;
        }

        $max = max(strlen($a), strlen($b));
        $lev = $max > 0 ? max(0.0, 1.0 - (levenshtein($a, $b) / $max)) : 1.0;
        $tokens = self::tokenJaccard($a, $b);

        // Combina proximidade textual e sobreposição de palavras.
        return max($lev, $tokens);
    }

    public static function refreshFor(PDO $pdo, int $marcoId, float $threshold = 0.82, int $yearTolerance = 5): int {
        $stmt = $pdo->prepare('SELECT id, nome, ano_ref FROM marcos WHERE id = ?');
        $stmt->execute([$marcoId]);
        $marco = $stmt->fetch();
        if (!$marco) return 0;

        // Remove apenas sugestões pendentes deste marco. Decisões humanas já
        // resolvidas devem ser preservadas para não reaparecerem na reanálise.
        $pdo->prepare('DELETE FROM duplicate_candidates WHERE status = "pendente" AND (marco_a = ? OR marco_b = ?)')->execute([$marcoId, $marcoId]);

        // Registros já marcados como duplicados não podem voltar a gerar candidatos.
        $stmt = $pdo->prepare('SELECT id, nome, ano_ref FROM marcos WHERE id <> ? AND status NOT IN ("rejeitado","duplicado") AND (ano_ref IS NULL OR ? IS NULL OR ABS(ano_ref - ?) <= ?) LIMIT 1000');
        $stmt->execute([$marcoId, $marco['ano_ref'], $marco['ano_ref'], $yearTolerance]);
        $insert = $pdo->prepare('INSERT IGNORE INTO duplicate_candidates (marco_a, marco_b, similarity, status) VALUES (?, ?, ?, "pendente")');
        $count = 0;
        foreach ($stmt as $candidate) {
            $sim = self::similarity($marco['nome'], $candidate['nome']);
            if ($sim >= $threshold) {
                [$a, $b] = $marcoId < $candidate['id'] ? [$marcoId, $candidate['id']] : [$candidate['id'], $marcoId];
                $insert->execute([$a, $b, $sim]);
                $count += $insert->rowCount();
            }
        }
        return $count;
    }

    public static function rebuildAll(PDO $pdo, float $threshold = 0.82, int $yearTolerance = 5): int {
        $pdo->exec('DELETE FROM duplicate_candidates WHERE status = "pendente"');
        $ids = $pdo->query('SELECT id FROM marcos WHERE status <> "rejeitado" AND status <> "duplicado" ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id) {
            self::refreshFor($pdo, (int)$id, $threshold, $yearTolerance);
        }
        return (int)$pdo->query('SELECT COUNT(*) FROM duplicate_candidates WHERE status = "pendente"')->fetchColumn();
    }
}
