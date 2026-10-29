<?php
declare(strict_types=1);
/** Sugestoes para marcos do acervo; nunca escreve no MySQL nem confirma avaliacao. */
final class PontuacaoAcervoIA {
    public const VERSION='mat-pontuacao-acervo-1.1';

    public static function directory(bool $create=false): string {
        $env=getenv('MAT_TRIAGEM_PRIVADA');
        $root=is_string($env)&&trim($env)!=='' ? rtrim($env,"\\/")
            : dirname((string)(realpath(MAT_ROOT) ?: MAT_ROOT),4).DIRECTORY_SEPARATOR.'MAT_IA_TRIAGEM_PRIVADA';
        $dir=$root.DIRECTORY_SEPARATOR.'acervo';
        if($create && !is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) {
            throw new RuntimeException('Nao foi possivel criar pasta privada: '.$dir);
        }
        return $dir;
    }

    public static function filepath(int $id): string {
        if($id<1)throw new InvalidArgumentException('ID invalido');
        return self::directory().DIRECTORY_SEPARATOR.'marco_'.$id.'.json';
    }

    public static function load(int $id): ?array {
        if($id<1)return null;
        $file=self::filepath($id);
        if(!is_file($file))return null;
        $data=json_decode((string)file_get_contents($file),true);
        return is_array($data)?$data:null;
    }

    public static function save(int $id,array $data): void {
        self::directory(true);
        $path=self::filepath($id);
        $tmp=$path.'.tmp-'.getmypid();
        $json=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
        if(file_put_contents($tmp,$json,LOCK_EX)===false)throw new RuntimeException('Falha ao gravar proposta');
        if(!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Falha ao finalizar proposta');}
    }

    public static function usableSources(array $sources): array {
        return array_values(array_filter($sources,static fn(array $s)=>strlen(trim((string)($s['trecho']??'')))>=20));
    }

    public static function proposal(array $raw, int $linkedCount): array {
        $criteria=[];$assessment=[];$count=0;
        foreach(RevisedMethodology::ALL as $key=>$label){
            $item=is_array($raw['criterios'][$key]??null)?$raw['criterios'][$key]:[];
            $note=$item['nota']??null;
            $quote=trim((string)($item['citacao']??''));
            $ok=is_int($note) && $note>=0 && $note<=3 && strlen($quote)>=20;
            $criteria[$key]=['nota'=>$ok?$note:null,'citacao'=>$ok?$quote:'',
                'justificativa'=>(string)($item['justificativa']??''),'status'=>(string)($item['status']??'sem evidencia')];
            if($ok){$count++;$assessment[$key]=$note;$assessment['evidencia_'.$key]=$quote.' — '.(string)($item['justificativa']??'');}
        }
        $math=null;
        if($count===11 && $linkedCount>0){
            $components=RevisedMethodology::components($assessment,$linkedCount,false);
            if($components!==null)$math=$components+['w'=>RevisedMethodology::weight($components)];
        }
        return ['criterios'=>$criteria,'notas_citadas'=>$count,
            'peso_provisorio_nao_validado'=>$math,'natureza_sugerida'=>(string)($raw['natureza']??''),
            'acao_sugerida'=>(string)($raw['acao_sugerida']??''),
            'riscos'=>array_slice(is_array($raw['riscos']??null)?$raw['riscos']:[],0,8),
            'estado'=>'PROPOSTA_NAO_VALIDADA'];
    }

    /** Rascunho assistido: valor que o pesquisador precisa revisar e salvar conscientemente. */
    public static function prefills(?array $existing, ?array $proposal): array {
        $result=[];
        foreach(RevisedMethodology::ALL as $key=>$label){
            $old=$existing[$key]??null;
            $oldReason=trim((string)($existing['evidencia_'.$key]??''));
            $suggestion=$proposal['criterios'][$key]??[];
            $suggested=$suggestion['nota']??null;
            $note=$old!==null && $old!=='' ? $old : (is_int($suggested)?$suggested:null);
            $quote=trim((string)($suggestion['citacao']??''));
            $just=trim((string)($suggestion['justificativa']??''));
            $evidence=$oldReason!==''?$oldReason:($note!==null && $quote!=='' ? $quote.($just!==''?' — '.$just:'') : '');
            $result[$key]=['nota'=>$note,'evidencia'=>substr($evidence,0,3000),
                'origem'=>$old!==null?'rascunho anterior':($suggested!==null?'IA para revisao':'sem evidencia')];
        }
        return $result;
    }
}
