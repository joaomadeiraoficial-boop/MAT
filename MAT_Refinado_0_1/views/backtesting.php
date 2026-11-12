<?php
/** Backtesting do MAT Final. Consulta somente leitura; os resultados são exploratórios. */
require_once MAT_ROOT.'/app/TemporisFinal.php';
$btSrc=(string)($_GET['fonte']??'auto');
if(!isset(TemporisFinal::FONTES[$btSrc]))$btSrc='auto';
$btCoorte=(string)($_GET['coorte']??'ampla');
if(!isset(TemporisFinal::COORTES[$btCoorte]))$btCoorte='ampla';
$btHoriz=filter_var($_GET['horizonte']??25,FILTER_VALIDATE_INT);
if(!in_array($btHoriz,[10,25,50,100],true))$btHoriz=25;
$btRef=(string)($_GET['referencia']??'mediana');
if(!isset(TemporisFinal::MODELOS[$btRef]))$btRef='mediana';
$btShowAll=($_GET['mostrar_todos']??'')==='1';
$btCortePed=(string)($_GET['corte']??'todos');
$btCorte=null;
if(ctype_digit($btCortePed)&&in_array((int)$btCortePed,TemporisFinal::CORTES_FIXOS,true))$btCorte=(int)$btCortePed;
// Um backtesting exige que o ano-alvo JÁ exista na série observada.
// Os dois filtros são independentes: horizonte = duração; corte = origem histórica.
$btUltimoAnoObservado=null;
$btAvisoCorte=null;
$btError=null;$btRows=[];$btAudit=[];$btSource='—';$btFonteReal='';$btCount=0;
$btFmt=static fn(?float $n,int $digits=1):string=>$n===null?'—':number_format($n,$digits,',','.');
try {
    $btResult=TemporisFinal::obterDados($btSrc,2026);
    $btAudit=$btResult['dados']['audit'];$btSource=$btResult['database'];$btFonteReal=$btResult['fonte'];
    $btPoints=TemporisFinal::filtrar($btResult['dados']['pontos'],$btCoorte);
    $btCount=count($btPoints);
    $btLine=TemporisFinal::linha($btPoints,'quadratica');
    $btUltimoAnoObservado=$btLine['anos']===[]?null:(int)max($btLine['anos']);
    if($btCorte!==null && $btUltimoAnoObservado!==null && $btCorte+$btHoriz>$btUltimoAnoObservado){
        $btAvisoCorte='O teste '.($btCorte).' → '.($btCorte+$btHoriz).' termina depois do último ano com marcos observados ('.$btUltimoAnoObservado.'). Para medir o erro, precisamos conhecer o que aconteceu no ano-alvo. Foram exibidos os testes históricos válidos.';
        $btCorte=null;
    }
    foreach(TemporisFinal::MODELOS as $id=>$label){
        $stat=$btCorte!==null
            ? TemporisFinal::validarCorte($btLine,$id,$btHoriz,$btCorte)
            : TemporisFinal::validar($btLine,$id,$btHoriz,$btUltimoAnoObservado??2026);
        $btRows[]=['id'=>$id,'label'=>$label,'stat'=>$stat];
    }
} catch(Throwable $e){$btError=$e->getMessage();}
$btById=[]; foreach($btRows as $r){ $btById[$r['id']]=$r; }
$btRanked=array_values(array_filter($btRows,static fn($r)=>$r['stat']['wape']!==null));
usort($btRanked,static function($a,$b){
    $x=$a['stat']['wape'];$y=$b['stat']['wape'];
    if($x===null)return $y===null?0:1;
    if($y===null)return -1;
    return $x<=>$y;
});
$btBest=$btRanked[0]??null;
$btWorst=$btRanked ? $btRanked[count($btRanked)-1] : null;
$btTop=array_slice($btRanked,0,3);
$btRefRow=$btById[$btRef]??null;
$btSampleBase=$btRefRow && !empty($btRefRow['stat']['folds']) ? $btRefRow : ($btBest && !empty($btBest['stat']['folds']) ? $btBest : null);
$btSample=$btSampleBase ? $btSampleBase['stat']['folds'][count($btSampleBase['stat']['folds'])-1] : null;
$btNCounts=array_unique(array_map(static fn($r)=>(int)$r['stat']['n'],$btRanked));
$btDifferentSamples=count($btNCounts)>1;
$btPosRank=null;
if($btRefRow && $btRefRow['stat']['wape']!==null){
    foreach($btRanked as $i=>$r){ if($r['id']===$btRef){ $btPosRank=$i+1; break; } }
}
?>
<header class="page-heading tf-heading tf-bt-heading">
 <div class="section-overline">ÍNDICE TÊMPORIS / TESTE DAS PREVISÕES</div>
 <h1>Backtesting <em>· o quanto as previsões acertaram?</em></h1>
 <p>Usamos dados do passado para fazer previsões e depois comparamos com o que foi registrado. Aqui você vê os resultados primeiro e pode abrir os detalhes quando quiser.</p>
