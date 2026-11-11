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
$tfEscala=$tfGet('escala','empirica',TemporisFinal::ESCALAS);
$tfBase=filter_var($_GET['base']??1900,FILTER_VALIDATE_INT);
$tfBase=$tfBase===false?1900:max(1600,min(2000,$tfBase));
$tfCorte=filter_var($_GET['corte']??2026,FILTER_VALIDATE_INT);
$tfCorte=$tfCorte===false?2026:max(1700,min(2026,$tfCorte));
$tfErro=null;$tfFonte='desconhecida';$tfBanco='—';$tfDados=null;
$tfTodos=[];$tfPontos=[];$tfLinha=[];$tfHistorico=[];$tfPeriodos=[];$tfProjecoes=[];$tfIndex=null;
$tfNumber=static fn(?float $v,int $digits=2):string=>$v===null?'—':number_format($v,$digits,',','.');
try {
    $resultado=TemporisFinal::obterDados($tfFontePedida,2026);
    $tfDados=$resultado['dados'];$tfFonte=$resultado['fonte'];$tfBanco=$resultado['database'];
    $tfTodos=$tfDados['pontos'];$tfPontos=TemporisFinal::filtrar($tfTodos,$tfCoorte);
    $tfLinha=TemporisFinal::linha($tfPontos,$tfPeso);
    $tfKbase=TemporisFinal::k($tfLinha,$tfBase);
    $tfK2026=TemporisFinal::k($tfLinha,2026);
    $tfIndex=TemporisFinal::indice($tfK2026,$tfKbase,$tfEscala);
    $tfAuditoria=$tfDados['audit'];
    $tfValidos=count(array_filter($tfPontos,static fn($p)=>$p['ano_validado']));
    $tfSem=count(array_filter($tfPontos,static fn($p)=>$p['sem_evidencia']));
    $tfProv=count(array_filter($tfPontos,static fn($p)=>$p['provisorio']));
    $tfExtra=count(array_filter($tfPontos,static fn($p)=>$p['ano_extraido']));
    foreach([1800,1850,1900,1925,1950,1975,2000,2010,2016,2020,2026] as $year){
        $k=TemporisFinal::k($tfLinha,$year);
        $row=['ano'=>$year,'k'=>$k];
        foreach(TemporisFinal::ESCALAS as $escala=>$rotulo)$row[$escala]=TemporisFinal::indice($k,$tfKbase,$escala);
        $tfHistorico[]=$row;
    }
    foreach([[1800,1900],[1900,1950],[1950,2000],[2000,2027],[2016,2027]] as [$ini,$fim]) {
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
  <div class="tf-formula">T(t) = 100 × K(t) / K(<?=$tfBase?>)</div>
  <p>O peso de cada marco é <strong>w = (I/10)² × C × F</strong>, e K(t) soma os pesos dos marcos datados até o ano t. A fórmula acumula registros, não demonstra sozinha aceleração histórica.</p>
  <a href="<?=mat_url('metodo')?>">Ver as seis fórmulas no caderno →</a></div>
  <div class="tf-hero-value"><span>TÊMPORIS · 2026</span><strong><?=$tfNumber($tfIndex)?></strong><small>Ano-base <?=$tfBase?> = 100 · <?=h(TemporisFinal::ESCALAS[$tfEscala])?></small><small><?=count($tfPontos)?> registros efetivamente calculados</small></div>
</section>
<div class="tf-source-bar" role="status"><strong><?= $tfFonte==='snapshot'?'Exportação estática (não atualiza)':'MySQL conectado · leitura em tempo real' ?></strong><span>Origem: <code><?=h($tfBanco)?></code></span><span>Solicitado: <?=h(TemporisFinal::FONTES[$tfFontePedida])?></span></div>
<form class="tf-form" method="get" action="index.php">
 <input type="hidden" name="page" value="temporis">
 <div class="tf-form-main"><label>Origem dos dados<select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfFontePedida===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
 <label>Seleção de marcos<select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfCoorte===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
 <button class="btn btn-dark" type="submit">Atualizar análise →</button></div>
 <details class="tf-filter-detail" <?=($tfPeso!=='quadratica'||$tfEscala!=='empirica'||$tfBase!==1900||$tfCorte!==2026)?'open':''?>>
  <summary>Opções avançadas · pesos, escalas e ano-base</summary><div class="tf-form-advanced">
  <label>Pesos<select name="peso"><?php foreach(TemporisFinal::PESOS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfPeso===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
  <label>Transformação do índice<select name="escala"><?php foreach(TemporisFinal::ESCALAS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfEscala===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
  <label>Ano-base<input type="number" name="base" value="<?=$tfBase?>" min="1600" max="2000"></label>
  <label>Ano de corte nas previsões<input type="number" name="corte" value="<?=$tfCorte?>" min="1700" max="2026"></label>
  </div></details>
</form>
<section class="tf-audit-box" aria-label="Rastreabilidade da contagem dos marcos">
 <div class="tf-counts"><div><span>Candidatos na fonte</span><strong><?=$tfAuditoria['total']?></strong></div><div><span>Elegíveis para cálculo</span><strong><?=$tfAuditoria['elegiveis']?></strong></div><div><span>Na seleção atual</span><strong><?=count($tfPontos)?></strong></div><div><span>Não elegíveis</span><strong><?=$tfAuditoria['total']-$tfAuditoria['elegiveis']?></strong></div></div>
 <details><summary>Por que nem todos os marcos aparecem? · detalhar exclusões e qualidade dos dados</summary>
  <p>O MAT <strong>não apaga</strong> os candidatos excluídos desta curva. A soma abaixo explica os registros existentes nesta fonte, e os filtros podem reduzir ainda mais a série calculada.</p>
  <div class="tf-audit-grid"><span>Rejeitados: <b><?=$tfAuditoria['rejeitados']?></b></span><span>Sem as 11 notas: <b><?=$tfAuditoria['sem_11_notas']?></b></span><span>Sem data utilizável: <b><?=$tfAuditoria['sem_data']?></b></span><span>Posteriores a 2026: <b><?=$tfAuditoria['futuros']?></b></span><span>Datas extraídas (na seleção): <b><?=$tfExtra?></b></span><span>Notas provisórias (na seleção): <b><?=$tfProv?></b></span><span>Com SEM_EVIDENCIA: <b><?=$tfSem?></b></span><span>Datas não extraídas (na seleção): <b><?=$tfValidos?></b></span></div>
  <p>Os anos extraídos e as notas provisórias não se tornam homologados por entrarem no ensaio. Para incluir registros sem ano será necessário revisar a datação, não atribuir uma data fictícia. Consulte o <a href="<?=mat_url('andamento')?>">acompanhamento da curadoria</a> para trabalhar no conjunto completo.</p>
 </details>
</section>
<?php if($tfKbase<=0):?><div class="mat-final-warning">K(<?=$tfBase?>) = 0. O índice é matematicamente indefinido nessa seleção; altere a amostra ou o ano-base.</div><?php endif; ?>
<section class="paper-panel tf-chart-main" aria-labelledby="tf-curva-titulo">
 <div class="panel-head"><span>01 / RESULTADO OBSERVADO</span><span>APENAS O MÉTODO SELECIONADO</span></div>
 <h2 id="tf-curva-titulo">A curva do Têmporis</h2>
 <p>Esta é a trajetória da <strong>formulação selecionada</strong>, com os anos na distância cronológica correta. O resultado principal fica sozinho para facilitar a leitura.</p>
 <?php $tfColors=['empirica'=>'#135d50','log'=>'#7d8290','pot065'=>'#b47953','pot125'=>'#715799'];$tfPlots=[];
 foreach(TemporisFinal::ESCALAS as $key=>$rotulo){$d=[];foreach($tfHistorico as $point)if(in_array($point['ano'],[1800,1900,1925,1950,1975,2000,2026],true))$d[$point['ano']]=$point[$key];$tfPlots[$key]=['name'=>$rotulo,'data'=>$d,'color'=>$tfColors[$key],'principal'=>$key===$tfEscala];}
 ?>
 <div class="mat-final-chart"><?=$tfSvg([$tfPlots[$tfEscala]],[1800,1900,1950,2000,2026],320)?></div>
 <div class="tf-milestones"><?php foreach([1900,1950,2000,2026] as $yr): $row=null;foreach($tfHistorico as $item){if($item['ano']===$yr){$row=$item;break;}} ?><div><span><?=$yr?></span><strong><?=$tfNumber($row[$tfEscala]??null)?></strong></div><?php endforeach;?></div>
</section>
<details class="tf-expander"><summary><span><strong>Mostrar mais · outras fórmulas e escalas</strong><small>Compare a Legada, a Reformulada, a Final e a escala experimental de potência 1,25</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body"><h2>As fórmulas sobre os mesmos dados</h2>
 <p>Usamos exatamente os mesmos marcos e pesos para visualizar as diferenças entre as transformações. A versão Legada aqui é a <strong>adaptação matemática às rubricas atuais</strong>, não uma reprodução do acervo legado original.</p>
 <div class="mat-final-chart"><?=$tfSvg(array_values($tfPlots),[1800,1900,1950,2000,2026],320)?></div>
 <div class="mat-final-legend"><?php foreach(TemporisFinal::ESCALAS as $key=>$desc):?><span><i style="background:<?=h($tfColors[$key])?>"></i><?=h($desc)?></span><?php endforeach;?></div>
 <div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Escala</th><th>1900</th><th>1950</th><th>2000</th><th>2026</th></tr></thead><tbody><?php foreach(TemporisFinal::ESCALAS as $scale=>$desc):?><tr class="<?=$scale===$tfEscala?'mat-final-adotada':''?>"><th><?=h($desc)?></th><?php foreach([1900,1950,2000,2026] as $yr):?><td><?=$tfNumber(TemporisFinal::indice(TemporisFinal::k($tfLinha,$yr),$tfKbase,$scale))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div>
 <a href="<?=mat_url('metodo')?>">Abrir o Caderno de Fórmulas →</a></div>
</details>
<section class="paper-panel mat-final-section"><div class="panel-head"><span>02 / RITMO HISTÓRICO</span><span>INCREMENTO OBSERVADO · SEM PREVISÕES</span></div>
<h2>Quanto peso novo foi registrado por ano?</h2><p>A barra mede a soma dos pesos em cada período, dividida pela sua duração; não é uma afirmação de causa ou aceleração comprovada.</p>
<div class="mat-final-bars"><?php $tfMaxRate=max(.00001,...array_column($tfPeriodos,'taxa'));foreach($tfPeriodos as $part):?>
<div class="mat-final-barrow"><span><?=$part['de']?>–<?=$part['ate']-1?></span><div class="mat-final-track"><span style="width:<?=number_format(100*$part['taxa']/$tfMaxRate,2,'.','')?>%"></span></div><strong><?=$tfNumber($part['taxa'],4)?></strong><small><?=$part['quantidade']?> marcos</small></div><?php endforeach;?></div>
<details class="tf-inline-details"><summary>Mostrar dados completos dos períodos</summary><div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Intervalo</th><th>Marcos</th><th>Σ pesos</th><th>Peso/ano</th><th>Notas provisórias</th><th>Datas extraídas</th></tr></thead><tbody><?php foreach($tfPeriodos as $part):?><tr><td><?=$part['de']?>–<?=$part['ate']-1?></td><td><?=$part['quantidade']?></td><td><?=$tfNumber($part['incremento'],3)?></td><td><?=$tfNumber($part['taxa'],4)?></td><td><?=$part['provisorios']?></td><td><?=$part['datas_extraidas']?></td></tr><?php endforeach;?></tbody></table></div></details>
</section>
<details class="tf-expander"><summary><span><strong>Mostrar mais · modelos de previsão concorrentes</strong><small>Exponencial (principal), linear, logístico e demais cenários · horizonte de 25 anos</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
<div class="tf-expand-body"><h2>O que os outros modelos projetam?</h2>
<p><strong>Modelo principal: Exponencial</strong> — projeção <strong>T(t+h) = T(t)·e^(g·h)</strong>, com taxa estimada do histórico <strong>g = ln(K(<?=$tfCorte?>)/K(<?=$tfCorte-50?>))/50</strong> (sem constantes arbitrárias). Os demais modelos (linear, logístico e outros) permanecem disponíveis para comparação científica.</p>
<?php
$tfKcut=TemporisFinal::k($tfLinha,$tfCorte);
$tfKold50=TemporisFinal::k($tfLinha,$tfCorte-50);
$tfG=($tfKcut>0 && $tfKold50>0)?(log($tfKcut/$tfKold50)/50.0):null;
$tfCutIndex=TemporisFinal::indice($tfKcut,$tfKbase,$tfEscala); ?>
<?php if($tfG!==null):?><div class="tf-bt-data-line" role="note"><span><strong>Taxa exponencial estimada:</strong> g = <?=number_format($tfG*100,3,',','.')?>% ao ano (últimos 50 anos, dados reais)</span></div><?php endif; ?>
<div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Modelo</th><th>Índice no corte (<?=$tfCorte?>)</th><th>Projeção +25 anos (<?=$tfCorte+25?>)</th><th>Incremento previsto ΔK</th></tr></thead><tbody>
<?php foreach(TemporisFinal::MODELOS as $id=>$label):$v=TemporisFinal::projetar($tfLinha,$tfCorte,25,$id);$t=$v===null?null:TemporisFinal::indice($v,$tfKbase,$tfEscala);$isExp=($id==='exponencial50');?><tr <?=$isExp?'style="background:#eef4ee"':''?>><th><?=h($label)?><?=$isExp?' · modelo principal: Exponencial':''?></th><td><?=$tfNumber($tfCutIndex)?></td><td><?=$tfNumber($t)?></td><td><?=$v===null?'—':$tfNumber($v-$tfKcut,3)?></td></tr><?php endforeach;?></tbody></table></div>
<p class="mat-final-disclaimer">“—” indica que não há dados suficientes para ajustar o modelo. A linha destacada é a projeção principal (exponencial); superioridade comprovada depende do backtesting. Diferentes linhas não são novas fórmulas do Índice Têmporis: são hipóteses de extrapolação do mesmo K. Consulte o backtesting antes de interpretar as previsões.</p>
<a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte,'horizonte'=>25])?>">Abrir testes retrospectivos e erros por corte →</a></div></details>
<div class="tf-next-cards"><a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte])?>"><span>03 / VALIDAÇÃO</span><strong>Backtesting separado</strong><p>Ver erros históricos, amostras válidas e modelos de previsão (linear, exponencial, logístico e outros).</p><b>Comparar previsões →</b></a><a href="<?=mat_url('metodo')?>"><span>04 / DOCUMENTAÇÃO</span><strong>Caderno de Fórmulas</strong><p>As seis equações essenciais com as diferenças entre Legada, Reformulada e Final.</p><b>Consultar metodologia →</b></a></div>
<p class="tf-final-note">Pesquisa exploratória. Os resultados dependem da seleção, do ano-base, da datação dos marcos e das notas, que ainda podem exigir auditoria independente. Nenhuma nota é alterada por esta página.</p>
<?php endif; ?>
