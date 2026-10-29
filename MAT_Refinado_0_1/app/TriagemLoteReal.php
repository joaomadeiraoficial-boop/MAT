<?php
declare(strict_types=1);
/**
 * Fila de sugestoes a partir do banco REAL, sem escrever em marcos/import_itens.
 * Os resultados ficam FORA do htdocs/Git. Nao sao avaliacoes confirmadas.
 */
final class TriagemLoteReal {
    public const SOURCE_FILE='MAT_300_CANDIDATOS_PARA_IMPORTACAO.csv';
    public const VERSION='mat-triagem-300-reais-v1.0';
    public const CRITERIA=[
        'impacto_difusao','impacto_magnitude','impacto_alcance','impacto_persistencia',
        'conf_rastreabilidade','conf_independencia','conf_datacao','conf_consistencia',
        'fund_pervasividade','fund_aprimoramento','fund_complementaridade'
    ];
    public static function privateDir(): string {
        $fromEnv=getenv('MAT_TRIAGEM_PRIVADA');
        if(is_string($fromEnv) && trim($fromEnv)!=='')return rtrim($fromEnv,"\\/");
        // No projeto atual: D:\Faculdade nero\MAT_IA_TRIAGEM_PRIVADA
        return dirname(MAT_ROOT,4).DIRECTORY_SEPARATOR.'MAT_IA_TRIAGEM_PRIVADA';
    }
    public static function directory(bool $create=false): string {
        $dir=self::privateDir().DIRECTORY_SEPARATOR.'lote300';
        if($create && !is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) {
            throw new RuntimeException('Nao foi possivel criar diretorio privado: '.$dir);
        }
        return $dir;
    }
    public static function filepath(int $id): string {
        if($id<=0)throw new InvalidArgumentException('ID invalido');
        return self::directory().DIRECTORY_SEPARATOR.'registro_'.$id.'.json';
    }
    public static function load(int $id): ?array {
        $p=self::filepath($id);
        if(!is_file($p))return null;
        $obj=json_decode((string)file_get_contents($p),true);
        return is_array($obj)?$obj:null;
    }
    public static function save(int $id, array $data): void {
        self::directory(true);
        $target=self::filepath($id);
        $content=json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);
        $tmp=$target.'.tmp-'.getmypid();
        if(file_put_contents($tmp,$content,LOCK_EX)===false)throw new RuntimeException('Falha na gravacao da proposta');
        if(!rename($tmp,$target)){@unlink($tmp);throw new RuntimeException('Falha em salvar arquivo de proposta');}
    }
    public static function fetch300(PDO $pdo): array {
        $stmt=$pdo->prepare('SELECT i.id,i.lote_id,i.numero_linha,i.titulo,i.ano,i.categoria,i.descricao,i.fonte,i.bruto_json,i.status,l.arquivo_nome
           FROM import_itens i INNER JOIN import_lotes l ON l.id=i.lote_id WHERE l.arquivo_nome=? ORDER BY i.numero_linha ASC, i.id ASC');
        $stmt->execute([self::SOURCE_FILE]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function evidence(array $row): string {
        $raw=json_decode((string)($row['bruto_json']??''),true);
        return trim((string)(is_array($raw)?($raw['trecho_evidencia']??''):''));
    }
    public static function metadata(array $row): array {
        $raw=json_decode((string)($row['bruto_json']??''),true)?:[];
        return ['pagina_livro'=>$raw['pagina_livro']??null,'pagina_pdf'=>$raw['pagina_pdf']??null,'id_extracao'=>$raw['id_extracao']??null];
    }
    public static function rules(array $row): array {
        $excerpt=self::evidence($row);
        $flags=[];$title=(string)($row['titulo']??'');
        if(preg_match('/^A revisar\s*:/iu',$title))$flags[]='Titulo ainda e uma frase extraida; reformular apos conferir a fonte';
        if(strlen($excerpt)<70)$flags[]='Trecho curto demais para a analise automatica completa';
        $year=$row['ano']??null;
        if(!is_numeric($year)||!preg_match('/^-?\d+$/D',(string)$year)|| (int)$year> (int)date('Y'))$flags[]='Revisar a data antes de aprovar';
        if(preg_match('/\b(born|died|birthday|his birth|her birth|was born|death of|in the 19\d0s|in the 18\d0s|controversy|claimed)\b/iu',$excerpt)) $flags[]='O contexto pode mencionar biografia, controversia ou periodo, em vez da data do acontecimento';
        $flags[]='Localizar e verificar a pagina original; investigar duplicatas e fontes independentes';
        return [
            'estado_original'=>(string)($row['status']??''),
            'natureza'=>'CANDIDATO_NAO_VALIDADO',
            'pode_enviar_ia'=>strlen($excerpt)>=70,
            'caracteres_trecho'=>strlen($excerpt),
            'flags'=>$flags,
            'metadados'=>self::metadata($row),
        ];
    }
    public static function proposal(array $ai): array {
        $valid=0;$all=[];$assessment=[];
        foreach(self::CRITERIA as $field) {
            $c=is_array($ai['criterios'][$field]??null)?$ai['criterios'][$field]:[];
            $score=$c['nota']??null;
            if(is_int($score) && $score>=0 && $score<=3 && trim((string)($c['citacao']??''))!=='') {
                $valid++;$assessment[$field]=$score;
                $assessment['evidencia_'.$field]=(string)$c['citacao'].' — '.(string)($c['justificativa']??'');
            }
            $all[$field]=['nota'=>(is_int($score)&&$score>=0&&$score<=3&&trim((string)($c['citacao']??''))!=='')?$score:null,
                'citacao'=>(string)($c['citacao']??''), 'justificativa'=>(string)($c['justificativa']??''),
                'status'=>(string)($c['status']??'a revisar')];
        }
        $math=null;
        // Peso meramente provisório se todas as notas possuem citação literal localizada.
        if($valid===11 && class_exists('RevisedMethodology')) {
            $parts=RevisedMethodology::components($assessment,1,false);
            if($parts!==null)$math=['I'=>$parts['I'],'C'=>$parts['C'],'F'=>$parts['F'],'w'=>RevisedMethodology::weight($parts,2.0)];
        }
        return ['status'=>'PROPOSTA_NAO_VALIDADA','notas_com_citacao'=>$valid,'total_criterios'=>11,
            'avaliacao_completa_apenas_textual'=>$valid===11,'peso_provisorio_nao_validado'=>$math,
            'natureza_sugerida'=>(string)($ai['natureza']??''), 'acao_sugerida'=>(string)($ai['acao_sugerida']??''),
            'riscos'=>is_array($ai['riscos']??null)?$ai['riscos']:[], 'criterios'=>$all];
    }
    public static function state(array $rows): array {
        $prepared=0;$aiCompleted=0;$aiIncomplete=0;$failed=0;$missing=0;
        $perEra=[];
        foreach($rows as $r){
            $key=class_exists('AtlasReais')?AtlasReais::keyForYear($r['ano']??null):null;
            $perEra[$key??'fora_de_escala']=($perEra[$key??'fora_de_escala']??0)+1;
            $a=self::load((int)$r['id']);
            if(!$a){$missing++;continue;}
            $prepared++;
            if(($a['ia_status']??'')==='erro')$failed++;
            elseif(($a['ia_status']??'')==='concluido'){
                if((int)($a['proposta']['notas_com_citacao']??0)===11)$aiCompleted++;
                else $aiIncomplete++;
            }
        }
        return ['total_lote'=>count($rows),'preparados'=>$prepared,'aguardando_preparacao'=>$missing,
            'avaliados_ia'=>$aiCompleted+$aiIncomplete,'ia_11_notas'=>$aiCompleted,
            'ia_incompleta'=>$aiIncomplete,'erros_ia'=>$failed,'rubricas_confirmadas_pelo_lote'=>0,
            'contagem_por_era'=>$perEra,'aviso'=>'Nao conta resultados como marcos validados.\nNao calcula Temporis oficial com IA.'];
    }
}
