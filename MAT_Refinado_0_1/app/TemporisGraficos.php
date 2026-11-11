<?php
declare(strict_types=1);
/**
 * Visualização cronológica e diagnóstico do método, sem qualquer escrita no banco.
 * Faixas horizontais = incerteza DOCUMENTAL da data; nunca intervalo de confiança.
 * Erros do backtesting = resíduos da previsão, nunca incerteza das datas.
 */
final class TemporisGraficos
{
    public static function formato(float $valor, int $casas=2): string
    {
        return number_format($valor, $casas, ',', '.');
    }

    public static function indice(float $k, float $kBase, string $formula='log'): ?float
    {
        if (!is_finite($k) || !is_finite($kBase) || $kBase<=0. || $k<0.) return null;
        return $formula==='potencia'
            ? 100. * pow($k/$kBase, .65)
            : 100. * log1p($k)/log1p($kBase);
    }

    /** Curvas compatíveis: mesmo conjunto de 11 critérios, duas transformações de escala.
     *  NÃO substitui o método original que utiliza outras notas no banco legado.
     */
    public static function curvas(array $points, int $anoBase): array
    {
        $points=array_values(array_filter($points,static fn($p)=>isset($p['year'],$p['score'])
            && is_numeric($p['year']) && is_numeric($p['score'])
            && is_finite((float)$p['score']) && (float)$p['score']>=0.));
        usort($points,static fn($a,$b)=>((int)$a['year']<=>(int)$b['year'])
            ?:strcmp((string)($a['id']??''),(string)($b['id']??'')));
        $sum=0.;$base=0.;$out=[];
        foreach($points as $p){
            $sum+=(float)$p['score'];
            if((int)$p['year']<=$anoBase)$base=$sum;
            $out[]=$p+['k'=>0.];
            $out[count($out)-1]['k']=$sum;
        }
        foreach($out as &$p){
            $p['log']=self::indice((float)$p['k'],$base,'log');
            $p['potencia']=self::indice((float)$p['k'],$base,'potencia');
        }unset($p);
        return ['series'=>$out,'k_base'=>$base,'k_total'=>$sum,'normalizavel'=>$base>0.,'included'=>count($out),'ano_base'=>$anoBase];
    }

