<?php
/** Backtesting do MAT Final. Consulta somente leitura; os resultados são exploratórios. */
require_once MAT_ROOT.'/app/TemporisFinal.php';
$btSrc=(string)($_GET['fonte']??'auto');
if(!isset(TemporisFinal::FONTES[$btSrc]))$btSrc='auto';
$btCoorte=(string)($_GET['coorte']??'ampla');
if(!isset(TemporisFinal::COORTES[$btCoorte]))$btCoorte='ampla';
$btHoriz=filter_var($_GET['horizonte']??25,FILTER_VALIDATE_INT);
if(!in_array($btHoriz,[10,25,50,100],true))$btHoriz=25;
$btError=null;$btRows=[];$btAudit=[];$btSource='—';$btFonteReal='';$btCount=0;
$btFmt=static fn(?float $n,int $digits=1):string=>$n===null?'—':number_format($n,$digits,',','.');
try {
    $btResult=TemporisFinal::obterDados($btSrc,2026);
    $btAudit=$btResult['dados']['audit'];$btSource=$btResult['database'];$btFonteReal=$btResult['fonte'];
    $btPoints=TemporisFinal::filtrar($btResult['dados']['pontos'],$btCoorte);
    $btCount=count($btPoints);
    $btLine=TemporisFinal::linha($btPoints,'quadratica');
    foreach(TemporisFinal::MODELOS as $id=>$label){
        $stat=TemporisFinal::validar($btLine,$id,$btHoriz,2026);
        $btRows[]=['id'=>$id,'label'=>$label,'stat'=>$stat];
    }
    usort($btRows,static function($a,$b){
        $x=$a['stat']['wape'];$y=$b['stat']['wape'];
        if($x===null)return $y===null?0:1;
        if($y===null)return -1;
        return $x<=>$y;
    });
} catch(Throwable $e){$btError=$e->getMessage();}
$btRanked=array_values(array_filter($btRows,static fn($r)=>$r['stat']['wape']!==null));
$btBest=$btRanked[0]??null;
$btTop=array_slice($btRanked,0,3);
$btSample=$btBest && $btBest['stat']['folds'] ? $btBest['stat']['folds'][count($btBest['stat']['folds'])-1] : null;
$btNCounts=array_unique(array_map(static fn($r)=>(int)$r['stat']['n'],$btRanked));
$btDifferentSamples=count($btNCounts)>1;
?>
<header class="page-heading tf-heading tf-bt-heading">
 <div class="section-overline">ÍNDICE TÊMPORIS / TESTE DAS PREVISÕES</div>
 <h1>Backtesting <em>· o quanto as previsões acertaram?</em></h1>
 <p>Usamos dados do passado para fazer previsões e depois comparamos com o que foi registrado. Aqui você vê os resultados primeiro e pode abrir os detalhes quando quiser.</p>
</header>

