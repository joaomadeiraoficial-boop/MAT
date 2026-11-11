<?php
$pares = db() ? duplicate_rows() : [];
$totalPendente=0;$totalDecidido=0;
foreach($pares as $par){
    $d=(string)($par['decisao_explicita']??'');
    $decidido=($par['status']==='nao_duplicado' || in_array($d,['duplicata_manter_a','duplicata_manter_b','duplicata_distintos'],true));
    $decidido ? ++$totalDecidido : ++$totalPendente;
}
?>
<header class="page-heading"><div class="section-overline">LABORATÓRIO / CONSISTÊNCIA</div><h1>Comparar antes <em>de decidir.</em></h1><p>Dois títulos semelhantes não provam que são o mesmo acontecimento. Compare data, descrição, área e fontes antes de resolver cada par.</p></header>
<?php
require_once MAT_ROOT.'/app/CuradoriaDuplicatas.php';
$curPairs=[];$curOk=false;
$curPdo=is_admin()?CuradoriaRodadas::connect():null;
if($curPdo){try{$curPairs=CuradoriaDuplicatas::listar($curPdo);$curOk=true;}catch(Throwable $e){error_log('MAT: comparar duplicatas da curadoria '.$e->getMessage());}}
?>
<?php
$curAlertas=0;
foreach($curPairs as $p){
  $decision=(string)($p['decisao']['decisao']??'');
  if(!in_array($decision,['manter_a','manter_b','nao_duplicado'],true) && ($p['diagnostico']['nivel']??'')==='alerta')$curAlertas++;
}
?>
<?php if(is_admin()):?>
<section class="paper-panel mat-dup-board" id="pares-curadoria">
 <div class="panel-head"><span>CURADORIA CIENTÍFICA · MAT_TEMPORIS_CURADORIA</span><span><?=count($curPairs)?> PARES REFERENCIADOS</span></div>
 <h2>Possíveis duplicatas das rodadas</h2>
 <p><strong>Por que aparecem estes pares?</strong> Eles foram marcados nos arquivos importados pelo campo <code>duplicatas_json</code>. O MAT anterior exibia essa ligação como suspeita sem verificar a semelhança dos eventos e sem explicar a origem. Agora distinguimos o <strong>vínculo recebido</strong> dos <strong>indícios reais</strong>. Alertas automáticos não são decisões científicas.</p>
 <?php if(!$curOk):?><p>Banco de curadoria não conectado.</p><?php else:?>
 <div class="mat-dup-tabs" data-dup-filters><button type="button" data-dup-filter="pending" aria-pressed="true">Pendentes</button><button type="button" data-dup-filter="decided" aria-pressed="false">Decididos</button><button type="button" data-dup-filter="alerts" aria-pressed="false">⚠ Sem semelhança aparente (<?=$curAlertas?>)</button><button type="button" data-dup-filter="all" aria-pressed="false">Todos</button></div>
 <?php foreach($curPairs as $pair):
    $exp=$pair['diagnostico'];
    $dec=$pair['decisao'];$tipo=(string)($dec['decisao']??'');
    $fechado=in_array($tipo,['manter_a','manter_b','nao_duplicado'],true);
    $princ=(string)($dec['principal_id']??'');
 ?>
 <article class="mat-dup-case" data-dup-state="<?=$fechado?'decided':'pending'?>" data-dup-risk="<?=h((string)$exp['nivel'])?>">
   <div class="mat-dup-casehead"><strong class="mat-dup-case-index"><?=h($pair['chave'])?></strong><span class="mat-dup-pill <?=$fechado?'is-done':'is-pending'?>"><?=h(match($tipo){'manter_a'=>'Principal: A','manter_b'=>'Principal: B','nao_duplicado'=>'Assuntos distintos','revisao_manual'=>'Investigar depois',default=>'Pendente'})?></span></div>
   <section class="mat-dup-diagnosis mat-dup-diagnosis-<?=h((string)$exp['nivel'])?>" aria-label="Explicação da suspeita de duplicidade">
     <div class="mat-dup-diagnosis-overline">ANÁLISE DO VÍNCULO · NÃO É VEREDITO AUTOMÁTICO</div>
     <h3><?=h((string)$exp['titulo'])?></h3>
     <p class="mat-dup-diagnosis-main"><?=h((string)$exp['resumo'])?></p>
     <p><strong>POR QUE O SISTEMA MARCOU?</strong> <?=h((string)$exp['origem'])?> A ligação foi recebida nos dados importados, não identificada por uma comparação histórica feita nesta tela.</p>
     <?php if($exp['sem_justificativa']):?>
       <p class="mat-dup-no-reason"><strong>JUSTIFICATIVA DA IMPORTAÇÃO: NÃO INFORMADA.</strong> Sem prova documental do motivo original da associação. Identificar uma referência não comprova identidade de eventos.</p>
     <?php else:?>
       <div class="mat-dup-imported"><strong>JUSTIFICATIVA SALVA NA IMPORTAÇÃO (AINDA NÃO VERIFICADA):</strong><?php foreach($exp['motivos_importados'] as $motivoOriginal):?><p><?=h((string)$motivoOriginal)?></p><?php endforeach;?></div>
     <?php endif;?>
     <div class="mat-dup-clues"><strong>O QUE A COMPARAÇÃO MOSTRA:</strong><ul><?php foreach($exp['pistas'] as $pista):?><li><?=h((string)$pista)?></li><?php endforeach;?></ul></div>
     <?php if(!$pair['vinculo_ativo'] && $tipo==='nao_duplicado'):?><p><strong>VÍNCULO ENCERRADO:</strong> referências cruzadas retiradas após parecer humano documentado. O histórico segue visível abaixo.</p><?php endif;?>
   </section>
   <div class="mat-dup-compare">
    <?php foreach(['a'=>'A','b'=>'B'] as $k=>$letter):$item=$pair[$k]; ?>
    <div class="mat-dup-side <?=$princ===$item['id_extracao']?'mat-dup-primary':''?>">
     <div class="mat-dup-side-label"><span><?=h($item['id_extracao'])?> · REGISTRO <?=$letter?></span><?php if($princ===$item['id_extracao']):?><b>PRINCIPAL</b><?php endif;?></div>
     <h3><?=h((string)$item['titulo_proposto'])?></h3>
     <div class="mat-dup-facts"><span><?=isset($item['ano_validado'])&&$item['ano_validado']!==null?yfmt((int)$item['ano_validado']):(isset($item['ano_original'])&&$item['ano_original']!==null?yfmt((int)$item['ano_original']).' (extraído)':'Data não identificada')?></span><span><?=h((string)$item['categoria'])?></span></div>
     <p class="mat-dup-description"><?=h((string)($item['observacao_metodologica']?:'Consulte o dossiê e suas fontes para determinar se é o mesmo acontecimento.'))?></p>
     <p class="mat-dup-description"><strong>Fontes cadastradas:</strong> <?=h(implode(' · ', $item['fontes']??[]) ?: 'Nenhuma fonte adicional registrada')?></p>
     <a class="mat-dup-open" href="<?=mat_url('rodadas',['id'=>$item['id_extracao']])?>">Abrir dossiê <?=$letter?> ↗</a>
    </div>
    <?php endforeach;?>
   </div>
   <?php if($dec):?><p class="mat-dup-history"><strong>Último parecer:</strong> <?=h((string)($dec['motivo']?:'Não foi incluída observação adicional.'))?></p><?php endif;?>
   <?php if(is_admin()&&$pair['ambos_presentes']&&$pair['vinculo_ativo']):?>
   <form class="mat-dup-decision-form" method="post" action="actions.php"><?=csrf_field()?>
    <input type="hidden" name="action" value="curadoria_par_decisao"><input type="hidden" name="a" value="<?=h($pair['a']['id_extracao'])?>"><input type="hidden" name="b" value="<?=h($pair['b']['id_extracao'])?>">
    <label class="mat-dup-reason"><strong>Seu parecer fundamentado (obrigatório)</strong><input type="text" name="motivo" minlength="40" maxlength="1000" required placeholder="Ex.: Stonehenge e roda de oleiro são eventos técnicos distintos, com funções, contextos e períodos diferentes..."></label>
    <label class="mat-dup-confirm"><input type="checkbox" name="confirmacao_comparacao" value="1" required> Conferi os títulos, os períodos e os documentos disponíveis; esta é uma decisão humana, não automática.</label>
    <div class="mat-dup-actions"><button class="mat-dup-btn is-keep" type="submit" name="decision" value="manter_a">✓ Manter A</button><button class="mat-dup-btn is-keep" type="submit" name="decision" value="manter_b">✓ Manter B</button><button class="mat-dup-btn is-distinct" type="submit" name="decision" value="nao_duplicado">≠ Marcos distintos</button><button class="mat-dup-btn is-later" type="submit" name="decision" value="revisao_manual">◷ Revisar depois</button></div>
   </form>
   <?php elseif(!$pair['ambos_presentes']):?><p class="mat-dup-safety">O outro candidato ainda não está importado. Importe ambos antes de registrar uma decisão.</p>
   <?php elseif(!$pair['vinculo_ativo']):?><p class="mat-dup-safety">Este par foi marcado como distinto; a ligação foi encerrada, mas o parecer permanece para auditoria.</p><?php endif;?>
   <p class="mat-dup-safety">“Marcos distintos” remove apenas a referência cruzada entre os dois IDs, após sua confirmação, mantendo registro em <code>cur_log</code>. “Manter A/B” é parecer de referência e mantém a pendência para consolidação posterior. Nenhuma escolha aprova ou homologa o marco.</p>
 </article>
 <?php endforeach;?>
 <?php if(!$curPairs):?><p>Nenhum par explícito encontrado nos dados da curadoria.</p><?php endif;?>
 <p class="mat-dup-empty" data-dup-empty hidden>Não existem pares nessa categoria.</p>
 <?php endif;?>
