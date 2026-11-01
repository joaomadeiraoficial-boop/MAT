<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/GeminiMAT.php';
require_once dirname(__DIR__).'/app/NormalizacaoCandidatos.php';
$bad=false;
try {GeminiMAT::requestJson('modelo-invalido','teste','teste','fake');}catch(InvalidArgumentException $e){$bad=true;}
if(!$bad)throw new RuntimeException('Modelo indevido nao bloqueado');
$quote=NormalizacaoCandidatos::validate(['tipo'=>'evento_tecnologico','titulo_proposto'=>'Maquina a vapor','ano_proposto'=>1769,'citacao_literal'=>'trecho inexistente','confianca'=>'alta'],'Foi criada uma maquina a vapor.');
if($quote['citacao_encontrada'] || $quote['identificacao_util'] || $quote['aprovado'])throw new RuntimeException('Proposta sem citacao foi aceita');
echo "[OK] Gemini: modelo rejeitado e proposta sem citacao nao validada.\n";
