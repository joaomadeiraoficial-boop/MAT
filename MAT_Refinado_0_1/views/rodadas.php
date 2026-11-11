<?php
if (!defined('MAT_ROOT') || !is_admin()) { http_response_code(403); exit; }
require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
require_once MAT_ROOT.'/app/CuradoriaRevisao.php';
$pdo=CuradoriaRodadas::connect();
$dados=null;$lista=null;$registro=null;$erro=false;$resumosRodadas=[];
$id=strtoupper(trim((string)($_GET['id']??'')));
$filtro=(string)($_GET['filtro']??'todos');
if(!in_array($filtro,['todos','pesquisados','planejados','nao_iniciados','duplicatas','sem_evidencia','em_revisao','aprovados','homologados','rejeitados'],true))$filtro='todos';
$busca=substr(trim((string)($_GET['busca']??'')),0,100);
$pagina=max(1,min(99999,(int)($_GET['pagina']??1)));
if($pdo){try{
    $dados=CuradoriaRodadas::overview($pdo);
    foreach(($dados['rodadas']??[]) as $etapa){
        $numero=(int)($etapa['numero']??0);
        if($numero<1)continue;
        $planejada=(($etapa['fase']??'')==='PLANEJADA');
        $resumosRodadas[]=['numero'=>$numero,'estado'=>(string)($etapa['fase']??''),
           'descricao'=>(string)($etapa['descricao']??''),
           'itens'=>CuradoriaRodadas::roundItems($pdo,$numero,$planejada)];
    }
    if($id!==''){
        $registro=CuradoriaRodadas::record($pdo,$id);
        if($registro===null)http_response_code(404);
    } else $lista=CuradoriaRodadas::listing($pdo,$filtro,$busca,$pagina);
}catch(Throwable $e){error_log('MAT: curadoria unificada indisponível: '.$e->getMessage());$erro=true;$dados=null;}}
?>
<header class="page-heading mat-cur-heading">
 <div class="section-overline">MAT / FONTE ÚNICA · <?=h(CuradoriaRodadas::databaseName())?></div>
 <h1>Curadoria dos <em>candidatos.</em></h1>
 <p>Esta seção consulta <strong>somente</strong> o banco científico da curadoria. Os marcos do acervo principal permanecem separados; nenhuma proposta é homologada automaticamente.</p>
</header>
<?php if(!$dados): ?>
<section class="paper-panel mat-cur-empty" role="status"><div class="panel-head"><span>VERIFICAÇÃO DE ORIGEM</span><span>NENHUM VALOR SIMULADO</span></div>
<h2>Banco de pesquisa indisponível.</h2><p>O MAT usa o banco <strong><?=h(CuradoriaRodadas::databaseName())?></strong>. Verifique se o banco de curadoria já contém as tabelas <code>cur_candidatos</code>, <code>cur_criterios</code>, <code>cur_fontes</code>, <code>cur_rodadas</code> e <code>cur_log</code> e se o usuário MySQL do MAT possui permissão de leitura. Não serão exibidos valores simulados.</p></section>
<?php else: ?>
<div class="mat-quick-link-strip"><a href="<?=mat_url('fila_rapida')?>">✓ Abrir curadoria integral dos <?=short_num($dados['total'])?> candidatos (0–11 notas) →</a><span>Somente conferência humana, sem aprovação automática</span></div>
<div class="mat-cur-statusbar"><span class="mat-cur-statusdot"></span> FONTE ATIVA: <?=h(CuradoriaRodadas::databaseName())?> <span class="mat-cur-statusright">MYSQL · DADOS REAIS · PROPOSTAS NÃO HOMOLOGADAS</span></div>
<?php if(!$dados['integra']): ?>
<div class="lab-alert" role="status"><span class="lab-symbol">!</span><div><strong>Identificadores duplicados detectados.</strong><p>O banco contém <?=short_num($dados['total'])?> registros e <?=short_num($dados['ids_distintos'])?> IDs distintos. Verifique a integridade antes de continuar.</p></div></div>
<?php endif; ?>
<div class="mat-review-flow" aria-label="Etapas da revisão científica">
 <div><small>01</small><strong>Pesquisar</strong><span>Fontes e acontecimento</span></div>
 <div><small>02</small><strong>Pontuar</strong><span>11 critérios justificados</span></div>
 <div><small>03</small><strong>Revisar</strong><span>Data, fontes e ressalvas</span></div>
 <div><small>04</small><strong>Aprovar</strong><span>Decisão humana registrada</span></div>
 <div><small>05</small><strong>Homologar</strong><span>Incluir na Têmporis 0.2</span></div>
