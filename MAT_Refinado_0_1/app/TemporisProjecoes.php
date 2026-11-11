<?php
declare(strict_types=1);

/**
 * Projeções retrospectivas/centenárias: trabalha EXCLUSIVAMENTE com os anos e
 * pesos já derivados das onze rubricas em TemporisCuradoria. Não grava nada.
 *
 * O ano-base é configurável; por padrão dos exemplos, T(1900)=100:
 *   log:      100 log(1+K(t)) / log(1+K(ano-base))
 *   potência: 100 (K(t)/K(ano-base)) ^ 0.65
 * No motor isolado, omitir o ano-base o define igual ao corte.
 *
 * Informação pós-corte é usada APENAS como comparação observada, jamais treino.
 */
final class TemporisProjecoes
{
    public const FORMULAS = [
        'log' => 'Reformulada · logaritmo',
        'potencia' => 'Original adaptada · potência 0,65',
    ];
    public const MODELOS = [
        'exponencial' => 'Exponencial · K(t+h)=K(t)·e^(g·h) · PRINCIPAL',
        'media50' => 'Média dos 50 anos anteriores',
        'media100' => 'Média dos 100 anos anteriores',
        'tendencia100' => 'Tendência entre duas janelas de 50 anos',
    ];
    /** Modelo exibido por padrão (projeção principal do Têmporis). */
    public const MODELO_PRINCIPAL = 'exponencial';
    /** Janela (anos) usada para estimar a taxa exponencial g a partir do histórico. */
    public const JANELA_G = 50;

    public static function indice(float $k, float $kBase, string $formula): ?float
    {
        if (!isset(self::FORMULAS[$formula])) throw new InvalidArgumentException('Fórmula não reconhecida.');
        if (!is_finite($k) || !is_finite($kBase) || $k < 0 || $kBase <= 0) return null;
        $t = $formula === 'log'
            ? 100.0 * log1p($k) / log1p($kBase)
            : 100.0 * pow($k / $kBase, 0.65);
        return is_finite($t) ? $t : null;
    }

    /** Filtra pesos inválidos e mantém só anos documentados até o ano corrente. */
    public static function preparar(array $points, int $asOf): array
    {
        $out = [];
        foreach ($points as $p) {
            $y = filter_var($p['year'] ?? null, FILTER_VALIDATE_INT);
            $w = $p['score'] ?? $p['weight'] ?? null;
            if ($y === false || $y === null || $y > $asOf || !is_numeric($w)) continue;
            $w = (float)$w;
            if (!is_finite($w) || $w < 0) continue;
            $out[] = ['year'=>(int)$y, 'weight'=>$w, 'id'=>(string)($p['id'] ?? '')];
        }
        usort($out, static fn($a,$b) => ($a['year'] <=> $b['year']) ?: strcmp($a['id'], $b['id']));
        return $out;
    }

    private static function acumular(array $points, int $ate): float
    {
        $k = 0.0;
        foreach ($points as $p) {
            if ($p['year'] > $ate) break;
            $k += $p['weight'];
        }
        return $k;
    }

    private static function janela(array $train, int $ini, int $fim): float
    {
        $k = 0.0;
        foreach ($train as $p) if ($p['year'] >= $ini && $p['year'] <= $fim) $k += $p['weight'];
        return $k;
    }

    private static function taxa(array $train, int $cut, string $modelo, int $passo): float
    {
        if ($modelo === 'exponencial') {
            $g = self::taxaExponencial($train, $cut, self::JANELA_G);
            // Exponencial pura acumulada passo a passo: K(corte+p) = K(corte)·e^(g·p).
            $kPrev = self::acumular($train, $cut);
            return max(0.0, $kPrev * (exp($g * (float)$passo) - exp($g * (float)($passo - 1))));
        }
        if ($modelo === 'media50') return self::janela($train, $cut-49, $cut) / 50.0;
        if ($modelo === 'media100') return self::janela($train, $cut-99, $cut) / 100.0;
        $old = self::janela($train, $cut-99, $cut-50) / 50.0;
        $now = self::janela($train, $cut-49, $cut) / 50.0;
        return max(0.0, $now + (($now-$old)/50.0)*$passo);
    }

