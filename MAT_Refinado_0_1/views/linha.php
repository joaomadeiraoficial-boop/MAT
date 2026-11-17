<?php
/** Linha do tempo do acervo da CURADORIA (mat_temporis_curadoria), no sistema visual .mat-bt.
 *  O acervo legado (mat_observatorio) não é misturado: bases distintas permanecem separadas. */
require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
require_once MAT_ROOT.'/app/AtlasReais.php';
$pdoCur=CuradoriaRodadas::connect();
$linhaErro=null;$candidatos=[];$totalFonte=0;$semAno=0;$aprovados=0;$comValidado=0;
if(!$pdoCur){
    $linhaErro='Banco científico da curadoria indisponível (mat_temporis_curadoria). Inicie o MySQL no XAMPP para consultar o acervo; nenhum dado de outra base é exibido no lugar.';
} else {
    try {
        $totalFonte=(int)$pdoCur->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
        $rows=$pdoCur->query('SELECT id_extracao,titulo_proposto,COALESCE(ano_validado,ano_original) AS ano,ano_validado,ano_original,categoria,estado_revisao_humana,analise_historica_json FROM cur_candidatos WHERE COALESCE(ano_validado,ano_original) IS NOT NULL ORDER BY COALESCE(ano_validado,ano_original) ASC,id_extracao ASC')->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r){
            $ano=(int)$r['ano'];
            $analise=json_decode((string)$r['analise_historica_json'],true);
            $desc=(string)($analise['o_que_aconteceu']??'');
            if(function_exists('mb_substr') && mb_strlen($desc)>220)$desc=mb_substr($desc,0,220).'…';
            elseif(strlen($desc)>220)$desc=substr($desc,0,220).'…';
            $estado=(string)$r['estado_revisao_humana'];
            $aprov=in_array($estado,['APROVADO','HOMOLOGADO'],true);
            if($aprov)$aprovados++;
            if($r['ano_validado']!==null)$comValidado++;
            $era=AtlasReais::keyForYear($ano);
            if($era===null)continue;
            $candidatos[$era][]=['id'=>(string)$r['id_extracao'],'titulo'=>(string)$r['titulo_proposto'],'ano'=>$ano,
                'categoria'=>trim((string)$r['categoria']),'estado'=>$estado,'aprov'=>$aprov,
                'validado'=>$r['ano_validado']!==null,'desc'=>$desc];
        }
        $semAno=$totalFonte;
        foreach($candidatos as $lista)$semAno-=count($lista);
        $semAno=max(0,$semAno);
    } catch(Throwable $e) {
        error_log('MAT linha do tempo / curadoria: '.$e->getMessage());
        $linhaErro='Falha ao consultar os candidatos da curadoria.';
    }
}
$focus=(string)($_GET['era']??'');
if(!isset(AtlasReais::ERAS[$focus]))$focus='';
$noRecorte=0;foreach($candidatos as $era=>$lista){if($focus===''||$era===$focus)$noRecorte+=count($lista);}
$recorteValidados=0;$recorteAprovados=0;
foreach($candidatos as $era=>$lista){if($focus!==''&&$era!==$focus)continue;foreach($lista as $c){if($c['validado'])$recorteValidados++;if($c['aprov'])$recorteAprovados++;}}
?>
<div class="mat-bt" id="mat-linha">

  <header class="mat-bt-hero">
    <div class="mat-bt-hero-top">
      <span class="mat-bt-eyebrow"><span class="mat-bt-dot" aria-hidden="true"></span>Cartografia do tempo · Curadoria do MAT</span>
      <span class="mat-bt-year">Edição de pesquisa · MMXXVI</span>
    </div>
    <div class="mat-bt-hero-body">
      <div>
        <h1>O tempo em <em>sequência.</em></h1>
        <p>A sequência cronológica dos dossiês da curadoria com ano classificável. Cada cartão é um candidato em andamento; a aprovação e a validação de datas permanecem decisão humana.</p>
      </div>
      <div class="mat-bt-hero-mark" aria-hidden="true"><span><?=number_format($totalFonte,0,',','.')?></span><small>candidatos</small></div>
    </div>
    <div class="mat-bt-hero-bottom">
      <span>Banco <code>mat_temporis_curadoria</code> · leitura somente-consulta</span>
      <span><?=number_format($noRecorte,0,',','.')?> com ano classificável</span>
      <span><?=number_format($semAno,0,',','.')?> sem ano · contados no resumo</span>
    </div>
  </header>

<?php if($linhaErro): ?>
  <div class="mat-bt-warning" role="alert" style="margin-top:22px"><strong>Os dados da curadoria não puderam ser carregados.</strong> <?=h($linhaErro)?> <a href="<?=mat_url('metodo')?>">Entenda as bases do projeto ↗</a></div>
</div>
<?php return; endif; ?>

  <form class="mat-bt-console" method="get" action="index.php" style="margin-top:22px">
    <input type="hidden" name="page" value="linha">
    <div class="mat-bt-console-head"><strong>Filtro da linha do tempo</strong><span><?=number_format($noRecorte,0,',','.')?> candidato(s) no recorte atual</span></div>
    <div class="mat-bt-console-fields">
      <label><span>Era histórica</span><select name="era"><option value="">Todas as eras</option><?php foreach(AtlasReais::ERAS as $id=>$e):?><option value="<?=h($id)?>" <?=$id===$focus?'selected':''?>><?=h($e['nome'])?> · <?=h($e['datas'])?></option><?php endforeach;?></select></label>
      <button class="mat-bt-run" type="submit">Filtrar →</button>
