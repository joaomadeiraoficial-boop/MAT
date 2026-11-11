<?php
if(!defined('MAT_ROOT') || !is_admin()) { http_response_code(403);exit; }
require_once MAT_ROOT.'/app/CuradoriaFilaRapida.php';
$curDb=CuradoriaRodadas::connect();
$queue=null;$current=null;$queueError=false;
$filter=CuradoriaFilaRapida::filtro((string)($_GET['filtro']??'pendentes'));
$search=substr(trim((string)($_GET['busca']??'')),0,100);
$problem=CuradoriaFilaRapida::problema((string)($_GET['problema']??''));
$id=strtoupper(trim((string)($_GET['id']??'')));
if($curDb) { try {
    $queue=CuradoriaFilaRapida::consultar($curDb,$filter,$search,$id,$problem);
    if($queue['selected']) $current=CuradoriaRodadas::record($curDb,(string)$queue['selected']['id_extracao']);
} catch(Throwable $e){$queueError=true;error_log('MAT fila rápida: '.$e->getMessage());} }
?>
<header class="page-heading mat-cur-heading mat-fast-heading">
 <div class="section-overline">MAT / PESQUISA E CURADORIA · REVISÃO HUMANA</div>
 <h1>Fila de <em>curadoria completa.</em></h1>
 <p>Todos os dossiês aparecem aqui, mesmo com <strong>0 a 10 notas</strong>. Leia as justificativas e confira as referências antes de decidir. A fila <strong>não</strong> aprova nem homologa ninguém automaticamente.</p>
</header>
<section class="paper-panel mat-fast-mode-panel" aria-label="Configuração da curadoria">
 <div class="mat-fast-mode-intro">
  <div><span class="mat-fast-eyebrow">CONFIGURAÇÃO ATUAL</span><strong>Modo de avaliação</strong><p>Use o modo simplificado para concluir a análise preliminar. A homologação oficial continua rigorosa.</p></div>
  <form method="post" action="actions.php" class="mat-fast-mode-form">
   <?=csrf_field()?><input type="hidden" name="action" value="curadoria_modo">
   <label for="mat-fast-modo">Política de revisão</label>
   <select id="mat-fast-modo" name="modo" aria-label="Modo de curadoria">
    <option value="simplificado" <?=CuradoriaPolitica::modo()==='simplificado'?'selected':''?>>Simplificado — preliminar</option>
    <option value="rigoroso" <?=CuradoriaPolitica::modo()==='rigoroso'?'selected':''?>>Rigoroso — científico</option>
   </select>
   <button type="submit" class="mat-fast-btn mat-fast-btn-primary">Aplicar</button>
  </form>
 </div>
 <p class="mat-fast-mode-foot"><strong>Importante:</strong> notas 1 provisórias e ressalvas permanecem identificadas. Mudar o modo não apaga os dados nem homologa candidatos.</p>
</section>
<?php if(!$queue): ?>
<section class="paper-panel mat-cur-empty" role="alert"><h2>Curadoria indisponível</h2><p>Conecte o banco <code>mat_temporis_curadoria</code> e verifique a existência das tabelas de candidatos, fontes e critérios. Nenhum dado fictício será exibido.</p><a href="<?=mat_url('andamento')?>">Voltar ao progresso científico ↗</a></section>
<?php else: ?>
<div class="mat-fast-overview-head">
 <div><span class="mat-fast-eyebrow">PAINEL DE CURADORIA</span><h2>Visão geral do banco</h2></div>
 <p><strong>Próxima ação recomendada:</strong> revisar os candidatos prontos, registrar sua decisão e depois corrigir os pendentes.</p>
</div>
<div class="mat-fast-stats" aria-label="Resumo real da fila">
 <div class="mat-fast-stat mat-fast-stat-neutral"><small>TOTAL DE MARCOS</small><strong><?=short_num($queue['counts']['todos'])?></strong><span>Cadastrados no banco</span></div>
 <div class="mat-fast-stat"><small>AGUARDANDO DECISÃO</small><strong><?=short_num($queue['counts']['pendentes'])?></strong><span>Inclui prontos e pendentes</span></div>
 <a class="mat-fast-stat mat-fast-stat-ready" href="<?=mat_url('fila_rapida',['filtro'=>'aptos'])?>"><small>PRONTOS PARA REVISAR</small><strong><?=short_num($queue['counts']['aptos'])?></strong><span>Conferir e decidir <span aria-hidden="true">→</span></span></a>
 <a class="mat-fast-stat mat-fast-stat-attention" href="<?=mat_url('fila_rapida',['filtro'=>'bloqueados'])?>"><small>PRECISAM DE CORREÇÃO</small><strong><?=short_num($queue['counts']['bloqueados'])?></strong><span>Abrir pendências <span aria-hidden="true">→</span></span></a>
 <div class="mat-fast-stat"><small>APROVADOS</small><strong><?=short_num($queue['counts']['aprovados'])?></strong><span>Aprovação preliminar; não é homologação</span></div>
