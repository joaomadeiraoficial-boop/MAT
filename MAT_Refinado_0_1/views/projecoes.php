<?php
/** MAT-Proj - Projeções centenárias e validação retrospectiva. */
require_once MAT_ROOT.'/app/TemporisCuradoria.php';
require_once MAT_ROOT.'/app/TemporisProjecoes.php';
// Compatibilidade documental com a implementação antiga: TemporisBacktest::experiment
// foi substituído nesta página por TemporisProjecoes::avaliar, que suporta alvo futuro.
$projFormulas=TemporisProjecoes::FORMULAS;
$projModelos=TemporisProjecoes::MODELOS;
$projSeriesLabels=[
    'previa'=>'Prévia documental · data validada, 11 notas',
    'ensaio'=>'Ensaio exploratório · inclui datas extraídas',
    'homologada'=>'Somente marcos homologados',
];
$projSerie=(string)($_GET['serie']??'previa');
if(!isset($projSeriesLabels[$projSerie]))$projSerie='previa';
$projFormula=(string)($_GET['formula']??'log');
if(!isset($projFormulas[$projFormula]))$projFormula='log';
$projModelo=(string)($_GET['modelo']??'media50');
if(!isset($projModelos[$projModelo]))$projModelo='media50';
$projCorte=filter_var($_GET['corte']??1926,FILTER_VALIDATE_INT);
$projAlvo=filter_var($_GET['alvo']??2026,FILTER_VALIDATE_INT);
$projCorte=$projCorte===false?1926:max(-10000,min(3000,$projCorte));
$projAlvo=$projAlvo===false?2026:max(-9999,min(3200,$projAlvo));
$projAsOf=(int)date('Y');
$projBase=filter_var($_GET['base']??1900,FILTER_VALIDATE_INT);
$projBase=$projBase===false?1900:max(-10000,min(3000,$projBase));
$projErro=null;$projResultado=null;$projMetricas=[];$projPontos=[];$projVariacoes=[];
try{
    $projPDO=CuradoriaRodadas::connect();
    if(!$projPDO)throw new RuntimeException('O banco isolado da curadoria do MAT Final não está disponível.');
    $projDados=TemporisCuradoria::consultar($projPDO,$projCorte);
    $projPontos=$projDados[$projSerie]['series']??[];
    $projResultado=TemporisProjecoes::avaliar($projPontos,$projCorte,$projAlvo,$projFormula,$projModelo,$projAsOf,$projBase);
    if($projResultado['status']!=='insuficiente'){
        $projMetricas=TemporisProjecoes::metricasHistoricas($projPontos,$projCorte,$projAlvo-$projCorte,
            $projFormula,$projModelo,$projAsOf,20,$projBase);
        foreach($projModelos as $chave=>$nome){
            $r=($chave===$projModelo)?$projResultado:
                TemporisProjecoes::avaliar($projPontos,$projCorte,$projAlvo,$projFormula,$chave,$projAsOf,$projBase);
            $projVariacoes[$chave]=$r;
        }
    }
}catch(Throwable $e){$projErro=$e->getMessage();}
$fmtProj=static fn($n,int $casas=2):string=>$n===null?'—':number_format((float)$n,$casas,',','.');
$fmtSinal=static fn($n):string=>$n===null?'—':(((float)$n>0?'+':'').number_format((float)$n,2,',','.'));
$projAtalhos=[['titulo'=>'1900 → 2000','corte'=>1900,'alvo'=>2000],
    ['titulo'=>'1926 → 2026','corte'=>1926,'alvo'=>2026],
    ['titulo'=>'2026 → 2126','corte'=>2026,'alvo'=>2126]];
