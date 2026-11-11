<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/AuditoriaMaterialAcervo.php';
$ok=0;
function check11(bool $condition, string $message): void { global $ok; if (!$condition) throw new RuntimeException($message); $ok++; }
$base=['id'=>7,'titulo'=>'Tecnologia teste','ano'=>1844,'status'=>'pendente',
    'descricao'=>'Uma descricao historica extensa para permitir identificar um evento documentado.',
    'fonte'=>'Acervo institucional','referencia_url'=>'https://example.org/item/7', 'fonte_pagina'=>''];
$a=AuditoriaMaterialAcervo::analisar($base,[]);
check11($a['acao']==='ABRIR_LINK_E_TRANSCRIVER_CITACAO_LITERAL','URL deve gerar acao concreta');
check11($a['tem_descricao'] && $a['tem_referencia'] && $a['tem_url'],'Reconhecer dados ja existentes');
check11($a['vinculos_com_trecho']===0 && !$a['citacoes_verificadas_pelo_mat'],'Descricao nao e citacao');
check11($a['prioridade']===3,'Prioridade da URL');
$b=AuditoriaMaterialAcervo::analisar($base,[['trecho'=>'Este e um trecho literal atribuido a fonte original','url'=>'https://example.org/item/7']]);
check11($b['vinculos_com_trecho']===1 && $b['prioridade']===1,'Trecho existente deve ter prioridade');
check11(!$b['aprovado'],'Trecho nao aprova automaticamente');
$c=AuditoriaMaterialAcervo::analisar($base,[],null,['bruto_json'=>json_encode(['trecho_evidencia'=>str_repeat('Acontecimento registrado em fonte. ',4)])]);
check11($c['trecho_importado_util'] && $c['prioridade']===2,'Reaproveitar trecho importado apenas para auditoria');
check11(in_array('TRECHO_IMPORTADO_AINDA_NAO_VERIFICADO',$c['alertas'],true),'Trecho importado precisa de aviso');
$d=AuditoriaMaterialAcervo::analisar(['id'=>8,'titulo'=>'Sem documento','ano'=>1900],[]);
check11($d['acao']==='IDENTIFICAR_MARCO_E_PESQUISAR_DOCUMENTACAO','Nao inventar fonte');
check11(!$d['tem_referencia'] && !$d['tem_descricao'],'Ausencia preservada');
$e=AuditoriaMaterialAcervo::analisar(['id'=>9,'titulo'=>'Com fonte','ano'=>1900,'fonte'=>'Instituto de historia'],[]);
check11($e['prioridade']===4,'Fonte sem URL continua util');
$f=AuditoriaMaterialAcervo::analisar(['id'=>10,'titulo'=>'Com id fonte','ano'=>1920],[],['titulo'=>'Catalogo do museu','url'=>'https://example.org/obj']);
check11($f['tem_referencia'] && $f['tem_url'],'Reaproveitar fonte_id sem marco_fontes');
check11(AuditoriaMaterialAcervo::trechoImportado(['bruto_json'=>'INVALIDO'])==='','Nao inventar trecho de JSON invalido');
echo "OK {$ok} verificacoes da auditoria documental.\n";
