<?php
if (!defined('MAT_ROOT')) { http_response_code(403); exit; }
require_once MAT_ROOT.'/app/CuradoriaRodadas.php';
$curDb=CuradoriaRodadas::connect(); $progress=null;
if ($curDb) { try { $progress=CuradoriaRodadas::progressSnapshot($curDb, is_admin()); }
    catch (Throwable $e) { error_log('MAT: andamento documental: '.$e->getMessage()); } }
$percent=$progress ? min(100,max(0,(float)$progress['progresso_percentual'])) : 0;
?>
<header class="page-heading mat-cur-heading">
  <div class="section-overline">MAT / CADERNO DE PESQUISA · FONTE ÚNICA</div>
  <h1>O progresso da <em>pesquisa.</em></h1>
  <p>Da extração histórica à validação científica: acompanhe os candidatos, os dossiês investigados e as decisões humanas, sem confundir quantidade cadastrada com evidência comprovada.</p>
</header>
<?php if (!$progress): ?>
<section class="paper-panel mat-cur-empty" role="alert"><div class="panel-head"><span>PAINEL DOCUMENTAL</span><span>SEM DADOS SIMULADOS</span></div>
<h2>Banco de curadoria não encontrado.</h2>
<p>Verifique a conexão com <strong><?=h(CuradoriaRodadas::databaseName())?></strong>. Esta página não usa números fixos nem arquivos locais para substituir registros do MySQL.</p></section>
<?php else: ?>
<div class="mat-cur-statusbar"><span class="mat-cur-statusdot"></span> DADOS DA PESQUISA · <?=h(CuradoriaRodadas::databaseName())?> <span class="mat-cur-statusright">SOMENTE LEITURA · ATUALIZADO AO ABRIR</span></div>
<section class="paper-panel mat-progress-hero" aria-labelledby="mat-progress-title">
  <div class="panel-head"><span>ESTADO DA CURADORIA</span><span>CONTAGEM VERIFICÁVEL · MYSQL</span></div>
  <div class="mat-progress-hero-top">
   <div><p class="mat-progress-kicker">CANDIDATOS PESQUISADOS</p><div class="mat-progress-main-number" id="mat-progress-title"><?=short_num($progress['pesquisados'])?><small> / <?=short_num($progress['total'])?></small></div><p>Dos candidatos cadastrados no banco, esta é a quantidade que possui pesquisa preliminar documentada.</p></div>
   <div class="mat-progress-hero-percent"><strong><?=decimal($percent,1)?>%</strong><span>cobertura documental do catálogo</span></div>
  </div>
  <div class="mat-progress-bar" role="progressbar" aria-label="Candidatos pesquisados" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=h((string)$percent)?>"><span style="width:<?=h((string)$percent)?>%"></span></div>
  <div class="mat-progress-note"><span><?=short_num($progress['em_analise'])?> ainda não pesquisados</span><span>Base: <?=short_num($progress['total'])?> IDs cadastrados</span></div>
  <?php if ($progress['total'] < 300): ?><div class="mat-progress-warning"><strong>Catálogo parcial.</strong> O banco contém <?=short_num($progress['total'])?> registros. Para apresentar “300 candidatos / 20 pesquisados”, primeiro cadastre os candidatos restantes como <em>não iniciados</em>, preservando os dossiês já documentados. Não exibimos 300 como dado do banco antes disso.</div><?php endif; ?>
</section>
<div class="mat-progress-stat-grid">
 <div class="mat-progress-stat"><span>CATALOGADOS</span><strong><?=short_num($progress['total'])?></strong><small>IDs únicos na base de curadoria</small></div>
 <div class="mat-progress-stat"><span>PESQUISADOS</span><strong><?=short_num($progress['pesquisados'])?></strong><small>Com documentação proposta</small></div>
 <div class="mat-progress-stat"><span>COM AS 11 NOTAS</span><strong><?=short_num($progress['propostas_11'])?></strong><small>Preenchimento completo, não aprovação</small></div>
 <div class="mat-progress-stat"><span>REVISADOS POR HUMANO</span><strong><?=short_num($progress['revisados_humanos'])?></strong><small>Revisão explicitamente registrada</small></div>
 <div class="mat-progress-stat"><span>HOMOLOGADOS</span><strong><?=short_num($progress['homologados'])?></strong><small>Elegíveis somente após aprovação</small></div>
