<?php
declare(strict_types=1);
require __DIR__.'/engine.php';
function e($x): string {return htmlspecialchars((string)$x,ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');}
$kind=($_POST['kind']??'reconstruct')==='forecast'?'forecast':'reconstruct';
$year=max(2016,min(2026,(int)($_POST['year']??2022)));
$pct=max(5,min(80,(int)($_POST['pct']??20)));
$selection=($_POST['selection']??'stratified')==='biased'?'biased':'stratified';
$seed=max(0,min(10000000,(int)($_POST['seed']??42)));
$source=($_POST['source']??'demo');
$realPath=__DIR__.'/data/owid_internet.csv';
$sourceLabel='DEMONSTRAÇÃO SINTÉTICA';$isDemo=true;$r=null;$error=null;$backtest=[];
try {
 if ($source==='real'){
    if(!is_file($realPath))throw new RuntimeException('CSV oficial ainda não instalado. Use o comando de atualização do README ou envie um CSV no formulário.');
    $path=$realPath;$sourceLabel='DADOS REAIS · OUR WORLD IN DATA';$isDemo=false;
 }elseif($source==='upload'){
    $f=$_FILES['csv']??null;
    if (!$f || $f['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione um CSV válido. No XAMPP, confira também o limite de upload do PHP.');
    if(!is_uploaded_file($f['tmp_name'])||$f['size']>12000000)throw new RuntimeException('Arquivo muito grande ou envio inválido (máximo 12 MB).');
    $path=$f['tmp_name'];$sourceLabel='CSV ENVIADO NESTA SESSÃO';$isDemo=false;
 }else{$path=__DIR__.'/data/EXEMPLO_SINTETICO.csv';}
 $data=mp_load_csv($path);
 $r=$kind==='forecast'?mp_forecast($data,$year):mp_reconstruct($data,$year,$pct,$selection,$seed);
 $backtest=mp_backtest($data,2017,2024);
} catch(Throwable $ex){$error=$ex->getMessage();}
function fmt($v,int $n=2): string {return $v===null?'—':number_format((float)$v,$n,',','.');}
function relative(float $v,float $b): string {return $b>0?number_format((1-$v/$b)*100,1,',','.').'%':'indefinida';}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101d20">
<title>MAT-Proj · Laboratório de projeções · MAT</title>
<link rel="icon" href="../assets/insignia.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/proj.css">
</head><body>
<div class="layout">
<aside class="rail">
 <a class="brand" href="../index.php"><img src="../assets/insignia.svg" alt="" width="42" height="42"><span><strong>MAT<span class="bronze">.</span></strong><small>OBSERVATÓRIO DO TEMPO</small></span></a>
 <div class="side-caption">CADERNO DE EXPERIÊNCIAS <span>08—</span></div>
 <nav aria-label="Navegação"><a href="../index.php" class="nav">← Voltar ao Observatório</a><a href="../index.php?page=temporis" class="nav">Índice Têmporis</a><a class="nav active" href="index.php">◇ MAT-Proj · Experimentos</a></nav>
 <div class="side-note"><span class="eyebrow">ESTAÇÃO EXPERIMENTAL / 001</span><p>O futuro não se adivinha: ele se submete a hipóteses que podem falhar.</p></div>
 <div class="side-foot">MAT-PROJ <span>VERSÃO 0.1.0</span></div>
</aside>
<div class="main">
 <header class="top"><span>MAT <span class="slash">/</span> LABORATÓRIO DE PROJEÇÕES</span><span>ESTUDO EXPERIMENTAL · 2026</span></header>
 <div class="content">
 <div class="hero">
  <span class="eyebrow"><i class="square"></i> MÓDULO INDEPENDENTE <span class="small-gap">/</span> MAT-PROJ 0.1.0</span>
  <h1>Projetar o<br><em>desconhecido.</em></h1>
  <p>A história oferece um ponto de partida. O MAT-Proj mede o que mudou, estima aquilo que falta e confronta cada hipótese com os dados reais.</p>
  <div class="hero-bottom"><span>01 / DADOS PARCIAIS</span><span>02 / MODELO HISTÓRICO</span><span>03 / VALIDAÇÃO</span></div>
 </div>
 <div class="notice <?= $isDemo?'demo':'real' ?>"><strong><?=e($sourceLabel)?></strong><span><?= $isDemo?'Valores totalmente artificiais, gerados para testar o software. Nenhuma taxa exibida representa um país real.':'Use apenas séries comparáveis. Os resultados deste ensaio não foram submetidos a validação científica independente.'?></span></div>
 <section class="panel controls" aria-labelledby="params"><div class="section-head"><span class="eyebrow">EXPERIMENTO / CONFIGURAÇÃO</span><h2 id="params">Montar a simulação</h2></div>
 <form method="post" enctype="multipart/form-data">
  <div class="controls-grid">
   <label>Modalidade<select name="kind"><option value="reconstruct"<?=$kind==='reconstruct'?' selected':''?>>Reconstruir ano com dados parciais</option><option value="forecast"<?=$kind==='forecast'?' selected':''?>>Prever ano seguinte sem dados dele</option></select></label>
   <label>Ano de referência<input type="number" name="year" min="2016" max="2026" value="<?=e($year)?>"></label>
   <label>Fonte de dados<select name="source"><option value="demo"<?=$source==='demo'?' selected':''?>>Exemplo sintético (incluído)</option><option value="real"<?=$source==='real'?' selected':''?>>OWID instalado no módulo</option><option value="upload"<?=$source==='upload'?' selected':''?>>Meu arquivo CSV (envio único)</option></select></label>
   <label>Dados disponíveis (reconstrução)<select name="pct"><?php foreach([5,10,20,40,60,80] as $p):?><option value="<?=$p?>"<?=$pct===$p?' selected':''?>><?=$p?>% dos países</option><?php endforeach;?></select></label>
   <label>Ordem dos dados parciais<select name="selection"><option value="stratified"<?=$selection==='stratified'?' selected':''?>>Estratificada (por nível anterior)</option><option value="biased"<?=$selection==='biased'?' selected':''?>>Tendenciosa (maiores níveis primeiro)</option></select></label>
   <label>Semente reprodutível<input type="number" name="seed" min="0" max="10000000" value="<?=e($seed)?>"></label>
  </div>
  <label class="file-label">Arquivo CSV para opção “Meu arquivo” <input type="file" name="csv" accept=".csv,text/csv" aria-label="Selecionar arquivo CSV"></label>
  <div class="form-bottom"><button type="submit">Executar experimento <span>↗</span></button><a href="https://ourworldindata.org/grapher/share-of-individuals-using-the-internet.csv?v=1&amp;csvType=full&amp;useColumnShortNames=true" target="_blank" rel="noopener noreferrer">Baixar dados reais (OWID) ↗</a></div>
 </form>
 </section>
 <?php if($error):?><div class="error"><strong>Não foi possível executar:</strong> <?=e($error)?></div><?php endif;?>
 <?php if($r):?>
 <section class="result" aria-label="Resultados da experiência">
  <div class="section-head"><span class="eyebrow">RESULTADO / <?= $kind==='reconstruct'?'RECONSTRUÇÃO HISTÓRICA':'PREVISÃO TEMPORAL'?></span><h2>O que o modelo encontrou</h2></div>
  <div class="metrics">
    <article><span>PAÍSES COM REFERÊNCIA</span><strong><?=e($r['eligible'])?></strong><small>Análise por país, não por população</small></article>
    <article><span><?= $kind==='reconstruct'?'DADOS REVELADOS':'DADOS DO ANO PREVISTO'?></span><strong><?= $kind==='reconstruct'?e($r['observed']):'0'?></strong><small><?= $kind==='reconstruct'?e($r['hidden']).' ocultados':'Nenhum valor-alvo no treinamento'?></small></article>
    <article><span>MAE DO MODELO</span><strong><?=fmt($r['mae'])?></strong><small><?= $r['mae']!==null?'pontos percentuais':'Sem resultado histórico para comparar'?></small></article>
    <article><span>MAE INGÊNUO</span><strong><?=fmt($r['baseline_mae'])?></strong><small>Manter percentual do ano anterior</small></article>
  </div>
  <div class="comparison">
   <div><span class="eyebrow">MÉDIA SIMPLES DOS PAÍSES</span><h3>Três leituras do mesmo período</h3><p>As médias representam os países elegíveis da experiência. Não são a taxa mundial de uso da internet.</p></div>
   <div class="bar-chart">
   <?php foreach([['Referência ingênua',$r['baseline_mean'],'light'],['Previsão MAT-Proj',$r['pred_mean'],'dark'],['Valor histórico',$r['real_mean'],'copper']] as [$label,$value,$class]):?>
   <div class="bar-item"><div class="bar-item-head"><span><?=e($label)?></span><strong><?=fmt($value)?>%</strong></div><div class="bar-track"><div class="bar <?=$class?>" style="width:<?=e($value===null?0:mp_limit((float)$value))?>%"></div></div></div>
   <?php endforeach;?></div>
  </div>
  <div class="interpret">
   <?php if($r['mae']===null):?>
     **Previsão ainda não verificável:** os dados do ano previsto não constam na base. Nenhuma alegação de precisão deve ser feita por enquanto.
   <?php elseif($r['mae']<$r['baseline_mae']):?>
     <strong>Resultado do ensaio:</strong> o MAT-Proj teve erro médio menor que a referência ingênua neste teste (redução de <?=e(relative($r['mae'],$r['baseline_mae']))?>). Isso não garante sucesso em outros períodos.
   <?php else:?>
     <strong>Resultado do ensaio:</strong> o MAT-Proj não superou a referência ingênua nesta configuração. Este resultado será mantido, não ocultado.
   <?php endif;?>
  </div>
 </section>
 <section class="panel table-panel"><div class="section-head"><span class="eyebrow">AUDITORIA / UNIDADES INDIVIDUAIS</span><h2>Rastro das estimativas</h2></div><p class="hint">Na reconstrução, “oculto” significa que o valor-alvo não entrou no algoritmo, mesmo sendo revelado nesta tabela após o cálculo.</p>
 <div class="table-scroll"><table><thead><tr><th>Unidade</th><th>Estado</th><th>Referência</th><th>Estimativa</th><th>Real (teste)</th></tr></thead><tbody>
 <?php foreach(array_slice($r['details'],0,24) as $d):?><tr><td><?=e($d['country'])?></td><td><?= $d['observed']?'<span class="tag-green">Observado</span>':'<span class="tag-muted">Oculto / futuro</span>'?></td><td><?=fmt($d['baseline'])?>%</td><td><?=fmt($d['predicted'])?>%</td><td><?=fmt($d['actual'])?>%</td></tr><?php endforeach; ?>
 </tbody></table></div><p class="hint">Exibindo os primeiros 24 países; utilize o CLI para outros resultados.</p></section>
 <?php endif;?>
 <?php if($backtest):?><section class="panel table-panel"><div class="section-head"><span class="eyebrow">VALIDAÇÃO / ANOS RETROSPECTIVOS</span><h2>O modelo ao longo do tempo</h2></div><p class="hint">Cada linha utiliza apenas as referências disponíveis até aquele ano. MAE em pontos percentuais; erros menores são melhores.</p>
 <div class="table-scroll"><table><thead><tr><th>Ano</th><th>Países</th><th>Reconstrução</th><th>Ingênuo</th><th>Previsão futura</th><th>Ingênuo</th></tr></thead><tbody><?php foreach($backtest as $b):?><tr><td><?=$b['year']?></td><td><?=$b['count']?></td><td><?=fmt($b['recon_mae'])?></td><td><?=fmt($b['recon_naive'])?></td><td><?=fmt($b['forecast_mae'])?></td><td><?=fmt($b['forecast_naive'])?></td></tr><?php endforeach;?></tbody></table></div></section><?php endif;?>
 <section class="explain"><span class="eyebrow">CADERNO / MÉTODO</span><h2>Referência, variação, estimativa.</h2><div class="three"><div><span>01</span><h3>Referência anterior</h3><p>O percentual do ano anterior serve como ponto de partida específico de cada país.</p></div><div><span>02</span><h3>Variação observada</h3><p>As diferenças são medidas por faixas de adoção e suavizadas quando há poucas observações.</p></div><div><span>03</span><h3>Verificação retrospectiva</h3><p>Comparamos os valores estimados com registros que foram escondidos ou que pertencem ao ano seguinte.</p></div></div>
 <p class="footnote">Inspirado na lógica pública do Apuração Aberta, não é cópia do algoritmo eleitoral. Não mede a Têmporis nem prevê descobertas tecnológicas. Fontes reais: ITU / Banco Mundial, via Our World in Data. A versão atual NÃO calcula intervalos de previsão calibrados.</p></section>
 </div><footer>MAT · OBSERVATÓRIO DO TEMPO <span>ESTAÇÃO EXPERIMENTAL MAT-PROJ / 0.1.0</span></footer>
</div></div>
</body></html>
