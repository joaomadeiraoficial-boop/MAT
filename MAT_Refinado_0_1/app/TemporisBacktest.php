<?php
declare(strict_types=1);
/**
 * MAT - backtesting retrospectivo da Têmporis reformulada.
 * Somente dados já aprovados e avaliações confirmadas, usando o motor oficial.
 * O modelo prevê incrementos de K, não inventa eventos futuros nem notas.
 * Não escreve em arquivos ou no banco.
 */
if (!class_exists('RevisedMethodology', false)) {
    if (is_file(__DIR__ . '/RevisedMethodology.php')) require_once __DIR__ . '/RevisedMethodology.php';
    else require_once __DIR__ . '/compat/RevisedMethodology_020.php';
}
final class TemporisBacktest {
    public const VERSION = 'backtesting-temporis-0.8';
    public const MODEL_NAMES = [
        'media50' => 'Média de pesos/ano nos 50 anos anteriores',
        'media100' => 'Média de pesos/ano nos 100 anos anteriores',
        'tendencia100' => 'Tendência das taxas nas duas metades de 50 anos',
    ];

    /** Recorta o conjunto científico ANTES de qualquer avaliação. */
    public static function approvedPoints(array $markers, array $assessments, int $maxYear, float $beta=2.0): array {
        $subset = array_values(array_filter($markers, static function ($m) use ($maxYear): bool {
            $year = RevisedMethodology::year($m['ano'] ?? null);
            return $year !== null && $year <= $maxYear;
        }));
        $res = RevisedMethodology::series($subset, $assessments, $maxYear, $beta);
        return [
            'points'=>array_map(static fn(array $x): array => ['year'=>(int)$x['year'], 'weight'=>(float)$x['score'], 'id'=>$x['id']], $res['series']),
            'included'=>$res['included'], 'excluded'=>$res['excluded'], 'total_before_target'=>count($subset),
            'method'=>$res['method'], 'beta'=>$beta
        ];
    }

