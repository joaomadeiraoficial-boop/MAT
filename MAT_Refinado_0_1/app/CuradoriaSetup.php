<?php
declare(strict_types=1);

/**
 * Configuração automática e NÃO destrutiva da curadoria no banco já utilizado pelo MAT.
 * Só é executada depois do login do pesquisador e somente se o lote ORIGINAL
 * contiver exatamente 300 candidatos com IDs distintos.
 * Não cria banco, não altera credenciais, nem modifica a tabela de marcos oficiais.
 */
final class CuradoriaSetup
{
    private static string $notice='';
    public static function notice(): string { return self::$notice; }

    private static function exists(PDO $pdo, string $table): bool
    {
        $s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->execute([$table]);
        return (int)$s->fetchColumn()===1;
    }

    private static function ready(PDO $pdo): bool
    {
        foreach(['temporis_cur_rodadas','temporis_cur_fila','temporis_cur_avaliacoes','temporis_cur_criterios','temporis_cur_fontes'] as $table) {
            if(!self::exists($pdo,$table))return false;
        }
        return (int)$pdo->query('SELECT COUNT(*) FROM temporis_cur_fila')->fetchColumn()===300
            && (int)$pdo->query('SELECT COUNT(*) FROM temporis_cur_avaliacoes')->fetchColumn()>=10
            && (int)$pdo->query('SELECT COUNT(*) FROM temporis_cur_criterios')->fetchColumn()>=110;
    }

    public static function ensure(PDO $pdo, string $sha): void
    {
        static $attempted=false;
        if($attempted || !function_exists('is_admin') || !is_admin())return;
        $attempted=true;
        self::$notice='';
        try {
            if(!self::exists($pdo,'import_lotes') || !self::exists($pdo,'import_itens')) {
                self::$notice='O banco atual não possui as tabelas de importação do MAT.';
                return;
            }
            $s=$pdo->prepare('SELECT COUNT(*) AS n,COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(i.bruto_json,\'$.id_extracao\'))) AS u FROM import_itens i INNER JOIN import_lotes l ON l.id=i.lote_id WHERE l.arquivo_sha256=?');
            $s->execute([$sha]);
            $counts=$s->fetch(PDO::FETCH_ASSOC)?:[];
            if((int)($counts['n']??0)!==300 || (int)($counts['u']??0)!==300) {
                self::$notice='O banco atual não contém o lote original completo dos 300 candidatos. Os dados não serão inventados.';
                return;
            }
            if(self::ready($pdo))return;
            $lock='mat_cur_'.substr(sha1((string)$pdo->query('SELECT DATABASE()')->fetchColumn()),0,24);
            $l=$pdo->prepare('SELECT GET_LOCK(?,3)');$l->execute([$lock]);
            if((int)$l->fetchColumn()!==1){self::$notice='A preparação da curadoria está em andamento em outra sessão.';return;}
            try {
                if(self::ready($pdo))return;
                $queries=require __DIR__.'/CuradoriaSeed.php';
                foreach($queries as $q) {
                    if(!is_string($q) || !preg_match('/^\s*(CREATE TABLE IF NOT EXISTS|INSERT IGNORE INTO `temporis_cur_)/i',$q)){
                        throw new RuntimeException('Comando de curadoria inesperado.');
                    }
                    $pdo->exec($q);
                }
                if(!self::ready($pdo))throw new RuntimeException('Contagens de curadoria incompletas após a instalação automática.');
            } finally {
                $release=$pdo->prepare('SELECT RELEASE_LOCK(?)');$release->execute([$lock]);
            }
        } catch(Throwable $e) {
            self::$notice='Não foi possível preparar a curadoria automaticamente. Verifique as permissões do MySQL e o registro de erros do PHP.';
            error_log('MAT: auto-preparação de curadoria: '.$e->getMessage());
        }
    }
}
