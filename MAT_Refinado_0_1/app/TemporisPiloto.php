<?php
declare(strict_types=1);
/*
 * Correcao v0.6: o teste CLI da v0.5 carregava a metodologia,
 * mas a pagina web dependia de um bootstrap que nem sempre a carregava.
 * Preferir a classe original de MAT 0.2. O fallback so roda se nao existir.
 */
if (!class_exists('RevisedMethodology', false)) {
    $revisedOfficial = __DIR__ . '/RevisedMethodology.php';
    if (is_file($revisedOfficial)) {
        require_once $revisedOfficial;
    }
}
if (!class_exists('RevisedMethodology', false)) {
    require_once __DIR__ . '/compat/RevisedMethodology_020.php';
}
/**
 * Ensaio DIDATICO: nao representa avaliacao historica, nem a serie aprovada.
 * A serie historica real segue exclusivamente RevisedMethodology::series().
 */
final class TemporisPiloto {
    public const BASE_YEAR = 1971;
    public const FIELDS = [
        'impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia',
        'conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia',
        'fund_pervasividade','fund_aprimoramento','fund_complementaridade'
    ];
    /** Eventos reais; NOTAS HIPOTETICAS para teste da matematica, sem parecer cientifico. */
    public static function examples(): array {
        return [
            ['year'=>1712,'title'=>'Maquina atmosferica de Newcomen','group'=>'Energia',
             'scores'=>[2,3,2,3, 2,2,3,3, 2,3,3]],
            ['year'=>1844,'title'=>'Demonstracao do telegrafo de Morse','group'=>'Comunicacao',
             'scores'=>[2,3,2,3, 2,2,3,3, 2,2,3]],
            ['year'=>1947,'title'=>'Demonstracao do transistor de contato pontual','group'=>'Computacao',
             'scores'=>[3,3,3,3, 2,3,3,3, 3,3,3]],
            ['year'=>1971,'title'=>'Lancamento comercial do Intel 4004','group'=>'Computacao',
             'scores'=>[3,3,3,3, 2,2,3,3, 3,3,3]],
            ['year'=>1989,'title'=>'Proposta inicial da World Wide Web','group'=>'Comunicacao',
             'scores'=>[3,3,3,3, 2,2,3,3, 3,3,3]],
            ['year'=>2012,'title'=>'Demonstracao do CRISPR-Cas9 programavel','group'=>'Biotecnologia',
             'scores'=>[2,3,2,2, 2,2,3,3, 2,3,2]],
        ];
    }
    public static function calc(array $scores, float $beta=2.0): array {
        if(count($scores)!==11) throw new InvalidArgumentException('Exatamente 11 notas obrigatorias.');
        $s=[];
        foreach($scores as $v) {
            $n=RevisedMethodology::criterion($v);
            if($n===null) throw new InvalidArgumentException('Nota ausente, nao substituir por zero.');
            $s[]=$n;
        }
        $si=array_sum(array_slice($s,0,4));
        $sc=array_sum(array_slice($s,4,4));
        $sf=array_sum(array_slice($s,8,3));
        $c=['I'=>1+9*$si/12, 'C'=>$sc/12, 'F'=>1+0.5*$sf/9];
        $c['w']=RevisedMethodology::weight($c,$beta);
        return $c;
    }
    public static function series(array $examples, int $baseYear=self::BASE_YEAR,float $beta=2.0): array {
        $points=[];
        foreach($examples as $row) {
            $year=RevisedMethodology::year($row['year']??null);
            if($year===null) throw new InvalidArgumentException('Ano invalido.');
            $components=self::calc($row['scores']??[],$beta);
            $points[]=['year'=>$year,'title'=>(string)$row['title'],'group'=>(string)($row['group']??''),
                'scores'=>$row['scores']]+$components;
        }
        usort($points,static fn($a,$b)=>($a['year']<=>$b['year']) ?: strcmp($a['title'],$b['title']));
        $k=0.;$kBase=0.;
        foreach($points as &$p) {
            $k += $p['w'];$p['k']=$k;
            if($p['year']<=$baseYear) $kBase=$k;
        }unset($p);
        foreach($points as &$p) $p['t']=$kBase>0?100*log1p($p['k'])/log1p($kBase):null;
        unset($p);
        return ['points'=>$points,'base'=>$baseYear,'beta'=>$beta,'k_base'=>$kBase,'k_total'=>$k,
            't_latest'=>$points?(end($points)['t']??null):null,'n'=>count($points)];
    }
}