</header>

<form class="tf-form tf-bt-form" method="get" action="index.php">
 <input type="hidden" name="page" value="backtesting">
 <div class="tf-bt-form-first">
  <label for="bt-horizonte">1. Quantos anos quero prever? (duração)
   <select name="horizonte" id="bt-horizonte"><?php foreach([10,25,50,100] as $h):?><option value="<?=$h?>" <?=$btHoriz===$h?'selected':''?>><?=$h?> anos</option><?php endforeach;?></select>
  </label>
  <label for="bt-corte">2. Em que ano começo? (ponto de partida)
   <select name="corte" id="bt-corte" data-last-observed="<?=$btUltimoAnoObservado??''?>">
    <option value="todos" <?=$btCorte===null?'selected':''?>>Vários anos (avaliação geral)</option>
<?php foreach(TemporisFinal::CORTES_FIXOS as $y):?>
    <option value="<?=$y?>" <?=$btCorte===$y?'selected':''?> <?=($btUltimoAnoObservado!==null && $y+$btHoriz>$btUltimoAnoObservado)?'disabled':''?>><?=$y?></option>
<?php endforeach;?>
   </select>
  </label>
  <label for="bt-referencia">Seu método (modelo de referência)
   <select name="referencia" id="bt-referencia"><?php foreach(TemporisFinal::MODELOS as $id=>$label):?><option value="<?=h($id)?>" <?=$btRef===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
  </label>
  <button class="btn btn-dark" type="submit">Ver resultado →</button>
 </div>
 <div class="tf-bt-explain" aria-live="polite">
  <strong>Como interpretar:</strong>
  <span id="bt-explicacao-corte"><?php if($btCorte!==null):?>O modelo considera somente dados até <?=$btCorte?> e prevê <?=$btCorte+$btHoriz?> (<?=$btHoriz?> anos depois).<?php else: ?>O MAT repete a previsão em vários anos históricos, cada uma com <?=$btHoriz?> anos de duração, e resume os erros.<?php endif; ?></span>
  <small>O primeiro campo define a distância da previsão; o segundo, quais dados históricos o modelo poderá utilizar. Mudar qualquer um deles altera o teste.</small>
 </div>
 <details class="tf-bt-options" <?=$btShowAll?'open':''?>><summary>Mostrar mais opções · fonte, conjunto e exibição</summary>
  <div class="tf-form-advanced">
   <label for="bt-fonte">De onde vêm os dados?
    <select name="fonte" id="bt-fonte"><?php foreach(TemporisFinal::FONTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btSrc===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
   </label>
   <label for="bt-coorte">Quais marcos entram no teste?
    <select name="coorte" id="bt-coorte"><?php foreach(TemporisFinal::COORTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btCoorte===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
   </label>
   <label class="tf-check-inline"><input type="checkbox" name="mostrar_todos" value="1" <?=$btShowAll?'checked':''?>> Mostrar comparação detalhada de todos os modelos</label>
  </div>
 </details>
</form>
<script>
(function(){
  'use strict';
  const horizon=document.getElementById('bt-horizonte');
  const cut=document.getElementById('bt-corte');
  const expl=document.getElementById('bt-explicacao-corte');
  if(!horizon||!cut||!expl)return;
  function update(){
    const years=Number(horizon.value);
    const last=Number(cut.dataset.lastObserved);
    for(const opt of cut.options){
      if(opt.value==='todos')continue;
      opt.disabled=!!last&&(Number(opt.value)+years>last);
    }
    if(cut.selectedOptions[0]?.disabled)cut.value='todos';
    expl.textContent=cut.value==='todos'
      ?'O MAT repete a previsão em vários anos históricos, cada uma com '+years+' anos de duração, e resume os erros.'
      :'O modelo considera somente dados até '+cut.value+' e prevê '+(Number(cut.value)+years)+' ('+years+' anos depois).';
  }
  horizon.addEventListener('change',update);
  cut.addEventListener('change',update);
  update();
})();
</script>
<?php if($btAvisoCorte): ?>
<div class="mat-final-warning" role="status"><strong>Período não disponível para backtesting:</strong> <?=h($btAvisoCorte)?></div>
<?php endif; ?>

<?php if($btError): ?>
<div class="mat-final-warning" role="alert"><strong>Não foi possível calcular as previsões.</strong> <?=h($btError)?>. <a href="<?=mat_url('backtesting',['fonte'=>'snapshot','coorte'=>$btCoorte,'horizonte'=>$btHoriz])?>">Consultar a exportação histórica disponível</a>.</div>
<?php else: ?>
<div class="tf-bt-data-line" role="note">
 <span><strong>Dados utilizados:</strong> <?=h($btFonteReal==='snapshot'?'exportação histórica (não atualiza com o MySQL)':($btFonteReal==='curadoria'?'curadoria ativa (MySQL)':'base isolada do MAT Final (MySQL)'))?></span>
 <span><strong><?=$btCount?></strong> marcos no teste, de <strong><?= (int)($btAudit['total']??0) ?></strong> candidatos</span>
</div>

<?php if($btCorte!==null): ?>
<section class="paper-panel mat-final-section" style="border-color:#194a40;background:#eef4ee">
 <div class="panel-head"><span>CORTE ESCOLHIDO · MODO ÚNICO</span><span>UM ÚNICO TESTE HISTÓRICO</span></div>
 <h2 style="margin-top:4px">Previsão a partir de <strong><?=$btCorte?> → <?=$btCorte+$btHoriz?></strong></h2>
 <p>Cada modelo usa <strong>somente</strong> os dados disponíveis até <strong><?=$btCorte?></strong> para prever o crescimento até <strong><?=$btCorte+$btHoriz?></strong>, que é comparado com o crescimento realmente observado. Como é um único corte, o percentual de erro se refere a esse teste isolado — não é uma média histórica; para a visão agregada, volte o corte para “Todos os cortes”.</p>
</section>
<?php endif; ?>

<section class="tf-bt-result" aria-labelledby="bt-resultado-titulo">
 <div class="tf-bt-result-main">
  <span class="tf-bt-kicker">01 / SEU MÉTODO EM DESTAQUE</span>
  <h2 id="bt-resultado-titulo">Qual é o seu método e como ele se compara?</h2>
  <p><strong>Metodologia do índice (sua base):</strong> peso <strong>Impacto quadrático (adotada)</strong> com índice <strong>acumulado sem compressão</strong> (MAT Final). O backtesting abaixo compara <strong>modelos de previsão</strong> do estoque K sobre essa base (seleção atual).</p>
  <?php if($btRefRow): ?>
    <p><strong>Seu modelo de referência (previsão):</strong> <?=h($btRefRow['label'])?>.
    <?php if($btRefRow['stat']['wape']!==null): ?>
      Erro agregado (WAPE): <strong><?=$btFmt($btRefRow['stat']['wape'])?>%</strong><?php if($btPosRank): ?> — <strong><?=$btPosRank?>º</strong> de <?=count($btRanked)?> entre os calculáveis<?php endif; ?> (<?=$btRefRow['stat']['n']?> testes).
    <?php else: ?>
      <strong>Sem WAPE calculável</strong> nesta seleção/horizonte (<?=$btRefRow['stat']['n']?> testes válidos).
    <?php endif; ?></p>
  <?php endif; ?>
  <p class="tf-bt-interpretation">Menor WAPE indica menor erro relativo no teste retrospectivo; não garante desempenho futuro nem valida o índice por si só.</p>
 </div>
 <div class="tf-bt-result-number">
  <span>Erro do seu método</span>
  <strong><?=($btRefRow && $btRefRow['stat']['wape']!==null)?$btFmt($btRefRow['stat']['wape']).'%':'—'?></strong>
  <small><?=h($btRefRow['label']??'Modelo de referência')?> · horizonte <?=$btHoriz?> anos</small>
 </div>
</section>

<div class="tf-bt-facts" aria-label="Resumo da avaliação">
 <div><span>Seu método</span><strong><?=h($btRefRow['label']??'—')?></strong><small><?=($btRefRow && $btRefRow['stat']['wape']!==null)?$btFmt($btRefRow['stat']['wape']).'% WAPE':'WAPE indisponível'?></small></div>
 <div><span>Melhor no teste</span><strong><?=h($btBest['label']??'—')?></strong><small><?=$btBest?$btFmt($btBest['stat']['wape']).'% WAPE':'—'?></small></div>
 <div><span>Pior no teste</span><strong><?=h($btWorst['label']??'—')?></strong><small><?=$btWorst?$btFmt($btWorst['stat']['wape']).'% WAPE':'—'?></small></div>
 <div><span>Modelos com resultado</span><strong><?=count($btRanked)?> de <?=count($btRows)?></strong></div>
</div>

<section class="paper-panel mat-final-section tf-bt-simple" aria-labelledby="bt-comparacao-titulo">
 <div class="panel-head"><span>02 / COMPARAÇÃO ESSENCIAL</span><span>SEU MÉTODO · MELHOR · PIOR</span></div>
 <h2 id="bt-comparacao-titulo">Comparação direta</h2>
 <p>Veja primeiro o seu método e os extremos do teste. Os demais modelos ficam ocultos por padrão para reduzir confusão.</p>
 <?php
 $btEssential=[]; $seen=[];
 foreach([['Meu método (referência)',$btRefRow],['Melhor desempenho',$btBest],['Pior desempenho',$btWorst]] as $pair){
   [$tag,$row]=$pair; if(!$row) continue; $key=$tag.':'.$row['id']; if(isset($seen[$key])) continue; $seen[$key]=true; $btEssential[]=['tag'=>$tag,'row'=>$row];
 }
 ?>
 <?php if($btEssential): ?>
 <div class="tf-bt-shortlist">
  <?php $refWape=max(1.,(float)($btWorst['stat']['wape']??($btBest['stat']['wape']??1))); foreach($btEssential as $i=>$item):$row=$item['row'];$stat=$row['stat'];?>
  <div class="tf-bt-short-row <?=$row['id']===$btRef?'tf-bt-short-first':''?>">
   <div class="tf-bt-short-title"><span><?=h($item['tag'])?>:</span><strong><?=h($row['label'])?></strong>
    <?php if($row['id']===$btRef):?><small>seu método</small><?php endif; ?>
    <?php if($btBest && $row['id']===$btBest['id']):?><small>menor erro</small><?php endif; ?>
    <?php if($btWorst && $row['id']===$btWorst['id']):?><small>maior erro (calculável)</small><?php endif; ?>
   </div>
   <div class="tf-bt-short-meter" aria-hidden="true"><span style="width:<?=($stat['wape']===null)?3:max(3.,min(100.,100.*(float)$stat['wape']/$refWape))?>%"></span></div>
   <div class="tf-bt-short-value"><strong><?=$stat['wape']===null?'—':$btFmt($stat['wape']).'%'?></strong><small><?=$stat['n']?> testes</small></div>
  </div>
  <?php endforeach; ?>
 </div>
 <p class="tf-bt-hint">Barras menores indicam menos erro (WAPE). <?php if($btDifferentSamples):?><strong>Atenção:</strong> os modelos podem ter números diferentes de testes válidos; compare com cautela.<?php endif; ?> Para ver todos os <?=count($btRows)?> modelos, marque “Mostrar comparação detalhada de todos os modelos” e aplique o filtro.</p>
 <?php else: ?><p>Nenhum modelo apresentou WAPE calculável nesta seleção.</p><?php endif; ?>
</section>

<?php if($btSample): ?>
<section class="tf-bt-example" aria-labelledby="bt-exemplo-titulo">
 <div><span class="tf-bt-kicker">03 / EXEMPLO CONCRETO</span><h2 id="bt-exemplo-titulo">O que foi previsto × o que aconteceu?</h2>
 <p>Em um dos testes do modelo <strong><?=h($btSampleBase['label'] ?? ($btBest['label'] ?? '—'))?></strong> (<?=($btSampleBase && $btRefRow && $btSampleBase['id']===$btRefRow['id'])?'seu método de referência':'destaque do teste'?>), usamos os dados disponíveis até <strong><?=$btSample['corte']?></strong> para estimar o crescimento acumulado até <strong><?=$btSample['alvo']?></strong>.</p></div>
 <div class="tf-bt-actual-predicted"><div><span>Previsão de crescimento</span><strong><?=$btFmt($btSample['crescimento_previsto'],2)?></strong><small>unidades do estoque K</small></div><div><span>Crescimento observado</span><strong><?=$btFmt($btSample['crescimento_real'],2)?></strong><small>unidades do estoque K</small></div></div>
 <p class="tf-bt-example-footnote">Diferença neste exemplo: <strong><?=$btFmt(abs($btSample['erro']),2)?> unidades de K</strong> (<?=$btSample['erro']>0?'previsão acima do observado':($btSample['erro']<0?'previsão abaixo do observado':'previsão igual ao observado')?>). K é a soma dos pesos dos marcos, não a quantidade de marcos. Este é apenas um corte histórico, não o erro percentual geral.</p>
</section>
<?php endif; ?>

<?php if($btShowAll): ?>
<details class="tf-expander tf-bt-more" id="bt-todos-modelos" open><summary><span><strong>Comparação detalhada de todos os <?=count($btRows)?> modelos</strong><small>Tabela completa com erro percentual, erro médio, tendência de superestimar ou subestimar e testes válidos</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body">
 <p><strong>Como ler:</strong> WAPE é o erro percentual agregado (menor costuma ser melhor). MAE mede o erro médio em unidades de K. Viés positivo significa tendência a prever acima do observado; negativo, abaixo. “—” significa que não foi possível calcular, não que o erro foi zero. Etiquetas: <em>seu método</em>, <em>melhor</em> e <em>pior</em> (entre os calculáveis).</p>
 <div class="mat-final-table-wrap"><table class="mat-final-table tf-bt-table"><thead><tr><th>Modelo</th><th>Erro (WAPE)</th><th>MAE (K)</th><th>Viés médio (K)</th><th>Testes válidos</th></tr></thead><tbody>
 <?php foreach($btRows as $row):$stat=$row['stat'];?><tr class="<?=$row['id']==='sem_avanco'?'tf-control-row':''?><?=$row['id']===$btRef?' tf-ref-row':''?>"><th><?=h($row['label'])?><?=$row['id']==='sem_avanco'?' · controle':''?><?=$row['id']===$btRef?' · seu método':''?><?=$btBest&&$row['id']===$btBest['id']?' · melhor':''?><?=$btWorst&&$row['id']===$btWorst['id']?' · pior':''?></th><td><?=$stat['wape']===null?'—':$btFmt($stat['wape']).'%'?></td><td><?=$btFmt($stat['mae'],3)?></td><td><?=$btFmt($stat['bias'],3)?></td><td><?=$stat['n']?></td></tr><?php endforeach;?></tbody></table></div>
 <p class="mat-final-disclaimer">A classificação por WAPE não é um ranking universal: pode depender do horizonte, da seleção dos marcos e do número de testes válidos.</p>
 </div>
</details>

<details class="tf-expander tf-bt-more"><summary><span><strong>Conferir cada previsão histórica</strong><small>Veja, ano a ano, quanto cada modelo previu e o que foi observado</small></span><span class="tf-chevron" aria-hidden="true">⌄</span></summary>
 <div class="tf-expand-body">
 <?php foreach($btRows as $row):$folds=$row['stat']['folds'];?><details class="tf-inline-details"><summary><?=h($row['label'])?> · <?=count($folds)?> testes</summary>
 <?php if(!$folds):?><p>Não houve dados históricos suficientes para testar este modelo neste horizonte.</p><?php else:?><div class="mat-final-table-wrap"><table class="mat-final-table"><thead><tr><th>Ano da previsão</th><th>Ano avaliado</th><th>Crescimento observado ΔK</th><th>Crescimento previsto ΔK</th><th>Diferença (com sinal)</th></tr></thead><tbody>
 <?php foreach($folds as $fold):?><tr><td><?=$fold['corte']?></td><td><?=$fold['alvo']?></td><td><?=$btFmt($fold['crescimento_real'],4)?></td><td><?=$btFmt($fold['crescimento_previsto'],4)?></td><td><?=$btFmt($fold['erro'],4)?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></details><?php endforeach; ?>
 </div>
</details>
<?php else: ?>
<p class="tf-bt-hint" style="margin:1rem 0">Para reduzir confusão, os demais modelos e as tabelas detalhadas estão ocultos. <a href="<?=mat_url('backtesting',['fonte'=>$btSrc,'coorte'=>$btCoorte,'horizonte'=>$btHoriz,'referencia'=>$btRef,'mostrar_todos'=>1])?>#bt-todos-modelos">Mostrar todos os modelos e previsões históricas</a>.</p>
<?php endif; ?>

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
