<?php
declare(strict_types=1);
/**
 * Adaptação DOCUMENTADA do peso da Têmporis original à rubrica de 11 critérios.
 * NÃO lê o antigo acervo `marcos` nem converte notas legadas 1-5.
 * Para pontos completados pela CuradoriaRodadas::memory:
 *  I = 1 + 9 × somaImpacto/12, C(%) = 100 × somaConfiabilidade/12,
 *  F = 1 + 0,5 × somaFundacional/9.
 * P_original_adaptado = I² × (C(%)/100) × F.
 * O peso reformulado dos MESMOS critérios é (I/10)² × C × F.
 * Logo, quando ambos existem: P_original_adaptado = 100 × P_reformulado.
 * Esta diferença cancela na razão K(t)/K(base), mas não no logaritmo.
 */
final class TemporisPotenciaAtual
{
    public static function peso(array $p): ?float
    {
        foreach (['I','C','F'] as $campo) {
            if (!isset($p[$campo]) || !is_numeric($p[$campo]) || !is_finite((float)$p[$campo])) return null;
        }
        $I=(float)$p['I'];$C=(float)$p['C'];$F=(float)$p['F'];
        if ($I<1. || $I>10. || $C<0. || $C>1. || $F<1. || $F>1.5) return null;
        return $I*$I*$C*$F;
    }

    /** Preserva ano validado, intervalo, ID e status para o gráfico. */
    public static function preparar(array $registros): array
    {
        $out=[];
        foreach ($registros as $reg) {
            if (!isset($reg['year']) || !is_numeric($reg['year'])) continue;
            $peso=self::peso($reg);
            if ($peso===null) continue;
            $reg['score']=$peso;
            $out[]=$reg;
        }
        return $out;
    }
}