</div>
<div class="mat-cur-metrics">
 <div class="mat-cur-metric"><small>CANDIDATOS CADASTRADOS</small><strong><?=short_num($dados['total'])?></strong><span>Dossiês realmente presentes no MySQL</span></div>
 <div class="mat-cur-metric"><small>PESQUISADOS</small><strong><?=short_num($dados['pesquisados'])?></strong><span>Investigados, ainda sob auditoria</span></div>
 <div class="mat-cur-metric"><small>PRÓXIMA RODADA</small><strong><?=short_num($dados['planejados'])?></strong><span>Somente os próximos dossiês já inseridos</span></div>
 <div class="mat-cur-metric"><small>HOMOLOGADOS</small><strong><?=short_num($dados['homologados'])?></strong><span>Validados explicitamente, não por IA</span></div>
</div>
<?php if($id!==''): ?>
<div class="mat-cur-return"><a href="<?=mat_url('rodadas')?>">← Todos os candidatos</a> · <a href="<?=mat_url('fila_rapida',['id'=>$id,'filtro'=>'todos'])?>">Abrir na revisão rápida ↗</a></div>
<?php if(!$registro): ?>
<section class="paper-panel mat-cur-empty"><h2>ID não encontrado na origem.</h2><p>Este identificador ainda não está cadastrado no banco de curadoria.</p></section>
<?php else: $r=$registro;$c=$r['criterios'];$o=$r['original'];$m=$r['memoria'];$revisao=(string)($r['estado_revisao_humana']??'NAO_REVISADO');$impedimentos=CuradoriaRevisao::impedimentos($r);$podeAprovar=!$impedimentos;$podeHomologar=!CuradoriaRevisao::impedimentos($r,CuradoriaPolitica::RIGOROSO); ?>
<section class="paper-panel mat-cur-record">
 <div class="panel-head"><span>DOSSIÊ DE PESQUISA · <?=h($r['id_extracao'])?></span><span><?=!empty($r['rodada'])?'RODADA '.(int)$r['rodada']:'AINDA NÃO PESQUISADO'?></span></div>
 <div class="mat-cur-record-head"><div><h2><?=h($r['titulo_proposto'])?></h2><p><?=h(CuradoriaDatacao::intervalo($r) ? CuradoriaDatacao::texto($r) : ($r['ano_validado']!==null ? yfmt((int)$r['ano_validado']) : ($r['periodo_historico'] ?: 'Data em investigação'))) ?></p></div><span class="mat-cur-chip"><?=h(CuradoriaRevisao::ESTADOS[$revisao]??CuradoriaRodadas::label((string)($r['estado_fluxo']??'SEM_CONTROLE')))?></span></div>
 <?php if((int)($m['notas_provisorias']??0)>0): ?><div class="mat-cur-pendencias"><b><?= (int)$m['notas_provisorias'] ?> nota(s) 1 provisória(s)</b>: atribuições exploratórias por evidência insuficiente, não homologadas.</div><?php endif; ?>