</div>
<?php if(CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO): ?>
<details class="mat-fast-batch">
 <summary><span><strong>Preencher notas mínimas em lote</strong><small>Ferramenta opcional · até 25 fichas por vez · não aprova candidatos</small></span><span class="mat-fast-batch-toggle" aria-hidden="true">Mostrar opções</span></summary>
 <div class="mat-fast-batch-body">
  <p>Preenche apenas critérios sem nota ou com nota 0, em dossiês ainda pendentes que já tenham fonte. Notas 2 e 3 são preservadas, assim como as pendências e a revisão humana.</p>
  <?php if((int)$queue['counts']['incompletos']===0): ?><p class="mat-fast-batch-note">No momento, a fila não aponta fichas com menos de 11 notas. O preenchimento em lote pode ser desnecessário, embora ainda possam existir notas 0.</p><?php endif; ?>
  <form method="post" action="actions.php" class="mat-fast-batch-form">
   <?=csrf_field()?><input type="hidden" name="action" value="curadoria_minimos_lote">
   <label for="mat-fast-batch-reason">Justificativa desta rodada</label><textarea id="mat-fast-batch-reason" name="motivo" minlength="15" maxlength="2500" required rows="2" placeholder="Avaliação exploratória por insuficiência de evidências específicas."></textarea>
   <label class="mat-fast-check"><input type="checkbox" name="confirmacao_provisoria" value="1" required> Sei que nota 1 é provisória e não comprova impacto baixo.</label>
   <button type="submit" class="mat-fast-btn mat-fast-btn-primary">Preencher até 25 fichas</button>
  </form>
 </div>
</details>
<?php endif; ?>
<section class="mat-fast-filter-bar" aria-label="Filtros da fila">
 <?php foreach(CuradoriaFilaRapida::FILTROS as $key=>$name): ?><a class="<?=$key===$filter?'active':''?>" <?=$key===$filter?'aria-current="page"':''?> href="<?=mat_url('fila_rapida',['filtro'=>$key])?>"><?=h($name)?> <span><?=short_num($queue['counts'][$key])?></span></a><?php endforeach; ?>
</section>
<?php if($filter==='bloqueados'): ?>
<section class="mat-fast-problem-filters" aria-label="Separar por tipo de impedimento">
 <strong>Quero resolver:</strong>
 <?php foreach(CuradoriaFilaRapida::PROBLEMAS as $pKey=>$pTitle): ?>
 <a class="<?=($problem===$pKey?'active':'')?>" href="<?=mat_url('fila_rapida',['filtro'=>$filter,'problema'=>$pKey,'busca'=>$search])?>"><?=h($pTitle)?> <span><?=short_num($queue['problema_counts'][$pKey]??0)?></span></a>
 <?php endforeach; ?>
 <small>Um dossiê pode ter problemas em mais de uma categoria. Os mais próximos de serem liberados aparecem primeiro.</small>
</section>
<?php endif; ?>
<form class="mat-fast-search" action="index.php" method="get">
 <input type="hidden" name="page" value="fila_rapida"><input type="hidden" name="filtro" value="<?=h($filter)?>"><input type="hidden" name="problema" value="<?=h($problem)?>">
 <label for="mat-fast-find">Buscar candidato na fila</label><input type="search" id="mat-fast-find" name="busca" maxlength="100" value="<?=h($search)?>" placeholder="ID ou nome do marco">
 <button class="btn btn-dark" type="submit">Buscar ↗</button>
 <?php if($search!==''):?><a href="<?=mat_url('fila_rapida',['filtro'=>$filter,'problema'=>$problem])?>">Limpar busca</a><?php endif; ?>
</form>
<?php if($queue['total']===0): ?>
 <section class="paper-panel mat-fast-empty"><h2>Nenhum dossiê neste filtro.</h2><p><?php if($queue['counts']['todos']===0):?>Ainda não há candidatos cadastrados no banco de curadoria.<?php else:?>Mude o filtro ou remova a busca para consultar os outros dossiês.<?php endif;?></p><a href="<?=mat_url('rodadas')?>">Ver todas as rodadas ↗</a></section>
