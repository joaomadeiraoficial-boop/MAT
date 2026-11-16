<?php
/** Índice Têmporis · método Final. Curadoria ativa é priorizada; arquivos estáticos identificados. */
require_once MAT_ROOT.'/app/TemporisFinal.php';
$tfGet = static function(string $key,string $fallback,array $choices):string{
    $value=(string)($_GET[$key]??$fallback);
    return isset($choices[$value])?$value:$fallback;
};
$tfFontePedida=$tfGet('fonte','auto',TemporisFinal::FONTES);
$tfCoorte=$tfGet('coorte','ampla',TemporisFinal::COORTES);
$tfPeso=$tfGet('peso','quadratica',TemporisFinal::PESOS);
$tfEscala=$tfGet('escala','bruta',TemporisFinal::ESCALAS);
$tfBase=filter_var($_GET['base']??1900,FILTER_VALIDATE_INT);
$tfBase=$tfBase===false?1900:max(1600,min(2000,$tfBase));
$tfCorte=filter_var($_GET['corte']??2026,FILTER_VALIDATE_INT);
$tfCorte=$tfCorte===false?2026:max(1700,min(2026,$tfCorte));
$tfErro=null;$tfFonte='desconhecida';$tfBanco='—';$tfDados=null;
$tfTodos=[];$tfPontos=[];$tfLinha=[];$tfHistorico=[];$tfPeriodos=[];$tfProjecoes=[];$tfIndex=null;
$tfConsulta=null;$tfConsultaErro=null;$tfAvanco=null;
$tfNumber=static fn(?float $v,int $digits=2):string=>$v===null?'—':number_format($v,$digits,',','.');
try {
    $resultado=TemporisFinal::obterDados($tfFontePedida,2026);
    $tfDados=$resultado['dados'];$tfFonte=$resultado['fonte'];$tfBanco=$resultado['database'];
    $tfTodos=$tfDados['pontos'];$tfPontos=TemporisFinal::filtrar($tfTodos,$tfCoorte);
    $tfLinha=TemporisFinal::linha($tfPontos,$tfPeso);
    $tfKbase=TemporisFinal::k($tfLinha,$tfBase);
    $tfK2026=TemporisFinal::k($tfLinha,2026);
    $tfIndex=TemporisFinal::indice($tfK2026,$tfKbase,$tfEscala);
    // Consulta pontual: Têmporis de um ano e avanço entre dois anos.
    // Reutilizam a mesma linha de pesos (marcos reais do banco), sem interpolação de valores.
    $tfAnoConsulta=filter_var($_GET['ano_consulta']??null,FILTER_VALIDATE_INT);
    $tfAnoA=filter_var($_GET['ano_a']??null,FILTER_VALIDATE_INT);
    $tfAnoB=filter_var($_GET['ano_b']??null,FILTER_VALIDATE_INT);
    if($tfAnoConsulta!==false&&$tfAnoConsulta!==null){
        $tfAnoConsulta=max(-9999,min(2100,$tfAnoConsulta));
        $tfKConsulta=TemporisFinal::k($tfLinha,$tfAnoConsulta);
        $tfConsulta=['ano'=>$tfAnoConsulta,'k'=>$tfKConsulta,
            't'=>TemporisFinal::indice($tfKConsulta,$tfKbase,$tfEscala),
            'marcos'=>count(array_filter($tfPontos,static fn(array $p):bool=>$p['year']<=$tfAnoConsulta))];
    }
    if($tfAnoA!==false&&$tfAnoA!==null&&$tfAnoB!==false&&$tfAnoB!==null){
        $tfAnoA=max(-9999,min(2100,$tfAnoA));$tfAnoB=max(-9999,min(2100,$tfAnoB));
        if($tfAnoA===$tfAnoB){$tfConsultaErro='Escolha dois anos diferentes para calcular o avanço.';}
        else{
            if($tfAnoA>$tfAnoB){[$tfAnoA,$tfAnoB]=[$tfAnoB,$tfAnoA];}
            $tfKa=TemporisFinal::k($tfLinha,$tfAnoA);$tfKb=TemporisFinal::k($tfLinha,$tfAnoB);
            $tfTa=TemporisFinal::indice($tfKa,$tfKbase,$tfEscala);$tfTb=TemporisFinal::indice($tfKb,$tfKbase,$tfEscala);
            $tfSpan=max(1,abs($tfAnoB-$tfAnoA));
            $tfAvanco=['a'=>$tfAnoA,'b'=>$tfAnoB,'ka'=>$tfKa,'kb'=>$tfKb,'ta'=>$tfTa,'tb'=>$tfTb,
                'dt'=>($tfTa!==null&&$tfTb!==null)?$tfTb-$tfTa:null,'dk'=>$tfKb-$tfKa,
                'marcos'=>count(array_filter($tfPontos,static fn(array $p):bool=>$p['year']>=$tfAnoA&&$p['year']<=$tfAnoB)),
                'anos'=>$tfSpan,'taxa'=>($tfKb-$tfKa)/$tfSpan];
        }
    }
    $tfAuditoria=$tfDados['audit'];
    $tfValidos=count(array_filter($tfPontos,static fn($p)=>$p['ano_validado']));
    $tfSem=count(array_filter($tfPontos,static fn($p)=>$p['sem_evidencia']));
    $tfProv=count(array_filter($tfPontos,static fn($p)=>$p['provisorio']));
    $tfExtra=count(array_filter($tfPontos,static fn($p)=>$p['ano_extraido']));
    foreach([1700,1750,1800,1850,1900,1950,2000,2026] as $year){
        $k=TemporisFinal::k($tfLinha,$year);
        $row=['ano'=>$year,'k'=>$k];
        foreach(TemporisFinal::ESCALAS as $escala=>$rotulo)$row[$escala]=TemporisFinal::indice($k,$tfKbase,$escala);
        $tfHistorico[]=$row;
    }
    foreach([[1701,1751],[1751,1801],[1801,1851],[1851,1901],[1901,1951],[1951,2001],[2001,2027]] as [$ini,$fim]) {
        $part=TemporisFinal::periodo($tfPontos,$tfPeso,$ini,$fim);
        $part['de']=$ini;$part['ate']=$fim;
        $tfPeriodos[]=$part;
    }
} catch (Throwable $e) {$tfErro=$e->getMessage();}