<div class="mat-cur-facts"><span>Categoria original: <b><?=h($r['categoria_original']?:'Não informada')?></b></span><span>Página do livro: <b><?=h((string)($r['pagina_livro']??$o['pagina_livro']??'Não informada'))?></b></span><span>Critérios pontuados: <b><?=$m['avaliadas']?>/11</b></span><span>Homologado: <b><?=empty($r['homologado'])?'NÃO':'SIM'?></b></span></div>
 <?php if($r['pendencias']): ?><div class="mat-cur-pendencias"><h3>Pendências documentais</h3><ul><?php foreach($r['pendencias'] as $p):if(is_scalar($p)):?><li><?=h((string)$p)?></li><?php endif;endforeach;?></ul></div><?php endif; ?>
</section>
<section class="paper-panel mat-review-board" id="revisao-humana" aria-label="Controle de revisão humana">
 <div class="panel-head"><span>DECISÃO HUMANA / <?=h($r['id_extracao'])?></span><span>REGISTRO AUDITÁVEL NO MYSQL</span></div>
 <div class="mat-review-top"><div><h2>Revisão do dossiê</h2><p>A IA apenas propõe notas. A revisão humana registra justificativa e decisão, sem apagar o registro histórico.</p></div><span class="mat-review-tag status-<?=h(strtolower($revisao))?>"><?=h(CuradoriaRevisao::ESTADOS[$revisao]??$revisao)?></span></div>
 <div class="mat-review-checks"><div><b>Notas completas</b><span><?=$m['avaliadas']?> / 11</span></div><div><b>Fontes cadastradas</b><span><?=count($r['fontes'])?></span></div><div><b>Impedimentos</b><span><?=count($impedimentos)?></span></div><div><b>Homologado</b><span><?=!empty($r['homologado'])?'SIM':'NÃO'?></span></div></div>
 <?php if($impedimentos):?><details class="mat-review-blockers" open><summary><?=count($impedimentos)?> pendências impedem a aprovação <span aria-hidden="true">⌄</span></summary><ul><?php foreach($impedimentos as $p):?><li><?=h($p)?></li><?php endforeach;?></ul></details><?php else: ?><div class="mat-review-ready">Os requisitos estruturais estão completos. Confira pessoalmente a qualidade e independência das fontes antes de aprovar.</div><?php endif; ?>
 <?php if($revisao==='HOMOLOGADO'): ?>
  <form method="post" action="actions.php" class="mat-review-form"><?=csrf_field()?><input type="hidden" name="action" value="curadoria_decisao"><input type="hidden" name="id_extracao" value="<?=h($r['id_extracao'])?>"><input type="hidden" name="decisao" value="reabrir"><label>Justificativa para reabrir homologação<textarea name="motivo" minlength="15" maxlength="2500" required placeholder="Explique a correção ou a nova evidência..." rows="2"></textarea></label><button class="mat-review-btn mat-review-neutral" type="submit">↶ Reabrir para revisão</button></form>
 <?php else: ?>
 <form method="post" action="actions.php" class="mat-review-form"><?=csrf_field()?><input type="hidden" name="action" value="curadoria_decisao"><input type="hidden" name="id_extracao" value="<?=h($r['id_extracao'])?>">
  <label>Seu parecer documentado<textarea name="motivo" minlength="15" maxlength="2500" rows="3" required placeholder="Ex.: conferi a data e as fontes; a nota de difusão ainda depende de uma referência independente..."></textarea></label>
  <label class="mat-review-confirm"><input type="checkbox" name="confirmacao_humana" value="1"> <?=CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO?'Conferi a ocorrência e a fonte e reconheço as ressalvas da avaliação preliminar. A homologação continua rigorosa.':'Conferi pessoalmente todas as 11 rubricas, datas, citações e fontes para aprovar ou homologar.'?></label>
  <div class="mat-review-actions">
   <button type="submit" name="decisao" value="em_revisao" class="mat-review-btn mat-review-amber">◷ Em revisão</button>
   <button type="submit" name="decisao" value="rejeitar" class="mat-review-btn mat-review-red">✕ Rejeitar candidato</button>
   <button type="submit" name="decisao" value="aprovar" class="mat-review-btn mat-review-green" <?=$podeAprovar?'':'disabled title="Corrija os impedimentos antes da aprovação"'?>>✓ Aprovar dossiê</button>
   <?php if($revisao==='APROVADO'): ?><button type="submit" name="decisao" value="homologar" class="mat-review-btn mat-review-deep" <?=$podeHomologar?'':'disabled'?>>✧ Homologar para o índice</button><?php endif; ?>
  </div>
 </form>
 <?php endif; ?>
 <details class="mat-review-edit"><summary>Editar data e registrar resolução de pendências <span>⌄</span></summary>
 <form method="post" action="actions.php" class="mat-review-form"><?=csrf_field()?><input type="hidden" name="action" value="curadoria_metadados"><input type="hidden" name="id_extracao" value="<?=h($r['id_extracao'])?>"><input type="hidden" name="hash_itens" value="<?=h(CuradoriaRevisao::hashItens($r))?>">
 <?php $registroDatacao=$r; include MAT_ROOT.'/views/datacao_campos.php'; ?>
 <?php if(!empty($r['pendencias'])): ?><fieldset><legend>Pendências — selecionar individualmente</legend>
 <?php foreach($r['pendencias'] as $ix=>$item): ?><label class="mat-review-confirm"><input type="checkbox" name="pendencias_resolvidas[]" value="<?=$ix?>"> <?=h(is_scalar($item)?(string)$item:json_encode($item,JSON_UNESCAPED_UNICODE))?></label>
 <label>Fundamentação para a pendência <?=$ix+1?> (obrigatória quando selecionada)<textarea name="parecer_pendencias[<?=$ix?>]" rows="2" maxlength="2000" placeholder="Referência consultada, trecho e conclusão do pesquisador (mínimo 40 caracteres)."></textarea></label>
 <label>Fonte de suporte para esta pendência<select name="fonte_pendencias[<?=$ix?>]"><option value="">Selecione...</option><?php foreach($r['fontes'] as $f): ?><option value="<?=h((string)$f['fonte_id'])?>"><?=h((string)$f['fonte_id'])?> — <?=h((string)($f['titulo']??''))?></option><?php endforeach; ?></select></label><?php endforeach; ?></fieldset><?php endif; ?>
 <?php if(!empty($r['duplicatas'])): ?><fieldset><legend>Duplicatas — selecionar somente falsos positivos comprovados</legend>
 <?php foreach($r['duplicatas'] as $ix=>$item): ?><label class="mat-review-confirm"><input type="checkbox" name="duplicatas_resolvidas[]" value="<?=$ix?>"> <?=h(is_scalar($item)?(string)$item:json_encode($item,JSON_UNESCAPED_UNICODE))?></label>
 <label>Fundamentação para a possível duplicata <?=$ix+1?><textarea name="parecer_duplicatas[<?=$ix?>]" rows="2" maxlength="2000" placeholder="Distinção histórica comprovada por fonte (mínimo 40 caracteres)."></textarea></label>
 <label>Fonte de suporte para esta duplicata<select name="fonte_duplicatas[<?=$ix?>]"><option value="">Selecione...</option><?php foreach($r['fontes'] as $f): ?><option value="<?=h((string)$f['fonte_id'])?>"><?=h((string)$f['fonte_id'])?> — <?=h((string)($f['titulo']??''))?></option><?php endforeach; ?></select></label><?php endforeach; ?></fieldset><?php endif; ?>
 <label class="mat-review-confirm"><input type="checkbox" name="confirmacao_documental" value="1"> Se marquei pendências ou duplicatas resolvidas, conferi pessoalmente os documentos e justificarei individualmente cada resolução.</label>
 <label>Justificativa histórica e fontes consultadas<textarea name="motivo" minlength="15" maxlength="2500" required rows="3" placeholder="Ex.: ano conferido na publicação original; páginas e links; resolução de dúvidas..."></textarea></label>
 <button type="submit" class="mat-review-btn mat-review-neutral">Salvar correções e registrar auditoria</button>
 </form></details>
 <p class="mat-review-legal"><strong>Aprovar</strong> confirma a auditoria do dossiê; <strong>homologar</strong> é uma decisão posterior e explícita que autoriza o cálculo oficial. Rejeitar não exclui fisicamente dados. Qualquer mudança fica no <code>cur_log</code>.</p>
