<?php
declare(strict_types=1);
require_once __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/PontuacaoAcervoIA.php';
if(!is_admin()){header('Location: index.php?page=login');exit;}
if(!db()){http_response_code(503);exit('MAT: ligue o MySQL para trabalhar com dados reais.');}
$markers=all_markers();
$evaluations=revised_all_evaluations();
$sourceStats=[];
try {
    foreach(db()->query('SELECT marco_id, COUNT(*) n, SUM(CASE WHEN trecho IS NOT NULL AND CHAR_LENGTH(TRIM(trecho)) >= 20 THEN 1 ELSE 0 END) c FROM marco_fontes GROUP BY marco_id') as $st){
        $sourceStats[(int)$st['marco_id']]=['n'=>(int)$st['n'],'c'=>(int)$st['c']];
    }
}catch(Throwable $err){error_log($err->getMessage());}
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$selected=null;
foreach($markers as $row)if((int)$row['id']===$id){$selected=$row;break;}
$proposalRecord=$selected?PontuacaoAcervoIA::load($id):null;
$proposal=$proposalRecord['proposta']??null;
$existing=$selected?($evaluations[$id]??null):null;
$prefills=$selected?PontuacaoAcervoIA::prefills($existing,$proposal):[];
$fmt=static fn($v,int $digits=4)=>number_format((float)$v,$digits,',','.');
$stats=['total'=>count($markers),'confirmados'=>0,'com_trecho'=>0,'sem_trecho'=>0,'com_ia'=>0,'sem_rubrica'=>0];
foreach($markers as $m){
    $mid=(int)$m['id'];
    if(($evaluations[$mid]['estado']??'')==='confirmada')$stats['confirmados']++;
    if(($sourceStats[$mid]['c']??0)>0)$stats['com_trecho']++;else $stats['sem_trecho']++;
    if((PontuacaoAcervoIA::load($mid)['status']??'')==='concluido')$stats['com_ia']++;
    if(empty($evaluations[$mid]))$stats['sem_rubrica']++;
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#101c1e"><title>MAT — Pontuação assistida do acervo</title>
<link rel="icon" href="assets/insignia.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css">
<style>
.mat-score{max-width:1140px;margin:0 auto;padding:40px 22px 90px;font-family:Georgia,serif;color:#162d30}.mat-score h1{font-size:clamp(34px,5vw,60px);font-weight:400;line-height:1.15}.mat-score h2{font-size:26px;margin-top:27px}.mat-score a{color:#995d3f}.mat-score p{line-height:1.6}.mat-score .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:12px;margin:20px 0}.mat-score .stat,.mat-score .panel{border:1px solid #ddd1c2;background:#fffcf6;padding:19px}.mat-score .stat strong{display:block;font-size:30px;margin:8px 0}.mat-score .muted{font:13px/1.5 monospace;color:#6a625a}.mat-score .notice{background:#fff0df;border-left:4px solid #a36e4d;padding:15px;margin:20px 0}.mat-score .tablebox{overflow:auto}.mat-score table{border-collapse:collapse;width:100%;min-width:760px;font-size:14px}.mat-score th,.mat-score td{border-bottom:1px solid #e4d9ca;padding:10px;text-align:left;vertical-align:top}.mat-score input,.mat-score select,.mat-score textarea{width:100%;max-width:100%;box-sizing:border-box;font:15px Georgia,serif;padding:10px;border:1px solid #c6bba9;background:#fffdfa;color:#20343a}.mat-score textarea{min-height:85px}.mat-score label{display:block}.mat-score .formgrid{display:grid;grid-template-columns:minmax(180px,1fr) minmax(250px,2fr);gap:14px;margin:12px 0;padding-bottom:14px;border-bottom:1px solid #ddd1c2}.mat-score .btn{padding:12px 17px;background:#162d30;color:white;text-decoration:none;border:0;display:inline-block;cursor:pointer}.mat-score .fieldlabel{font-weight:bold}.mat-score .quicklinks{display:flex;flex-wrap:wrap;gap:12px}.mat-score details{margin:12px 0}.mat-score .note{font-size:13px;color:#695e55}.mat-score mark{background:#f7e9cc}
@media(max-width:720px){.mat-score .formgrid{grid-template-columns:1fr}.mat-score{padding:22px 13px}}
</style></head><body style="background:#f5f0e8"><main class="mat-score">
<nav class="quicklinks"><a href="index.php?page=acervo">← Acervo</a><a href="triagem_300_reais.php">Triagem dos 300</a><a href="index.php?page=temporis">Índice Têmporis</a><a href="experimento_temporis.php">Backtesting real</a></nav>
<p class="muted">MAT / PESQUISA DOCUMENTAL / PONTUAÇÃO ASSISTIDA</p><h1>Pontuar os <em>marcos do acervo.</em></h1>
<p>Fila dos marcos cadastrados no seu MySQL. O Qwen propõe apenas notas sustentadas por trechos já vinculados ao dossiê. Você revisa e salva como <strong>rascunho</strong>; a confirmação científica é uma etapa separada.</p>
<div class="stats"><div class="stat">No acervo<strong><?=$stats['total']?></strong></div><div class="stat">Com trecho documental<strong><?=$stats['com_trecho']?></strong></div><div class="stat">Propostas do Qwen<strong><?=$stats['com_ia']?></strong></div><div class="stat">Rubricas confirmadas<strong><?=$stats['confirmados']?></strong></div></div>
<div class="notice"><strong>Não é pontuação automática aprovada.</strong> Nenhuma sugestão é gravada sem uma ação sua. A correspondência literal de uma citação não prova que a nota está correta. Sem fonte vinculada e evidência examinável, o sistema não solicita pontuação ao Qwen.</div>
<section class="panel"><h2>Como começar</h2><p>Primeiro vincule trechos das fontes aos marcos que estão sem documentação. Depois execute no PowerShell do VS Code:</p>
<pre style="white-space:pre-wrap;overflow-wrap:anywhere">&amp; "D:\Faculdade nero\xampp\php\php.exe" ".\MAT_Refinado_0_1\cli\pontuar_acervo_ia.php" --modo=ia --limite=2</pre>
<p>Depois atualize esta página, abra o marco analisado na fila e revise as notas sugeridas. O processo pode ser repetido; ele pula marcos já analisados e não altera avaliações confirmadas.</p><p class="muted">Saída privada: <?=h(PontuacaoAcervoIA::directory())?></p></section>
<?php if($selected):$sc=$sourceStats[$id]??['n'=>0,'c'=>0];?>
<section class="panel" id="avaliar" style="margin-top:20px"><p><a href="fila_pontuacao.php">← Voltar à fila</a></p><h2><?=h($selected['titulo'])?></h2>
<p>Marco #<?=$id?> · Ano: <?=h((string)$selected['ano'])?> · Situação: <?=h((string)$selected['status'])?> · Fontes: <?=$sc['n']?> · Fontes com trecho: <?=$sc['c']?></p>
<div class="quicklinks"><a href="<?=mat_url('fontes',['marco_id'=>$id])?>">Vincular fonte e trecho</a><a href="<?=mat_url('triagem',['id'=>$id])?>">Revisar escopo</a><a href="<?=mat_url('avaliacao',['id'=>$id])?>">Avaliação oficial dos 11 critérios</a></div>
<?php if(($existing['estado']??'')==='confirmada'):?><div class="notice">Este marco já tem avaliação confirmada. Não vamos sobrescrevê-la a partir da IA; utilize o formulário oficial para qualquer revisão consciente.</div>
<?php elseif(!$proposal):?><div class="notice"><?=$sc['c']<1?'Faltam trechos documentais: vincule uma fonte com um trecho de pelo menos 20 caracteres.':'Este marco ainda não possui proposta concluída do Qwen. Execute o comando acima e atualize a página.'?></div>
<?php else:?>
<p><strong><?=$proposal['notas_citadas']?>/11 notas com citação localizada.</strong>
<?php if(is_array($proposal['peso_provisorio_nao_validado']??null)):?> Peso matemático provisório: <strong><?=$fmt($proposal['peso_provisorio_nao_validado']['w'],6)?></strong> (não entra na Têmporis).<?php endif;?></p>
<?php if($proposal['riscos']??null):?><details><summary>Ressalvas sinalizadas pela IA</summary><ul><?php foreach($proposal['riscos'] as $risk):?><li><?=h((string)$risk)?></li><?php endforeach;?></ul></details><?php endif;?>
<p class="note">As lacunas continuam em branco. Confira as fontes originais e não aceite uma nota apenas por parecer plausível.</p>
<form action="actions.php" method="post"><?=csrf_field()?><input type="hidden" name="action" value="save_revised"><input type="hidden" name="id" value="<?=$id?>">
<?php foreach([['Impacto',RevisedMethodology::IMPACT],['Confiabilidade documental',RevisedMethodology::CONFIDENCE],['Fundação tecnológica',RevisedMethodology::FOUNDATION]] as [$heading,$group]):?><h3><?=h($heading)?></h3>
<?php foreach($group as $field=>$label):$v=$prefills[$field];?>
<div class="formgrid"><div><label class="fieldlabel" for="score_<?=h($field)?>"><?=h($label)?></label><p class="note"><?=h(RevisedMethodology::GUIDANCE[$field]??'')?></p><select id="score_<?=h($field)?>" name="<?=h($field)?>"><option value="">— Sem evidência —</option><?php foreach([0,1,2,3] as $n):?><option value="<?=$n?>" <?=($v['nota']!==null && (int)$v['nota']===$n)?'selected':''?>><?=$n?></option><?php endforeach;?></select><p class="muted">Origem do preenchimento: <?=h($v['origem'])?></p></div><div><label for="ev_<?=h($field)?>">Evidência e justificativa — conferir</label><textarea name="<?=h('evidencia_'.$field)?>" id="ev_<?=h($field)?>" maxlength="3000"><?=h($v['evidencia'])?></textarea></div></div>
<?php endforeach;endforeach; ?>
<p class="notice"><strong>Salvar somente rascunho.</strong> As notas sugeridas ainda não foram confirmadas e podem estar erradas. Você poderá corrigir e confirmar depois em “Avaliação oficial dos 11 critérios”.</p>
<button class="btn" type="submit">Salvar rascunho revisado ↗</button>
</form>
<?php endif;?></section><?php endif;?>
<section class="panel" style="margin-top:20px"><h2>Fila atual do acervo</h2><div class="tablebox"><table><thead><tr><th>ID</th><th>Ano</th><th>Marco</th><th>Fonte com trecho</th><th>IA</th><th>Rubrica</th><th>Ação</th></tr></thead><tbody>
<?php foreach($markers as $m):$mid=(int)$m['id'];$st=$sourceStats[$mid]??['c'=>0];$ap=PontuacaoAcervoIA::load($mid);$ev=$evaluations[$mid]??null;?><tr><td><?=$mid?></td><td><?=h((string)$m['ano'])?></td><td><?=h($m['titulo'])?></td><td><?=$st['c']>0?$st['c'].' trecho(s)':'Pendente'?></td><td><?=($ap['status']??'')==='concluido'?h((string)($ap['proposta']['notas_citadas']??0)).'/11':h((string)($ap['status']??'não analisado'))?></td><td><?=h((string)($ev['estado']??'sem avaliação'))?></td><td><a href="?id=<?=$mid?>#avaliar">Abrir ↗</a></td></tr><?php endforeach;?></tbody></table></div></section>
</main></body></html>
