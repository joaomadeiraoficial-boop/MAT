<?php
declare(strict_types=1);
require_once __DIR__.'/TemporisFinal.php';
/**
 * Contrato científico vigente (2026):
 * 1. K(t) bruto é o índice. Ano-base não integra o cálculo.
 * 2. Evolução = incrementos e velocidade por ano, na mesma coorte.
 * 3. Previsões treinam somente com anos <= corte; confronto usa períodos futuros do corte.
 * 4. WAPE dos INCREMENTOS é erro retrospectivo, NÃO probabilidade de acerto
 *    nem intervalo de confiança para projeções ainda não observadas.
 * Leitura apenas; não concede aprovação, não altera nota ou status.
 */
final class TemporisMetodologia2026 {
    public const METODOS_COMPARAVEIS = [
        'linear50' => 'Linear local · 50 anos',
        'exponencial50' => 'Exponencial local · 50 anos',
        'linear100' => 'Linear local · 100 anos',
        'exponencial100' => 'Exponencial local · 100 anos',
    ];
    public const HORIZONTES = [10,25,50,100];
    public const ANOS_HISTORICOS = [1700,1750,1800,1850,1900,1950,2000,2026];

    public static function historico(array $linha,array $anos=self::ANOS_HISTORICOS):array {
        $out=[];$prevYear=null;$prev=null;
        foreach($anos as $year){
            $value=TemporisFinal::k($linha,(int)$year);
            $span=$prevYear===null?null:(int)$year-$prevYear;
            if($span!==null && $span<=0)throw new InvalidArgumentException('Anos fora de ordem.');
            $inc=$prev===null?null:$value-$prev;
            $out[]=['ano'=>(int)$year,'k'=>$value,'delta'=>$inc,'duracao'=>$span,
                'pontos_por_ano'=>$span===null?null:$inc/$span];
            $prev=$value;$prevYear=(int)$year;
        }
        return $out;
    }
    public static function velocidade(array $linha,int $ano,int $janela):float {
        if($janela<1 || $janela>1000)throw new InvalidArgumentException('Janela inválida');
        return (TemporisFinal::k($linha,$ano)-TemporisFinal::k($linha,$ano-$janela))/$janela;
    }
    public static function confronto(array $linha,int $corte,int $horizonte,string $modelo,int $limiteObservado=2026):array {
        if(!in_array($horizonte,self::HORIZONTES,true))throw new InvalidArgumentException('Horizonte indisponível');
        $kCorte=TemporisFinal::k($linha,$corte);
        $previsto=TemporisFinal::projetar($linha,$corte,$horizonte,$modelo);
        $alvo=$corte+$horizonte;
        $hasObs=$alvo<=$limiteObservado;
        $obs=$hasObs?TemporisFinal::k($linha,$alvo):null;
        $dp=$previsto===null?null:$previsto-$kCorte;
        $do=$obs===null?null:$obs-$kCorte;
        $e=($dp===null||$do===null)?null:$dp-$do;
        return ['corte'=>$corte,'alvo'=>$alvo,'horizonte'=>$horizonte,'modelo'=>$modelo,
            'k_corte'=>$kCorte,'previsto'=>$previsto,'observado'=>$obs,
            'delta_previsto'=>$dp,'delta_observado'=>$do,'erro_delta'=>$e,
            'erro_absoluto'=>$e===null?null:abs($e),
            'erro_pct_delta'=>$e===null||$do<=0?null:100*abs($e)/$do,
            'erro_pct_nivel'=>$e===null||$obs<=0?null:100*abs($e)/$obs,
            'status'=>$previsto===null?'dados_insuficientes':($hasObs?'observado_no_catalogo':'futuro_sem_erro')];
    }
    /** Fold individual avaliado em momento no qual o alvo já foi observado no catálogo. */
    public static function validacao(array $linha,string $modelo,int $horizonte,int $limite=2026):array {
        return TemporisFinal::validar($linha,$modelo,$horizonte,$limite);
    }
    /**
     * Comparação JUSTA: os métodos concorrentes são pontuados nos MESMOS cortes.
     * Algoritmos com dados insuficientes não recebem erro zero ou vantagem por amostra menor.
     * A classificação ainda é pós-teste, portanto exploratória.
     */
    public static function compararMesmosCortes(array $linha,int $horizonte,int $limite=2026):array {
        if(!in_array($horizonte,self::HORIZONTES,true))throw new InvalidArgumentException('Horizonte indisponível');
        $map=[];
        foreach(self::METODOS_COMPARAVEIS as $id=>$name){
            $v=self::validacao($linha,$id,$horizonte,$limite);
            $map[$id]=[];
            foreach($v['folds'] as $fold)$map[$id][$fold['corte']]=$fold;
        }
        $common=null;
        foreach($map as $folds){
            $cuts=array_keys($folds);
            $common=$common===null?$cuts:array_values(array_intersect($common,$cuts));
        }
        sort($common,SORT_NUMERIC);
        $out=[];
        foreach(self::METODOS_COMPARAVEIS as $id=>$label){
            $den=0.0;$num=0.0;$folds=[];
            foreach($common as $c){
                $fold=$map[$id][$c];
                $folds[]=$fold;$den+=(float)$fold['crescimento_real'];$num+=abs((float)$fold['erro']);
            }
            $out[$id]=['nome'=>$label,'n'=>count($folds),'wape'=>$den>0?100*$num/$den:null,
                'mae'=>$folds?$num/count($folds):null,'folds'=>$folds];
        }
        return ['cortes'=>$common,'n'=>count($common),'modelos'=>$out,
            'nota'=>'Ranking exploratório após observar dados; não é validação externa nem percentual garantido.'];
    }
    /** Confrontos históricos com seleção progressiva: o próximo corte não informa o modelo selecionado. */
    public static function selecaoProgressiva(array $linha,int $horizonte,int $limite=2026,int $minTreino=3):array {
        if(!in_array($horizonte,self::HORIZONTES,true))throw new InvalidArgumentException('Horizonte indisponível');
        $all=self::compararMesmosCortes($linha,$horizonte,$limite);
        $results=[];
        foreach($all['cortes'] as $cut){
            $best=null;$bestScore=INF;
            foreach($all['modelos'] as $id=>$m){
                $past=array_values(array_filter($m['folds'],static fn($r)=>$r['alvo']<=$cut));
                if(count($past)<$minTreino)continue;
                $den=0.0;$num=0.0;
                foreach($past as $f){$den+=(float)$f['crescimento_real'];$num+=abs((float)$f['erro']);}
                if($den<=0)continue;
                $score=$num/$den;
                if($score<$bestScore){$best=$id;$bestScore=$score;}
            }
            if($best===null)continue;
            foreach($all['modelos'][$best]['folds'] as $f){if($f['corte']===$cut){$results[]=$f+['modelo_selecionado'=>$best];break;}}
        }
        $sumErr=0.0;$sumActual=0.0;
        foreach($results as $f){$sumErr+=abs((float)$f['erro']);$sumActual+=(float)$f['crescimento_real'];}
        return ['n'=>count($results),'wape'=>$sumActual>0?100*$sumErr/$sumActual:null,'folds'=>$results];
    }
}
