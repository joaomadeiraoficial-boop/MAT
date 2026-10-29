<?php
/** MAT-Proj 0.1.0: motor experimental independente, sem MySQL, sem Têmporis. */
declare(strict_types=1);

function mp_limit(float $v, float $a=0.0, float $b=100.0): float {return max($a,min($b,$v));}
function mp_group(float $baseline): int {return min(3,(int)floor(mp_limit($baseline)/25));}
function mp_mean(array $xs): float {return count($xs) ? array_sum($xs)/count($xs) : 0.0;}
function mp_mae(array $actual,array $pred): float {
    if (!count($actual) || count($actual)!==count($pred)) throw new InvalidArgumentException('Pares inválidos para MAE');
    $n=0;foreach($actual as $k=>$v)$n+=abs($v-$pred[$k]);return $n/count($actual);
}
function mp_rmse(array $actual,array $pred): float {
    if (!count($actual) || count($actual)!==count($pred)) throw new InvalidArgumentException('Pares inválidos para RMSE');
    $n=0;foreach($actual as $k=>$v)$n+=($v-$pred[$k])**2;return sqrt($n/count($actual));
}
/** OWID CSV columns Entity,Code,Year,value; filters non-ISO3 aggregate codes and invalid observations. */
function mp_load_csv(string $path): array {
    $fh=@fopen($path,'rb');if (!$fh) throw new RuntimeException('Não foi possível abrir o CSV');
    $h=fgetcsv($fh,0,',','"','');if (!$h) {fclose($fh);throw new RuntimeException('CSV vazio');}
    $h=array_map(fn($v)=>strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',(string)$v))),$h);
    $cols=[];foreach(['entity','code','year'] as $x){$p=array_search($x,$h,true);if ($p===false) {fclose($fh);throw new RuntimeException("CSV sem coluna {$x}");}$cols[$x]=$p;}
    $value=null;foreach($h as $j=>$name){if (!in_array($j,$cols,true) && $name!==''){$value=$j;break;}}
    if ($value===null){fclose($fh);throw new RuntimeException('CSV sem coluna de percentual');}
    $out=[];$rows=0;
    while (($r=fgetcsv($fh,0,',','"',''))!==false) {
        if (++$rows>40000) {fclose($fh);throw new RuntimeException('CSV excede o limite de 40 mil linhas');}
        $code=strtoupper(trim($r[$cols['code']]??''));$entity=trim($r[$cols['entity']]??'');
        $yr=filter_var($r[$cols['year']]??null,FILTER_VALIDATE_INT);
        $txt=trim($r[$value]??'');
        if (!preg_match('/^[A-Z]{3}$/D',$code) || $entity==='' || $yr===false || $yr<1900 || $yr>2100 || $txt==='' || !is_numeric($txt))continue;
        $v=(float)$txt;if (!is_finite($v)||$v<0||$v>100)continue;
        $out[$code]['name']=$entity;$out[$code]['years'][$yr]=$v;
    }
    fclose($fh);
    if (!count($out))throw new RuntimeException('Nenhum registro de país válido encontrado');
    ksort($out);return $out;
}
function mp_years(array $data): array {
    $years=[];foreach($data as $country)foreach($country['years'] as $y=>$v)$years[(int)$y]=true;
    $keys=array_keys($years);sort($keys,SORT_NUMERIC);return $keys;
}
/** Return countries with baseline and target observation, for reproducible retrospective validation. */
function mp_pairs(array $data,int $year): array {
    $res=[];foreach($data as $code=>$country){
        if (!isset($country['years'][$year-1],$country['years'][$year]))continue;
        $res[$code]=['code'=>$code,'name'=>$country['name'],'prev'=>(float)$country['years'][$year-1],'actual'=>(float)$country['years'][$year]];
    }return $res;
}
function mp_split(array $rows,int $year,int $seed,int $pct,string $selection): array {
    if ($pct<1 || $pct>90)throw new InvalidArgumentException('Fração fora do intervalo');
    if (!in_array($selection,['stratified','biased'],true))throw new InvalidArgumentException('Estratégia inválida');
    $by=[];foreach($rows as $code=>$r)$by[mp_group($r['prev'])][$code]=$r;
    $chosen=[];
    if($selection==='biased'){
        $x=array_values($rows);usort($x,fn($a,$b)=>($b['prev']<=>$a['prev']) ?: strcmp($a['code'],$b['code']));
        $n=max(1,(int)round(count($x)*$pct/100));foreach(array_slice($x,0,$n) as $r)$chosen[$r['code']]=true;
    }else{
        foreach($by as $g=>$items){$codes=array_keys($items);
            usort($codes,fn($a,$b)=>strcmp(hash('sha256',"{$seed}:{$year}:{$a}"),hash('sha256',"{$seed}:{$year}:{$b}")));
            $n=max(1,(int)round(count($codes)*$pct/100));foreach(array_slice($codes,0,$n) as $code)$chosen[$code]=true;
        }
    }
    return $chosen;
}
/** Shrink observed within-group change to mean observed change; no target from hidden units is used. */
function mp_deltas(array $observed,float $lambda=8.0): array {
    $by=[];$all=[];
    foreach($observed as $r){$d=$r['actual']-$r['prev'];$by[mp_group($r['prev'])][]=$d;$all[]=$d;}
    $general=mp_mean($all);$res=[];
    for($g=0;$g<4;$g++){
        $n=count($by[$g]??[]);$res[$g]=($n*mp_mean($by[$g]??[])+$lambda*$general)/($n+$lambda);
    }
    return ['groups'=>$res,'global'=>$general,'counts'=>array_map(fn($v)=>count($v),$by)];
}
/** Retrospective reconstruction. All reported predictions for missing units are generated without their target values. */
function mp_reconstruct(array $data,int $year,int $pct=20,string $selection='stratified',int $seed=42): array {
    $rows=mp_pairs($data,$year);if(count($rows)<20)throw new RuntimeException('Pelo menos 20 países com dados dos dois anos são necessários.');
    $chosen=mp_split($rows,$year,$seed,$pct,$selection);$observed=array_intersect_key($rows,$chosen);$missing=array_diff_key($rows,$chosen);
    if(!$missing)throw new RuntimeException('Nenhuma unidade oculta: reduza a fração observada');
    $delta=mp_deltas($observed);$allPred=[];$allNaive=[];$missingPred=[];$missingNaive=[];$actualMiss=[];$details=[];
    foreach($rows as $code=>$r){
        $isObs=isset($chosen[$code]);$base=$r['prev'];$p=$isObs?$r['actual']:mp_limit($base+$delta['groups'][mp_group($base)]);
        $b=$isObs?$r['actual']:$base;$allPred[]=$p;$allNaive[]=$b;
        if (!$isObs){$actualMiss[]=$r['actual'];$missingPred[]=$p;$missingNaive[]=$b;}
        $details[]=['code'=>$code,'country'=>$r['name'],'baseline'=>$base,'observed'=>$isObs,'predicted'=>$p,'actual'=>$r['actual']];
    }
    $truth=array_map(fn($r)=>$r['actual'],array_values($rows));
    return ['kind'=>'reconstruct','year'=>$year,'pct'=>$pct,'selection'=>$selection,'seed'=>$seed,'eligible'=>count($rows),'observed'=>count($observed),'hidden'=>count($missing),
        'pred_mean'=>mp_mean($allPred),'baseline_mean'=>mp_mean($allNaive),'real_mean'=>mp_mean($truth),
        'mae'=>mp_mae($actualMiss,$missingPred),'rmse'=>mp_rmse($actualMiss,$missingPred),'baseline_mae'=>mp_mae($actualMiss,$missingNaive),
        'delta'=>$delta,'details'=>$details];
}
/** Predict target year strictly using earlier years; target never used in estimation. */
function mp_forecast(array $data,int $year): array {
    $train=[];
    foreach($data as $code=>$c){$ys=$c['years'];if(!isset($ys[$year-2],$ys[$year-1]))continue;
        $train[$code]=['code'=>$code,'name'=>$c['name'],'prev'=>(float)$ys[$year-2],'actual'=>(float)$ys[$year-1],'target'=>$ys[$year]??null];
    }
    if(count($train)<20)throw new RuntimeException('Pelo menos 20 países com dois anos anteriores são necessários.');
    $model=mp_deltas($train,8.0);$details=[];$pred=[];$naive=[];$truth=[];$pTruth=[];$bTruth=[];
    foreach($train as $code=>$r){
        $last=$r['actual'];$own=$r['actual']-$r['prev'];$typical=$model['groups'][mp_group($last)];
        $prediction=mp_limit($last+0.5*$own+0.5*$typical);
        $pred[]=$prediction;$naive[]=$last;
        if ($r['target']!==null){$truth[]=(float)$r['target'];$pTruth[]=$prediction;$bTruth[]=$last;}
        $details[]=['code'=>$code,'country'=>$r['name'],'baseline'=>$last,'observed'=>false,'predicted'=>$prediction,'actual'=>$r['target']===null?null:(float)$r['target']];
    }
    return ['kind'=>'forecast','year'=>$year,'eligible'=>count($train),'observed'=>0,'hidden'=>count($train),'evaluated'=>count($truth),
        'pred_mean'=>mp_mean($pred),'baseline_mean'=>mp_mean($naive),'real_mean'=>count($truth)?mp_mean($truth):null,
        'mae'=>count($truth)?mp_mae($truth,$pTruth):null,'rmse'=>count($truth)?mp_rmse($truth,$pTruth):null,
        'baseline_mae'=>count($truth)?mp_mae($truth,$bTruth):null,'details'=>$details,'delta'=>$model];
}
/** Rolling historical evaluation. Each year only looks at earlier observations plus a simulated partial year in reconstruction. */
function mp_backtest(array $data,int $start=2017,int $end=2024): array {
    $out=[];for($y=$start;$y<=$end;$y++){
        try{$a=mp_reconstruct($data,$y,20,'stratified',42);$b=mp_forecast($data,$y);
            $out[]=['year'=>$y,'recon_mae'=>$a['mae'],'recon_naive'=>$a['baseline_mae'],'forecast_mae'=>$b['mae'],'forecast_naive'=>$b['baseline_mae'],'count'=>$a['eligible']];
        }catch(RuntimeException $e){continue;}
    }return $out;
}
