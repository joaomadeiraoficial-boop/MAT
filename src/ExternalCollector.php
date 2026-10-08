<?php
final class ExternalCollector {
    private PDO $pdo;
    private array $app;

    public function __construct(PDO $pdo, array $app) {
        $this->pdo = $pdo;
        $this->app = $app;
    }

    public function previewWikidata(array $options): array {
        $query = self::buildWikidataQuery($options);
        $rows = $this->requestWikidata($query);
        return [
            'query' => $query,
            'rows' => $rows,
            'options' => self::sanitizeOptions($options),
        ];
    }

    public function importWikidata(array $options): array {
        $options = self::sanitizeOptions($options);
        $preview = $this->previewWikidata($options);
        $rows = $preview['rows'];
        $query = $preview['query'];

        $this->pdo->beginTransaction();
        try {
            $filename = sprintf('wikidata_%s_%s_%s.csv', $options['preset'], $options['year_start'], $options['year_end']);
            $stmt = $this->pdo->prepare(
                'INSERT INTO import_batches (filename, delimiter_char, total_rows, imported_rows, duplicate_candidates, status, source_type, source_url, query_json)
                 VALUES (?, ";", ?, 0, 0, "processando", "wikidata", ?, ?)'
            );
            $stmt->execute([
                $filename,
                count($rows),
                'https://query.wikidata.org/sparql',
                json_encode(['options'=>$options,'sparql'=>$query], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            ]);
            $batchId = (int)$this->pdo->lastInsertId();

            $src = $this->pdo->prepare(
                'INSERT INTO fontes (nome,tipo,url,observacao,verificada)
                 VALUES ("Wikidata","base_dados","https://www.wikidata.org/","Base estruturada usada para descoberta de candidatos. A confirmação científica deve ser feita em fonte independente.",0)
                 ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)'
            );
            $src->execute();
            $sourceId = (int)$this->pdo->lastInsertId();

            $insertRaw = $this->pdo->prepare(
                'INSERT IGNORE INTO marcos_raw
                 (batch_id,row_number,raw_name,raw_year,raw_category,raw_source,raw_location,raw_description,raw_payload,status,external_source,external_id,external_url,content_hash)
                 VALUES (?,?,?,?,?,?,?,?,?,"importado","wikidata",?,?,?)'
            );
            $insertMarco = $this->pdo->prepare(
                'INSERT INTO marcos
                 (raw_id,nome,nome_normalizado,ano_inicio,ano_fim,ano_ref,precisao_data,categoria,descricao,origem,natureza,elegibilidade_temporis,sugestao_elegibilidade,motivo_escopo,escopo_revisado,classificador_versao,scope_score,scope_confidence,status,incluir_temporis)
                 VALUES (?,?,?,?,?,?,"ano",?,?,?, ?,"revisar",?,?,0,?,?,?,"revisao",0)'
            );
            $linkSource = $this->pdo->prepare('INSERT IGNORE INTO marco_fontes (marco_id,fonte_id,papel,trecho) VALUES (?, ?, "origem", ?)');

            $imported = 0;
            $skipped = 0;
            $rowNumber = 1;
            foreach ($rows as $row) {
                $rowNumber++;
                $qid = (string)($row['qid'] ?? '');
                $label = Normalizer::text($row['label'] ?? '');
                $year = $row['year'] ?? null;
                if ($qid === '' || $label === '' || $year === null) { $skipped++; continue; }

                $externalUrl = 'https://www.wikidata.org/wiki/' . rawurlencode($qid);
                $description = Normalizer::text($row['description'] ?? '');
                $category = self::presetLabel($options['preset']);
                $payload = [
                    'qid'=>$qid,
                    'label'=>$label,
                    'description'=>$description,
                    'date'=>$row['date'] ?? null,
                    'year'=>$year,
                    'preset'=>$options['preset'],
                    'wikidata_url'=>$externalUrl,
                ];
                $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

                $insertRaw->execute([
                    $batchId,$rowNumber,$label,(string)$year,$category,'Wikidata','',$description,
                    json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                    $qid,$externalUrl,$hash
                ]);
                if ($insertRaw->rowCount() === 0) { $skipped++; continue; }
                $rawId = (int)$this->pdo->lastInsertId();

                $scope = ScopeClassifier::classify($label, $description, $category);
                $insertMarco->execute([
                    $rawId,$label,Normalizer::key($label),(float)$year,(float)$year,(float)$year,
                    $category,$description,'Wikidata',$scope['natureza'],$scope['sugestao_elegibilidade'],$scope['motivo_escopo'],$scope['classificador_versao'],$scope['scope_score'],$scope['scope_confidence']
                ]);
                $marcoId = (int)$this->pdo->lastInsertId();
                $linkSource->execute([$marcoId,$sourceId,'Registro candidato obtido via Wikidata Query Service; confirmar em fonte acadêmica/institucional independente.']);
                $imported++;
            }

            $this->pdo->prepare(
                'UPDATE import_batches SET imported_rows=?, duplicate_candidates=0, status="concluido", finished_at=NOW() WHERE id=?'
            )->execute([$imported,$batchId]);

            Audit::log($this->pdo,'import_batch',$batchId,'collect_wikidata',null,[
                'imported'=>$imported,'skipped'=>$skipped,'options'=>$options
            ]);
            $this->pdo->commit();

            return [
                'batch_id'=>$batchId,
                'received'=>count($rows),
                'imported'=>$imported,
                'skipped'=>$skipped,
                'duplicates'=>0,
                'query'=>$query,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public static function buildWikidataQuery(array $options): string {
        $o = self::sanitizeOptions($options);
        $start = $o['year_start'];
        $end = $o['year_end'];
        $limit = $o['limit'];
        $offset = $o['offset'];

        $pattern = '?item wdt:P575 ?date .';
        if ($o['preset'] === 'with_inventor') {
            $pattern .= "\n  ?item wdt:P61 ?inventor .";
        } elseif ($o['preset'] === 'inventions') {
            $pattern .= "\n  ?item wdt:P31/wdt:P279* wd:Q12579633 .";
        }

        return <<<SPARQL
SELECT DISTINCT ?item ?itemLabel ?itemDescription ?date WHERE {
  {$pattern}
  FILTER(YEAR(?date) >= {$start} && YEAR(?date) <= {$end})
  SERVICE wikibase:label { bd:serviceParam wikibase:language "pt,en". }
}
ORDER BY ?date ?item
LIMIT {$limit}
OFFSET {$offset}
SPARQL;
    }

    private function requestWikidata(string $query): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensão cURL do PHP não está ativa. No XAMPP, habilite extension=curl e reinicie o Apache.');
        }

        $endpoint = 'https://query.wikidata.org/sparql?format=json&query=' . rawurlencode($query);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Accept: application/sparql-results+json',
                'User-Agent: MAT-Temporis-Lab/6.2 (academic research; local XAMPP)',
            ],
        ]);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Falha ao consultar Wikidata: ' . ($error ?: 'sem resposta'));
        }
        if ($http === 429) {
            throw new RuntimeException('Wikidata limitou temporariamente as consultas (HTTP 429). Aguarde e tente um lote menor.');
        }
        if ($http < 200 || $http >= 300) {
            throw new RuntimeException('Wikidata respondeu HTTP ' . $http . '. Tente reduzir o intervalo de anos ou o tamanho do lote.');
        }

        $json = json_decode($body, true);
        if (!is_array($json) || !isset($json['results']['bindings'])) {
            throw new RuntimeException('Resposta inválida do Wikidata Query Service.');
        }

        $out = [];
        foreach ($json['results']['bindings'] as $b) {
            $uri = $b['item']['value'] ?? '';
            if (!preg_match('~/entity/(Q\d+)$~', $uri, $m)) continue;
            $date = $b['date']['value'] ?? '';
            $year = self::yearFromWikidataDate($date);
            if ($year === null) continue;
            $out[] = [
                'qid'=>$m[1],
                'label'=>$b['itemLabel']['value'] ?? $m[1],
                'description'=>$b['itemDescription']['value'] ?? '',
                'date'=>$date,
                'year'=>$year,
            ];
        }
        return $out;
    }

    private static function yearFromWikidataDate(string $date): ?int {
        if (preg_match('/^(-?\d{1,6})-\d{2}-\d{2}/', $date, $m)) return (int)$m[1];
        return null;
    }

    public static function sanitizeOptions(array $options): array {
        $preset = (string)($options['preset'] ?? 'with_inventor');
        if (!in_array($preset, ['with_inventor','all_p575','inventions'], true)) $preset = 'with_inventor';
        $start = filter_var($options['year_start'] ?? 1500, FILTER_VALIDATE_INT);
        $end = filter_var($options['year_end'] ?? (int)date('Y'), FILTER_VALIDATE_INT);
        $limit = filter_var($options['limit'] ?? 250, FILTER_VALIDATE_INT);
        $offset = filter_var($options['offset'] ?? 0, FILTER_VALIDATE_INT);
        $start = $start === false ? 1500 : max(1, min(9999, $start));
        $end = $end === false ? (int)date('Y') : max($start, min(9999, $end));
        $limit = $limit === false ? 250 : max(10, min(1000, $limit));
        $offset = $offset === false ? 0 : max(0, min(100000, $offset));
        return ['preset'=>$preset,'year_start'=>$start,'year_end'=>$end,'limit'=>$limit,'offset'=>$offset];
    }

    public static function presetLabel(string $preset): string {
        return match ($preset) {
            'inventions' => 'Invenção — Wikidata',
            'all_p575' => 'Descoberta/invenção — Wikidata',
            default => 'Descoberta/invenção com inventor — Wikidata',
        };
    }
}
