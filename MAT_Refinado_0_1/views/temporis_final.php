<?php
/** Índice Têmporis · método Final. Curadoria ativa é priorizada; arquivos estáticos identificados. */
require_once MAT_ROOT.'/app/TemporisFinal.php';
$tfGet = static function(string $key,string $fallback,array $choices):string{
    $value=(string)($_GET[$key]??$fallback);
    return isset($choices[$value])?$value:$fallback;
};
$tfFontePedida=$tfGet('fonte','auto',TemporisFinal::FONTES);
$tfCoorte=$tfGet('coorte','ampla',TemporisFinal::COORTES);
$tfPeso=$tfGet('peso','quadratica',TemporisFinal::PESOS);
$tfEscala=$tfGet('escala','bruta',TemporisFinal::ESCALAS);
$tfBase=filter_var($_GET['base']??1900,FILTER_VALIDATE_INT);
$tfBase=$tfBase===false?1900:max(1600,min(2000,$tfBase));
$tfCorte=filter_var($_GET['corte']??2026,FILTER_VALIDATE_INT);
$tfCorte=$tfCorte===false?2026:max(1700,min(2026,$tfCorte));
$tfErro=null;$tfFonte='desconhecida';$tfBanco='—';$tfDados=null;
$tfTodos=[];$tfPontos=[];$tfLinha=[];$tfHistorico=[];$tfPeriodos=[];$tfProjecoes=[];$tfIndex=null;
$tfConsulta=null;$tfConsultaErro=null;$tfAvanco=null;
$tfNumber=static fn(?float $v,int $digits=2):string=>$v===null?'—':number_format($v,$digits,',','.');
try {
    $resultado=TemporisFinal::obterDados($tfFontePedida,2026);
    $tfDados=$resultado['dados'];$tfFonte=$resultado['fonte'];$tfBanco=$resultado['database'];
    $tfTodos=$tfDados['pontos'];$tfPontos=TemporisFinal::filtrar($tfTodos,$tfCoorte);
    $tfLinha=TemporisFinal::linha($tfPontos,$tfPeso);
    $tfKbase=TemporisFinal::k($tfLinha,$tfBase);
    $tfK2026=TemporisFinal::k($tfLinha,2026);
    $tfIndex=TemporisFinal::indice($tfK2026,$tfKbase,$tfEscala);
    // Consulta pontual: Têmporis de um ano e avanço entre dois anos.
    // Reutilizam a mesma linha de pesos (marcos reais do banco), sem interpolação de valores.
    $tfAnoConsulta=filter_var($_GET['ano_consulta']??null,FILTER_VALIDATE_INT);
    $tfAnoA=filter_var($_GET['ano_a']??null,FILTER_VALIDATE_INT);
    $tfAnoB=filter_var($_GET['ano_b']??null,FILTER_VALIDATE_INT);
    if($tfAnoConsulta!==false&&$tfAnoConsulta!==null){
        $tfAnoConsulta=max(-9999,min(2100,$tfAnoConsulta));
        $tfKConsulta=TemporisFinal::k($tfLinha,$tfAnoConsulta);
        $tfConsulta=['ano'=>$tfAnoConsulta,'k'=>$tfKConsulta,
            't'=>TemporisFinal::indice($tfKConsulta,$tfKbase,$tfEscala),
            'marcos'=>count(array_filter($tfPontos,static fn(array $p):bool=>$p['year']<=$tfAnoConsulta))];
    }
    if($tfAnoA!==false&&$tfAnoA!==null&&$tfAnoB!==false&&$tfAnoB!==null){
        $tfAnoA=max(-9999,min(2100,$tfAnoA));$tfAnoB=max(-9999,min(2100,$tfAnoB));
        if($tfAnoA===$tfAnoB){$tfConsultaErro='Escolha dois anos diferentes para calcular o avanço.';}
        else{
            if($tfAnoA>$tfAnoB){[$tfAnoA,$tfAnoB]=[$tfAnoB,$tfAnoA];}
            $tfKa=TemporisFinal::k($tfLinha,$tfAnoA);$tfKb=TemporisFinal::k($tfLinha,$tfAnoB);
            $tfTa=TemporisFinal::indice($tfKa,$tfKbase,$tfEscala);$tfTb=TemporisFinal::indice($tfKb,$tfKbase,$tfEscala);
            $tfSpan=max(1,abs($tfAnoB-$tfAnoA));
            $tfAvanco=['a'=>$tfAnoA,'b'=>$tfAnoB,'ka'=>$tfKa,'kb'=>$tfKb,'ta'=>$tfTa,'tb'=>$tfTb,
                'dt'=>($tfTa!==null&&$tfTb!==null)?$tfTb-$tfTa:null,'dk'=>$tfKb-$tfKa,
                'marcos'=>count(array_filter($tfPontos,static fn(array $p):bool=>$p['year']>=$tfAnoA&&$p['year']<=$tfAnoB)),
                'anos'=>$tfSpan,'taxa'=>($tfKb-$tfKa)/$tfSpan];
        }
    }
    $tfAuditoria=$tfDados['audit'];
    $tfValidos=count(array_filter($tfPontos,static fn($p)=>$p['ano_validado']));
    $tfSem=count(array_filter($tfPontos,static fn($p)=>$p['sem_evidencia']));
    $tfProv=count(array_filter($tfPontos,static fn($p)=>$p['provisorio']));
    $tfExtra=count(array_filter($tfPontos,static fn($p)=>$p['ano_extraido']));
    foreach([1700,1750,1800,1850,1900,1950,2000,2026] as $year){
        $k=TemporisFinal::k($tfLinha,$year);
        $row=['ano'=>$year,'k'=>$k];
        foreach(TemporisFinal::ESCALAS as $escala=>$rotulo)$row[$escala]=TemporisFinal::indice($k,$tfKbase,$escala);
        $tfHistorico[]=$row;
    }
    foreach([[1701,1751],[1751,1801],[1801,1851],[1851,1901],[1901,1951],[1951,2001],[2001,2027]] as [$ini,$fim]) {
        $part=TemporisFinal::periodo($tfPontos,$tfPeso,$ini,$fim);
        $part['de']=$ini;$part['ate']=$fim;
        $tfPeriodos[]=$part;
    }
} catch (Throwable $e) {$tfErro=$e->getMessage();}