    public static function kAt(array $points, int $year): float {
        $k=0.0;
        foreach ($points as $p) {
            if ((int)$p['year'] <= $year) $k += (float)$p['weight'];
        }
        return $k;
    }
    public static function scale(float $k, float $kBase): ?float {
        return $kBase>0 && $k>=0 ? 100.0 * log1p($k) / log1p($kBase) : null;
    }
    private static function sumWindow(array $points, int $from, int $to): float {
        $sum=0.0;
        foreach ($points as $p) if ($p['year'] >= $from && $p['year'] <= $to) $sum += (float)$p['weight'];
        return $sum;
    }
    /** Modelos são definidos previamente. Não ajustam parâmetros a partir do resultado de 2020. */
    public static function experiment(array $points, int $origin=1920, int $target=2020): array {
        if ($origin < -10000 || $target > 3000 || $target <= $origin || $target-$origin>600) {
            throw new InvalidArgumentException('Anos invalidos. O alvo deve ser posterior ao corte.');
        }
        $clean=[];
        foreach ($points as $p) {
            $y=RevisedMethodology::year($p['year']??null);
            $w=$p['weight']??null;
            if ($y===null || !is_numeric($w) || !is_finite((float)$w) || (float)$w<0) {
                throw new InvalidArgumentException('Ponto com ano ou peso invalido.');
            }
            if ($y <= $target) $clean[]=['year'=>$y,'weight'=>(float)$w];
        }
        usort($clean, static fn($a,$b)=>$a['year']<=>$b['year']);
        $train = array_values(array_filter($clean, static fn($p)=>$p['year']<=$origin));
        $test = array_values(array_filter($clean, static fn($p)=>$p['year']>$origin));
        $k0=self::kAt($train,$origin);
        $kObs=self::kAt($clean,$target);
        $actual=count($test)>0 ? self::scale($kObs,$k0) : null;
        $n=count($train);
        $base=['origin'=>$origin,'target'=>$target,'horizon'=>$target-$origin,
            'n_train'=>$n,'n_after_origin'=>count($test), 'k_origin'=>$k0,
            'k_observed'=>$actual===null?null:$kObs,'t_observed'=>$actual,
            'status'=>'insuficiente','models'=>[], 'notes'=>[]];
        if ($n<10 || $k0<=0) {
            $base['notes'][]='São necessários pelo menos 10 marcos aprovados até o ano de corte e K(corte)>0.';
            return $base;
        }
        if (count($test)===0) {
            $base['notes'][]='Não há marcos avaliados após o corte até o alvo. Sem referência observada, o erro é indefinido.';
        }
        $earliest=$train[0]['year'];
        $h=$target-$origin;
        foreach (self::MODEL_NAMES as $key=>$label) {
            $need=($key==='media50')?50:100;
            if ($earliest > $origin-$need+1) {
                $base['models'][$key]=['label'=>$label,'status'=>'insuficiente',
                    'reason'=>'Amostra sem cobertura temporal mínima de '.$need.' anos até o corte.'];
                continue;
            }
            if ($key==='media50') {
                $r=self::sumWindow($train,$origin-49,$origin)/50.0;
                $added=$h*$r;
            } elseif ($key==='media100') {
                $r=self::sumWindow($train,$origin-99,$origin)/100.0;
                $added=$h*$r;
            } else {
                $rOld=self::sumWindow($train,$origin-99,$origin-50)/50.0;
                $rRecent=self::sumWindow($train,$origin-49,$origin)/50.0;
                // Previsão da taxa anual: extrapolação de tendência entre duas janelas.
                // Projeta 1..h anos sem permitir pesos negativos.
                $slope=($rRecent-$rOld)/50.0;
                $added=0.0;
                for ($step=1;$step<=$h;$step++) $added+=max(0.0,$rRecent+$slope*$step);
            }
            $kPred=$k0+$added;
            $tp=self::scale($kPred,$k0);
            $error=$actual===null?null:($tp-$actual);
            $base['models'][$key]=['label'=>$label,'status'=>'calculado',
                'k_predicted'=>$kPred,'t_predicted'=>$tp, 'error_points'=>$error,
                'absolute_error'=>$error===null?null:abs($error),
                'relative_error_percent'=>$error===null || abs($actual)<1e-12 ? null : 100.0*abs($error)/abs($actual)];
        }
        $base['status']=$actual!==null?'comparavel':'sem_referencia_observada';
        $base['notes'][]='Notas podem ter sido atribuídas retrospectivamente com conhecimento posterior ao corte; isto deve ser auditado.';
        $base['notes'][]='O índice observado é calculado pelo método e pela amostra aprovada, não uma verdade externa independente.';
        return $base;
    }
    /** Múltiplos cortes, horizonte FIXO. Testes históricos terminam até o corte final. */
    public static function historicalBacktests(array $points, int $finalOrigin=1920, int $horizon=100, int $step=20): array {
        if ($horizon<1 || $step<1 || $horizon>600) throw new InvalidArgumentException('Parâmetros invalidos');
        if ($points===[])return [];
        $years=array_map(static fn($p)=>(int)$p['year'], $points);
        $earliest=min($years);
        $start = $earliest + 99;
        $result=[];
        // Primeira validação termina em ou antes de finalOrigin. Nunca usa alvos posteriores.
        for ($cut=$start; $cut+$horizon<=$finalOrigin; $cut+=$step) {
            $r=self::experiment($points, $cut, $cut+$horizon);
            if ($r['status']==='comparavel' && $r['n_train']>=10) $result[]=$r;
        }
        return $result;
    }
    public static function summarize(array $folds): array {
        $ret=[];
        foreach (self::MODEL_NAMES as $key=>$label) {
            $errors=[];$pes=[];
            foreach($folds as $f) {
                $p=$f['models'][$key]??null;
                if(($p['status']??'')!=='calculado' || $p['absolute_error']===null)continue;
                $errors[]=(float)$p['absolute_error'];
                if($p['relative_error_percent']!==null)$pes[]=(float)$p['relative_error_percent'];
            }
            $n=count($errors);
            $ret[$key]=['label'=>$label,'n_folds'=>$n,
                'mae'=>$n?array_sum($errors)/$n:null,
                'rmse'=>$n?sqrt(array_sum(array_map(static fn($e)=>$e*$e,$errors))/$n):null,
                'mape_percent'=>$pes?array_sum($pes)/count($pes):null];
        }
        return $ret;
    }
    /** Somente exercício computacional fictício, jamais dado real do acervo. */
    public static function simulatedPoints(): array {
        $scores=['impact'=>[2,2,2,2], 'confidence'=>[2,2,2,2], 'foundation'=>[2,2,2]];
        $I=1+9*array_sum($scores['impact'])/12;
        $C=array_sum($scores['confidence'])/12;
        $F=1+0.5*array_sum($scores['foundation'])/9;
        $w=RevisedMethodology::weight(['I'=>$I,'C'=>$C,'F'=>$F],2.0);
        $result=[];
        for ($y=1700;$y<=2020;$y+=5) {
            $count=$y<=1760?1:($y<=1860?2:($y<=1920?3:5));
            for ($j=0;$j<$count;$j++)$result[]=['year'=>$y,'weight'=>$w];
        }
        return $result;
    }
}
