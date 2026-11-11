<?php
/** Ensaio didático com dados efetivos da curadoria; NÃO gera amostras fictícias. */
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/TemporisCuradoria.php';
require_once __DIR__.'/app/TemporisGraficos.php';
if(!is_admin()){redirect_to('login',['return'=>'temporis']);}
$page='ensaio_didatico';$navPage='ensaio_didatico';
$pageLabels=['ensaio_didatico'=>'Ensaio didático do Têmporis'];
$stats=app_stats();$flash=take_flash();
$dataset=null;$erro=null;$anoBase=(int)cfg('temporis_reformulada_ano_base',1920);
try{$pdo=CuradoriaRodadas::connect();if(!$pdo)throw new RuntimeException('Banco da curadoria indisponível.');$dataset=TemporisCuradoria::consultar($pdo,$anoBase);}
catch(Throwable $e){$erro=$e->getMessage();}
require __DIR__.'/views/header.php';
?>
<header class="page-heading"><div class="section-overline">03 / ÍNDICE TÊMPORIS / ENSAIO DIDÁTICO</div><h1>Entender antes de <em>validar.</em></h1><p>Uma leitura dos mesmos dossiês da curadoria, sem inventar notas, anos ou acontecimentos.</p></header>
<div class="lab-alert"><span class="lab-symbol">※</span><div><strong>ENSAIO COM DADOS REAIS, AINDA NÃO HOMOLOGADOS</strong><p>A prévia matemática não equivale à aprovação histórica. Este é um recurso pedagógico diferente do Backtesting: aqui estudamos como as duas transformações respondem ao acúmulo dos pesos.</p></div></div>
<?php if($erro):?><div class="mat-grafo-crit" role="alert"><?=h($erro)?></div><?php else:?>
<?php $pts=$dataset['ensaio']['series']??[];$calc=TemporisGraficos::curvas($pts,$anoBase);?>
<div class="exp-boxes"><div class="exp-box">Dossiês com notas e data utilizável<strong><?=count($pts)?></strong></div><div class="exp-box">Soma até <?=$anoBase?><strong><?=decimal((float)$calc['k_base'],3)?></strong></div><div class="exp-box">Homologados no índice<strong><?=count($dataset['homologada']['series']??[])?></strong></div></div>
<section class="paper-panel mat-panel-spaced"><div class="panel-head"><span>TRANSFORMAÇÕES MATEMÁTICAS</span><span>MESMOS PESOS</span></div><h2>Logaritmo e potência na mesma linha do tempo</h2><div class="mat-grafo-bloco"><?=TemporisGraficos::svgCronologico($pts,$anoBase,'log',true)?></div><p class="mat-grafo-nota">As duas curvas usam os mesmos pesos de 11 critérios; a potência aqui não reproduz o motor legado de notas 1–5. Os anos extraídos sem validação são provisórios. Datas em intervalo não são intervalos de confiança.</p></section>
<section class="paper-panel mat-panel-spaced"><h2>O que cada fórmula pergunta?</h2><p><strong>Reformulada:</strong> T(t)=100 × ln(1+K(t))/ln(1+K(base)). <strong>Potência comparativa:</strong> T(t)=100 × (K(t)/K(base))<sup>0,65</sup>. Em ambos os casos, K(t) soma os pesos dos eventos até o ano t.</p><p>Uma curva crescente é consequência do acúmulo de marcos; não demonstra, isoladamente, aceleração tecnológica. O <a class="text-link" href="experimento_temporis.php">Backtesting</a> avalia erros retrospectivos, enquanto o ensaio explica as transformações.</p></section>
<?php endif;?>
<?php require __DIR__.'/views/footer.php'; ?>
