<?php
/**
 * Pré-codificação automática das métricas Têmporis.
 *
 * IMPORTANTE: este componente NÃO valida cientificamente um marco e NÃO aprova
 * pontuações. Ele executa uma análise determinística de sinais textuais já
 * presentes no registro e nos trechos de fontes vinculadas. Quando a evidência
 * é insuficiente, retorna null em vez de inventar uma nota.
 */
final class MetricSuggester {
    public const VERSION = 'metric-rules-1.0';

    private const CONF_RANK = [
        'insuficiente' => 0,
        'baixa' => 1,
        'media' => 2,
        'alta' => 3,
        'conflito' => 0,
    ];

    /**
     * Regras bilíngues de pré-codificação. As expressões são deliberadamente
     * conservadoras: ausência de sinal explícito => sem sugestão.
     */
    private static function rules(): array {
        return [
            'impacto_difusao' => [
                3 => [
                    ['widely (used|adopted|deployed|available)', 'uso/adoção ampla'],
                    ['widespread|ubiquitous|mass adoption|global adoption|adopted worldwide|worldwide use', 'adoção global'],
                    ['amplamente (usad|adotad|difundid)|uso generalizado|adocao global|adotad[oa] em todo o mundo|escala global', 'adoção ampla/global'],
                ],
                2 => [
                    ['large[- ]scale|commerciali[sz]ed|industry[- ]wide|multiple countries|national adoption|standardized use', 'adoção em larga escala'],
                    ['larga escala|comercializad|varios paises|adocao nacional|padrao industrial|uso industrial amplo', 'adoção abrangente'],
                ],
                1 => [
                    ['pilot|prototype|experimental|limited adoption|niche|local use|small[- ]scale', 'adoção limitada'],
                    ['piloto|prototipo|experimental|adocao limitada|nicho|uso local|pequena escala', 'adoção limitada'],
                ],
                0 => [
                    ['never adopted|not adopted|abandoned prototype|failed to diffuse', 'não adotado'],
                    ['nunca adotad|nao adotad|prototipo abandonado|nao se difund', 'não difundido'],
                ],
            ],
            'impacto_magnitude' => [
                3 => [
                    ['revolutioni[sz]ed|transformative|transformed .* (industry|society|science|medicine|technology)|paradigm shift|created a new (industry|field|capability)|first practical', 'mudança transformadora'],
                    ['revolucion|transformador|transformou .* (industria|sociedade|ciencia|medicina|tecnologia)|mudanca de paradigma|criou (uma )?nova (industria|area|capacidade)|primeir[oa] .* pratic', 'mudança transformadora'],
                ],
                2 => [
                    ['major advance|major improvement|substantial improvement|significantly improved|dramatically increased|enabled practical use', 'avanço substancial'],
                    ['grande avanc|melhoria substancial|melhorou significativamente|aumentou significativamente|viabilizou o uso pratico|salto tecnologico', 'avanço substancial'],
                ],
                1 => [
                    ['incremental|minor improvement|modest improvement|refinement|improved version', 'melhoria incremental'],
                    ['incremental|pequena melhoria|melhoria modesta|refinamento|versao melhorada', 'melhoria incremental'],
                ],
                0 => [
                    ['no measurable improvement|no practical improvement', 'sem ganho demonstrado'],
                    ['sem melhoria mensuravel|sem ganho pratico', 'sem ganho demonstrado'],
                ],
            ],
            'impacto_alcance' => [
                3 => [
                    ['across multiple sectors|across industries|economy[- ]wide|society[- ]wide|global population|many sectors|multiple domains', 'alcance multissetorial/global'],
                    ['multiplos setores|varios setores|diversas industrias|toda a economia|toda a sociedade|populacao global|multiplos dominios', 'alcance multissetorial/global'],
                ],
                2 => [
                    ['multiple applications|several industries|broad range of applications|large population|widely applicable', 'múltiplas aplicações'],
                    ['multiplas aplicacoes|diversas aplicacoes|varias industrias|ampla gama de aplicacoes|grande populacao|amplamente aplicavel', 'múltiplas aplicações'],
                ],
                1 => [
                    ['single application|single sector|specialized use|specialised use|niche application', 'alcance especializado'],
                    ['aplicacao unica|um unico setor|uso especializado|aplicacao de nicho', 'alcance especializado'],
                ],
                0 => [
                    ['no practical application|no users', 'sem alcance prático'],
                    ['sem aplicacao pratica|sem usuarios', 'sem alcance prático'],
                ],
            ],
            'impacto_persistencia' => [
                3 => [
                    ['still (used|in use|relevant) today|remains (in use|standard|foundational)|for centuries|for decades and remains|long[- ]term foundation', 'persistência longa e atual'],
                    ['ainda (e )?(usad|utilizad|relevante) hoje|permanece (em uso|padrao|fundamental)|por seculos|por decadas e permanece|base de longo prazo', 'persistência longa e atual'],
                ],
                2 => [
                    ['for decades|long[- ]lasting|long[- ]lived|multiple generations|remained in use', 'persistência prolongada'],
                    ['por decadas|duradour|longa duracao|varias geracoes|permaneceu em uso', 'persistência prolongada'],
                ],
                1 => [
                    ['short[- ]lived|quickly superseded|temporary use|briefly used', 'persistência curta'],
                    ['curta duracao|rapidamente substituid|uso temporario|usado por pouco tempo', 'persistência curta'],
                ],
                0 => [
                    ['never entered use|immediately abandoned', 'sem persistência'],
                    ['nunca entrou em uso|imediatamente abandonad', 'sem persistência'],
                ],
            ],
            'fund_pervasividade' => [
                3 => [
                    ['general purpose technolog|general[- ]purpose|across multiple sectors|economy[- ]wide|platform technology|ubiquitous infrastructure', 'tecnologia de uso geral/pervasiva'],
                    ['tecnologia de proposito geral|multiplos setores|toda a economia|tecnologia plataforma|infraestrutura ubiqua|infraestrutura fundamental', 'tecnologia de uso geral/pervasiva'],
                ],
                2 => [
                    ['multiple domains|multiple industries|many applications|cross[- ]sector|broad applicability', 'aplicabilidade ampla'],
                    ['multiplos dominios|varias industrias|muitas aplicacoes|multissetorial|ampla aplicabilidade', 'aplicabilidade ampla'],
                ],
                1 => [
                    ['single domain|specialized technology|specialised technology|niche technology', 'tecnologia especializada'],
                    ['dominio unico|tecnologia especializada|tecnologia de nicho', 'tecnologia especializada'],
                ],
                0 => [
                    ['no downstream use|no broader application', 'sem uso transversal'],
                    ['sem uso posterior|sem aplicacao mais ampla', 'sem uso transversal'],
                ],
            ],
            'fund_aprimoramento' => [
                3 => [
                    ['continuous improvement|ongoing technical improvement|successive generations|rapidly improving|scaling over time|performance improved .* orders of magnitude', 'melhoria técnica contínua'],
                    ['melhoria continua|aprimoramento tecnico continuo|geracoes sucessivas|melhorias sucessivas|escalabilidade ao longo do tempo|desempenho .* ordens de grandeza', 'melhoria técnica contínua'],
                ],
                2 => [
                    ['improved over time|multiple versions|subsequent improvements|iterative improvement|later refinements', 'melhorias posteriores'],
                    ['melhorou ao longo do tempo|multiplas versoes|melhorias posteriores|melhoria iterativa|refinamentos posteriores', 'melhorias posteriores'],
                ],
                1 => [
                    ['little subsequent improvement|limited improvement|mostly fixed design', 'baixo potencial de aprimoramento'],
                    ['pouca melhoria posterior|aprimoramento limitado|projeto praticamente fixo', 'baixo potencial de aprimoramento'],
                ],
                0 => [
                    ['no technical improvement possible|no subsequent development', 'sem trajetória de aprimoramento'],
                    ['sem possibilidade de melhoria tecnica|sem desenvolvimento posterior', 'sem trajetória de aprimoramento'],
                ],
            ],
            'fund_complementaridade' => [
                3 => [
                    ['enabled many (later|subsequent|new) (technologies|innovations|applications)|foundation for .* technologies|underpins .* technologies|spawned .* (industry|industries|ecosystem)|platform for innovation', 'habilitou amplo ecossistema posterior'],
                    ['viabilizou muitas (tecnologias|inovacoes|aplicacoes)|base para .* tecnologias|fundamento para .* tecnologias|deu origem .* (industria|industrias|ecossistema)|plataforma para inovacao', 'habilitou amplo ecossistema posterior'],
                ],
                2 => [
                    ['enabled (later|subsequent|new) (technologies|innovations|applications)|led to .* innovations|basis for .* applications|complementary innovations', 'habilitou inovações posteriores'],
                    ['viabilizou (tecnologias|inovacoes|aplicacoes) posteriores|levou a .* inovacoes|base para .* aplicacoes|inovacoes complementares', 'habilitou inovações posteriores'],
                ],
                1 => [
                    ['enabled a specific application|used as component in|supported one application', 'complementaridade restrita'],
                    ['viabilizou uma aplicacao especifica|usad[oa] como componente|apoiou uma aplicacao', 'complementaridade restrita'],
                ],
                0 => [
                    ['no downstream innovation|did not enable later innovation', 'sem complementaridade posterior'],
                    ['sem inovacao posterior|nao habilitou inovacoes posteriores', 'sem complementaridade posterior'],
                ],
            ],
        ];
    }

