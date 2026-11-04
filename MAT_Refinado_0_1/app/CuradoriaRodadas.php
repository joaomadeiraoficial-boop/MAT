<?php
declare(strict_types=1);

/**
 * Pesquisa histórica do MAT — consulta exclusivamente o banco mat_temporis_curadoria.
 * Não altera a conexão principal do MAT; não cria tabelas ou preenche registros.
 * As contagens são obtidas por SELECT no MySQL, sem JSON, CSV ou valores simulados.
 */
final class CuradoriaRodadas
{
    public const CRITERIOS = [
        'impacto_difusao' => 'Difusão',
        'impacto_magnitude' => 'Magnitude',
        'impacto_alcance' => 'Alcance',
        'impacto_persistencia' => 'Persistência',
        'conf_rastreabilidade' => 'Rastreabilidade documental',
        'conf_independencia' => 'Independência das evidências',
        'conf_datacao' => 'Precisão da datação',
        'conf_consistencia' => 'Consistência histórica',
        'fund_pervasividade' => 'Aplicabilidade entre setores',
        'fund_aprimoramento' => 'Potencial de aprimoramento',
        'fund_complementaridade' => 'Tecnologias complementares',
    ];
    public const GRUPOS = [
        'Impacto' => ['impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia'],
        'Confiabilidade histórica' => ['conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia'],
        'Relevância fundacional' => ['fund_pervasividade','fund_aprimoramento','fund_complementaridade'],
    ];

    /** Nome do banco de pesquisa: nunca aponta automaticamente para o banco de marcos do MAT. */
    public static function databaseName(): string { return 'mat_temporis_curadoria'; }

