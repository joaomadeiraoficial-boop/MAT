<?php
declare(strict_types=1);

/** Compatibilidade da versao oficial MAT 0.2: carregada somente quando a classe nao esta disponivel. */
/** Implementação independente da proposta do artigo revisado; SEM notas inferidas. */
final class RevisedMethodology {
    public const VERSION = 'artigo-revisado-experimental-0.2';
    public const IMPACT = [
        'impacto_difusao' => 'Difusão',
        'impacto_magnitude' => 'Magnitude',
        'impacto_alcance' => 'Alcance',
        'impacto_persistencia' => 'Persistência',
    ];
    public const CONFIDENCE = [
        'conf_rastreabilidade' => 'Rastreabilidade documental',
        'conf_independencia' => 'Independência / convergência de evidências',
        'conf_datacao' => 'Precisão da datação',
        'conf_consistencia' => 'Consistência da descrição',
    ];
    public const FOUNDATION = [
        'fund_pervasividade' => 'Aplicabilidade entre setores',
        'fund_aprimoramento' => 'Potencial de aprimoramento',
        'fund_complementaridade' => 'Tecnologias complementares',
    ];
    public const ALL = self::IMPACT + self::CONFIDENCE + self::FOUNDATION;
    // Orientações de pontuação: versões iniciais, a calibrar com revisão independente.
    public const GUIDANCE = [
        'impacto_difusao' => '0: não demonstrada; 1: local; 2: regional ou setorial; 3: difusão ampla documentada.',
        'impacto_magnitude' => '0: não demonstrada; 1: incremental; 2: relevante; 3: transformação substancial de práticas.',
        'impacto_alcance' => '0: não identificado; 1: grupo limitado; 2: grupos ou setores distintos; 3: alcance amplo demonstrado.',
        'impacto_persistencia' => '0: não demonstrada; 1: passageira; 2: duradoura; 3: efeito de longa duração.',
        'conf_rastreabilidade' => '0: sem origem; 1: menção indireta; 2: obra e página identificáveis; 3: fontes primárias localizáveis.',
        'conf_independencia' => '0: sem confirmação; 1: fonte única; 2: fontes relacionadas; 3: evidências independentes convergentes.',
        'conf_datacao' => '0: data sem suporte; 1: período amplo; 2: intervalo delimitado; 3: ano ou período bem sustentado.',
        'conf_consistencia' => '0: evidências conflitantes; 1: contradições; 2: coerência com ressalvas; 3: descrição coerente e sustentada.',
        'fund_pervasividade' => '0: não demonstrada; 1: um setor; 2: setores conexos; 3: múltiplos setores distintos.',
        'fund_aprimoramento' => '0: não demonstrado; 1: poucas melhorias; 2: aperfeiçoamentos rastreáveis; 3: família extensiva.',
        'fund_complementaridade' => '0: sem vínculos; 1: vínculo limitado; 2: técnicas relacionadas; 3: muitas técnicas dependentes.',
    ];

    public static function criterion(mixed $raw): ?int {
        if ($raw === '' || $raw === null) return null;
        if (is_int($raw)) $num = $raw;
        elseif (is_string($raw) && preg_match('/^[0-3]$/D', trim($raw))) $num = (int)trim($raw);
        elseif (is_float($raw) && is_finite($raw) && floor($raw) === $raw) $num = (int)$raw;
        else throw new InvalidArgumentException('Os critérios devem ser inteiros de 0 a 3.');
        if ($num < 0 || $num > 3) throw new InvalidArgumentException('Os critérios devem ser inteiros de 0 a 3.');
        return $num;
    }

    /** Ausência de avaliação => null, não zero; exige justificativa individual e fonte. */
    public static function components(array $evaluation, int $sourceCount, bool $requireConfirmation = true): ?array {
        if ($sourceCount < 1) return null;
        if ($requireConfirmation && ($evaluation['estado'] ?? '') !== 'confirmada') return null;
        $sums = ['impacto'=>0, 'confiabilidade'=>0, 'fundacional'=>0];
        foreach (['impacto'=>self::IMPACT,'confiabilidade'=>self::CONFIDENCE,'fundacional'=>self::FOUNDATION] as $kind=>$fields) {
            foreach ($fields as $field=>$label) {
                try { $v=self::criterion($evaluation[$field] ?? null); }
                catch (InvalidArgumentException) { return null; }
                if ($v === null) return null;
                if (trim((string)($evaluation['evidencia_'.$field] ?? '')) === '') return null;
                $sums[$kind] += $v;
            }
        }
        $I = 1.0 + 9.0 * $sums['impacto']/12.0;
        $C = $sums['confiabilidade']/12.0; // 0..1, não percentual
        $F = 1.0 + 0.5 * $sums['fundacional']/9.0;
        return ['I'=>$I,'C'=>$C,'F'=>$F,'impacto_soma'=>$sums['impacto'],
            'confiabilidade_soma'=>$sums['confiabilidade'],'fundacional_soma'=>$sums['fundacional']];
    }

