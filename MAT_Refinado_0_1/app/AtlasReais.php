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
        foreach(self::ERAS as $key=>$era) $data[$key]=$era+['acervo'=>0,'aprovados'=>0,'preparacao'=>0];
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
    public static function fetchCounts(PDO $pdo): array {
        $markers=$pdo->query("SELECT ano,status FROM marcos WHERE status <> 'demonstracao'")->fetchAll(PDO::FETCH_ASSOC);
        $imports=$pdo->query("SELECT ano,status FROM import_itens WHERE status='pendente'")->fetchAll(PDO::FETCH_ASSOC);
        return self::counts($markers,$imports);
    }
    /** Detalhes só sob login para não expor trechos/candidatos privados. */
    public static function rows(PDO $pdo, string $era, bool $import=false, int $limit=45): array {
        if(!isset(self::ERAS[$era]))return [];
        $range=self::ERAS[$era];$from=$range['de'];$to=$range['ate'];
        $sql=$import ? 'SELECT id,titulo,ano,status FROM import_itens WHERE status=\'pendente\'' : "SELECT id,titulo,ano,status FROM marcos WHERE status<>'demonstracao'";
        $params=[];
        if($from!==null){$sql.=' AND ano>=?';$params[]=$from;}
        if($to!==null){$sql.=' AND ano<=?';$params[]=$to;}
        $sql.=' ORDER BY ano ASC,id ASC LIMIT '.max(1,min(200,$limit));
        $s=$pdo->prepare($sql);$s->execute($params);return $s->fetchAll(PDO::FETCH_ASSOC);
    }
}
