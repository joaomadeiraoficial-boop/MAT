<?php
declare(strict_types=1);
/** Reabre revisão quando um marco aprovado sofre mudança no conteúdo científico. */
final class MarcoRevisao {
    public const CAMPOS_SUBSTANTIVOS = [
        'titulo','ano','ano_fim','precisao_data','categoria','descricao','detalhes',
        'local','civilizacao','criador','fonte','referencia_url','fonte_pagina',
        'criterio_inclusao','etiquetas','impacto','confiabilidade','fator_fundacional',
    ];
    private static function normalizar(mixed $v): string {
        if($v===null)return '';
        if(is_bool($v))return $v?'1':'0';
        if(is_numeric($v))return (string)(float)$v;
        return trim((string)$v);
    }
    public static function camposAlterados(array $anterior,array $novo): array {
        $out=[];
        foreach(self::CAMPOS_SUBSTANTIVOS as $campo)
            if(self::normalizar($anterior[$campo]??null)!==self::normalizar($novo[$campo]??null))$out[]=$campo;
        return $out;
    }
    /** Mantém propostas pendentes, nunca encerra avaliação ou cria uma aprovação nova. */
    public static function invalidarAprovacao(array $anterior,array &$novo): array {
        $mudancas=self::camposAlterados($anterior,$novo);
        if($mudancas && ($anterior['status']??'')==='aprovado') {
            $novo['status']='pendente';
            $novo['incluir_temporis']=0;
            $novo['escopo_revisado']=0;
            $novo['elegibilidade_temporis']='revisar';
        }
        return $mudancas;
    }
}
