<?php
declare(strict_types=1);
/** Datação opcional: salva metadados no JSON de análise existente, sem alterar o esquema do MySQL. */
final class CuradoriaDatacao {
    public const CHAVE = 'datacao_intervalar';
    public const ANO_MINIMO = -5000000; // Inclui ferramentas pré-históricas; não implica precisão documental.
    public const ANO_MAXIMO = 2100;
    public static function anoValido(mixed $ano): bool {
        if(!is_int($ano) && !is_string($ano))return false;
        if(!preg_match('/^-?[0-9]{1,7}$/D',(string)$ano))return false;
        $n=(int)$ano;
        return $n!==0 && $n>=self::ANO_MINIMO && $n<=self::ANO_MAXIMO;
    }

    public static function analise(array $r): array {
        if(isset($r['analise']) && is_array($r['analise'])) return $r['analise'];
        $d=json_decode((string)($r['analise_historica_json']??''),true);
        return is_array($d) && !array_is_list($d) ? $d : [];
    }
    public static function intervalo(array $r): ?array {
        $a=self::analise($r);
        $d=$a[self::CHAVE]??null;
        if(!is_array($d) || !in_array($d['tipo']??'', ['incerteza','processo'],true))return null;
        foreach(['inicio','fim'] as $k)if(!isset($d[$k]) || !self::anoValido($d[$k]))return null;
        $a=(int)$d['inicio'];$b=(int)$d['fim'];
        if($a===0 || $b===0 || $a < self::ANO_MINIMO || $b > self::ANO_MAXIMO || $a >= $b)return null;
        if(strlen(trim((string)($d['justificativa']??'')))<30 || trim((string)($d['fonte_id']??''))==='')return null;
        return $d;
    }
    public static function temData(array $r): bool {
        $v=$r['ano_validado']??null;
        if(self::anoValido($v)) return true;
        return self::intervalo($r)!==null;
    }
    public static function conferirFonte(array $r): bool {
        $d=self::intervalo($r);
        if($d===null)return true;
        foreach(($r['fontes']??[]) as $s)if((string)($s['fonte_id']??'')===(string)$d['fonte_id'])return true;
        return false;
    }
    public static function validarEntrada(array $d,array $fontes): array {
        $inicio=trim((string)($d['intervalo_inicio']??''));
        $fim=trim((string)($d['intervalo_fim']??''));
        if(!preg_match('/^-?[0-9]{1,7}$/D',$inicio) || !preg_match('/^-?[0-9]{1,7}$/D',$fim))throw new InvalidArgumentException('Informe os dois limites inteiros do intervalo.');
        $a=(int)$inicio;$b=(int)$fim;
        if($a===0 || $b===0 || $a < self::ANO_MINIMO || $b>self::ANO_MAXIMO || $a >= $b)throw new InvalidArgumentException('Intervalo inválido: início deve ser menor que fim, sem ano zero (limites -5.000.000 e 2100).');
        $tipo=trim((string)($d['intervalo_tipo']??''));
        if(!in_array($tipo,['incerteza','processo'],true))throw new InvalidArgumentException('Indique se é incerteza de datação ou processo contínuo.');
        $just=trim((string)($d['intervalo_justificativa']??''));
        if(strlen($just)<30 || strlen($just)>2000)throw new InvalidArgumentException('Explique em 30 a 2.000 caracteres por que este intervalo é sustentado pela fonte.');
        $fid=trim((string)($d['intervalo_fonte_id']??''));
        if($fid==='' || !in_array($fid,array_column($fontes,'fonte_id'),true))throw new InvalidArgumentException('Selecione uma fonte já cadastrada no dossiê para sustentar o intervalo.');
        return ['tipo'=>$tipo,'inicio'=>$a,'fim'=>$b,'justificativa'=>$just,'fonte_id'=>$fid];
    }
    /** O ano representativo é uma convenção de simulação, não uma data histórica exata. */
    public static function anoRepresentativo(array $d,string $cenario='medio'): int {
        $ano=match($cenario){'inicio'=>(int)$d['inicio'],'fim'=>(int)$d['fim'],default=>(int)floor(((int)$d['inicio']+(int)$d['fim'])/2)};
        return $ano===0?1:$ano;
    }
    public static function dataParaCalculo(array $r,bool $permitirExtraida=false,string $cenario='medio'): ?array {
        $v=$r['ano_validado']??null;
        if(self::anoValido($v))
            return ['ano'=>(int)$v,'inicio'=>(int)$v,'fim'=>(int)$v,'origem'=>'validada'];
        $d=self::intervalo($r);
        if($d!==null) return ['ano'=>self::anoRepresentativo($d,$cenario),'inicio'=>(int)$d['inicio'],'fim'=>(int)$d['fim'],'origem'=>$d['tipo']];
        $v=$r['ano_original']??null;
        if($permitirExtraida && self::anoValido($v))
            return ['ano'=>(int)$v,'inicio'=>(int)$v,'fim'=>(int)$v,'origem'=>'extraida_nao_validada'];
        return null;
    }
    public static function texto(array $r): string {
        $d=self::intervalo($r);
        if($d===null)return 'Sem intervalo documentado';
        $f=static fn(int $n):string=>abs($n).($n<0?' a.C.':' d.C.');
        return $f((int)$d['inicio']).' a '.$f((int)$d['fim']).' ('.($d['tipo']==='processo'?'processo contínuo':'data incerta').')';
    }
}