</section>
<section class="paper-panel mat-cur-sources">
 <div class="panel-head"><span>DOCUMENTO DE ORIGEM</span><span>FONTE DO BANCO · NÃO É HOMOLOGAÇÃO</span></div>
 <h2>Fragmento e referência preservados da extração</h2>
 <p><strong>Livro:</strong> <?=h($r['fonte_original']??'McClellan III & Dorn (2006)')?></p>
 <p><strong>Título extraído:</strong> <?=h($r['titulo_extraido']??'')?></p>
 <p><strong>Fragmento extraído (pode estar truncado):</strong> <?=h((string)($o['trecho_evidencia']??'Não registrado'))?></p>
 <p><strong>Capítulo:</strong> <?=h((string)($o['capitulo']??'Não informado'))?> · <strong>Página no PDF:</strong> <?=h((string)($o['pagina_pdf']??'Não informada'))?></p>
 <p class="mat-cur-research-disclaimer">Os fragmentos foram preservados como recebidos. Nem sempre representam o trecho integral do livro; verifique a obra original antes da homologação.</p>
</section>
<?php if(!empty($r['analise'])): ?>
<section class="paper-panel mat-cur-sources">
 <div class="panel-head"><span>ANÁLISE DOCUMENTAL</span><span>PROPOSTA · SUJEITA A REVISÃO</span></div>
 <h2>Como o acontecimento foi interpretado</h2>
 <?php foreach(['o_que_aconteceu'=>'O que aconteceu','problema_resolvido'=>'Problema resolvido','transformacao'=>'Transformação tecnológica','consequencias_posteriores'=>'Consequências posteriores','limitacoes_controversias'=>'Limitações e controvérsias'] as $campo=>$rotulo): ?>
  <?php if(!empty($r['analise'][$campo]) && is_scalar($r['analise'][$campo])): ?><p><strong><?=h($rotulo)?>:</strong> <?=h((string)$r['analise'][$campo])?></p><?php endif; ?>
 <?php endforeach; ?>
