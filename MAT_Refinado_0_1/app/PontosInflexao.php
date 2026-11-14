<?php
declare(strict_types=1);
/**
 * Segunda camada do Atlas: candidatos a Pontos de Inflexão Tecnológica.
 * Cada ponto propõe uma ruptura tecnológica documentável (o que a impulsionou,
 * quais tecnologias foram transformadas e o que se seguiu). Os intervalos são
 * convenções de apresentação por ANO e podem se sobrepor: um dossiê pode apoiar
 * mais de um ponto. Nada aqui homologa singularidade: a ruptura é proposta
 * documental sujeita a revisão humana, e o termo é distinto da singularidade
 * tecnológica associada à inteligência artificial.
 */
final class PontosInflexao {
    public const PONTOS = [
        'revolucao_neolitica'=>['nome'=>'Revolução Neolítica','datas'=>'≈9000 a.C. — 3501 a.C.','descricao'=>'Domesticação de plantas e animais reorganiza a vida material em bases sedentárias.','de'=>-9000,'ate'=>-3501,
            'motor'=>'Agricultura, pastoreio e a necessidade de armazenar excedentes.',
            'tecnologias'=>'Cerâmica, tecelagem, ferramentas de pedra polida, moagem e armazenamento.',
            'consequencias'=>'Aldeias permanentes, demografia crescente e trabalho especializado que abriram caminho para as sociedades urbanas.'],
        'escrita_burocracia'=>['nome'=>'Escrita e burocracia','datas'=>'3500 a.C. — 1200 a.C.','descricao'=>'Registros contábeis de templos e estados urbanos criam a memória externa da sociedade.','de'=>-3500,'ate'=>-1201,
            'motor'=>'Administração de estoques, tributos e obras nas primeiras cidades da Mesopotâmia e do Egito.',
            'tecnologias'=>'Escrita cuneiforme e hieroglífica, numeração, calendários e registros em argila e papiro.',
            'consequencias'=>'Estado administrativo, leis codificadas e transmissão de conhecimento independente da memória individual.'],
        'impressao_tipografica'=>['nome'=>'Imprensa tipográfica','datas'=>'1450 — 1600','descricao'=>'Tipos móveis metálicos e prensas tornam a cópia de textos barata e padronizada.','de'=>1450,'ate'=>1600,
            'motor'=>'Fundição de tipos em liga metálica, prensa de parafuso e papel disponível em escala.',
            'tecnologias'=>'Prensas tipográficas, papel industrial, tipos móveis e ilustração gravada.',
            'consequencias'=>'Difusão acelerada de textos técnicos, padronização de edições e crescimento da alfabetização na Europa.'],
        'revolucao_cientifica'=>['nome'=>'Revolução Científica','datas'=>'1543 — 1687','descricao'=>'Instrumentos de medição e o método experimental institucionalizam a investigação da natureza.','de'=>1543,'ate'=>1687,
            'motor'=>'Telescópio e microscópio, matematização da física e sociedades científicas com publicações periódicas.',
            'tecnologias'=>'Instrumentos de precisão, astronomia telescópica, cálculo matemático e avaliação por pares embrionária.',
            'consequencias'=>'Ciência institucionalizada e engenharia apoiada em medidas e leis matemáticas.'],
        'revolucao_industrial'=>['nome'=>'Revolução Industrial','datas'=>'1769 — 1840','descricao'=>'A máquina a vapor substitui força humana, animal e hidráulica como motor central da produção.','de'=>1769,'ate'=>1840,
            'motor'=>'Vapor de carvão, manufatura fabril e mercado em expansão na Grã-Bretanha.',
            'tecnologias'=>'Máquina a vapor, fiações mecanizadas, metalurgia com coque e ferrovias iniciais.',
            'consequencias'=>'Fábricas, urbanização acelerada e transporte mecanizado em escala continental.'],
        'eletricidade_massa'=>['nome'=>'Eletricidade e produção em massa','datas'=>'1870 — 1913','descricao'=>'Redes elétricas, aço barato e química industrial reorganizam fábricas, cidades e laboratórios.','de'=>1870,'ate'=>1913,
            'motor'=>'Dinamos e motores elétricos, siderurgia em larga escala e laboratórios de pesquisa nas empresas.',
            'tecnologias'=>'Geração e distribuição elétrica, iluminação, telecomunicações e veículos a combustão.',
            'consequencias'=>'Cidades eletrificadas, produção em série e industrialização de pesquisa e desenvolvimento.'],
        'mobilizacao_mundiais'=>['nome'=>'Guerras mundiais e mobilização industrial','datas'=>'1914 — 1945','descricao'=>'A mobilização militar e industrial intensifica linhas de desenvolvimento tecnológico estratégicas.','de'=>1914,'ate'=>1945,
            'motor'=>'Estados mobilizando indústria, universidades e ciência para esforços de guerra em escala global.',
            'tecnologias'=>'Aviação, rádio e radar, comunicação de campo, química de explosivos, produção em massa de penicilina e reatores nucleares.',
            'consequencias'=>'Ciência planejada pelo Estado, computação inicial, aeroespaço e redes de pesquisa militar-civil permanentes.'],
        'transistor_computacao'=>['nome'=>'Transistor e computação','datas'=>'1947 — 1990','descricao'=>'Semicondutores e arquiteturas digitais tornam o processamento automático acessível e barato.','de'=>1947,'ate'=>1990,
            'motor'=>'Transistor, circuito integrado e demanda militar, espacial e depois de mercado de massa.',
            'tecnologias'=>'Semicondutores, computadores digitais, software e armazenamento magnético.',
            'consequencias'=>'Miniaturização contínua, automação de cálculo e informática pessoal.'],
        'rede_global'=>['nome'=>'Rede global','datas'=>'1989 — presente','descricao'=>'A Web conecta documentos e serviços em escala planetária e muda a circulação do conhecimento.','de'=>1989,'ate'=>null,
            'motor'=>'Web e Internet comercializada sobre telecomunicações globais de dados.',
            'tecnologias'=>'Web, busca em larga escala, dispositivos móveis e computação em nuvem.',
            'consequencias'=>'Ciência e publicação em rede, dados massivos e plataformas digitais de coordenação.'],
    ];