?>
<style>
/* MAT-Proj: componentes de pesquisa coerentes com a identidade visual do observatório. */
.mat-proj-lead{max-width:940px}
.mat-proj-pills{display:flex;flex-wrap:wrap;gap:9px;margin:17px 0}
.mat-proj-pill{display:inline-flex;align-items:center;gap:7px;padding:11px 15px;border:1px solid #d7c9b7;background:#f9f4eb;color:#24413b;text-decoration:none;font:600 13px/1.35 system-ui,sans-serif}
.mat-proj-pill:hover,.mat-proj-pill:focus{border-color:#b37b52;background:#eee2d0}
.mat-proj-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(178px,1fr));gap:12px;margin:18px 0 30px}
.mat-proj-metric{border:1px solid #d9ccba;background:#fffaf2;padding:17px 18px;min-height:127px}
.mat-proj-metric .tag{font:11px/1.45 monospace;letter-spacing:.08em;text-transform:uppercase;color:#75685c}
.mat-proj-metric strong{font:normal 34px/1.25 Georgia,serif;color:#123f3c;display:block;margin:8px 0 5px}
.mat-proj-metric small{font:12px/1.45 system-ui,sans-serif;color:#6c7368;display:block}
.mat-proj-metric.primary{background:#194a40;border-color:#194a40}.mat-proj-metric.primary .tag,.mat-proj-metric.primary small{color:#cfddd3}.mat-proj-metric.primary strong{color:#fff8e9}
.mat-proj-svg{width:100%;height:auto;display:block;max-height:530px}
.mat-proj-grafico{overflow-x:auto;border:1px solid #d9ccba;background:#fbf8f0;padding:12px 14px;margin:20px 0}
.mat-proj-grafico svg{min-width:690px}
.mat-proj-empty{padding:22px;color:#786d5f}
.mat-proj-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;margin:18px 0}
.mat-proj-info{padding:18px 20px;border:1px solid #ddcfba;background:#f8f3e9}
.mat-proj-info h3{margin:0 0 9px;font:normal 21px/1.3 Georgia,serif;color:#263d36}
.mat-proj-info p{margin:0;font:13px/1.7 system-ui,sans-serif;color:#52675c}
.mat-proj-warn{padding:16px 19px;border-left:4px solid #ab7758;background:#f9ead8;margin:18px 0;font:14px/1.65 system-ui,sans-serif;color:#493b2e}
.mat-proj-warn strong{font-weight:700}
.mat-proj-formula{font:normal 22px Georgia,serif;color:#1c4840;margin:10px 0 20px;text-align:center}
.mat-proj-notes{font:13px/1.75 system-ui,sans-serif;color:#52675e;max-width:1010px}
.mat-proj-section{margin-top:30px}
@media(max-width:780px){.mat-proj-grid{grid-template-columns:1fr}.mat-proj-metric strong{font-size:28px}}
</style>
<div class="mat-final-legacy-note">Módulo anterior de projeções, mantido como referência. Para explorar a cópia histórica de 7/11/2026 (até 667 candidatos elegíveis), 4 pesos e horizontes de 10/25/50/100 anos, <a href="<?=mat_url('temporis_final')?>">acesse MAT Final →</a></div>
<header class="page-heading">
  <div class="section-overline">MAT / ÍNDICE TÊMPORIS / MAT-PROJ · ANÁLISE CRONOLÓGICA</div>
  <h1>Prever o <em>tempo tecnológico.</em></h1>
  <p class="mat-proj-lead">Escolha a fórmula, feche o histórico em um ano e estime o índice décadas ou séculos depois. Se o ano de destino já passou, o MAT calcula <strong>quanto a previsão superestimou ou subestimou</strong> o valor observado no acervo.</p>
</header>
<div class="lab-alert"><span class="lab-symbol">✳</span><div><strong>PREVISÃO MATEMÁTICA ≠ MARCOS HOMOLOGADOS</strong><p>Os experimentos usam pesos obtidos das 11 rubricas atuais. “Original adaptada” é a fórmula de potência 0,65 aplicada à mesma base, não uma recuperação automática das notas históricas do acervo legado. A previsão não inventa marcos futuros nem os aprova.</p></div></div>
<div class="mat-proj-pills" aria-label="Exemplos de projeção centenária">
<?php foreach($projAtalhos as $preset):
  $qs=http_build_query(['page'=>'projecoes','serie'=>$projSerie,'formula'=>$projFormula,'modelo'=>$projModelo,'base'=>$projBase,'corte'=>$preset['corte'],'alvo'=>$preset['alvo']]); ?>
<a class="mat-proj-pill" href="?<?=h($qs)?>">◷ <?=h($preset['titulo'])?></a>
<?php endforeach; ?>
</div>
<form class="exp-form" method="get" action="index.php">
<input type="hidden" name="page" value="projecoes">
<label>Fórmula do Têmporis
<select name="formula" aria-label="Fórmula do Têmporis">
<?php foreach($projFormulas as $k=>$label):?><option value="<?=h($k)?>" <?=$k===$projFormula?'selected':''?>><?=h($label)?></option><?php endforeach; ?>
</select></label>
<label>Modelo de crescimento dos pesos
<select name="modelo">
<?php foreach($projModelos as $k=>$label):?><option value="<?=h($k)?>" <?=$k===$projModelo?'selected':''?>><?=h($label)?></option><?php endforeach; ?>
</select></label>
<label>Conjunto documental
<select name="serie">
<?php foreach($projSeriesLabels as $k=>$label):?><option value="<?=h($k)?>" <?=$k===$projSerie?'selected':''?>><?=h($label)?></option><?php endforeach; ?>
</select></label>
<label>Ano-base fixo do índice (T = 100)<input name="base" type="number" min="-10000" max="<?=$projCorte?>" value="<?=$projBase?>" required></label>
<label>Ano de corte (até onde o modelo conhece)<input name="corte" type="number" min="-10000" max="<?=$projAsOf?>" value="<?=$projCorte?>" required></label>
<label>Ano que deseja prever<input name="alvo" type="number" min="-9999" max="3200" value="<?=$projAlvo?>" required></label>
<button type="submit" class="btn btn-dark">Calcular previsão e erro →</button>
</form>
<?php if($projErro):?>
<div class="mat-proj-warn" role="alert"><strong>Não foi possível realizar a consulta:</strong> <?=h($projErro)?></div>
<?php elseif($projResultado):
    $r=$projResultado;$met=$projMetricas;
    $modo=$r['target']>$projAsOf?'Projeção para o futuro':'Retroprevisão confrontada';
?>
<div class="mat-proj-formula">
<?php if($projFormula==='log'):?>T(t) = 100 × ln(1 + K(t)) / ln(1 + K(<?=$projBase?>))
<?php else:?>T(t) = 100 × (K(t) / K(<?=$projBase?>))<sup>0,65</sup><?php endif;?>
</div>
<div class="mat-proj-metrics">
<div class="mat-proj-metric"><span class="tag">Modo</span><strong style="font-size:23px"><?=h($modo)?></strong><small>Horizonte: <?=$r['horizon']?> anos</small></div>
<div class="mat-proj-metric"><span class="tag">Treino até <?=$projCorte?></span><strong><?=$r['n_train']?></strong><small>Marcos ponderados do conjunto <?=h($projSerie)?></small></div>
<div class="mat-proj-metric primary"><span class="tag">T(<?=$projAlvo?>) previsto</span><strong><?=$fmtProj($r['t_predicted'])?></strong><small>T(<?=$projBase?>) = 100 · T(<?=$projCorte?>) = <?=$fmtProj($r['t_origin'])?></small></div>
<div class="mat-proj-metric"><span class="tag">T(<?=$projAlvo?>) observado</span><strong><?=$fmtProj($r['t_observed'])?></strong><small><?= $r['t_observed']===null?'Ainda não há observação futura':'Calculado com o acervo disponível até '.$projAsOf?></small></div>
<div class="mat-proj-metric"><span class="tag">Erro com sinal</span><strong><?=$fmtSinal($r['error_signed'])?></strong><small><?=h((string)($r['direction']??'Sem erro observável'))?> · previsto − observado</small></div>
<div class="mat-proj-metric"><span class="tag">Erro absoluto / percentual</span><strong><?=$fmtProj($r['error_absolute'])?></strong><small><?=$fmtSinal($r['error_percent'])?><?= $r['error_percent']!==null?'%':''?> no confronto do ano-alvo</small></div>
</div>
<?php if($r['status']==='insuficiente'):?><div class="mat-proj-warn"><strong>Não há dados para projetar com este recorte.</strong> <?=h((string)$r['reason'])?> Experimente a série “Ensaio” ou um ano de corte mais recente. Não serão inventados pesos faltantes.</div>
<?php else:?>
<section class="paper-panel mat-panel-spaced mat-proj-section">
  <div class="panel-head"><span>01 / SÉRIE HISTÓRICA E CENÁRIO</span><span>ANO REAL · SEM ANOS ARTIFICIAIS</span></div>
  <h2>O que o modelo conhecia e o que projetou</h2>
  <div class="mat-proj-grafico"><?=TemporisProjecoes::grafico($r,$met)?></div>
  <p class="mat-proj-notes">A linha verde mostra o índice resultante dos marcos já registrados, com a mesma base <?=$projBase?> em todos os cenários;  a linha tracejada mostra a extrapolação das taxas estimadas <strong>somente até <?=$projCorte?></strong>. O traço vermelho no destino representa a diferença entre previsto e observado, quando existe. A escala X mantém o espaçamento proporcional aos anos históricos.</p>
</section>
<?php if($r['t_observed']!==null && $r['year_last']!==null && (int)$r['year_last']<$projAlvo): ?>
<div class="mat-proj-warn"><strong>Atenção à cobertura documental:</strong> o último marco datado neste recorte é de <strong><?=$r['year_last']?></strong>, embora o alvo seja <?=$projAlvo?>. O valor indicado como “observado” corresponde ao acúmulo disponível no banco, que pode estar incompleto. Não o interprete como uma medição histórica externa definitiva.</div>
<?php endif; ?>
<?php if($r['t_observed']!==null): ?>
<div class="mat-proj-info" style="margin-top:18px"><h3>Leitura do erro <?=$projCorte?> → <?=$projAlvo?></h3><p>Previsão: <strong><?=$fmtProj($r['t_predicted'])?></strong> · Observado no banco: <strong><?=$fmtProj($r['t_observed'])?></strong> · Diferença: <strong><?=$fmtSinal($r['error_signed'])?> pontos</strong> · Erro absoluto: <strong><?=$fmtProj($r['error_absolute'])?> pontos</strong> · Erro relativo com sinal: <strong><?=$fmtSinal($r['error_percent'])?>%</strong>. Um erro positivo significa previsão acima do observado; negativo significa abaixo.</p></div>
<?php else: ?>
<div class="mat-proj-warn"><strong>Não é possível calcular o erro de <?=$projAlvo?> agora.</strong> O valor previsto é uma extrapolação, enquanto o índice observado só poderá ser calculado com marcos documentados até aquele ano. Não use o RMSE de períodos antigos como se fosse um intervalo de confiança para o futuro.</div>
<?php endif; ?>
<section class="paper-panel mat-panel-spaced mat-proj-section">
<div class="panel-head"><span>02 / CENÁRIOS DE EXTRAPOLAÇÃO</span><span>MESMA FÓRMULA · MODELOS DIFERENTES</span></div>
<h2>E se a taxa de crescimento for outra?</h2>
<div class="exp-overflow"><table class="exp-table"><thead><tr><th>Modelo de extrapolação</th><th>Previsto em <?=$projAlvo?></th><th>Observado</th><th>Diferença com sinal</th><th>Erro % com sinal</th><th>Situação</th></tr></thead><tbody>
<?php foreach($projModelos as $nome=>$rotulo):$v=$projVariacoes[$nome]??[];?><tr><td><strong><?=h($rotulo)?></strong><?=$projModelo===$nome?' · selecionado':''?></td><td><?=$fmtProj($v['t_predicted']??null)?></td><td><?=$fmtProj($v['t_observed']??null)?></td><td><?=$fmtSinal($v['error_signed']??null)?></td><td><?=$fmtSinal($v['error_percent']??null)?><?=isset($v['error_percent'])?'%':''?></td><td><?=h((string)($v['status']??'indisponível'))?></td></tr><?php endforeach; ?>
</tbody></table></div>
<p class="mat-proj-notes">A formulação escolhida transforma K(t) em T(t); o modelo de extrapolação estima o crescimento futuro de K(t). São escolhas matemáticas diferentes. A hipótese de tendência pode crescer ou desacelerar muito em horizontes longos: compare sempre com as médias de 50 e 100 anos.</p>
</section>
<section class="paper-panel mat-panel-spaced mat-proj-section">
<div class="panel-head"><span>03 / VALIDAÇÃO RETROSPECTIVA</span><span>ERRO HISTÓRICO OBSERVADO</span></div><h2>Quão longe nossas previsões erraram no passado?</h2>
<div class="mat-proj-grid"><div class="mat-proj-info"><h3><?= (int)($met['n']??0) ?> confrontos retrospectivos</h3><p>Janelas históricas com o mesmo horizonte (<?=$r['horizon']?> anos), base <?=$projBase?> e que terminam <strong>até o ano de corte <?=$projCorte?></strong>. Os marcos posteriores a cada corte não entram no ajuste daquela previsão.</p></div><div class="mat-proj-info"><h3>MAE <?=$fmtProj($met['mae']??null)?> · RMSE <?=$fmtProj($met['rmse']??null)?></h3><p>MAE é a média dos erros absolutos; RMSE penaliza mais os erros grandes; viés médio = <?=$fmtSinal($met['bias']??null)?> pontos. Essas medidas exigem testes históricos suficientes e <strong>não são intervalos de confiança</strong>.</p></div></div>
<?php if(($met['n']??0)>0):?><details><summary>Conferir os cortes e erros dos testes históricos</summary><div class="exp-overflow"><table class="exp-table"><thead><tr><th>Corte</th><th>Alvo</th><th>Previsto</th><th>Observado</th><th>Erro com sinal</th></tr></thead><tbody><?php foreach($met['folds'] as $f):?><tr><td><?=$f['corte']?></td><td><?=$f['alvo']?></td><td><?=$fmtProj($f['previsto'])?></td><td><?=$fmtProj($f['observado'])?></td><td><?=$fmtSinal($f['erro'])?></td></tr><?php endforeach;?></tbody></table></div></details><?php endif; ?>
<p class="mat-proj-notes">Aviso metodológico: as notas da curadoria foram atribuídas retrospectivamente. O modelo não vê eventos posteriores ao corte, porém o próprio trabalho de selecionar e pontuar os marcos pode ter sido influenciado por conhecimento posterior. Isso produz um teste retrospectivo exploratório, <strong>não uma previsão prospectiva historicamente independente</strong>.</p>
</section>
<?php endif; ?>
<?php endif; ?>
