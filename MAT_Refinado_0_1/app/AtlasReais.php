<?php
declare(strict_types=1);
/** Periodizacao por ANO em vez de strings inconsistentes de periodo. */
final class AtlasReais {
    public const ERAS = [
        'prehistoria'=>['nome'=>'Pré-história','romano'=>'I','datas'=>'Antes de 3500 a.C.','descricao'=>'As primeiras ferramentas e os vestígios de modos de vida.','de'=>null,'ate'=>-3501],
        'antiguidade'=>['nome'=>'Antiguidade','romano'=>'II','datas'=>'3500 a.C. — 475','descricao'=>'Escrita, engenharia, transmissão de conhecimento.','de'=>-3500,'ate'=>475],
        'medieval'=>['nome'=>'Idade Média','romano'=>'III','datas'=>'476 — 1452','descricao'=>'Ofícios, materiais e circulação de técnicas.','de'=>476,'ate'=>1452],
        'moderna'=>['nome'=>'Idade Moderna','romano'=>'IV','datas'=>'1453 — 1788','descricao'=>'Instrumentação, navegação e mecanização crescente.','de'=>1453,'ate'=>1788],
        'industrial'=>['nome'=>'Era Industrial','romano'=>'V','datas'=>'1789 — 1944','descricao'=>'Energia, fábricas, transportes e indústria.','de'=>1789,'ate'=>1944],
        'digital'=>['nome'=>'Era Digital','romano'=>'VI','datas'=>'1945 — 1999','descricao'=>'Computação, redes e sistemas eletrônicos.','de'=>1945,'ate'=>1999],
        'contemporanea'=>['nome'=>'Era Contemporânea','romano'=>'VII','datas'=>'2000 — presente','descricao'=>'Tecnologias em rede e novas fronteiras de pesquisa.','de'=>2000,'ate'=>null],
    ];
    public static function keyForYear(mixed $year): ?string {
        if (!is_numeric($year) || !preg_match('/^-?\d+$/D', (string)$year)) return null;
        $y=(int)$year;
        foreach (self::ERAS as $key=>$era) {
            if (($era['de']===null || $y >= $era['de']) && ($era['ate']===null || $y <= $era['ate'])) return $key;
        }
        return null;
    }
    public static function blank(): array {
        $data=[];
        foreach(self::ERAS as $key=>$era) $data[$key]=$era+['acervo'=>0,'aprovados'=>0,'preparacao'=>0,'cur_aprovados'=>0,'cur_homologados'=>0];
        return $data;
    }
    public static function counts(array $markers, array $imports): array {
        $data=self::blank();
        foreach($markers as $m) {
            if (($m['status']??'')==='demonstracao') continue;
            $key=self::keyForYear($m['ano']??null);
            if($key===null)continue;
            $data[$key]['acervo']++;
            if(($m['status']??'')==='aprovado')$data[$key]['aprovados']++;
        }
        foreach($imports as $r) {
            if(($r['status']??'')!=='pendente')continue;
            $key=self::keyForYear($r['ano']??null);
            if($key!==null)$data[$key]['preparacao']++;
        }
        return $data;
    }
    /**
     * As duas fontes são entidades DIFERENTES. A curadoria é a fonte de verdade
     * para candidatos/aprovações; o acervo legado permanece identificado.
     * Contar por ano não reduz a quantidade de candidatos: sem ano ficam em
     * categoria separada, nunca desaparecem do painel.
     */
    public static function fromSources(array $legacy, array $curCandidates): array {
        $data=self::blank();
        $summary=['cur_total'=>0,'cur_com_ano'=>0,'cur_sem_ano'=>0,
            'cur_aprovados'=>0,'cur_homologados'=>0,'cur_ano_validado'=>0,'cur_ano_extraido'=>0,'legado_total'=>0,'legado_aprovados'=>0];
        foreach($legacy as $m){
            if(($m['status']??'')==='demonstracao')continue;
            $summary['legado_total']++;
            if(($m['status']??'')==='aprovado')$summary['legado_aprovados']++;
            $key=self::keyForYear($m['ano']??null);
            if($key!==null){$data[$key]['acervo']++;
                if(($m['status']??'')==='aprovado')$data[$key]['aprovados']++;}
        }
        foreach($curCandidates as $c){
            $summary['cur_total']++;
            $state=(string)($c['estado_revisao_humana']??'');
            $isApproved=in_array($state,['APROVADO','HOMOLOGADO'],true);
            $isHomologated=($state==='HOMOLOGADO' && (int)($c['homologado']??0)===1);
            if($isApproved)$summary['cur_aprovados']++;
            if($isHomologated)$summary['cur_homologados']++;
            $ano=$c['ano_validado']??$c['ano_original']??null;
            $key=self::keyForYear($ano);
            if($key===null){$summary['cur_sem_ano']++;continue;}
            $summary['cur_com_ano']++;
            if(self::keyForYear($c['ano_validado']??null)!==null)$summary['cur_ano_validado']++;
            else $summary['cur_ano_extraido']++;
            $data[$key]['preparacao']++;
            if($isApproved)$data[$key]['cur_aprovados']++;
            if($isHomologated)$data[$key]['cur_homologados']++;
        }
        return ['eras'=>$data,'resumo'=>$summary];
    }
    public static function fetchOverview(?PDO $pdo): array {
        require_once __DIR__.'/CuradoriaRodadas.php';
        $cur=CuradoriaRodadas::connect();
        if(!$cur)throw new RuntimeException('Banco científico da curadoria indisponível.');
        $legacy=$pdo?$pdo->query("SELECT ano,status FROM marcos WHERE status <> 'demonstracao'")->fetchAll(PDO::FETCH_ASSOC):[];
        $candidates=$cur->query('SELECT ano_validado,ano_original,estado_revisao_humana,homologado FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        return self::fromSources($legacy,$candidates);
    }
    public static function fetchCounts(PDO $pdo): array {
        return self::fetchOverview($pdo)['eras'];
    }
    /** Apenas investigadores logados podem abrir detalhes individuais dos candidatos. */
    public static function rows(?PDO $pdo, string $era, bool $import=false, int $limit=45): array {
        if(!isset(self::ERAS[$era]))return [];
        $range=self::ERAS[$era];$from=$range['de'];$to=$range['ate'];
        if($import){
            require_once __DIR__.'/CuradoriaRodadas.php';
            $cur=CuradoriaRodadas::connect();
            if(!$cur)return [];
            $sql="SELECT id_extracao,titulo_proposto AS titulo,COALESCE(ano_validado,ano_original) AS ano, estado_revisao_humana AS status FROM cur_candidatos WHERE 1=1";
            $params=[];
            if($from!==null){$sql.=' AND COALESCE(ano_validado,ano_original)>=?';$params[]=$from;}
            if($to!==null){$sql.=' AND COALESCE(ano_validado,ano_original)<=?';$params[]=$to;}
            $sql.=' ORDER BY ano ASC,id_extracao ASC LIMIT '.max(1,min(200,$limit));
            $s=$cur->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);
        }
        if(!$pdo)return [];
        $sql="SELECT id,titulo,ano,status FROM marcos WHERE status<>'demonstracao'";
        $params=[];
        if($from!==null){$sql.=' AND ano>=?';$params[]=$from;}
        if($to!==null){$sql.=' AND ano<=?';$params[]=$to;}
        $sql.=' ORDER BY ano ASC,id ASC LIMIT '.max(1,min(200,$limit));
        $s=$pdo->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