</div>
<section class="paper-panel mat-progress-section mat-progress-pipeline">
 <div class="panel-head"><span>ETAPAS DOCUMENTAIS</span><span>NÃO SE TRATA DE PREVISÃO AUTOMÁTICA</span></div>
 <h2>O que já foi realizado</h2>
 <div class="mat-progress-stages">
 <?php $steps=[['01','Candidatos catalogados',$progress['total'],'Identificação e trecho original'],['02','Pesquisa histórica',$progress['pesquisados'],'Fontes e interpretação documentadas'],['03','Onze critérios propostos',$progress['propostas_11'],'Dossiês com todas as rubricas pontuadas'],['04','Revisão independente',$progress['revisados_humanos'],'Etapa humana distinta da pesquisa'],['05','Homologação científica',$progress['homologados'],'Só após validação explícita']];foreach($steps as [$num,$name,$val,$sub]): ?>
 <div class="mat-progress-stage"><span class="mat-progress-stage-index"><?=h($num)?></span><div><b><?=h($name)?></b><small><?=h($sub)?></small></div><strong><?=short_num($val)?></strong></div>
 <?php endforeach; ?>
 </div>
 <div class="mat-progress-explain">Foram propostas <strong><?=short_num($progress['notas_propostas'])?></strong> notas, com <strong><?=short_num($progress['criterios_sem_evidencia'])?></strong> rubricas registradas sem evidência suficiente. Cada nota pode ser aberta com justificativa, fontes e memória de cálculo no dossiê individual.</div>
</section>
<div class="mat-progress-bottom-grid">
 <section class="paper-panel mat-progress-section"><div class="panel-head"><span>EVOLUÇÃO DAS RODADAS</span><span><?=count($progress['etapas'])?> REGISTROS DE RODADAS</span></div><h2>Pesquisa por lotes de dez</h2>
  <p>As rodadas só entram na contagem quando aparecem no banco; uma rodada planejada não significa que seus marcos já foram pesquisados.</p>
  <div class="mat-progress-rounds">
  <?php foreach($progress['etapas'] as $round):if((int)$round['numero']===0)continue; ?>
   <div class="mat-progress-round"><div><b>Rodada <?=str_pad((string)$round['numero'],2,'0',STR_PAD_LEFT)?></b><small><?=h(CuradoriaRodadas::label((string)$round['estado']))?></small></div><span><?=short_num((int)$round['pesquisados'])?> / <?=short_num((int)$round['total'])?> pesquisados</span></div>
  <?php endforeach; ?>
  </div><?php if(is_admin()): ?><a href="<?=mat_url('rodadas')?>" class="mat-progress-more">Explorar todos os dossiês ↗</a><?php endif; ?>
 </section>
 <section class="paper-panel mat-progress-section"><div class="panel-head"><span>TRILHA DE AUDITORIA</span><span>EVENTOS SALVOS NO MYSQL</span></div><h2>Atividade registrada</h2>
 <?php if(!is_admin()): ?><p>O histórico detalhado e os registros individuais estão disponíveis apenas na área do pesquisador. Os indicadores e as rodadas acima são públicos.</p>
 <?php elseif(!$progress['atividade']):?><p>Nenhum evento foi registrado em cur_log até o momento.</p><?php else: ?><div class="mat-progress-activity">
 <?php foreach($progress['atividade'] as $event): ?><div><small><?=h((string)($event['data_acao']??''))?></small><b><?=h((string)($event['acao']??'Registro'))?></b><span><?=h((string)($event['descricao']??''))?></span><?php if(preg_match('/^MCD-[0-9]{5}$/D',(string)($event['id_extracao']??''))): ?><a href="<?=mat_url('rodadas',['id'=>$event['id_extracao']])?>">Abrir <?=h($event['id_extracao'])?> ↗</a><?php endif; ?></div><?php endforeach; ?></div><?php endif; ?>
 </section>
</div>
<section class="mat-progress-footer-note"><strong>Critério de apresentação científica:</strong> o sistema separa identificação, investigação, pontuação e validação. O avanço é mensurado pela documentação disponível no banco e não pelo resultado sugerido por uma IA. Nenhum candidato passa a integrar a série Têmporis apenas por ter recebido notas.</section>
<?php endif; ?>
