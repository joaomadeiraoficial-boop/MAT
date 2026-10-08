<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
require_csrf();
try {
    $rows=$pdo->query("SELECT id,nome,descricao,categoria FROM marcos WHERE escopo_revisado=0 AND status NOT IN ('rejeitado','duplicado')")->fetchAll();
    $stmt=$pdo->prepare('UPDATE marcos SET natureza=?,sugestao_elegibilidade=?,motivo_escopo=?,classificador_versao=?,scope_score=?,scope_confidence=? WHERE id=? AND escopo_revisado=0');
    $count=0;
    foreach($rows as $m){
        $r=ScopeClassifier::classify((string)$m['nome'],(string)$m['descricao'],(string)$m['categoria']);
        $stmt->execute([$r['natureza'],$r['sugestao_elegibilidade'],$r['motivo_escopo'],$r['classificador_versao'],$r['scope_score'],$r['scope_confidence'],$m['id']]);
        $count += $stmt->rowCount();
    }
    Audit::log($pdo,'scope',null,'reclassify_pending',null,['count'=>$count,'classifier'=>ScopeClassifier::VERSION]);
    echo json_encode(['ok'=>true,'count'=>$count],JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