    /**
     * Taxa exponencial de crescimento estimada SOMENTE do histórico disponível:
     *   g = ln( K(corte) / K(corte − janela) ) / janela
     * Sem constantes arbitrárias; retorna 0 quando os dados não sustentam o cálculo.
     */
    public static function taxaExponencial(array $train, int $cut, int $janela = self::JANELA_G): float
    {
        if ($janela <= 0) return 0.0;
        $kCut = self::acumular($train, $cut);
        $kOld = self::acumular($train, $cut - $janela);
        if ($kCut <= 0.0 || $kOld <= 0.0) return 0.0;
        $g = log($kCut / $kOld) / (float)$janela;
        return is_finite($g) ? $g : 0.0;
    }

    /**
     * Avaliação a partir de uma base documental fechada até asOf.
     * Se target > asOf, NÃO há observação nem erro e nenhuma linha de 2126 é inventada.
     */
    public static function avaliar(array $points, int $cut, int $target, string $formula = 'log',
                                   string $modelo = 'media50', int $asOf = 2026, ?int $anoBase = null): array
    {
        if (!isset(self::MODELOS[$modelo]) || !isset(self::FORMULAS[$formula]))
            throw new InvalidArgumentException('Escolha uma fórmula e um método de previsão válidos.');
        if ($cut < -10000 || $cut > 3000 || $target <= $cut || $target > 3200 || $target - $cut > 800)
            throw new InvalidArgumentException('Defina um alvo posterior ao corte, com horizonte entre 1 e 800 anos.');
        if ($cut > $asOf) throw new InvalidArgumentException('O corte não pode estar no futuro: não há dados para treinar.');
        $anoBase=$anoBase??$cut;
        if($anoBase>$cut || $anoBase < -10000)throw new InvalidArgumentException('O ano-base deve ser anterior ou igual ao corte, para evitar uso de dados futuros.');

        $all = self::preparar($points, $asOf);
        $train = array_values(array_filter($all, static fn($p) => $p['year'] <= $cut));
        $kCut = self::acumular($train, $cut);
        $kBase = self::acumular($train, $anoBase);
        $earliest = $train ? (int)$train[0]['year'] : null;
        $rateWindow = match ($modelo) { 'media50' => 50, 'exponencial' => self::JANELA_G, default => 100 };
        $base = [
            'status'=>'insuficiente', 'origin'=>$cut, 'target'=>$target,
            'as_of'=>$asOf, 'formula'=>$formula, 'modelo'=>$modelo, 'reference_year'=>$anoBase,
            'horizon'=>$target-$cut, 'n_total'=>count($all), 'n_train'=>count($train),
            'n_after_origin'=>count(array_filter($all, static fn($p) => $p['year'] > $cut && $p['year'] <= $target)),
            'year_last'=>$all ? (int)end($all)['year'] : null,
            'k_origin'=>$kCut, 'k_reference'=>$kBase, 't_origin'=>self::indice($kCut,$kBase,$formula),
            'k_predicted'=>null, 'k_observed'=>null,
            't_predicted'=>null, 't_observed'=>null, 'error_signed'=>null,
            'error_absolute'=>null, 'error_percent'=>null, 'direction'=>null,
            'projection'=>[], 'historic'=>[], 'reason'=>null,
        ];
        if (count($train) < 10 || $kBase <= 0 || $kCut <= 0) {
            $base['reason']='São necessários pelo menos 10 marcos até o corte e K(ano-base) positivo. Se faltar peso em 1900, escolha um ano-base posterior.';
            return $base;
        }
        if ($earliest === null || $earliest > $cut - $rateWindow + 1) {
            $base['reason']='Falta cobertura histórica de '.$rateWindow.' anos antes do corte para o método selecionado.';
            return $base;
        }

        // Previsão: cada incremento futuro depende apenas das TAXAS estimadas no corte.
        $kPred = $kCut;
        $projected = [['year'=>$cut,'k'=>$kCut,'t'=>$base['t_origin']]];
        for ($step=1; $step<=$target-$cut; $step++) {
            $kPred += self::taxa($train, $cut, $modelo, $step);
            $t = self::indice($kPred, $kBase, $formula);
            if ($t === null) throw new RuntimeException('A extrapolação extrapolou a faixa numérica suportada.');
            $projected[]=['year'=>$cut+$step,'k'=>$kPred,'t'=>$t];
        }
        $base['k_predicted']=$kPred;
        $base['t_predicted']=self::indice($kPred, $kBase, $formula);
        $base['projection']=$projected;

        // Série histórica com a mesma normalização fixa em K(corte).
        $timeline=[];$k=0.0;
        foreach ($all as $p) {
            if ($p['year']>$target) break;
            $k += $p['weight'];
            $timeline[]=['year'=>$p['year'], 'k'=>$k, 't'=>self::indice($k,$kBase,$formula)];
        }
        $base['historic']=$timeline;

        // Se ano-alvo está além do período observado, NÃO existe erro verificável.
        $lastObserved=$base['year_last'];
        if ($target <= $asOf && $lastObserved !== null) {
            $kObs = self::acumular($all, $target);
            $tObs = self::indice($kObs, $kBase, $formula);
            $delta = $base['t_predicted']-$tObs;
            $base['k_observed']=$kObs;
            $base['t_observed']=$tObs;
            $base['error_signed']=$delta;
            $base['error_absolute']=abs($delta);
            $base['error_percent']=abs($tObs)>1e-12 ? 100.0 * $delta / $tObs : null;
            $base['direction']=abs($delta)<1e-9?'igual':($delta>0?'superestimou':'subestimou');
            $base['status']='comparavel';
        } else {
            $base['status']='futuro_sem_observacao';
            $base['reason']='O alvo ainda não possui observação histórica completa nesta amostra; nenhuma margem de erro real é calculável.';
        }
        return $base;
    }

