<?php
final class ImportService {
    private PDO $pdo;
    private array $app;

    public function __construct(PDO $pdo, array $app) {
        $this->pdo = $pdo;
        $this->app = $app;
    }

    /**
     * Lê CSV/TXT/TSV sem gravar nada e tenta mapear as colunas automaticamente.
     */
    public function previewDelimited(string $filePath, string $originalName, ?string $forcedDelimiter = null, int $previewRows = 12): array {
        $delimiter = $forcedDelimiter ?: $this->detectDelimiter($filePath);
        $fh = fopen($filePath, 'rb');
        if (!$fh) throw new RuntimeException('Não foi possível abrir o arquivo.');

        $headerOriginal = fgetcsv($fh, 0, $delimiter, '"', '\\');
        if (!$headerOriginal || count($headerOriginal) < 2) {
            fclose($fh);
            throw new RuntimeException('Não consegui identificar as colunas do arquivo.');
        }
        $headerOriginal = array_map(fn($h) => $this->cleanCell((string)$h), $headerOriginal);
        $header = array_map(fn($h) => Normalizer::key((string)$h), $headerOriginal);
        $map = $this->mapColumns($header);

        $rows = [];
        $total = 0;
        while (($row = fgetcsv($fh, 0, $delimiter, '"', '\\')) !== false) {
            if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
            $total++;
            if (count($rows) < $previewRows) {
                $rows[] = array_map(fn($v) => $this->cleanCell((string)$v), $row);
            }
        }
        fclose($fh);

        return [
            'filename' => $originalName,
            'delimiter' => $delimiter,
            'delimiter_label' => $this->delimiterLabel($delimiter),
            'header_original' => $headerOriginal,
            'header_normalized' => $header,
            'map' => $map,
            'rows' => $rows,
            'total_rows' => $total,
            'recognized_fields' => array_keys($map),
        ];
    }

    public function importCsv(string $filePath, string $originalName, string $delimiter = ';'): array {
        return $this->importDelimited($filePath, $originalName, $delimiter, null, 'csv');
    }