    public static function analyze(array $marco, array $sources): array {
        $contexts = self::buildContexts($marco, $sources);
        $criteria = [];
        foreach (self::rules() as $field => $tiers) {
            $criteria[$field] = self::analyzeCriterion($field, $tiers, $contexts);
        }
        $confidence = self::suggestDocumentarySupport($marco, $sources);

        $suggested = array_filter($criteria, fn($r) => $r['score'] !== null);
        $high = count(array_filter($suggested, fn($r) => $r['confidence'] === 'alta'));
        $medium = count(array_filter($suggested, fn($r) => $r['confidence'] === 'media'));
        $low = count(array_filter($suggested, fn($r) => $r['confidence'] === 'baixa'));

        $input = [
            'marco' => array_intersect_key($marco, array_flip([
                'nome','descricao','categoria','subcategoria','origem','organizacao','ambiente','precisao_data',
                'evidencia_impacto_difusao','evidencia_impacto_magnitude','evidencia_impacto_alcance','evidencia_impacto_persistencia',
                'evidencia_fund_pervasividade','evidencia_fund_aprimoramento','evidencia_fund_complementaridade'
            ])),
            'sources' => array_map(fn($s) => array_intersect_key($s, array_flip(['nome','tipo','doi','verificada','papel','pagina','trecho','observacao'])), $sources),
        ];

        return [
            'version' => self::VERSION,
            'criteria' => $criteria,
            'confidence_support' => $confidence,
            'coverage' => [
                'suggested' => count($suggested),
                'total' => 7,
                'percent' => (int)round(100 * count($suggested) / 7),
                'high' => $high,
                'medium' => $medium,
                'low' => $low,
            ],
            'strict_apply_fields' => array_values(array_keys(array_filter($criteria, fn($r) => $r['score'] !== null && $r['confidence'] === 'alta'))),
            'input_hash' => hash('sha256', json_encode($input, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
            'warning' => 'Pré-codificação automática: as sugestões não substituem a validação humana nem a evidência documental de cada critério.',
        ];
    }

    private static function buildContexts(array $marco, array $sources): array {
        $contexts = [];
        $add = function(string $label, ?string $text, int $weight, string $kind) use (&$contexts): void {
            $text = trim((string)$text);
            if ($text === '') return;
            $contexts[] = [
                'label' => $label,
                'text' => $text,
                'norm' => Normalizer::key($text),
                'weight' => $weight,
                'kind' => $kind,
            ];
        };

        foreach (Methodology::IMPACT_FIELDS + Methodology::FOUNDATION_FIELDS as $field => $label) {
            $ev = Methodology::evidenceField($field);
            $add('Evidência já registrada — '.$label, $marco[$ev] ?? '', 6, 'criterion_evidence');
        }
        foreach ($sources as $s) {
            $name = trim((string)($s['nome'] ?? 'Fonte'));
            $role = trim((string)($s['papel'] ?? ''));
            $add('Trecho de fonte — '.$name.($role!==''?' ['.$role.']':''), $s['trecho'] ?? '', 5, 'source_excerpt');
            $add('Observação de fonte — '.$name, $s['observacao'] ?? '', 3, 'source_note');
        }
        $add('Descrição', $marco['descricao'] ?? '', 4, 'description');
        $add('Origem', $marco['origem'] ?? '', 2, 'origin');
        $add('Organização', $marco['organizacao'] ?? '', 2, 'organization');
        $add('Ambiente/contexto', $marco['ambiente'] ?? '', 2, 'environment');
        $add('Subcategoria', $marco['subcategoria'] ?? '', 1, 'subcategory');
        $add('Categoria', $marco['categoria'] ?? '', 1, 'category');
        $add('Nome', $marco['nome'] ?? '', 1, 'name');
        return $contexts;
    }

    private static function analyzeCriterion(string $field, array $tiers, array $contexts): array {
        $matches = [];
        foreach ($contexts as $ctx) {
            foreach ($tiers as $score => $patterns) {
                foreach ($patterns as [$pattern, $label]) {
                    if (@preg_match('/(?:^|\b)(?:'.$pattern.')(?:\b|$)/i', $ctx['norm'])) {
                        $matches[] = [
                            'score' => (int)$score,
                            'label' => $label,
                            'context' => $ctx['label'],
                            'kind' => $ctx['kind'],
                            'weight' => $ctx['weight'],
                        ];
                    }
                }
            }
        }

        if (!$matches) {
            return [
                'score' => null,
                'confidence' => 'insuficiente',
                'rationale' => 'Nenhum sinal textual explícito suficiente foi encontrado. O MAT não inventou uma nota.',
                'matches' => [],
            ];
        }

        $scores = array_values(array_unique(array_column($matches, 'score')));
        sort($scores);
        $strongConflict = false;
        if (count($scores) > 1 && (max($scores) - min($scores) >= 2)) {
            $lowStrong = false; $highStrong = false;
            foreach ($matches as $m) {
                if ($m['weight'] >= 4 && $m['score'] <= min($scores)+0) $lowStrong = true;
                if ($m['weight'] >= 4 && $m['score'] >= max($scores)-0) $highStrong = true;
            }
            $strongConflict = $lowStrong && $highStrong;
        }
        if ($strongConflict) {
            return [
                'score' => null,
                'confidence' => 'conflito',
                'rationale' => 'Foram encontrados sinais fortes contraditórios. Revisão humana obrigatória.',
                'matches' => array_slice($matches, 0, 8),
            ];
        }

        $sum = 0.0; $w = 0.0; $bestContext = 0; $distinctLabels = [];
        foreach ($matches as $m) {
            $sum += $m['score'] * $m['weight'];
            $w += $m['weight'];
            $bestContext = max($bestContext, (int)$m['weight']);
            $distinctLabels[$m['label']] = true;
        }
        $avg = $w > 0 ? $sum / $w : 0;
        $score = (int)max(0, min(3, round($avg)));
        $signalCount = count($distinctLabels);
        $totalWeight = (int)array_sum(array_column($matches, 'weight'));

        if ($bestContext >= 5 || ($bestContext >= 4 && ($signalCount >= 2 || $totalWeight >= 8))) $confidence = 'alta';
        elseif ($bestContext >= 4 || $totalWeight >= 5) $confidence = 'media';
        else $confidence = 'baixa';

        $top = array_slice($matches, 0, 5);
        $labels = array_values(array_unique(array_map(fn($m) => $m['label'].' em '.$m['context'], $top)));
        $rationale = 'Pré-codificação baseada em '.implode('; ', $labels).'.';
        if ($confidence === 'baixa') $rationale .= ' Sinal fraco: usar apenas como pista para revisão.';

        return [
            'score' => $score,
            'confidence' => $confidence,
            'rationale' => $rationale,
            'matches' => $top,
        ];
    }

    /**
     * Escore de suporte documental (0–100). Não é probabilidade de verdade.
     * A pontuação usa metadados verificáveis: tipo da fonte, verificação,
     * DOI, quantidade de fontes independentes, trecho/página e precisão de data.
     */
    private static function suggestDocumentarySupport(array $marco, array $sources): array {
        if (!$sources) {
            return [
                'score' => null,
                'confidence' => 'insuficiente',
                'rationale' => 'Nenhuma fonte vinculada; o suporte documental não pode ser estimado.',
            ];
        }

        $qualities = [];
        $verified = 0; $hasDoi = false; $hasExcerpt = false; $hasPage = false; $specificRole = false;
        foreach ($sources as $s) {
            $type = (string)($s['tipo'] ?? 'outra');
            $q = match($type) {
                'artigo', 'livro', 'base_dados' => 3,
                'site_institucional' => 2,
                'importada' => 1,
                default => 1,
            };
            if (!empty($s['verificada'])) {
                $verified++;
                $q = min(4, $q + 1);
            } else {
                // Sem verificação manual, a classificação do tipo da fonte não basta
                // para produzir suporte documental alto.
                $q = min(2, $q);
            }
            if (trim((string)($s['doi'] ?? '')) !== '') $hasDoi = true;
            if (trim((string)($s['trecho'] ?? '')) !== '') $hasExcerpt = true;
            if (trim((string)($s['pagina'] ?? '')) !== '') $hasPage = true;
            if (in_array((string)($s['papel'] ?? ''), ['data','impacto','fundacional','confiabilidade'], true)) $specificRole = true;
            $qualities[] = min(4, $q);
        }

        rsort($qualities);
        $best = $qualities[0] ?? 1;
        $count = count($sources);
        $score = 20 + 12 * $best;
        if ($count >= 2) $score += 8;
        if ($count >= 3) $score += 8;
        if ($hasDoi) $score += 6;
        if ($hasExcerpt) $score += 5;
        if ($hasPage) $score += 3;
        if ($specificRole) $score += 3;
        $precision = (string)($marco['precisao_data'] ?? 'desconhecida');
        if ($precision === 'ano') $score += 5;
        elseif ($precision === 'intervalo') $score += 3;
        if ($verified >= 2) $score += 5;

        $score = min(95, $score);
        if ($count >= 3 && $verified >= 3 && $best >= 4 && $hasExcerpt) $score = 100;

        $confidence = $score >= 80 ? 'alta' : ($score >= 60 ? 'media' : 'baixa');
        $parts = [
            $count.' fonte(s)',
            $verified.' verificada(s)',
            'qualidade máxima '.$best.'/4',
        ];
        if ($hasDoi) $parts[] = 'DOI presente';
        if ($hasExcerpt) $parts[] = 'trecho registrado';
        if ($hasPage) $parts[] = 'página registrada';
        if ($precision === 'ano') $parts[] = 'ano exato';
        elseif ($precision === 'intervalo') $parts[] = 'data em intervalo';

        return [
            'score' => (int)$score,
            'confidence' => $confidence,
            'rationale' => 'Escore de suporte documental: '.implode(', ', $parts).'. Não representa probabilidade estatística de verdade.',
        ];
    }

    public static function confidenceRank(string $label): int {
        return self::CONF_RANK[$label] ?? 0;
    }
}