// Gráfico SVG próprio: eixo horizontal ano real; nunca usar quantidade de registros como escala temporal.
$tfSvg=function(array $series,array $years,int $height=290):string{
    $W=960;$left=78;$right=923;$top=28;$bottom=$height-52;
    $start=min($years);$end=max($years);$values=[];
    foreach($series as $s)foreach($s['data'] as $year=>$v)if($v!==null && is_finite((float)$v))$values[]=(float)$v;
    if(!$values)return '<p>Sem série normalizável para essa seleção.</p>';
    $lo=min($values);$hi=max($values);$span=max(1.,$hi-$lo);$lo=max(0.,$lo-$span*.12);$hi+=$span*.12;
    $x=static fn($year)=>$left+($year-$start)/max(1,$end-$start)*($right-$left);
    $y=static fn($v)=>$bottom-($v-$lo)/max(.00001,$hi-$lo)*($bottom-$top);
    $f=static fn($v)=>number_format((float)$v,2,'.','');
    $out='<svg viewBox="0 0 '.$W.' '.$height.'" role="img" aria-label="Evolução histórica do Índice Têmporis, com anos proporcionais" xmlns="http://www.w3.org/2000/svg">';
    for($i=0;$i<=4;$i++){$v=$lo+($hi-$lo)*$i/4;$Y=$y($v);
        $out.='<line x1="'.$left.'" x2="'.$right.'" y1="'.$f($Y).'" y2="'.$f($Y).'" stroke="#e4dbca" stroke-dasharray="4 4"/>';
        $out.='<text x="65" y="'.$f($Y+4).'" text-anchor="end" fill="#5a6b64" font-size="13">'.number_format($v,1,',','.').'</text>';
    }
    foreach($years as $year){$X=$x($year);$out.='<text x="'.$f($X).'" y="'.$f($bottom+25).'" text-anchor="middle" fill="#526b5f" font-size="13">'.(int)$year.'</text>';}
    foreach($series as $s){$path='';$first=true;
        foreach($s['data'] as $year=>$v){if($v===null)continue;$path.=($first?'M':'L').$f($x((int)$year)).' '.$f($y((float)$v)).' ';$first=false;}
        $color=htmlspecialchars($s['color'],ENT_QUOTES);$label=htmlspecialchars($s['name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $out.='<path d="'.trim($path).'" fill="none" stroke="'.$color.'" stroke-width="'.($s['principal']??false?'4':'2.5').'" '.(!($s['principal']??false)?'stroke-dasharray="6 5"':'').'><title>'.$label.'</title></path>';
        if(($s['principal']??false))foreach($s['data'] as $year=>$v){if($v!==null)$out.='<circle cx="'.$f($x((int)$year)).'" cy="'.$f($y((float)$v)).'" r="4" fill="'.$color.'"><title>'.$label.' · '.$year.' · '.$f($v).'</title></circle>';}
    }
    return $out.'</svg>';
};
?>
<header class="page-heading tf-heading">
 <div class="section-overline">ÍNDICE TÊMPORIS / MAT FINAL / FORMULAÇÃO EM TESTE</div>
 <h1>Índice Têmporis <em>· método atual.</em></h1>
 <p>Primeiro, o que a nossa metodologia registra. A comparação com fórmulas alternativas e os modelos de previsão ficam disponíveis apenas quando você quiser examiná-los.</p>
</header>
<?php if($tfErro): ?>
<div class="mat-final-warning" role="alert"><strong>Os dados solicitados não puderam ser carregados:</strong> <?=h($tfErro)?>. <a href="<?=mat_url('temporis',['fonte'=>'auto'])?>">Tentar fonte automática</a> · <a href="<?=mat_url('temporis',['fonte'=>'snapshot'])?>">Ver exportação histórica</a>. Não apresentamos um valor de outra fonte como se fosse o banco solicitado.</div>
<?php else: ?>
<section class="tf-primary-hero" aria-labelledby="tf-principal-titulo">
  <div class="tf-hero-info"><span class="tf-eyebrow">RESULTADO DA FORMULAÇÃO FINAL</span><h2 id="tf-principal-titulo">O índice calculado com os marcos selecionados</h2>
  <div class="tf-formula">K(t) = Σ wᵢ (marcos até o ano t)</div>
  <p>O peso de cada marco é <strong>w = (I/10)² × C × F</strong>, e K(t) soma os pesos dos marcos datados até o ano t. O índice bruto não depende de 1900; a normalização é apenas uma forma opcional de exibição. Acumular não demonstra, sozinho, aceleração.</p>
  <a href="<?=mat_url('metodo')?>">Ver as seis fórmulas no caderno →</a></div>
  <div class="tf-hero-value"><span>TÊMPORIS · 2026</span><strong><?=$tfNumber($tfIndex)?></strong><small><?=h(TemporisFinal::ESCALAS[$tfEscala])?><?= $tfEscala==='bruta' ? ' · sem ano-base' : ' · referência '.$tfBase.' = 100' ?></small><small><?=count($tfPontos)?> registros efetivamente calculados</small></div>
</section>
<div class="tf-source-bar" role="status"><strong><?= $tfFonte==='snapshot'?'Exportação estática (não atualiza)':'MySQL conectado · leitura em tempo real' ?></strong><span>Origem: <code><?=h($tfBanco)?></code></span><span>Solicitado: <?=h(TemporisFinal::FONTES[$tfFontePedida])?></span></div>
<?php if($tfFonte==='snapshot'):?><div class="mat-final-warning" role="alert"><strong>Estes números NÃO vêm do banco de dados.</strong> O MySQL da curadoria não respondeu nesta leitura, então a página exibiu a exportação estática de 07/11/2026 (800 candidatos), que não acompanha o acervo atual. Inicie o MySQL no XAMPP e recarregue para calcular sobre os dados reais; ou abra a fonte “Curadoria atual” diretamente para ver o erro de conexão.</div><?php endif; ?>
<form class="tf-form" method="get" action="index.php">
 <input type="hidden" name="page" value="temporis">
 <div class="tf-form-main"><label>Origem dos dados<select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfFontePedida===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
 <label>Seleção de marcos<select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfCoorte===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
 <button class="btn btn-dark" type="submit">Atualizar análise →</button></div>
 <details class="tf-filter-detail" <?=($tfPeso!=='quadratica'||$tfEscala!=='bruta'||$tfBase!==1900||$tfCorte!==2026)?'open':''?>>
  <summary>Opções avançadas · pesos, escalas e ano-base</summary><div class="tf-form-advanced">
  <label>Pesos<select name="peso"><?php foreach(TemporisFinal::PESOS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfPeso===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
  <label>Transformação do índice<select name="escala"><?php foreach(TemporisFinal::ESCALAS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfEscala===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
  <label>Ano-base (só para escalas relativas)<input type="number" name="base" value="<?=$tfBase?>" min="1600" max="2000"></label>
  <label>Ano de corte nas previsões<input type="number" name="corte" value="<?=$tfCorte?>" min="1700" max="2026"></label>
  </div></details>
</form>
<section class="tf-audit-box" aria-label="Rastreabilidade da contagem dos marcos">
 <div class="tf-counts"><div><span>Candidatos na fonte</span><strong><?=$tfAuditoria['total']?></strong></div><div><span>Elegíveis para cálculo</span><strong><?=$tfAuditoria['elegiveis']?></strong></div><div><span>Na seleção atual</span><strong><?=count($tfPontos)?></strong></div><div><span>Não elegíveis</span><strong><?=$tfAuditoria['total']-$tfAuditoria['elegiveis']?></strong></div></div>
 <details><summary>Por que nem todos os marcos aparecem? · detalhar exclusões e qualidade dos dados</summary>
  <p>O MAT <strong>não apaga</strong> os candidatos excluídos desta curva. A soma abaixo explica os registros existentes nesta fonte, e os filtros podem reduzir ainda mais a série calculada.</p>
  <div class="tf-audit-grid"><span>Rejeitados: <b><?=$tfAuditoria['rejeitados']?></b></span><span>Sem as 11 notas: <b><?=$tfAuditoria['sem_11_notas']?></b></span><span>Sem data utilizável: <b><?=$tfAuditoria['sem_data']?></b></span><span>Posteriores a 2026: <b><?=$tfAuditoria['futuros']?></b></span><span>Datas extraídas (na seleção): <b><?=$tfExtra?></b></span><span>Notas provisórias (na seleção): <b><?=$tfProv?></b></span><span>Com SEM_EVIDENCIA: <b><?=$tfSem?></b></span><span>Datas não extraídas (na seleção): <b><?=$tfValidos?></b></span></div>
  <p>Os anos extraídos e as notas provisórias não se tornam validados por entrarem no ensaio. Para incluir registros sem ano será necessário revisar a datação, não atribuir uma data fictícia. Consulte o <a href="<?=mat_url('andamento')?>">acompanhamento da curadoria</a> para trabalhar no conjunto completo.</p>
 </details>
</section>
<?php if($tfKbase<=0 && $tfEscala!=='bruta'):?><div class="mat-final-warning">K(<?=$tfBase?>) = 0. O índice é matematicamente indefinido nessa seleção; altere a amostra ou o ano-base.</div><?php endif; ?>
<section class="paper-panel mat-final-section" aria-labelledby="tf-consulta-titulo">
 <div class="panel-head"><span>CONSULTA DIRETA</span><span>MESMOS MARCOS · MESMOS PESOS · MESMA ESCALA</span></div>
 <h2 id="tf-consulta-titulo">Têmporis de um ano e avanço entre dois anos</h2>
 <p>As duas consultas somam os <strong>marcos reais do banco</strong> até o ano pedido, na seleção atual — nada é interpolado ou extrapolado. O avanço é a diferença do índice entre os dois anos, em pontos.</p>
 <form class="tf-form" method="get" action="index.php">
  <input type="hidden" name="page" value="temporis">
  <input type="hidden" name="fonte" value="<?=h($tfFontePedida)?>">
  <input type="hidden" name="coorte" value="<?=h($tfCoorte)?>">
  <input type="hidden" name="peso" value="<?=h($tfPeso)?>">
  <input type="hidden" name="escala" value="<?=h($tfEscala)?>">
  <input type="hidden" name="base" value="<?=$tfBase?>">
  <input type="hidden" name="corte" value="<?=$tfCorte?>">
  <div class="tf-form-main"><label>Têmporis de um ano<input type="number" name="ano_consulta" value="<?=$tfConsulta!==null?(string)$tfConsulta['ano']:''?>" min="-9999" max="2100" placeholder="Ex.: 1969" required></label>
  <button class="btn btn-dark" type="submit">Calcular do ano →</button></div>
 </form>
 <form class="tf-form" method="get" action="index.php">
  <input type="hidden" name="page" value="temporis">
  <input type="hidden" name="fonte" value="<?=h($tfFontePedida)?>">
  <input type="hidden" name="coorte" value="<?=h($tfCoorte)?>">
  <input type="hidden" name="peso" value="<?=h($tfPeso)?>">
  <input type="hidden" name="escala" value="<?=h($tfEscala)?>">
  <input type="hidden" name="base" value="<?=$tfBase?>">
  <input type="hidden" name="corte" value="<?=$tfCorte?>">
  <div class="tf-form-main"><label>Avanço · de<input type="number" name="ano_a" value="<?=$tfAvanco!==null?(string)$tfAvanco['a']:''?>" min="-9999" max="2100" placeholder="1950" required></label>
  <label>até<input type="number" name="ano_b" value="<?=$tfAvanco!==null?(string)$tfAvanco['b']:''?>" min="-9999" max="2100" placeholder="2026" required></label>
  <button class="btn btn-dark" type="submit">Calcular avanço →</button></div>
 </form>
 <?php if($tfConsultaErro):?><div class="mat-final-warning" role="alert"><?=h($tfConsultaErro)?></div><?php endif; ?>
 <?php if($tfConsulta): ?>
 <div class="exp-boxes">
  <div class="exp-box">Têmporis de <?=$tfConsulta['ano']?><strong><?=$tfNumber($tfConsulta['t'])?></strong></div>
  <div class="exp-box">K(<?=$tfConsulta['ano']?>) acumulado<strong><?=$tfNumber($tfConsulta['k'],3)?></strong></div>
  <div class="exp-box">Marcos até <?=$tfConsulta['ano']?><strong><?=$tfConsulta['marcos']?></strong></div>
 </div>
 <p class="mat-final-disclaimer"><?= $tfEscala==='bruta' ? 'K(t) é a soma ponderada até a data, independente de 1900.' : 'O valor relativo foi obtido a partir de K(t) e da referência '.$tfBase.'; o cálculo bruto permanece inalterado.' ?><?php if($tfConsulta['t']===null):?> · <strong>indefinido nesta seleção</strong>: K(<?=$tfBase?>) é zero, então o índice não pode ser calculado<?php endif; ?>.</p>
 <?php endif; ?>
 <?php if($tfAvanco): ?>
 <div class="exp-boxes">
  <div class="exp-box">Têmporis de <?=$tfAvanco['a']?><strong><?=$tfNumber($tfAvanco['ta'])?></strong></div>
  <div class="exp-box">Têmporis de <?=$tfAvanco['b']?><strong><?=$tfNumber($tfAvanco['tb'])?></strong></div>
  <div class="exp-box">Avanço · T(<?=$tfAvanco['b']?>) − T(<?=$tfAvanco['a']?>)<strong><?=$tfNumber($tfAvanco['dt'])?></strong></div>
  <div class="exp-box">ΔK no período<strong><?=$tfNumber($tfAvanco['dk'],3)?></strong></div>
 </div>
 <p class="mat-final-disclaimer">Entre <?=$tfAvanco['a']?> e <?=$tfAvanco['b']?> entraram <strong><?=$tfAvanco['marcos']?> marcos</strong> (Σ pesos = <?=$tfNumber($tfAvanco['dk'],3)?>), ritmo de <?=$tfNumber($tfAvanco['taxa'],4)?> pontos de K por ano em <?=$tfAvanco['anos']?> anos. O avanço em Têmporis é <strong><?=$tfNumber($tfAvanco['dt'])?> pontos</strong><?php if($tfAvanco['ta']===null||$tfAvanco['tb']===null):?> · indefinido nesta seleção (K do ano-base é zero)<?php endif; ?>.</p>
 <?php endif; ?>
</section>
<section class="paper-panel tf-chart-main" aria-labelledby="tf-curva-titulo">
 <div class="panel-head"><span>01 / RESULTADO OBSERVADO</span><span>APENAS O MÉTODO SELECIONADO</span></div>
 <h2 id="tf-curva-titulo">A curva do Têmporis</h2>
 <p>Esta é a trajetória da <strong>formulação selecionada</strong>, com os anos na distância cronológica correta. O resultado principal fica sozinho para facilitar a leitura.</p>
 <?php $tfColors=['bruta'=>'#135d50','empirica'=>'#31917a','log'=>'#7d8290','pot065'=>'#b47953','pot125'=>'#715799'];$tfPlots=[];
 foreach(TemporisFinal::ESCALAS as $key=>$rotulo){$d=[];foreach($tfHistorico as $point)if(in_array($point['ano'],[1700,1800,1900,1950,2000,2026],true))$d[$point['ano']]=$point[$key];$tfPlots[$key]=['name'=>$rotulo,'data'=>$d,'color'=>$tfColors[$key],'principal'=>$key===$tfEscala];}
 ?>
 <div class="mat-final-chart"><?=$tfSvg([$tfPlots[$tfEscala]],[1700,1800,1900,2000,2026],320)?></div>
 <div class="tf-milestones"><?php foreach([1900,1950,2000,2026] as $yr): $row=null;foreach($tfHistorico as $item){if($item['ano']===$yr){$row=$item;break;}} ?><div><span><?=$yr?></span><strong><?=$tfNumber($row[$tfEscala]??null)?></strong></div><?php endforeach;?></div>
</section>
<details class="tf-expander"><summary><span><strong>Mostrar mais · outras fórmulas e escalas</strong><small>K(t) é a medida principal; base 1900 e compressões são comparações opcionais</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body"><h2>As fórmulas sobre os mesmos dados</h2>
 <p>Usamos exatamente os mesmos marcos e pesos para visualizar as diferenças entre as transformações. A versão Legada aqui é a <strong>adaptação matemática às rubricas atuais</strong>, não uma reprodução do acervo legado original.</p>
 <div class="mat-final-chart"><?=$tfSvg(array_values($tfPlots),[1700,1800,1900,2000,2026],320)?></div>
 <div class="mat-final-legend"><?php foreach(TemporisFinal::ESCALAS as $key=>$desc):?><span><i style="background:<?=h($tfColors[$key])?>"></i><?=h($desc)?></span><?php endforeach;?></div>
 <div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Escala</th><th>1900</th><th>1950</th><th>2000</th><th>2026</th></tr></thead><tbody><?php foreach(TemporisFinal::ESCALAS as $scale=>$desc):?><tr class="<?=$scale===$tfEscala?'mat-final-adotada':''?>"><th><?=h($desc)?></th><?php foreach([1900,1950,2000,2026] as $yr):?><td><?=$tfNumber(TemporisFinal::indice(TemporisFinal::k($tfLinha,$yr),$tfKbase,$scale))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div>
 <a href="<?=mat_url('metodo')?>">Abrir o Caderno de Fórmulas →</a></div>
</details>
<?php require_once MAT_ROOT.'/app/TemporisMetodologia2026.php';
$tfPassado=TemporisMetodologia2026::historico($tfLinha);
$tfV25=TemporisMetodologia2026::velocidade($tfLinha,2026,25);
$tfV50=TemporisMetodologia2026::velocidade($tfLinha,2026,50);
?>
<section class="paper-panel mat-final-section" aria-label="Índice histórico bruto a cada cinquenta anos">
<div class="panel-head"><span>EVOLUÇÃO HISTÓRICA · VALORES OBSERVADOS NO CADASTRO</span><span>SEM NORMALIZAÇÃO · SEM PROJEÇÃO</span></div>
<h2>K(t) de 50 em 50 anos — 1700 a 2026</h2>
<p>Esta tabela soma diretamente as notas dos marcos cadastrados até cada ano. O último período tem <strong>26 anos</strong>, não 50; não compare os incrementos sem considerar a duração.</p>
<div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Ano</th><th>Índice bruto K(t)</th><th>Δ pontos no período</th><th>Tempo transcorrido</th><th>Velocidade (pontos/ano)</th></tr></thead><tbody>
<?php foreach($tfPassado as $r):?><tr><td><?=$r['ano']?></td><td><?=$tfNumber($r['k'])?></td><td><?=$tfNumber($r['delta'])?></td><td><?=$r['duracao']===null?'—':$r['duracao'].' anos'?></td><td><?=$tfNumber($r['pontos_por_ano'],3)?></td></tr><?php endforeach;?></tbody></table></div>
<p><strong>Velocidade em 2026:</strong> média dos últimos 25 anos = <?=$tfNumber($tfV25,3)?> ponto(s)/ano; média dos últimos 50 anos = <?=$tfNumber($tfV50,3)?> ponto(s)/ano.</p>
<p class="mat-final-disclaimer">Valores condicionados à fonte e à coorte escolhidas. Ampliar o acervo para 2.000 marcos pode alterar valores históricos sem que a tecnologia do passado tenha mudado. Auditar datas, duplicatas e cobertura recente antes de concluir desaceleração ou aceleração.</p>
</section>
<section class="paper-panel mat-final-section"><div class="panel-head"><span>02 / RITMO HISTÓRICO</span><span>INCREMENTO OBSERVADO · SEM PREVISÕES</span></div>
<h2>Quanto peso novo foi registrado por ano?</h2><p>A barra mede a soma dos pesos em cada período, dividida pela sua duração; não é uma afirmação de causa ou aceleração comprovada.</p>
<div class="mat-final-bars"><?php $tfMaxRate=max(.00001,...array_column($tfPeriodos,'taxa'));foreach($tfPeriodos as $part):?>
<div class="mat-final-barrow"><span><?=$part['de']?>–<?=$part['ate']-1?></span><div class="mat-final-track"><span style="width:<?=number_format(100*$part['taxa']/$tfMaxRate,2,'.','')?>%"></span></div><strong><?=$tfNumber($part['taxa'],4)?></strong><small><?=$part['quantidade']?> marcos</small></div><?php endforeach;?></div>
<details class="tf-inline-details"><summary>Mostrar dados completos dos períodos</summary><div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Intervalo</th><th>Marcos</th><th>Σ pesos</th><th>Peso/ano</th><th>Notas provisórias</th><th>Datas extraídas</th></tr></thead><tbody><?php foreach($tfPeriodos as $part):?><tr><td><?=$part['de']?>–<?=$part['ate']-1?></td><td><?=$part['quantidade']?></td><td><?=$tfNumber($part['incremento'],3)?></td><td><?=$tfNumber($part['taxa'],4)?></td><td><?=$part['provisorios']?></td><td><?=$part['datas_extraidas']?></td></tr><?php endforeach;?></tbody></table></div></details>
</section>
<details class="tf-expander"><summary><span><strong>Mostrar mais · modelos de previsão concorrentes</strong><small>Linear, exponencial e alternativas · comparar WAPE antes de escolher</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
<div class="tf-expand-body"><h2>O que os outros modelos projetam?</h2>
<p><strong>Nenhum modelo foi declarado vencedor.</strong> O MAT calcula primeiro K(t), depois extrapola somente usando valores históricos anteriores ao corte. O WAPE deve ser consultado por horizonte, coorte e número de testes, sem usar o ano-base nas previsões.</p>
<?php
$tfKcut=TemporisFinal::k($tfLinha,$tfCorte);
$tfKold50=TemporisFinal::k($tfLinha,$tfCorte-50);
$tfG=($tfKcut>0 && $tfKold50>0)?(log($tfKcut/$tfKold50)/50.0):null;
$tfCutIndex=TemporisFinal::indice($tfKcut,$tfKbase,$tfEscala); ?>
<?php if($tfG!==null):?><div class="tf-bt-data-line" role="note"><span><strong>Taxa exponencial estimada:</strong> g = <?=number_format($tfG*100,3,',','.')?>% ao ano (últimos 50 anos, dados reais)</span></div><?php endif; ?>
<div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Modelo</th><th>Índice no corte (<?=$tfCorte?>)</th><th>Projeção +25 anos (<?=$tfCorte+25?>)</th><th>Incremento previsto ΔK</th><th>WAPE histórico</th><th>Testes</th></tr></thead><tbody>
<?php foreach(TemporisFinal::MODELOS as $id=>$label):$v=TemporisFinal::projetar($tfLinha,$tfCorte,25,$id);$t=$v===null?null:TemporisFinal::indice($v,$tfKbase,$tfEscala);$stat=TemporisFinal::validar($tfLinha,$id,25,2026);?><tr><th><?=h($label)?></th><td><?=$tfNumber($tfCutIndex)?></td><td><?=$tfNumber($t)?></td><td><?=$v===null?'—':$tfNumber($v-$tfKcut,3)?></td><td><?=$stat['wape']===null?'—':$tfNumber($stat['wape'],1).'%'?></td><td><?=$stat['n']?></td></tr><?php endforeach;?></tbody></table></div>
<p class="mat-final-disclaimer">“—” indica que não há dados suficientes para ajustar o modelo. Nenhuma projeção é a resposta obrigatória; o erro percentual é retrospectivo, não uma garantia de erro futuro. Diferentes linhas não são novas fórmulas do Índice Têmporis: são hipóteses de extrapolação do mesmo K. Consulte o backtesting antes de interpretar as previsões.</p>
<a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte,'horizonte'=>25])?>">Abrir testes retrospectivos e erros por corte →</a></div></details>
<div class="tf-next-cards"><a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte])?>"><span>03 / VALIDAÇÃO</span><strong>Backtesting separado</strong><p>Ver erros históricos, amostras válidas e modelos de previsão (linear, exponencial, logístico e outros).</p><b>Comparar previsões →</b></a><a href="<?=mat_url('metodo')?>"><span>04 / DOCUMENTAÇÃO</span><strong>Caderno de Fórmulas</strong><p>As seis equações essenciais com as diferenças entre Legada, Reformulada e Final.</p><b>Consultar metodologia →</b></a></div>
<p class="tf-final-note">Pesquisa exploratória. Os resultados brutos dependem da seleção, da datação dos marcos e das notas; o ano-base altera apenas as versões relativas, que ainda podem exigir auditoria independente. Nenhuma nota é alterada por esta página.</p>
<?php endif; ?>
