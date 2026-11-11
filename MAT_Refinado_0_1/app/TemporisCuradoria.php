<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';
require_once __DIR__.'/CuradoriaRevisao.php';
require_once __DIR__.'/CuradoriaDatacao.php';

/** Fórmula Têmporis 0.2 (β=2) e cenários experimentais, sem aprovações automáticas. */
final class TemporisCuradoria {
    public const VERSION='reformulada-0.2-beta2-log-intervalos';
    public const BETA=2.0;
    public const ANOS_CENARIO=[800,1000,1500,1800,1920,2000,2026];

    public static function calcular(array $candidatos, array $criterios, array $fontes, int $anoBase): array {
        $pros=[];$ofic=[];$ens=[];$decisoes=[];$incompletos=0;$semData=0;$bloqueados=0;
        foreach($candidatos as $r){
            $id=(string)($r['id_extracao']??'');
            if(($r['estado_revisao_humana']??'')==='REJEITADO')continue;
            $scores=$criterios[$id]??[];$src=$fontes[$id]??[];
            $mem=CuradoriaRodadas::memory($scores,$src);
            // Decisões humanas independem de estar pontuado para a curva.
            // Localizar apenas datas VALIDADAS: jamais converter ano extraído em confirmação.
            $estado=(string)($r['estado_revisao_humana']??'');
            if(in_array($estado,['APROVADO','HOMOLOGADO'],true)){
                $dataDecidida=CuradoriaDatacao::dataParaCalculo($r,false);
                $decisoes[]=['id'=>$id,'title'=>(string)($r['titulo_proposto']??''),
                    'estado'=>$estado,'homologado'=>(int)($r['homologado']??0),
                    'year'=>$dataDecidida===null?null:(int)$dataDecidida['ano'],
                    'year_inicio'=>$dataDecidida===null?null:(int)$dataDecidida['inicio'],
                    'year_fim'=>$dataDecidida===null?null:(int)$dataDecidida['fim'],
                    'notas_completas'=>(bool)$mem['completa']];
            }
            if(!$mem['completa']){$incompletos++;continue;}
            $data=CuradoriaDatacao::dataParaCalculo($r,false);
            $dataTeste=CuradoriaDatacao::dataParaCalculo($r,true);
            // O ensaio matemático aceita data extraída, MAS não a confunde com validação.
            if($dataTeste!==null)$ens[]=self::ponto($r,$mem,$dataTeste);
            if($data===null){$semData++;continue;}
            $p=self::ponto($r,$mem,$data);
            $pros[]=$p;
            $review=$r+['criterios'=>$scores,'fontes'=>$src,
                'pendencias'=>CuradoriaRodadas::decodeArray((string)($r['pendencias_json']??'')),
                'duplicatas'=>CuradoriaRodadas::decodeArray((string)($r['duplicatas_json']??''))];
            if((int)($r['homologado']??0)===1 && ($r['estado_revisao_humana']??'')==='HOMOLOGADO' && !CuradoriaRevisao::impedimentos($review,CuradoriaPolitica::RIGOROSO))$ofic[]=$p;
            else $bloqueados++;
        }
        $previa=self::serie($pros,$anoBase);
        $homologada=self::serie($ofic,$anoBase);
        $ensaio=self::serie($ens,$anoBase);
        return ['previa'=>$previa,'homologada'=>$homologada,'ensaio'=>$ensaio,
            'sensibilidade'=>self::compararDatas($ens,$anoBase),
            'sensibilidade_beta'=>self::compararBeta($ens,$anoBase),
            'sensibilidade_exclusao'=>self::sensibilidadeExclusao($ens,$anoBase),
            'sensibilidade_exclusao_homologada'=>self::sensibilidadeExclusao($ofic,$anoBase),
            'incompletos'=>$incompletos,'sem_data'=>$semData,'ainda_nao_homologados'=>$bloqueados,
            'decisoes_humanas'=>$decisoes,'metodo'=>self::VERSION];
    }
    private static function ponto(array $r,array $mem,array $data): array {
        $origem=(string)$data['origem'];
        return ['id'=>(string)($r['id_extracao']??''),'title'=>(string)($r['titulo_proposto']??''),
            'year'=>(int)$data['ano'],'year_inicio'=>(int)$data['inicio'],'year_fim'=>(int)$data['fim'],
            'origem_data'=>$origem,'provisorio_ano'=>$origem==='extraida_nao_validada',
            'score'=>(float)$mem['peso'],'I'=>(float)$mem['I'],'C'=>(float)$mem['C'],'F'=>(float)$mem['F'],
            'estado_revisao_humana'=>(string)($r['estado_revisao_humana']??''),
            'homologado'=>(int)($r['homologado']??0),'notas_provisorias'=>(int)($mem['notas_provisorias']??0)];
    }
    public static function serie(array $points,int $anoBase):array {
        usort($points,static fn($a,$b)=>($a['year']<=>$b['year']) ?: strcmp($a['id'],$b['id']));
        $k=0.;$kBase=0.;$totalAnoInseguro=0;$totalIntervalo=0;$eventosNotasProvisorias=0;
        foreach($points as &$p){
            $k+=$p['score'];$p['k']=$k;
            if($p['year']<=$anoBase)$kBase=$k;
            if(!empty($p['provisorio_ano']))$totalAnoInseguro++;
            if((int)($p['notas_provisorias']??0)>0)$eventosNotasProvisorias++;
            if(($p['year_inicio']??$p['year'])!==($p['year_fim']??$p['year']))$totalIntervalo++;
        }unset($p);
        foreach($points as &$p)$p['t']=$kBase>0.?100.*log1p($p['k'])/log1p($kBase):null;unset($p);
        return ['series'=>$points,'k_total'=>$k,'k_base'=>$kBase,'t_latest'=>$kBase>0.&&$points?end($points)['t']:null,
            'reference_year'=>$anoBase,'included'=>count($points),'datas_provisorias'=>$totalAnoInseguro,'datas_intervalares'=>$totalIntervalo,'marcos_notas_provisorias'=>$eventosNotasProvisorias];
    }
    private static function kAte(array $points,int $limite,string $cenario,float $beta=2.):float {
        $total=0.;
        foreach($points as $p){
            $ano=match($cenario){'inicio'=>$p['year_inicio']??$p['year'],'fim'=>$p['year_fim']??$p['year'],default=>$p['year']};
            if($ano<=$limite)$total+=pow($p['I']/10,$beta)*$p['C']*$p['F'];
        }
        return $total;
    }
    /** São cenários, NÃO limites de confiança estatísticos. Não altera a fórmula. */
    public static function compararDatas(array $points,int $anoBase):array {
        $anos=array_values(array_unique(array_merge(self::ANOS_CENARIO,[$anoBase])));sort($anos,SORT_NUMERIC);
        $out=[];
        foreach(['inicio','medio','fim'] as $cenario){
            $kb=self::kAte($points,$anoBase,$cenario);
            $linha=[];
            foreach($anos as $ano){
                $k=self::kAte($points,$ano,$cenario);
                $linha[$ano]=$kb>0?100*log1p($k)/log1p($kb):null;
            }
            $out[$cenario]=['k_base'=>$kb,'valores'=>$linha];
        }
        return ['anos'=>$anos,'cenarios'=>$out];
    }
    /** Teste básico de sensibilidade de β, SEM substituir β=2 oficial. */
    public static function compararBeta(array $points,int $anoBase):array {
        $out=[];
        foreach([1.,2.,3.] as $beta){
            $kb=self::kAte($points,$anoBase,'medio',$beta);
            $kTotal=0.;foreach($points as $p)$kTotal+=pow($p['I']/10,$beta)*$p['C']*$p['F'];
            $out[(string)(int)$beta]=['k_base'=>$kb,'k_total'=>$kTotal,
                't_ultimo'=>$kb>0?100*log1p($kTotal)/log1p($kb):null];
        }
        return $out;
    }
    /** Leave-one-out: impacto de retirar cada marco, sem ajustar pontuações nem datas. */
    public static function sensibilidadeExclusao(array $points,int $anoBase): array {
        $original=self::serie($points,$anoBase);
        $base=$original['t_latest'];
        if($base===null || count($points)<2)return ['calculavel'=>false,'quantidade'=>count($points),
            'referencia'=>$base,'maior_variacao'=>null,'marcos'=>[]];
        $variacoes=[];
        foreach($points as $i=>$point){
            $out=$points;array_splice($out,$i,1);
            $novo=self::serie($out,$anoBase);
            $valor=$novo['t_latest'];
            $variacoes[]=['id'=>(string)($point['id']??''),'ano'=>(int)$point['year'],
                'peso'=>(float)$point['score'],'indice_sem_marco'=>$valor,
                'variacao'=>$valor===null?null:($valor-$base)];
        }
        usort($variacoes,static function(array $a,array $b):int{
            return (($b['variacao']===null?INF:abs($b['variacao'])) <=> ($a['variacao']===null?INF:abs($a['variacao'])))
                ?:strcmp($a['id'],$b['id']);
        });
        $validas=array_values(array_filter(array_column($variacoes,'variacao'),static fn($v)=>$v!==null));
        return ['calculavel'=>true,'quantidade'=>count($points),'referencia'=>$base,
            'maior_variacao'=>$validas?max(array_map('abs',$validas)):null,
            'indefinidos'=>count($points)-count($validas),
            'marcos'=>array_slice($variacoes,0,10)];
    }

    public static function consultar(PDO $pdo,int $anoBase):array {
        $c=$pdo->query('SELECT id_extracao,titulo_proposto,ano_original,ano_validado,analise_historica_json,estado_revisao_humana,homologado,pendencias_json,duplicatas_json FROM cur_candidatos')->fetchAll(PDO::FETCH_ASSOC);
        $scores=[];
        foreach($pdo->query('SELECT id_extracao,codigo,nota,situacao,justificativa,fontes_ids_json FROM cur_criterios') as $row)$scores[$row['id_extracao']][$row['codigo']]=$row;
        $sources=[];
        foreach($pdo->query('SELECT id_extracao,fonte_id FROM cur_fontes') as $src)$sources[$src['id_extracao']][]=$src;
        return self::calcular($c,$scores,$sources,$anoBase);
    }
}