    /** Série em escada com posições X calculadas PELOS ANOS reais, não pela ordem da fila. */
    public static function svgCronologico(array $points, int $anoBase, string $destaque='log', bool $comparar=true, ?int $desde=null): string
    {
        $calc=self::curvas($points,$anoBase);
        $all=$calc['series'];
        $previo=null;
        if($desde!==null){
            foreach($all as $p){if((int)$p['year']<$desde)$previo=$p;}
            $pts=array_values(array_filter($all,static fn($p)=>(int)$p['year']>=$desde));
        }else{$pts=$all;}
        if(count($pts)<2)return '<p class="mat-grafo-vazio">São necessários pelo menos dois marcos com notas e datas utilizáveis.</p>';
        $normal=$calc['normalizavel'];
        $series=$normal?($comparar?['log'=>'Reformulada · log','potencia'=>'Potência 0,65']:[$destaque=>'Índice']):['k'=>'K(t) · sem normalização'];
        $years=[];$values=[];
        if($previo!==null){
            foreach($series as $key=>$label)$values[]=(float)($key==='k'?$previo['k']:$previo[$key]);
        }
        foreach($pts as $p){
            $years[]=(int)$p['year'];
            if(isset($p['year_inicio']))$years[]=(int)$p['year_inicio'];
            if(isset($p['year_fim']))$years[]=(int)$p['year_fim'];
            foreach($series as $key=>$label)$values[]=(float)($key==='k'?$p['k']:$p[$key]);
        }
        $xmin=min($years);$xmax=max($years);
        // Ano-base na régua somente quando já pertence à extensão dos dados.
        $xmin=min($xmin, $anoBase >= $xmin && $anoBase <= $xmax ? $anoBase : $xmin);
        $range=max(1,$xmax-$xmin);
        $ymax=max(1.,max($values)*1.12);
        $W=1140;$H=445;$L=79;$R=1090;$T=48;$B=367;
        $x=static fn(int $yr):float=>$L+($yr-$xmin)/$range*($R-$L);
        $y=static fn(float $v):float=>$B-min($ymax,max(0.,$v))/$ymax*($B-$T);
        $num=static fn(float $v):string=>number_format($v,2,'.','');
        $esc=static fn(string $s):string=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $svg='<svg xmlns="http://www.w3.org/2000/svg" class="mat-grafo-svg" viewBox="0 0 '.$W.' '.$H.'" role="img" aria-label="Série cronológica real com fórmulas e incertezas de datação">';
        $svg.='<rect width="1140" height="445" fill="#fbf8f0" rx="12"/>';
        for($i=0;$i<=4;$i++){
            $v=$ymax*$i/4;$yy=$y($v);
            $svg.='<line x1="'.$L.'" y1="'.$num($yy).'" x2="'.$R.'" y2="'.$num($yy).'" stroke="#e3d9c9" stroke-dasharray="4 5"/>';
            $svg.='<text x="67" y="'.$num($yy+4).'" text-anchor="end" fill="#62746c" font-size="13">'.$esc(self::formato($v,1)).'</text>';
        }
        for($i=0;$i<=6;$i++){
            $yr=(int)round($xmin+$range*$i/6);$xx=$x($yr);
            $svg.='<line x1="'.$num($xx).'" y1="'.$T.'" x2="'.$num($xx).'" y2="'.$B.'" stroke="#e8e0d3"/>';
            $svg.='<text x="'.$num($xx).'" y="394" text-anchor="middle" fill="#536960" font-size="13">'.$esc($yr<0?abs($yr).' a.C.':(string)$yr).'</text>';
        }
        if($anoBase>=$xmin && $anoBase<=$xmax){
            $xb=$x($anoBase);$svg.='<line x1="'.$num($xb).'" y1="'.$T.'" x2="'.$num($xb).'" y2="'.$B.'" stroke="#ad6c49" stroke-width="1.8" stroke-dasharray="7 5"/>';
            $svg.='<text x="'.$num(min($R-100,$xb+7)).'" y="31" fill="#9b6042" font-size="13">Base '.$anoBase.'</text>';
        }
        $colors=['log'=>'#135c58','potencia'=>'#ad734f','k'=>'#135c58'];
        foreach($series as $key=>$label){
            $parts=[];$v0=$previo===null?0.:(float)($key==='k'?$previo['k']:$previo[$key]);
            $parts[]='M '.$num($L).' '.$num($y($v0));
            foreach($pts as $p){
                $xx=$x((int)$p['year']);$vv=(float)($key==='k'?$p['k']:$p[$key]);
                $parts[]='H '.$num($xx).' V '.$num($y($vv));
                $v0=$vv;
            }
            $parts[]='H '.$num($R);
            $width=$key===$destaque||$key==='k'?'3.6':'2.5';
            $dash=$key==='potencia'?' stroke-dasharray="9 6"':'';
            $svg.='<path d="'.implode(' ',$parts).'" fill="none" stroke="'.$colors[$key].'" stroke-width="'.$width.'" stroke-linejoin="round"'.$dash.'/>';
        }
        // Faixas de incerteza do ANO: não são barras de confiança do índice.
        foreach($pts as $p){
            $v=(float)($normal?$p[$destaque]:$p['k']);
            $xx=$x((int)$p['year']);$yy=$y($v);
            $begin=(int)($p['year_inicio']??$p['year']);
            $end=(int)($p['year_fim']??$p['year']);
            if($begin<$end){
                $svg.='<line x1="'.$num($x($begin)).'" y1="'.$num($yy).'" x2="'.$num($x($end)).'" y2="'.$num($yy).'" stroke="#bf814f" stroke-width="5" opacity=".55"/>';
                foreach([$begin,$end] as $bound)$svg.='<line x1="'.$num($x($bound)).'" x2="'.$num($x($bound)).'" y1="'.$num($yy-6).'" y2="'.$num($yy+6).'" stroke="#bf814f" stroke-width="2"/>';
            }
            $state=(string)($p['estado_revisao_humana']??'');
            $homolog=(int)($p['homologado']??0)===1&&$state==='HOMOLOGADO';
            $approved=$homolog||$state==='APROVADO';
            $color=$homolog?'#178467':($approved?'#b88635':'#576b66');
            $stroke=$homolog?'#16684d':'#fffdf6';
            $r=$homolog?6.5:($approved?5.5:3.3);
            $title=(string)($p['title']??'Marco')." | ".(string)($p['id']??'')." | ano ".(int)$p['year'];
            if($begin<$end)$title.=" | intervalo $begin a $end";
            $title.=' | '.($homolog?'HOMOLOGADO':($approved?'APROVADO':'provisório')).' | peso '.self::formato((float)$p['score'],4);
            $svg.='<circle cx="'.$num($xx).'" cy="'.$num($yy).'" r="'.$r.'" fill="'.$color.'" stroke="'.$stroke.'" stroke-width="1.6"><title>'.$esc($title).'</title></circle>';
        }
        $svg.='<text x="'.$L.'" y="426" fill="#62746c" font-size="13">Eixo X = ano histórico (espaçamento cronológico proporcional)</text>';
        $svg.='</svg>';
        $tituloSerie= $normal ? ($destaque==='potencia' && !$comparar ? 'Potência 0,65' : 'Reformulada · log') : 'K(t) sem base';
        $corSerie= $destaque==='potencia' && !$comparar ? '#ad734f' : '#135c58';
        $svg.='<div class="mat-grafo-legenda"><span><i style="background:'.$corSerie.'"></i>'.$tituloSerie.'</span>';
        if($normal && $comparar)$svg.='<span><i style="background:#ad734f"></i>Potência 0,65 · mesma rubrica</span>';
        $svg.='<span><i style="background:#b88635"></i>Aprovado pelo pesquisador</span><span><i style="background:#178467"></i>Homologado</span><span><i style="background:#bf814f"></i>Faixa de datação (não estatística)</span></div>';
        if(!$normal)$svg.='<p class="mat-grafo-aviso">A amostra não possui peso positivo até o ano-base '.$anoBase.'. Exibimos K(t), sem inventar um denominador para T(t).</p>';
        return $svg;
    }