// Gráfico SVG próprio: eixo horizontal ano real; nunca usar quantidade de registros como escala temporal.
$tfSvg=function(array $series,array $years,int $height=290):string{
    $W=1000;$left=84;$right=944;$top=30;$bottom=$height-58;
    $start=min($years);$end=max($years);$values=[];
    foreach($series as $s)foreach($s['data'] as $year=>$v)if($v!==null && is_finite((float)$v))$values[]=(float)$v;
    if(!$values)return '<p style="margin:0;color:#78877d;font-size:.87rem">Sem série normalizável para essa seleção.</p>';
    $lo=min($values);$hi=max($values);$span=max(1.,$hi-$lo);$lo=max(0.,$lo-$span*.12);$hi+=$span*.12;
    $x=static fn($year)=>$left+($year-$start)/max(1,$end-$start)*($right-$left);
    $y=static fn($v)=>$bottom-($v-$lo)/max(.00001,$hi-$lo)*($bottom-$top);
    $f=static fn($v)=>number_format((float)$v,2,'.','');
    $out='<svg viewBox="0 0 '.$W.' '.$height.'" role="img" aria-label="Evolução histórica do Índice Têmporis, com anos proporcionais" xmlns="http://www.w3.org/2000/svg">';
    $out.='<defs><filter id="tfShadow" x="-10%" y="-10%" width="120%" height="120%"><feDropShadow dx="0" dy="1.4" stdDeviation="1.4" flood-color="#0d2d23" flood-opacity="0.10"/></filter></defs>';
    $out.='<rect x="'.$left.'" y="'.$top.'" width="'.($right-$left).'" height="'.($bottom-$top).'" rx="11" fill="#fcfbf7" stroke="#ece6d8"/>';
    for($i=0;$i<=4;$i++){$v=$lo+($hi-$lo)*$i/4;$Y=$y($v);
        $out.='<line x1="'.$left.'" x2="'.$right.'" y1="'.$f($Y).'" y2="'.$f($Y).'" stroke="'.($i===0?'#d8d1c2':'#e8e1d2').'" stroke-width="1" '.($i===0?'':'stroke-dasharray="4 5"').'/>';
        $out.='<text x="'.($left-10).'" y="'.$f($Y+4).'" text-anchor="end" fill="#5a6b64" font-size="12" font-family="system-ui,Segoe UI,Arial">'.number_format($v,1,',','.').'</text>';
    }
    foreach($years as $year){$X=$x($year);
        $out.='<line x1="'.$f($X).'" y1="'.$top.'" x2="'.$f($X).'" y2="'.$bottom.'" stroke="#efe9dc" stroke-width="1"/>';
        $out.='<text x="'.$f($X).'" y="'.($bottom+24).'" text-anchor="middle" fill="#526b5f" font-size="12" font-family="system-ui,Segoe UI,Arial">'.(int)$year.'</text>';
    }
    foreach($series as $s){$path='';$first=true;
        foreach($s['data'] as $year=>$v){if($v===null)continue;$path.=($first?'M':'L').$f($x((int)$year)).' '.$f($y((float)$v)).' ';$first=false;}
        $color=htmlspecialchars($s['color'],ENT_QUOTES);$label=htmlspecialchars($s['name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $out.='<path d="'.trim($path).'" fill="none" stroke="'.$color.'" stroke-width="'.($s['principal']??false?'4':'2.5').'" stroke-linecap="round" stroke-linejoin="round" '.(!($s['principal']??false)?'stroke-dasharray="7 5"':'').' filter="url(#tfShadow)"><title>'.$label.'</title></path>';
        $tfPts=[];
        foreach($s['data'] as $year=>$v){if($v===null)continue;$tfPts[]=[$x((int)$year),$y((float)$v),(int)$year,(float)$v];}
        if(($s['principal']??false))foreach($tfPts as [$px,$py,$yr,$v]){
            $out.='<circle cx="'.$f($px).'" cy="'.$f($py).'" r="4.4" fill="#fff" stroke="'.$color.'" stroke-width="2.2"><title>'.$label.' · '.$yr.' · '.$f($v).'</title></circle>';
            if(in_array($yr,[1900,1950,2000,2026],true)){
                $tly=$py-12;if($tly<$top+14)$tly=$py+20;
                $out.='<text x="'.$f($px).'" y="'.$f($tly).'" text-anchor="middle" font-size="11.5" font-weight="700" fill="'.$color.'" font-family="system-ui,Segoe UI,Arial">'.number_format($v,1,',','.').'</text>';
            }
        }
    }
    $out.='<text x="'.($left+($right-$left)/2).'" y="'.($height-16).'" text-anchor="middle" font-size="12.5" fill="#4b5d56" font-family="system-ui,Segoe UI,Arial">Ano (distância cronológica proporcional)</text>';
    $out.='<text transform="translate(24,'.($top+($bottom-$top)/2).') rotate(-90)" text-anchor="middle" font-size="12.5" fill="#4b5d56" font-family="system-ui,Segoe UI,Arial">Índice Têmporis</text>';
    $lx=$left+16;$ly=$top+16;$li=0;
    foreach($series as $s){$yy=$ly+$li*20;$color=htmlspecialchars($s['color'],ENT_QUOTES);$label=htmlspecialchars($s['name'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
        $out.='<line x1="'.$lx.'" y1="'.$yy.'" x2="'.($lx+24).'" y2="'.$yy.'" stroke="'.$color.'" stroke-width="'.($s['principal']??false?4:2.5).'" '.(!($s['principal']??false)?'stroke-dasharray="7 5"':'').'/>';
        $out.='<text x="'.($lx+32).'" y="'.($yy+4).'" font-size="12" fill="#33413b" font-family="system-ui,Segoe UI,Arial">'.$label.'</text>';$li++;}
    return $out.'</svg>';
};
?>
<div class="mat-bt" id="mat-temporis-final">

  <header class="mat-bt-hero">
    <div class="mat-bt-hero-top">
      <span class="mat-bt-eyebrow"><span class="mat-bt-dot" aria-hidden="true"></span>Índice Têmporis · MAT Final · Formulação em teste</span>
      <span class="mat-bt-year">Edição de pesquisa · MMXXVI</span>
    </div>
    <div class="mat-bt-hero-body">
      <div>
        <h1>Índice Têmporis <em>· método atual.</em></h1>
        <p>Primeiro, o que a nossa metodologia registra. A comparação com fórmulas alternativas e os modelos de previsão ficam disponíveis apenas quando você quiser examiná-los.</p>
      </div>
<?php if(!$tfErro): ?>
      <div class="mat-bt-hero-mark" aria-hidden="true"><span><?=$tfNumber($tfIndex)?></span><small>Têmporis · 2026</small></div>
<?php endif; ?>
    </div>
<?php if(!$tfErro): ?>
    <div class="mat-bt-hero-bottom">
      <span><?= $tfFonte==='snapshot'?'Exportação estática (não atualiza)':'MySQL conectado · leitura em tempo real' ?></span>
      <span>Origem: <code><?=h($tfBanco)?></code></span>
      <span><?=count($tfPontos)?> registros efetivamente calculados</span>
    </div>
<?php endif; ?>
  </header>

<?php if($tfErro): ?>
  <div class="mat-bt-warning" role="alert" style="margin-top:22px"><strong>Os dados solicitados não puderam ser carregados:</strong> <?=h($tfErro)?>. <a href="<?=mat_url('temporis',['fonte'=>'auto'])?>">Tentar fonte automática</a> · <a href="<?=mat_url('temporis',['fonte'=>'snapshot'])?>">Ver exportação histórica</a>. Não apresentamos um valor de outra fonte como se fosse o banco solicitado.</div>
</div>
<?php return; endif; ?>

  <nav class="mat-bt-nav" aria-label="Seções do índice">
    <a href="#tf-principal">Resultado</a>
    <a href="#tf-consulta">Consulta direta</a>
    <a href="#tf-curva">Curva observada</a>
    <a href="#tf-historico">K(t) de 50 em 50</a>
    <a href="#tf-ritmo">Ritmo histórico</a>
    <a href="#tf-previsoes">Modelos de previsão</a>
  </nav>

  <section id="tf-principal" class="mat-bt-section" aria-labelledby="tf-principal-titulo">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Resultado da formulação final</span><h2 id="tf-principal-titulo">O índice calculado com os <em>marcos selecionados</em></h2></div><span class="mat-bt-source-pill"><?=h(TemporisFinal::ESCALAS[$tfEscala])?><?= $tfEscala==='bruta' ? ' · sem ano-base' : ' · referência '.$tfBase.' = 100' ?></span></div>
    <div class="mat-bt-cards mat-bt-cards-4">
      <div class="mat-bt-card mat-bt-card-lead">
        <span class="mat-bt-card-label">Têmporis · 2026</span>
        <div class="mat-bt-card-number"><?=$tfNumber($tfIndex)?></div>
        <p><?=count($tfPontos)?> registros efetivamente calculados.</p>
      </div>
      <div class="mat-bt-card">
        <span class="mat-bt-card-label">Fórmula do índice</span>
        <div class="mat-bt-formula">K(t) = Σ wᵢ (marcos até o ano t)</div>
        <p>O peso de cada marco é <strong>w = (I/10)² × C × F</strong>, e K(t) soma os pesos dos marcos datados até o ano t.</p>
      </div>
      <div class="mat-bt-card">
        <span class="mat-bt-card-label">O que este número representa</span>
        <strong>Leitura responsável</strong>
        <p>O índice bruto não depende de 1900; a normalização é apenas uma forma opcional de exibição. Acumular não demonstra, sozinho, aceleração.</p>
      </div>
      <div class="mat-bt-card">
        <span class="mat-bt-card-label">Documentação</span>
        <strong>Caderno de fórmulas</strong>
        <p><a href="<?=mat_url('metodo')?>">Ver as seis fórmulas no caderno →</a></p>
      </div>
    </div>
<?php if($tfFonte==='snapshot'):?><div class="mat-bt-warning" role="alert" style="margin-top:15px"><strong>Estes números NÃO vêm do banco de dados.</strong> O MySQL da curadoria não respondeu nesta leitura, então a página exibiu a exportação estática de 07/11/2026 (800 candidatos), que não acompanha o acervo atual. Inicie o MySQL no XAMPP e recarregue para calcular sobre os dados reais; ou abra a fonte “Curadoria atual” diretamente para ver o erro de conexão.</div><?php endif; ?>
    <form class="mat-bt-console" method="get" action="index.php" style="margin-top:16px">
      <input type="hidden" name="page" value="temporis">
      <div class="mat-bt-console-head"><strong>Console do índice</strong><span>Solicitado: <?=h(TemporisFinal::FONTES[$tfFontePedida])?></span></div>
      <div class="mat-bt-console-fields">
        <label><span>Origem dos dados</span><select name="fonte"><?php foreach(TemporisFinal::FONTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfFontePedida===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
        <label><span>Seleção de marcos</span><select name="coorte"><?php foreach(TemporisFinal::COORTES as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfCoorte===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
        <button class="mat-bt-run" type="submit">Atualizar análise →</button>
      </div>
      <details class="mat-bt-details mat-bt-options" style="margin-top:15px;box-shadow:none" <?=($tfPeso!=='quadratica'||$tfEscala!=='bruta'||$tfBase!==1900||$tfCorte!==2026)?'open':''?>>
        <summary>Opções avançadas · pesos, escalas e ano-base</summary>
        <div class="mat-bt-details-body" style="padding-top:14px">
          <div class="mat-bt-console-fields">
            <label><span>Pesos</span><select name="peso"><?php foreach(TemporisFinal::PESOS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfPeso===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
            <label><span>Transformação do índice</span><select name="escala"><?php foreach(TemporisFinal::ESCALAS as $id=>$desc):?><option value="<?=h($id)?>" <?=$tfEscala===$id?'selected':''?>><?=h($desc)?></option><?php endforeach;?></select></label>
            <label><span>Ano-base (só para escalas relativas)</span><input type="number" name="base" value="<?=$tfBase?>" min="1600" max="2000"></label>
            <label><span>Ano de corte nas previsões</span><input type="number" name="corte" value="<?=$tfCorte?>" min="1700" max="2026"></label>
          </div>
        </div>
      </details>
    </form>
  </section>

  <section class="mat-bt-section" aria-label="Rastreabilidade da contagem dos marcos">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Rastreabilidade · contagem dos marcos</span><h2>Quem entra na curva — e <em>quem fica de fora</em></h2></div><span class="mat-bt-source-pill">Soma auditable</span></div>
    <div class="mat-bt-cards mat-bt-cards-4">
      <div class="mat-bt-card"><span class="mat-bt-card-label">Candidatos na fonte</span><div class="mat-bt-card-number"><?=$tfAuditoria['total']?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">Elegíveis para cálculo</span><div class="mat-bt-card-number"><?=$tfAuditoria['elegiveis']?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">Na seleção atual</span><div class="mat-bt-card-number"><?=count($tfPontos)?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">Não elegíveis</span><div class="mat-bt-card-number"><?=$tfAuditoria['total']-$tfAuditoria['elegiveis']?></div></div>
    </div>
    <details class="mat-bt-details" style="margin-top:13px"><summary>Por que nem todos os marcos aparecem? · detalhar exclusões e qualidade dos dados</summary>
      <div class="mat-bt-details-body">
        <p>O MAT <strong>não apaga</strong> os candidatos excluídos desta curva. A soma abaixo explica os registros existentes nesta fonte, e os filtros podem reduzir ainda mais a série calculada.</p>
        <div class="mat-bt-strip mat-bt-strip-4">
          <div><small>Rejeitados</small><b><?=$tfAuditoria['rejeitados']?></b></div>
          <div><small>Sem as 11 notas</small><b><?=$tfAuditoria['sem_11_notas']?></b></div>
          <div><small>Sem data utilizável</small><b><?=$tfAuditoria['sem_data']?></b></div>
          <div><small>Posteriores a 2026</small><b><?=$tfAuditoria['futuros']?></b></div>
          <div><small>Datas extraídas (na seleção)</small><b><?=$tfExtra?></b></div>
          <div><small>Notas provisórias (na seleção)</small><b><?=$tfProv?></b></div>
          <div><small>Com SEM_EVIDENCIA</small><b><?=$tfSem?></b></div>
          <div><small>Datas não extraídas (na seleção)</small><b><?=$tfValidos?></b></div>
        </div>
        <p>Os anos extraídos e as notas provisórias não se tornam validados por entrarem no ensaio. Para incluir registros sem ano será necessário revisar a datação, não atribuir uma data fictícia. Consulte o <a href="<?=mat_url('andamento')?>">acompanhamento da curadoria</a> para trabalhar no conjunto completo.</p>
      </div>
    </details>
  </section>
<?php if($tfKbase<=0 && $tfEscala!=='bruta'):?><div class="mat-bt-warning" role="alert">K(<?=$tfBase?>) = 0. O índice é matematicamente indefinido nessa seleção; altere a amostra ou o ano-base.</div><?php endif; ?>

  <section id="tf-consulta" class="mat-bt-section" aria-labelledby="tf-consulta-titulo">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Consulta direta · mesmos marcos · mesmos pesos · mesma escala</span><h2 id="tf-consulta-titulo">Têmporis de um ano e <em>avanço entre dois anos</em></h2></div><span class="mat-bt-source-pill">Sem interpolação</span></div>
    <p class="mat-bt-intro">As duas consultas somam os <strong>marcos reais do banco</strong> até o ano pedido, na seleção atual — nada é interpolado ou extrapolado. O avanço é a diferença do índice entre os dois anos, em pontos.</p>
    <form class="mat-bt-console" method="get" action="index.php">
      <input type="hidden" name="page" value="temporis">
      <input type="hidden" name="fonte" value="<?=h($tfFontePedida)?>">
      <input type="hidden" name="coorte" value="<?=h($tfCoorte)?>">
      <input type="hidden" name="peso" value="<?=h($tfPeso)?>">
      <input type="hidden" name="escala" value="<?=h($tfEscala)?>">
      <input type="hidden" name="base" value="<?=$tfBase?>">
      <input type="hidden" name="corte" value="<?=$tfCorte?>">
      <div class="mat-bt-console-head"><strong>Consulta 1 · Têmporis de um ano</strong><span>Ex.: 1969</span></div>
      <div class="mat-bt-console-fields">
        <label><span>Ano consultado</span><input type="number" name="ano_consulta" value="<?=$tfConsulta!==null?(string)$tfConsulta['ano']:''?>" min="-9999" max="2100" placeholder="Ex.: 1969" required></label>
        <button class="mat-bt-run" type="submit">Calcular do ano →</button>
      </div>
    </form>
    <form class="mat-bt-console" method="get" action="index.php" style="margin-top:11px">
      <input type="hidden" name="page" value="temporis">
      <input type="hidden" name="fonte" value="<?=h($tfFontePedida)?>">
      <input type="hidden" name="coorte" value="<?=h($tfCoorte)?>">
      <input type="hidden" name="peso" value="<?=h($tfPeso)?>">
      <input type="hidden" name="escala" value="<?=h($tfEscala)?>">
      <input type="hidden" name="base" value="<?=$tfBase?>">
      <input type="hidden" name="corte" value="<?=$tfCorte?>">
      <div class="mat-bt-console-head"><strong>Consulta 2 · Avanço entre dois anos</strong><span>Diferença em pontos</span></div>
      <div class="mat-bt-console-fields">
        <label><span>De</span><input type="number" name="ano_a" value="<?=$tfAvanco!==null?(string)$tfAvanco['a']:''?>" min="-9999" max="2100" placeholder="1950" required></label>
        <label><span>Até</span><input type="number" name="ano_b" value="<?=$tfAvanco!==null?(string)$tfAvanco['b']:''?>" min="-9999" max="2100" placeholder="2026" required></label>
        <button class="mat-bt-run" type="submit">Calcular avanço →</button>
      </div>
    </form>
    <?php if($tfConsultaErro):?><div class="mat-bt-warning" role="alert" style="margin-top:13px"><?=h($tfConsultaErro)?></div><?php endif; ?>
    <?php if($tfConsulta): ?>
    <div class="mat-bt-cards" style="margin-top:14px">
      <div class="mat-bt-card mat-bt-card-lead"><span class="mat-bt-card-label">Têmporis de <?=$tfConsulta['ano']?></span><div class="mat-bt-card-number"><?=$tfNumber($tfConsulta['t'])?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">K(<?=$tfConsulta['ano']?>) acumulado</span><div class="mat-bt-card-number"><?=$tfNumber($tfConsulta['k'],3)?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">Marcos até <?=$tfConsulta['ano']?></span><div class="mat-bt-card-number"><?=$tfConsulta['marcos']?></div></div>
    </div>
    <p class="mat-bt-table-note"><?= $tfEscala==='bruta' ? 'K(t) é a soma ponderada até a data, independente de 1900.' : 'O valor relativo foi obtido a partir de K(t) e da referência '.$tfBase.'; o cálculo bruto permanece inalterado.' ?><?php if($tfConsulta['t']===null):?> · <strong>indefinido nesta seleção</strong>: K(<?=$tfBase?>) é zero, então o índice não pode ser calculado<?php endif; ?>.</p>
    <?php endif; ?>
    <?php if($tfAvanco): ?>
    <div class="mat-bt-cards mat-bt-cards-4" style="margin-top:14px">
      <div class="mat-bt-card"><span class="mat-bt-card-label">Têmporis de <?=$tfAvanco['a']?></span><div class="mat-bt-card-number"><?=$tfNumber($tfAvanco['ta'])?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">Têmporis de <?=$tfAvanco['b']?></span><div class="mat-bt-card-number"><?=$tfNumber($tfAvanco['tb'])?></div></div>
      <div class="mat-bt-card mat-bt-card-lead"><span class="mat-bt-card-label">Avanço · T(<?=$tfAvanco['b']?>) − T(<?=$tfAvanco['a']?>)</span><div class="mat-bt-card-number"><?=$tfNumber($tfAvanco['dt'])?></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">ΔK no período</span><div class="mat-bt-card-number"><?=$tfNumber($tfAvanco['dk'],3)?></div></div>
    </div>
    <p class="mat-bt-table-note">Entre <?=$tfAvanco['a']?> e <?=$tfAvanco['b']?> entraram <strong><?=$tfAvanco['marcos']?> marcos</strong> (Σ pesos = <?=$tfNumber($tfAvanco['dk'],3)?>), ritmo de <?=$tfNumber($tfAvanco['taxa'],4)?> pontos de K por ano em <?=$tfAvanco['anos']?> anos. O avanço em Têmporis é <strong><?=$tfNumber($tfAvanco['dt'])?> pontos</strong><?php if($tfAvanco['ta']===null||$tfAvanco['tb']===null):?> · indefinido nesta seleção (K do ano-base é zero)<?php endif; ?>.</p>
    <?php endif; ?>
  </section>

  <section id="tf-curva" class="mat-bt-section" aria-labelledby="tf-curva-titulo">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">01 / Resultado observado · apenas o método selecionado</span><h2 id="tf-curva-titulo">A curva do <em>Têmporis</em></h2></div><span class="mat-bt-source-pill">Anos proporcionais</span></div>
    <p class="mat-bt-intro">Esta é a trajetória da <strong>formulação selecionada</strong>, com os anos na distância cronológica correta. O resultado principal fica sozinho para facilitar a leitura.</p>
    <?php $tfColors=['bruta'=>'#135d50','empirica'=>'#31917a','log'=>'#7d8290','pot065'=>'#b47953','pot125'=>'#715799'];$tfPlots=[];
    foreach(TemporisFinal::ESCALAS as $key=>$rotulo){$d=[];foreach($tfHistorico as $point)if(in_array($point['ano'],[1700,1800,1900,1950,2000,2026],true))$d[$point['ano']]=$point[$key];$tfPlots[$key]=['name'=>$rotulo,'data'=>$d,'color'=>$tfColors[$key],'principal'=>$key===$tfEscala];}
    ?>
    <div class="mat-final-chart"><?=$tfSvg([$tfPlots[$tfEscala]],[1700,1800,1900,2000,2026],320)?></div>
    <div class="mat-bt-strip mat-bt-strip-4" style="margin-top:15px"><?php foreach([1900,1950,2000,2026] as $yr): $row=null;foreach($tfHistorico as $item){if($item['ano']===$yr){$row=$item;break;}} ?><div><small><?=$yr?></small><b><?=$tfNumber($row[$tfEscala]??null)?></b></div><?php endforeach;?></div>
  </section>
  <details class="mat-bt-details" style="margin-top:12px"><summary><span><strong>Mostrar mais · outras fórmulas e escalas</strong> — K(t) é a medida principal; base 1900 e compressões são comparações opcionais</span></summary>
   <div class="mat-bt-details-body"><h3>As fórmulas sobre os mesmos dados</h3>
   <p>Usamos exatamente os mesmos marcos e pesos para visualizar as diferenças entre as transformações. A versão Legada aqui é a <strong>adaptação matemática às rubricas atuais</strong>, não uma reprodução do acervo legado original.</p>
   <div class="mat-final-chart"><?=$tfSvg(array_values($tfPlots),[1700,1800,1900,2000,2026],320)?></div>
   <div class="mat-bt-legend"><?php foreach(TemporisFinal::ESCALAS as $key=>$desc):?><span><i style="background:<?=h($tfColors[$key])?>"></i><?=h($desc)?></span><?php endforeach;?></div>
   <div class="mat-bt-table-wrap" style="margin-top:15px"><table class="mat-bt-table"><thead><tr><th>Escala</th><th>1900</th><th>1950</th><th>2000</th><th>2026</th></tr></thead><tbody><?php foreach(TemporisFinal::ESCALAS as $scale=>$desc):?><tr <?=$scale===$tfEscala?'class="mat-bt-featured-row"':''?>><th><?=h($desc)?></th><?php foreach([1900,1950,2000,2026] as $yr):?><td><?=$tfNumber(TemporisFinal::indice(TemporisFinal::k($tfLinha,$yr),$tfKbase,$scale))?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table></div>
   <p style="margin:12px 0 0"><a href="<?=mat_url('metodo')?>">Abrir o Caderno de Fórmulas →</a></p></div>
  </details>
<?php require_once MAT_ROOT.'/app/TemporisMetodologia2026.php';
$tfPassado=TemporisMetodologia2026::historico($tfLinha);
$tfV25=TemporisMetodologia2026::velocidade($tfLinha,2026,25);
$tfV50=TemporisMetodologia2026::velocidade($tfLinha,2026,50);
?>
  <section id="tf-historico" class="mat-bt-section" aria-label="Índice histórico bruto a cada cinquenta anos">
   <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Evolução histórica · valores observados no cadastro</span><h2>K(t) de 50 em 50 anos — <em>1700 a 2026</em></h2></div><span class="mat-bt-source-pill">Sem normalização · sem projeção</span></div>
   <p class="mat-bt-intro">Esta tabela soma diretamente as notas dos marcos cadastrados até cada ano. O último período tem <strong>26 anos</strong>, não 50; não compare os incrementos sem considerar a duração.</p>
   <div class="mat-bt-table-wrap"><table class="mat-bt-table"><thead><tr><th>Ano</th><th>Índice bruto K(t)</th><th>Δ pontos no período</th><th>Tempo transcorrido</th><th>Velocidade (pontos/ano)</th></tr></thead><tbody>
<?php foreach($tfPassado as $r):?><tr><th><?=$r['ano']?></th><td><?=$tfNumber($r['k'])?></td><td><?=$tfNumber($r['delta'])?></td><td><?=$r['duracao']===null?'—':$r['duracao'].' anos'?></td><td><?=$tfNumber($r['pontos_por_ano'],3)?></td></tr><?php endforeach;?></tbody></table></div>
   <div class="mat-bt-emphasis" style="margin-top:14px"><span class="mat-bt-emphasis-icon" aria-hidden="true">⚡</span><p><strong>Velocidade em 2026:</strong> média dos últimos 25 anos = <?=$tfNumber($tfV25,3)?> ponto(s)/ano; média dos últimos 50 anos = <?=$tfNumber($tfV50,3)?> ponto(s)/ano.</p></div>
   <p class="mat-bt-table-note">Valores condicionados à fonte e à coorte escolhidas. Ampliar o acervo para 2.000 marcos pode alterar valores históricos sem que a tecnologia do passado tenha mudado. Auditar datas, duplicatas e cobertura recente antes de concluir desaceleração ou aceleração.</p>
  </section>
  <section id="tf-ritmo" class="mat-bt-section"><div class="mat-bt-section-heading"><div><span class="mat-bt-overline">02 / Ritmo histórico · incremento observado · sem previsões</span><h2>Quanto peso novo foi <em>registrado por ano?</em></h2></div><span class="mat-bt-source-pill">Observado</span></div>
  <p class="mat-bt-intro">A barra mede a soma dos pesos em cada período, dividida pela sua duração; não é uma afirmação de causa ou aceleração comprovada.</p>
  <div class="mat-bt-bars"><?php $tfMaxRate=max(.00001,...array_column($tfPeriodos,'taxa'));foreach($tfPeriodos as $part):?>
  <div class="mat-bt-bar"><span><?=$part['de']?>–<?=$part['ate']-1?></span><div class="mat-bt-bar-track"><span style="width:<?=number_format(100*$part['taxa']/$tfMaxRate,2,'.','')?>%"></span></div><span class="mat-bt-bar-val"><strong><?=$tfNumber($part['taxa'],4)?></strong> · <?=$part['quantidade']?> marcos</span></div><?php endforeach;?></div>
  <details class="mat-bt-details" style="margin-top:13px"><summary>Mostrar dados completos dos períodos</summary><div class="mat-bt-details-body"><div class="mat-bt-table-wrap"><table class="mat-bt-table"><thead><tr><th>Intervalo</th><th>Marcos</th><th>Σ pesos</th><th>Peso/ano</th><th>Notas provisórias</th><th>Datas extraídas</th></tr></thead><tbody><?php foreach($tfPeriodos as $part):?><tr><th><?=$part['de']?>–<?=$part['ate']-1?></th><td><?=$part['quantidade']?></td><td><?=$tfNumber($part['incremento'],3)?></td><td><?=$tfNumber($part['taxa'],4)?></td><td><?=$part['provisorios']?></td><td><?=$part['datas_extraidas']?></td></tr><?php endforeach;?></tbody></table></div></div></details>
  </section>
  <details class="mat-bt-details" id="tf-previsoes" style="margin-top:26px"><summary><span><strong>Mostrar mais · modelos de previsão concorrentes</strong> — Linear, exponencial e alternativas · comparar WAPE antes de escolher</span></summary>
  <div class="mat-bt-details-body"><h3>O que os outros modelos projetam?</h3>
  <p><strong>Nenhum modelo foi declarado vencedor.</strong> O MAT calcula primeiro K(t), depois extrapola somente usando valores históricos anteriores ao corte. O WAPE deve ser consultado por horizonte, coorte e número de testes, sem usar o ano-base nas previsões.</p>
<?php
$tfKcut=TemporisFinal::k($tfLinha,$tfCorte);
$tfKold50=TemporisFinal::k($tfLinha,$tfCorte-50);
$tfG=($tfKcut>0 && $tfKold50>0)?(log($tfKcut/$tfKold50)/50.0):null;
$tfCutIndex=TemporisFinal::indice($tfKcut,$tfKbase,$tfEscala); ?>
  <?php if($tfG!==null):?><div class="mat-bt-emphasis" role="note"><span class="mat-bt-emphasis-icon" aria-hidden="true">%</span><p><strong>Taxa exponencial estimada:</strong> g = <?=number_format($tfG*100,3,',','.')?>% ao ano (últimos 50 anos, dados reais)</p></div><?php endif; ?>
  <div class="mat-bt-table-wrap" style="margin-top:14px"><table class="mat-bt-table"><thead><tr><th>Modelo</th><th>Índice no corte (<?=$tfCorte?>)</th><th>Projeção +25 anos (<?=$tfCorte+25?>)</th><th>Incremento previsto ΔK</th><th>WAPE histórico</th><th>Testes</th></tr></thead><tbody>
<?php foreach(TemporisFinal::MODELOS as $id=>$label):$v=TemporisFinal::projetar($tfLinha,$tfCorte,25,$id);$t=$v===null?null:TemporisFinal::indice($v,$tfKbase,$tfEscala);$stat=TemporisFinal::validar($tfLinha,$id,25,2026);?><tr><th><?=h($label)?></th><td><?=$tfNumber($tfCutIndex)?></td><td><?=$tfNumber($t)?></td><td><?=$v===null?'—':$tfNumber($v-$tfKcut,3)?></td><td><?=$stat['wape']===null?'—':$tfNumber($stat['wape'],1).'%'?></td><td><?=$stat['n']?></td></tr><?php endforeach;?></tbody></table></div>
  <p class="mat-bt-table-note">“—” indica que não há dados suficientes para ajustar o modelo. Nenhuma projeção é a resposta obrigatória; o erro percentual é retrospectivo, não uma garantia de erro futuro. Diferentes linhas não são novas fórmulas do Índice Têmporis: são hipóteses de extrapolação do mesmo K. Consulte o backtesting antes de interpretar as previsões.</p>
  <p style="margin:10px 0 0"><a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte,'horizonte'=>25])?>">Abrir testes retrospectivos e erros por corte →</a></p></div></details>
  <section class="mat-bt-section" aria-label="Próximos passos">
    <div class="mat-bt-cards">
      <div class="mat-bt-card mat-bt-card-lead"><span class="mat-bt-card-label">03 / Validação</span><strong>Backtesting separado</strong><p>Ver erros históricos, amostras válidas e modelos de previsão (linear, exponencial, logístico e outros).</p><div class="mat-bt-card-bottom"><a href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte])?>">Comparar previsões →</a></div></div>
      <div class="mat-bt-card"><span class="mat-bt-card-label">04 / Documentação</span><strong>Caderno de Fórmulas</strong><p>As seis equações essenciais com as diferenças entre Legada, Reformulada e Final.</p><div class="mat-bt-card-bottom"><a href="<?=mat_url('metodo')?>">Consultar metodologia →</a></div></div>
    </div>
  </section>
  <footer class="mat-bt-footnote">
    <p>Pesquisa exploratória. Os resultados brutos dependem da seleção, da datação dos marcos e das notas; o ano-base altera apenas as versões relativas, que ainda podem exigir auditoria independente. Nenhuma nota é alterada por esta página.</p>
    <div class="mat-bt-actions">
      <a class="mat-bt-button" href="<?=mat_url('backtesting',['fonte'=>$tfFontePedida,'coorte'=>$tfCoorte])?>">Teste das previsões →</a>
      <a class="mat-bt-textlink" href="<?=mat_url('metodo')?>">Caderno de fórmulas</a>
    </div>
  </footer>
</div>