    /**
     * Importa um arquivo delimitado usando mapeamento automático ou mapeamento confirmado pelo usuário.
     * $fieldMap é [campo_do_mat => índice_da_coluna].
     */
    public function importDelimited(string $filePath, string $originalName, string $delimiter, ?array $fieldMap = null, string $sourceType = 'arquivo'): array {
        $fh = fopen($filePath, 'rb');
        if (!$fh) throw new RuntimeException('Não foi possível abrir o arquivo.');

        $headerOriginal = fgetcsv($fh, 0, $delimiter, '"', '\\');
        if (!$headerOriginal || count($headerOriginal) < 2) {
            fclose($fh);
            throw new RuntimeException('Cabeçalho inválido.');
        }
        $headerOriginal = array_map(fn($h) => $this->cleanCell((string)$h), $headerOriginal);
        $header = array_map(fn($h) => Normalizer::key((string)$h), $headerOriginal);
        $map = $fieldMap ?: $this->mapColumns($header);
        $map = $this->validateFieldMap($map, count($header));
        if (!isset($map['nome'])) {
            fclose($fh);
            throw new RuntimeException('Não encontrei uma coluna para o nome do marco. Escolha manualmente a coluna correspondente antes de importar.');
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('INSERT INTO import_batches (filename, delimiter_char, total_rows, imported_rows, status, source_type) VALUES (?, ?, 0, 0, "processando", ?)');
            $stmt->execute([$originalName, $delimiter, $sourceType]);
            $batchId = (int)$this->pdo->lastInsertId();

            $rowNo = 1; $total = 0; $imported = 0; $duplicates = 0;
            while (($row = fgetcsv($fh, 0, $delimiter, '"', '\\')) !== false) {
                $rowNo++;
                if (count(array_filter($row, fn($v) => trim((string)$v) !== '')) === 0) continue;
                $total++;
                $row = array_map(fn($v) => $this->cleanCell((string)$v), $row);

                $payload = [];
                foreach ($headerOriginal as $i => $h) $payload[$h !== '' ? $h : "col_$i"] = $row[$i] ?? null;
                $get = fn($key) => isset($map[$key]) ? ($row[(int)$map[$key]] ?? null) : null;

                $name = Normalizer::text($get('nome'));
                if ($name === '') continue;

                [$yearStart,$yearEnd,$yearRef,$precision] = Normalizer::parseYearRange($get('ano'));
                $category = Normalizer::category($get('categoria'));
                $sourceName = Normalizer::text($get('fonte'));
                $description = Normalizer::text($get('descricao'));
                $inventores = Normalizer::text($get('inventores'));
                $organizacao = Normalizer::text($get('organizacao'));
                $ambiente = Normalizer::text($get('ambiente'));
                $pais = Normalizer::text($get('pais'));
                $cidade = Normalizer::text($get('cidade'));
                $local = Normalizer::text($get('local'));
                $civilizacao = Normalizer::text($get('civilizacao'));
                if ($cidade === '' && $local !== '') $cidade = $local;

                $scopeText = trim(implode(' ', array_filter([$description, $inventores, $organizacao, $ambiente])));
                $scope = ScopeClassifier::classify($name, $scopeText, $category);

                $rawStmt = $this->pdo->prepare('INSERT INTO marcos_raw (batch_id,row_number,raw_name,raw_year,raw_category,raw_source,raw_location,raw_description,raw_payload,status) VALUES (?,?,?,?,?,?,?,?,?,"importado")');
                $rawStmt->execute([
                    $batchId,$rowNo,$get('nome'),$get('ano'),$get('categoria'),$sourceName,
                    trim($cidade . ($pais !== '' ? ', ' . $pais : '')), $description,
                    json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
                ]);
                $rawId = (int)$this->pdo->lastInsertId();

                $origin = 'Arquivo: ' . mb_strimwidth($originalName, 0, 235, '…', 'UTF-8');
                $stmt = $this->pdo->prepare('INSERT INTO marcos (raw_id,nome,nome_normalizado,ano_inicio,ano_fim,ano_ref,precisao_data,categoria,pais,cidade,civilizacao,descricao,origem,inventores,organizacao,ambiente,natureza,elegibilidade_temporis,sugestao_elegibilidade,motivo_escopo,escopo_revisado,classificador_versao,scope_score,scope_confidence,status,incluir_temporis) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, ?,0,?,?,?,"revisao",0)');
                $stmt->execute([
                    $rawId,$name,Normalizer::key($name),$yearStart,$yearEnd,$yearRef,$precision,$category,
                    $pais,$cidade,$civilizacao,$description,$origin,$inventores,$organizacao,$ambiente,
                    $scope['natureza'],'revisar',$scope['sugestao_elegibilidade'],$scope['motivo_escopo'],$scope['classificador_versao'],$scope['scope_score'],$scope['scope_confidence']
                ]);
                $marcoId = (int)$this->pdo->lastInsertId();

                if ($sourceName !== '') {
                    foreach ($this->splitSources($sourceName) as $sourcePart) {
                        $storedName = mb_strimwidth($sourcePart, 0, 490, '…', 'UTF-8');
                        $src = $this->pdo->prepare('INSERT INTO fontes (nome,tipo,observacao) VALUES (?,"importada",?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
                        $src->execute([$storedName, 'Importada automaticamente de ' . $originalName]);
                        $sourceId = (int)$this->pdo->lastInsertId();
                        $this->pdo->prepare('INSERT IGNORE INTO marco_fontes (marco_id,fonte_id,papel) VALUES (?, ?, "origem")')->execute([$marcoId,$sourceId]);
                    }
                }

                $duplicates += DuplicateDetector::refreshFor($this->pdo,$marcoId,$this->app['duplicate_threshold'],$this->app['duplicate_year_tolerance']);
                $imported++;
            }
            fclose($fh);
            $this->pdo->prepare('UPDATE import_batches SET total_rows=?, imported_rows=?, duplicate_candidates=?, status="concluido", finished_at=NOW() WHERE id=?')->execute([$total,$imported,$duplicates,$batchId]);
            Audit::log($this->pdo,'import_batch',$batchId,'import_delimited',null,['filename'=>$originalName,'rows'=>$imported,'delimiter'=>$this->delimiterLabel($delimiter),'mapping'=>$map]);
            $this->pdo->commit();
            return ['batch_id'=>$batchId,'total'=>$total,'imported'=>$imported,'duplicates'=>$duplicates];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if (is_resource($fh)) fclose($fh);
            throw $e;
        }
    }

    private function detectDelimiter(string $filePath): string {
        $fh = fopen($filePath, 'rb');
        if (!$fh) throw new RuntimeException('Não foi possível abrir o arquivo para detectar o separador.');
        $sample = [];
        for ($i=0; $i<5 && !feof($fh); $i++) {
            $line = fgets($fh);
            if ($line !== false && trim($line) !== '') $sample[] = $line;
        }
        fclose($fh);
        if (!$sample) return ';';

        $candidates = ["\t", ';', ',', '|'];
        $best = ';'; $bestScore = 1;
        foreach ($candidates as $d) {
            $counts = [];
            foreach ($sample as $line) $counts[] = count(str_getcsv($line, $d, '"', '\\'));
            sort($counts);
            $score = $counts[(int)floor(count($counts)/2)] ?? 1;
            if ($score > $bestScore) { $bestScore = $score; $best = $d; }
        }
        return $best;
    }

    private function delimiterLabel(string $delimiter): string {
        return match($delimiter) {
            "\t" => 'TAB (tabulação)',
            ';' => 'ponto e vírgula (;)',
            ',' => 'vírgula (,)',
            '|' => 'barra vertical (|)',
            default => $delimiter,
        };
    }

    private function cleanCell(string $value): string {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1');
            if (is_string($converted)) $value = $converted;
        }
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;
        return trim($value);
    }

