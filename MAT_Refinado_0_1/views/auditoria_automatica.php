<?php
/**
 * MAT — Painel de Pendências Científicas v3
 * Somente leitura. Nenhuma decisão ou homologação é feita nesta tela.
 * Requer CuradoriaAuditoriaAutomatica v2 e CuradoriaRodadas, instaladas previamente.
 */
if (!defined('MAT_ROOT') || !function_exists('is_admin') || !is_admin()) { http_response_code(403); exit; }
require_once MAT_ROOT.'/app/CuradoriaAuditoriaAutomatica.php';

if (!function_exists('matPendUrl')) {
    function matPendUrl(string $filtro, int $rodada = 0, string $busca = '', int $pagina = 1): string {
        return 'auditoria_automatica.php?' . http_build_query([
            'filtro'=>$filtro, 'rodada'=>$rodada, 'busca'=>$busca, 'pagina'=>$pagina,
        ]);
    }
}
if (!function_exists('matPendIds')) {
    /** IDs presentes em duplicatas_json, sem inferir pares pela similaridade de títulos. */
    function matPendIds(array $reg): array {
        $res=[];
        foreach (($reg['duplicatas']??[]) as $v) {
            $id=is_array($v)?($v['id_extracao']??$v['id']??null):$v;
            if(is_string($id) && preg_match('/^MCD-[0-9]{5}$/D',$id) && $id!==($reg['id_extracao']??'')) $res[$id]=true;
        }
        return array_keys($res);
    }
}
if (!function_exists('matPendText')) {
    function matPendText($item): string {
        if (is_scalar($item) || $item===null) return trim((string)$item);
        if (is_array($item)) {
            foreach (['descricao','motivo','texto','pendencia','observacao'] as $key)
                if (isset($item[$key]) && is_scalar($item[$key])) return (string)$item[$key];
        }
        return (string)json_encode($item, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
}
if (!function_exists('matPendAno')) {
    function matPendAno(array $reg): string {
        if(CuradoriaDatacao::intervalo($reg)!==null)return CuradoriaDatacao::texto($reg);
        $ano = $reg['ano_validado']??null;
        if ($ano!==null && $ano!=='') return (string)$ano;
        $ano = $reg['ano_original']??null;
        return $ano!==null && $ano!=='' ? (string)$ano.' (a conferir)' : 'Data não definida';
    }
}

$pendErro=null;$pendSnapshot=null;
try {
    $pendPdo=CuradoriaRodadas::connect();
    if (!$pendPdo) throw new RuntimeException('Não foi possível conectar ao banco da curadoria.');
    $pendSnapshot=CuradoriaAuditoriaAutomatica::carregar($pendPdo);
} catch (Throwable $e) {
    $pendErro='Não foi possível carregar os dossiês do MySQL. Verifique a conexão com mat_temporis_curadoria.';
    error_log('MAT painel de pendencias v3: '.$e->getMessage());
}

$pendFiltros=['pendentes'=>'Prioridades','todos'=>'Todos','duplicata'=>'Duplicatas','contexto'=>'Escopo','incompleto'=>'Dados faltantes','alerta'=>'História','fontes'=>'Fontes','apto'=>'Aptos à auditoria','amostra'=>'Amostra','finalizado'=>'Decididos'];
$pendFiltro=(string)($_GET['filtro']??'pendentes');
if(!isset($pendFiltros[$pendFiltro]))$pendFiltro='pendentes';
$pendRodada=max(0,min(300,(int)($_GET['rodada']??0)));
$pendBusca=trim((string)($_GET['busca']??''));
$pendBusca=substr($pendBusca,0,100);
$pendPagina=max(1,min(1000,(int)($_GET['pagina']??1)));
$pendItens=$pendSnapshot['itens']??[];
$pendContagens=$pendSnapshot['contagens']??[];
$pendAnos=0;$pendNotas=0;$pendFontes=0;$pendPares=[];$pendComPendencias=0;
$pendRodadas=[];
foreach($pendItens as $id=>$r) {
    $pendRodadas[(int)($r['rodada']??0)]=true;
    if(!CuradoriaDatacao::temData($r))++$pendAnos;
    if((int)($r['notas_validas']??0)<11)++$pendNotas;
    if(count($r['fontes']??[])<2)++$pendFontes;
    if(!empty($r['pendencias']))++$pendComPendencias;
    foreach(matPendIds($r) as $outro){$par=[$id,$outro];sort($par,SORT_STRING);$pendPares[implode('|',$par)]=true;}
}
ksort($pendRodadas,SORT_NUMERIC);
$pendPendentes=count(array_filter($pendItens,static fn($v)=>!in_array(($v['categoria_auditoria']??''),['apto','finalizado'],true)));
$pendLista=$pendSnapshot?CuradoriaAuditoriaAutomatica::filtro($pendSnapshot,$pendFiltro==='pendentes'?'todos':$pendFiltro,$pendRodada,$pendBusca):[];
if($pendFiltro==='pendentes')$pendLista=array_values(array_filter($pendLista,static fn($v)=>!in_array(($v['categoria_auditoria']??''),['apto','finalizado'],true))); 
$pendTotal=count($pendLista);
$pendPorPagina=20;
$pendPaginas=max(1,(int)ceil($pendTotal/$pendPorPagina));
$pendPagina=min($pendPagina,$pendPaginas);
$pendVisiveis=array_slice($pendLista,($pendPagina-1)*$pendPorPagina,$pendPorPagina);
$pendCategorias=[
    'duplicata'=>['titulo'=>'Possível duplicata','acao'=>'Compare os dois registros antes de indicar qual deve ser considerado principal.'],
    'contexto'=>['titulo'=>'Escopo a delimitar','acao'=>'Verifique se o trecho descreve uma inovação independente ou apenas contexto histórico.'],
    'incompleto'=>['titulo'=>'Pesquisa complementar','acao'=>'Complete dados documentais e critérios na ficha do marco.'],
    'alerta'=>['titulo'=>'Ressalva histórica','acao'=>'Confira discrepâncias de data, interpretação ou evidência.'],
    'fontes'=>['titulo'=>'Fontes a conferir','acao'=>'Verifique origem e independência das referências.'],
    'apto'=>['titulo'=>'Apto à auditoria','acao'=>'Sem bloqueios estruturais detectados; ainda exige conferência científica.'],
    'finalizado'=>['titulo'=>'Decisão já registrada','acao'=>'A decisão registrada é mantida. Consulte o dossiê para o histórico.'],
];
?>
<!-- Carregamento local do CSS: independe da versão do header.php instalada. -->
<link rel="stylesheet" href="assets/auditoria-automatica.css?v=3">
<header class="page-heading mat-pend-heading">
 <div class="section-overline">MAT / PESQUISA E CURADORIA / CONTROLE CIENTÍFICO</div>
 <h1>Mapa de <em>pendências.</em></h1>
 <p>Uma visão simples do que ainda precisa de atenção nas rodadas do Têmporis. <strong>Sem aprovar, rejeitar ou alterar notas nesta página:</strong> as decisões continuam no dossiê de cada marco.</p>
</header>
<?php if($pendErro): ?>
<section class="paper-panel mat-pend-error"><h2>Conexão indisponível</h2><p><?=h($pendErro)?></p></section>
<?php else: ?>
<section class="mat-pend-banner" aria-label="Resumo da pesquisa">
 <div>
  <span class="mat-pend-eyebrow">PANORAMA DE CURADORIA · DADOS REAIS DO MYSQL</span>
  <h2>Do catálogo à evidência.</h2>
  <p>Identifique rapidamente os registros que exigem pesquisa, comparação ou correção. Os avisos são <strong>sugestões de triagem</strong>, não conclusões automáticas.</p>
  <div class="mat-pend-banner-actions"><a href="#fila-de-pendencias" class="mat-pend-white-link">Ver registros pendentes ↓</a><button type="button" class="mat-pend-print" id="matPendPrint">Imprimir resumo para apresentação</button></div>
 </div>
 <div class="mat-pend-total"><span>DOSSIÊS NO MYSQL</span><strong><?=short_num($pendSnapshot['total'])?></strong><small>Rastreáveis por ID e rodada</small></div>
</section>

<section class="mat-pend-metrics" aria-label="Indicadores da auditoria">
 <a href="<?=h(matPendUrl('duplicata'))?>" class="mat-pend-metric mat-pend-amber" data-mat-pend-nav><span>Duplicatas sinalizadas</span><strong><?=short_num($pendContagens['duplicata']??0)?></strong><small><?=short_num(count($pendPares))?> pares explicitamente referenciados</small></a>
 <a href="<?=h(matPendUrl('contexto'))?>" class="mat-pend-metric" data-mat-pend-nav><span>Escopo duvidoso</span><strong><?=short_num($pendContagens['contexto']??0)?></strong><small>Contexto ou acontecimento a delimitar</small></a>
 <a href="<?=h(matPendUrl('incompleto'))?>" class="mat-pend-metric" data-mat-pend-nav><span>Pesquisa complementar</span><strong><?=short_num($pendContagens['incompleto']??0)?></strong><small>Classificação principal do diagnóstico</small></a>
 <div class="mat-pend-metric"><span>Sem ano revisado</span><strong><?=short_num($pendAnos)?></strong><small>Também podem aparecer em outras filas</small></div>
 <div class="mat-pend-metric"><span>Menos de 11 notas</span><strong><?=short_num($pendNotas)?></strong><small>Não é reprovação do marco</small></div>
 <a href="<?=h(matPendUrl('apto'))?>" class="mat-pend-metric mat-pend-green" data-mat-pend-nav><span>Aptos estruturalmente</span><strong><?=short_num($pendContagens['apto']??0)?></strong><small>Ainda precisam de conferência documental</small></a>
</section>
<p class="mat-pend-disclaimer">Os indicadores podem se sobrepor. Por exemplo, um candidato pode ser duplicata e também estar sem notas. A ordem de prioridade sugere por onde começar; não é uma avaliação histórica definitiva.</p>

<div class="mat-pend-section-heading"><div><div class="panel-head"><span>ORDEM RECOMENDADA</span></div><h2>Por onde começar?</h2></div><span class="mat-pend-aside">TRÊS ETAPAS, SEM REPETIR APROVAÇÃO</span></div>
<section class="mat-pend-workflow" aria-label="Prioridades da pesquisa">
 <a class="mat-pend-step" href="<?=h(matPendUrl('duplicata'))?>" data-mat-pend-nav><span class="mat-pend-step-number">01</span><div><strong>Conferir repetições</strong><p>Compare títulos, datas e referências. Um mesmo acontecimento não deve ser contado duas vezes no índice.</p><span>Ver duplicatas →</span></div></a>
 <a class="mat-pend-step" href="<?=h(matPendUrl('contexto'))?>" data-mat-pend-nav><span class="mat-pend-step-number">02</span><div><strong>Delimitar os marcos</strong><p>Separe invenções e descobertas de estatísticas, tendências, instituições e passagens de contexto.</p><span>Ver escopo →</span></div></a>
 <a class="mat-pend-step" href="<?=h(matPendUrl('incompleto'))?>" data-mat-pend-nav><span class="mat-pend-step-number">03</span><div><strong>Completar evidências</strong><p>Corrija datas, verifique fontes e identifique critérios realmente sem sustentação documental.</p><span>Ver lacunas →</span></div></a>
</section>

<p class="mat-progress-explain"><a href="export_curadoria_pendencias.php">Exportar diagnóstico dos candidatos em CSV para conferência externa ↗</a> · Somente leitura; não aprova marcos.</p>
<section class="paper-panel mat-pend-queue" id="fila-de-pendencias">
 <div class="panel-head"><span>DIAGNÓSTICO DOS DOSSIÊS</span><span><?=short_num($pendTotal)?> RESULTADOS</span></div>
 <div class="mat-pend-queue-title"><div><h2>O que falta resolver?</h2><p>Clique em “Ver detalhes” para encontrar as pendências específicas. Os candidatos são organizados por ID e rodada e podem ser consultados sem alterar o banco.</p></div><a class="mat-pend-comparison-link" href="<?=h(mat_url('duplicatas'))?>#pares-curadoria">Abrir comparação completa de pares ↗</a></div>
 <nav class="mat-pend-chips" aria-label="Tipos de pendência">
  <?php foreach($pendFiltros as $k=>$nome):$count=($k==='pendentes'?$pendPendentes:($k==='todos'?$pendSnapshot['total']:($k==='amostra'?count($pendSnapshot['amostra']):($pendContagens[$k]??null)))); ?>
   <a href="<?=h(matPendUrl($k,$pendRodada,$pendBusca))?>" class="<?=$pendFiltro===$k?'is-current':''?>" <?=$pendFiltro===$k?'aria-current="page"':''?> data-mat-pend-nav><?=h($nome)?><?php if($count!==null):?><span><?=short_num($count)?></span><?php endif;?></a>
  <?php endforeach; ?>
 </nav>
 <form class="mat-pend-filters" method="get" action="auditoria_automatica.php" id="matPendFilters">
  <input type="hidden" name="filtro" value="<?=h($pendFiltro)?>">
  <label>Buscar por ID ou acontecimento<input type="search" name="busca" maxlength="100" value="<?=h($pendBusca)?>" placeholder="Ex.: MCD-00194, Newcomen, DNA"></label>
  <label>Rodada<select name="rodada"><option value="0">Todas as rodadas</option><?php foreach(array_keys($pendRodadas) as $rd):?><option value="<?=$rd?>" <?=$rd===$pendRodada?'selected':''?>>Rodada <?=$rd?></option><?php endforeach;?></select></label>
  <button type="submit">Aplicar filtros</button>
 </form>
 <div class="mat-pend-results" role="list">
 <?php foreach($pendVisiveis as $reg):
   $id=(string)$reg['id_extracao'];$tipo=(string)$reg['categoria_auditoria'];$titulo=(string)$reg['titulo_proposto'];$info=$pendCategorias[$tipo]??$pendCategorias['alerta'];
   $faltantes=[];
   foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$nome){$c=$reg['criterios'][$codigo]??null;if(!$c || ($c['nota']??null)===null)$faltantes[]=$nome;}
   $links=matPendIds($reg);
   $fontes=$reg['fontes']??[];
   $motivos=array_merge($reg['pendencias']??[],$reg['avisos']??[],$reg['impedimentos']??[]);
   $motivos=array_values(array_unique(array_filter(array_map('matPendText',$motivos))));
   $isDupe=$tipo==='duplicata';
 ?>
 <article class="mat-pend-item mat-pend-item-<?=h($tipo)?>" role="listitem">
  <div class="mat-pend-item-summary"><div class="mat-pend-item-content"><div class="mat-pend-item-meta"><span class="mat-pend-id"><?=h($id)?></span><span>Rodada <?=h((string)$reg['rodada'])?></span><span><?=h(matPendAno($reg))?></span></div><h3><?=h($titulo)?></h3><p><?=h($info['acao'])?></p></div><div class="mat-pend-item-right"><span class="mat-pend-label mat-pend-label-<?=h($tipo)?>"><?=h($info['titulo'])?></span><span class="mat-pend-mini"><?=count($faltantes)?> notas sem evidência · <?=count($fontes)?> fontes</span></div></div>
  <details class="mat-pend-details"><summary>Ver o que falta e próximos passos <span aria-hidden="true">⌄</span></summary>
   <div class="mat-pend-details-body">
    <div class="mat-pend-detail-column"><h4>Pontos a conferir</h4><ul>
      <?php if(!CuradoriaDatacao::temData($reg)):?><li><strong>Data:</strong> ano não validado. Original: <?=h(matPendAno(['ano_original'=>$reg['ano_original']??null]))?>.</li><?php endif;?>
      <?php if($faltantes):?><li><strong>Notas:</strong> <?=count($faltantes)?> de 11 critérios sem nota proposta. <details class="mat-pend-mini-details"><summary>Ver critérios</summary><ul><?php foreach($faltantes as $f):?><li><?=h((string)$f)?></li><?php endforeach;?></ul></details></li><?php endif;?>
      <?php if(count($fontes)<2):?><li><strong>Fontes:</strong> <?=count($fontes)?> cadastrada(s). Conferir se há evidências independentes suficientes.</li><?php endif;?>
      <?php if($links):?><li><strong>Duplicidade:</strong> <?=h(implode(', ',$links))?>.</li><?php endif;?>
      <?php if(!$motivos && !$faltantes && CuradoriaDatacao::temData($reg) && count($fontes)>=2):?><li>Nenhuma lacuna estrutural detectada. Conferir a pertinência e autenticidade das fontes antes de concluir.</li><?php endif;?>
      <?php foreach(array_slice($motivos,0,7) as $mot):?><li><?=h($mot)?></li><?php endforeach;?>
      <?php if(count($motivos)>7):?><li>Há mais <?=count($motivos)-7?> observação(ões) na ficha completa.</li><?php endif;?>
    </ul></div>
    <div class="mat-pend-detail-column"><h4>Como resolver</h4><p>Abra o dossiê para corrigir notas, datas, interpretação histórica e referências. A decisão final permanece na ficha do marco.</p>
      <a class="mat-pend-action-link" href="<?=h(mat_url('rodadas',['id'=>$id]))?>">Abrir ficha <?=h($id)?> ↗</a>
      <?php if($isDupe || $links):?><a class="mat-pend-secondary-link" href="<?=h(mat_url('duplicatas'))?>#pares-curadoria">Comparar duplicatas no MAT ↗</a><?php endif;?>
    </div>
   </div>
   <?php if($links):?><div class="mat-pend-pair-zone"><div class="mat-pend-pair-heading"><strong>Possível duplicata — comparação preliminar</strong><span>Sem exclusão automática</span></div>
     <?php foreach(array_slice($links,0,4) as $other):$ref=$pendItens[$other]??null; ?>
     <div class="mat-pend-compare"><div class="mat-pend-compare-half"><span>REGISTRO A</span><strong><?=h($id)?></strong><p><?=h($titulo)?></p><small><?=h(matPendAno($reg))?> · <?=h((string)($reg['categoria']??'Sem categoria'))?></small><small><?=count($fontes)?> fonte(s) registrada(s)</small><a href="<?=h(mat_url('rodadas',['id'=>$id]))?>">Abrir A ↗</a></div>
       <div class="mat-pend-compare-separator">×</div>
       <div class="mat-pend-compare-half"><span>REGISTRO B</span><strong><?=h($other)?></strong><p><?=h((string)($ref['titulo_proposto']??'Registro ainda não importado'))?></p><small><?=$ref?h(matPendAno($ref)).' · '.h((string)($ref['categoria']??'Sem categoria')):'Referência não encontrada no banco'?></small><?php if($ref):?><small><?=count($ref['fontes']??[])?> fonte(s) registrada(s)</small><?php endif;?><?php if($ref):?><a href="<?=h(mat_url('rodadas',['id'=>$other]))?>">Abrir B ↗</a><?php endif;?></div>
     </div><?php endforeach;?>
     <p class="mat-pend-caution">Semelhança ou vínculo cadastrado não provam identidade histórica. A comparação completa e a decisão entre A/B ficam na ferramenta de duplicatas já existente.</p>
   </div><?php elseif($isDupe):?><p class="mat-pend-caution">Possível duplicata sinalizada, porém sem ID do par no cadastro. Consulte a ficha para identificar o registro relacionado antes de comparar.</p><?php endif;?>
  </details>
 </article>
 <?php endforeach;?>
 <?php if(!$pendVisiveis):?><div class="mat-pend-empty"><strong>Nenhum resultado para estes filtros.</strong><p>Tente outra rodada, palavra-chave ou categoria.</p></div><?php endif;?>
 </div>
 <div class="mat-pend-pagination"><span>Mostrando <?=count($pendVisiveis)?> de <?=short_num($pendTotal)?> · página <?=$pendPagina?> de <?=$pendPaginas?></span><div><?php if($pendPagina>1):?><a data-mat-pend-nav href="<?=h(matPendUrl($pendFiltro,$pendRodada,$pendBusca,$pendPagina-1))?>">← Anterior</a><?php endif;?><?php if($pendPagina<$pendPaginas):?><a data-mat-pend-nav href="<?=h(matPendUrl($pendFiltro,$pendRodada,$pendBusca,$pendPagina+1))?>">Próxima →</a><?php endif;?></div></div>
</section>
<footer class="mat-pend-footer"><strong>Critério científico:</strong> esta é uma análise estrutural de dados cadastrados. O sistema não verifica automaticamente se as afirmações históricas são verdadeiras, nem determina que um registro deve ser homologado. A aprovação e a homologação permanecem no dossiê de cada marco.</footer>
<script>
(function(){
  var print=document.getElementById('matPendPrint');if(print)print.addEventListener('click',function(){window.print()});
  var key='mat-pend-scroll-v3';
  function save(){try{sessionStorage.setItem(key,JSON.stringify({y:window.scrollY,t:Date.now()}));}catch(e){}}
  document.querySelectorAll('[data-mat-pend-nav]').forEach(function(a){a.addEventListener('click',save)});
  var form=document.getElementById('matPendFilters');if(form)form.addEventListener('submit',save);
  try{var raw=sessionStorage.getItem(key);if(raw){sessionStorage.removeItem(key);var s=JSON.parse(raw);if(Date.now()-s.t<12000)requestAnimationFrame(function(){window.scrollTo(0,Math.min(s.y,document.documentElement.scrollHeight-window.innerHeight))});}}catch(e){}
})();
</script>
<?php endif; ?>
