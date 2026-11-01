<?php
declare(strict_types=1);
/**
 * Pre-auditoria documental deterministica. Nao pontua, nao aprova e nao consulta IA.
 * O resultado expressa sinais de revisao, NAO uma probabilidade de veracidade.
 */
final class AuditoriaAmbiguidade {
    public const VERSION = 'mat-ambiguidade-v1.0';
    public static function evaluate(array $row): array {
        $title=trim((string)($row['titulo']??''));
        $description=trim((string)($row['descricao']??''));
        $source=trim((string)($row['fonte']??''));
        $excerpt=class_exists('TriagemLoteReal') ? TriagemLoteReal::evidence($row) : '';
        $meta=class_exists('TriagemLoteReal') ? TriagemLoteReal::metadata($row) : [];
        $risks=[];
        $add=static function(string $code,string $severity,string $message)use(&$risks):void{
            $risks[]=['codigo'=>$code,'gravidade'=>$severity,'mensagem'=>$message];
        };
        if($title==='' || preg_match('/^A revisar\s*:/iu',$title)===1)
            $add('TITULO_NAO_EVENTO','alta','Titulo ausente ou mantido como frase de extracao; identificar um acontecimento tecnologico especifico.');
        if($title!=='' && (function_exists('mb_strlen')?mb_strlen($title,'UTF-8'):strlen($title))>160)
            $add('TITULO_EXTENSO','media','Titulo extenso: verificar se descreve um acontecimento unico.');
        $year=$row['ano']??null;
        if(!is_scalar($year) || !preg_match('/^-?\d{1,6}$/D',(string)$year) || (int)$year>2026 || (int)$year< -100000)
            $add('DATA_INVALIDA','alta','Data numerica ausente, fora de escala ou futura.');
        if($excerpt==='' || (function_exists('mb_strlen')?mb_strlen($excerpt,'UTF-8'):strlen($excerpt))<70)
            $add('EVIDENCIA_CURTA','alta','Trecho documental insuficiente para fundamentar o evento e sua data.');
        if($source==='')$add('FONTE_AUSENTE','alta','Fonte bibliografica nao informada.');
        if(empty($meta['pagina_livro']) && empty($meta['pagina_pdf']))
            $add('PAGINA_AUSENTE','media','Sem referencia de pagina para conferir a extracao.');
        $context=$title.' '.$description.' '.$excerpt;
        if(preg_match('/\b(born|was born|died|death of|birthday|nasceu|faleceu|morreu)\b/iu',$context)===1)
            $add('CONTEXTO_BIOGRAFICO','media','Pode tratar de biografia e nao da criacao ou difusao de tecnologia.');
        if(preg_match('/\b(claimed|controversy|allegedly|supostamente|alegou|controvers[ao])\b/iu',$context)===1)
            $add('AFIRMACAO_CONTESTADA','media','Afirmacao possivelmente controversa; procurar corroboracao independente.');
        if(preg_match('/\b(in the (?:18|19|20)\d0s|seculo|século|decada|década|entre \d{3,4} e \d{3,4})\b/iu',$context)===1)
            $add('DATA_APROXIMADA','media','O trecho pode referir um periodo amplo em vez do ano do acontecimento.');
        $high=count(array_filter($risks,static fn($r)=>$r['gravidade']==='alta'));
        $status=$high>0?'BLOQUEAR_PONTUACAO':'REVISAR_EVIDENCIAS';
        return ['versao'=>self::VERSION,'situacao'=>$status,'riscos'=>$risks,
            'quantidade_riscos'=>count($risks),'riscos_altos'=>$high,
            'pode_pontuar_automaticamente'=>false,
            'fontes_independentes_verificadas'=>0,
            'observacao'=>'Triagem heuristica. Nao prova a inexistencia de ambiguidades e nao valida fatos ou fontes.'];
    }
}
