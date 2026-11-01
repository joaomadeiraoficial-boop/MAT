<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/AuditoriaAmbiguidade.php';
require_once dirname(__DIR__).'/app/NormalizacaoCandidatos.php';
$good=['titulo'=>'A revisar: frase extraida','ano'=>1900,'fonte'=>'Livro','bruto_json'=>json_encode(['trecho_evidencia'=>str_repeat('Evidencia historica clara. ',5)])];
$a=NormalizacaoCandidatos::classify($good);
if(!$a['elegivel_identificacao']||!$a['pontuacao_bloqueada'])throw new Exception('Triagem errada');
$bad=$good;$bad['bruto_json']=json_encode(['trecho_evidencia'=>'curto']);
if(NormalizacaoCandidatos::classify($bad)['elegivel_identificacao'])throw new Exception('Sem evidencia liberado');
$r=NormalizacaoCandidatos::validate(['tipo'=>'evento_tecnologico','titulo_proposto'=>'Motor de teste','ano_proposto'=>1900,'citacao_literal'=>'Evidencia historica clara.','confianca'=>'alta'],TriagemLoteReal::evidence($good));
if(!$r['identificacao_util']||$r['aprovado'])throw new Exception('Validacao de proposta incorreta');
$r2=NormalizacaoCandidatos::validate(['tipo'=>'evento_tecnologico','titulo_proposto'=>'Inexistente','ano_proposto'=>1900,'citacao_literal'=>'citacao inventada'],TriagemLoteReal::evidence($good));
if($r2['identificacao_util']||$r2['citacao_encontrada'])throw new Exception('Citacao inventada aceita');
echo "[OK] Normalizacao: 4 verificacoes offline aprovadas.\n";