</section>
<?php endif; ?>
<section class="paper-panel mat-cur-criteria">
 <div class="panel-head"><span>MEMÓRIA DOCUMENTAL</span><span>11 CRITÉRIOS · ESCALA DE 1 A 3</span></div>
 <h2>Notas, justificativas e evidências</h2>
 <p>Abra cada rubrica para conferir a justificativa registrada, os IDs das fontes e o que ainda falta comprovar. Falta de informação não equivale à nota zero.</p>
 <?php foreach(CuradoriaRodadas::GRUPOS as $grupo=>$chaves): ?>
  <div class="mat-cur-group-title"><?=h($grupo)?></div>
  <?php foreach($chaves as $key): $item=$c[$key]??null;$valid=$item && $item['nota']!==null && in_array((int)$item['nota'],[0,1,2,3],true); ?>
  <details class="mat-cur-disclosure">
   <summary><span><small class="mat-cur-criterion-code"><?=h($key)?></small><strong><?=h(CuradoriaRodadas::CRITERIOS[$key])?></strong></span><span class="mat-cur-score <?=$valid?'':'pending'?>"><?=$valid?(int)$item['nota'].' / 3':($item && $item['situacao']==='NAO_ATENDE'?'NÃO ATENDE':'SEM EVIDÊNCIA')?></span></summary>
   <div class="mat-cur-disclosure-body">
   <p><b>Justificativa registrada:</b> <?=h((string)($item['justificativa']??'Ainda não foi registrada uma avaliação documental para este critério.'))?></p>
   <?php if($item):$ids=CuradoriaRodadas::decodeArray((string)$item['fontes_ids_json']);$evidence=CuradoriaRodadas::decodeArray((string)$item['evidencias_json']); ?>
     <p><b>Fontes associadas:</b> <?=h($ids?implode(' · ',array_map('strval',$ids)):'Nenhuma fonte associada')?></p>
     <?php if($evidence): ?><div class="mat-cur-evidence"><b>Evidências registradas:</b><ul><?php foreach($evidence as $ev): ?><li><?=h(is_scalar($ev)?(string)$ev:json_encode($ev,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))?></li><?php endforeach;?></ul></div><?php endif;?>
     <?php if(!empty($item['o_que_falta'])): ?><div class="mat-cur-needed"><b>O que falta:</b> <?=h((string)$item['o_que_falta'])?></div><?php endif; ?>
   <?php endif; ?>
   <details class="mat-review-edit"><summary>Corrigir esta rubrica e salvar parecer <span>⌄</span></summary>
    <form method="post" action="actions.php" class="mat-review-form"><?=csrf_field()?><input type="hidden" name="action" value="curadoria_criterio"><input type="hidden" name="id_extracao" value="<?=h($r['id_extracao'])?>"><input type="hidden" name="codigo" value="<?=h($key)?>">
     <label>Nota revisada<select name="nota"><option value="SEM_EVIDENCIA" <?=$item===null||$item['nota']===null?'selected':''?>>SEM EVIDÊNCIA (sem nota)</option><?php foreach([0,1,2,3] as $notaOpt):?><option value="<?=$notaOpt?>" <?=$item!==null&&$item['nota']!==null&&(int)$item['nota']===$notaOpt?'selected':''?>><?=$notaOpt?> — <?=['Não atende (com evidência)','Baixo','Médio','Alto'][$notaOpt]?></option><?php endforeach;?></select></label>
     <label>Justificativa documental<textarea name="justificativa" rows="3" maxlength="8000" placeholder="Fundamente a nota com evidências..." ><?=h((string)($item['justificativa']??''))?></textarea></label>
     <label>IDs de fontes cadastradas, separados por vírgula<input type="text" name="fontes_ids" value="<?=h($item?implode(', ',array_map('strval',CuradoriaRodadas::decodeArray((string)$item['fontes_ids_json']))):'')?>" placeholder="Ex.: B2006, RS_HIST"></label>
     <label>Motivo da alteração (será salvo no histórico)<textarea name="motivo" rows="2" minlength="15" maxlength="2500" required placeholder="Explique por que mudou ou confirmou a nota..."></textarea></label>
     <button class="mat-review-btn mat-review-neutral" type="submit">Registrar nova avaliação</button>
    </form>
   </details>
   </div>
  </details>
  <?php endforeach; ?>
 <?php endforeach; ?>