    public static function weight(array $components, float $beta = 2.0): float {
        if (!is_finite($beta) || $beta <= 0 || $beta > 5) throw new InvalidArgumentException('Expoente beta fora do intervalo de experimento.');
        return pow($components['I']/10.0, $beta) * $components['C'] * $components['F'];
    }

    public static function eligible(array $marker): bool {
        return ($marker['status'] ?? '') === 'aprovado'
            && ($marker['incluir_temporis'] ?? 0) == 1
            && ($marker['elegibilidade_temporis'] ?? '') === 'sim'
            && ($marker['escopo_revisado'] ?? 0) == 1
            && ($marker['fonte_vinculada'] ?? 0) >= 1;
    }

    public static function year(mixed $raw): ?int {
        if (is_int($raw)) return $raw;
        if (is_string($raw) && preg_match('/^-?[0-9]+$/D', trim($raw))) {
            $v = filter_var(trim($raw), FILTER_VALIDATE_INT);
            return $v === false ? null : $v;
        }
        return null;
    }

    /** O valor em t0 corresponde a 100; o logaritmo é transformação de escala, não evidência de aceleração. */
    public static function series(array $markers, array $evaluationsById, int $referenceYear, float $beta=2.0): array {
        $points=[];
        $excluded=['sem_aprovacao'=>0, 'sem_rubrica'=>0, 'data_invalida'=>0];
        foreach ($markers as $m) {
            if (!self::eligible($m)) { $excluded['sem_aprovacao']++; continue; }
            $id=(int)($m['id'] ?? 0);
            $components=self::components($evaluationsById[$id] ?? [], (int)$m['fonte_vinculada']);
            if ($components===null) { $excluded['sem_rubrica']++; continue; }
            $year=self::year($m['ano']??null);
            if($year===null) { $excluded['data_invalida']++; continue; }
            $points[]=['id'=>$id,'title'=>(string)($m['titulo']??''),'year'=>$year,
                'score'=>self::weight($components,$beta),'I'=>$components['I'],
                'C'=>$components['C'],'F'=>$components['F']];
        }
        usort($points, static fn(array $a,array $b)=>($a['year']<=>$b['year']) ?: ($a['id']<=>$b['id']) ?: strcmp($a['title'],$b['title']));
        $sum=0.;$kBase=0.;
        foreach($points as &$p) {
            $sum+=$p['score'];$p['k']=$sum;
            if($p['year']<=$referenceYear)$kBase=$sum;
        }
        unset($p);
        foreach($points as &$p) $p['t']=$kBase>0 ? 100.0*log1p($p['k'])/log1p($kBase) : null;
        unset($p);
        $rates=[];
        $byYear=[];
        foreach($points as $p) $byYear[$p['year']]=($byYear[$p['year']]??0.0)+$p['score'];
        ksort($byYear,SORT_NUMERIC);
        $prevYear=null;
        foreach($byYear as $year=>$delta){
            $year=(int)$year;
            // A primeira taxa não tem observação precedente de referência.
            $rates[]=['year'=>$year,'weight'=>$delta,'years_elapsed'=>$prevYear===null?null:$year-$prevYear,
                'mean_rate'=>$prevYear===null?null:$delta/($year-$prevYear)];
            $prevYear=$year;
        }
        return ['rates'=>$rates,'series'=>$points,'k_base'=>$kBase,'k_total'=>$sum,'t_latest'=>$points?(end($points)['t']??null):null,
            'reference_year'=>$referenceYear,'included'=>count($points),'excluded'=>$excluded,'method'=>self::VERSION,'beta'=>$beta];
    }
}