    /**
     * Validação retrospectiva em cortes DISTINTOS, sempre com horizonte fixo e
     * alvos no máximo no corte principal; nada da janela pós-corte entra no treino.
     */
    public static function metricasHistoricas(array $points, int $cut, int $horizon,
                                              string $formula, string $modelo, int $asOf, int $step=20, ?int $anoBase=null): array
    {
        if ($horizon < 1 || $horizon > 800 || $step < 1) throw new InvalidArgumentException('Horizonte inválido.');
        $all=self::preparar($points,$asOf);
        if ($all===[]) return ['n'=>0,'mae'=>null,'rmse'=>null,'bias'=>null,'r2'=>null,'folds'=>[]];
        $first=(int)$all[0]['year'];
        $end=min($cut,$asOf,(int)end($all)['year']);
        $start=max(-10000, $first+100, $anoBase??-10000);
        $folds=[];
        for ($c=$start;$c+$horizon<=$end;$c+=$step) {
            $r=self::avaliar($all,$c,$c+$horizon,$formula,$modelo,$asOf,$anoBase);
            if ($r['status']==='comparavel') {
                $folds[]=['corte'=>$c,'alvo'=>$c+$horizon, 'previsto'=>$r['t_predicted'],
                    'observado'=>$r['t_observed'], 'erro'=>$r['error_signed']];
            }
            if (count($folds)>=1000) break;
        }
        $n=count($folds);
        if (!$n) return ['n'=>0,'mae'=>null,'rmse'=>null,'bias'=>null,'r2'=>null,'folds'=>[]];
        $sum=0.0;$abs=0.0;$sq=0.0;
        $obsMean=0.0; foreach ($folds as $f) $obsMean+=(float)$f['observado'];
        $obsMean/=$n;
        $ssTot=0.0; foreach ($folds as $f) $ssTot+=pow((float)$f['observado']-$obsMean,2);
        $ssRes=0.0; foreach ($folds as $f) $ssRes+=pow((float)$f['erro'],2);
        foreach ($folds as $f) {$err=(float)$f['erro'];$sum+=$err;$abs+=abs($err);$sq+=$err*$err;}
        $r2=$ssTot>1e-12?1.0-$ssRes/$ssTot:null;
        return ['n'=>$n,'mae'=>$abs/$n,'rmse'=>sqrt($sq/$n),'bias'=>$sum/$n,'r2'=>$r2,'folds'=>$folds];
    }