</section>
<section class="paper-panel mat-cur-sources">
 <div class="panel-head"><span>TRANSPARÊNCIA</span><span>VISUALIZAÇÃO OPCIONAL</span></div>
 <details class="mat-cur-disclosure mat-cur-calc"><summary><span><strong>Mostrar a memória de cálculo</strong><small>Fórmulas da Têmporis 0.2, apenas para auditoria</small></span><span aria-hidden="true">⌄</span></summary>
 <div class="mat-cur-disclosure-body">
  <?php foreach(CuradoriaRodadas::GRUPOS as $group=>$keys): ?>
  <div class="mat-cur-calc-line"><strong><?=h($group)?></strong><span><?=implode(' + ',array_map(static fn($k)=>$m['scores'][$k]===null?'?':(string)$m['scores'][$k],$keys))?> = <?=short_num($m['sums'][$group])?> / <?=count($keys)*3?></span></div>
  <?php endforeach; ?>
  <?php if($m['completa']): ?>
    <div class="mat-cur-calc-formulas">
     <p><code>I = 1 + 9 × (<?=$m['sums']['Impacto']?> / 12) = <?=decimal($m['I'],4)?></code></p>
     <p><code>C = <?=$m['sums']['Confiabilidade histórica']?> / 12 = <?=decimal($m['C'],4)?></code></p>
     <p><code>F = 1 + 0,5 × (<?=$m['sums']['Relevância fundacional']?> / 9) = <?=decimal($m['F'],4)?></code></p>
     <p><code>peso = (I / 10)² × C × F = <?=decimal($m['peso'],6)?></code></p>
    </div>
    <p><strong>Resultado demonstrativo não homologado.</strong> Este peso individual não constitui a série histórica ou o Índice Têmporis oficial. Depende da auditoria, da confirmação das fontes e da elegibilidade do marco.</p>
  <?php else: ?><p>Não é possível concluir o peso: <?=$m['avaliadas']?>/11 notas válidas e justificadas foram registradas, e/ou há documentação insuficiente. Os campos marcados com “?” ficam pendentes; nada é preenchido artificialmente.</p><?php endif; ?>
 </div></details>
