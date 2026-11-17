<?php
/**
 * Demonstração do índice bruto e de três escalas históricas opcionais.
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
  'bruta' => [
    'titulo' => 'Têmporis principal — Acumulado bruto K(t)',
    'curta' => 'Principal / K(t)',
    'peso' => 'wᵢ = (Iᵢ / 10)² · Cᵢ · Fᵢ',
    'indice' => 'K(t) = Σ pesos até a data',
    'desc' => 'Sem compressão e sem ano-base; valor somado diretamente dos marcos.',
    'cor' => '#165c4e',
    'corFill' => 'rgba(20,92,78,0.08)',
  ],
  'empirica' => [
    'titulo' => 'Relativa proporcional (opcional) — base 100',
    'curta'  => 'Relativa / Proporcional',
    'peso'   => 'wᵢ = (Iᵢ / 10)² · Cᵢ · Fᵢ',
    'indice' => 'T(t) = 100 · K(t) / K(t₀)',
    'desc'   => 'Transformação opcional para comparar proporções; o Índice Têmporis principal é o acumulado bruto K(t).',
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

    $out = '<svg viewBox="0 0 '.$W.' '.$H.'" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Comparação do índice bruto com três escalas opcionais">';
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
$tFinal = TemporisFinal::indice($k2026,$Kbase,'bruta');
$tLog = TemporisFinal::indice($k2026,$Kbase,'log');
$tLeg = TemporisFinal::indice($k2026,$Kbase,'pot065');
$ln1k = log1p(max(0.0,$k2026));
$ln1k0 = log1p(max(0.0,$Kbase));
$fonteLabel = $fonte==='curadoria' ? 'Curadoria ativa (MySQL)' : ($fonte==='final' ? 'Base isolada MAT Final (MySQL)' : ($fonte==='snapshot' ? 'Exportação estática (snapshot)' : 'Fonte automática'));
?>

<div class="mat-ed" id="mat-demo-formulas">

  <header class="mat-ed-hero">
    <div class="mat-ed-hero-top">
      <span class="mat-ed-eyebrow"><span class="mat-ed-dot" aria-hidden="true"></span>ÍNDICE TÊMPORIS / DEMONSTRAÇÃO SIMPLES · FÓRMULAS EM FUNCIONAMENTO</span>
      <span class="mat-ed-year">EDIÇÃO DE PESQUISA · MMXXVI</span>
    </div>
    <div class="mat-ed-hero-body">
      <div>
        <h1>Demonstrativo <em>das fórmulas.</em></h1>
        <p>Mesma base de dados, mesmo acúmulo <strong>K(t)</strong> — e as comparações opcionais que transformam esse acúmulo em índice <strong>T(t)</strong>. Cada passo fica aberto para conferência.</p>
      </div>
      <div class="mat-ed-hero-mark" aria-hidden="true"><span>4</span><small>FÓRMULAS À VISTA</small></div>
    </div>
<?php if(!$erro): ?>
    <div class="mat-ed-hero-bottom">
      <span><?=h($fonteLabel)?></span>
      <span>Banco <code><?=h($banco)?></code></span>
      <span><?=$fmt(count($pontos),0)?> marcos · somente leitura</span>
    </div>
<?php endif; ?>
  </header>

  <nav class="mat-ed-nav" aria-label="Ir para as seções da demonstração">
    <a href="#demo-dados">Dados de hoje</a>
    <a href="#demo-grafico">Gráfico comparativo</a>
    <a href="#demo-passo">Passo a passo</a>
    <a href="#demo-escalas">K bruto e escalas</a>
    <a href="#demo-tabela">Resultados numéricos</a>
  </nav>

  <section id="demo-dados" class="mat-ed-section">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">01 / CONEXÃO E DADOS ATUAIS</span><h2 id="demo-dados-t">De onde vêm os números <em>agora.</em></h2></div><span class="mat-ed-source-pill">Somente leitura</span></div>
<?php if ($erro): ?>
    <div class="mat-ed-warning" role="alert"><strong>Não foi possível carregar os dados:</strong> <?=h($erro)?>. Tente outra fonte: <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'auto','base'=>$base,'coorte'=>$coorte])?>">automática</a> · <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'curadoria','base'=>$base,'coorte'=>$coorte])?>">curadoria (MySQL)</a> · <a href="<?=mat_url('demonstracao_formulas',['fonte'=>'snapshot','base'=>$base,'coorte'=>$coorte])?>">snapshot</a>.</div>
<?php else: ?>
    <form method="get" action="index.php" class="mat-ed-console">
      <input type="hidden" name="page" value="demonstracao_formulas">
      <div class="mat-ed-console-head"><strong>Console da demonstração</strong><span>Origem: <code><?=h($banco)?></code> · Solicitado: <?=h(TemporisFinal::FONTES[$fontePedida] ?? $fontePedida)?></span></div>
      <div class="mat-ed-console-fields">
        <label><span>Fonte de dados</span><select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$desc): ?><option value="<?=h($id)?>" <?=$fontePedida===$id?'selected':''?>><?=h($desc)?></option><?php endforeach; ?></select></label>
        <label><span>Seleção de marcos</span><select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$desc): ?><option value="<?=h($id)?>" <?=$coorte===$id?'selected':''?>><?=h($desc)?></option><?php endforeach; ?></select></label>
        <label><span>Ano-base (escalas relativas)</span><input type="number" name="base" value="<?=$base?>" min="1600" max="2000"></label>
        <button class="mat-ed-run" type="submit">Recalcular →</button>
      </div>
    </form>
    <div class="mat-ed-cards" style="margin-top:16px">
      <div class="mat-ed-card mat-ed-card-lead">
        <span class="mat-ed-card-label">Marcos na seleção atual</span>
        <div class="mat-ed-card-number"><?=$fmt(count($pontos),0)?></div>
        <p>De <b><?=$fmt($dados['audit']['elegiveis'] ?? 0,0)?></b> elegíveis · <b><?=$fmt($dados['audit']['total'] ?? 0,0)?></b> na fonte.</p>
      </div>
      <div class="mat-ed-card">
        <span class="mat-ed-card-label">Acúmulo no ano-base</span>
        <div class="mat-ed-card-number">K(<?=$base?>) <span>=</span> <?=$fmt($Kbase,3)?></div>
        <p>Referência usada apenas pelas escalas opcionais.</p>
      </div>
      <div class="mat-ed-card">
        <span class="mat-ed-card-label">Acúmulo atual</span>
        <div class="mat-ed-card-number">K(2026) <span>=</span> <?=$fmt($k2026,3)?></div>
        <p>Razão K(2026)/K(<?=$base?>) = <b><?=$ratio===null?'—':$fmt($ratio,4)?></b>.</p>
      </div>
      <div class="mat-ed-card">
        <span class="mat-ed-card-label">Resultado final · índice bruto</span>
        <div class="mat-ed-card-number"><?=$fmt($tFinal,2)?></div>
        <p>K(2026) sem ano-base — o valor usado na previsão.</p>
      </div>
    </div>
    <?php if ($Kbase <= 0): ?>
      <div class="mat-ed-warning" style="margin-top:15px"><strong>Atenção:</strong> K(<?=$base?>)=0 nesta seleção; somente as escalas relativas ficam indefinidas. O índice bruto K(t) continua válido.</div>
    <?php endif; ?>
<?php endif; ?>
  </section>

<?php if (!$erro): ?>
  <section id="demo-grafico" class="mat-ed-section" aria-labelledby="demo-grafico-t">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">02 / GRÁFICO COMPARATIVO · K(t) BRUTO E 3 ESCALAS OPCIONAIS</span><h2 id="demo-grafico-t">Como cada fórmula transforma o <em>mesmo acúmulo</em></h2></div><span class="mat-ed-source-pill">Mesmos marcos · mesmos pesos</span></div>
    <p class="mat-ed-intro">Mesmos marcos, mesmas datas e mesmos pesos → mesmo <strong>K(t)</strong>. O que muda é apenas a transformação matemática para obter <strong>T(t)</strong>. O acumulado bruto é o método atual; as demais curvas são apenas formas alternativas de apresentação.</p>
    <div class="mat-ed-chart"><?=$svgChart($tabela,$formulas,$base)?></div>
    <p class="mat-ed-table-note">Dica: passe o mouse sobre os pontos para ver os valores. Os anos do eixo horizontal estão na proporção temporal real.</p>
  </section>

  <section id="demo-passo" class="mat-ed-section" aria-labelledby="demo-passo-t">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">03 / DECOMPOSIÇÃO DA FÓRMULA · DO MARCO AO ÍNDICE</span><h2 id="demo-passo-t">Como a fórmula funciona, <em>parte por parte</em></h2></div><span class="mat-ed-source-pill">4 etapas</span></div>
    <div class="mat-ed-steps">
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">Etapa 1 · Notas do marco</span>
        <h4>Rubricas (0 a 3)</h4>
        <p>Cada marco recebe 11 notas, agrupadas em somas:</p>
        <div class="mat-ed-formula">S<sub>I</sub> = soma das 4 rubricas de Impacto ∈ [0,12]<br>S<sub>C</sub> = soma das 4 rubricas de Confiabilidade ∈ [0,12]<br>S<sub>F</sub> = soma das 3 rubricas de Fundação ∈ [0,9]</div>
      </div>
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">Etapa 2 · Dimensões</span>
        <h4>Conversão das somas em I, C, F</h4>
        <div class="mat-ed-formula">I = 1 + 9·(S<sub>I</sub>/12) &nbsp;⇒&nbsp; I ∈ [1, 10]<br>C = S<sub>C</sub>/12 &nbsp;⇒&nbsp; C ∈ [0, 1]<br>F = 1 + 0,5·(S<sub>F</sub>/9) &nbsp;⇒&nbsp; F ∈ [1; 1,5]</div>
        <p>Médias na seleção atual: I=<?=$fmt($stats['I'],3)?>, C=<?=$fmt($stats['C'],3)?>, F=<?=$fmt($stats['F'],3)?>.</p>
      </div>
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">Etapa 3 · Peso do marco</span>
        <h4>Peso wᵢ e contribuição</h4>
        <div class="mat-ed-formula">fator impacto = (I / 10)<br>impacto² = (I / 10)²<br>wᵢ = (I / 10)² · C · F</div>
        <p>Peso médio: w=<?=$fmt($stats['w'],4)?> (min <?=$fmt($stats['wmin'],4)?>, máx <?=$fmt($stats['wmax'],4)?>).</p>
      </div>
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">Etapa 4 · Acúmulo no tempo</span>
        <h4>Construção de K(t)</h4>
        <div class="mat-ed-formula">K(t) = Σ wᵢ &nbsp; para todo marco i com ano tᵢ ≤ t</div>
        <p>Exemplo com dados atuais: <code>K(<?=$base?>)=<?=$fmt($Kbase,4)?></code>, <code>K(2026)=<?=$fmt($k2026,4)?></code>.</p>
      </div>
    </div>

    <?php if ($exemplo): 
      $eI=(float)$exemplo['I']; $eC=(float)$exemplo['C']; $eF=(float)$exemplo['F']; $eW=(float)$exemplo['pesos']['quadratica'];
      $eImp = $eI/10.0; $eImp2 = $eImp*$eImp; $eAno=(int)$exemplo['year'];
    ?>
    <details class="mat-ed-details" style="margin-top:14px" open>
      <summary><strong>Exemplo real decomposto (1 marco do banco)</strong></summary>
      <div class="mat-ed-details-body">
        <div class="mat-ed-steps">
          <div class="mat-ed-step">
            <span class="mat-ed-step-k">Marco exemplo</span>
            <h4><?=h((string)$exemplo['titulo'])?> <span class="mat-ed-row-mark"><?=$eAno?></span></h4>
            <p>ID: <code><?=h((string)$exemplo['id'])?></code><br>Dimensões calculadas a partir das notas: <code>I=<?=$fmt($eI,6)?></code>, <code>C=<?=$fmt($eC,6)?></code>, <code>F=<?=$fmt($eF,6)?></code></p>
          </div>
          <div class="mat-ed-step">
            <span class="mat-ed-step-k">Cálculo do peso wᵢ</span>
            <h4>Substituindo na fórmula</h4>
            <div class="mat-ed-formula">(I/10) = <?=$fmt($eI,6)?> / 10 = <?=$fmt($eImp,6)?><br>(I/10)² = <?=$fmt($eImp,6)?>² = <?=$fmt($eImp2,6)?><br>wᵢ = <?=$fmt($eImp2,6)?> · <?=$fmt($eC,6)?> · <?=$fmt($eF,6)?><br><strong>wᵢ = <?=$fmt($eW,6)?></strong></div>
          </div>
          <div class="mat-ed-step">
            <span class="mat-ed-step-k">Contribuição para K(t)</span>
            <h4>Efeito no acúmulo</h4>
            <p>Esse marco soma <strong><?=$fmt($eW,6)?></strong> a <code>K(t)</code> em todos os anos <code>t ≥ <?=$eAno?></code>. K(t) é o próprio Têmporis principal; escalas normalizadas são secundárias.</p>
          </div>
        </div>
      </div>
    </details>
    <?php endif; ?>
  </section>

  <section id="demo-escalas" class="mat-ed-section" aria-labelledby="demo-escalas-t">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">04 / ÍNDICE PRINCIPAL E ESCALAS COMPARATIVAS · ANO 2026, BASE OPCIONAL <?=$base?></span><h2 id="demo-escalas-t">Primeiro o K bruto, <em>depois a comparação</em></h2></div><span class="mat-ed-source-pill">Mesmo acervo</span></div>
    <div class="mat-ed-steps">
      <div class="mat-ed-step">
        <span class="mat-ed-step-k">Fórmula principal · K(t)</span>
        <h4>Acumulado bruto</h4>
        <div class="mat-ed-formula">K(t) = Σ wᵢ, anoᵢ ≤ t<br><br>K(2026) = <strong><?=$fmt($tFinal,6)?></strong></div>
        <p>Esta é a medida que usamos na previsão. 1900 não participa deste cálculo.</p>
      </div>
      <div class="mat-ed-step mat-ed-step-alt-rel">
        <span class="mat-ed-step-k">Escala opcional · proporcional</span>
        <h4>Ano-base = 100 (comparação)</h4>
        <div class="mat-ed-formula">N(t) = 100 · K(t) / K(<?=$base?>)<br>N(2026) = <?=$fmt(TemporisFinal::indice($k2026,$Kbase,'empirica'),6)?></div>
        <p>É uma regra de exibição; o crescimento bruto continua inalterado.</p>
      </div>
      <div class="mat-ed-step mat-ed-step-alt-log">
        <span class="mat-ed-step-k">Escala opcional · reformulada</span>
        <h4>Transformação logarítmica</h4>
        <div class="mat-ed-formula">T(t) = 100 · ln(1 + K(t)) / ln(1 + K(t₀))<br><br>ln(1 + K(2026)) = ln(1 + <?=$fmt($k2026,6)?>) = <strong><?=$fmt($ln1k,6)?></strong><br>ln(1 + K(<?=$base?>)) = ln(1 + <?=$fmt($Kbase,6)?>) = <strong><?=$fmt($ln1k0,6)?></strong><br>T(2026) = 100 · <?=$fmt($ln1k,6)?> / <?=$fmt($ln1k0,6)?> = <strong><?=$fmt($tLog,6)?></strong></div>
        <p>Interpretação: comprime a escala quando K cresce muito.</p>
      </div>
      <div class="mat-ed-step mat-ed-step-alt-leg">
        <span class="mat-ed-step-k">Escala opcional · legada (adaptada)</span>
        <h4>Potência 0,65</h4>
        <div class="mat-ed-formula">T(t) = 100 · (K(t) / K(t₀))^0,65<br><br>R = K(2026)/K(<?=$base?>) = <strong><?=$ratio===null?'—':$fmt($ratio,6)?></strong><br>R^0,65 = (<?=$ratio===null?'—':$fmt($ratio,6)?>)^0,65 = <strong><?=$ratio===null?'—':$fmt(pow((float)$ratio,0.65),6)?></strong><br>T(2026) = 100 · R^0,65 = <strong><?=$fmt($tLeg,6)?></strong></div>
        <p>Interpretação: atenua o crescimento pela potência &lt; 1.</p>
      </div>
    </div>
  </section>

  <section id="demo-tabela" class="mat-ed-section" aria-labelledby="demo-tabela-t">
    <div class="mat-ed-section-heading"><div><span class="mat-ed-overline">05 / RESULTADOS NUMÉRICOS · TABELA COMPARATIVA POR ANO</span><h2 id="demo-tabela-t">Valores calculados, <em>mesma base de dados</em></h2></div><span class="mat-ed-source-pill">Ano-base <?=$base?> = 100</span></div>
    <div class="mat-ed-table-wrap">
      <table class="mat-ed-table">
        <thead>
          <tr>
            <th>Ano</th>
            <th>K(t) — acúmulo</th>
            <th>Razão K/K(<?=$base?>)</th>
            <th>Relativa (base 100)</th>
            <th>Reformulada (log)</th>
            <th>Legada (pot. 0,65)</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($tabela as $row): $isBase=((int)$row['ano']===$base); $rk = ($Kbase>0)?($row['k']/$Kbase):null; ?>
          <tr <?=$isBase?'class="mat-ed-featured-row"':''?>>
            <th><?= (int)$row['ano'] ?><?=$isBase?' (base)':''?></th>
            <td><?=$fmt($row['k'],4)?></td>
            <td><?=$rk===null?'—':$fmt($rk,4)?></td>
            <td><span class="mat-ed-result"><?=$fmt($row['empirica'],4)?></span></td>
            <td><?=$fmt($row['log'],4)?></td>
            <td><?=$fmt($row['pot065'],4)?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="mat-ed-table-note">A primeira coluna numérica, <strong>K(t) bruto</strong>, é o índice científico principal. As demais são transformações opcionais usadas apenas para comparar escalas. Os modelos preditivos usam K(t), sem base 100.</p>
  </section>

  <footer class="mat-ed-footnote">
    <p><strong>Uso rápido:</strong> link direto <code><?=h((isset($_SERVER['HTTP_HOST'])?((!empty($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']):'').strtok($_SERVER['REQUEST_URI'] ?? 'index.php?page=demonstracao_formulas','?'))?>?page=demonstracao_formulas</code>. Esta página é demonstrativa e de somente leitura: não altera notas nem aprova marcos. Os resultados dependem da fonte, da seleção e do ano-base escolhidos.</p>
    <div class="mat-ed-actions">
      <a class="mat-ed-button" href="<?=mat_url('metodo')?>">Caderno de fórmulas →</a>
      <a class="mat-ed-button" href="<?=mat_url('backtesting')?>">Teste das previsões →</a>
      <a class="mat-ed-textlink" href="<?=mat_url('temporis')?>">Voltar ao Índice Têmporis</a>
    </div>
  </footer>
<?php endif; ?>
</div>
