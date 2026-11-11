<?php
/**
 * Página separada: Demonstração simples das 3 fórmulas em funcionamento.
 * Conectada à base de dados atual (somente leitura).
 */
require_once MAT_ROOT.'/app/TemporisFinal.php';

$fontePedida = (string)($_GET['fonte'] ?? 'auto');
if (!isset(TemporisFinal::FONTES[$fontePedida])) $fontePedida = 'auto';
$base = filter_var($_GET['base'] ?? 1900, FILTER_VALIDATE_INT);
$base = $base === false ? 1900 : max(1600, min(2000, $base));
$coorte = (string)($_GET['coorte'] ?? 'ampla');
if (!isset(TemporisFinal::COORTES[$coorte])) $coorte = 'ampla';

$erro = null; $resultado = null; $dados = null; $fonte = ''; $banco = '';
$pontos = []; $linha = []; $Kbase = 0.0;
$anosRef = [1800,1850,1900,1925,1950,1975,2000,2010,2020,2026];
$tabela = [];
$formulas = [
  'empirica' => [
    'titulo' => 'Final (atual) — Acumulação proporcional',
    'curta'  => 'Final / Proporcional',
    'peso'   => 'wᵢ = (Iᵢ / 10)² · Cᵢ · Fᵢ',
    'indice' => 'T(t) = 100 · K(t) / K(t₀)',
    'desc'   => 'Sem compressão: a variação do índice acompanha diretamente a variação proporcional do acúmulo K.',
    'cor'    => '#0f766e',
    'corFill'=> 'rgba(15,118,110,0.10)',
  ],
  'log' => [
    'titulo' => 'Reformulada — Transformação logarítmica',
    'curta'  => 'Reformulada / Log',
    'peso'   => 'wᵢ = (Iᵢ / 10)² · Cᵢ · Fᵢ',
    'indice' => 'T(t) = 100 · ln(1 + K(t)) / ln(1 + K(t₀))',
    'desc'   => 'Aplica logaritmo para comprimir valores altos; muda a escala de leitura do mesmo acúmulo.',
    'cor'    => '#5b6470',
    'corFill'=> 'rgba(91,100,112,0.06)',
  ],
  'pot065' => [
    'titulo' => 'Legada — Potência 0,65 (adaptação comparativa)',
    'curta'  => 'Legada / Potência 0,65',
    'peso'   => 'Pᵢ = Iᵢ² · Cᵢ · Fᵢ  (equivalente a 100·w)',
    'indice' => 'T(t) = 100 · (K(t) / K(t₀))^0,65',
    'desc'   => 'Eleva a razão à potência 0,65, atenuando diferenças do estoque acumulado.',
    'cor'    => '#b45309',
    'corFill'=> 'rgba(180,83,9,0.06)',
  ],
];

$stats = ['n'=>0,'I'=>0,'C'=>0,'F'=>0,'w'=>0,'Imin'=>null,'Imax'=>null,'Cmin'=>null,'Cmax'=>null,'Fmin'=>null,'Fmax'=>null,'wmin'=>null,'wmax'=>null];
$exemplo = null;

