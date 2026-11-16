<?php
/** Previsões do MAT: contrato Têmporis bruto / validação temporal. */
require_once MAT_ROOT.'/app/TemporisMetodologia2026.php';
$pmPick=static function(string $name,string $default,array $valid):string {
    $v=(string)($_GET[$name]??$default);
    return isset($valid[$v])?$v:$default;
};
$pmFonte=$pmPick('fonte','auto',TemporisFinal::FONTES);
$pmCoorte=$pmPick('coorte','ampla',TemporisFinal::COORTES);
$pmModelo=$pmPick('modelo','linear50',TemporisFinal::MODELOS);
$pmCorte=filter_var($_GET['corte']??2026,FILTER_VALIDATE_INT);
$pmCorte=$pmCorte===false?2026:max(1700,min(2026,$pmCorte));
$pmHorizonte=(int)($_GET['horizonte']??25);
if(!in_array($pmHorizonte,TemporisMetodologia2026::HORIZONTES,true))$pmHorizonte=25;
$pmError=null;$pmRows=[];$pmComparacao=[];$pmFocus=[];$pmHistoric=[];$pmAudit=[];
$pmInfo=null;$pmLine=[];$pmQuality=[];$pmV25=null;$pmV50=null;
$pmFmt=static fn($n,int $dec=2):string=>$n===null?'—':number_format((float)$n,$dec,',','.');
try {
    $pmInfo=TemporisFinal::obterDados($pmFonte,2026);
    $pmPts=TemporisFinal::filtrar($pmInfo['dados']['pontos'],$pmCoorte);
    $pmLine=TemporisFinal::linha($pmPts,'quadratica');
    $pmAudit=$pmInfo['dados']['audit'];
    $pmHistoric=TemporisMetodologia2026::historico($pmLine);
    $pmV25=TemporisMetodologia2026::velocidade($pmLine,$pmCorte,25);
    $pmV50=TemporisMetodologia2026::velocidade($pmLine,$pmCorte,50);
    $pmComparacao=TemporisMetodologia2026::compararMesmosCortes($pmLine,$pmHorizonte,2026);
    foreach(TemporisFinal::MODELOS as $id=>$label) {
        $pmPred=TemporisMetodologia2026::confronto($pmLine,$pmCorte,$pmHorizonte,$id,2026);
        $pmStats=TemporisMetodologia2026::validacao($pmLine,$id,$pmHorizonte,2026);
        $pmRows[$id]=['label'=>$label,'pred'=>$pmPred,'stats'=>$pmStats];
    }
    $pmFocus=$pmRows[$pmModelo];
    $pmQuality=['total'=>count($pmPts),
        'sem_evidencia'=>count(array_filter($pmPts,static fn($p)=>$p['sem_evidencia'])),
        'datas_extraidas'=>count(array_filter($pmPts,static fn($p)=>$p['ano_extraido'])),
        'provisorias'=>count(array_filter($pmPts,static fn($p)=>$p['provisorio'])),
        'aprovadas'=>count(array_filter($pmPts,static fn($p)=>$p['aprovado']))];
} catch(Throwable $e) {$pmError=$e->getMessage();}
?>
<style>
.mat-met-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin:16px 0}
.mat-met-steps>div{background:#f9f7f0;border:1px solid #dfdacd;border-radius:11px;padding:14px}
.mat-met-steps b{display:block;margin:0 0 6px;color:#184d40}.mat-met-steps p{margin:0;font-size:.92rem}
.mat-met-highlight{border:1px solid #8ea99d;background:#eff5ee;padding:15px;border-radius:12px;margin:16px 0}
.mat-met-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:15px 0}
.mat-met-grid div{border:1px solid #dedbd2;border-radius:11px;background:#fffdfa;padding:13px;min-width:0}
.mat-met-grid small{display:block;color:#607069;line-height:1.3}.mat-met-grid strong{font-size:1.45rem;color:#174c40;display:block;margin-top:5px}
.mat-met-scroll{overflow-x:auto;width:100%}.mat-met-table{width:100%;border-collapse:collapse;min-width:680px;font-size:.92rem}
.mat-met-table th,.mat-met-table td{border-bottom:1px solid #e4e1d7;padding:10px 8px;text-align:left;vertical-align:top}
.mat-met-table th{color:#385b50;white-space:nowrap}.mat-met-table tr.is-current{background:#ecf4ee}
.mat-met-warning{padding:13px;border:1px solid #dbc8a4;background:#fdf9ef;border-radius:10px;margin:14px 0}
.mat-met-tag{display:inline-block;font-size:.78rem;color:#475e55;border:1px solid #cadbd2;border-radius:16px;padding:3px 10px}
.mat-met-filter{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:15px 0}
.mat-met-filter label{display:flex;flex-direction:column;gap:4px;min-width:140px;flex:1}
.mat-met-filter select,.mat-met-filter input{padding:9px 10px;width:100%;border:1px solid #c8d3c9;border-radius:7px;background:white}
@media(max-width:720px){.mat-met-steps{grid-template-columns:1fr}.mat-met-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<header class="page-heading tf-heading">
 <div class="section-overline">MAT / ÍNDICE TÊMPORIS / PROJEÇÕES REPRODUZÍVEIS</div>
 <h1>Prever usando <em>o histórico bruto.</em></h1>
 <p>Primeiro calculamos K(t) pelos marcos; em seguida, medimos sua velocidade; por último, cada modelo prevê sem enxergar os anos posteriores ao corte. A escala 1900 = 100 não participa do ajuste.</p>
</header>
<div class="mat-met-steps"><div><b>01 / Medir</b><p>K(t) = soma dos pesos documentados até cada ano. Sem ano-base.</p></div><div><b>02 / Analisar</b><p>ΔK por período e V25/V50 em pontos por ano.</p></div><div><b>03 / Validar</b><p>Previsão às cegas por corte; comparar incrementos com WAPE.</p></div></div>
<form class="mat-met-filter" method="get" action="index.php">
<input type="hidden" name="page" value="projecoes">
<label>Fonte<select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$label):?><option value="<?=h($id)?>" <?=$id===$pmFonte?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label>
<label>Conjunto de marcos<select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$label):?><option value="<?=h($id)?>" <?=$id===$pmCoorte?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label>
<label>Corte histórico<input name="corte" type="number" min="1700" max="2026" value="<?=$pmCorte?>"></label>
<label>Prever<select name="horizonte"><?php foreach(TemporisMetodologia2026::HORIZONTES as $h):?><option value="<?=$h?>" <?=$h===$pmHorizonte?'selected':''?>><?=$h?> anos</option><?php endforeach;?></select></label>
<label>Modelo visualizado<select name="modelo"><?php foreach(TemporisFinal::MODELOS as $id=>$label):?><option value="<?=h($id)?>" <?=$pmModelo===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select></label>
<button type="submit" class="btn btn-dark">Calcular com esses dados →</button>
</form>
<?php if($pmError): ?>
<div class="mat-met-warning"><strong>Não foi possível carregar a fonte solicitada.</strong> <?=h($pmError)?>. <a href="<?=mat_url('projecoes',['fonte'=>'snapshot'])?>">Abrir somente o snapshot histórico (base menor)</a>.</div>
<?php else: ?>
<div class="mat-met-highlight"><strong>Origem efetiva:</strong> <?=h($pmInfo['fonte']==='snapshot'?'Snapshot fixo de 07/11/2026 — NÃO é o banco atual':$pmInfo['database'])?> · <strong><?=$pmQuality['total']?></strong> marcos na coorte. <?php if($pmInfo['fonte']==='snapshot'):?><span>Inicie o MySQL para usar o acervo mais recente; nunca compare o snapshot como se fosse a base atual.</span><?php endif;?></div>
<section class="paper-panel mat-final-section">
<div class="panel-head"><span>01 / MEDIÇÃO HISTÓRICA</span><span>ACUMULADO BRUTO</span></div><h2>Têmporis registrado de 50 em 50 anos</h2>
<div class="mat-met-scroll"><table class="mat-met-table"><thead><tr><th>Ano</th><th>K(t) bruto</th><th>Δ K no período</th><th>Anos no período</th><th>Velocidade</th></tr></thead><tbody>
<?php foreach($pmHistoric as $row):?><tr><td><?=$row['ano']?></td><td><?=$pmFmt($row['k'])?></td><td><?=$pmFmt($row['delta'])?></td><td><?=$row['duracao']??'—'?></td><td><?=$pmFmt($row['pontos_por_ano'],3)?> ponto(s)/ano</td></tr><?php endforeach;?></tbody></table></div>
<p>O intervalo 2000–2026 tem 26 anos. Para comparar ritmos de períodos com durações distintas, usamos a coluna <strong>velocidade</strong>, não somente o crescimento acumulado.</p>
</section>
<div class="mat-met-grid">
<div><small>Índice bruto no corte <?=$pmCorte?></small><strong><?=$pmFmt(TemporisFinal::k($pmLine,$pmCorte))?></strong></div>
<div><small>Ritmo médio · últimos 25 anos</small><strong><?=$pmFmt($pmV25,3)?></strong><small>pontos/ano</small></div>
<div><small>Ritmo médio · últimos 50 anos</small><strong><?=$pmFmt($pmV50,3)?></strong><small>pontos/ano</small></div>
<div><small>Marcos aprovados na seleção</small><strong><?=$pmQuality['aprovadas']?> / <?=$pmQuality['total']?></strong><small>Aprovado no sistema ≠ validação externa</small></div>
</div>
<section class="paper-panel mat-final-section"><div class="panel-head"><span>02 / TESTE DE PREVISÃO</span><span>COMPARAÇÃO DO ANO-ALVO <?=$pmCorte+$pmHorizonte?></span></div>
<h2>Treinar até <?=$pmCorte?> e tentar prever <?=$pmCorte+$pmHorizonte?></h2>
<p>O modelo selecionado usa apenas <strong>K(t) até <?=$pmCorte?></strong> para estimar o futuro. Se o alvo já está documentado no acervo, comparamos com o valor registrado. Caso contrário, <strong>não há erro futuro observável</strong>.</p>
<?php $focus=$pmFocus['pred']; $focusStats=$pmFocus['stats']; ?>
<div class="mat-met-grid">
<div><small>Modelo em foco</small><strong style="font-size:1.05rem"><?=h($pmFocus['label'])?></strong></div>
<div><small>Previsão para <?=$focus['alvo']?></small><strong><?=$pmFmt($focus['previsto'])?></strong><small>pontos brutos</small></div>
<div><small><?= $focus['status']==='futuro_sem_erro'?'Sem observado futuro':'Observado no catálogo' ?></small><strong><?=$pmFmt($focus['observado'])?></strong></div>
<div><small>Erro do crescimento no teste</small><strong><?=$focus['erro_pct_delta']===null?'—':$pmFmt($focus['erro_pct_delta'],1).'%'?></strong><small><?=$focus['status']==='futuro_sem_erro'?'Não calculável antes do alvo':'Erro deste corte específico'?></small></div>
</div>
<?php
$pmGraph = static function(array $line,int $cut,int $target,?float $forecast,int $lastYear=2026):string {
    if($forecast===null)return '<p>Gráfico indisponível: este modelo não pôde ser ajustado com os marcos anteriores ao corte.</p>';
    $start=max(0,$cut-150);$end=max($target,$cut+1);
    $base=[];
    for($t=$start;$t<=$cut;$t+=10)$base[]=['year'=>$t,'k'=>TemporisFinal::k($line,$t)];
    if(!$base || end($base)['year']!==$cut)$base[]=['year'=>$cut,'k'=>TemporisFinal::k($line,$cut)];
    $observed=[['year'=>$cut,'k'=>TemporisFinal::k($line,$cut)]];
    for($t=$cut+10;$t<=min($target,$lastYear);$t+=10)$observed[]=['year'=>$t,'k'=>TemporisFinal::k($line,$t)];
    if($target<=$lastYear && end($observed)['year']!==$target)$observed[]=['year'=>$target,'k'=>TemporisFinal::k($line,$target)];
    if($target>$lastYear && $lastYear>$cut && end($observed)['year']!==$lastYear)$observed[]=['year'=>$lastYear,'k'=>TemporisFinal::k($line,$lastYear)];
    $max=max(1.0, $forecast, ...array_column($base,'k'), ...array_column($observed,'k'))*1.13;
    $W=1000;$H=330;$L=72;$R=967;$T=25;$B=271;
    $x=static fn(int $y):float=>$L+($y-$start)/($end-$start)*($R-$L);
    $y=static fn(float $v):float=>$B-$v/$max*($B-$T);
    $n=static fn(float $v):string=>number_format($v,2,'.','');
    $path=static function(array $points)use($x,$y,$n):string {
        $res=[];foreach($points as $i=>$p)$res[]=($i===0?'M':'L').$n($x((int)$p['year'])).' '.$n($y((float)$p['k']));
        return implode(' ',$res);
    };
    $out='<svg viewBox="0 0 '.$W.' '.$H.'" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Índice bruto histórico até o corte e extrapolação do modelo selecionado">';
    $out.='<rect width="'.$W.'" height="'.$H.'" fill="#fcfbf7" rx="10"/>';
    for($i=0;$i<=4;$i++){
        $v=$max*$i/4;$yy=$n($y($v));
        $out.='<line x1="'.$L.'" x2="'.$R.'" y1="'.$yy.'" y2="'.$yy.'" stroke="#dedbd0" stroke-dasharray="4 5"/>';
        $out.='<text x="64" y="'.$n($y($v)+4).'" text-anchor="end" fill="#536660" font-size="12">'.number_format($v,0,',','.').'</text>';
    }
    foreach([$start,$cut,$end] as $yr){$out.='<text x="'.$n($x($yr)).'" y="294" text-anchor="middle" fill="#536660" font-size="13">'.$yr.'</text>';}
    $out.='<path d="'.$path($base).'" fill="none" stroke="#166c58" stroke-width="3.4"/>';
    if(count($observed)>1)$out.='<path d="'.$path($observed).'" fill="none" stroke="#23977a" stroke-width="3.4"/>';
    $forecastPath=$path([['year'=>$cut,'k'=>TemporisFinal::k($line,$cut)],['year'=>$target,'k'=>$forecast]]);
    $out.='<path d="'.$forecastPath.'" fill="none" stroke="#b57d51" stroke-width="3" stroke-dasharray="9 6"/>';
    $out.='<line x1="'.$n($x($cut)).'" x2="'.$n($x($cut)).'" y1="'.$T.'" y2="'.$B.'" stroke="#9b765e" stroke-dasharray="5 5"/>';
    $out.='<circle cx="'.$n($x($target)).'" cy="'.$n($y($forecast)).'" r="5.5" fill="#b57d51"><title>Projeção K('.$target.') = '.number_format($forecast,2,',','.').'</title></circle>';
    $out.='<text x="75" y="321" fill="#166c58" font-size="13">Histórico observado no catálogo (verde)</text>';
    $out.='<text x="570" y="321" fill="#9a6344" font-size="13">Extrapolação (tracejado)</text></svg>';
    return $out;
};
?>
<div style="border:1px solid #e1ddd4;border-radius:12px;padding:12px;margin:16px 0;background:#fcfbf7">
<?=$pmGraph($pmLine,$pmCorte,$focus['alvo'],$focus['previsto'])?>
<p style="margin:4px 0;color:#52655e;font-size:.86rem">Gráfico em anos cronológicos reais. A linha tracejada é somente a hipótese do modelo selecionado; não significa trajetória histórica confirmada.</p>
</div>
<?php if($focus['status']==='dados_insuficientes'):?><p class="mat-met-warning">Os dados anteriores ao corte não são suficientes para ajustar este método. Não substituímos por outra fórmula silenciosamente.</p><?php endif;?>
<div class="mat-met-scroll"><table class="mat-met-table"><thead><tr><th>Método</th><th>K previsto</th><th>ΔK previsto</th><th>Erro neste corte</th><th>WAPE histórico*</th><th>Testes</th></tr></thead><tbody>
<?php foreach($pmRows as $id=>$row): $pred=$row['pred'];$stat=$row['stats'];?><tr class="<?=$id===$pmModelo?'is-current':''?>"><td><strong><?=h($row['label'])?></strong><?=$id===$pmModelo?' (selecionado)':''?></td><td><?=$pmFmt($pred['previsto'])?></td><td><?=$pmFmt($pred['delta_previsto'])?></td><td><?=$pred['erro_pct_delta']===null?'—':$pmFmt($pred['erro_pct_delta'],1).'%'?></td><td><?=$stat['wape']===null?'—':$pmFmt($stat['wape'],1).'%'?></td><td><?=$stat['n']?></td></tr><?php endforeach;?></tbody></table></div>
<p><small>* O WAPE é o erro absoluto total dos <strong>incrementos</strong> dividido pelo crescimento real total em cortes históricos de 25 em 25 anos, nunca uma chance de acerto nem uma margem de confiança futura. Alguns métodos possuem números diferentes de cortes válidos; compare o ranking justo abaixo.</small></p>
</section>
<section class="paper-panel mat-final-section"><div class="panel-head"><span>03 / AVALIAÇÃO COM AS MESMAS AMOSTRAS</span><span>SEM ESCOLHER UMA EXPONENCIAL DE ANTEMÃO</span></div>
<h2>Linear e exponencial nas mesmas condições</h2>
<p>Os quatro modelos abaixo disputam <strong>os mesmos <?=$pmComparacao['n']?> cortes válidos</strong>, evitando vantagem por testar um método apenas nos períodos mais fáceis. Os horizontes são fixos, e nenhum dado posterior a cada corte é usado para estimar a previsão daquele corte.</p>
<div class="mat-met-scroll"><table class="mat-met-table"><thead><tr><th>Método</th><th>WAPE dos incrementos</th><th>MAE em pontos</th><th>Cortes iguais</th></tr></thead><tbody>
<?php foreach($pmComparacao['modelos'] as $id=>$row):?><tr class="<?=$id===$pmModelo?'is-current':''?>"><td><?=h($row['nome'])?></td><td><?=$row['wape']===null?'—':$pmFmt($row['wape'],1).'%'?></td><td><?=$pmFmt($row['mae'])?></td><td><?=$row['n']?></td></tr><?php endforeach;?></tbody></table></div>
<details><summary>Conferir os cortes e previsões, um por um</summary><div class="mat-met-scroll"><table class="mat-met-table"><thead><tr><th>Modelo</th><th>Corte → alvo</th><th>ΔK previsto</th><th>ΔK registrado</th><th>Erro absoluto</th></tr></thead><tbody><?php foreach($pmComparacao['modelos'] as $id=>$row)foreach($row['folds'] as $fold):?><tr><td><?=h($row['nome'])?></td><td><?=$fold['corte']?> → <?=$fold['alvo']?></td><td><?=$pmFmt($fold['crescimento_previsto'])?></td><td><?=$pmFmt($fold['crescimento_real'])?></td><td><?=$pmFmt(abs($fold['erro']))?></td></tr><?php endforeach;?></tbody></table></div></details>
<div class="mat-met-warning"><strong>Limite científico:</strong> o ranking é escolhido depois de olhar esses testes, então não é uma validação externa independente. A própria catalogação histórica pode usar conhecimento de acontecimentos posteriores (viés retrospectivo). Os erros históricos não são limites garantidos para 2036, 2051, 2076 ou 2126.</div>
</section>
<section class="paper-panel mat-final-section"><div class="panel-head"><span>04 / QUALIDADE DO CADASTRO</span><span>ALERTA DE INTERPRETAÇÃO</span></div>
<h2>O que muda quando chegarmos a 2.000 marcos?</h2>
<p>O MAT recalculará todo K(t) segundo as datas dos novos marcos: números antigos podem aumentar porque o catálogo ficou mais completo, não porque o passado mudou. Por isso, sempre identificar a fonte e congelar uma versão do banco para reproduzir um teste.</p>
<p>Nesta coorte: <?=$pmQuality['datas_extraidas']?> datas ainda extraídas/não validadas, <?=$pmQuality['provisorias']?> notas provisórias e <?=$pmQuality['sem_evidencia']?> casos marcados com insuficiência de evidência. Não são marcos cientificamente validados por aparecerem na curva.</p>
<p><a href="<?=mat_url('backtesting',['fonte'=>$pmFonte,'coorte'=>$pmCoorte,'horizonte'=>$pmHorizonte])?>">Abrir backtesting aprofundado dos 11 modelos →</a> · <a href="<?=mat_url('temporis',['fonte'=>$pmFonte,'coorte'=>$pmCoorte])?>">Voltar para o índice histórico →</a> · <a href="<?=mat_url('metodo')?>">Ver fórmulas e premissas →</a></p>
</section>
<?php endif; ?>
