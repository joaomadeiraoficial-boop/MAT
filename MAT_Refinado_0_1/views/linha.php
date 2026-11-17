<?php
/** Linha do tempo do acervo da CURADORIA (mat_temporis_curadoria), no sistema visual .mat-bt.
 *  Unica página do grupo Exploração histórica além do Atlas: cronologia completa, busca e filtros.
 *  O acervo legado (mat_observatorio) não é misturado: bases distintas permanecem separadas. */
require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
require_once MAT_ROOT.'/app/AtlasReais.php';
$pdoCur=CuradoriaRodadas::connect();
$focus=(string)($_GET['era']??'');
if(!isset(AtlasReais::ERAS[$focus]))$focus='';
$busca=trim((string)($_GET['q']??''));
$estadoF=(string)($_GET['estado']??'');
if(!in_array($estadoF,['APROVADO','HOMOLOGADO','NAO_REVISADO'],true))$estadoF='';
$provF=(string)($_GET['prov']??'');
if(!in_array($provF,['validado','extraido'],true))$provF='';
$linhaErro=null;$candidatos=[];$semAnoLista=[];$totalFonte=0;$semAnoFonte=0;
if(!$pdoCur){
    $linhaErro='Banco científico da curadoria indisponível (mat_temporis_curadoria). Inicie o MySQL no XAMPP para consultar o acervo; nenhum dado de outra base é exibido no lugar.';
} else {
    try {
        $totalFonte=(int)$pdoCur->query('SELECT COUNT(*) FROM cur_candidatos')->fetchColumn();
        $rows=$pdoCur->query('SELECT id_extracao,titulo_proposto,COALESCE(ano_validado,ano_original) AS ano,ano_validado,ano_original,categoria,estado_revisao_humana,analise_historica_json FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        $low=function_exists('mb_strtolower')?static fn(string $s): string=>mb_strtolower($s,'UTF-8'):static fn(string $s): string=>strtolower($s);
        $find=function_exists('mb_stripos')?static fn(string $hay, string $needle): bool=>mb_stripos($hay,$needle)!==false:static fn(string $hay, string $needle): bool=>stripos($hay,$needle)!==false;
        $q=$low($busca);
        foreach($rows as $r){
            $cand=['id'=>(string)$r['id_extracao'],'titulo'=>(string)$r['titulo_proposto'],'ano'=>null,
                'categoria'=>trim((string)$r['categoria']),'estado'=>(string)$r['estado_revisao_humana'],
                'validado'=>$r['ano_validado']!==null,'era'=>null,'desc'=>''];
            if($q!=='' && !$find($cand['titulo'],$q) && !$find($cand['categoria'],$q))continue;
            if($estadoF!=='' && $cand['estado']!==$estadoF)continue;
            if($provF==='validado' && !$cand['validado'])continue;
            if($provF==='extraido' && $cand['validado'])continue;
            $cand['ano']=isset($r['ano'])&&$r['ano']!==null?(int)$r['ano']:null;
            if($cand['ano']!==null){
                $analise=json_decode((string)$r['analise_historica_json'],true);
                $desc=(string)($analise['o_que_aconteceu']??'');
                if(function_exists('mb_substr') && mb_strlen($desc)>220)$desc=mb_substr($desc,0,220).'…';
                elseif(strlen($desc)>220)$desc=substr($desc,0,220).'…';
                $cand['desc']=$desc;
                $cand['era']=AtlasReais::keyForYear($cand['ano']);
            }
            if($cand['era']===null){
                $semAnoLista[]=$cand;
            } else {
                $candidatos[$cand['era']][]=$cand;
            }
        }
        foreach($rows as $r){ if($r['ano']===null)$semAnoFonte++; }
    } catch(Throwable $e) {
        error_log('MAT linha do tempo / curadoria: '.$e->getMessage());
        $linhaErro='Falha ao consultar os candidatos da curadoria.';
    }
}
$noRecorte=0;foreach($candidatos as $era=>$lista){if($focus===''||$era===$focus)$noRecorte+=count($lista);}
$recorteValidados=0;$recorteAprovados=0;
foreach($candidatos as $era=>$lista){if($focus!==''&&$era!==$focus)continue;foreach($lista as $c){if($c['validado'])$recorteValidados++;if(in_array($c['estado'],['APROVADO','HOMOLOGADO'],true))$recorteAprovados++;}}
$estadoNome=['APROVADO'=>'Aprovado','HOMOLOGADO'=>'Homologado','NAO_REVISADO'=>'Não revisado'];
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
        <p>A sequência cronológica dos dossiês da curadoria, com busca e filtros. Cada cartão é um candidato em andamento; a aprovação e a validação de datas permanecem decisão humana.</p>
      </div>
      <div class="mat-bt-hero-mark" aria-hidden="true"><span><?=number_format($totalFonte,0,',','.')?></span><small>candidatos</small></div>
    </div>
    <div class="mat-bt-hero-bottom">
      <span>Banco <code>mat_temporis_curadoria</code> · leitura somente-consulta</span>
      <span><?=number_format($noRecorte,0,',','.')?> com ano classificável</span>
      <span><?=number_format($semAnoFonte,0,',','.')?> sem ano · listados ao final</span>
    </div>
  </header>

<?php if($linhaErro): ?>
  <div class="mat-bt-warning" role="alert" style="margin-top:22px"><strong>Os dados da curadoria não puderam ser carregados.</strong> <?=h($linhaErro)?> <a href="<?=mat_url('metodo')?>">Entenda as bases do projeto ↗</a></div>
</div>
<?php return; endif; ?>

  <form class="mat-bt-console" method="get" action="index.php" style="margin-top:22px">
    <input type="hidden" name="page" value="linha">
    <div class="mat-bt-console-head"><strong>Busca e filtros da linha do tempo</strong><span><?=number_format($noRecorte,0,',','.')?> candidato(s) com ano no recorte atual</span></div>
    <div class="mat-bt-console-fields">
      <label><span>Pesquisar</span><input type="search" name="q" value="<?=h($busca)?>" placeholder="Título ou categoria..."></label>
      <label><span>Era histórica</span><select name="era"><option value="">Todas as eras</option><?php foreach(AtlasReais::ERAS as $id=>$e):?><option value="<?=h($id)?>" <?=$id===$focus?'selected':''?>><?=h($e['nome'])?> · <?=h($e['datas'])?></option><?php endforeach;?></select></label>
      <label><span>Estado da revisão</span><select name="estado"><option value="">Todos os estados</option><?php foreach(['APROVADO','HOMOLOGADO','NAO_REVISADO'] as $e):?><option value="<?=$e?>" <?=$e===$estadoF?'selected':''?>><?=h($estadoNome[$e])?></option><?php endforeach;?></select></label>
      <label><span>Proveniência do ano</span><select name="prov"><option value="">Anos validados e extraídos</option><option value="validado" <?=$provF==='validado'?'selected':''?>>Somente anos validados</option><option value="extraido" <?=$provF==='extraido'?'selected':''?>>Somente anos extraídos</option></select></label>
      <button class="mat-bt-run" type="submit">Filtrar →</button>
<?php if($focus!==''||$busca!==''||$estadoF!==''||$provF!==''): ?>
      <a class="mat-bt-textlink" href="<?=mat_url('linha')?>">Limpar filtros</a>
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
      <li>A aprovação e a homologação são decisão humana, registradas no painel de curadoria e sujeitas a auditoria; não equivalem a validação histórica externa.</li>
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
<?php foreach($lista as $m): ?>
      <article class="mat-tl-row" id="mat-linha-cand-<?=h($m['id'])?>">
        <div class="mat-tl-date"><strong><?=yfmt($m['ano'])?></strong><span><?=h($m['validado']?'Ano validado':'Ano extraído · provisório')?></span></div>
        <div class="mat-tl-spine"><span class="mat-tl-node<?=$m['validado']?'':' mat-tl-node-prov'?>" aria-hidden="true"></span></div>
        <div class="mat-tl-card">
          <div class="mat-tl-card-top">
            <span class="mat-bt-overline"><?=h($m['id'])?> · <?=h(mb_exists_upper($m['categoria']!==''?$m['categoria']:'Categoria em definição'))?></span>
            <span class="mat-tl-status mat-tl-status-<?=in_array($m['estado'],['APROVADO','HOMOLOGADO'],true)?'validado':'revisao'?>"><?=h($estadoNome[$m['estado']]??str_replace('_',' ',strtolower($m['estado'])))?></span>
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
<?php if($noRecorte===0): ?>
      <div class="mat-bt-warning" role="status" style="margin-top:18px"><strong>Nenhum candidato com ano classificável neste recorte.</strong> <a href="<?=mat_url('linha')?>">Limpar filtros ↗</a></div>
<?php endif; ?>
    </div>
  </section>

  <section id="sem-ano" class="mat-bt-section" aria-labelledby="sem-ano-titulo">
    <div class="mat-bt-section-heading"><div><span class="mat-bt-overline">Aguardando classificação de ano</span><h2 id="sem-ano-titulo">Sem ano, <em>mas no acervo.</em></h2></div><span class="mat-bt-source-pill"><?=number_format(count($semAnoLista),0,',','.')?> no recorte · <?=number_format($semAnoFonte,0,',','.')?> na base</span></div>
    <div class="mat-bt-verdict" role="note" style="margin-top:0;margin-bottom:16px">
      <strong>NENHUM REGISTRO RECEBE DATA FICTÍCIA</strong>
      <ul><li>Estes dossiês ainda não têm ano classificável; ficam fora da sequência cronológica, mas permanecem catalogados aqui até a datação humana ser concluída.</li></ul>
    </div>
<?php if(!$semAnoLista): ?>
    <div class="mat-bt-warning" role="status"><strong>Nenhum candidato sem ano neste recorte.</strong> <a href="<?=mat_url('linha')?>">Limpar filtros ↗</a></div>
<?php else: ?>
    <div class="mat-tl-sem">
<?php foreach($semAnoLista as $m): ?>
      <div class="mat-tl-sem-row">
        <span class="mat-bt-overline"><?=h($m['id'])?> · <?=h($m['categoria']!==''?$m['categoria']:'Categoria em definição')?></span>
        <h3><?php if(is_admin()): ?><a href="<?=mat_url('rodadas',['id'=>$m['id']])?>"><?=h($m['titulo'])?></a><?php else: ?><?=h($m['titulo'])?><?php endif; ?></h3>
        <span class="mat-tl-status mat-tl-status-<?=in_array($m['estado'],['APROVADO','HOMOLOGADO'],true)?'validado':'revisao'?>"><?=h($estadoNome[$m['estado']]??str_replace('_',' ',strtolower($m['estado'])))?></span>
      </div>
<?php endforeach; ?>
    </div>
<?php endif; ?>
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