<?php if($focus): ?>
      <a class="mat-bt-textlink" href="<?=mat_url('linha')?>">Limpar filtro</a>
<?php endif; ?>
    </div>
  </form>

  <div class="mat-bt-strip mat-bt-strip-4" style="margin-top:15px">
    <div><small>No recorte atual</small><b><?=number_format($noRecorte,0,',','.')?></b></div>
    <div><small>Com ano validado</small><b><?=number_format($recorteValidados,0,',','.')?></b></div>
    <div><small>Com ano apenas extraído</small><b><?=number_format($noRecorte-$recorteValidados,0,',','.')?></b></div>
    <div><small>Aprovados pelo pesquisador</small><b><?=number_format($recorteAprovados,0,',','.')?></b></div>
  </div>

  <div class="mat-bt-verdict" role="note" style="margin-top:15px">
    <strong>COMO LER ESTA SEQUÊNCIA</strong>
    <ul>
      <li><b>Ano validado</b> é a data confirmada pelo pesquisador; <b>ano extraído</b> vem da planilha de origem e permanece provisório — o selo do cartão distingue os dois.</li>
      <li><?=number_format($semAno,0,',','.')?> candidatos ainda sem ano classificável ficam fora da sequência, mas continuam contados no acervo; nenhum registro recebe data fictícia.</li>
      <li>Os recortes por era reutilizam as mesmas faixas do Atlas; são convenções de apresentação, não critérios de pontuação.</li>
    </ul>
  </div>

  <section class="mat-bt-section" aria-label="Sequência cronológica da curadoria">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Sequência cronológica · dossiês da curadoria</span><h2>Da antiguidade ao <em>presente.</em></h2></div><span class="mat-bt-source-pill">Ordem cronológica real</span></div>

    <div class="mat-tl">
<?php foreach(AtlasReais::ERAS as $eraKey=>$eraInfo):
    if($focus!=='' && $eraKey!==$focus)continue;
    $lista=$candidatos[$eraKey]??[]; ?>
      <div class="mat-tl-era"><strong><?=h($eraInfo['nome'])?></strong><span><?=h($eraInfo['datas'])?> · <?=number_format(count($lista),0,',','.')?> candidato(s) com ano</span></div>
<?php foreach($lista as $m): $poso=$m['validado']?'validado':'extraido'; ?>
      <article class="mat-tl-row" id="mat-linha-cand-<?=h($m['id'])?>">
        <div class="mat-tl-date"><strong><?=yfmt($m['ano'])?></strong><span><?=h($m['validado']?'Ano validado':'Ano extraído · provisório')?></span></div>
        <div class="mat-tl-spine"><span class="mat-tl-node<?=$m['validado']?'':' mat-tl-node-prov'?>" aria-hidden="true"></span></div>
        <div class="mat-tl-card">
          <div class="mat-tl-card-top">
            <span class="mat-bt-overline"><?=h($m['id'])?> · <?=h(mb_exists_upper($m['categoria']!==''?$m['categoria']:'Categoria em definição'))?></span>
            <span class="mat-tl-status mat-tl-status-<?=in_array($m['estado'],['APROVADO','HOMOLOGADO'],true)?'validado':'revisao'?>"><?=h(match($m['estado']){'APROVADO'=>'Aprovado','HOMOLOGADO'=>'Homologado','NAO_REVISADO'=>'Não revisado',default=>str_replace('_',' ',strtolower($m['estado']))})?></span>
          </div>
          <h2><?php if(is_admin()): ?><a href="<?=mat_url('rodadas',['id'=>$m['id']])?>"><?=h($m['titulo'])?> <span aria-hidden="true">↗</span></a><?php else: ?><span class="mat-tl-title-plain"><?=h($m['titulo'])?></span><?php endif; ?></h2>
<?php if($m['desc']!==''): ?><p><?=h($m['desc'])?></p><?php endif; ?>
          <div class="mat-tl-foot">
            <span>◈ <?=h($m['validado']?'Data revisada pelo pesquisador':'Data ainda não revisada — não usar como fato confirmado')?></span>
<?php if(is_admin()): ?><a href="<?=mat_url('rodadas',['id'=>$m['id']])?>">Abrir no painel de curadoria →</a>
<?php else: ?><span class="mat-tl-locked">Dossiê completo no painel do pesquisador</span><?php endif; ?>
          </div>
        </div>
      </article>
<?php endforeach; endforeach; ?>
<?php if(!$candidatos || $noRecorte===0): ?>
      <div class="mat-bt-warning" role="status" style="margin-top:18px"><strong>Nenhum candidato com ano classificável neste recorte.</strong> <a href="<?=mat_url('linha')?>">Limpar filtro ↗</a></div>
<?php endif; ?>
    </div>
  </section>

  <footer class="mat-bt-footnote">
    <p>Esta página exibe somente a curadoria atual; o acervo legado permanece identificado nas páginas próprias e nunca é misturado aqui. Novos dossiês e revisões de datação alteram a sequência sem que o passado tenha mudado.</p>
    <div class="mat-bt-actions">
      <a class="mat-bt-button" href="<?=mat_url('atlas')?>">Abrir o Atlas das eras →</a>
      <a class="mat-bt-textlink" href="<?=mat_url('backtesting',['fonte'=>'auto'])?>">Índice Têmporis da curadoria</a>
    </div>
  </footer>
</div>
<?php function mb_exists_upper(string $s):string {return function_exists('mb_strtoupper')?mb_strtoupper($s):strtoupper($s);} ?>
