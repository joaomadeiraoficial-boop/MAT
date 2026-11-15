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
    /** Sub-períodos por era: aparecem apenas ao expandir o cartão; chaves usadas como 'era::sub'. */
    public const SUBERAS = [
        'prehistoria'=>[
            'paleolitico'=>['nome'=>'Paleolítico','datas'=>'Antes de 10000 a.C.','de'=>null,'ate'=>-10001],
            'mesolitico'=>['nome'=>'Mesolítico','datas'=>'10000 a.C. — 5001 a.C.','de'=>-10000,'ate'=>-5001],
            'neolitico'=>['nome'=>'Neolítico','datas'=>'5000 a.C. — 3501 a.C.','de'=>-5000,'ate'=>-3501],
        ],
        'antiguidade'=>[
            'primeiras_civilizacoes'=>['nome'=>'Primeiras civilizações','datas'=>'3500 a.C. — 1201 a.C.','de'=>-3500,'ate'=>-1201],
            'antiguidade_classica'=>['nome'=>'Antiguidade clássica','datas'=>'1200 a.C. — 475','de'=>-1200,'ate'=>475],
        ],
        'medieval'=>[
            'alta_idade_media'=>['nome'=>'Alta Idade Média','datas'=>'476 — 999','de'=>476,'ate'=>999],
            'baixa_idade_media'=>['nome'=>'Baixa Idade Média','datas'=>'1000 — 1452','de'=>1000,'ate'=>1452],
        ],
        'moderna'=>[
            'renascimento_expansao'=>['nome'=>'Renascimento e expansão','datas'=>'1453 — 1599','de'=>1453,'ate'=>1599],
            'ciencia_absolutismo'=>['nome'=>'Ciência moderna e absolutismo','datas'=>'1600 — 1749','de'=>1600,'ate'=>1749],
            'iluminismo'=>['nome'=>'Iluminismo e reformas','datas'=>'1750 — 1788','de'=>1750,'ate'=>1788],
        ],
        'industrial'=>[
            'primeira_revolucao'=>['nome'=>'Primeira Revolução Industrial','datas'=>'1789 — 1869','de'=>1789,'ate'=>1869],
            'segunda_revolucao'=>['nome'=>'Segunda Revolução Industrial','datas'=>'1870 — 1913','de'=>1870,'ate'=>1913],
            'guerras_mundiais'=>['nome'=>'Guerras mundiais','datas'=>'1914 — 1944','de'=>1914,'ate'=>1944],
        ],
        'digital'=>[
            'computacao_inicial'=>['nome'=>'Computação inicial','datas'=>'1945 — 1969','de'=>1945,'ate'=>1969],
            'microinformatica'=>['nome'=>'Microinformática','datas'=>'1970 — 1989','de'=>1970,'ate'=>1989],
            'web_inicial'=>['nome'=>'Web inicial','datas'=>'1990 — 1999','de'=>1990,'ate'=>1999],
        ],
        'contemporanea'=>[
            'redes_moveis'=>['nome'=>'Redes e dispositivos móveis','datas'=>'2000 — 2015','de'=>2000,'ate'=>2015],
            'ia_dados_massivos'=>['nome'=>'IA e dados massivos','datas'=>'2016 — presente','de'=>2016,'ate'=>null],
        ],
    ];

    /** Resolve uma era simples ou uma sub-era no formato 'era::sub'; devolve o intervalo. */
    public static function rangeFor(string $era): ?array {
        if(isset(self::ERAS[$era]))return self::ERAS[$era];
        if(str_contains($era,'::')){
            [$p,$s]=array_pad(explode('::',$era,2),2,'');
            if(isset(self::SUBERAS[$p][$s]))return self::SUBERAS[$p][$s]+['romano'=>'','descricao'=>''];
        }
        return null;
    }

    /** Imagem de faixa opcional por era. Convenção: assets/eras/<chave>.(jpg|jpeg|png|webp|avif). */
    public static function imagem(string $key): ?string {
        if(!isset(self::ERAS[$key]))return null;
        foreach(['jpg','jpeg','png','webp','avif'] as $ext){
            $rel='assets/eras/'.$key.'.'.$ext;
            if(!str_contains($rel,'..') && is_file(MAT_ROOT.'/'.$rel))return $rel;
        }
        return null;
    }

    /** Miniatura opcional de sub-período. Convenção: assets/eras/<era>__<sub>.(jpg|jpeg|png|webp|avif). */
    public static function subImagem(string $parent, string $sub): ?string {
        if(!isset(self::ERAS[$parent]) || !isset(self::SUBERAS[$parent][$sub]))return null;
        foreach(['jpg','jpeg','png','webp','avif'] as $ext){
            $rel='assets/eras/'.$parent.'__'.$sub.'.'.$ext;
            if(!str_contains($rel,'..') && is_file(MAT_ROOT.'/'.$rel))return $rel;
        }
        return null;
    }

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
    /** Contagens reais da curadoria por era e por sub-era. Um SELECT; candidatos com ano classificável. */
    public static function fetchSubCounts(): array {
        require_once __DIR__.'/CuradoriaRodadas.php';
        $cur=CuradoriaRodadas::connect();
        if(!$cur)return [];
        $rows=$cur->query('SELECT ano_validado,ano_original,estado_revisao_humana FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        $out=[];
        foreach(self::SUBERAS as $p=>$subs){
            $out[$p]=['total'=>0,'aprovados'=>0,'subs'=>[]];
            foreach($subs as $sk=>$s)$out[$p]['subs'][$sk]=['total'=>0,'aprovados'=>0];
        }
        foreach($rows as $c){
            $ano=$c['ano_validado']??$c['ano_original']??null;
            $p=self::keyForYear($ano);
            if($p===null || !isset($out[$p]))continue;
            $ano=(int)$ano;
            $aprov=in_array((string)($c['estado_revisao_humana']??''),['APROVADO','HOMOLOGADO'],true);
            $out[$p]['total']++;
            if($aprov)$out[$p]['aprovados']++;
            foreach(self::SUBERAS[$p] as $sk=>$s){
                if(($s['de']===null||$ano>=$s['de'])&&($s['ate']===null||$ano<=$s['ate'])){
                    $out[$p]['subs'][$sk]['total']++;
                    if($aprov)$out[$p]['subs'][$sk]['aprovados']++;
                }
            }
        }
        return $out;
    }

    public static function fetchCounts(PDO $pdo): array {
        return self::fetchOverview($pdo)['eras'];
    }
    /** Apenas investigadores logados podem abrir detalhes individuais dos candidatos. */
    public static function rows(?PDO $pdo, string $era, bool $import=false, int $limit=45): array {
        $range=self::rangeFor($era);
        if($range===null)return [];
        $from=$range['de'];$to=$range['ate'];
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
