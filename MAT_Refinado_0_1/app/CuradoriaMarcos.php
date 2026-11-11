<?php
declare(strict_types=1);
/** MAT: organiza SEM IA e SEM alterar MySQL. Nunca promove candidato ao acervo oficial. */
final class CuradoriaMarcos {
    public const VERSION = 'mat-curadoria-offline-1.0';
    public static function resolve(array $row, ?array $historico): array {
        $id=(int)($row['id']??0);
        $original=trim((string)($row['titulo']??''));
        $source=trim((string)($row['fonte']??''));
        $evidence=TriagemLoteReal::evidence($row);
        $meta=TriagemLoteReal::metadata($row);
        $proposta=is_array($historico['proposta']??null)?$historico['proposta']:[];
        require_once __DIR__.'/SelecaoLote300.php';
        $selecionado=SelecaoLote300::encontrar($row);
        $identified=($proposta['tipo']??'')==='evento_tecnologico'
            && ($proposta['identificacao_util']??false)===true
            && !empty($proposta['citacao_encontrada']);
        $title=$identified?trim((string)($proposta['titulo_proposto']??'')):
            ($selecionado!==null?(string)$selecionado['titulo']:$original);
        $provisional=$title==='' || preg_match('/^A revisar\s*:/iu',$title)===1;
        $year=$identified?($proposta['ano_proposto']??null):($row['ano']??null);
        $anoExtraido=(int)($row['ano']??0);
        $revisarSelecao=$selecionado!==null && ($selecionado['status']??'')!=='PRIORIZADO_PARA_PONTUACAO';
        $erroData=$selecionado!==null && (int)$selecionado['ano']!==$anoExtraido;
        $validYear=(is_int($year)||is_string($year)) && preg_match('/^-?\d{1,6}$/D',(string)$year)===1
            && (int)$year>=-100000 && (int)$year <= (int)date('Y');
        $hasEvidence=(function_exists('mb_strlen')?mb_strlen($evidence,'UTF-8'):strlen($evidence))>=70;
        $issues=[];
        if($provisional)$issues[]='EVENTO_NAO_DEFINIDO';
        if($revisarSelecao)$issues[]=(string)$selecionado['status'];
        if($erroData)$issues[]='ANO_EXTRAIDO_DIVERGENTE';
        if(!$validYear)$issues[]='DATA_PENDENTE';
        if(!$hasEvidence)$issues[]='TRECHO_INSUFICIENTE';
        if($source==='')$issues[]='FONTE_AUSENTE';
        if(empty($meta['pagina_livro'])&&empty($meta['pagina_pdf']))$issues[]='PAGINA_NAO_INFORMADA';
        if($identified && trim((string)($proposta['citacao_literal']??''))!==''
          && strpos($evidence,(string)$proposta['citacao_literal'])===false)$issues[]='NORMALIZACAO_DESATUALIZADA';
        $fpTitle=self::canonical($title);
        return [
            'id'=>$id,'titulo_original'=>$original,'titulo_candidato'=>$title,
            'ano'=>$validYear?(int)$year:null,'fonte_declarada'=>$source,
            'referencia_pagina'=>$meta['pagina_livro']??$meta['pagina_pdf']??null,
            'id_extracao'=>$meta['id_extracao']??null,
            'trecho'=>$evidence, 'trecho_sha256'=>hash('sha256',$evidence),
            'identificacao'=> $identified?'proposta_ia_preexistente':($selecionado!==null?'curadoria_inicial_documental':($provisional?'pendente':'titulo_original')),
            'chave_evento'=> (!$provisional && $validYear && $fpTitle!=='')?$fpTitle.'|'.(int)$year:null,
            'alertas'=>$issues,
            'apto_pontuacao_assistida'=> !$provisional && $validYear && $hasEvidence && $source!==''
                && !$revisarSelecao && !$erroData,
            'observacao_triagem'=>$selecionado['alerta']??'',
            'fonte_verificada'=>false,'aprovado'=>false
        ];
    }
    public static function canonical(string $title):string {
        $title=trim($title);
        $s=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$title);
        if(!is_string($s)||$s==='')$s=$title;
        $s=strtolower($s);
        $s=(string)preg_replace('/[^a-z0-9]+/',' ', $s);
        return trim((string)preg_replace('/\s+/',' ',$s));
    }
    /** Indica pares suspeitos; nunca mescla ou remove automaticamente. */
    public static function duplicateAlerts(array $items):array {
        $byEvent=[];$byExcerpt=[];$out=[];
        foreach($items as $c){
            $id=(int)$c['id'];$hits=[];
            // Duplicata identificada na curadoria NAO deve invalidar a ocorrencia principal,
            // mesmo quando seu fragmento aparece antes dela no SQL.
            if(in_array('POSSIVEL_DUPLICATA',$c['alertas']??[],true)){ $out[$id]=[];continue; }
            $key=$c['chave_evento'];
            if(is_string($key)&&$key!==''&&isset($byEvent[$key]))$hits[]=$byEvent[$key];
            $h=(string)$c['trecho_sha256'];
            if($h!==hash('sha256','') && isset($byExcerpt[$h]))$hits[]=$byExcerpt[$h];
            $out[$id]=array_values(array_unique($hits));
            if(is_string($key)&&$key!=='')$byEvent[$key]??=$id;
            $byExcerpt[$h]??=$id;
        }
        return $out;
    }
    public static function fingerprint(array $c,string $model,string $rubricVersion):string {
        return hash('sha256',json_encode([$c['id'],$c['titulo_candidato'],$c['ano'],
            $c['fonte_declarada'],$c['referencia_pagina'],$c['trecho_sha256'],$model,$rubricVersion],
            JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
    }
}