try {
    $resultado = TemporisFinal::obterDados($fontePedida, 2026);
    $dados = $resultado['dados'];
    $fonte = $resultado['fonte'];
    $banco = $resultado['database'];
    $pontos = TemporisFinal::filtrar($dados['pontos'] ?? [], $coorte);
    $linha = TemporisFinal::linha($pontos, 'quadratica');
    $Kbase = TemporisFinal::k($linha, $base);
    foreach ($anosRef as $ano) {
        $k = TemporisFinal::k($linha, $ano);
        $row = ['ano' => $ano, 'k' => $k];
        foreach (array_keys($formulas) as $esc) {
            $row[$esc] = TemporisFinal::indice($k, $Kbase, $esc);
        }
        $tabela[] = $row;
    }
    // estatísticas e exemplo para decomposição
    $n = count($pontos);
    $stats['n'] = $n;
    if ($n > 0) {
        $sumI=$sumC=$sumF=$sumW=0.0;
        foreach ($pontos as $i=>$p) {
            $I=(float)$p['I']; $C=(float)$p['C']; $F=(float)$p['F']; $w=(float)($p['pesos']['quadratica'] ?? 0);
            $sumI+=$I; $sumC+=$C; $sumF+=$F; $sumW+=$w;
            foreach ([['I',$I],['C',$C],['F',$F],['w',$w]] as [$k,$v]) {
                $stats[$k.'min'] = ($stats[$k.'min']===null)?$v:min($stats[$k.'min'],$v);
                $stats[$k.'max'] = ($stats[$k.'max']===null)?$v:max($stats[$k.'max'],$v);
            }
        }
        $stats['I']=$sumI/$n; $stats['C']=$sumC/$n; $stats['F']=$sumF/$n; $stats['w']=$sumW/$n;
        // exemplo representativo: marco com peso mais próximo da média
        $bestIdx = 0; $bestDiff = INF;
        foreach ($pontos as $i=>$p) {
            $d = abs((float)($p['pesos']['quadratica']??0) - $stats['w']);
            if ($d < $bestDiff) { $bestDiff = $d; $bestIdx = $i; }
        }
        $exemplo = $pontos[$bestIdx];
    }
} catch (Throwable $e) {
    $erro = $e->getMessage();
}

$fmt = static function($v, $d=2): string {
    if ($v === null || !is_finite((float)$v)) return '—';
    return number_format((float)$v, $d, ',', '.');
};