</section>
<?php endif;?>
<div class="mat-dup-section-separator">ACERVO PRINCIPAL · OUTRO BANCO, OUTRA CONFERÊNCIA</div>
<div class="lab-alert"><span class="lab-symbol">≋</span><div><strong>ACERVO PRINCIPAL · MAT_OBSERVATORIO</strong><p>Esta tela verifica pares do acervo principal. Os candidatos das rodadas estão em outro banco, <code>mat_temporis_curadoria</code>. <a href="<?=mat_url('rodadas',['filtro'=>'duplicatas'])?>">Ver possíveis duplicatas das rodadas ↗</a></p></div></div>
<?php if(!db()):?>
 <div class="empty-box">A comparação exige conexão com o banco MySQL do acervo.</div>
<?php else:?>
<?php if(is_admin()):?>
<section class="paper-panel mat-dup-scan"><div class="panel-head"><span>VARREDURA CONTROLADA</span><span>AVALIA ATÉ 1.500 REGISTROS</span></div>
 <div class="mat-dup-scan-inner"><div><h2>Buscar pares semelhantes</h2><p>Pesquisa títulos com similaridade de pelo menos 83% e datas com diferença de até cinco anos. É apenas uma sugestão automática; decisões humanas anteriores são preservadas.</p></div><form method="post" action="actions.php"><?=csrf_field()?><input type="hidden" name="action" value="duplicates_scan"><button type="submit" class="btn btn-dark">Reanalisar possíveis pares ↗</button></form></div>
