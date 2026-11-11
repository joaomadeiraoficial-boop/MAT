<?php
declare(strict_types=1);
/**
 * Audita MATERIAL DOCUMENTAL do acervo sem confundir descricoes com citacoes.
 * Este componente NUNCA cria notas, aprova marcos ou grava no banco.
 */
final class AuditoriaMaterialAcervo {
    public const VERSION = 'mat-auditoria-material-1.0';

    public static function trechoImportado(?array $import): string {
        if (!is_array($import)) return '';
        $data = json_decode((string)($import['bruto_json'] ?? ''), true);
        if (!is_array($data)) return '';
        return trim((string)($data['trecho_evidencia'] ?? ''));
    }

    /** @return array<string, mixed> */
    public static function analisar(array $marco, array $vinculos, ?array $fonteDireta = null, ?array $import = null): array {
        $id = (int)($marco['id'] ?? 0);
        $titulo = trim((string)($marco['titulo'] ?? ''));
        $descricao = trim((string)($marco['descricao'] ?? ''));
        $referencia = trim((string)($marco['fonte'] ?? ''));
        $url = trim((string)($marco['referencia_url'] ?? ''));
        $pagina = trim((string)($marco['fonte_pagina'] ?? ''));
        $fontes = [];
        $trechosVinculados = 0;
        foreach ($vinculos as $v) {
            if (!is_array($v)) continue;
            if (strlen(trim((string)($v['trecho'] ?? ''))) >= 20) $trechosVinculados++;
            $fonteUrl = trim((string)($v['url'] ?? ''));
            $fonteTitulo = trim((string)($v['titulo'] ?? ''));
            if ($fonteTitulo !== '' || $fonteUrl !== '') {
                $fontes[] = ['titulo' => $fonteTitulo, 'url' => $fonteUrl, 'pagina' => (string)($v['pagina'] ?? '')];
            }
            if ($url === '' && $fonteUrl !== '') $url = $fonteUrl;
            if ($referencia === '' && $fonteTitulo !== '') $referencia = $fonteTitulo;
            if ($pagina === '' && trim((string)($v['pagina'] ?? '')) !== '') $pagina = trim((string)$v['pagina']);
        }
        if (is_array($fonteDireta)) {
            $fonteUrl = trim((string)($fonteDireta['url'] ?? ''));
            $fonteTitulo = trim((string)($fonteDireta['titulo'] ?? ''));
            if ($fonteTitulo !== '' || $fonteUrl !== '') {
                $fontes[] = ['titulo' => $fonteTitulo, 'url' => $fonteUrl, 'pagina' => ''];
            }
            if ($url === '' && $fonteUrl !== '') $url = $fonteUrl;
            if ($referencia === '' && $fonteTitulo !== '') $referencia = $fonteTitulo;
        }
        $textoImportado = self::trechoImportado($import);
        $temImportado = strlen($textoImportado) >= 70;
        $temDescricao = strlen($descricao) >= 40;
        $temFonte = $referencia !== '' || $url !== '' || $fontes !== [];
        $urlValida = filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true);
        if ($trechosVinculados > 0) {
            $acao = 'REVISAR_TRECHOS_VINCULADOS';
            $prioridade = 1;
        } elseif ($temImportado && $temFonte) {
            $acao = 'CONFERIR_TRECHO_IMPORTADO_NA_FONTE';
            $prioridade = 2;
        } elseif ($urlValida) {
            $acao = 'ABRIR_LINK_E_TRANSCRIVER_CITACAO_LITERAL';
            $prioridade = 3;
        } elseif ($temFonte) {
            $acao = 'LOCALIZAR_REFERENCIA_E_TRANSCRIVER_CITACAO_LITERAL';
            $prioridade = 4;
        } elseif ($temDescricao) {
            $acao = 'PESQUISAR_FONTE_DOCUMENTAL_PARA_DESCRICAO';
            $prioridade = 5;
        } else {
            $acao = 'IDENTIFICAR_MARCO_E_PESQUISAR_DOCUMENTACAO';
            $prioridade = 6;
        }
        $alertas = [];
        if (!$temFonte) $alertas[] = 'SEM_FONTE_DECLARADA';
        if (!$temDescricao) $alertas[] = 'SEM_DESCRICAO_SUFICIENTE';
        if ($trechosVinculados === 0) $alertas[] = 'SEM_CITACAO_VINCULADA';
        if ($pagina === '') $alertas[] = 'PAGINA_NAO_INFORMADA';
        if ($temImportado) $alertas[] = 'TRECHO_IMPORTADO_AINDA_NAO_VERIFICADO';
        return [
            'id' => $id, 'titulo' => $titulo, 'ano' => (int)($marco['ano'] ?? 0),
            'status_acervo' => (string)($marco['status'] ?? ''),
            'descricao' => $descricao, 'fonte_declarada' => $referencia,
            'url_referencia' => $url, 'pagina_declarada' => $pagina,
            'vinculos' => count($vinculos), 'vinculos_com_trecho' => $trechosVinculados,
            'tem_descricao' => $temDescricao, 'tem_referencia' => $temFonte,
            'tem_url' => (bool)$urlValida, 'trecho_importado' => $textoImportado,
            'trecho_importado_util' => $temImportado, 'fontes_identificadas' => $fontes,
            'acao' => $acao, 'prioridade' => $prioridade, 'alertas' => $alertas,
            'citacoes_verificadas_pelo_mat' => false, 'aprovado' => false,
            'observacao' => 'Descricao, URL e trecho importado nao comprovam a fonte nem autorizam pontuacao automatica.'
        ];
    }
}