<form class="tf-form tf-bt-form" method="get" action="index.php">
 <input type="hidden" name="page" value="backtesting">
 <div class="tf-bt-form-first">
  <label for="bt-horizonte">Prever quantos anos à frente?
   <select name="horizonte" id="bt-horizonte"><?php foreach([10,25,50,100] as $h):?><option value="<?=$h?>" <?=$btHoriz===$h?'selected':''?>><?=$h?> anos</option><?php endforeach;?></select>
  </label>
  <button class="btn btn-dark" type="submit">Ver resultado →</button>
 </div>
 <details class="tf-bt-options"><summary>Mostrar mais · escolher fonte e conjunto de marcos</summary>
  <div class="tf-form-advanced">
   <label for="bt-fonte">De onde vêm os dados?
    <select name="fonte" id="bt-fonte"><?php foreach(TemporisFinal::FONTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btSrc===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
   </label>
   <label for="bt-coorte">Quais marcos entram no teste?
    <select name="coorte" id="bt-coorte"><?php foreach(TemporisFinal::COORTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btCoorte===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
   </label>
  </div>
 </details>
</form>

<?php if($btError): ?>
<div class="mat-final-warning" role="alert"><strong>Não foi possível calcular as previsões.</strong> <?=h($btError)?>. <a href="<?=mat_url('backtesting',['fonte'=>'snapshot','coorte'=>$btCoorte,'horizonte'=>$btHoriz])?>">Consultar a exportação histórica disponível</a>.</div>
<?php else: ?>
<div class="tf-bt-data-line" role="note">
 <span><strong>Dados utilizados:</strong> <?=h($btFonteReal==='snapshot'?'exportação histórica (não atualiza com o MySQL)':($btFonteReal==='curadoria'?'curadoria ativa (MySQL)':'base isolada do MAT Final (MySQL)'))?></span>
 <span><strong><?=$btCount?></strong> marcos no teste, de <strong><?= (int)($btAudit['total']??0) ?></strong> candidatos</span>
</div>

<section class="tf-bt-result" aria-labelledby="bt-resultado-titulo">
 <div class="tf-bt-result-main">
  <span class="tf-bt-kicker">01 / O RESULTADO EM POUCAS PALAVRAS</span>
  <h2 id="bt-resultado-titulo">Qual modelo chegou mais perto dos dados históricos?</h2>
  <?php if($btBest): ?>
  <p>Para previsões de <strong><?=$btHoriz?> anos</strong>, o modelo <strong><?=h($btBest['label'])?></strong> apresentou o menor erro percentual agregado entre os que puderam ser calculados nesta seleção.</p>
  <p class="tf-bt-interpretation">Isso não garante que ele acertará previsões futuras. Mostra apenas como se comportou nos testes retrospectivos realizados.</p>
  <?php else: ?>
  <p>Não há dados suficientes para calcular o erro percentual nesta seleção. Experimente outro conjunto de marcos ou um horizonte menor.</p>
  <?php endif; ?>
 </div>
 <div class="tf-bt-result-number">
  <span>Menor erro no teste</span>
  <strong><?=$btBest?$btFmt($btBest['stat']['wape']).'%':'—'?></strong>
  <small>WAPE: erro absoluto total dividido pelo crescimento observado total. Menor é melhor neste teste, não é porcentagem de acertos.</small>
 </div>
</section>

<div class="tf-bt-facts" aria-label="Resumo da avaliação">
 <div><span>Período previsto</span><strong><?=$btHoriz?> anos</strong></div>
 <div><span>Modelos com resultado</span><strong><?=count($btRanked)?> de <?=count($btRows)?></strong></div>
 <div><span>Testes históricos do destaque</span><strong><?=$btBest?(int)$btBest['stat']['n']:'—'?></strong></div>
</div>

<section class="paper-panel mat-final-section tf-bt-simple" aria-labelledby="bt-comparacao-titulo">
 <div class="panel-head"><span>02 / COMPARAÇÃO RÁPIDA</span><span>MENOR ERRO = MELHOR AJUSTE NESTE TESTE</span></div>
 <h2 id="bt-comparacao-titulo">Os 3 menores erros</h2>
 <p>Compare os resultados sem precisar ler toda a tabela técnica. O percentual mede a diferença acumulada entre o <strong>crescimento previsto</strong> e o <strong>crescimento observado</strong>.</p>
 <?php if($btTop): ?>
 <div class="tf-bt-shortlist">
  <?php foreach($btTop as $i=>$row):$stat=$row['stat'];$reference=max(1.,(float)($btTop[count($btTop)-1]['stat']['wape']??1.));?>
  <div class="tf-bt-short-row <?=$i===0?'tf-bt-short-first':''?>">
   <div class="tf-bt-short-title"><span><?=$i+1?>.</span><strong><?=h($row['label'])?></strong><?php if($i===0):?><small>menor erro observado</small><?php endif;?></div>
   <div class="tf-bt-short-meter" aria-hidden="true"><span style="width:<?=max(3.,min(100.,100.*(float)$stat['wape']/$reference))?>%"></span></div>
   <div class="tf-bt-short-value"><strong><?=$btFmt($stat['wape'])?>%</strong><small><?=$stat['n']?> testes</small></div>
  </div>
  <?php endforeach; ?>
 </div>
 <p class="tf-bt-hint">Barras menores indicam menos erro. <?php if($btDifferentSamples):?><strong>Atenção:</strong> os modelos não foram testados no mesmo número de períodos; a comparação precisa de cautela.<?php else:?>Os modelos com percentual calculável usam <?=$btBest?(int)$btBest['stat']['n']:0?> períodos históricos cada nesta seleção.<?php endif;?></p>
 <?php else: ?><p>Nenhum modelo apresentou WAPE calculável. Os testes sem resultado continuam disponíveis abaixo para auditoria.</p><?php endif; ?>
</section>

<?php if($btSample): ?>
<section class="tf-bt-example" aria-labelledby="bt-exemplo-titulo">
 <div><span class="tf-bt-kicker">03 / EXEMPLO CONCRETO</span><h2 id="bt-exemplo-titulo">O que foi previsto × o que aconteceu?</h2>
 <p>Em um dos testes do modelo <strong><?=h($btBest['label'])?></strong>, usamos os dados disponíveis até <strong><?=$btSample['corte']?></strong> para estimar o crescimento acumulado até <strong><?=$btSample['alvo']?></strong>.</p></div>
 <div class="tf-bt-actual-predicted"><div><span>Previsão de crescimento</span><strong><?=$btFmt($btSample['crescimento_previsto'],2)?></strong><small>unidades do estoque K</small></div><div><span>Crescimento observado</span><strong><?=$btFmt($btSample['crescimento_real'],2)?></strong><small>unidades do estoque K</small></div></div>
 <p class="tf-bt-example-footnote">Diferença neste exemplo: <strong><?=$btFmt(abs($btSample['erro']),2)?> unidades de K</strong> (<?=$btSample['erro']>0?'previsão acima do observado':($btSample['erro']<0?'previsão abaixo do observado':'previsão igual ao observado')?>). K é a soma dos pesos dos marcos, não a quantidade de marcos. Este é apenas um corte histórico, não o erro percentual geral.</p>
</section>
<?php endif; ?>

<details class="tf-expander tf-bt-more" id="bt-todos-modelos"><summary><span><strong>Mostrar mais · comparar todos os <?=count($btRows)?> modelos</strong><small>Tabela completa com erro percentual, erro médio, tendência de superestimar ou subestimar e testes válidos</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body">
 <p><strong>Como ler:</strong> WAPE é o erro percentual agregado (menor costuma ser melhor). MAE mede o erro médio em unidades de K. Viés positivo significa tendência a prever acima do observado; negativo, abaixo. “—” significa que não foi possível calcular, não que o erro foi zero.</p>
 <div class="mat-final-table-wrap"><table class="mat-final-table tf-bt-table"><thead><tr><th>Modelo</th><th>Erro (WAPE)</th><th>MAE (K)</th><th>Viés médio (K)</th><th>Testes válidos</th></tr></thead><tbody>
 <?php foreach($btRows as $row):$stat=$row['stat'];?><tr class="<?=$row['id']==='sem_avanco'?'tf-control-row':''?>"><th><?=h($row['label'])?><?=$row['id']==='sem_avanco'?' · controle':''?></th><td><?=$stat['wape']===null?'—':$btFmt($stat['wape']).'%'?></td><td><?=$btFmt($stat['mae'],3)?></td><td><?=$btFmt($stat['bias'],3)?></td><td><?=$stat['n']?></td></tr><?php endforeach;?></tbody></table></div>
 <p class="mat-final-disclaimer">A classificação por WAPE não é um ranking universal: pode depender do horizonte, da seleção dos marcos e do número de testes válidos.</p>
 </div>
</details>

<details class="tf-expander tf-bt-more"><summary><span><strong>Mostrar mais · conferir cada previsão histórica</strong><small>Veja, ano a ano, quanto cada modelo previu e o que foi observado</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body">
 <?php foreach($btRows as $row):$folds=$row['stat']['folds'];?><details class="tf-inline-details"><summary><?=h($row['label'])?> · <?=count($folds)?> testes</summary>
 <?php if(!$folds):?><p>Não houve dados históricos suficientes para testar este modelo neste horizonte.</p><?php else:?><div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Ano da previsão</th><th>Ano avaliado</th><th>Crescimento observado ΔK</th><th>Crescimento previsto ΔK</th><th>Diferença (com sinal)</th></tr></thead><tbody>
 <?php foreach($folds as $fold):?><tr><td><?=$fold['corte']?></td><td><?=$fold['alvo']?></td><td><?=$btFmt($fold['crescimento_real'],4)?></td><td><?=$btFmt($fold['crescimento_previsto'],4)?></td><td><?=$btFmt($fold['erro'],4)?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></details><?php endforeach; ?>
 </div>
</details>

<details class="tf-expander tf-bt-more"><summary><span><strong>Mostrar mais · como o teste funciona e quais são os limites?</strong><small>Explicação técnica da metodologia, das fontes e dos cuidados científicos</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body">
 <h2>Como fizemos o backtesting?</h2>
 <p>Em cada corte histórico de 25 em 25 anos, começando em 1700, o programa usa o estoque acumulado K até aquele ano para projetar o crescimento nos próximos <?=$btHoriz?> anos. Em seguida, compara a estimativa com a variação de K registrada na base escolhida. O método atual usa pesos quadráticos para construir K.</p>
 <p><strong>Fonte efetivamente utilizada:</strong> <?=h($btSource)?>. <strong>Coorte:</strong> <?=h(TemporisFinal::COORTES[$btCoorte])?>. <strong>Marcos selecionados:</strong> <?=$btCount?> de <?= (int)($btAudit['total']??0) ?> candidatos cadastrados. O programa não altera nem aprova marcos durante o teste.</p>
 <h2>O que esse resultado não prova?</h2>
 <p>Este é um experimento retrospectivo exploratório. A base histórica e as notas foram organizadas depois de muitos dos acontecimentos avaliados; por isso, não é uma previsão feita de verdade naquela época. Resultados melhores não demonstram, por si só, validade externa do Índice Têmporis, nem garantem que o mesmo modelo será superior no futuro.</p>
 <p>Para aprofundar a validação científica, é necessário revisar datas e fontes independentes, testar a estabilidade dos pesos, possíveis duplicatas, períodos históricos diferentes e a distribuição dos marcos. O WAPE calculado é relativo ao crescimento de K, não diretamente aos pontos normalizados de Têmporis.</p>
 </div>
</details>
<div class="tf-bt-footer"><a class="btn btn-dark" href="<?=mat_url('temporis',['fonte'=>$btSrc,'coorte'=>$btCoorte])?>">← Voltar ao Índice Têmporis</a><a href="<?=mat_url('metodo')?>">Consultar o Caderno de Fórmulas →</a></div>
<?php endif; ?>
