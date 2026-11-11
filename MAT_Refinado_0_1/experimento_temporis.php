<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/TemporisBacktest.php';
require_once __DIR__.'/app/TemporisCuradoria.php';
require_once __DIR__.'/app/TemporisGraficos.php';
if (!is_admin()) {header('Location: index.php?page=login');exit;}

$origin=filter_input(INPUT_GET,'corte',FILTER_VALIDATE_INT) ?: 1920;
$target=filter_input(INPUT_GET,'alvo',FILTER_VALIDATE_INT) ?: 2020;
$allowed=['previa'=>'Curadoria: prévia documentada','ensaio'=>'Curadoria: datas extraídas (não auditadas)','homologada'=>'Curadoria: exclusivamente homologados','legado'=>'Banco legado: avaliações confirmadas'];
$mode=(string)($_GET['conjunto']??'previa');
if(!isset($allowed[$mode]))$mode='previa';
$origin=max(-10000,min(2099,$origin));$target=max(-9999,min(2100,$target));
$error=null;$notes=[];$excluded=[];$points=[];$num=0;$eligible=0;$summary=[];$folds=[];$result=null;$selected=[];$decisoes=[];
try {
    if ($target<=$origin)throw new InvalidArgumentException('O ano observado deve ser posterior ao corte de treinamento.');
    if($mode==='legado'){
        if(!db())throw new RuntimeException('Banco legado não disponível. Confira a configuração de conexão.');
        require_once __DIR__.'/app/revised_repository.php';
        $data=TemporisBacktest::approvedPoints(all_markers(),revised_all_evaluations(),$target);
        $points=$data['points'];$num=$data['total_before_target'];$eligible=$data['included'];$excluded=$data['excluded'];
    }else{
        $pdo=CuradoriaRodadas::connect();
        if(!$pdo)throw new RuntimeException('Banco mat_temporis_curadoria indisponível; nenhuma série fictícia foi utilizada.');
        $dataset=TemporisCuradoria::consultar($pdo,$origin);
        $selected=$dataset[$mode]??[];
        $decisoes=$dataset['decisoes_humanas']??[];
        $points=array_map(static fn(array $x):array=>['year'=>(int)$x['year'],'weight'=>(float)$x['score'],
            'id'=>(string)$x['id']], $selected['series']??[]);
        $num=(int)$pdo->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
        $eligible=count($points);
        $excluded=['com_notas_pendentes'=>(int)($dataset['incompletos']??0),
            'com_notas_mas_sem_data_validada'=>(int)($dataset['sem_data']??0),
            'pendentes_de_homologacao'=>(int)($dataset['ainda_nao_homologados']??0)];
        if($mode==='ensaio')$notes[]='Série exploratória: datas extraídas não são datas validadas; podem ocorrer duplicidades e notas ainda não confirmadas.';
        if($mode==='previa')$notes[]='Prévia exploratória: as 11 notas podem ser propostas, sem homologação; não é uma previsão científica validada.';
    }
    $result=TemporisBacktest::experiment($points,$origin,$target);
    $folds=TemporisBacktest::historicalBacktests($points,$origin,$target-$origin,20);
    $summary=TemporisBacktest::summarize($folds);
    if(($_GET['export']??'')==='json'){
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mat_backtest_temporis.json"');
        echo json_encode(['ALERTA'=>'Os dados de prévia/ensaio não representam marcos homologados.',
            'conjunto'=>$mode,'total_acervo'=>$num,'elegiveis_conjunto'=>$eligible,'exclusoes'=>$excluded,
            'resultado'=>$result,'backtests_anteriores'=>$folds,'resumo'=>$summary],
            JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        exit;
    }
}catch(Throwable $e){$error=$e->getMessage();}
$fmt=static fn($v,$digits=2)=>$v===null?'—':number_format((float)$v,$digits,',','.');
$warn=($mode!=='homologada'&&$mode!=='legado');
$baseReady=$result && ($result['k_origin']??0)>0;
?>
<?php
$page='backtesting';$navPage='backtesting';
$pageLabels=['backtesting'=>'Backtesting · Têmporis 0.2'];
$stats=app_stats();$flash=take_flash();
require __DIR__.'/views/header.php';
?>
<section class="mat-experimento">
<a class="text-link" href="index.php?page=temporis">← Voltar ao índice</a>
<p class="exp-tag">MAT / METODOLOGIA / TEMPO HISTÓRICO / ERRO DE PREVISÃO</p>
<header class="page-heading"><div class="section-overline">03 / ÍNDICE TÊMPORIS / TESTES DE VALIDAÇÃO</div><h1>O passado como <em>teste.</em></h1><p>Backtesting da Têmporis 0.2 com corte histórico, previsão e confronto com o resultado observado.</p></header>
<p>A curva usa os <strong>anos efetivos dos marcos</strong>. A margem de erro só é calculada quando existem resultados observados e previsões suficientes; dados faltantes nunca são substituídos por valores fictícios.</p>
<div class="exp-warning"><strong>DADOS DO BANCO — REGRAS ESTRITAS</strong><p><strong>Homologada:</strong> apenas marcos com revisão científica completa. <strong>Prévia/ensaio:</strong> exibidos separadamente, com notas e anos reais do banco de curadoria, mas sujeitos à revisão; esses resultados não constituem evidência confirmada. O banco legado possui critérios próprios e não é convertido automaticamente.</p></div>
<form class="exp-form" method="get">
 <label>Conjunto de dados
  <select name="conjunto"><?php foreach($allowed as $key=>$label):?><option value="<?=h($key)?>" <?=$mode===$key?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
 </label>
 <label>Corte do treinamento<input type="number" name="corte" value="<?=h((string)$origin)?>" min="-10000" max="2099"></label>
 <label>Ano observado<input type="number" name="alvo" value="<?=h((string)$target)?>" min="-9999" max="2100"></label>
 <button class="exp-btn" type="submit">Calcular previsão e erro</button>
</form>
<?php if($error):?><div class="exp-warning"><strong>Não foi possível calcular:</strong> <?=h($error)?></div>
<?php else:?>
<div class="exp-boxes">
 <div class="exp-box">Registros no acervo consultado<strong><?=$num?></strong></div>
 <div class="exp-box">Marcos com nota elegível nesta seleção<strong><?=$eligible?></strong></div>
 <div class="exp-box">Marcos até <?=$origin?><strong><?=$result['n_train']?></strong></div>
 <div class="exp-box">Marcos depois de <?=$origin?> até <?=$target?><strong><?=$result['n_after_origin']?></strong></div>
</div>
<?php if($warn):?><div class="mat-grafo-crit"><strong>SÉRIE PROVISÓRIA.</strong> As notas ainda podem depender de confirmação humana. Erros e projeções são exercícios retrospectivos sobre os valores registrados, não validações externas da aceleração tecnológica.</div><?php endif; ?>
<?php foreach($notes as $note):?><p class="exp-small"><?=h($note)?></p><?php endforeach;?>
<h2>01 / Cronologia — marcos na posição histórica correta</h2>
<div class="mat-grafo-bloco">
<?php if($mode!=='legado'):?>
 <?=TemporisGraficos::svgCronologico($selected['series']??[],$origin,'log',true)?>
<?php else:?>
 <p class="mat-grafo-nota">O banco legado segue os próprios filtros de aprovação. Para ver os 800 dossiês atuais e as duas curvas na mesma escala, selecione a prévia documentada acima.</p>
<?php endif;?>
</div>
<?php if($mode!=='legado'): ?>
<h3>Marcos com decisão humana, inclusive fora do cálculo</h3>
<div class="mat-grafo-bloco"><?=TemporisGraficos::svgDecisoes($decisoes)?></div>
<?php endif; ?>
<p class="mat-grafo-nota">As barras laranja são <strong>faixas de datação</strong>, não desvios-padrão. Pontos de aprovação e homologação usam cores distintas. Cada evento é plotado pelo próprio ano, sem comprimir arbitrariamente os espaços entre acontecimentos.</p>
<h2>02 / Índice e observação</h2>
<div class="exp-boxes">
 <div class="exp-box">T(<?=$origin?>) — base<strong><?=$baseReady?'100,00':'—'?></strong></div>
 <div class="exp-box">K(<?=$origin?>) calculado nesta seleção<strong><?=$fmt($result['k_origin'],3)?></strong></div>
 <div class="exp-box">K(<?=$target?>) observado<strong><?=$fmt($result['k_observed'],3)?></strong></div>
 <div class="exp-box">T(<?=$target?>) observado<strong><?=$fmt($result['t_observed'])?></strong></div>
</div>
<?php foreach($result['notes'] as $note):?><div class="exp-warning"><?=h((string)$note)?></div><?php endforeach;?>
<h2>03 / Erro da previsão — <?=$origin?> → <?=$target?></h2>
<p>O segmento entre o valor previsto e o observado representa o <strong>erro absoluto em pontos do índice</strong>. Não é uma faixa de confiança estatística.</p>
<div class="mat-grafo-bloco"><?=TemporisGraficos::svgPrevisao($result,$summary)?></div>
<div class="exp-overflow"><table class="exp-table"><thead><tr><th>Modelo</th><th>Previsto</th><th>Observado</th><th>Resíduo (previsto − observado)</th><th>Erro absoluto</th><th>Erro %</th></tr></thead><tbody>
<?php foreach(TemporisBacktest::MODEL_NAMES as $key=>$label):$m=$result['models'][$key]??[]; ?>
<tr><td><?=h($label)?></td><td><?=$fmt($m['t_predicted']??null)?></td><td><?=$fmt($result['t_observed'])?></td><td><?=$fmt($m['error_points']??null)?></td><td><?=$fmt($m['absolute_error']??null)?></td><td><?=$fmt($m['relative_error_percent']??null)?><?=($m['relative_error_percent']??null)!==null?'%':''?></td></tr>
<?php endforeach;?></tbody></table></div>
<h2>04 / Erro histórico em múltiplos cortes</h2>
<p>O cálculo testa cortes históricos que terminam antes ou no corte selecionado, respeitando o mesmo horizonte de previsão. MAE e RMSE são calculados apenas quando existem comparações reais; nenhuma margem de erro é inferida a partir de zero testes.</p>
<div class="exp-overflow"><table class="exp-table"><thead><tr><th>Modelo</th><th>Testes válidos</th><th>MAE (pontos)</th><th>RMSE (pontos)</th><th>MAPE (%)</th></tr></thead><tbody>
<?php foreach($summary as $m):?><tr><td><?=h((string)$m['label'])?></td><td><?=h((string)$m['n_folds'])?></td><td><?=$fmt($m['mae'])?></td><td><?=$fmt($m['rmse'])?></td><td><?=$fmt($m['mape_percent'])?></td></tr><?php endforeach; ?></tbody></table></div>
<?php
$quantas=array_sum(array_map(static fn($s)=>(int)($s['n_folds']??0),$summary));
?>
<?php if($quantas===0):?><div class="mat-grafo-crit">Margem de erro estatística <strong>não estimável</strong> com os cortes históricos disponíveis. O sistema exibe erros pontuais apenas quando há previsão e observação comparáveis.</div>
<?php else: ?><div class="mat-grafo-crit"><strong>Como ler o RMSE:</strong> resume a magnitude dos erros históricos do modelo, na unidade do índice. Não é automaticamente um intervalo de confiança de 95% nem garante a incerteza futura. Os cortes podem se sobrepor temporalmente.</div><?php endif; ?>
<p><a class="exp-btn" href="?<?=h(http_build_query(['conjunto'=>$mode,'corte'=>$origin,'alvo'=>$target,'export'=>'json']))?>">Exportar dados e métricas em JSON</a></p>
<?php endif;?>
<h2>Fórmulas e limites</h2><p class="exp-small">I = 1 + 9Σ(4 impactos)/12; C = Σ(4 confianças)/12; F = 1 + 0,5Σ(3 fatores)/9<br>
w = (I/10)² × C × F; K(t) = Σw, para ano ≤ t<br>
T(t) = 100 × ln(1 + K(t)) / ln(1 + K(corte))<br>
Erro absoluto = |T previsto − T observado|; erro relativo = 100 × erro absoluto / |T observado|<br>
MAE = média dos erros absolutos; RMSE = raiz da média dos erros ao quadrado.</p>
<p class="exp-small">O índice observado depende da própria metodologia e das notas da curadoria, não de uma verdade tecnológica independente. A publicação científica exige análise de sensibilidade, validação externa e auditoria contra viés retrospectivo.</p>
</section>
<?php require __DIR__.'/views/footer.php'; ?>