    public static function connect(): ?PDO {
        static $attempted = false, $pdo = null;
        if ($attempted) return $pdo;
        $attempted = true;
        try {
            $host = (string)cfg('db_host','127.0.0.1');
            $port = (int)cfg('db_port',3306);
            $pdo = new PDO(
                'mysql:host='.$host.';port='.$port.';dbname='.self::databaseName().';charset=utf8mb4',
                (string)cfg('db_user','root'),
                (string)cfg('db_pass',''),
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES=>false,
                 PDO::ATTR_TIMEOUT=>2]
            );
            // Banco separado precisa ter as cinco tabelas da pesquisa; sem migração implícita.
            foreach (['cur_candidatos','cur_criterios','cur_fontes','cur_rodadas','cur_log'] as $table) {
                $pdo->query('SELECT 1 FROM `'.$table.'` LIMIT 1');
            }
            return $pdo;
        } catch (Throwable $e) {
            error_log('MAT: banco de curadoria indisponível: '.$e->getMessage());
            $pdo = null;
            return null;
        }
    }

    public static function overview(PDO $pdo): array {
        $counts = $pdo->query(
            'SELECT COUNT(*) AS total, COUNT(DISTINCT id_extracao) AS ids_distintos, '
            .'SUM(CASE WHEN status_avaliacao IN (\'PROPOSTA_COMPLETA_AGUARDANDO_AUDITORIA\',\'PENDENTE_DE_EVIDENCIAS\') THEN 1 ELSE 0 END) AS pesquisados, '
            .'SUM(CASE WHEN homologado=1 THEN 1 ELSE 0 END) AS homologados, '
            .'SUM(CASE WHEN duplicatas_json IS NOT NULL AND JSON_VALID(duplicatas_json) AND JSON_LENGTH(duplicatas_json)>0 THEN 1 ELSE 0 END) AS duplicatas, '
            .'SUM(CASE WHEN titulo_original IS NOT NULL AND CHAR_LENGTH(titulo_original)>=70 THEN 1 ELSE 0 END) AS trechos_suficientes, '
            .'SUM(CASE WHEN status_avaliacao IN (\'NAO_INICIADO\',\'PENDENTE_DE_TRIAGEM\') THEN 1 ELSE 0 END) AS nao_iniciados '
            .'FROM cur_candidatos'
        )->fetch(PDO::FETCH_ASSOC) ?: [];
        foreach (['total','ids_distintos','pesquisados','homologados','duplicatas','trechos_suficientes','nao_iniciados'] as $key) {
            $counts[$key] = (int)($counts[$key] ?? 0);
        }
        $counts['trechos_pendentes'] = $counts['total'] - $counts['trechos_suficientes'];
        $counts['criterios_registrados'] = (int)$pdo->query('SELECT COUNT(*) FROM cur_criterios')->fetchColumn();
        $counts['notas_propostas'] = (int)$pdo->query('SELECT COUNT(*) FROM cur_criterios WHERE nota BETWEEN 0 AND 3')->fetchColumn();
        $counts['criterios_sem_evidencia'] = (int)$pdo->query('SELECT COUNT(*) FROM cur_criterios WHERE nota IS NULL OR situacao=\'SEM_EVIDENCIA\'')->fetchColumn();
        $counts['propostas_11'] = (int)$pdo->query('SELECT COUNT(*) FROM (SELECT id_extracao FROM cur_criterios WHERE nota BETWEEN 0 AND 3 GROUP BY id_extracao HAVING COUNT(DISTINCT codigo)=11) AS completos')->fetchColumn();
        $counts['fontes_curadoria'] = (int)$pdo->query('SELECT COUNT(*) FROM cur_fontes')->fetchColumn();
        $counts['avaliacoes'] = $counts['pesquisados'];
        // Campo legado apenas para compatibilidade das telas. Não existe uma fila de 300 neste banco.
        $counts['fila_controlada'] = $counts['total'];
        $counts['rodadas'] = $pdo->query('SELECT numero, estado AS fase, observacao AS descricao FROM cur_rodadas ORDER BY numero')->fetchAll(PDO::FETCH_ASSOC);
        $counts['planejados'] = (int)$pdo->query('SELECT COUNT(*) FROM cur_candidatos c JOIN cur_rodadas r ON r.numero=c.rodada WHERE r.estado=\'PLANEJADA\'')->fetchColumn();
        $counts['integra'] = $counts['ids_distintos'] === $counts['total'];
        return $counts;
    }

    /** Snapshot documental exclusivamente de SELECTs no banco de curadoria. */
    public static function progressSnapshot(PDO $pdo, bool $includeActivity = false): array {
        $v = self::overview($pdo);
        $v['revisados_humanos'] = (int)$pdo->query(
            "SELECT COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana IN ('APROVADO','REJEITADO','HOMOLOGADO')"
        )->fetchColumn();
        $v['em_revisao_humana'] = (int)$pdo->query("SELECT COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana='EM_REVISAO'")->fetchColumn();
        $v['aprovados_humanos'] = (int)$pdo->query("SELECT COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana='APROVADO'")->fetchColumn();
        $v['rejeitados_humanos'] = (int)$pdo->query("SELECT COUNT(*) FROM cur_candidatos WHERE estado_revisao_humana='REJEITADO'")->fetchColumn();
        $v['em_analise'] = max(0, $v['total'] - $v['pesquisados']);
        $v['progresso_percentual'] = $v['total'] > 0 ? round(100 * $v['pesquisados'] / $v['total'], 1) : 0.0;
        $v['pontuacao_percentual'] = $v['pesquisados'] > 0 ? round(100 * $v['notas_propostas'] / (11 * $v['pesquisados']), 1) : 0.0;
        $v['etapas'] = $pdo->query(
            'SELECT r.numero,r.estado,r.observacao,COUNT(c.id_extracao) AS total,'
            ."SUM(CASE WHEN c.status_avaliacao IN ('PROPOSTA_COMPLETA_AGUARDANDO_AUDITORIA','PENDENTE_DE_EVIDENCIAS') THEN 1 ELSE 0 END) AS pesquisados "
            .'FROM cur_rodadas r LEFT JOIN cur_candidatos c ON c.rodada=r.numero '
            .'GROUP BY r.numero,r.estado,r.observacao ORDER BY r.numero ASC'
        )->fetchAll(PDO::FETCH_ASSOC);
        $v['atividade'] = $includeActivity ? $pdo->query(
            'SELECT l.id_extracao,l.acao,l.descricao,l.data_acao,c.titulo_proposto '
            .'FROM cur_log l LEFT JOIN cur_candidatos c ON c.id_extracao=l.id_extracao '
            .'ORDER BY l.data_acao DESC,l.id DESC LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC) : [];
        return $v;
    }

    private static function conditions(string $filter, string $query, array &$params): string {
        $parts = [];
        $allowed = [
            'pesquisados' => "c.status_avaliacao IN ('PROPOSTA_COMPLETA_AGUARDANDO_AUDITORIA','PENDENTE_DE_EVIDENCIAS')",
            'planejados' => "r.estado='PLANEJADA'",
            'nao_iniciados' => "c.status_avaliacao IN ('NAO_INICIADO','PENDENTE_DE_TRIAGEM')",
            'duplicatas' => 'c.duplicatas_json IS NOT NULL AND JSON_VALID(c.duplicatas_json) AND JSON_LENGTH(c.duplicatas_json)>0',
            'em_revisao' => "c.estado_revisao_humana='EM_REVISAO'",
            'aprovados' => "c.estado_revisao_humana='APROVADO'",
            'homologados' => "c.estado_revisao_humana='HOMOLOGADO' AND c.homologado=1",
            'rejeitados' => "c.estado_revisao_humana='REJEITADO'",
            'sem_evidencia' => '(NOT EXISTS(SELECT 1 FROM cur_criterios t WHERE t.id_extracao=c.id_extracao) OR EXISTS(SELECT 1 FROM cur_criterios t WHERE t.id_extracao=c.id_extracao AND t.nota IS NULL))',
        ];
        if (isset($allowed[$filter])) $parts[]=$allowed[$filter];
        if ($query!=='') {
            $parts[]='(c.id_extracao LIKE :id OR c.titulo_proposto LIKE :titulo OR c.titulo_original LIKE :original)';
            $params[':id']='%'.$query.'%';
            $params[':titulo']='%'.$query.'%';
            $params[':original']='%'.$query.'%';
        }
        return $parts ? ' WHERE '.implode(' AND ',$parts) : '';
    }

    public static function listing(PDO $pdo,string $filter,string $query,int $page,int $perPage=15): array {
        $params=[];
        $where=self::conditions($filter,$query,$params);
        $join=' FROM cur_candidatos c INNER JOIN cur_rodadas r ON r.numero=c.rodada ';
        $s=$pdo->prepare('SELECT COUNT(*)'.$join.$where);
        $s->execute($params);$total=(int)$s->fetchColumn();
        $perPage=max(1,min(50,$perPage));$pages=max(1,(int)ceil($total/$perPage));$page=max(1,min($page,$pages));
        $sql='SELECT c.id_extracao,c.titulo_proposto,c.titulo_original AS titulo_extraido, c.ano_original, c.ano_validado,c.pagina_livro, '
            .'c.status_avaliacao AS estado_fluxo,c.status_avaliacao,c.estado_revisao_humana,c.homologado,c.rodada AS rodada_pesquisa, '
            .'r.estado AS estado_rodada'
            .$join.$where.' ORDER BY c.rodada ASC,c.id_extracao ASC LIMIT :limite OFFSET :inicio';
        $s=$pdo->prepare($sql);
        foreach($params as $k=>$v)$s->bindValue($k,$v,PDO::PARAM_STR);
        $s->bindValue(':limite',$perPage,PDO::PARAM_INT);
        $s->bindValue(':inicio',($page-1)*$perPage,PDO::PARAM_INT);
        $s->execute();
        return ['rows'=>$s->fetchAll(PDO::FETCH_ASSOC),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }

    public static function roundItems(PDO $pdo,int $round,bool $planned=false): array {
        $sql='SELECT c.id_extracao,c.titulo_proposto,c.status_avaliacao AS estado_fluxo,c.status_avaliacao,c.homologado '
            .'FROM cur_candidatos c JOIN cur_rodadas r ON r.numero=c.rodada '
            .'WHERE c.rodada=:num AND '.($planned?"r.estado='PLANEJADA'":"r.estado<>'PLANEJADA'")
            .' ORDER BY c.id_extracao LIMIT 100';
        $s=$pdo->prepare($sql);$s->execute([':num'=>$round]);return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function record(PDO $pdo,string $id): ?array {
        if (!preg_match('/^MCD-[0-9]{5}$/D',$id)) return null;
        $s=$pdo->prepare('SELECT c.* FROM cur_candidatos c WHERE c.id_extracao=:id LIMIT 1');
        $s->execute([':id'=>$id]);$r=$s->fetch(PDO::FETCH_ASSOC);
        if (!$r) return null;
        $r['titulo_extraido'] = (string)($r['titulo_original']??'');
        $r['ano_importado'] = $r['ano_original'];
        $r['categoria_original'] = $r['categoria'];
        $r['fonte_original'] = 'McClellan III & Dorn (2006)';
        $r['estado_fluxo'] = $r['status_avaliacao'];
        // O banco contém o fragmento de extração, não o texto integral do livro.
        $r['original'] = [
            'trecho_evidencia' => $r['titulo_original'],
            'pagina_livro' => $r['pagina_livro'],
            'pagina_pdf' => $r['pagina_pdf'],
            'capitulo' => null,
        ];
        $r['pendencias'] = self::decodeArray((string)($r['pendencias_json']??''));
        $r['duplicatas'] = self::decodeArray((string)($r['duplicatas_json']??''));
        $r['analise'] = json_decode((string)($r['analise_historica_json']??''),true) ?: [];
        $r['criterios']=[];
        $s=$pdo->prepare('SELECT codigo AS criterio,nota,situacao,justificativa,evidencias_json,fontes_ids_json,o_que_falta FROM cur_criterios WHERE id_extracao=:id');
        $s->execute([':id'=>$id]);
        foreach($s->fetchAll(PDO::FETCH_ASSOC) as $c)$r['criterios'][$c['criterio']]=$c;
        $s=$pdo->prepare('SELECT fonte_id,autor_instituicao,titulo,ano AS ano_texto,descricao_evidencia,url,natureza FROM cur_fontes WHERE id_extracao=:id ORDER BY fonte_id');
        $s->execute([':id'=>$id]);$r['fontes']=$s->fetchAll(PDO::FETCH_ASSOC);
        $s=$pdo->prepare('SELECT acao,descricao,data_acao FROM cur_log WHERE id_extracao=:id ORDER BY data_acao,id');
        $s->execute([':id'=>$id]);$r['log']=$s->fetchAll(PDO::FETCH_ASSOC);
        $r['memoria']=self::memory($r['criterios'],$r['fontes']);
        return $r;
    }

    /** Cálculo individual demonstrativo segundo a rubrica existente, jamais homologação. */
    public static function memory(array $criteria,array $sources): array {
        $scores=[];$sums=[];$used=0;$complete=true;
        foreach(self::GRUPOS as $group=>$keys){
            $sum=0;
            foreach($keys as $key){
                $c=$criteria[$key]??null;$v=$c['nota']??null;
                if($v===null||!preg_match('/^[0-3]$/D',(string)$v)){$complete=false;$scores[$key]=null;continue;}
                $scores[$key]=(int)$v;$sum+=(int)$v;$used++;
                if(trim((string)($c['justificativa']??''))===''||!self::decodeArray((string)($c['fontes_ids_json']??'')))$complete=false;
            }
            $sums[$group]=$sum;
        }
        $result=['scores'=>$scores,'sums'=>$sums,'avaliadas'=>$used,'completa'=>$complete && $used===11 && count($sources)>0,'I'=>null,'C'=>null,'F'=>null,'peso'=>null];
        if($result['completa']){
            $result['I']=1+9*$sums['Impacto']/12;
            $result['C']=$sums['Confiabilidade histórica']/12;
            $result['F']=1+0.5*$sums['Relevância fundacional']/9;
            $result['peso']=pow($result['I']/10,2)*$result['C']*$result['F'];
        }
        return $result;
    }
    public static function decodeArray(string $value): array {
        $data=json_decode($value,true);return is_array($data)&&array_is_list($data)?$data:[];
    }
    public static function sourceUrl(mixed $url): ?string {
        if(!is_string($url)||!filter_var($url,FILTER_VALIDATE_URL))return null;
        $scheme=strtolower((string)parse_url($url,PHP_URL_SCHEME));
        return in_array($scheme,['http','https'],true)?$url:null;
    }
    public static function label(string $code): string {
        return match($code){
            'PROPOSTA_COMPLETA_AGUARDANDO_AUDITORIA' => '11 propostas · aguardando auditoria',
            'PENDENTE_DE_EVIDENCIAS' => 'Pesquisado · evidências pendentes',
            'NAO_REVISADO' => 'Revisão humana pendente',
            'EM_REVISAO' => 'Em revisão humana',
            'APROVADO' => 'Aprovado pelo pesquisador',
            'REJEITADO' => 'Rejeitado · preservado',
            'HOMOLOGADO' => 'Homologado',
            'NAO_INICIADO' => 'Não iniciado',
            'PENDENTE_DE_TRIAGEM' => 'Aguardando triagem',
            'PLANEJADA' => 'Planejada',
            'PESQUISA_PRELIMINAR_CONCLUIDA' => 'Pesquisa preliminar concluída',
            'SEM_EVIDENCIA' => 'Sem evidência suficiente',
            'NAO_ATENDE' => 'Não atende',
            default => str_replace('_',' ',strtolower($code)),
        };
    }
}
