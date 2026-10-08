<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_csrf();
$input=json_decode(file_get_contents('php://input'),true)?:$_POST;
$id=(int)($input['id']??0);$action=$input['action']??'';
$stmt=$pdo->prepare('SELECT * FROM duplicate_candidates WHERE id=? AND status="pendente"');$stmt->execute([$id]);$d=$stmt->fetch();
if(!$d){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Par não encontrado']);exit;}
try{
 $pdo->beginTransaction();
 if($action==='not_duplicate'){
   $pdo->prepare("UPDATE duplicate_candidates SET status='nao_duplicado',reviewed_at=NOW() WHERE id=?")->execute([$id]);
 } elseif(in_array($action,['merge_a','merge_b'],true)){
   $master=$action==='merge_a'?(int)$d['marco_a']:(int)$d['marco_b'];$dup=$master===(int)$d['marco_a']?(int)$d['marco_b']:(int)$d['marco_a'];
   $s=$pdo->prepare('SELECT * FROM marcos WHERE id IN (?,?) ORDER BY id');$s->execute([$master,$dup]);$pair=$s->fetchAll();
   $m1=null;$m2=null;foreach($pair as $r){if((int)$r['id']===$master)$m1=$r;else$m2=$r;}
   $fields=['descricao','origem','categoria','subcategoria','civilizacao','pais','cidade','impacto','confiabilidade','fator_fundacional','score_pi','justificativa_impacto','justificativa_confiabilidade','justificativa_fundacional'];
   $updates=[];$vals=[];foreach($fields as $f){if(($m1[$f]===null||$m1[$f]==='')&&($m2[$f]!==null&&$m2[$f]!=='')){$updates[]="$f=?";$vals[]=$m2[$f];}}
   if($updates){$vals[]=$master;$pdo->prepare('UPDATE marcos SET '.implode(',',$updates).' WHERE id=?')->execute($vals);}
   $pdo->prepare('INSERT IGNORE INTO marco_fontes (marco_id,fonte_id,papel,trecho,pagina) SELECT ?,fonte_id,papel,trecho,pagina FROM marco_fontes WHERE marco_id=?')->execute([$master,$dup]);
   $pdo->prepare("UPDATE marcos SET status='duplicado',incluir_temporis=0 WHERE id=?")->execute([$dup]);
   $pdo->prepare("UPDATE duplicate_candidates SET status='mesclado',reviewed_at=NOW() WHERE id=?")->execute([$id]);
   Audit::log($pdo,'duplicate',$id,'merge',['master'=>$master,'duplicate'=>$dup],null);
 } else throw new RuntimeException('Ação inválida.');
 $pdo->commit();echo json_encode(['ok'=>true]);
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
