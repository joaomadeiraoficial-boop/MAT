<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';
require_once __DIR__.'/CuradoriaDatacao.php';

/** MAT Final: experimento independente e somente leitura, usando as 11 notas existentes.
 * Nenhuma nota é inventada, homologada ou corrigida por este módulo.
 */
final class TemporisFinal {
    public const PESOS = [
        'quadratica'=>'Impacto quadrático (adotada)',
        'linear'=>'Impacto linear (sensibilidade)',
        'geometrica'=>'Geométrica equilibrada (sensibilidade)',
        'media'=>'Média dos componentes (sensibilidade)',
    ];
    public const ESCALAS = [
        'empirica'=>'Acumulada sem compressão (adotada)',
        'log'=>'Logarítmica anterior',
        'pot065'=>'Potência 0,65',
        'pot125'=>'Potência 1,25',
    ];
    public const COORTES = [
        'ampla'=>'Todos não rejeitados com ano e 11 notas',
        'aprovados'=>'Somente aprovados, com data disponível',
        'validada'=>'Todos com data validada',
        'aprovados_validados'=>'Aprovados e com data validada',
        'sem_sem_evidencia'=>'Ampla sem texto SEM_EVIDENCIA',
        'sem_provisorias'=>'Ampla sem nota mínima provisória',
        'sem_contextos'=>'Ampla sem contexto ou escopo duvidoso',
        'homologada'=>'Apenas homologados',
    ];
    public const MODELOS = [
        'sem_avanco'=>'Sem crescimento (controle)',
        'linear50'=>'Linear · últimos 50 anos',
        'linear100'=>'Linear · últimos 100 anos',
        'exponencial50'=>'Exponencial · últimos 50 anos',
        'exponencial100'=>'Exponencial · últimos 100 anos',
        'aceleracao'=>'Aceleração local (experimental)',
        'amortecida'=>'Tendência amortecida (cenário)',
        'mediana'=>'Mediana de 3 métodos',
        'impeto'=>'Ímpeto dK/dt = aK^β (experimental)',
        'logistico'=>'Logístico com saturação (experimental)',
        'gompertz'=>'Gompertz com saturação (experimental)',
    ];
    public const FONTES = [
        'auto' => 'Automática: curadoria atual → MAT Final → snapshot',
        'curadoria' => 'Curadoria atual (MySQL do MAT Refinado)',
        'final' => 'Base isolada do MAT Final (MySQL)',
        'snapshot' => 'Exportação fixa de 07/11/2026 (sem MySQL)',
    ];

    /** Somente SELECT. A escolha automática prefere o banco em que se trabalha na curadoria. */
    public static function obterDados(string $fonte='auto', int $anoMax=2026): array {
        if (!isset(self::FONTES[$fonte])) throw new InvalidArgumentException('Fonte de dados desconhecida.');
        if ($fonte==='curadoria' || $fonte==='auto') {
            $pdo=CuradoriaRodadas::connect();
            if ($pdo!==null) {
                try {
                    return ['dados'=>self::carregar($pdo,$anoMax),'fonte'=>'curadoria',
                        'database'=>CuradoriaRodadas::databaseName()];
                } catch (Throwable $e) {
                    error_log('Têmporis: falha de leitura da curadoria: '.$e->getMessage());
                    if ($fonte==='curadoria') throw new RuntimeException('O banco da curadoria está acessível, mas a leitura dos candidatos ou critérios falhou. Confira o esquema e os logs.');
                }
            }
            if ($fonte==='curadoria') throw new RuntimeException('Não foi possível conectar à curadoria atual ('.CuradoriaRodadas::databaseName().'). Inicie MySQL e confira config.local.php.');
        }
        if ($fonte==='final' || $fonte==='auto') {
            $pdo=self::conectar();
            if ($pdo!==null) {
                try {
                    return ['dados'=>self::carregar($pdo,$anoMax),'fonte'=>'final',
                        'database'=>(string)cfg('temporis_final_db_name','mat_final_curadoria')];
                } catch (Throwable $e) {
                    error_log('Têmporis: falha de leitura do banco final: '.$e->getMessage());
                    if ($fonte==='final') throw new RuntimeException('O banco MAT Final está acessível, mas os campos da curadoria não puderam ser consultados. Confira os logs.');
                }
            }
            if ($fonte==='final') throw new RuntimeException('Banco isolado MAT Final indisponível. Confira temporis_final_db_name e a importação SQL.');
        }
        return ['dados'=>self::carregarSnapshot($anoMax),'fonte'=>'snapshot',
            'database'=>'arquivo instalacao/temporis_final_snapshot.json'];
    }