<?php else: $chosen=$queue['selected']; ?>
<div class="mat-fast-layout">
 <aside class="mat-fast-queue" aria-label="Candidatos em qualquer etapa de pontuação">
  <div class="mat-fast-queue-title"><strong>Fila selecionada</strong><span><?=short_num($queue['total'])?> dossiês</span></div>
  <p class="mat-fast-help">Escolha um registro. O próximo passo aparece na ficha; notas faltantes continuam em aberto.</p>
  <div class="mat-fast-items">
   <?php foreach($queue['rows'] as $n=>$item):
      $active=$chosen['id_extracao']===$item['id_extracao'];
      $blocked=(int)$item['quantidade_bloqueios']>0;
      $state=(string)$item['estado_revisao_humana'];
   ?>
    <a class="mat-fast-item <?=$active?'active':''?>" <?=$active?'aria-current="page"':''?> href="<?=mat_url('fila_rapida',['id'=>$item['id_extracao'],'filtro'=>$filter,'busca'=>$search,'problema'=>$problem])?>">
      <span class="mat-fast-item-head"><b><?=h($item['id_extracao'])?></b><small><?=h((string)($item['quantidade_notas']??0))?>/11</small></span>
      <span class="mat-fast-item-name"><?=h((string)$item['titulo_proposto'])?></span>
      <span class="mat-fast-item-foot"><?=h((string)($item['ano_validado']===null?'Ano pendente':yfmt((int)$item['ano_validado'])))?> · <?=h($state==='APROVADO'?'Aprovado':($state==='HOMOLOGADO'?'Homologado':($state==='REJEITADO'?'Rejeitado':($blocked?'⚠ '.$item['quantidade_bloqueios'].' impedimento(s)':'Pronto para conferir'))))?></span>
    </a>
   <?php endforeach; ?>
  </div>
 </aside>
 <div class="mat-fast-detail">
  <div class="mat-fast-stepbar"><span>REGISTRO <?=$queue['position']?> DE <?=$queue['total']?></span><div>
    <?php if($queue['prev']): ?><a data-fast-prev href="<?=mat_url('fila_rapida',['id'=>$queue['prev'],'filtro'=>$filter,'busca'=>$search,'problema'=>$problem])?>">← Anterior</a><?php endif; ?>
    <?php if($queue['next']): ?><a data-fast-next href="<?=mat_url('fila_rapida',['id'=>$queue['next'],'filtro'=>$filter,'busca'=>$search,'problema'=>$problem])?>">Próximo →</a><?php endif; ?>
  </div></div>
  <p class="mat-fast-shortcuts">Atalhos: <kbd>Alt + J</kbd> próximo e <kbd>Alt + K</kbd> anterior, fora dos campos de texto.</p>
  <?php if(!$current): ?><section class="paper-panel"><p>O dossiê selecionado não pôde ser carregado. Atualize o banco ou tente novamente.</p></section>
  <?php else: $blockers=CuradoriaRevisao::impedimentos($current); $state=(string)$current['estado_revisao_humana']; $canApprove=empty($blockers);
    $sessionDraft=$_SESSION['mat_fast_draft']??null;
    $draft=is_array($sessionDraft) && ($sessionDraft['id']??'')===$current['id_extracao'] ? ($sessionDraft['fields']??[]) : [];
    if($draft)unset($_SESSION['mat_fast_draft']);
    $criterioSession=$_SESSION['mat_fast_criterion_draft']??null;
    $criterioDraft=is_array($criterioSession) && ($criterioSession['id']??'')===$current['id_extracao'] ? $criterioSession : null;
    if($criterioDraft)unset($_SESSION['mat_fast_criterion_draft']); ?>
  <div class="mat-fast-direct-action"><a href="#decisao-rapida">Ir ao parecer após a conferência ↓</a></div>
  <article class="paper-panel mat-fast-record">
   <div class="panel-head"><span>DOSSIÊ / <?=h($current['id_extracao'])?></span><span>RODADA <?=h((string)$current['rodada'])?></span></div>
   <h2><?=h($current['titulo_proposto'])?></h2>
   <p class="mat-fast-facts">Ano de referência: <strong><?=h(CuradoriaDatacao::intervalo($current) ? CuradoriaDatacao::texto($current) : ($current['ano_validado']===null?'Não validado':yfmt((int)$current['ano_validado'])))?></strong> · Fontes cadastradas: <strong><?=count($current['fontes'])?></strong> · Revisão: <strong><?=h(CuradoriaRevisao::ESTADOS[$state]??$state)?></strong></p>
   <?php if(CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO): $avisos=CuradoriaPolitica::avisos($current); ?>
   <div class="mat-fast-blocker" style="margin:14px 0"><strong>Modo simplificado: <?=count($avisos)?> ressalva(s) para a auditoria futura</strong>
   <?php if($avisos): ?><details><summary>Ver ressalvas preservadas</summary><ul><?php foreach($avisos as $av): ?><li><?=h($av)?></li><?php endforeach; ?></ul></details><?php endif; ?>
   <p>Esta fase permite apenas aprovação preliminar. Notas mínimas não são comprovação científica.</p></div>
  <?php endif; ?>
  <p class="mat-fast-facts"><strong>Próxima ação:</strong> <?=h((string)($chosen['proxima_acao']??'Conferir dossiê'))?> · <strong><?=h((string)($chosen['quantidade_notas']??0))?>/11 notas</strong></p>
   <div class="mat-fast-extract"><b>Menção de origem</b><p><?=h((string)($current['titulo_original']??'Não informado'))?></p><small>Página do livro: <?=h((string)($current['pagina_livro']??'não informada'))?> · Página do PDF: <?=h((string)($current['pagina_pdf']??'não informada'))?></small></div>
   <?php if($blockers): ?>
   <div class="mat-fast-blocker" role="status"><h3><span aria-hidden="true">!</span> <?=count($blockers)?> impedimento(s) para aprovação</h3>
    <p class="mat-fast-blocker-explain">Cada impedimento tem uma correção acessível nesta página. Marque como resolvido <b>apenas após verificar o documento</b>, e registre a justificativa no histórico.</p>
    <ul><?php foreach($blockers as $error):
        $link='#mat-fast-edit-meta';
        if(str_starts_with($error,'Vincular pelo menos uma fonte'))$link='#mat-fast-add-source';
        elseif(str_starts_with($error,'Pontuar com evidência:') || str_starts_with($error,'Justificar a nota:') || str_starts_with($error,'Informar a fonte vinculada ao critério:') || str_starts_with($error,'Fonte ')){
            foreach(CuradoriaRodadas::CRITERIOS as $code=>$nome){if(str_contains($error,$nome)){$link='#mat-fast-criterion-'.$code;break;}}
        }
      ?><li><?=h($error)?> <a href="<?=h($link)?>">Corrigir ↓</a></li><?php endforeach;?></ul>
   </div>
   <?php else: ?><div class="mat-fast-eligible"><strong>Sem bloqueios formais.</strong> A conferência histórica e documental ainda precisa ser realizada por você; pontuação completa não prova que o candidato é válido.</div><?php endif; ?>
   <?php if($flash && in_array($flash['type'],['success','error'],true) && isset($_GET['foco'])): ?>
    <div class="mat-fast-save-feedback <?=h($flash['type'])?>" id="mat-fast-save-feedback" role="alert"><strong><?= $flash['type']==='success'?'Resultado confirmado':'Atencao: correcao nao foi salva' ?></strong><p><?=h((string)$flash['message'])?></p><small>Impedimentos atuais deste dossie: <?=count($blockers)?>. Uma pendencia documental e cada rubrica sem nota sao correcoes diferentes.</small></div>
   <?php endif; ?>
   <?php if($state!=='HOMOLOGADO'): ?>
   <details id="mat-fast-edit-meta" class="mat-fast-fix" <?=($blockers && (empty($current['ano_validado']) || $current['pendencias'] || $current['duplicatas']))?'open':''?>>
    <summary>01 · Corrigir data, pendências e duplicatas <span>⌄</span></summary>
    <div class="mat-fast-fix-body">
      <form method="post" action="actions.php" class="mat-fast-inline-form">
      <?=csrf_field()?><input type="hidden" name="action" value="curadoria_metadados"><input type="hidden" name="id_extracao" value="<?=h($current['id_extracao'])?>"><input type="hidden" name="hash_itens" value="<?=h(CuradoriaRevisao::hashItens($current))?>">
      <input type="hidden" name="origem" value="fila_rapida"><input type="hidden" name="retorno_filtro" value="<?=h($filter)?>"><input type="hidden" name="retorno_problema" value="<?=h($problem)?>"><input type="hidden" name="retorno_busca" value="<?=h($search)?>">
      <?php $registroDatacao=$current; $datacaoDraft=$draft; include MAT_ROOT.'/views/datacao_campos.php'; ?>
      <?php if($current['pendencias']): ?><fieldset><legend>Pendências documentais — marque só as que verificou</legend>
       <?php foreach($current['pendencias'] as $ix=>$pend): ?>
         <label class="mat-fast-issue"><input type="checkbox" name="pendencias_resolvidas[]" value="<?=$ix?>" <?=in_array((string)$ix,array_map('strval',(array)($draft['pendencias_resolvidas']??[])),true)?'checked':''?>><span><?=h(is_scalar($pend)?(string)$pend:json_encode($pend,JSON_UNESCAPED_UNICODE))?></span></label>
         <label>Parecer específico da pendência <?=$ix+1?> (mín. 40 caracteres, obrigatório se marcada) <textarea name="parecer_pendencias[<?=$ix?>]" rows="2" maxlength="2000" placeholder="Informe fonte, trecho e por que esta pendência foi resolvida."><?=h((string)($draft['parecer_pendencias'][$ix]??''))?></textarea></label>
         <label>Fonte utilizada para esta pendência <select name="fonte_pendencias[<?=$ix?>]"><option value="">Selecione a fonte...</option><?php foreach($current['fontes'] as $f): ?><option value="<?=h((string)$f['fonte_id'])?>" <?=((string)($draft['fonte_pendencias'][$ix]??'')===(string)$f['fonte_id'])?'selected':''?> ><?=h((string)$f['fonte_id'])?> — <?=h((string)($f['titulo']??''))?></option><?php endforeach; ?></select></label>
       <?php endforeach; ?></fieldset><?php endif; ?>
      <?php if($current['duplicatas']): ?><fieldset><legend>Possíveis duplicatas — confirme os registros distintos</legend>
       <p class="mat-fast-warning">Se comprovou ser o MESMO acontecimento, não elimine o alerta para aprová-lo. Registre a rejeição ou mantenha em revisão.</p>
       <?php foreach($current['duplicatas'] as $ix=>$dup): ?>
         <label class="mat-fast-issue"><input type="checkbox" name="duplicatas_resolvidas[]" value="<?=$ix?>" <?=in_array((string)$ix,array_map('strval',(array)($draft['duplicatas_resolvidas']??[])),true)?'checked':''?>><span><?=h(is_scalar($dup)?(string)$dup:json_encode($dup,JSON_UNESCAPED_UNICODE))?> — verifiquei que não é a mesma ocorrência</span></label>
         <label>Parecer específico da duplicata <?=$ix+1?> (mín. 40 caracteres, obrigatório se marcada) <textarea name="parecer_duplicatas[<?=$ix?>]" rows="2" maxlength="2000" placeholder="Explique diferenças históricas e cite as referências consultadas."><?=h((string)($draft['parecer_duplicatas'][$ix]??''))?></textarea></label>
         <label>Fonte utilizada para esta duplicata <select name="fonte_duplicatas[<?=$ix?>]"><option value="">Selecione a fonte...</option><?php foreach($current['fontes'] as $f): ?><option value="<?=h((string)$f['fonte_id'])?>" <?=((string)($draft['fonte_duplicatas'][$ix]??'')===(string)$f['fonte_id'])?'selected':''?> ><?=h((string)$f['fonte_id'])?> — <?=h((string)($f['titulo']??''))?></option><?php endforeach; ?></select></label>
       <?php endforeach; ?></fieldset><?php endif; ?>
      <?php $faltantes=[]; foreach(CuradoriaRodadas::CRITERIOS as $codigoF=>$nomeF)if(($current['criterios'][$codigoF]['nota']??null)===null)$faltantes[$codigoF]=$nomeF; ?>
      <?php if($faltantes): ?>
      <fieldset class="mat-fast-batch-rubrics"><legend>Concluir as <?=count($faltantes)?> rubricas sem nota — junto com este salvamento</legend>
       <p>Preencha cada criterio que ja pesquisou. Nota, justificativa e fonte sao obrigatorias para cada criterio preenchido. Os campos em branco permanecem pendentes.</p>
       <?php foreach($faltantes as $codigoF=>$nomeF): $dR=$draft['criterios_complementares'][$codigoF]??[]; ?>
        <div class="mat-fast-batch-criterion"><strong><?=h($nomeF)?></strong>
         <label>Nota (1–3) <select name="criterios_complementares[<?=h($codigoF)?>][nota]"><option value="">Nao pontuar agora</option><?php foreach(['1','2','3'] as $n):?><option value="<?=$n?>" <?=((string)($dR['nota']??'')===$n?'selected':'')?>><?=$n?> / 3</option><?php endforeach;?></select></label>
         <label>Justificativa documental desta rubrica <textarea name="criterios_complementares[<?=h($codigoF)?>][justificativa]" rows="2" maxlength="1500" placeholder="Explique o motivo da nota com a evidencia consultada (minimo 25 caracteres)."><?=h((string)($dR['justificativa']??''))?></textarea></label>
         <label>Fonte que sustenta esta nota <select name="criterios_complementares[<?=h($codigoF)?>][fonte_id]"><option value="">Selecione fonte ja cadastrada</option><?php foreach($current['fontes'] as $f):?><option value="<?=h((string)$f['fonte_id'])?>" <?=((string)($dR['fonte_id']??'')===(string)$f['fonte_id']?'selected':'')?>><?=h((string)$f['fonte_id'])?> — <?=h((string)$f['titulo'])?></option><?php endforeach;?></select></label>
        </div>
       <?php endforeach; ?>
      </fieldset>
      <?php endif; ?>
      <label>Justificativa documental <small>(obrigatória; para resolver itens, mínimo de 60 caracteres)</small>
       <textarea name="motivo" rows="3" minlength="15" maxlength="2500" required placeholder="Indique o que conferiu, fonte consultada (autor, título/link), ano e por que resolveu o item."><?=h((string)($draft['motivo']??''))?></textarea></label>
      <label class="mat-fast-issue"><input type="checkbox" name="confirmacao_documental" value="1" <?=!empty($draft['confirmacao_documental'])?'checked':''?>><span>Conferi pessoalmente os itens marcados e descrevi o fundamento acima.</span></label>
      <button type="submit" class="mat-fast-btn mat-fast-btn-primary">Salvar e conferir o que foi gravado ↗</button><p class="mat-fast-help">Depois de salvar, o MAT mostrara aqui o numero real de impedimentos restantes. Nao e necessario preencher a mesma ficha varias vezes sem alterar campos.</p>
      </form>
    </div>
   </details>
   <?php endif; ?>
   <div class="mat-fast-criteria-head" id="mat-fast-criteria"><h3>Conferência das 11 rubricas</h3><span>NOTAS PROPOSTAS · NÃO HOMOLOGADAS</span></div>
   <?php foreach(CuradoriaRodadas::GRUPOS as $group=>$codes): ?>
    <section class="mat-fast-criteria-group" aria-label="<?=h($group)?>"><h4><?=h($group)?></h4>
     <?php foreach($codes as $code): $note=$current['criterios'][$code]??null; $nval=$note['nota']??null; $sourceIds=$note?CuradoriaRodadas::decodeArray((string)$note['fontes_ids_json']):[]; ?>
      <?php $critDraft=$criterioDraft && ($criterioDraft['codigo']??'')===$code ? ($criterioDraft['fields']??[]) : []; ?>
      <div class="mat-fast-criterion" id="mat-fast-criterion-<?=h($code)?>">
       <div class="mat-fast-criterion-head"><strong><?=h(CuradoriaRodadas::CRITERIOS[$code])?></strong><span class="<?=$nval===null?'missing':''?>"><?=$nval===null?'SEM EVIDÊNCIA':h((string)$nval).' / 3'?></span></div>
       <p><?=h((string)($note['justificativa']??'Justificativa ainda não registrada.'))?></p>
       <small><b>Fontes da rubrica:</b> <?=h($sourceIds?implode(' · ',array_map('strval',$sourceIds)):'Sem vínculo documentado')?></small>
       <?php if($state!=='HOMOLOGADO'): ?>
       <details class="mat-fast-inline-edit" <?= $critDraft?'open':'' ?>><summary>Editar esta avaliação <span aria-hidden="true">⌄</span></summary>
        <form method="post" action="actions.php" class="mat-fast-inline-form">
         <?=csrf_field()?><input type="hidden" name="action" value="curadoria_criterio"><input type="hidden" name="id_extracao" value="<?=h($current['id_extracao'])?>"><input type="hidden" name="codigo" value="<?=h($code)?>">
         <input type="hidden" name="origem" value="fila_rapida"><input type="hidden" name="retorno_filtro" value="<?=h($filter)?>"><input type="hidden" name="retorno_problema" value="<?=h($problem)?>"><input type="hidden" name="retorno_busca" value="<?=h($search)?>">
         <?php $notaEdit=(string)($critDraft['nota']??($nval===null?'SEM_EVIDENCIA':$nval)); ?>
         <label>Nota revisada <select name="nota"><option value="SEM_EVIDENCIA" <?=$notaEdit==='SEM_EVIDENCIA'?'selected':''?>>Sem evidência (sem nota)</option><?php foreach([0,1,2,3] as $option): ?><option value="<?=$option?>" <?=$notaEdit===(string)$option?'selected':''?>><?=$option?> / 3</option><?php endforeach;?></select></label>
         <label>Justificativa baseada em evidência <textarea rows="3" name="justificativa" maxlength="8000"><?=h((string)($critDraft['justificativa']??($note['justificativa']??'')))?></textarea></label>
         <label>IDs de fontes já cadastradas, separados por vírgula <input type="text" name="fontes_ids" value="<?=h((string)($critDraft['fontes_ids']??implode(', ',array_map('strval',$sourceIds))))?>" placeholder="Ex.: B2006, MUSEU"></label>
         <label>Motivo documentado desta correção <textarea name="motivo" rows="2" minlength="15" maxlength="2500" required><?=h((string)($critDraft['motivo']??''))?></textarea></label>
         <button class="mat-fast-btn mat-fast-btn-primary" type="submit">Salvar avaliação desta rubrica ↗</button>
        </form>
       </details>
       <?php endif; ?>
      </div>
     <?php endforeach; ?>
    </section>
   <?php endforeach; ?>
   <?php
   $rubricasParaNormalizar=0;
   foreach(CuradoriaRodadas::CRITERIOS as $codMin=>$nomeMin){
      $valorMin=$current['criterios'][$codMin]['nota']??null;
      if($valorMin===null || (string)$valorMin==='0')$rubricasParaNormalizar++;
   }
   ?>
   <?php if(CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO && !in_array($state,['REJEITADO','HOMOLOGADO'],true) && $rubricasParaNormalizar>0): ?>
   <details class="mat-fast-normalizacao" id="mat-fast-normalizacao">
    <summary>
     <span class="mat-fast-tool-mark" aria-hidden="true">+</span>
     <span class="mat-fast-tool-head"><strong>Completar <?=h((string)$rubricasParaNormalizar)?> <?= $rubricasParaNormalizar===1?'nota mínima provisória':'notas mínimas provisórias' ?></strong><small>Opcional · altera apenas notas ausentes ou 0 · não aprova o marco</small></span>
     <span class="mat-fast-tool-expand" aria-hidden="true">Abrir</span>
    </summary>
    <div class="mat-fast-normalizacao-body">
     <p>Use esta opção apenas quando não houver informação suficiente para uma nota maior. A nota 1 ficará identificada como <strong>provisória</strong> e continuará exigindo pesquisa na homologação.</p>
     <form method="post" action="actions.php" class="mat-fast-normalizacao-form">
      <?=csrf_field()?><input type="hidden" name="action" value="curadoria_minimos"><input type="hidden" name="id_extracao" value="<?=h($current['id_extracao'])?>">
      <label for="mat-fast-min-reason">Justificativa da avaliação preliminar</label>
      <textarea id="mat-fast-min-reason" name="motivo" minlength="15" maxlength="2500" required rows="3" placeholder="Ex.: Não encontrei evidências documentais suficientes para atribuir nota maior; usar nota 1 provisória nesta etapa."></textarea>
      <label class="mat-fast-check mat-fast-normalizacao-check"><input type="checkbox" name="confirmacao_provisoria" value="1" required><span>Entendo que nota 1 provisória não demonstra ausência nem baixo impacto.</span></label>
      <button type="submit" class="mat-fast-btn mat-fast-btn-primary">Preencher <?=h((string)$rubricasParaNormalizar)?> <?= $rubricasParaNormalizar===1?'nota':'notas' ?> e conferir ↗</button>
     </form>
    </div>
   </details>
   <?php endif; ?>
   <div class="mat-fast-sources" id="mat-fast-add-source"><h3>Fontes para conferir</h3>
    <?php foreach($current['fontes'] as $src): $url=CuradoriaRodadas::sourceUrl($src['url']??null); ?>
     <div class="mat-fast-source"><div><small><?=h((string)$src['fonte_id'])?></small><strong><?=h((string)($src['titulo']?:'Fonte sem título'))?></strong><p><?=h((string)($src['autor_instituicao']?:'Autoria não informada'))?> · <?=h((string)($src['descricao_evidencia']?:'Sem excerto adicional'))?></p></div><?php if($url):?><a href="<?=h($url)?>" target="_blank" rel="noopener noreferrer">Conferir ↗</a><?php endif;?></div>
    <?php endforeach; ?>
    <?php if(!$current['fontes']): ?><p>Nenhuma fonte vinculada: a aprovação permanecerá bloqueada.</p><?php endif; ?>
    <?php if($state!=='HOMOLOGADO'): ?>
    <details class="mat-fast-fix mat-fast-add-source" <?=!$current['fontes']?'open':''?>>
     <summary>02 · Cadastrar uma nova fonte verificável <span>⌄</span></summary>
     <div class="mat-fast-fix-body"><p>Preencha somente referências efetivamente consultadas. Adicionar uma fonte <strong>não</strong> a vincula automaticamente às 11 rubricas.</p>
      <form method="post" action="actions.php" class="mat-fast-inline-form mat-fast-source-form">
      <?=csrf_field()?><input type="hidden" name="action" value="curadoria_fonte"><input type="hidden" name="id_extracao" value="<?=h($current['id_extracao'])?>">
      <input type="hidden" name="origem" value="fila_rapida"><input type="hidden" name="retorno_filtro" value="<?=h($filter)?>"><input type="hidden" name="retorno_problema" value="<?=h($problem)?>"><input type="hidden" name="retorno_busca" value="<?=h($search)?>">
      <label>ID único da fonte <input name="fonte_id" maxlength="48" minlength="2" required placeholder="EX.: ARTIGO_2020"></label>
      <label>Autor ou instituição <input name="autor_instituicao" maxlength="180" required placeholder="Nome do pesquisador ou da instituição"></label>
      <label>Título do documento <input name="titulo_fonte" maxlength="255" required placeholder="Título verificável"></label>
      <label>Ano da publicação (opcional) <input type="number" name="ano_fonte" min="-5000000" max="2100" step="1"></label>
      <label>Link oficial ou DOI (URL completa) <input type="url" name="url" maxlength="500" placeholder="https://..."></label>
      <label>Trecho ou evidência conferida <textarea name="descricao_evidencia" rows="3" minlength="25" maxlength="4000" required placeholder="Descreva precisamente o dado que a fonte comprova."></textarea></label>
      <label>Motivo de acrescentar esta fonte <textarea name="motivo" rows="2" minlength="15" maxlength="2500" required></textarea></label>
      <button type="submit" class="mat-fast-btn mat-fast-btn-primary">Cadastrar fonte no dossiê ↗</button>
      </form>
     </div>
    </details>
    <?php endif; ?>
   </div>
   <div class="mat-fast-dossier-link"><a href="<?=mat_url('rodadas',['id'=>$current['id_extracao']])?>">Ver dossiê detalhado, memória de cálculo e histórico ↗</a></div>
  </article>
  <section class="paper-panel mat-fast-decision" id="decisao-rapida">
   <div class="panel-head"><span>PARECER DO PESQUISADOR</span><span>TRILHA DE AUDITORIA · MYSQL</span></div>
   <h2>Registrar e ir ao próximo</h2>
   <?php if($state==='HOMOLOGADO'): ?><p>Este dossiê já foi homologado. Para reabri-lo, acesse a ficha completa; a fila não altera homologações automaticamente.</p><a href="<?=mat_url('rodadas',['id'=>$current['id_extracao']])?>#revisao-humana">Reabrir no dossiê ↗</a>
   <?php else: ?>
   <form method="post" action="actions.php" class="mat-fast-decision-form" data-fast-form>
    <?=csrf_field()?><input type="hidden" name="action" value="curadoria_decisao"><input type="hidden" name="id_extracao" value="<?=h($current['id_extracao'])?>">
    <input type="hidden" name="origem" value="fila_rapida"><input type="hidden" name="retorno_filtro" value="<?=h($filter)?>"><input type="hidden" name="retorno_busca" value="<?=h($search)?>"><input type="hidden" name="retorno_problema" value="<?=h($problem)?>"><input type="hidden" name="proximo_id" value="<?=h((string)($queue['next']??''))?>">
    <label for="mat-fast-reason">Justificativa da decisão <small>(mínimo de 15 caracteres; salva com usuário e data)</small></label>
    <textarea id="mat-fast-reason" name="motivo" rows="3" minlength="15" maxlength="2500" required placeholder="Registre o que verificou nas fontes, datação e rubricas, ou o motivo de manter a revisão aberta."></textarea>
    <p class="mat-fast-check-error" data-fast-error role="alert" hidden></p>
    <label class="mat-fast-check"><input type="checkbox" name="confirmacao_humana" value="1" data-fast-checked><span><?=CuradoriaPolitica::modo()==='simplificado'?'Conferi a ocorrência e sua fonte, reconheço que as notas são preliminares e li as ressalvas pendentes.':'Conferi pessoalmente as 11 notas, justificativas, datas e fontes; nenhuma avaliação é automática.'?></span></label>
    <div class="mat-fast-actions">
     <button class="mat-fast-btn mat-fast-review" type="submit" name="decisao" value="em_revisao">Manter em revisão →</button>
     <button class="mat-fast-btn mat-fast-reject" type="submit" name="decisao" value="rejeitar">Rejeitar →</button>
     <?php if($state==='APROVADO'): ?><button class="mat-fast-btn mat-fast-approve" type="submit" name="decisao" value="homologar" <?=!CuradoriaRevisao::impedimentos($current,CuradoriaPolitica::RIGOROSO)?'':'disabled'?>>Homologar após conferência rigorosa →</button>
     <?php elseif($state==='REJEITADO'): ?><span class="mat-fast-action-hint">Para reconsiderar um rejeitado, use “Manter em revisão”.</span>
     <?php else: ?><button class="mat-fast-btn mat-fast-approve" type="submit" name="decisao" value="aprovar" <?=$canApprove?'':'disabled'?>>✓ Aprovar e próximo →</button><?php endif; ?>
    </div>
    <?php if(!$canApprove): ?><p class="mat-fast-action-hint">A aprovação permanece desativada até resolver os impedimentos acima. <a href="#mat-fast-edit-meta">Corrigir data/pendências</a> ou <a href="#mat-fast-criteria">conferir rubricas</a>. Você pode registrar “Em revisão” e avançar.</p><?php endif; ?>
    <p class="mat-fast-legal">Aprovação é uma decisão humana individual; homologação é outra decisão explícita. Todas as alterações ficam em <code>cur_log</code>. <a href="<?=mat_url('temporis')?>">Consultar gráfico exploratório Têmporis ↗</a></p>
   </form>
   <?php endif; ?>
  </section>
  <?php endif; ?>
 </div>
</div>
<?php endif; ?>
<?php endif; ?>