$svgChart = function(array $rows, array $seriesDef, int $baseYear) use ($fmt) {
    $W=1000; $H=420; $left=78; $right=940; $top=34; $bottom=332;
    $years = array_column($rows, 'ano');
    $vals = [];
    foreach ($rows as $r) foreach ($seriesDef as $k=>$def) {
        if ($r[$k] !== null && is_finite((float)$r[$k])) $vals[] = (float)$r[$k];
    }
    if (!$vals || count($years) < 2) return '<p>Sem dados suficientes para gerar o gráfico.</p>';
    $yMin = min($vals); $yMax = max($vals);
    $pad = max(5.0, ($yMax - $yMin) * 0.14);
    $yMin = max(0.0, floor(($yMin - $pad) / 10) * 10);
    $yMax = ceil(($yMax + $pad) / 10) * 10;
    if ($yMax <= $yMin) $yMax = $yMin + 10;
    $x0 = min($years); $x1 = max($years);
    $X = function($yr) use ($x0,$x1,$left,$right){ return $left + ($yr-$x0)/max(1,$x1-$x0)*($right-$left); };
    $Y = function($v) use ($yMin,$yMax,$top,$bottom){ return $bottom - ($v-$yMin)/max(0.00001,$yMax-$yMin)*($bottom-$top); };
    $nf = function($n){ return number_format((float)$n,2,'.',''); };

    $out = '<svg viewBox="0 0 '.$W.' '.$H.'" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Comparação das três fórmulas do índice Têmporis">';
    $out .= '<defs>';
    foreach ($seriesDef as $k=>$def) {
        $gid = 'g'.preg_replace('/[^a-z0-9]/i','',$k);
        $out .= '<linearGradient id="'.$gid.'" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="'.htmlspecialchars($def['cor']).'" stop-opacity="0.18"/><stop offset="100%" stop-color="'.htmlspecialchars($def['cor']).'" stop-opacity="0.01"/></linearGradient>';
    }
    $out .= '<filter id="softShadow" x="-10%" y="-10%" width="120%" height="120%"><feDropShadow dx="0" dy="1" stdDeviation="1.2" flood-color="#000" flood-opacity="0.08"/></filter>';
    $out .= '</defs>';

    // fundo
    $out .= '<rect x="'.$left.'" y="'.$top.'" width="'.($right-$left).'" height="'.($bottom-$top).'" rx="10" fill="#fcfbf7" stroke="#ece6d8"/>';

    // linhas horizontais e rótulos Y
    $ticks = 6;
    for ($i=0; $i<=$ticks; $i++) {
        $v = $yMin + ($yMax-$yMin)*$i/$ticks;
        $yy = $Y($v);
        $isAxis = ($i==0);
        $out .= '<line x1="'.$left.'" y1="'.$nf($yy).'" x2="'.$right.'" y2="'.$nf($yy).'" stroke="'.($isAxis?'#d8d1c2':'#e8e1d2').'" stroke-width="'.($isAxis?'1.2':'1').'" '.($isAxis?'':'stroke-dasharray="4 5"').'/>';
        $out .= '<text x="'.($left-10).'" y="'.$nf($yy+4).'" text-anchor="end" font-size="12" fill="#5a6b64" font-family="system-ui,Segoe UI,Arial">'.number_format($v,0,',','.').'</text>';
    }

    // linhas verticais e rótulos X
    foreach ($years as $yr) {
        $xx = $X($yr);
        $out .= '<line x1="'.$nf($xx).'" y1="'.$top.'" x2="'.$nf($xx).'" y2="'.$bottom.'" stroke="#efe9dc" stroke-width="1"/>';
        $out .= '<text x="'.$nf($xx).'" y="'.($bottom+24).'" text-anchor="middle" font-size="12" fill="#526b5f" font-family="system-ui,Segoe UI,Arial">'.$yr.'</text>';
    }

    // marca do ano-base
    $bx = $X($baseYear);
    if ($bx >= $left && $bx <= $right) {
        $out .= '<line x1="'.$nf($bx).'" y1="'.$top.'" x2="'.$nf($bx).'" y2="'.$bottom.'" stroke="#9aa39b" stroke-width="1.5" stroke-dasharray="5 4"/>';
        $out .= '<rect x="'.$nf($bx-32).'" y="'.($top+6).'" width="64" height="18" rx="9" fill="#f3efe4" stroke="#d9d1bf"/>';
        $out .= '<text x="'.$nf($bx).'" y="'.($top+19).'" text-anchor="middle" font-size="11" fill="#5c5442" font-family="system-ui,Segoe UI,Arial">base '.$baseYear.'</text>';
    }

    // áreas sutis (abaixo das linhas)
    foreach (['log','pot065','empirica'] as $k) {
        if (!isset($seriesDef[$k])) continue;
        $def = $seriesDef[$k]; $gid = 'g'.preg_replace('/[^a-z0-9]/i','',$k);
        $d=''; $first=true; $pts=[];
        foreach ($rows as $r){ if ($r[$k]===null || !is_finite((float)$r[$k])) continue; $px=$X($r['ano']); $py=$Y($r[$k]); $pts[] = [$px,$py]; $d.=($first?'M':'L').$nf($px).' '.$nf($py).' '; $first=false; }
        if (count($pts) >= 2) {
            $area = $d.' L '.$nf(end($pts)[0]).' '.$nf($bottom).' L '.$nf($pts[0][0]).' '.$nf($bottom).' Z';
            $out .= '<path d="'.trim($area).'" fill="url(#'.$gid.')" stroke="none"/>';
        }
    }

    // linhas e pontos
    foreach (['log','pot065','empirica'] as $k) {
        if (!isset($seriesDef[$k])) continue;
        $def = $seriesDef[$k];
        $d=''; $first=true; $lastPt=null;
        foreach ($rows as $r){ if ($r[$k]===null || !is_finite((float)$r[$k])) continue; $px=$X($r['ano']); $py=$Y($r[$k]); $d.=($first?'M':'L').$nf($px).' '.$nf($py).' '; $first=false; $lastPt=['x'=>$px,'y'=>$py,'v'=>$r[$k],'ano'=>$r['ano']]; }
        if ($d==='') continue;
        $stroke = htmlspecialchars($def['cor'],ENT_QUOTES);
        $w = ($k==='empirica') ? '4' : '2.6';
        $dash = ($k==='empirica') ? '' : ' stroke-dasharray="7 5"';
        $out .= '<path d="'.trim($d).'" fill="none" stroke="'.$stroke.'" stroke-width="'.$w.'" stroke-linecap="round" stroke-linejoin="round"'.$dash.' filter="url(#softShadow)"/>';
        foreach ($rows as $r){
            if ($r[$k]===null || !is_finite((float)$r[$k])) continue;
            $cx=$X($r['ano']); $cy=$Y($r[$k]);
            $rpt = ($k==='empirica') ? 4.6 : 3.6;
            $out .= '<circle cx="'.$nf($cx).'" cy="'.$nf($cy).'" r="'.$rpt.'" fill="#fff" stroke="'.$stroke.'" stroke-width="2"><title>'.htmlspecialchars($def['curta']).' — '.$r['ano'].': '.number_format((float)$r[$k],2,',','.').'</title></circle>';
            // rótulo em pontos-chave
            if (in_array((int)$r['ano'], [1900,2000,2026], true)) {
                $label = number_format((float)$r[$k],1,',','.'); 
                $ly = $cy - 10; if ($ly < $top+14) $ly = $cy + 16;
                $out .= '<text x="'.$nf($cx).'" y="'.$nf($ly).'" text-anchor="middle" font-size="11" font-weight="600" fill="'.$stroke.'" font-family="system-ui,Segoe UI,Arial">'.$label.'</text>';
            }
        }
    }

    // títulos dos eixos
    $out .= '<text x="'.($left + ($right-$left)/2).'" y="'.($H-18).'" text-anchor="middle" font-size="13" fill="#4b5d56" font-family="system-ui,Segoe UI,Arial">Ano (escala temporal proporcional)</text>';
    $out .= '<text transform="translate(22,'.($top + ($bottom-$top)/2).') rotate(-90)" text-anchor="middle" font-size="13" fill="#4b5d56" font-family="system-ui,Segoe UI,Arial">Índice T(t), base '.$baseYear.' = 100</text>';

    // legenda
    $lx = $left + 14; $ly = $top + 18;
    $i=0;
    foreach (['empirica','log','pot065'] as $k) { if(!isset($seriesDef[$k])) continue; $def=$seriesDef[$k];
        $yy = $ly + $i*22;
        $out .= '<line x1="'.$lx.'" y1="'.$yy.'" x2="'.($lx+26).'" y2="'.$yy.'" stroke="'.htmlspecialchars($def['cor']).'" stroke-width="'.($k==='empirica'?4:2.6).'" '.($k==='empirica'?'':'stroke-dasharray="7 5"').'/>';
        $out .= '<circle cx="'.($lx+13).'" cy="'.$yy.'" r="3.5" fill="#fff" stroke="'.htmlspecialchars($def['cor']).'" stroke-width="2"/>';
        $out .= '<text x="'.($lx+34).'" y="'.($yy+4).'" font-size="12.5" fill="#33413b" font-family="system-ui,Segoe UI,Arial">'.htmlspecialchars($def['curta']).'</text>';
        $i++;
    }

    $out .= '</svg>';
    return $out;
};

