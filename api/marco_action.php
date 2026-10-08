<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_csrf();
$input=json_decode(file_get_contents('php://input'),true)?:$_POST;
$id=(int)($input['id']??0);$action=$input['action']??'';
$stmt=$pdo->prepare('SELECT * FROM marcos WHERE id=?');$stmt->execute([$id]);$m=$stmt->fetch();
if(!$m){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Marco não encontrado']);exit;}
try{
 if($action==='approve'){
   $src=$pdo->prepare('SELECT COUNT(*) FROM marco_fontes WHERE marco_id=?');$src->execute([$id]);$sourceCount=(int)$src->fetchColumn();
   if($sourceCount<1) throw new RuntimeException('Para aprovar, vincule pelo menos uma fonte.');
   if($m['score_pi']===null) throw new RuntimeException('Para aprovar, preencha Impacto, Confiabilidade e Fator Fundacional.');
   if(($m['escopo_revisado']??0)!=1) throw new RuntimeException('Revise primeiro o escopo científico deste marco.');
   if(($m['elegibilidade_temporis']??'revisar')!=='sim') throw new RuntimeException('Somente marcos definidos como elegíveis podem entrar na Têmporis.');
   $pdo->prepare("UPDATE marcos SET status='aprovado',incluir_temporis=1 WHERE id=?")->execute([$id]);
 } elseif($action==='reject'){
   $pdo->prepare("UPDATE marcos SET status='rejeitado',incluir_temporis=0 WHERE id=?")->execute([$id]);
 } else throw new RuntimeException('Ação inválida.');
 Audit::log($pdo,'marco',$id,$action,$m,null);
 echo json_encode(['ok'=>true]);
}catch(Throwable $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