    private const IMP=['impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia'];
    private const CON=['conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia'];
    private const FUN=['fund_pervasividade','fund_aprimoramento','fund_complementaridade'];

    /** Conexão separada e exclusiva para o snapshot do MAT Final. Não modifica a curadoria original. */
    public static function conectar(): ?PDO {
        static $attempted = false, $pdo = null;
        if ($attempted) return $pdo;
        $attempted = true;
        $database = (string)cfg('temporis_final_db_name', 'mat_final_curadoria');
        // Impede injeção de identificadores em configuração externa.
        if (!preg_match('/^[A-Za-z0-9_]{1,64}$/D', $database)) {
            error_log('MAT Final: nome de banco inválido.');
            return null;
        }
        // Uma instalação equivocada jamais deve direcionar o ensaio para a curadoria ativa.
        if ($database === CuradoriaRodadas::databaseName() || $database === (string)cfg('db_name','mat_observatorio')) {
            error_log('MAT Final: banco do experimento deve ser diferente dos bancos originais.');
            return null;
        }
        try {
            $host = (string)cfg('db_host','127.0.0.1');
            $port = (int)cfg('db_port',3306);
            $pdo = new PDO('mysql:host='.$host.';port='.$port.';dbname='.$database.';charset=utf8mb4',
                (string)cfg('db_user','root'), (string)cfg('db_pass',''),
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES=>false, PDO::ATTR_TIMEOUT=>2]);
            foreach (['cur_candidatos','cur_criterios'] as $table) {
                $pdo->query('SELECT 1 FROM `'.$table.'` LIMIT 1');
            }
            return $pdo;
        } catch (Throwable $e) {
            error_log('MAT Final: banco exploratório indisponível: '.$e->getMessage());
            $pdo = null;
            return null;
        }
    }

    /** Exportação histórica verificável, inclusa no ZIP. Modo apenas leitura sem MySQL.
     * O processamento é o mesmo usado no banco e não cria notas ou validações.
     */
    public static function carregarSnapshot(int $anoMax=2026): array {
        $path = dirname(__DIR__).'/instalacao/temporis_final_snapshot.json';
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Snapshot histórico local ausente. Extraia o ZIP inteiro novamente.');
        }
        $raw = file_get_contents($path);
        if ($raw === false) throw new RuntimeException('Falha na leitura do snapshot histórico.');
        $content = json_decode($raw,true,512,JSON_THROW_ON_ERROR);
        if (!is_array($content) || !isset($content['candidatos'],$content['criterios'])
            || !is_array($content['candidatos']) || !is_array($content['criterios'])
            || count($content['candidatos']) !== 800 || count($content['criterios']) !== 8800) {
            throw new RuntimeException('Exportação histórica incompleta ou mal formatada.');
        }
        $criteria = [];
        foreach ($content['criterios'] as $r) {
            if (!isset($r['id_extracao'],$r['codigo'])) continue;
            $criteria[(string)$r['id_extracao']][(string)$r['codigo']]=$r;
        }
        return self::processar($content['candidatos'],$criteria,$anoMax);
    }

    /** Faz somente SELECT. A inclusão exploratória não altera status, escopo ou notas. */
    public static function carregar(PDO $pdo,int $anoMax=2026): array {
        $candidatos=$pdo->query('SELECT id_extracao,titulo_proposto,ano_original,ano_validado,analise_historica_json,estado_revisao_humana,homologado,categoria FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        $criterios=[];
        foreach($pdo->query('SELECT id_extracao,codigo,nota,situacao,justificativa FROM cur_criterios') as $r) {
            $criterios[(string)$r['id_extracao']][(string)$r['codigo']]=$r;
        }
        return self::processar($candidatos,$criterios,$anoMax);
    }
    /** Núcleo determinístico testável sem conexão de rede ou banco. */
    public static function processar(array $candidatos,array $criterios,int $anoMax=2026): array {
        $out=[];
        $audit=['total'=>count($candidatos),'rejeitados'=>0,'sem_11_notas'=>0,'sem_data'=>0,'futuros'=>0,'elegiveis'=>0,'notas_provisorias'=>0,'sem_evidencia'=>0,'datas_extraidas'=>0];
        foreach($candidatos as $r){
            if((string)$r['estado_revisao_humana']==='REJEITADO'){$audit['rejeitados']++;continue;}
            $id=(string)$r['id_extracao'];$scores=$criterios[$id]??[];
            $all=true;$sums=[];
            foreach([self::IMP,self::CON,self::FUN] as $j=>$group){
                $sum=0;
                foreach($group as $code){
                    $n=$scores[$code]['nota']??null;
                    if($n===null || !preg_match('/^[0-3]$/D',(string)$n)){$all=false;break;}
                    $sum+=(int)$n;
                }
                $sums[$j]=$sum;
            }
            if(!$all){$audit['sem_11_notas']++;continue;}
            $data=CuradoriaDatacao::dataParaCalculo($r,true);
            if($data===null){$audit['sem_data']++;continue;}
            $year=(int)$data['ano'];
            if($year>$anoMax){$audit['futuros']++;continue;}
            $I=1+9*$sums[0]/12;$C=$sums[1]/12;$F=1+.5*$sums[2]/9;
            $u=$I/10;$f=$F/1.5;
            $sem=false;$prov=false;
            foreach($scores as $c){
                if(stripos((string)($c['justificativa']??''),'SEM_EVIDENCIA')!==false || (string)($c['situacao']??'')==='SEM_EVIDENCIA')$sem=true;
                if((string)($c['situacao']??'')==='PROVISORIA_SEM_EVIDENCIA')$prov=true;
            }
            $analise=(string)($r['analise_historica_json']??'');
            if(str_contains($analise,'nota_minima_operacional'))$prov=true;
            $extra=(string)$data['origem']==='extraida_nao_validada';
            $audit['elegiveis']++;$audit['notas_provisorias']+=(int)$prov;
            $audit['sem_evidencia']+=(int)$sem;$audit['datas_extraidas']+=(int)$extra;
            $out[]=[
                'id'=>$id,'titulo'=>(string)$r['titulo_proposto'],'year'=>$year,
                'year_from'=>(int)$data['inicio'],'year_to'=>(int)$data['fim'],
                'aprovado'=>(string)$r['estado_revisao_humana']==='APROVADO',
                'homologado'=>(int)$r['homologado']===1 && (string)$r['estado_revisao_humana']==='HOMOLOGADO',
                'ano_validado'=>!$extra,'ano_extraido'=>$extra,'sem_evidencia'=>$sem,'provisorio'=>$prov,
                'contexto'=>in_array((string)$r['categoria'],['CONTEXTO_HISTORICO','ESCOPO_DUVIDOSO'],true),
                'I'=>$I,'C'=>$C,'F'=>$F,
                'pesos'=>[
                    'quadratica'=>$u*$u*$C*$F,
                    'linear'=>$u*$C*$F,
                    'geometrica'=>pow(max(0.,$u*$C*$f),1/3),
                    'media'=>($u+$C+$f)/3,
                ],
            ];
        }
        return ['pontos'=>$out,'audit'=>$audit];
    }
    public static function filtrar(array $points,string $coorte): array {
        if(!isset(self::COORTES[$coorte]))throw new InvalidArgumentException('Conjunto inválido');
        return array_values(array_filter($points,static function(array $p)use($coorte):bool{
            return match($coorte){
                'aprovados'=>$p['aprovado'], 'validada'=>$p['ano_validado'],
                'aprovados_validados'=>$p['aprovado'] && $p['ano_validado'],
                'sem_sem_evidencia'=>!$p['sem_evidencia'],
                'sem_provisorias'=>!$p['provisorio'],
                'sem_contextos'=>!$p['contexto'],
                'homologada'=>$p['homologado'],
                default=>true,
            };
        }));
    }
    public static function linha(array $points,string $weight):array {
        if(!isset(self::PESOS[$weight]))throw new InvalidArgumentException('Peso inválido');
        $sum=[];$count=[];
        foreach($points as $p){$y=(int)$p['year'];$sum[$y]=($sum[$y]??0)+(float)$p['pesos'][$weight];$count[$y]=($count[$y]??0)+1;}
        ksort($sum,SORT_NUMERIC);ksort($count,SORT_NUMERIC);
        return ['anos'=>array_keys($sum),'anos_peso'=>$sum,'n_anos'=>$count,'total'=>array_sum($sum)];
    }
    public static function k(array $linha,int $ano):float {
        $sum=0.;foreach($linha['anos_peso'] as $y=>$v){if((int)$y>$ano)break;$sum+=(float)$v;}return $sum;
    }
    public static function indice(float $k,float $base,string $escala='empirica'): ?float {
        if(!isset(self::ESCALAS[$escala]))throw new InvalidArgumentException('Escala inválida');
        if($base<=0 || !is_finite($k) || $k<0)return null;
        $ratio=$k/$base;
        return match($escala){
            'log'=>100*log1p($k)/log1p($base),
            'pot065'=>100*pow($ratio,.65),
            'pot125'=>100*pow($ratio,1.25),
            default=>100*$ratio,
        };
    }
    public static function periodo(array $points,string $weight,int $ini,int $fim):array {
        $sum=0.;$n=0;$prov=0;$date=0;
        foreach($points as $p)if($p['year']>=$ini&&$p['year']<$fim){$sum+=$p['pesos'][$weight];$n++;$prov+=(int)$p['provisorio'];$date+=(int)$p['ano_extraido'];}
        return ['de'=>$ini,'ate'=>$fim-1,'quantidade'=>$n,'incremento'=>$sum,'taxa'=>$sum/($fim-$ini),'provisorios'=>$prov,'datas_extraidas'=>$date];
    }
    private static function saturado(array $linha,int $cut,int $h,float $k0,string $tipo):?float {
        $xs=[];$ys=[];
        for($t=$cut-250;$t<=$cut;$t+=25){$k=self::k($linha,$t);if($k<=0)return null;$xs[]=$t-$cut;$ys[]=$k;}
        if(count(array_unique(array_map(static fn($x)=>round($x,5),$ys)))<5)return null;
        $best=null;$bestError=INF;
        // Capacidade L escolhida somente pelo histórico (grade pré-definida).
        foreach([1.05,1.1,1.2,1.4,1.7,2.1,2.8,4.,6.,10.,20.,40.] as $multiple){
            $L=$k0*$multiple;$zx=[];
            foreach($ys as $v){$q=$v/$L;if($q<=0||$q>=1)continue;
                $zx[]=$tipo==='logistico'?log($q/(1-$q)):log(-log($q));
            }
            if(count($zx)!==count($xs))continue;
            $xm=array_sum($xs)/count($xs);$zm=array_sum($zx)/count($zx);
            $cov=0.;$den=0.;foreach($xs as $j=>$x){$cov+=($x-$xm)*($zx[$j]-$zm);$den+=($x-$xm)**2;}
            if($den<=0)continue;$slope=$cov/$den;$intercept=$zm-$slope*$xm;
            if(($tipo==='logistico'&&$slope<=0)||($tipo==='gompertz'&&$slope>=0))continue;
            $err=0.;foreach($xs as $j=>$x){$z=$intercept+$slope*$x;
                $pred=$tipo==='logistico'?$L/(1+exp(max(-100.,min(100.,-$z)))):$L*exp(-exp(max(-100.,min(100.,$z))));
                $err+=($pred-$ys[$j])**2;
            }
            if($err<$bestError){$bestError=$err;$best=[$L,$slope,$intercept];}
        }
        if($best===null)return null;
        [$L,$slope,$z]=$best;$v=$z+$slope*$h;
        $p=$tipo==='logistico'?$L/(1+exp(max(-100.,min(100.,-$v)))):$L*exp(-exp(max(-100.,min(100.,$v))));
        return is_finite($p)?max($k0,$p):null;
    }
    private static function impeto(array $linha,int $cut,int $h,float $k0):?float {
        // Ajusta α, β em incrementos de 20 anos, inclusive intervalos com taxa zero.
        $data=[];for($a=$cut-200;$a<$cut;$a+=20){$k=self::k($linha,$a);$v=(self::k($linha,$a+20)-$k)/20;if($k>0)$data[]=[$k,$v];}
        if(count($data)<6)return null;
        $logs=array_map(static fn($r)=>log($r[0]),$data);if(max($logs)-min($logs)<.2)return null;
        $scale=array_sum(array_column($data,0))/count($data);
        $best=null;$mse=INF;
        for($i=-10;$i<=25;$i++){
            $beta=$i/10.;$xx=[];$dot=0.;$norm=0.;
            foreach($data as [$k,$v]){$q=pow($k/$scale,$beta);$xx[]=$q;$dot+=$q*$v;$norm+=$q*$q;}
            if($norm<1e-15)continue;$alpha=max(0.,$dot/$norm);
            $err=0.;foreach($data as $j=>$pair)$err+=($pair[1]-$alpha*$xx[$j])**2;
            if($err<$mse){$mse=$err;$best=[$beta,$alpha];}
        }
        if($best===null)return null;
        [$beta,$alpha]=$best;$k=$k0;
        for($j=0;$j<$h*2;$j++){
            $inc=.5*$alpha*pow(max($k,1e-12)/$scale,$beta);
            if(!is_finite($inc)||$inc<0||$k+$inc>1e15)return null;
            $k+=$inc;
        }
        return $k;
    }
    public static function projetar(array $linha,int $cut,int $h,string $modelo):?float {
        if(!isset(self::MODELOS[$modelo])||$h<1||$h>200)throw new InvalidArgumentException('Modelo ou horizonte inválido');
        $k=self::k($linha,$cut);$old=self::k($linha,$cut-50);$old100=self::k($linha,$cut-100);
        if($k<=0||$old100<=0)return null;
        $r50=max(0.,($k-$old)/50);$r100=max(0.,($k-$old100)/100);$rPrev=max(0.,($old-$old100)/50);
        $exp50=$old>0 ? $k*exp(min(80.,log($k/$old)*$h/50)):null;
        $exp100=$old100>0 ? $k*exp(min(80.,log($k/$old100)*$h/100)):null;
        $linear50=$k+$r50*$h;$linear100=$k+$r100*$h;
        if($modelo==='sem_avanco')return $k;
        if($modelo==='linear50')return $linear50;
        if($modelo==='linear100')return $linear100;
        if($modelo==='exponencial50')return $exp50;
        if($modelo==='exponencial100')return $exp100;
        if($modelo==='mediana'){$v=[$linear50,$linear100,$exp50??$linear50];sort($v);return $v[1];}
        if($modelo==='amortecida'){$d=pow(.5,1/50);return $k+$r50*(1-pow($d,$h))/(1-$d);}
        if($modelo==='aceleracao'){
            $a=($r50-$rPrev)/50;
            // Integral da velocidade max(0, v0 + a*t): estoque nunca diminui.
            $active=$a<0?min($h,max(0,-$r50/$a)):$h;
            return $k+max(0.,$r50*$active+.5*$a*$active*$active);
        }
        if($modelo==='impeto')return self::impeto($linha,$cut,$h,$k);
        if($modelo==='logistico'||$modelo==='gompertz')return self::saturado($linha,$cut,$h,$k,$modelo);
        return null;
    }
    /** Um único teste retrospectivo em um corte fixo; retorna null quando não há dados para ajustar. */
    private static function foldCorte(array $linha,string $modelo,int $h,int $cut):?array {
        $pred=self::projetar($linha,$cut,$h,$modelo);
        if($pred===null)return null;
        $kCut=self::k($linha,$cut);$actual=self::k($linha,$cut+$h)-$kCut;$growth=$pred-$kCut;
        $error=$growth-$actual;
        return ['corte'=>$cut,'alvo'=>$cut+$h,'crescimento_real'=>$actual,'crescimento_previsto'=>$growth,'erro'=>$error];
    }
    /** Agrega os folds em métricas (n, WAPE, MAE, viés). */
    private static function agregar(array $folds):array {
        $actualSum=0.;$errorSum=0.;$signed=0.;
        foreach($folds as $f){$actualSum+=$f['crescimento_real'];$errorSum+=abs($f['erro']);$signed+=$f['erro'];}
        return ['n'=>count($folds),'wape'=>$actualSum>0?100*$errorSum/$actualSum:null,
            'mae'=>$folds?$errorSum/count($folds):null,'bias'=>$folds?$signed/count($folds):null,'folds'=>$folds];
    }
    public static function validar(array $linha,string $modelo,int $h,int $ultimoAno=2026):array {
        $folds=[];
        // Origem fixa de 25 em 25 anos; não escolher cortes pelo desempenho.
        for($cut=1700;$cut+$h<=$ultimoAno;$cut+=25){
            $f=self::foldCorte($linha,$modelo,$h,$cut);
            if($f!==null)$folds[]=$f;
        }
        return self::agregar($folds);
    }
    /** Backtesting em um único corte escolhido pelo usuário (mesma métrica e mesmos dados). */
    public static function validarCorte(array $linha,string $modelo,int $h,int $cut):array {
        $f=self::foldCorte($linha,$modelo,$h,$cut);
        if($f===null)return ['n'=>0,'wape'=>null,'mae'=>null,'bias'=>null,'folds'=>[]];
        return self::agregar([$f]);
    }
    /** Cortes disponíveis para o modo de corte único (passo fixo de 25 anos). */
    public const CORTES_FIXOS = [1600,1625,1650,1675,1700,1725,1750,1775,1800,1825,1850,1875,1900,1925,1950,1975,2000];
}