</section>
<section class="paper-panel mat-cur-sources">
 <div class="panel-head"><span>REFERÊNCIAS DOCUMENTAIS</span><span><?=count($r['fontes'])?> FONTES CADASTRADAS</span></div>
 <h2>Fontes consultadas na pesquisa</h2>
 <?php foreach($r['fontes'] as $src):$link=CuradoriaRodadas::sourceUrl($src['url']??null); ?>
 <div class="mat-cur-source"><div><small><?=h($src['fonte_id'])?> · <?=h($src['ano_texto']?:'s.d.')?></small><strong><?=h($src['titulo']?:'Fonte sem título')?></strong><span><?=h($src['autor_instituicao']?:'Instituição não informada')?></span><?php if($src['descricao_evidencia']):?><p><?=h($src['descricao_evidencia'])?></p><?php endif;?></div><?php if($link):?><a href="<?=h($link)?>" rel="noopener noreferrer" target="_blank">Consultar fonte ↗</a><?php endif;?></div>
 <?php endforeach; ?>
 <?php if(!$r['fontes']):?><p>Ainda não há fontes adicionais cadastradas para este candidato. Não atribua notas sem evidência.</p><?php endif;?>
</section>
<?php if(!empty($r['log'])): ?>
<section class="paper-panel mat-cur-sources">
 <div class="panel-head"><span>TRILHA DE AUDITORIA</span><span><?=count($r['log'])?> EVENTOS</span></div>
 <details class="mat-cur-disclosure"><summary><span><strong>Mostrar histórico do dossiê</strong><small>Eventos que constam no MySQL</small></span><span aria-hidden="true">⌄</span></summary>
 <div class="mat-cur-disclosure-body">
 <?php foreach($r['log'] as $evento): ?>
 <p><small><?=h((string)$evento['data_acao'])?></small> <strong><?=h((string)$evento['acao'])?></strong> — <?=h((string)$evento['descricao'])?></p>
 <?php endforeach; ?>
 </div></details>