    private function splitSources(string $source): array {
        $parts = preg_split('/\s*;\s*/u', $source) ?: [$source];
        $parts = array_values(array_unique(array_filter(array_map(fn($v) => trim($v), $parts), fn($v) => $v !== '')));
        return $parts ?: [$source];
    }

    private function validateFieldMap(array $map, int $columnCount): array {
        $allowed = ['nome','ano','categoria','fonte','descricao','pais','cidade','local','civilizacao','inventores','organizacao','ambiente'];
        $out = [];
        foreach ($map as $field => $idx) {
            if (!in_array($field,$allowed,true)) continue;
            if ($idx === '' || $idx === null) continue;
            $idx = (int)$idx;
            if ($idx >= 0 && $idx < $columnCount) $out[$field] = $idx;
        }
        return $out;
    }

    public function automaticMap(array $headerOriginal): array {
        $header = array_map(fn($h) => Normalizer::key((string)$h), $headerOriginal);
        return $this->mapColumns($header);
    }

    private function mapColumns(array $header): array {
        $synonyms = [
            'nome' => ['nome','marco','evento','tecnologia','titulo','title','name','event','milestone','invention','innovation','discovery','invention name','innovation name'],
            'ano' => ['ano','data','periodo','year','date','intervalo','invention year','discovery year'],
            'categoria' => ['categoria','area','dominio','category','domain','innovationtype','innovation type','invention type','type'],
            'fonte' => ['fonte','origem','source','sources','referencia','referencias','bibliografia','reference','references'],
            'descricao' => ['descricao','resumo','description','abstract','observacao','observacoes','notas','notes'],
            'pais' => ['pais','country','nation'],
            'cidade' => ['cidade','city','localidade','place name province state','place','province state'],
            'local' => ['local','lugar','location'],
            'civilizacao' => ['civilizacao','sociedade','civilization','society'],
            'inventores' => ['inventores','inventor','inventors','discoverer','discoverers','scientist','scientists'],
            'organizacao' => ['organizacao','organization','institution','instituicao','laboratory','lab','company'],
            'ambiente' => ['ambiente','environment','setting','context'],
        ];
        $map = [];
        foreach ($synonyms as $field=>$options) {
            foreach ($header as $i=>$col) {
                if (in_array($col,$options,true)) { $map[$field]=$i; break; }
            }
        }
        return $map;
    }
}
