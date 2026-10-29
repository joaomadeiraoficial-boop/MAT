<?php
declare(strict_types=1);
function revised_schema_ready(): bool {
    if(!db()) return false;
    try { db()->query('SELECT marco_id FROM marco_avaliacoes_revisadas LIMIT 0'); return true; }
    catch (PDOException) { return false; }
}
function revised_evaluation(int $id): ?array {
    if($id<=0 || !revised_schema_ready()) return null;
    $q=db()->prepare('SELECT * FROM marco_avaliacoes_revisadas WHERE marco_id=?');$q->execute([$id]);
    return $q->fetch() ?: null;
}
function revised_all_evaluations(): array {
    if(!revised_schema_ready()) return [];
    $rows=db()->query('SELECT * FROM marco_avaliacoes_revisadas')->fetchAll();
    $map=[];foreach($rows as $r)$map[(int)$r['marco_id']]=$r;
    return $map;
}
function revised_current_series(int $referenceYear): array {
    return RevisedMethodology::series(all_markers(),revised_all_evaluations(),$referenceYear);
}