$k2026 = (!$erro) ? TemporisFinal::k($linha, 2026) : 0.0;
$ratio = ($Kbase > 0) ? ($k2026 / $Kbase) : null;
$tFinal = TemporisFinal::indice($k2026,$Kbase,'empirica');
$tLog = TemporisFinal::indice($k2026,$Kbase,'log');
$tLeg = TemporisFinal::indice($k2026,$Kbase,'pot065');
$ln1k = log1p(max(0.0,$k2026));
$ln1k0 = log1p(max(0.0,$Kbase));
?>

<style>
  .demo-hero-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.75rem;margin:.5rem 0 0}
  .demo-card{background:#fbfaf6;border:1px solid #e7dfd0;border-radius:12px;padding:.85rem .95rem}
  .demo-card h3{margin:.1rem 0 .35rem;font-size:1rem}
  .demo-card .big{font-size:1.35rem;font-weight:700;color:#17332d}
  .demo-formula-block{background:#f7f4ec;border:1px solid #e3dccb;border-radius:10px;padding:.7rem .85rem;margin:.45rem 0;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:.98rem;line-height:1.45}
  .demo-steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.8rem;margin-top:.6rem}
  .demo-step{background:#fff;border:1px solid #e6dfd1;border-left:4px solid #0f766e;border-radius:10px;padding:.75rem .85rem}
  .demo-step.alt-log{border-left-color:#5b6470}
  .demo-step.alt-leg{border-left-color:#b45309}
  .demo-step .k{display:inline-block;font-size:.75rem;letter-spacing:.04em;color:#5c6b66;font-weight:700;margin-bottom:.2rem}
  .demo-step h4{margin:.05rem 0 .35rem;font-size:1rem}
  .demo-step code{background:#f3efe5;padding:.05rem .3rem;border-radius:4px}
  .demo-table-note{color:#56655f;font-size:.92rem;margin:.35rem 0 .6rem}
  .demo-pill{display:inline-block;padding:.15rem .55rem;border-radius:999px;background:#eef3ef;color:#1f4a41;font-size:.8rem;font-weight:600;margin-left:.35rem}
  .mat-final-chart svg{width:100%;height:auto;display:block}
</style>

<header class="page-heading">
  <div class="section-overline">DEMONSTRAÇÃO SIMPLES / FÓRMULAS EM FUNCIONAMENTO</div>
  <h1>Demonstrativo <em>das fórmulas.</em></h1>
  <p>Página separada para mostrar, de forma clara, como as 3 fórmulas do Têmporis funcionam na prática: mesma base de dados, mesmo acúmulo <strong>K(t)</strong> e três formas diferentes de transformar esse acúmulo em índice <strong>T(t)</strong>.</p>
</header>

<section class="paper-panel" style="margin-bottom:1rem">
  <div class="panel-head"><span>CONEXÃO E DADOS ATUAIS</span><span>SOMENTE LEITURA</span></div>
  <?php if ($erro): ?>
    <div class="mat-final-warning" role="alert"><strong>Não foi possível carregar os dados:</strong> <?=h($erro)?></div>
    <p>Tente outra fonte: <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'auto','base'=>$base,'coorte'=>$coorte])?>">automática</a> · <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'curadoria','base'=>$base,'coorte'=>$coorte])?>">curadoria (MySQL)</a> · <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'final','base'=>$base,'coorte'=>$coorte])?>">MAT Final (MySQL)</a> · <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'snapshot','base'=>$base,'coorte'=>$coorte])?>">snapshot</a></p>
  <?php else: ?>
    <div class="tf-source-bar" role="status" style="margin-bottom:.75rem">
      <strong><?= $fonte==='curadoria' ? 'Conectado à curadoria atual (MySQL)' : ($fonte==='final' ? 'Conectado à base isolada MAT Final (MySQL)' : ($fonte==='snapshot' ? 'Usando exportação estática (snapshot)' : 'Fonte automática')) ?></strong>
      <span>Origem: <code><?=h($banco)?></code></span>
      <span>Solicitado: <?=h(TemporisFinal::FONTES[$fontePedida] ?? $fontePedida)?></span>
    </div>
    <form method="get" action="index.php" class="tf-form" style="margin-bottom:.5rem">
      <input type="hidden" name="page" value="demonstracao_formulas">
      <div class="tf-form-main">
        <label>Fonte de dados<select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$desc): ?><option value="<?=h($id)?>" <?=$fontePedida===$id?'selected':''?>><?=h($desc)?></option><?php endforeach; ?></select></label>
        <label>Seleção de marcos<select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$desc): ?><option value="<?=h($id)?>" <?=$coorte===$id?'selected':''?>><?=h($desc)?></option><?php endforeach; ?></select></label>
        <label>Ano-base (t₀)<input type="number" name="base" value="<?=$base?>" min="1600" max="2000"></label>
        <button class="btn btn-dark" type="submit">Recalcular</button>
      </div>
    </form>
    <div class="demo-hero-grid">
      <div class="demo-card"><div class="panel-head" style="margin:0 0 .3rem"><span>DADOS</span></div><div class="big"><?=$fmt(count($pontos),0)?></div><div>marcos na seleção atual (de <?=$fmt($dados['audit']['elegiveis'] ?? 0,0)?> elegíveis / <?=$fmt($dados['audit']['total'] ?? 0,0)?> na fonte)</div></div>
      <div class="demo-card"><div class="panel-head" style="margin:0 0 .3rem"><span>ACÚMULO BASE</span></div><div class="big">K(<?=$base?>)=<?=$fmt($Kbase,3)?></div><div>referência de normalização (T = 100 na base)</div></div>
      <div class="demo-card"><div class="panel-head" style="margin:0 0 .3rem"><span>ACÚMULO ATUAL</span></div><div class="big">K(2026)=<?=$fmt($k2026,3)?></div><div>razão K(2026)/K(<?=$base?>) = <?=$ratio===null?'—':$fmt($ratio,4)?></div></div>
      <div class="demo-card"><div class="panel-head" style="margin:0 0 .3rem"><span>RESULTADO FINAL</span></div><div class="big">T(2026)=<?=$fmt($tFinal,2)?></div><div>método atual (proporcional, sem compressão)</div></div>
    </div>
    <?php if ($Kbase <= 0): ?>
      <div class="mat-final-warning" style="margin-top:.75rem"><strong>Atenção:</strong> K(<?=$base?>)=0 nesta seleção, então T(t) fica indefinido. Troque o ano-base ou a seleção.</div>
    <?php endif; ?>
  <?php endif; ?>
</section>

<?php if (!$erro): ?>
<section class="paper-panel tf-chart-main" style="margin-bottom:1rem">
  <div class="panel-head"><span>GRÁFICO COMPARATIVO</span><span>AS 3 FÓRMULAS SOBRE OS MESMOS DADOS</span></div>
  <h2>Como cada fórmula transforma o mesmo acúmulo</h2>
  <p>Mesmos marcos, mesmas datas e mesmos pesos → mesmo <strong>K(t)</strong>. O que muda é apenas a transformação matemática para obter <strong>T(t)</strong>. A linha contínua destaca o método atual.</p>
  <div class="mat-final-chart"><?=$svgChart($tabela,$formulas,$base)?></div>
  <p class="demo-table-note">Dica: passe o mouse sobre os pontos para ver os valores. Os anos do eixo horizontal estão na proporção temporal real.</p>
</section>

<section class="paper-panel" style="margin-bottom:1rem">
  <div class="panel-head"><span>DECOMPOSIÇÃO DA FÓRMULA (PASSO A PASSO)</span><span>DO MARCO AO ÍNDICE</span></div>
  <h2>Como a fórmula funciona, parte por parte</h2>

  <div class="demo-steps">
    <div class="demo-step">
      <span class="k">ETAPA 1 — NOTAS DO MARCO</span>
      <h4>Rubricas (0 a 3)</h4>
      <p>Cada marco recebe 11 notas, agrupadas em somas:</p>
      <div class="demo-formula-block">S<sub>I</sub> = soma das 4 rubricas de Impacto ∈ [0,12]<br>S<sub>C</sub> = soma das 4 rubricas de Confiabilidade ∈ [0,12]<br>S<sub>F</sub> = soma das 3 rubricas de Fundação ∈ [0,9]</div>
    </div>
    <div class="demo-step">
      <span class="k">ETAPA 2 — DIMENSÕES</span>
      <h4>Conversão das somas em I, C, F</h4>
      <div class="demo-formula-block">I = 1 + 9·(S<sub>I</sub>/12) &nbsp;⇒&nbsp; I ∈ [1, 10]<br>C = S<sub>C</sub>/12 &nbsp;⇒&nbsp; C ∈ [0, 1]<br>F = 1 + 0,5·(S<sub>F</sub>/9) &nbsp;⇒&nbsp; F ∈ [1; 1,5]</div>
      <p class="demo-table-note">Médias na seleção atual: I=<?=$fmt($stats['I'],3)?>, C=<?=$fmt($stats['C'],3)?>, F=<?=$fmt($stats['F'],3)?>.</p>
    </div>
    <div class="demo-step">
      <span class="k">ETAPA 3 — PESO DO MARCO</span>
      <h4>Peso wᵢ e contribuição</h4>
      <div class="demo-formula-block">fator impacto = (I / 10)<br>impacto² = (I / 10)²<br>wᵢ = (I / 10)² · C · F</div>
      <p class="demo-table-note">Peso médio: w=<?=$fmt($stats['w'],4)?> (min <?=$fmt($stats['wmin'],4)?>, máx <?=$fmt($stats['wmax'],4)?>).</p>
    </div>
    <div class="demo-step">
      <span class="k">ETAPA 4 — ACÚMULO NO TEMPO</span>
      <h4>Construção de K(t)</h4>
      <div class="demo-formula-block">K(t) = Σ wᵢ &nbsp; para todo marco i com ano tᵢ ≤ t</div>
      <p>Exemplo com dados atuais: <code>K(<?=$base?>)=<?=$fmt($Kbase,4)?></code>, <code>K(2026)=<?=$fmt($k2026,4)?></code>.</p>
    </div>
  </div>

  <?php if ($exemplo): 
    $eI=(float)$exemplo['I']; $eC=(float)$exemplo['C']; $eF=(float)$exemplo['F']; $eW=(float)$exemplo['pesos']['quadratica'];
    $eImp = $eI/10.0; $eImp2 = $eImp*$eImp; $eAno=(int)$exemplo['year'];
  ?>
  <details style="margin-top:.85rem" open>
    <summary><strong>Exemplo real decomposto (1 marco do banco)</strong> — clique para expandir/recolher</summary>
    <div class="demo-steps" style="margin-top:.7rem">
      <div class="demo-step">
        <span class="k">MARCO EXEMPLO</span>
        <h4><?=h((string)$exemplo['titulo'])?> <span class="demo-pill"><?= $eAno ?></span></h4>
        <p>ID: <code><?=h((string)$exemplo['id'])?></code><br>Dimensões calculadas a partir das notas: <code>I=<?=$fmt($eI,6)?></code>, <code>C=<?=$fmt($eC,6)?></code>, <code>F=<?=$fmt($eF,6)?></code></p>
      </div>
      <div class="demo-step">
        <span class="k">CÁLCULO DO PESO wᵢ</span>
        <h4>Substituindo na fórmula</h4>
        <div class="demo-formula-block">(I/10) = <?=$fmt($eI,6)?> / 10 = <?=$fmt($eImp,6)?><br>(I/10)² = <?=$fmt($eImp,6)?>² = <?=$fmt($eImp2,6)?><br>wᵢ = <?=$fmt($eImp2,6)?> · <?=$fmt($eC,6)?> · <?=$fmt($eF,6)?><br><strong>wᵢ = <?=$fmt($eW,6)?></strong></div>
      </div>
      <div class="demo-step">
        <span class="k">CONTRIBUIÇÃO PARA K(t)</span>
        <h4>Efeito no acúmulo</h4>
        <p>Esse marco soma <strong><?=$fmt($eW,6)?></strong> ao valor de <code>K(t)</code> em todos os anos <code>t ≥ <?=$eAno?></code>. O índice <code>T(t)</code> é calculado a partir do <code>K(t)</code> acumulado de todos os marcos.</p>
      </div>
    </div>
  </details>
  <?php endif; ?>
</section>

<section class="paper-panel" style="margin-bottom:1rem">
  <div class="panel-head"><span>AS 3 FÓRMULAS (DECOMPOSTAS COM VALORES REAIS)</span><span>ANO 2026, BASE <?=$base?></span></div>
  <h2>Mesma entrada, três transformações</h2>
  <div class="demo-steps">
    <div class="demo-step" style="border-left-color:#0f766e">
      <span class="k">FÓRMULA 1 — FINAL (ATUAL)</span>
      <h4>Acumulação proporcional</h4>
      <div class="demo-formula-block">T(t) = 100 · K(t) / K(t₀)<br><br>R = K(2026)/K(<?=$base?>) = <?=$fmt($k2026,6)?> / <?=$fmt($Kbase,6)?> = <strong><?=$ratio===null?'—':$fmt($ratio,6)?></strong><br>T(2026) = 100 · R = <strong><?=$fmt($tFinal,6)?></strong></div>
      <p>Interpretação: razão direta entre acúmulos (sem compressão).</p>
    </div>
    <div class="demo-step alt-log">
      <span class="k">FÓRMULA 2 — REFORMULADA</span>
      <h4>Transformação logarítmica</h4>
      <div class="demo-formula-block">T(t) = 100 · ln(1 + K(t)) / ln(1 + K(t₀))<br><br>ln(1 + K(2026)) = ln(1 + <?=$fmt($k2026,6)?>) = <strong><?=$fmt($ln1k,6)?></strong><br>ln(1 + K(<?=$base?>)) = ln(1 + <?=$fmt($Kbase,6)?>) = <strong><?=$fmt($ln1k0,6)?></strong><br>T(2026) = 100 · <?=$fmt($ln1k,6)?> / <?=$fmt($ln1k0,6)?> = <strong><?=$fmt($tLog,6)?></strong></div>
      <p>Interpretação: comprime a escala quando K cresce muito.</p>
    </div>
    <div class="demo-step alt-leg">
      <span class="k">FÓRMULA 3 — LEGADA (ADAPTADA)</span>
      <h4>Potência 0,65</h4>
      <div class="demo-formula-block">T(t) = 100 · (K(t) / K(t₀))^0,65<br><br>R = K(2026)/K(<?=$base?>) = <strong><?=$ratio===null?'—':$fmt($ratio,6)?></strong><br>R^0,65 = (<?=$ratio===null?'—':$fmt($ratio,6)?>)^0,65 = <strong><?=$ratio===null?'—':$fmt(pow((float)$ratio,0.65),6)?></strong><br>T(2026) = 100 · R^0,65 = <strong><?=$fmt($tLeg,6)?></strong></div>
      <p>Interpretação: atenua o crescimento pela potência &lt; 1.</p>
    </div>
  </div>
</section>

<section class="paper-panel" style="margin-bottom:1rem">
  <div class="panel-head"><span>RESULTADOS NUMÉRICOS</span><span>TABELA COMPARATIVA POR ANO</span></div>
  <h2>Valores calculados (mesma base de dados)</h2>
  <div class="mat-final-table-wrap">
    <table class="mat-final-table">
      <thead>
        <tr>
          <th>Ano</th>
          <th>K(t) — acúmulo</th>
          <th>Razão K/K(<?=$base?>)</th>
          <th>Final (proporcional)</th>
          <th>Reformulada (log)</th>
          <th>Legada (pot. 0,65)</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($tabela as $row): $isBase=((int)$row['ano']===$base); $rk = ($Kbase>0)?($row['k']/$Kbase):null; ?>
        <tr <?=$isBase?'class="mat-final-adotada"':''?>>
          <th><?= (int)$row['ano'] ?><?=$isBase?' (base)':''?></th>
          <td><?=$fmt($row['k'],4)?></td>
          <td><?=$rk===null?'—':$fmt($rk,4)?></td>
          <td><strong><?=$fmt($row['empirica'],4)?></strong></td>
          <td><?=$fmt($row['log'],4)?></td>
          <td><?=$fmt($row['pot065'],4)?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="demo-table-note">A coluna <strong>Razão K/K(<?=$base?>)</strong> mostra a entrada comum das transformações. A diferença entre as fórmulas aparece na forma como essa razão é convertida em T(t).</p>
</section>

<section class="paper-panel">
  <div class="panel-head"><span>APRESENTAÇÃO</span><span>USO RÁPIDO</span></div>
  <p><strong>Link direto:</strong> <code><?=h((isset($_SERVER['HTTP_HOST'])?((!empty($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']):'').strtok($_SERVER['REQUEST_URI'] ?? 'index.php?page=demonstracao_formulas','?'))?>?page=demonstracao_formulas</code></p>
  <p>Esta página é demonstrativa e de somente leitura: ela não altera notas nem aprova marcos. Os resultados dependem da fonte, da seleção e do ano-base escolhidos.</p>
</section>
<?php endif; ?>