    /** Régua de decisões: pontos mantêm ano exato e tooltips; sem etiquetas sobrepostas. */
    public static function svgDecisoes(array $decisoes): string
    {
        $v=array_values(array_filter($decisoes,static fn($p)=>isset($p['year'])&&is_int($p['year'])));
        if(!$v)return '<p class="mat-grafo-vazio">Nenhuma decisão humana possui ainda uma data validada. Não convertemos ano extraído em confirmação.</p>';
        usort($v,static fn($a,$b)=>($a['year']<=>$b['year'])?:strcmp($a['id'],$b['id']));
        $first=min(array_column($v,'year'));$last=max(array_column($v,'year'));
        $delta=max(1,$last-$first);$left=84.;$right=1052.;
        $x=static fn(int $yr):float=>$first===$last?($left+$right)/2:$left+($yr-$first)/$delta*($right-$left);
        $n=static fn(float $a)=>number_format($a,2,'.','');
        $esc=static fn(string $a)=>htmlspecialchars($a,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $aprovados=0;$homologados=0;
        foreach($v as $p){if(($p['estado']??'')==='HOMOLOGADO'&&(int)($p['homologado']??0)===1)$homologados++;else $aprovados++;}
        $svg='<svg xmlns="http://www.w3.org/2000/svg" class="mat-grafo-svg" viewBox="0 0 1135 230" role="img" aria-label="Anos das decisões humanas em escala cronológica; consulte detalhes ao posicionar o ponteiro sobre os pontos">';
        $svg.='<rect width="1135" height="230" fill="#fbf8f0" rx="12"/>';
        $svg.='<text x="'.$left.'" y="29" fill="#3b5a50" font-size="15">Decisões humanas por ano histórico</text>';
        $svg.='<text x="'.$left.'" y="49" fill="#7b735e" font-size="12">'.$aprovados.' aprovações · '.$homologados.' homologações · '.count($v).' registros datados</text>';
        $svg.='<line x1="'.$left.'" x2="'.$right.'" y1="143" y2="143" stroke="#82948b" stroke-width="2"/>';
        // Apenas marcas maiores no eixo: texto para cada ponto causava sobreposição.
        for($i=0;$i<=6;$i++){
            $yr=(int)round($first+($last-$first)*$i/6);$xx=$x($yr);
            $txt=$yr<0?abs($yr).' a.C.':(string)$yr;
            $svg.='<line x1="'.$n($xx).'" x2="'.$n($xx).'" y1="143" y2="151" stroke="#8b9384"/>';
            $svg.='<text x="'.$n($xx).'" y="169" text-anchor="middle" fill="#52675e" font-size="12">'.$esc($txt).'</text>';
        }
        $used=[];
        foreach($v as $p){
            $xx=$x((int)$p['year']);$laneKey=(int)floor($xx/13);
            $offset=$used[$laneKey]??0;$used[$laneKey]=$offset+1;
            $yy=130.-($offset%4)*16.;
            $homolog=($p['estado']??'')==='HOMOLOGADO'&&(int)($p['homologado']??0)===1;
            $color=$homolog?'#188066':'#b88635';
            $title=(string)$p['id'].' · '.(string)$p['title'].' · ano '.(int)$p['year'].' · '.($homolog?'HOMOLOGADO':'APROVADO');
            if(empty($p['notas_completas']))$title.=' · notas incompletas';
            $svg.='<line x1="'.$n($xx).'" x2="'.$n($xx).'" y1="'.$n($yy+5).'" y2="143" stroke="'.$color.'" stroke-width="0.9" opacity=".42"/>';
            $svg.='<circle cx="'.$n($xx).'" cy="'.$n($yy).'" r="5.4" fill="'.$color.'" stroke="#fffdf7" stroke-width="1.5"><title>'.$esc($title).'</title></circle>';
        }
        $svg.='<text x="'.$left.'" y="199" font-size="12" fill="#53665d">Cada ponto conserva seu ano exato; passe o mouse para identificar o marco.</text>';
        $svg.='<circle cx="'.$left.'" cy="216" r="4.5" fill="#b88635"/><text x="'.($left+11).'" y="220" font-size="11" fill="#63736a">Aprovado</text>';
        $svg.='<circle cx="'.($left+110).'" cy="216" r="4.5" fill="#188066"/><text x="'.($left+121).'" y="220" font-size="11" fill="#63736a">Homologado</text>';
        return $svg.'</svg>';
    }

    /** Mesma escala no gráfico previsto/observado; barras conectam exatamente os valores. */
    public static function svgPrevisao(array $result, array $historico=[]): string
    {
        $models=array_filter($result['models']??[],static fn($m)=>($m['status']??'')==='calculado' && ($m['t_predicted']??null)!==null);
        $actual=$result['t_observed']??null;
        if(!$models||$actual===null)return '<p class="mat-grafo-vazio">Sem previsões comparáveis. Precisamos de uma amostra elegível suficiente antes do corte e de marcos observados depois dele.</p>';
        $num=static fn(float $v)=>number_format($v,2,'.','');
        $esc=static fn(string $v)=>htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $vals=[(float)$actual];
        foreach($models as $key=>$m){
            $pred=(float)$m['t_predicted'];$vals[]=$pred;
            $met=$historico[$key]??[];
            if(($met['n_folds']??0)>=3 && isset($met['rmse'])){
                $rmse=(float)$met['rmse'];if(is_finite($rmse)&&$rmse>=0.){
                    $vals[]=$pred-$rmse;$vals[]=$pred+$rmse;
                }
            }
        }
        $min=min($vals);
        $max=max($vals);
        $spread=max(1.,$max-$min);$lo=max(0.,$min-$spread*.20);$hi=$max+$spread*.20;
        $x=static fn(float $v)=>320+($v-$lo)/max(1e-9,$hi-$lo)*680;
        $H=115+count($models)*80;
        $svg='<svg class="mat-grafo-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1120 '.$H.'" role="img" aria-label="Comparação de previsão e observação; segmentos representam erro absoluto">';
        $svg.='<rect width="1120" height="'.$H.'" rx="12" fill="#fbf8f0"/>';
        $actualX=$x((float)$actual);
        $svg.='<line x1="'.$num($actualX).'" x2="'.$num($actualX).'" y1="45" y2="'.($H-52).'" stroke="#158265" stroke-width="3" stroke-dasharray="7 5"/>';
        $svg.='<text x="'.$num(min(990,$actualX+8)).'" y="32" font-size="14" fill="#13795e">Observado '.self::formato((float)$actual).'</text>';
        $n=0;
        foreach($models as $key=>$m){
            $yy=79+$n++*80;$pred=(float)$m['t_predicted'];$ex=$x($pred);
            $shortLabel=match((string)$key){'media50'=>'Média 50 anos','media100'=>'Média 100 anos',
                'tendencia100'=>'Tendência 100 anos',default=>(string)($m['label']??$key)};
            $svg.='<text x="20" y="'.($yy+5).'" font-size="15" fill="#28413c">'.$esc($shortLabel).'</text>';
            $met=$historico[$key]??[];
            if(($met['n_folds']??0)>=3 && isset($met['rmse'])){
                $rmse=(float)$met['rmse'];
                if(is_finite($rmse) && $rmse>=0.){
                    $xmin=$x($pred-$rmse);$xmax=$x($pred+$rmse);
                    $svg.='<rect x="'.$num($xmin).'" y="'.($yy-11).'" width="'.$num(max(1.,$xmax-$xmin)).'" height="22" fill="#b88764" opacity=".22" rx="3"><title>Faixa descritiva: previsão ± 1 RMSE histórico, '.(int)$met['n_folds'].' testes. NÃO é intervalo de confiança de 95%.</title></rect>';
                }
            }
            $svg.='<line x1="'.$num(min($actualX,$ex)).'" x2="'.$num(max($actualX,$ex)).'" y1="'.$yy.'" y2="'.$yy.'" stroke="#b77b59" stroke-width="6" stroke-linecap="round"/>';
            $svg.='<circle cx="'.$num($ex).'" cy="'.$yy.'" r="7" fill="#ad734f"><title>Previsto '.self::formato($pred).' | erro '.self::formato(abs($pred-(float)$actual)).'</title></circle>';
            $svg.='<text x="'.($num(min(1025,$ex+12))).'" y="'.($yy-11).'" font-size="13" fill="#8d5138">'.self::formato($pred).'</text>';
            $svg.='<text x="1040" y="'.($yy+4).'" text-anchor="end" font-size="13" fill="#8d5138">|erro| '.self::formato(abs($pred-(float)$actual)).'</text>';
        }
        $svg.='<text x="320" y="'.($H-18).'" font-size="13" fill="#607169">Linha verde = observado · segmento = erro · faixa clara = ±1 RMSE (se ≥3 testes; NÃO é IC 95%)</text></svg>';
        return $svg;
    }
}