</section>
<?php endif;?>
<section class="paper-panel mat-dup-board">
 <div class="panel-head"><span>PARES LOCALIZADOS</span><span>ÚLTIMOS 120 REGISTROS · LIMITAÇÃO DA CONSULTA</span></div>
 <div class="mat-dup-summary"><div><small>PENDENTES NA LISTA</small><strong><?=short_num($totalPendente)?></strong></div><div><small>COM DECISÃO NA LISTA</small><strong><?=short_num($totalDecidido)?></strong></div><div><small>REGISTROS EXIBIDOS</small><strong><?=short_num(count($pares))?></strong></div></div>
 <div class="mat-dup-tabs" role="group" aria-label="Filtrar pares" data-dup-filters>
  <button type="button" aria-pressed="true" data-dup-filter="pending">Aguardando decisão</button>
  <button type="button" aria-pressed="false" data-dup-filter="decided">Decididos</button>
  <button type="button" aria-pressed="false" data-dup-filter="all">Todos</button>
 </div>
 <div class="mat-dup-pairs" id="mat-dup-pairs">
 <?php foreach($pares as $pair):
    $st=(string)$pair['status'];$dec=(string)($pair['decisao_explicita']??'');
    $decidido=$st==='nao_duplicado' || in_array($dec,['duplicata_manter_a','duplicata_manter_b','duplicata_distintos'],true);
    $rotulo=match($dec){
       'duplicata_manter_a'=>'A definido como principal',
       'duplicata_manter_b'=>'B definido como principal',
       'duplicata_distintos'=>'Assuntos distintos',
       'duplicata_revisao_manual'=>'Aguardando investigação',
       default=>match($st){'nao_duplicado'=>'Assuntos distintos','revisao_manual'=>'Em revisão','pendente'=>'Aguardando decisão',default=>'A conferir'}
    };
    $principal=(int)($pair['principal_id']??0);
 ?>
 <article class="mat-dup-case" id="dup-<?=$pair['id']?>" data-dup-state="<?=$decidido?'decided':'pending'?>">
  <div class="mat-dup-casehead"><div class="mat-dup-case-index">PAR #<?=(int)$pair['id']?> <span>· <?=decimal((float)$pair['similarity']*100,1)?>% DE SIMILARIDADE TEXTUAL</span></div><span class="mat-dup-pill <?=$decidido?'is-done':'is-pending'?>"><?=h($rotulo)?></span></div>
  <div class="mat-dup-compare">
   <?php foreach(['a'=>'A','b'=>'B'] as $letra=>$letraMaiuscula):
       $principalAqui=$principal>0 && $principal===(int)$pair['marco_'.$letra];
   ?>
   <div class="mat-dup-side <?=$principalAqui?'mat-dup-primary':''?>">
    <div class="mat-dup-side-label"><span>REGISTRO <?=$letraMaiuscula?> · #<?=(int)$pair['marco_'.$letra]?></span><?php if($principalAqui):?><b>PRINCIPAL ESCOLHIDO</b><?php endif;?></div>
    <h3><?=h((string)$pair['nome_'.$letra])?></h3>
    <div class="mat-dup-facts"><span><?=yfmt((int)$pair['ano_'.$letra])?></span><span><?=h((string)($pair['categoria_'.$letra]?:'Sem área definida'))?></span><span>Data: <?=h((string)$pair['precisao_'.$letra])?></span></div>
    <p class="mat-dup-description"><?=nl2br(h((string)($pair['descricao_'.$letra]?:'Descrição ainda não registrada.')))?></p>
    <dl class="mat-dup-provenance"><div><dt>Origem / autoria</dt><dd><?=h((string)($pair['criador_'.$letra]?:'Não informado'))?></dd></div><div><dt>Local</dt><dd><?=h((string)($pair['local_'.$letra]?:'Não informado'))?></dd></div><div><dt>Fonte</dt><dd><?=h((string)($pair['fonte_'.$letra]?:'Não vinculada'))?><?php if(!empty($pair['pagina_'.$letra])):?> · p. <?=h((string)$pair['pagina_'.$letra])?><?php endif;?></dd></div></dl>
    <a href="<?=mat_url('marco',['id'=>(int)$pair['marco_'.$letra]])?>" class="mat-dup-open">Abrir dossiê <?=$letraMaiuscula?> ↗</a>
   </div>
   <?php endforeach; ?>
  </div>
  <?php if($decidido):?><p class="mat-dup-history"><strong>Decisão registrada:</strong> <?=h($rotulo)?>. <?=h((string)($pair['motivo_decisao']?:'Sem justificativa adicional.'))?> Os originais continuam disponíveis.</p><?php endif; ?>
  <?php if(is_admin() && in_array($st,['pendente','revisao_manual'],true)):?>
   <form action="actions.php" method="post" class="mat-dup-decision-form">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="duplicate_decision"><input type="hidden" name="id" value="<?=(int)$pair['id']?>">
    <label class="mat-dup-reason">Parecer opcional para o histórico<input type="text" maxlength="1000" name="motivo" placeholder="Ex.: mesmo evento, publicação diferente; tecnologias independentes..."></label>
    <div class="mat-dup-actions">
     <button class="mat-dup-btn is-keep" type="submit" name="decision" value="manter_a">✓ Manter A como principal</button>
     <button class="mat-dup-btn is-keep" type="submit" name="decision" value="manter_b">✓ Manter B como principal</button>
     <button class="mat-dup-btn is-distinct" type="submit" name="decision" value="nao_duplicado">≠ Assuntos distintos</button>
     <button class="mat-dup-btn is-later" type="submit" name="decision" value="revisao_manual">◷ Investigar depois</button>
    </div>
   </form>
  <?php endif; ?>
  <p class="mat-dup-safety">"Manter" define o registro de referência para a conferência do par. Não mescla, não exclui, não altera notas e não homologa automaticamente.</p>
 </article>
 <?php endforeach; ?>
 <?php if(!$pares):?><p class="mat-dup-empty">Nenhum par localizado. Se quiser, execute a varredura acima para procurar semelhanças.</p><?php endif; ?>
 <p class="mat-dup-empty" data-dup-empty hidden>Nenhum par nesta categoria entre os registros carregados.</p>
 </div>
</section>
<?php endif; ?>
