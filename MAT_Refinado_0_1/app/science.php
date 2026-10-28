<?php
declare(strict_types=1);
/** O laboratorio e complementar ao acervo original do Observatorio. */
function science_summary(): array {
    if (!db()) return ['pendentes'=>0,'triados'=>0,'elegiveis'=>0,'fontes'=>0,'vinculos'=>0,'duplicatas'=>0,'auditados'=>0];
    $pdo=db(); $s=[];
    foreach ([
        'pendentes'=>"SELECT COUNT(*) FROM marcos WHERE escopo_revisado=0 AND status <> 'rejeitado'",
        'triados'=>"SELECT COUNT(*) FROM marcos WHERE escopo_revisado=1",
        'elegiveis'=>"SELECT COUNT(*) FROM marcos WHERE status='aprovado' AND escopo_revisado=1 AND elegibilidade_temporis='sim' AND incluir_temporis=1",
        'fontes'=>'SELECT COUNT(*) FROM fontes',
        'vinculos'=>'SELECT COUNT(*) FROM marco_fontes',
        'duplicatas'=>"SELECT COUNT(*) FROM duplicate_candidates WHERE status='pendente'",
        'auditados'=>'SELECT COUNT(*) FROM revisoes',
    ] as $key=>$sql) $s[$key]=(int)$pdo->query($sql)->fetchColumn();
    return $s;
}
function sources_all(): array {
    if(!db())return [];
    return db()->query('SELECT f.*, (SELECT COUNT(*) FROM marco_fontes mf WHERE mf.fonte_id=f.id) AS qtd_marcos FROM fontes f ORDER BY f.titulo LIMIT 400')->fetchAll();
}
function linked_sources(int $id): array {
    if(!db()||$id<=0)return [];
    $q=db()->prepare('SELECT f.*,mf.papel,mf.pagina,mf.trecho FROM marco_fontes mf JOIN fontes f ON f.id=mf.fonte_id WHERE mf.marco_id=? ORDER BY f.titulo');
    $q->execute([$id]);return $q->fetchAll();
}
function scope_preview(array $m): array {
    return ScopeClassifier::classify((string)($m['titulo']??''),(string)($m['descricao']??''),(string)($m['categoria']??''));
}
function metrics_preview(array $m,array $sources): array {
    $old=[
     'nome'=>(string)($m['titulo']??''),'descricao'=>(string)($m['descricao']??''),
     'categoria'=>(string)($m['categoria']??''),'origem'=>(string)($m['civilizacao']??''),
     'organizacao'=>(string)($m['criador']??''),'precisao_data'=>($m['precisao_data']??'')==='exata'?'ano':(($m['precisao_data']??'')==='intervalo'?'intervalo':'desconhecida')
    ];
    $map=[];
    foreach($sources as $s) $map[]=[
     'nome'=>$s['titulo'],'tipo'=>match($s['tipo']){'instituicao'=>'site_institucional','catalogo'=>'base_dados',default=>$s['tipo']},
     'doi'=>'','verificada'=>0,'papel'=>$s['papel']??'confirmacao',
     'pagina'=>$s['pagina']??'','trecho'=>$s['trecho']??'','observacao'=>$s['notas']??''
    ];
    return MetricSuggester::analyze($old,$map);
}
function triage_rows(int $limit=50): array {
    if(!db())return [];
    $q=db()->prepare("SELECT id,titulo,ano,categoria,status,natureza,escopo_revisado,elegibilidade_temporis,sugestao_elegibilidade,incluir_temporis FROM marcos WHERE status <> 'rejeitado' ORDER BY escopo_revisado ASC, ano DESC, id DESC LIMIT ?");
    $q->bindValue(1,$limit,PDO::PARAM_INT);$q->execute();return $q->fetchAll();
}
/** Analise limitada para evitar trabalho explosivo; sem mesclagem automatica. */
function rescan_duplicates(int $maxRows=1500,int $yearWindow=5,float $threshold=.83): array {
    $pdo=db();if(!$pdo)return ['checked'=>0,'pending'=>0,'limited'=>false];
    $rows=$pdo->query("SELECT id,titulo,ano FROM marcos WHERE status <> 'rejeitado' ORDER BY ano,id LIMIT ".($maxRows+1))->fetchAll();
    $limited=count($rows)>$maxRows;
    if($limited)array_pop($rows);
    $pdo->beginTransaction();
    try{
        // Somente as sugestoes pendentes podem ser recalculadas.
        $pdo->exec("DELETE FROM duplicate_candidates WHERE status='pendente'");
        $insert=$pdo->prepare("INSERT IGNORE INTO duplicate_candidates(marco_a,marco_b,similarity,status) VALUES(?,?,?,'pendente')");
        foreach($rows as $i=>$a){
            for($j=$i+1;$j<count($rows);$j++){
                $b=$rows[$j];if((int)$b['ano']-(int)$a['ano']>$yearWindow)break;
                $sim=DuplicateDetector::similarity($a['titulo'],$b['titulo']);
                if($sim>=$threshold){$insert->execute([$a['id'],$b['id'],$sim]);}
            }
        }
        $pdo->commit();
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    return ['checked'=>count($rows),'pending'=>(int)$pdo->query("SELECT COUNT(*) FROM duplicate_candidates WHERE status='pendente'")->fetchColumn(),'limited'=>$limited];
}
function duplicate_rows(): array {
    if(!db())return [];
    return db()->query("SELECT d.*, a.titulo AS nome_a,b.titulo AS nome_b,a.ano AS ano_a,b.ano AS ano_b FROM duplicate_candidates d JOIN marcos a ON a.id=d.marco_a JOIN marcos b ON b.id=d.marco_b ORDER BY CASE WHEN d.status='pendente' THEN 0 ELSE 1 END, d.similarity DESC, d.id DESC LIMIT 120")->fetchAll();
}
function audit_rows(): array {
    if(!db())return [];
    return db()->query('SELECT r.*,m.titulo,u.nome AS revisor FROM revisoes r LEFT JOIN marcos m ON m.id=r.marco_id LEFT JOIN usuarios u ON u.id=r.usuario_id ORDER BY r.id DESC LIMIT 100')->fetchAll();
}
function science_log(int $id,string $action,array $before=[],array $after=[]): void {
    $q=db()->prepare('INSERT INTO revisoes(marco_id,usuario_id,operacao,dados_anteriores,dados_novos) VALUES(?,?,?,?,?)');
    $q->execute([$id,current_user()['id']??null,$action,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
}

/** Uma consulta curta e explícita ao JSON de origem do item importado. */
function source_payload(int $importId): ?array {
    if(!db() || $importId<=0) return null;
    $q=db()->prepare('SELECT i.bruto_json,i.numero_linha,i.status,l.arquivo_nome,l.id AS lote_id,l.arquivo_sha256 FROM import_itens i JOIN import_lotes l ON l.id=i.lote_id WHERE i.id=? LIMIT 1');
    $q->execute([$importId]);return $q->fetch()?:null;
}
