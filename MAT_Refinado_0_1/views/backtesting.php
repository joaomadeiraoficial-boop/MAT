<?php
/** Backtesting do MAT Final. Consulta somente leitura; os resultados são exploratórios. */
require_once MAT_ROOT.'/app/TemporisMetodologia2026.php';
// O teste inicial abre na base 07/11 (snapshot) para reproduzir exatamente os
// números da aba "★ Melhores previsões · 50 anos" (0,19% · 5,92% · 6,08%).
// O banco vivo da curadoria continua acessível escolhendo a fonte nas opções.
$btSrc=(string)($_GET['fonte']??'snapshot');
if(!isset(TemporisFinal::FONTES[$btSrc]))$btSrc='snapshot';
$btCoorte=(string)($_GET['coorte']??'ampla');
if(!isset(TemporisFinal::COORTES[$btCoorte]))$btCoorte='ampla';
$btHoriz=filter_var($_GET['horizonte']??50,FILTER_VALIDATE_INT);
if(!in_array($btHoriz,[10,25,50,100],true))$btHoriz=50;
$btRef=(string)($_GET['referencia']??'exponencial50');
if(!isset(TemporisFinal::MODELOS[$btRef]))$btRef='exponencial50';
$btShowAll=($_GET['mostrar_todos']??'')==='1';
$btCortePed=(string)($_GET['corte']??'todos');
$btCorte=null;
if(ctype_digit($btCortePed)&&in_array((int)$btCortePed,TemporisFinal::CORTES_FIXOS,true))$btCorte=(int)$btCortePed;
$btError=null;$btRows=[];$btComparacao=[];$btProgressiva=[];$btAudit=[];$btSource='—';$btFonteReal='';$btCount=0;
$btFmt=static fn(?float $n,int $digits=1):string=>$n===null?'—':number_format($n,$digits,',','.');
try {
    $btResult=TemporisFinal::obterDados($btSrc,2026);
    $btAudit=$btResult['dados']['audit'];$btSource=$btResult['database'];$btFonteReal=$btResult['fonte'];
    $btPoints=TemporisFinal::filtrar($btResult['dados']['pontos'],$btCoorte);
    $btCount=count($btPoints);
    $btLine=TemporisFinal::linha($btPoints,'quadratica');
    $btComparacao=TemporisMetodologia2026::compararMesmosCortes($btLine,$btHoriz,2026);
    $btProgressiva=TemporisMetodologia2026::selecaoProgressiva($btLine,$btHoriz,2026);
    foreach(TemporisFinal::MODELOS as $id=>$label){
        $stat=$btCorte!==null
            ? TemporisFinal::validarCorte($btLine,$id,$btHoriz,$btCorte)
            : TemporisFinal::validar($btLine,$id,$btHoriz,2026);
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
// Cortes individuais do modelo de referência com a MESMA métrica da aba de destaques
// (erro no crescimento = |Δ previsto − Δ observado| / Δ observado).
$btCortes=[];
if(!$btError && $btRefRow){
    foreach($btRefRow['stat']['folds'] as $f){
        $real=(float)($f['crescimento_real']??0);
        if($real<=0)continue;
        $kCorte=TemporisFinal::k($btLine,(int)$f['corte']);
        $btCortes[]=['corte'=>(int)$f['corte'],'alvo'=>(int)$f['alvo'],
            'prev'=>(float)$f['crescimento_previsto'],'real'=>$real,
            'erro_pct'=>100*abs((float)$f['erro'])/$real,
            'aumento'=>$kCorte>0?100*$real/$kCorte:null];
    }
    usort($btCortes,static fn($a,$b)=>$a['erro_pct']<=>$b['erro_pct']);
}
$btOrigemLabel=$btFonteReal==='snapshot'?'exportação histórica (não atualiza com o MySQL)':($btFonteReal==='curadoria'?'curadoria ativa (MySQL)':($btFonteReal!==''?'base isolada do MAT Final (MySQL)':'—'));
$btWorstCortes=$btCortes?array_slice($btCortes,-3):[];
$btPiorCorte=$btCortes?$btCortes[count($btCortes)-1]:null;
$btCorteAlvoTxt=$btCorte===null?'todos os cortes fixos':($btCorte.' → '.($btCorte+$btHoriz));
$btModeloDesc=[
 'sem_avanco'=>'Controle: não prevê crescimento nenhum; régua de comparação.',
 'linear50'=>'Extrapola a velocidade média dos últimos 50 anos.',
 'linear100'=>'Extrapola a velocidade média dos últimos 100 anos.',
 'exponencial50'=>'Extrapola o crescimento composto dos últimos 50 anos — modelo do snapshot e da aba “★ Melhores previsões · 50 anos”.',
 'exponencial100'=>'Extrapola o crescimento composto dos últimos 100 anos.',
 'aceleracao'=>'Usa a variação da velocidade local (acelerando ou desacelerando).',
 'amortecida'=>'Crescimento linear que perde metade da força a cada 50 anos (cenário).',
 'mediana'=>'Valor do meio entre linear50, linear100 e exponencial50.',
 'impeto'=>'Testa crescimento dependente do estoque: dK/dt = a·K^β (experimental).',
 'logistico'=>'Crescimento que desacelera ao se aproximar de um teto (experimental).',
 'gompertz'=>'Variante do logístico com curva assimétrica (experimental).'];
?>
<div class="mat-bt" id="mat-backtesting">

  <header class="mat-bt-hero">
    <div class="mat-bt-hero-top">
      <span class="mat-bt-eyebrow"><span class="mat-bt-dot" aria-hidden="true"></span>ÍNDICE TÊMPORIS / BACKTESTING · TESTE DAS PREVISÕES</span>
      <span class="mat-bt-year">EDIÇÃO DE PESQUISA · MMXXVI</span>
    </div>
    <div class="mat-bt-hero-body">
      <div>
        <h1>Backtesting · o quanto as previsões <em>acertaram?</em></h1>
        <p>Cada modelo recebe <strong>somente os dados anteriores ao corte</strong>, projeta o crescimento bruto ΔK e confronta o registro do acervo. O WAPE resume o erro retrospectivo — não é margem garantida para o futuro.</p>
      </div>
      <div class="mat-bt-hero-mark" aria-hidden="true"><span><?=$btHoriz?></span><small>ANOS · HORIZONTE</small></div>
    </div>
<?php if(!$btError): ?>
    <div class="mat-bt-hero-bottom">
      <span><?=h($btOrigemLabel)?></span>
      <span>Banco <code><?=h($btSource)?></code></span>
      <span><?=$btCount?> marcos · de <?= (int)($btAudit['total']??0) ?> candidatos</span>
    </div>
<?php endif; ?>
  </header>

  <nav class="mat-bt-nav" aria-label="Ir para as seções do teste">
    <a href="#bt-resumo">Resumo</a>
    <a href="#bt-ranking">Ranking dos modelos</a>
    <a href="#bt-cortes">Corte a corte</a>
    <a href="#bt-exemplo">Exemplo concreto</a>
    <a href="#bt-metodo">Como funciona</a>
  </nav>

  <form class="mat-bt-console" method="get" action="index.php">
   <input type="hidden" name="page" value="backtesting">
   <div class="mat-bt-console-head"><strong>Console do teste</strong><span>Escolha os parâmetros; todos os modelos são avaliados nos mesmos cortes fixos</span></div>
   <div class="mat-bt-console-fields">
    <label for="bt-horizonte"><span>Horizonte</span>
     <select name="horizonte" id="bt-horizonte"><?php foreach([10,25,50,100] as $h):?><option value="<?=$h?>" <?=$btHoriz===$h?'selected':''?>><?=$h?> anos</option><?php endforeach;?></select>
    </label>
    <label for="bt-corte"><span>Corte inicial</span>
     <select name="corte" id="bt-corte">
      <option value="todos" <?=$btCorte===null?'selected':''?>>Todos os cortes (padrão)</option>
<?php foreach(TemporisFinal::CORTES_FIXOS as $y):?>
      <option value="<?=$y?>" <?=$btCorte===$y?'selected':''?>><?=$y?> → <?=$y+$btHoriz?></option>
<?php endforeach;?>
     </select>
    </label>
    <label for="bt-referencia"><span>Modelo de referência</span>
     <select name="referencia" id="bt-referencia"><?php foreach(TemporisFinal::MODELOS as $id=>$label):?><option value="<?=h($id)?>" <?=$btRef===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
    </label>
    <label class="mat-bt-check"><input type="checkbox" name="mostrar_todos" value="1" <?=$btShowAll?'checked':''?>> Tabelas completas</label>
    <button class="mat-bt-run" type="submit">Ver resultado →</button>
   </div>
   <details class="mat-bt-details mat-bt-options" style="margin-top:15px;box-shadow:none"><summary>Fonte dos dados e coorte de marcos</summary>
    <div class="mat-bt-details-body" style="padding-top:14px">
     <div class="mat-bt-console-fields" style="margin-bottom:12px">
      <label for="bt-fonte"><span>Fonte</span>
       <select name="fonte" id="bt-fonte"><?php foreach(TemporisFinal::FONTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btSrc===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
      </label>
      <label for="bt-coorte"><span>Coorte</span>
       <select name="coorte" id="bt-coorte"><?php foreach(TemporisFinal::COORTES as $id=>$label):?><option value="<?=h($id)?>" <?=$btCoorte===$id?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
      </label>
     </div>
     <p style="margin:0;font-size:.8rem;color:#78877d">O padrão é a <strong>exportação histórica de 07/11</strong> (a mesma da aba “★ Melhores previsões”); escolha <em>curadoria (MySQL)</em> para testar no banco vivo. Nada é alterado em nenhuma fonte.</p>
    </div>
   </details>
  </form>

<?php if($btError): ?>
  <div class="mat-bt-warning" role="alert"><strong>Não foi possível calcular as previsões.</strong> <?=h($btError)?>. <a href="<?=mat_url('backtesting',['fonte'=>'snapshot','coorte'=>$btCoorte,'horizonte'=>$btHoriz])?>">Consultar a exportação histórica disponível</a>.</div>
<?php else: ?>

  <section id="bt-resumo" class="mat-bt-section" aria-labelledby="bt-resumo-t">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">01 / RESUMO DO TESTE · <?=h($btOrigemLabel)?></span><h2 id="bt-resumo-t">Veredito dos modelos <em>nesta seleção.</em></h2></div><span class="mat-bt-source-pill"><?=$btHoriz?> anos · <?=$btCorteAlvoTxt?></span></div>
    <div class="mat-bt-cards">
      <div class="mat-bt-card mat-bt-card-lead">
        <span class="mat-bt-card-label">Modelo de referência · erro agregado (WAPE)</span>
        <div class="mat-bt-card-number"><?=($btRefRow&&$btRefRow['stat']['wape']!==null)?$btFmt($btRefRow['stat']['wape']).'<span>%</span>':'—'?></div>
        <strong><?=h($btRefRow['label']??'—')?></strong>
        <p><?php if($btRefRow&&$btRefRow['stat']['wape']!==null):?><?=($btPosRank?'<b>'.$btPosRank.'º</b> de '.count($btRanked).' modelos calculáveis · ':'')?><?=$btRefRow['stat']['n']?> testes válidos.<?php else:?><strong>Sem WAPE calculável</strong> nesta seleção (<?=$btRefRow?$btRefRow['stat']['n']:0?> testes válidos).<?php endif;?></p>
        <div class="mat-bt-card-bottom">Coorte: <?=h(TemporisFinal::COORTES[$btCoorte])?></div>
      </div>
      <div class="mat-bt-card">
        <span class="mat-bt-card-label">Melhor no teste · menor WAPE</span>
        <div class="mat-bt-card-number"><?=$btBest?$btFmt($btBest['stat']['wape']).'<span>%</span>':'—'?></div>
        <strong><?=h($btBest['label']??'—')?></strong>
        <p><?=($btBest['stat']['n']??0)?> cortes válidos<?php if($btBest&&$btBest['id']===$btRef):?> — é o próprio modelo de referência<?php endif; ?>.</p>
        <div class="mat-bt-card-bottom">Melhor média geral ≠ melhores cortes individuais</div>
      </div>
      <div class="mat-bt-card mat-bt-card-worst">
        <span class="mat-bt-card-label">Pior no teste · maior WAPE</span>
        <div class="mat-bt-card-number"><?=$btWorst?$btFmt($btWorst['stat']['wape']).'<span>%</span>':'—'?></div>
        <strong><?=h($btWorst['label']??'—')?></strong>
        <p><?=$btWorst?'Registro completo: o controle “sem crescimento” fica em 100% por construção, pois não prevê avanço nenhum.':''?></p>
        <div class="mat-bt-card-bottom">Nada é escondido: os piores também ficam registrados</div>
      </div>
    </div>
    <div class="mat-bt-verdict" role="note">
      <strong>COMO LER ESTE TESTE</strong>
      <ul>
        <li><b>WAPE</b> é o erro percentual médio dos incrementos retrospectivos; <b>menor costuma ser melhor</b>. Nenhum ranking comprova previsão futura ou qualidade dos marcos.</li>
        <li>Os cortes têm origem fixa (25 em 25 anos, até o último ano observado); <b>nenhum corte é escolhido pelo desempenho</b>.</li>
        <li>A mesma métrica, corte a corte, está na aba <a href="<?=mat_url('destaques_previsoes')?>">★ Melhores previsões · 50 anos</a> — e o <b>melhor modelo médio pode não ser o dono dos melhores cortes individuais</b>.</li>
      </ul>
    </div>
  </section>

  <section id="bt-ranking" class="mat-bt-section" aria-labelledby="bt-ranking-t">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">02 / RANKING DOS MODELOS · TODOS OS CORTES DA SELEÇÃO</span><h2 id="bt-ranking-t">Os <?=count($btRows)?> modelos, do menor ao maior erro</h2></div><span class="mat-bt-source-pill"><?=$btHoriz?> anos · WAPE</span></div>
    <p class="mat-bt-intro">Barras <strong>menores</strong> indicam <strong>menos erro</strong>. A linha dourada é o modelo de referência escolhido no console; a linha verde é o melhor no geral.<?php if($btDifferentSamples):?> <strong>Atenção:</strong> os modelos têm números diferentes de testes válidos; compare com cautela.<?php endif; ?></p>
    <div class="mat-bt-rank">
<?php $btMaxWape=max(1.,(float)($btWorst['stat']['wape']??($btBest['stat']['wape']??1)));
foreach($btRanked as $i=>$row): $btW=(float)$row['stat']['wape']; ?>
      <div class="mat-bt-rank-row <?=$i===0?'mat-bt-rank-first':''?><?=$row['id']===$btRef?' mat-bt-rank-ref':''?><?=$i===count($btRanked)-1?' mat-bt-rank-last':''?>">
        <div class="mat-bt-rank-pos"><?=$i+1?>º<?=$i===0?'<small>melhor no geral</small>':''?></div>
        <div class="mat-bt-rank-name"><strong><?=h($row['label'])?></strong><small><?=$row['id']==='sem_avanco'?'controle · não prevê avanço':($row['id']===$btRef?'modelo de referência':($btModeloDesc[$row['id']]??''))?></small></div>
        <div class="mat-bt-rank-meter" aria-hidden="true"><span style="width:<?=max(3.,min(100.,100.*$btW/$btMaxWape))?>%"></span></div>
        <div class="mat-bt-rank-val"><strong><?=$btFmt($btW)?>%</strong><small><?=$row['stat']['n']?> testes</small></div>
      </div>
<?php endforeach;
foreach($btRows as $row){ if($row['stat']['wape']!==null) continue; ?>
      <div class="mat-bt-rank-row">
        <div class="mat-bt-rank-pos">—</div>
        <div class="mat-bt-rank-name"><strong><?=h($row['label'])?></strong><small>sem WAPE calculável nesta seleção</small></div>
        <div class="mat-bt-rank-meter" aria-hidden="true"><span style="width:3%"></span></div>
        <div class="mat-bt-rank-val"><strong>—</strong><small><?=$row['stat']['n']?> testes</small></div>
      </div>
<?php } ?>
    </div>
    <p class="mat-bt-table-note">A classificação por WAPE não é um ranking universal: pode depender do horizonte, da seleção dos marcos e do número de testes válidos. Consulta somente leitura; nada é alterado nem aprovado.</p>
  </section>

  <?php if($btCortes): ?>
  <section id="bt-cortes" class="mat-bt-section" aria-labelledby="bt-cortes-t">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">03 / CORTE A CORTE · MODELO DE REFERÊNCIA</span><h2 id="bt-cortes-t">Cada teste de <em><?=h($btRefRow['label']??'—')?></em></h2></div><span class="mat-bt-source-pill">Mesma métrica da aba de destaques</span></div>
    <p class="mat-bt-intro">Cada linha é um corte histórico de <strong><?=$btHoriz?> anos</strong>: o <strong>erro no crescimento</strong> compara o crescimento previsto com o observado do próprio corte. Ordenado do menor para o maior erro — os três piores ficam <strong style="color:#b3261e">em vermelho</strong>, nada escondido.</p>
    <div class="mat-bt-table-wrap"><table class="mat-bt-table">
      <thead><tr><th>Corte → alvo</th><th>ΔK previsto</th><th>ΔK observado</th><th>Erro no crescimento</th><th>Aumento real de K</th></tr></thead>
      <tbody>
      <?php $btWorstSet=[]; foreach($btWorstCortes as $w){$btWorstSet[$w['corte']]=true;}
      foreach($btCortes as $c): $btIsWorst=isset($btWorstSet[$c['corte']]); ?>
        <tr <?=$c===$btCortes[0]?'class="mat-bt-featured-row"':($btIsWorst?'class="mat-bt-worst-row"':'')?>><td><b><?= $c['corte'] ?> → <?= $c['alvo'] ?></b><?=$c===$btCortes[0]?'<span class="mat-bt-row-mark">Melhor corte</span>':''?></td><td><?=$btFmt($c['prev'],2)?></td><td><?=$btFmt($c['real'],2)?></td><td><?=($btIsWorst?'<span class="mat-bt-erro-worst">':'<span class="mat-bt-result">').$btFmt($c['erro_pct'],2).'%</span>'?></td><td><?=($c['aumento']===null?'—':'<span class="mat-bt-growth">+'.$btFmt($c['aumento'],2).'%</span>')?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <div class="mat-bt-strip" style="grid-template-columns:repeat(3,minmax(0,1fr))">
      <div><small>Melhor corte individual</small><b><?=$btFmt($btCortes[0]['erro_pct'],2)?>%</b></div>
      <div><small>Pior corte individual</small><b style="color:#b3261e"><?=($btPiorCorte?$btFmt($btPiorCorte['erro_pct'],2).'%':'—')?></b></div>
      <div><small>Modelo de referência</small><b><?=($btRefRow&&$btRefRow['stat']['wape']!==null?$btFmt($btRefRow['stat']['wape']).'%':'—')?></b></div>
    </div>
    <p class="mat-bt-table-note">Nenhum dos cortes, isoladamente, comprova ou refuta o modelo; o resumo é o WAPE de todos. Os cortes têm origem fixa; nenhum é escolhido pelo desempenho. <?php if($btHoriz===50): ?><a href="<?=mat_url('destaques_previsoes')?>">Abrir a aba “★ Melhores previsões · 50 anos” ↗</a> · <?php endif; ?><a href="<?=mat_url('backtesting',['fonte'=>'snapshot','coorte'=>'ampla','horizonte'=>50,'referencia'=>'exponencial50'])?>">Reproduzir a base histórica de 07/11 (667 marcos) ↗</a></p>
  </section>
  <?php endif; ?>

  <?php if($btSample): ?>
  <section id="bt-exemplo" class="mat-bt-section" aria-labelledby="bt-exemplo-t">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">04 / EXEMPLO CONCRETO · UM ÚNICO TESTE HISTÓRICO</span><h2 id="bt-exemplo-t">O que foi previsto × o que <em>aconteceu?</em></h2></div><span class="mat-bt-source-pill"><?=$btSample['corte']?> → <?=$btSample['alvo']?></span></div>
    <div class="mat-bt-example-grid">
      <div>
        <p style="margin:0"><strong>Modelo:</strong> <?=h($btSampleBase['label'] ?? ($btBest['label'] ?? '—'))?> (<?=($btSampleBase && $btRefRow && $btSampleBase['id']===$btRefRow['id'])?'modelo de referência':'destaque do teste'?>). Com os dados disponíveis <strong>somente até <?=$btSample['corte']?></strong>, o modelo estimou o crescimento acumulado de K até <strong><?=$btSample['alvo']?></strong>.</p>
        <p>Diferença neste teste: <strong><?=$btFmt(abs($btSample['erro']),2)?> unidades de K</strong> (<?=$btSample['erro']>0?'previsão acima do observado':($btSample['erro']<0?'previsão abaixo do observado':'previsão igual ao observado')?>). K é a soma dos pesos dos marcos, não a quantidade de marcos. Este é apenas um corte histórico, não o erro percentual geral.</p>
        <div class="mat-bt-emphasis"><span class="mat-bt-emphasis-icon">※</span><p>Um único corte <strong>não valida nem rejeita</strong> o modelo — por isso o teste usa todos os cortes fixos.</p></div>
      </div>
      <div class="mat-bt-actual-predicted">
        <div><span>Previsão de crescimento</span><strong><?=$btFmt($btSample['crescimento_previsto'],2)?></strong><small>unidades do estoque K</small></div>
        <div><span>Crescimento observado</span><strong><?=$btFmt($btSample['crescimento_real'],2)?></strong><small>unidades do estoque K</small></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section id="bt-metodo" class="mat-bt-section" aria-labelledby="bt-metodo-t">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">05 / MÉTODO, VALIDAÇÃO E LOTES · TUDO SOB DEMANDA</span><h2 id="bt-metodo-t">Como o teste <em>funciona?</em></h2></div><span class="mat-bt-source-pill">Consulta somente leitura</span></div>

    <details class="mat-bt-details"><summary><strong>Como o teste funciona e quais são os limites?</strong></summary>
     <div class="mat-bt-details-body">
      <h3>Procedimento</h3>
      <p>Em cada corte histórico de 25 em 25 anos, começando em 1700, o programa usa o estoque acumulado K até aquele ano para projetar o crescimento nos próximos <?=$btHoriz?> anos. Em seguida, compara a estimativa com a variação de K registrada na base escolhida. O método atual usa pesos quadráticos para construir K.</p>
      <p><strong>Fonte efetivamente utilizada:</strong> <?=h($btSource)?>. <strong>Coorte:</strong> <?=h(TemporisFinal::COORTES[$btCoorte])?>. <strong>Marcos selecionados:</strong> <?=$btCount?> de <?= (int)($btAudit['total']??0) ?> candidatos cadastrados. O programa não altera nem aprova marcos durante o teste.</p>
      <h3>Validação com os mesmos cortes (amostra comum de quatro métodos)</h3>
      <p>Linear e exponencial com janelas de 50 e 100 anos, avaliados nos mesmos <?=$btComparacao['n']??0?> cortes, independentemente de quantos outros testes cada algoritmo permitiria.</p>
      <div class="mat-bt-table-wrap"><table class="mat-bt-table"><thead><tr><th>Modelo</th><th>WAPE</th><th>MAE bruto</th><th>Cortes iguais</th></tr></thead><tbody>
      <?php foreach(($btComparacao['modelos']??[]) as $id=>$v):?><tr><td><?=h($v['nome'])?></td><td><?=$btFmt($v['wape'])?><?=$v['wape']===null?'':'%'?></td><td><?=$btFmt($v['mae'])?></td><td><?=$v['n']?></td></tr><?php endforeach;?></tbody></table></div>
      <p><strong>Teste de seleção progressiva:</strong> <?=($btProgressiva['n']??0)?> cortes avaliados depois de escolher o modelo com base apenas nos resultados históricos anteriores. WAPE de seleção: <?=($btProgressiva['wape']??null)===null?'não calculável':$btFmt($btProgressiva['wape']).'%'?>. Não é validação prospectiva: os marcos foram pontuados retrospectivamente.</p>
      <h3>O que este resultado não prova?</h3>
      <p>Este é um experimento retrospectivo exploratório. A base histórica e as notas foram organizadas depois de muitos dos acontecimentos avaliados; por isso, não é uma previsão feita de verdade naquela época. Resultados melhores não demonstram, por si só, validade externa do Índice Têmporis, nem garantem que o mesmo modelo será superior no futuro.</p>
      <p>Para aprofundar a validação científica, é necessário revisar datas e fontes independentes, testar a estabilidade dos pesos, possíveis duplicatas, períodos históricos diferentes e a distribuição dos marcos. O WAPE calculado é relativo ao crescimento de K, não diretamente aos pontos normalizados de Têmporis. Documentação completa: <a href="docs/previsoes-50-anos/METODO.md">docs/previsoes-50-anos/METODO.md</a>.</p>
     </div>
    </details>

    <details class="mat-bt-details"><summary><strong>Os <?=count(TemporisFinal::MODELOS)?> modelos disponíveis no teste</strong></summary>
     <div class="mat-bt-details-body">
      <ul>
<?php foreach($btModeloDesc as $id=>$desc): ?>
       <li><strong><?=h(TemporisFinal::MODELOS[$id])?></strong> — <?=h($desc)?><?=$id==='exponencial50'?' <em>· modelo do snapshot e da aba de destaques</em>':''?></li>
<?php endforeach; ?>
      </ul>
      <p style="margin-bottom:0">Todos são avaliados nos mesmos cortes históricos; nenhum resultado valida o modelo para o futuro.</p>
     </div>
    </details>

    <details class="mat-bt-details" <?=$btShowAll?'open':''?>><summary><strong>Tabelas completas · erro, viés e previsão ano a ano de cada modelo</strong></summary>
     <div class="mat-bt-details-body">
      <p><strong>Como ler:</strong> WAPE é o erro percentual agregado (menor costuma ser melhor). MAE mede o erro médio em unidades de K. Viés positivo significa tendência a prever acima do observado; negativo, abaixo. “—” significa que não foi possível calcular, não que o erro foi zero.</p>
      <div class="mat-bt-table-wrap"><table class="mat-bt-table"><thead><tr><th>Modelo</th><th>Erro (WAPE)</th><th>MAE (K)</th><th>Viés médio (K)</th><th>Testes válidos</th></tr></thead><tbody>
      <?php foreach($btRows as $row):$stat=$row['stat'];?>
       <tr class="<?=$row['id']===$btRef?'mat-bt-featured-row':''?>"><th><?=h($row['label'])?><?=$row['id']==='sem_avanco'?' · controle':''?><?=$row['id']===$btRef?' · referência':''?><?=$btBest&&$row['id']===$btBest['id']?' · melhor':''?><?=$btWorst&&$row['id']===$btWorst['id']?' · pior':''?></th><td><?=$stat['wape']===null?'—':$btFmt($stat['wape']).'%'?></td><td><?=$btFmt($stat['mae'],3)?></td><td><?=$btFmt($stat['bias'],3)?></td><td><?=$stat['n']?></td></tr>
      <?php endforeach;?></tbody></table></div>
      <h3>Cada previsão histórica, ano a ano</h3>
      <?php foreach($btRows as $row):$folds=$row['stat']['folds'];?>
      <details class="mat-bt-subdetails" style="box-shadow:none;margin-top:10px"><summary><?=h($row['label'])?> · <?=count($folds)?> testes</summary>
      <?php if(!$folds):?><p>Não houve dados históricos suficientes para testar este modelo neste horizonte.</p><?php else:?><div class="mat-bt-table-wrap" style="margin-top:8px"><table class="mat-bt-table" style="min-width:520px"><thead><tr><th>Ano da previsão</th><th>Ano avaliado</th><th>Crescimento observado ΔK</th><th>Crescimento previsto ΔK</th><th>Diferença (com sinal)</th></tr></thead><tbody>
      <?php foreach($folds as $fold):?><tr><td><?=$fold['corte']?></td><td><?=$fold['alvo']?></td><td><?=$btFmt($fold['crescimento_real'],4)?></td><td><?=$btFmt($fold['crescimento_previsto'],4)?></td><td><?=$btFmt($fold['erro'],4)?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></details>
      <?php endforeach; ?>
      <p class="mat-bt-table-note" style="margin-top:12px">A classificação por WAPE não é um ranking universal: pode depender do horizonte, da seleção dos marcos e do número de testes válidos.</p>
     </div>
    </details>
  </section>

  <footer class="mat-bt-footnote">
    <p><strong>Registro completo:</strong> os melhores e os piores cortes, o melhor e o pior modelo — tudo fica na página, nas duas origens de dados. Os resultados são exploratórios e não alteram o banco; os marcos seguem sujeitos à curadoria humana. As bases histórica (07/11) e viva (curadoria) não são intercambiáveis e são sempre rotuladas.</p>
    <div class="mat-bt-actions">
      <a class="mat-bt-button" href="<?=mat_url('temporis',['fonte'=>$btSrc,'coorte'=>$btCoorte])?>">← Voltar ao Índice Têmporis</a>
      <a class="mat-bt-button" href="<?=mat_url('destaques_previsoes')?>">★ Melhores previsões · 50 anos</a>
      <a class="mat-bt-textlink" href="<?=mat_url('metodo')?>">Consultar o Caderno de Fórmulas →</a>
    </div>
  </footer>
<?php endif; ?>
</div>