    /** Gráfico em escala de ano real: histórico, treino e projeção, com erro no alvo. */
    public static function grafico(array $result, array $metricas=[]): string
    {
        if (($result['status']??'')==='insuficiente')return '<p class="mat-proj-empty">A curva só é exibida quando houver dados suficientes no ano de corte.</p>';
        $esc=static fn(string $s)=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $n=static fn(float $v)=>number_format($v,2,'.','');
        $fmt=static fn(float $v)=>number_format($v,2,',','.');
        $cut=(int)$result['origin'];$target=(int)$result['target'];$asOf=(int)$result['as_of'];
        $h=$result['historic'];$p=$result['projection'];
        $minYear=$cut-100;
        if ($h) $minYear=max($minYear,(int)$h[0]['year']);
        $minYear=min($cut,$minYear);
        $span=max(1,$target-$minYear);
        $right=1060.0;$left=90.0;$top=50.0;$bottom=330.0;
        $x=static fn(int $y):float=>$left+($y-$minYear)/$span*($right-$left);
        $before=[];$after=[];
        $lastHistoric = 0.0;
        foreach ($h as $event) {
            if($event['year']<$minYear){$lastHistoric=(float)$event['t'];continue;}
            if($event['year']<=$cut)$before[]=['year'=>$event['year'],'t'=>(float)$event['t']];
            else if($event['year']<=min($target,$asOf))$after[]=['year'=>$event['year'],'t'=>(float)$event['t']];
        }
        $tCut=(float)($result['t_origin']??100.0);
        $before[]=['year'=>$cut,'t'=>$tCut];
        $after=array_merge([['year'=>$cut,'t'=>$tCut]],$after);
        $plotObsUntil=min($target,$asOf);
        if($plotObsUntil>$cut) {
            $kObsEnd = $result['k_origin'];
            foreach ($h as $event) if($event['year']<=$plotObsUntil)$kObsEnd=(float)$event['k'];
            $after[]=['year'=>$plotObsUntil,'t'=>(float)(self::indice($kObsEnd,(float)$result['k_origin'],(string)$result['formula'])??100.0)];
        }
        $values=array_merge([$tCut],array_column($before,'t'),array_column($after,'t'),array_column($p,'t'));
        if(($metricas['n']??0)>=3 && is_numeric($metricas['rmse'])) {
            $values[]=(float)$result['t_predicted']+(float)$metricas['rmse'];
            $values[]=max(0,(float)$result['t_predicted']-(float)$metricas['rmse']);
        }
        $mn=min($values);$mx=max($values);$range=max(1.0,$mx-$mn);
        $low=max(0,$mn-.18*$range);$high=$mx+.18*$range;
        $y=static fn(float $t):float=>$bottom-($t-$low)/max(1e-9,$high-$low)*($bottom-$top);
        $path=static function(array $arr)use($x,$y,$n):string{
            $s='';$first=true;
            foreach($arr as $r){$s.=($first?'M':'L').$n($x((int)$r['year'])).' '.$n($y((float)$r['t'])).' ';$first=false;}
            return trim($s);
        };
        $svg='<svg class="mat-proj-svg" viewBox="0 0 1120 425" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Curva histórica e previsão do Têmporis em anos cronológicos reais">';
        $svg.='<rect x="0" y="0" width="1120" height="425" rx="13" fill="#fbf8f0"/>';
        for($i=0;$i<=4;$i++){
            $v=$low+($high-$low)*$i/4;$yy=$y($v);
            $svg.='<line x1="90" x2="1060" y1="'.$n($yy).'" y2="'.$n($yy).'" stroke="#ded6c6" stroke-dasharray="4 5"/>';
            $svg.='<text x="78" y="'.$n($yy+5).'" text-anchor="end" font-size="13" fill="#596a60">'.$esc($fmt($v)).'</text>';
        }
        for($i=0;$i<=5;$i++){
            $year=(int)round($minYear+$span*$i/5);$xx=$x($year);
            $svg.='<line x1="'.$n($xx).'" x2="'.$n($xx).'" y1="'.$top.'" y2="'.$bottom.'" stroke="#e7dfd0"/>';
            $svg.='<text x="'.$n($xx).'" y="358" text-anchor="middle" font-size="13" fill="#56685e">'.$esc((string)$year).'</text>';
        }
        $cx=$x($cut);$cy=$y($tCut);
        $svg.='<line x1="'.$n($cx).'" x2="'.$n($cx).'" y1="'.$top.'" y2="'.$bottom.'" stroke="#94795c" stroke-dasharray="5 5" stroke-width="2"/>';
        $svg.='<text x="'.$n(min($cx+9,985)).'" y="32" font-size="14" fill="#94795c">Corte '.$cut.'</text>';
        if($before)$svg.='<path d="'.$path($before).'" fill="none" stroke="#1f7062" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>';
        if($after)$svg.='<path d="'.$path($after).'" fill="none" stroke="#13876b" stroke-width="3.5" stroke-linejoin="round"/>';
        if($p)$svg.='<path d="'.$path($p).'" fill="none" stroke="#ad774c" stroke-width="3.5" stroke-dasharray="8 6" stroke-linejoin="round"/>';
        $svg.='<circle cx="'.$n($cx).'" cy="'.$n($cy).'" r="5" fill="#1f7062" stroke="#fff" stroke-width="2"><title>T('.$cut.') = '.$fmt($tCut).' (ano-base '.(int)$result['reference_year'].')</title></circle>';
        $xx=$x($target);$yp=$y((float)$result['t_predicted']);
        $svg.='<circle cx="'.$n($xx).'" cy="'.$n($yp).'" r="6" fill="#ad774c" stroke="#fff" stroke-width="2"><title>Previsto '.$target.': '.$fmt((float)$result['t_predicted']).'</title></circle>';
        if($result['t_observed']!==null){
            $yo=$y((float)$result['t_observed']);
            $svg.='<line x1="'.$n($xx).'" x2="'.$n($xx).'" y1="'.$n($yp).'" y2="'.$n($yo).'" stroke="#c45443" stroke-width="3"/>';
            $svg.='<circle cx="'.$n($xx).'" cy="'.$n($yo).'" r="6" fill="#13876b" stroke="#fff" stroke-width="2"><title>Observado no acervo '.$target.': '.$fmt((float)$result['t_observed']).'</title></circle>';
        } else if(($metricas['n']??0)>=3 && is_numeric($metricas['rmse'])){
            $rmse=(float)$metricas['rmse'];
            $svg.='<line x1="'.$n($xx).'" x2="'.$n($xx).'" y1="'.$n($y((float)$result['t_predicted']+$rmse)).'" y2="'.$n($y(max(0,(float)$result['t_predicted']-$rmse))).'" stroke="#c19e77" stroke-width="9" opacity=".45"><title>Faixa descritiva ±1 RMSE histórico, NÃO é intervalo de confiança</title></line>';
        }
        $svg.='<line x1="90" x2="1060" y1="330" y2="330" stroke="#a4a997"/>';
        $svg.='<circle cx="105" cy="392" r="5" fill="#1f7062"/><text x="119" y="396" font-size="14" fill="#52665c">Histórico até o corte</text>';
        $svg.='<circle cx="355" cy="392" r="5" fill="#13876b"/><text x="369" y="396" font-size="14" fill="#52665c">Observado no acervo</text>';
        $svg.='<line x1="615" x2="642" y1="392" y2="392" stroke="#ad774c" stroke-dasharray="7 4" stroke-width="3"/><text x="652" y="396" font-size="14" fill="#52665c">Projeção sem dados futuros</text>';
        $svg.='<text x="90" y="20" font-size="13" fill="#607168">Têmporis: T('.(int)$result['reference_year'].') = 100 · eixo X cronológico</text>';
        return $svg.'</svg>';
    }

}
