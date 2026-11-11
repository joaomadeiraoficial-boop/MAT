<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';
require_once __DIR__.'/CuradoriaDatacao.php';

/** Dois regimes distintos; a escolha da sessão só altera a preparação preliminar. */
final class CuradoriaPolitica {
    public const SIMPLIFICADO='simplificado';
    public const RIGOROSO='rigoroso';
    public const SITUACAO_MINIMA='PROVISORIA_SEM_EVIDENCIA';

    public static function modo(): string {
        // Scripts CLI e verificações legadas permanecem rigorosos por padrão.
        if(session_status()!==PHP_SESSION_ACTIVE)return self::RIGOROSO;
        $modo=(string)($_SESSION['mat_curadoria_modo']??self::SIMPLIFICADO);
        return in_array($modo,[self::SIMPLIFICADO,self::RIGOROSO],true)?$modo:self::RIGOROSO;
    }
    public static function selecionar(string $modo): void {
        if(session_status()!==PHP_SESSION_ACTIVE)throw new RuntimeException('Sessão não iniciada.');
        if(!in_array($modo,[self::SIMPLIFICADO,self::RIGOROSO],true))throw new InvalidArgumentException('Modo de curadoria inválido.');
        $_SESSION['mat_curadoria_modo']=$modo;
    }
    public static function avisos(array $r): array {
        $avisos=[];
        if(!CuradoriaDatacao::temData($r) && CuradoriaDatacao::anoValido($r['ano_original']??null))
            $avisos[]='Ano somente extraído: não foi validado e não pode entrar na série oficial.';
        if(!CuradoriaDatacao::conferirFonte($r))$avisos[]='Fonte do intervalo ausente: conferir.';
        if(!empty($r['pendencias']))$avisos[]='Pendências históricas ainda abertas ('.count($r['pendencias']).').';
        if(!empty($r['duplicatas']))$avisos[]='Possíveis duplicatas não verificadas ('.count($r['duplicatas']).').';
        foreach(CuradoriaRodadas::CRITERIOS as $cod=>$nome){
            $c=$r['criterios'][$cod]??null;
            if(($c['situacao']??'')===self::SITUACAO_MINIMA)
                $avisos[]='Nota mínima provisória, sem evidência específica: '.$nome.'.';
            elseif($c && ($c['nota']??null)!==null && (trim((string)($c['justificativa']??''))==='' || CuradoriaRodadas::decodeArray((string)($c['fontes_ids_json']??''))===[]))
                $avisos[]='Critério com justificativa ou fonte insuficiente: '.$nome.'.';
        }
        return $avisos;
    }
}
