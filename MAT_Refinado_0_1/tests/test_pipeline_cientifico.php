<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/AuditoriaAmbiguidade.php';
require_once dirname(__DIR__).'/app/AvaliacaoNuvem.php';
$bad=['titulo'=>'A revisar: mencao biografica','ano'=>null,'descricao'=>'','fonte'=>'','bruto_json'=>'{}'];
$good=['titulo'=>'Motor a vapor','ano'=>1769,'descricao'=>'descricao','fonte'=>'Livro, p. 50',
 'bruto_json'=>json_encode(['trecho_evidencia'=>str_repeat('Uma descricao tecnologica verificavel. ',4),'pagina_livro'=>50])];
if(AvaliacaoNuvem::classify($bad)['elegivel_para_analise'])throw new Exception('Registro ruim liberado');
if(!AvaliacaoNuvem::classify($good)['elegivel_para_analise'])throw new Exception('Registro documental liberavel bloqueado');
if(AvaliacaoNuvem::rowStatus(['ia_status'=>'concluido','proposta'=>['notas_com_citacao'=>5]])!=='PROPOSTA_INCOMPLETA')throw new Exception('Estado incorreto');
echo "[OK] Pipeline: 3 verificacoes offline aprovadas.\n";
