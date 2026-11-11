<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';
/** Dupla codificação cega com escalas 0–3; não cria notas nem edita o MAT. */
final class ConcordanciaAvaliadores {
    public static function carregarCsv(string $path): array {
        $file=@fopen($path,'rb');
        if(!$file)throw new InvalidArgumentException('Arquivo CSV não encontrado: '.$path);
        try {
            $first=fgets($file);rewind($file);
            $delimiter=substr_count((string)$first,';')>substr_count((string)$first,',')?';':',';
            $header=fgetcsv($file,0,$delimiter,'"','');
            if(!$header)throw new InvalidArgumentException('CSV sem cabeçalho.');
            $header=array_map(static fn($k)=>trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$k)),$header);
            if(count(array_unique($header))!==count($header))throw new InvalidArgumentException('Colunas duplicadas.');
            foreach(array_merge(['id_extracao'],array_keys(CuradoriaRodadas::CRITERIOS)) as $needed)
                if(!in_array($needed,$header,true))throw new InvalidArgumentException('Faltou coluna: '.$needed);
            $out=[];$line=1;
            while(($parts=fgetcsv($file,0,$delimiter,'"',''))!==false){
                $line++;
                if(count($parts)===1 && trim((string)$parts[0])==='')continue;
                if(count($parts)!==count($header))throw new InvalidArgumentException('CSV linha '.$line.' com quantidade irregular de campos.');
                $row=array_combine($header,$parts);
                $id=strtoupper(trim((string)$row['id_extracao']));
                if(!CuradoriaRodadas::validId($id))throw new InvalidArgumentException('ID inválido na linha '.$line);
                if(isset($out[$id]))throw new InvalidArgumentException('ID repetido na linha '.$line.': '.$id);
                $scores=[];
                foreach(CuradoriaRodadas::CRITERIOS as $code=>$label){
                    $s=trim((string)$row[$code]);
                    if($s!=='' && !preg_match('/^[0-3]$/D',$s))throw new InvalidArgumentException('Nota fora da escala 0–3 na linha '.$line.': '.$code);
                    $scores[$code]=$s===''?null:(int)$s;
                }
                $out[$id]=$scores;
            }
            return $out;
        } finally {fclose($file);}
    }
    /** Kappa ponderado quadrático. Se não existe discordância esperada, fica indefinido. */
    public static function compararNotas(array $pares): array {
        $n=count($pares);
        if($n===0)return ['n'=>0,'concordancia_exata'=>null,'erro_absoluto_medio'=>null,'kappa_quadratico'=>null];
        $p=[0.,0.,0.,0.];$q=[0.,0.,0.,0.];$discordancia=0.;$iguais=0;$abs=0.;
        foreach($pares as [$a,$b]){
            if(!is_int($a)||!is_int($b)||$a<0||$a>3||$b<0||$b>3)
                throw new InvalidArgumentException('Notas de comparação fora da escala.');
            $p[$a]++;$q[$b]++;
            $discordancia+=(($a-$b)/3)**2;
            $abs+=abs($a-$b);
            if($a===$b)$iguais++;
        }
        $de=$do=0.;
        for($i=0;$i<=3;$i++)for($j=0;$j<=3;$j++)$de+=(($i-$j)/3)**2*($p[$i]/$n)*($q[$j]/$n);
        $do=$discordancia/$n;
        return ['n'=>$n,'concordancia_exata'=>$iguais/$n,'erro_absoluto_medio'=>$abs/$n,
            'kappa_quadratico'=>$de<=1e-12?null:1-$do/$de];
    }
    public static function analisar(array $a,array $b): array {
        $both=array_values(array_intersect(array_keys($a),array_keys($b)));
        sort($both,SORT_STRING);
        $criteria=[];$pairsAll=[];
        foreach(CuradoriaRodadas::CRITERIOS as $code=>$label){
            $pairs=[];
            foreach($both as $id){
                $x=$a[$id][$code]??null;$y=$b[$id][$code]??null;
                if($x===null || $y===null)continue;
                $pairs[]=[$x,$y];$pairsAll[]=[$x,$y];
            }
            $criteria[$code]=['nome'=>$label]+self::compararNotas($pairs);
        }
        return ['dossies_comuns'=>count($both),'somente_primeiro'=>count($a)-count($both),
            'somente_segundo'=>count($b)-count($both),'criterios'=>$criteria,
            'geral_descritivo'=>self::compararNotas($pairsAll),
            'alerta'=>'O resultado conjunto não fornece observações independentes: várias rubricas pertencem ao mesmo dossiê. Análise exploratória, não validade histórica.'];
    }
}