</section>
<?php endif; ?>
<?php endif; ?>
<?php else: ?>
<section class="mat-cur-round-grid">
 <?php foreach($resumosRodadas as $rod):$planejada=$rod['estado']==='PLANEJADA'; ?>
 <div class="paper-panel mat-cur-round <?=$planejada?'mat-cur-round-next':''?>">
  <div class="panel-head"><span>RODADA / <?=str_pad((string)$rod['numero'],2,'0',STR_PAD_LEFT)?></span><span><?=h(CuradoriaRodadas::label($rod['estado']))?></span></div>
  <h2><?=count($rod['itens'])?> <?=count($rod['itens'])===1?'candidato':'candidatos'?> cadastrados</h2>
  <p><?=h($rod['descricao']?:'Acompanhe os registros reais desta rodada no banco MySQL.')?></p>
  <div class="mat-cur-round-preview">
  <?php foreach(array_slice($rod['itens'],0,5) as $item): ?><a href="<?=mat_url('rodadas',['id'=>$item['id_extracao']])?>"><span><?=h($item['id_extracao'])?></span><span><?=h($item['titulo_proposto'])?></span><span>↗</span></a><?php endforeach; ?>
  <?php if(!$rod['itens']): ?><p>Nenhum dossiê desta rodada foi importado ainda.</p><?php endif; ?>
  </div>
 </div>
 <?php endforeach; ?>
</section>
<section class="paper-panel mat-cur-list">
 <div class="panel-head"><span>DOSSIÊS DA CURADORIA · MYSQL</span><span><?=short_num($lista['total'])?> RESULTADOS</span></div>
 <h2>Explorar os candidatos</h2>
 <p>Consulte todos os dossiês efetivamente armazenados. Os próximos lotes aparecerão ao serem importados, sem copiar dados para arquivos locais.</p>
 <form class="mat-cur-search" method="get" action="index.php"><input type="hidden" name="page" value="rodadas"><label>Pesquisar por ID ou título<input type="search" name="busca" maxlength="100" value="<?=h($busca)?>" placeholder="MCD-00422, transistor..."></label><label>Situação<select name="filtro"><?php foreach(['todos'=>'Todos','pesquisados'=>'Pesquisados','planejados'=>'Próxima rodada','nao_iniciados'=>'Não iniciados','duplicatas'=>'Possíveis duplicatas','sem_evidencia'=>'Sem pesquisa documental','em_revisao'=>'Em revisão','aprovados'=>'Aprovados','homologados'=>'Homologados','rejeitados'=>'Rejeitados'] as $k=>$t):?><option value="<?=h($k)?>" <?=$filtro===$k?'selected':''?>><?=h($t)?></option><?php endforeach;?></select></label><button class="btn btn-dark" type="submit">Filtrar ↗</button></form>
 <div class="mat-cur-table-wrap"><table class="mat-cur-table"><thead><tr><th>ID</th><th>Candidato</th><th>Situação</th><th>Ano</th><th>Documento</th></tr></thead><tbody>
 <?php foreach($lista['rows'] as $item): ?><tr><td class="mat-cur-id"><?=h($item['id_extracao'])?></td><td><?=h($item['titulo_proposto']?:'Título a conferir')?></td><td><span class="mat-cur-table-status"><?=h(CuradoriaRevisao::ESTADOS[(string)$item['estado_revisao_humana']]??CuradoriaRodadas::label((string)$item['estado_fluxo']))?></span></td><td><?=isset($item['ano_original'])?yfmt((int)$item['ano_original']):'—'?></td><td><a href="<?=mat_url('rodadas',['id'=>$item['id_extracao']])?>">Ver ficha ↗</a></td></tr><?php endforeach;?>
 <?php if(!$lista['rows']): ?><tr><td colspan="5">Nenhum registro encontrado nesse filtro.</td></tr><?php endif;?></tbody></table></div>
 <div class="mat-cur-pagination"><span>Página <?=$lista['page']?> de <?=$lista['pages']?></span><div><?php if($lista['page']>1):?><a href="<?=mat_url('rodadas',['filtro'=>$filtro,'busca'=>$busca,'pagina'=>$lista['page']-1])?>">← Anterior</a><?php endif;?><?php if($lista['page']<$lista['pages']):?><a href="<?=mat_url('rodadas',['filtro'=>$filtro,'busca'=>$busca,'pagina'=>$lista['page']+1])?>">Próxima →</a><?php endif;?></div></div>
</section>
<?php endif; ?>
<?php endif; ?>