    /** Intervalo inclusivo por ano; a.C. usa anos negativos, como nas eras. */
    private static function contains(array $ponto, mixed $year): bool {
        if (!is_numeric($year) || !preg_match('/^-?\d+$/D',(string)$year)) return false;
        $y=(int)$year;
        return ($ponto['de']===null || $y >= $ponto['de']) && ($ponto['ate']===null || $y <= $ponto['ate']);
    }

    public static function blank(): array {
        $data=[];
        foreach(self::PONTOS as $key=>$ponto) $data[$key]=$ponto+['acervo'=>0,'aprovados'=>0,'preparacao'=>0,'cur_aprovados'=>0,'cur_homologados'=>0];
        return $data;
    }

    /** Imagem de fundo opcional por ponto. Convenção: assets/inflexoes/<chave>.(jpg|jpeg|png|webp|avif), ou campo 'imagem' no ponto. */
    public static function imagem(string $key): ?string {
        if(!isset(self::PONTOS[$key]))return null;
        $custom=self::PONTOS[$key]['imagem']??null;
        $candidates=$custom && is_string($custom) && $custom!==''?[$custom]:[];
        foreach(['jpg','jpeg','png','webp','avif'] as $ext)$candidates[]='assets/inflexoes/'.$key.'.'.$ext;
        foreach($candidates as $rel){
            if(is_string($rel) && $rel!=='' && !str_contains($rel,'..') && is_file(MAT_ROOT.'/'.$rel))return $rel;
        }
        return null;
    }

    /**
     * As duas fontes permanecem entidades DIFERENTES, como no Atlas das eras.
     * A sobreposição de intervalos é intencional: o mesmo dossiê pode apoiar
     * pontos distintos, e a nota metodológica na página declara isso.
     */
    public static function fromSources(array $legacy, array $curCandidates): array {
        $data=self::blank();
        $summary=['legado_total'=>0,'legado_aprovados'=>0,'cur_total'=>0,'cur_com_ano'=>0,
            'cur_aprovados'=>0,'cur_homologados'=>0];
        foreach($legacy as $m){
            if(($m['status']??'')==='demonstracao')continue;
            $summary['legado_total']++;
            if(($m['status']??'')==='aprovado')$summary['legado_aprovados']++;
            foreach(self::PONTOS as $key=>$ponto){
                if(!self::contains($ponto,$m['ano']??null))continue;
                $data[$key]['acervo']++;
                if(($m['status']??'')==='aprovado')$data[$key]['aprovados']++;
            }
        }
        foreach($curCandidates as $c){
            $summary['cur_total']++;
            $state=(string)($c['estado_revisao_humana']??'');
            $isApproved=in_array($state,['APROVADO','HOMOLOGADO'],true);
            $isHomologated=($state==='HOMOLOGADO' && (int)($c['homologado']??0)===1);
            if($isApproved)$summary['cur_aprovados']++;
            if($isHomologated)$summary['cur_homologados']++;
            $ano=$c['ano_validado']??$c['ano_original']??null;
            if($ano!==null && self::contains(['de'=>null,'ate'=>null],$ano))$summary['cur_com_ano']++;
            foreach(self::PONTOS as $key=>$ponto){
                if(!self::contains($ponto,$ano))continue;
                $data[$key]['preparacao']++;
                if($isApproved)$data[$key]['cur_aprovados']++;
                if($isHomologated)$data[$key]['cur_homologados']++;
            }
        }
        return ['pontos'=>$data,'resumo'=>$summary];
    }

    public static function fetchOverview(?PDO $pdo): array {
        require_once __DIR__.'/CuradoriaRodadas.php';
        $cur=CuradoriaRodadas::connect();
        if(!$cur)throw new RuntimeException('Banco científico da curadoria indisponível.');
        $legacy=$pdo?$pdo->query("SELECT ano,status FROM marcos WHERE status <> 'demonstracao'")->fetchAll(PDO::FETCH_ASSOC):[];
        $candidates=$cur->query('SELECT ano_validado,ano_original,estado_revisao_humana,homologado FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        return self::fromSources($legacy,$candidates);
    }

    /** Apenas investigadores logados podem abrir detalhes individuais dos registros. */
    public static function rows(?PDO $pdo, string $ponto, bool $import=false, int $limit=45): array {
        if(!isset(self::PONTOS[$ponto]))return [];
        $range=self::PONTOS[$ponto];$from=$range['de'];$to=$range['ate'];
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
