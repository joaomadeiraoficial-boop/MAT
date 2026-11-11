<?php
declare(strict_types=1);
/**
 * MAT - Triagem assistida por snapshot externo.
 * Não altera cur_candidatos, cur_criterios, cur_fontes ou decisões de homologação.
 * Requer a importação manual de INSTALAR_TRIAGEM_ASSISTIDA.sql.
 */
require __DIR__.'/app/bootstrap.php';
require_once __DIR__.'/app/CuradoriaRodadas.php';
if (!is_admin()) { header('Location: index.php?page=login'); exit; }
$pdo=CuradoriaRodadas::connect();
if (!$pdo) { http_response_code(503);exit('Banco de curadoria indisponível. Faça o backup e confira a conexão.'); }
try { $pdo->query('SELECT 1 FROM cur_triagem_assistida LIMIT 1'); }
catch (Throwable $e) { http_response_code(503);exit('Importe primeiro INSTALAR_TRIAGEM_ASSISTIDA.sql no banco mat_temporis_curadoria.'); }

$allowed=['PENDENTE'=>'Pendente de ação','PESQUISAR_FONTES'=>'Pesquisar fontes','REVER_DATA'=>'Rever data','REVER_ESCOPO'=>'Rever escopo','REVER_DUPLICATA'=>'Rever duplicata','PARECER_REGISTRADO'=>'Parecer documental registrado'];
$flash='';$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_csrf();
    $id=substr(trim((string)($_POST['id_extracao']??'')),0,12);
    $status=(string)($_POST['situacao_triagem']??'');
    $note=trim((string)($_POST['parecer_pesquisador']??''));
    $version=filter_var($_POST['versao']??null,FILTER_VALIDATE_INT);
    if (!CuradoriaRodadas::validId($id) || !isset($allowed[$status]) || $version===false || $version<0) {
        $error='Dados da decisão inválidos.';
    } elseif ($status!=='PENDENTE' && (function_exists('mb_strlen') ? mb_strlen($note,'UTF-8') : strlen($note))<40) {
        $error='Escreva um parecer de ao menos 40 caracteres antes de registrar esta etapa.';
    } elseif ((function_exists('mb_strlen') ? mb_strlen($note,'UTF-8') : strlen($note))>3000) {
        $error='Parecer muito longo (máximo 3.000 caracteres).';
    } else {
        try {
            $pdo->beginTransaction();
            $read=$pdo->prepare('SELECT situacao_triagem,versao FROM cur_triagem_assistida WHERE id_extracao=? FOR UPDATE');
            $read->execute([$id]);$before=$read->fetch(PDO::FETCH_ASSOC);
            if (!$before || (int)$before['versao']!==$version) {
                $pdo->rollBack();$error='O registro foi alterado por outra operação. Reabra a ficha e confira.';
            } else {
                $update=$pdo->prepare('UPDATE cur_triagem_assistida SET situacao_triagem=?,parecer_pesquisador=?,versao=versao+1,modificado_em=NOW() WHERE id_extracao=? AND versao=?');
                $update->execute([$status,($note!==''?$note:null),$id,$version]);
                if ($update->rowCount()!==1) throw new RuntimeException('Atualização concorrente detectada.');
                $ins=$pdo->prepare('INSERT INTO cur_triagem_assistida_log (id_extracao,status_anterior,status_novo,parecer) VALUES (?,?,?,?)');
                $ins->execute([$id,(string)$before['situacao_triagem'],$status,$note]);
                $pdo->commit();
                header('Location: fila_triagem_assistida.php?'.http_build_query(['id'=>$id,'gravado'=>'1']));exit;
            }
        } catch(Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('MAT triagem assistida: '.$e->getMessage());
            $error='Não foi possível registrar o parecer. Tente novamente.';
        }
    }
}
if (isset($_GET['gravado']))$flash='Parecer salvo somente na triagem assistida. Nenhum marco foi aprovado ou homologado.';
$trilha=substr((string)($_GET['trilha']??''),0,48);
$estado=substr((string)($_GET['estado']??''),0,35);
$busca=substr(trim((string)($_GET['busca']??'')),0,80);
$page=max(1,min(1000,filter_var($_GET['pg']??1,FILTER_VALIDATE_INT)?:1));
$id=substr(trim((string)($_GET['id']??'')),0,12);
if ($estado!=='' && !isset($allowed[$estado]))$estado='';
$selectTrilhas=$pdo->query('SELECT trilha,COUNT(*) total FROM cur_triagem_assistida GROUP BY trilha ORDER BY total DESC')->fetchAll(PDO::FETCH_ASSOC);
$legitTrilhas=array_column($selectTrilhas,'trilha');if ($trilha!==''&&!in_array($trilha,$legitTrilhas,true))$trilha='';
$counts=['total'=>0,'pendentes'=>0,'pareceres'=>0];
foreach($pdo->query('SELECT situacao_triagem,COUNT(*) total FROM cur_triagem_assistida GROUP BY situacao_triagem') as $rr) {
    $counts['total']+=(int)$rr['total'];
    if($rr['situacao_triagem']==='PENDENTE')$counts['pendentes']+=(int)$rr['total'];
    if($rr['situacao_triagem']==='PARECER_REGISTRADO')$counts['pareceres']+=(int)$rr['total'];
}
$where=[];$params=[];
if($trilha!==''){$where[]='t.trilha=?';$params[]=$trilha;}
if($estado!==''){$where[]='t.situacao_triagem=?';$params[]=$estado;}
if($busca!==''){$where[]='(c.id_extracao LIKE ? OR c.titulo_proposto LIKE ?)';$params[]='%'.$busca.'%';$params[]='%'.$busca.'%';}
$wc=$where?' WHERE '.implode(' AND ',$where):'';
$sqlBase=' FROM cur_triagem_assistida t JOIN cur_candidatos c ON c.id_extracao=t.id_extracao';
$count=$pdo->prepare('SELECT COUNT(*)'.$sqlBase.$wc);$count->execute($params);$total=(int)$count->fetchColumn();
$offset=($page-1)*30;
$q=$pdo->prepare('SELECT t.id_extracao,t.trilha,t.notas_snapshot,t.pendencias_snapshot,t.ano_validado_snapshot,t.situacao_triagem,t.versao,c.titulo_proposto,c.ano_validado,c.estado_revisao_humana'.$sqlBase.$wc.' ORDER BY CASE WHEN t.situacao_triagem=\'PENDENTE\' THEN 0 ELSE 1 END, CASE t.trilha WHEN \'AUDITAR_PRIORITARIO\' THEN 0 WHEN \'COMPLETAR_POUCAS_NOTAS\' THEN 1 WHEN \'CONFERIR_DATA_FONTES\' THEN 2 WHEN \'TRIAR_DUPLICATA\' THEN 3 WHEN \'TRIAR_ESCOPO\' THEN 4 ELSE 5 END, t.id_extracao LIMIT 30 OFFSET '.(int)$offset);
$q->execute($params);$list=$q->fetchAll(PDO::FETCH_ASSOC);
if ($id===''&&$list)$id=(string)$list[0]['id_extracao'];
$selected=null;$sources=[];$criteria=[];$history=[];
if($id!==''&&CuradoriaRodadas::validId($id)){
  $st=$pdo->prepare('SELECT t.*,c.titulo_proposto,c.ano_original,c.ano_validado,c.periodo_historico,c.pagina_livro,c.pagina_pdf,c.pendencias_json,c.duplicatas_json,c.estado_revisao_humana,c.homologado FROM cur_triagem_assistida t JOIN cur_candidatos c ON c.id_extracao=t.id_extracao WHERE t.id_extracao=?');$st->execute([$id]);$selected=$st->fetch(PDO::FETCH_ASSOC)?:null;
  if($selected){
    $s=$pdo->prepare('SELECT fonte_id,titulo,autor_instituicao,url FROM cur_fontes WHERE id_extracao=? ORDER BY fonte_id LIMIT 30');$s->execute([$id]);$sources=$s->fetchAll(PDO::FETCH_ASSOC);
    $s=$pdo->prepare('SELECT codigo,nota,situacao,o_que_falta FROM cur_criterios WHERE id_extracao=? ORDER BY codigo');$s->execute([$id]);$criteria=$s->fetchAll(PDO::FETCH_ASSOC);
    $s=$pdo->prepare('SELECT status_anterior,status_novo,parecer,realizado_em FROM cur_triagem_assistida_log WHERE id_extracao=? ORDER BY id DESC LIMIT 10');$s->execute([$id]);$history=$s->fetchAll(PDO::FETCH_ASSOC);
  }
}
function self_url(array $q=[]):string{return 'fila_triagem_assistida.php?'.http_build_query($q);}
$maxpage=max(1,(int)ceil($total/30));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>MAT — Triagem assistida</title><link rel="icon" href="assets/insignia.svg" type="image/svg+xml"><style>
:root{color-scheme:light}body{font-family:Georgia,serif;background:#f5f0e7;color:#172c2d;margin:0}header{background:#122225;color:#e9dcc9;padding:20px max(24px,4vw)}header strong{font-size:30px;font-weight:normal}header a{color:#e9c9a6}main{margin:auto;max-width:1500px;padding:26px 28px 80px}.muted,small{font:12px/1.5 ui-monospace,monospace;color:#71685e}h1{font-weight:normal;font-size:clamp(32px,4vw,58px);margin:12px 0}.desc{font-size:17px;line-height:1.6;max-width:1050px}a{color:#a06746;text-decoration:none}a:hover{text-decoration:underline}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:26px 0}.card,section{border:1px solid #d7c9b9;background:#fffaf3;padding:18px}.card span{font-size:32px;display:block}.notice{padding:14px;background:#fbeacd;border-left:4px solid #9e6d3c;margin:14px 0}.success{background:#e5eee9;border-color:#255648}.filters{display:grid;grid-template-columns:1.2fr 1.2fr 2fr auto;gap:9px;margin:20px 0}input,textarea,select,button{font:15px Georgia,serif;color:#213032;background:white;border:1px solid #b5ab9b;padding:10px;width:100%;box-sizing:border-box}textarea{min-height:120px}button{background:#162f32;color:#fff;cursor:pointer;width:auto;padding:11px 20px}.layout{display:grid;grid-template-columns:minmax(300px,0.95fr) minmax(400px,1.4fr);gap:14px}.item{display:block;border-bottom:1px solid #ded2c3;padding:12px;color:#25363b}.item:hover,.item.active{background:#efe8d9;text-decoration:none}.item strong{font-weight:normal;font-size:16px}.item small{display:block;margin:4px 0}.fields{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin:18px 0}.field{border:1px solid #ddd2c5;padding:10px}.field strong{display:block;font-size:22px}.document{margin:16px 0;border-top:1px solid #ddd4c8;padding-top:12px}.document strong{display:block}.subtle{color:#715d4e;font-size:14px}.links{display:flex;gap:12px;flex-wrap:wrap;margin:10px 0 20px}details{margin:14px 0}li{margin:6px 0;overflow-wrap:anywhere}.pagination{display:flex;justify-content:space-between;margin:16px 0}form label{display:block;margin:12px 0 6px;font-weight:bold}.note{font:12px/1.5 ui-monospace,monospace;color:#665c53}h2{font-weight:normal;font-size:27px}.page-ref{font-family:ui-monospace,monospace;font-size:13px}
@media(max-width:920px){.layout{grid-template-columns:1fr}.filters{grid-template-columns:1fr}main{padding:18px}}
</style></head><body>
<header><strong>MAT</strong> · Triagem assistida do Índice Têmporis <span style="float:right"><a href="index.php?page=fila_rapida">Fila oficial ↗</a>　<a href="index.php?page=andamento">Progresso ↗</a></span></header>
<main><div class="muted">PESQUISA E CURADORIA / BANCO REAL / REVISÃO ASSISTIDA</div><h1>Revisar sem perder o rigor.</h1>
<p class="desc">Esta fila foi gerada a partir de um retrato dos 750 dossiês. As trilhas são <strong>recomendações operacionais</strong> calculadas a partir dos registros existentes. Registrar um parecer aqui <strong>não altera avaliações, aprovações, homologações ou o Índice Têmporis.</strong></p>
<div class="cards"><div class="card"><small>Dossiês carregados na fila</small><span><?=$counts['total']?></span></div><div class="card"><small>Aguardando triagem assistida</small><span><?=$counts['pendentes']?></span></div><div class="card"><small>Parecer documental registrado</small><span><?=$counts['pareceres']?></span></div><div class="card"><small>Resultados do filtro</small><span><?=$total?></span></div></div>
<?php if($flash):?><div class="notice success"><?=h($flash)?></div><?php endif;?>
<?php if($error):?><div class="notice" role="alert"><?=h($error)?></div><?php endif;?>
<form method="get" class="filters"><select name="trilha"><option value="">Todas as trilhas</option><?php foreach($selectTrilhas as $opt):?><option value="<?=h($opt['trilha'])?>" <?=$trilha===$opt['trilha']?'selected':''?>><?=h($opt['trilha'])?> (<?=$opt['total']?>)</option><?php endforeach;?></select><select name="estado"><option value="">Todos os estados</option><?php foreach($allowed as $v=>$lab):?><option value="<?=h($v)?>" <?=$estado===$v?'selected':''?>><?=h($lab)?></option><?php endforeach;?></select><input type="search" name="busca" value="<?=h($busca)?>" placeholder="ID ou nome do marco"><button type="submit">Filtrar</button></form>
<div class="layout"><section><h2>Fila de candidatos</h2><p class="note">Ordem privilegia dossiês mais próximos da conferência. O filtro não elimina nenhum registro.</p>
<?php foreach($list as $r):?><a class="item <?=$id===$r['id_extracao']?'active':''?>" href="<?=h(self_url(['trilha'=>$trilha,'estado'=>$estado,'busca'=>$busca,'pg'=>$page,'id'=>$r['id_extracao']]))?>"><small><?=h($r['id_extracao'])?> · <?=h($r['trilha'])?> · <?=h($r['situacao_triagem'])?></small><strong><?=h($r['titulo_proposto'])?></strong><small><?=h((string)$r['notas_snapshot'])?>/11 notas · <?=h((string)$r['pendencias_snapshot'])?> pendência(s)</small></a><?php endforeach;?>
<?php if(!$list):?><p>Nenhum resultado para este filtro.</p><?php endif;?>
<div class="pagination"><span><?php if($page>1):?><a href="<?=h(self_url(['trilha'=>$trilha,'estado'=>$estado,'busca'=>$busca,'pg'=>$page-1]))?>">← Anterior</a><?php endif;?></span><span><?=$page?> / <?=$maxpage?></span><span><?php if($page<$maxpage):?><a href="<?=h(self_url(['trilha'=>$trilha,'estado'=>$estado,'busca'=>$busca,'pg'=>$page+1]))?>">Próxima →</a><?php endif;?></span></div></section>
<section><?php if(!$selected):?><h2>Selecione um candidato</h2><?php else:?>
<small>FICHA / <?=h($selected['id_extracao'])?> · RASTREABILIDADE</small><h2><?=h($selected['titulo_proposto'])?></h2>
<div class="fields"><div class="field"><small>Notas</small><strong><?=h((string)$selected['notas_snapshot'])?>/11</strong></div><div class="field"><small>Ano original</small><strong><?=h((string)($selected['ano_original']??'—'))?></strong></div><div class="field"><small>Ano validado no banco</small><strong><?=h((string)($selected['ano_validado']??'—'))?></strong></div><div class="field"><small>Estado oficial</small><strong style="font-size:16px;margin-top:10px"><?=h((string)$selected['estado_revisao_humana'])?></strong></div></div>
<div class="notice"><strong>Próxima ação recomendada</strong><p><?=h($selected['acao_sugerida'])?></p><small>Trilha: <?=h($selected['trilha'])?>. O status é uma classificação automática, não uma avaliação científica.</small></div>
<p class="page-ref">Livro-base p. <?=h((string)($selected['pagina_livro']??'—'))?> · Arquivo PDF p. <?=h((string)($selected['pagina_pdf']??'—'))?> · Período: <?=h((string)($selected['periodo_historico']??'—'))?></p>
<div class="links"><a href="<?=h(mat_url('fila_rapida',['filtro'=>'todos','id'=>$selected['id_extracao']]))?>">Abrir na curadoria oficial ↗</a><a href="<?=h(mat_url('rodadas',['id'=>$selected['id_extracao']]))?>">Ver dossiê completo ↗</a></div>
<details><summary>Fontes catalogadas (<?=count($sources)?>)</summary><?php foreach($sources as $src):?><div class="document"><strong><?=h((string)$src['fonte_id'])?> — <?=h((string)($src['titulo']??''))?></strong><p class="subtle"><?=h((string)($src['autor_instituicao']??''))?></p><?php if(preg_match('~^https?://~i',(string)($src['url']??''))):?><a target="_blank" rel="noopener noreferrer" href="<?=h((string)$src['url'])?>">Abrir referência externa ↗</a><?php endif;?></div><?php endforeach;?></details>
<details><summary>11 rubricas e lacunas</summary><ul><?php foreach($criteria as $cr):?><li><strong><?=h($cr['codigo'])?></strong>: <?=($cr['nota']===null?'SEM_EVIDENCIA':h((string)$cr['nota']))?> — <?=h((string)($cr['o_que_falta']??''))?></li><?php endforeach;?></ul></details>
<form method="post" action="fila_triagem_assistida.php" autocomplete="off"><?=csrf_field()?><input type="hidden" name="id_extracao" value="<?=h($selected['id_extracao'])?>"><input type="hidden" name="versao" value="<?=h((string)$selected['versao'])?>">
<label for="estado">Próximo encaminhamento documental</label><select name="situacao_triagem" id="estado"><?php foreach($allowed as $value=>$label):?><option value="<?=h($value)?>" <?=$selected['situacao_triagem']===$value?'selected':''?>><?=h($label)?></option><?php endforeach;?></select>
<label for="parecer">Parecer e evidência conferida (obrigatório para encaminhamento)</label><textarea id="parecer" name="parecer_pesquisador" maxlength="3000" placeholder="Escreva qual fonte consultou, qual afirmação ela sustenta, controvérsias e o próximo passo. Não atribua validade a fontes que não leu."><?=h((string)($selected['parecer_pesquisador']??''))?></textarea><p class="note">40 caracteres ou mais para registrar qualquer estado diferente de pendente. Uma alteração cria registro próprio no histórico auxiliar.</p><button type="submit">Salvar encaminhamento documental</button></form>
<?php if($history):?><details><summary>Histórico dos encaminhamentos (<?=count($history)?> recentes)</summary><?php foreach($history as $entry):?><div class="document"><small><?=h($entry['realizado_em'])?> · <?=h($entry['status_anterior'])?> → <?=h($entry['status_novo'])?></small><p><?=h($entry['parecer'])?></p></div><?php endforeach;?></details><?php endif;?>
<?php endif;?></section></div>
<p class="note">As datas, notas e títulos mostrados vêm do banco existente. A nova tabela auxiliar organiza o trabalho e preserva o histórico sem homologar candidatos. Caso o banco original tenha mudado desde a extração, confronte a ficha atual antes de usar um parecer.</p>
</main></body></html>
