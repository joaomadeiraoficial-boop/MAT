<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/TemporisBacktest.php';
if(!is_admin()){header('Location: index.php?page=login');exit;}
$origin=filter_input(INPUT_GET,'corte',FILTER_VALIDATE_INT) ?: 1920;
$target=filter_input(INPUT_GET,'alvo',FILTER_VALIDATE_INT) ?: 2020;
// Pagina publica de pesquisa: SEM modo demonstrativo.
try {
     if (!db()) throw new RuntimeException('MySQL nao disponivel. Nenhum dado ficticio sera exibido.');
     if (!function_exists('revised_all_evaluations')) require_once __DIR__.'/app/revised_repository.php';
     $data=TemporisBacktest::approvedPoints(all_markers(),revised_all_evaluations(),$target);
     $points=$data['points'];$num=$data['total_before_target'];$eligible=$data['included'];$excluded=$data['excluded'];
 $result=TemporisBacktest::experiment($points,$origin,$target);
 $folds=TemporisBacktest::historicalBacktests($points,$origin,$target-$origin,20);
 $summary=TemporisBacktest::summarize($folds);
 if(($_GET['export']??'')==='json'){
     header('Content-Type: application/json; charset=utf-8');
     header('Content-Disposition: attachment; filename="mat_backtest_temporis.json"');
     echo json_encode(['AVISO'=>'RESULTADO CONDICIONAL A MARCOS E RUBRICAS REALMENTE APROVADOS','dados_elegiveis'=>$eligible,'resultado'=>$result,'backtests_anteriores'=>$folds,'resumo'=>$summary],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
     exit;
 }
 $error=null;
} catch(Throwable $e){$error=$e->getMessage();$result=null;$summary=[];$folds=[];$eligible=0;$num=0;$excluded=[];}
$fmt=static fn($v,$digits=2)=>$v===null?'—':number_format((float)$v,$digits,',','.');
?><!doctype html><html lang="pt-BR"><head><link rel="icon" href="assets/insignia.svg" type="image/svg+xml"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MAT | Backtesting Têmporis</title><link rel="stylesheet" href="assets/style.css"><style>
.main-exp{max-width:1100px;margin:auto;padding:35px 18px 70px;font-family:Georgia,serif;color:#193032}h1{font-size:clamp(32px,4vw,58px);font-weight:400}h2{font-size:24px}p{line-height:1.6}.exp-warning{border-left:4px solid #ad704c;background:#fff0dc;padding:16px;margin:12px 0}.exp-good{border-left:4px solid #557665;background:#e9f3eb;padding:16px;margin:12px 0}.exp-boxes{display:grid;grid-template-columns:repeat(auto-fit,minmax(175px,1fr));gap:12px}.exp-box{background:#fffdf7;border:1px solid #dccdbf;padding:14px}.exp-box strong{font-size:27px;display:block;margin-top:8px}.exp-table{width:100%;border-collapse:collapse;background:#fffdf7}.exp-table td,.exp-table th{padding:12px 9px;text-align:left;border-bottom:1px solid #ded3c7}.exp-overflow{overflow-x:auto}.exp-small{font:12px monospace;color:#666}.exp-form{display:flex;gap:14px;align-items:end;flex-wrap:wrap}.exp-form label{display:grid;gap:4px}.exp-form input{max-width:100px;padding:10px}.exp-btn{background:#172b2d;color:#fff;padding:11px 13px;border:0;cursor:pointer;text-decoration:none}.main-exp a:not(.exp-btn){color:#995b37}
</style></head><body style="background:#f6f1e9"><main class="main-exp">
<a href="index.php?page=temporis">← Índice Têmporis</a>
<p class="exp-small">MAT / METODOLOGIA REVISADA / EXPERIMENTO DE PREVISÃO HISTÓRICA</p>
<h1>Backtesting da <em>Têmporis</em></h1><p>Dados observados somente após aprovação científica. A ausência de números significa que o acervo ainda não permite uma comparação histórica.</p>
<div class="exp-warning"><strong>DADOS DO BANCO — REGRAS ESTRITAS</strong><p>Somente entram no cálculo marcos aprovados, incluídos, com fonte e os 11 critérios confirmados com justificativas. Propostas da IA e candidatos em preparação NÃO entram automaticamente.</p></div>
<form class="exp-form" method="get"><label>Corte do treinamento<input type="number" name="corte" value="<?=h((string)$origin)?>" min="1500" max="2100"></label><label>Ano observado<input type="number" name="alvo" value="<?=h((string)$target)?>" min="1600" max="2100"></label><button class="exp-btn" type="submit">Calcular previsão e erro</button></form>
<?php if($error): ?><div class="exp-warning"><?=h($error)?></div><?php else: ?>
<div class="exp-boxes"><div class="exp-box">Registros considerados<strong><?=$num?></strong></div><div class="exp-box">Com nota elegível<strong><?=$eligible?></strong></div><div class="exp-box">Marcos até <?=$origin?><strong><?=$result['n_train']?></strong></div><div class="exp-box">Marcos depois de <?=$origin?><strong><?=$result['n_after_origin']?></strong></div></div>
<h2>Valores do experimento</h2><div class="exp-boxes"><div class="exp-box">T(<?=$origin?>) — base<strong><?=$result['k_origin']>0?'100,00':'—'?></strong></div><div class="exp-box">K(<?=$origin?>) confirmado<strong><?=$fmt($result['k_origin'],3)?></strong></div><div class="exp-box">K(<?=$target?>) observado<strong><?=$fmt($result['k_observed'],3)?></strong></div><div class="exp-box">T(<?=$target?>) observado<strong><?=$fmt($result['t_observed'])?></strong></div></div>
<?php foreach($result['notes'] as $note): ?><div class="exp-warning"><?=h($note)?></div><?php endforeach; ?>
<h2>Previsão a partir de <?=$origin?> → <?=$target?></h2>
<div class="exp-overflow"><table class="exp-table"><thead><tr><th>Modelo</th><th>T previsto</th><th>T observado</th><th>Erro absoluto</th><th>Erro %</th></tr></thead><tbody>
<?php foreach(TemporisBacktest::MODEL_NAMES as $key=>$label): $m=$result['models'][$key]??[];?><tr><td><?=h($label)?></td><td><?=$fmt($m['t_predicted']??null)?></td><td><?=$fmt($result['t_observed'])?></td><td><?=$fmt($m['absolute_error']??null)?></td><td><?=isset($m['relative_error_percent'])?$fmt($m['relative_error_percent']).'%':'—'?></td></tr><?php endforeach;?></tbody></table></div>
<h2>Validação histórica anterior ao corte (mesmo horizonte)</h2><p>Nesses ensaios adicionais, nenhum alvo posterior a <?=$origin?> foi utilizado. Modelos podem ser comparados por MAE e RMSE quando houver quantidade suficiente de testes.</p>
<div class="exp-overflow"><table class="exp-table"><thead><tr><th>Modelo</th><th>Testes válidos</th><th>MAE</th><th>RMSE</th><th>MAPE</th></tr></thead><tbody><?php foreach($summary as $m):?><tr><td><?=h($m['label'])?></td><td><?=$m['n_folds']?></td><td><?=$fmt($m['mae'])?></td><td><?=$fmt($m['rmse'])?></td><td><?=isset($m['mape_percent'])?$fmt($m['mape_percent']).'%':'—'?></td></tr><?php endforeach;?></tbody></table></div>
<p><a class="exp-btn" href="?<?=h(http_build_query(['corte'=>$origin,'alvo'=>$target,'export'=>'json']))?>">Exportar resultados em JSON</a></p>
<?php endif; ?>
<h2>As fórmulas</h2><p class="exp-small">I = 1 + 9Σ(4 impactos)/12; C = Σ(4 confianças)/12; F = 1 + 0,5Σ(3 fatores)/9<br>w = (I/10)² × C × F; K(t) = Σw, para ano ≤ t<br>T(t) = 100 × ln(1 + K(t)) / ln(1 + K(corte))<br>Erro% = 100 × |T previsto − T observado| / |T observado|</p>
<p class="exp-small">A amostra aprovada pode ter viés documental ou avaliações retrospectivas que conheceram efeitos posteriores ao corte; isso precisa ser auditado. Mesmo com dados reais, o experimento valida uma previsão do índice construído, não sua correspondência a uma verdade tecnológica externa.</p>
</main></body></html>
