<?php
declare(strict_types=1);
/** Diagnostico do acervo: somente SELECTs e CSV privado, sem IA ou escrita MySQL. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Somente CLI'); }
require_once dirname(__DIR__).'/app/bootstrap.php';
require_once dirname(__DIR__).'/app/AuditoriaMaterialAcervo.php';
require_once dirname(__DIR__).'/app/PontuacaoAcervoIA.php';

function mat11CsvSeguro(string $v): string {
    // Neutraliza formula CSV, mantendo o valor para leitura e rastreamento.
    return preg_match('/^\s*[=+@\t\r]/u', $v) === 1 ? "'".$v : $v;
}
try {
    $pdo = db();
    if (!$pdo) throw new RuntimeException('MySQL indisponivel. Ligue o MySQL no XAMPP.');
    $marcos = all_markers();
    $fonteStmt = $pdo->prepare('SELECT id,titulo,url FROM fontes WHERE id=? LIMIT 1');
    $importStmt = $pdo->prepare('SELECT id,bruto_json FROM import_itens WHERE id=? LIMIT 1');
    $analises = [];
    $resumo = ['total' => 0, 'com_descricao' => 0, 'com_referencia_declarada' => 0,
        'com_url_referencia' => 0, 'com_trecho_vinculado' => 0,
        'com_trecho_importado_nao_verificado' => 0, 'sem_referencia' => 0,
        'prontos_para_pontuacao_automatica' => 0, 'acoes' => []];
    foreach ($marcos as $m) {
        $id = (int)$m['id'];
        $vinculos = linked_sources($id);
        $fonteDireta = null;
        if ((int)($m['fonte_id'] ?? 0) > 0) {
            $fonteStmt->execute([(int)$m['fonte_id']]);
            $f = $fonteStmt->fetch(PDO::FETCH_ASSOC);
            $fonteDireta = $f ?: null;
            $fonteStmt->closeCursor();
        }
        $import = null;
        if ((int)($m['import_item_id'] ?? 0) > 0) {
            $importStmt->execute([(int)$m['import_item_id']]);
            $i = $importStmt->fetch(PDO::FETCH_ASSOC);
            $import = $i ?: null;
            $importStmt->closeCursor();
        }
        $a = AuditoriaMaterialAcervo::analisar($m, $vinculos, $fonteDireta, $import);
        $analises[] = $a;
        $resumo['total']++;
        if ($a['tem_descricao']) $resumo['com_descricao']++;
        if ($a['tem_referencia']) $resumo['com_referencia_declarada']++;
        if ($a['tem_url']) $resumo['com_url_referencia']++;
        if ($a['vinculos_com_trecho'] > 0) $resumo['com_trecho_vinculado']++;
        if ($a['trecho_importado_util']) $resumo['com_trecho_importado_nao_verificado']++;
        if (!$a['tem_referencia']) $resumo['sem_referencia']++;
        $resumo['acoes'][$a['acao']] = ($resumo['acoes'][$a['acao']] ?? 0) + 1;
    }
    usort($analises, static fn(array $a,array $b): int => [$a['prioridade'],$a['ano'],$a['id']] <=> [$b['prioridade'],$b['ano'],$b['id']]);
    // Mesmo que existam trechos, NAO ha certificacao automatica, por isso mantem zero.
    $folder = PontuacaoAcervoIA::directory(true);
    $path = $folder.DIRECTORY_SEPARATOR.'auditoria_material_acervo.csv';
    $tmp = $path.'.tmp-'.getmypid();
    $fp = fopen($tmp,'wb');
    if ($fp === false) throw new RuntimeException('Nao foi possivel criar CSV privado.');
    fwrite($fp, "\xEF\xBB\xBF");
    fputcsv($fp, ['id','titulo','ano','status_acervo','acao','prioridade','descricao_cadastrada',
        'fonte_declarada','url_referencia','pagina','total_vinculos','vinculos_com_trecho',
        'trecho_importado_nao_verificado','alertas','aprovacao'], ';');
    foreach ($analises as $a) {
        fputcsv($fp, [$a['id'], mat11CsvSeguro($a['titulo']), $a['ano'], $a['status_acervo'], $a['acao'],
            $a['prioridade'], mat11CsvSeguro($a['descricao']), mat11CsvSeguro($a['fonte_declarada']),
            mat11CsvSeguro($a['url_referencia']), mat11CsvSeguro($a['pagina_declarada']),
            $a['vinculos'], $a['vinculos_com_trecho'], mat11CsvSeguro($a['trecho_importado']),
            implode('|', $a['alertas']), 'NAO_APROVADO'], ';');
    }
    fclose($fp);
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Falha ao finalizar CSV'); }
    echo json_encode(['estado'=>'DIAGNOSTICO_DOCUMENTAL','versao'=>AuditoriaMaterialAcervo::VERSION,
        'contagens'=>$resumo, 'csv'=>$path, 'mysql_alterado'=>false, 'requisicoes_ia'=>0,
        'aviso'=>'Fontes declaradas e descricoes NAO equivalem a citacoes verificadas. Use o CSV para auditar; nenhuma nota foi atribuida.'],
        JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'AUDITORIA DOCUMENTAL: '.$e->getMessage()."\n");
    exit(1);
}
