<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/PainelCientifico.php';
require_once dirname(__DIR__).'/app/TriagemLoteReal.php';
require_once dirname(__DIR__).'/app/CuradoriaMarcos.php';
require_once dirname(__DIR__).'/app/PontuadorGemini.php';
require_once dirname(__DIR__).'/app/RevisedMethodology.php';
function mat9assert(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('Teste MAT 09 falhou: '.$message);
}
function mat9row(int $id,string $title,string $excerpt,string $source='Livro A'):array{
    return ['id'=>$id,'titulo'=>$title,'ano'=>1991,'fonte'=>$source,
        'bruto_json'=>json_encode(['trecho_evidencia'=>$excerpt,'pagina_livro'=>'42'],JSON_THROW_ON_ERROR)];
}
$folder=sys_get_temp_dir().'/mat09-'.bin2hex(random_bytes(6));
if(!mkdir($folder,0700,true))throw new RuntimeException('Sem diretório temporario');
try{
    $evidence='Em 1991 o projeto de rede passou a usar documentos ligados por hipertexto, com comunicacao entre computadores.';
    $rows=[
        mat9row(1,'Rede de documentos em 1991',$evidence),
        mat9row(2,'A revisar: relato sobre a rede',$evidence.' Um contexto adicional.'),
        mat9row(3,'A revisar: pequeno','Frase curta.'),
        mat9row(4,'Rede de documentos em 1991',$evidence.' Mais palavras diferentes.'),
        mat9row(5,'Prototipo de transmissao',$evidence.' Outro objeto de estudo.'),
    ];
    $c=CuradoriaMarcos::resolve($rows[4],null);
    $model='gemini-3.5-flash-lite';
    $saved=['estado'=>'PROPOSTA_NAO_VALIDADA','modelo'=>$model,
       'fingerprint'=>CuradoriaMarcos::fingerprint($c,$model,RevisedMethodology::VERSION.'|'.PontuadorGemini::VERSION),
       'proposta'=>['criterios_com_citacao'=>11,'componentes_hipoteticos_nao_oficiais'=>['I'=>7.0,'C'=>0.7,'F'=>1.4,'w'=>0.5]]];
    file_put_contents($folder.'/pontuacao_gemini_5.json',json_encode($saved,JSON_THROW_ON_ERROR));
    $output=PainelCientifico::resumir($rows,$folder);
    mat9assert($output['total']===5,'total do lote');
    mat9assert($output['trechos_pendentes']===1,'registros com trecho curto');
    mat9assert($output['eventos_definidos']===3,'titulo provisório não é evento definido');
    mat9assert($output['duplicatas_suspeitas']===2,'duplicidade sinalizada nos DOIS registros');
    mat9assert($output['aptos_pontuacao']===1,'duplicatas fora da fila de pontuacao');
    mat9assert($output['propostas_11']===1,'proposta completa apenas nao oficial');
    mat9assert($output['fila'][0]['id']===5 && $output['fila'][0]['acao']==='AUDITAR_NOTAS_E_FONTES','prioridade auditavel');
    mat9assert($output['fila'][0]['avaliacao']==='NAO_APROVADA','nao aprova por pontuacao');
    // Fingerprint desatualizado não é contado.
    $saved['fingerprint']='antigo';
    file_put_contents($folder.'/pontuacao_gemini_5.json',json_encode($saved,JSON_THROW_ON_ERROR));
    $output=PainelCientifico::resumir($rows,$folder);
    mat9assert($output['propostas_11']===0,'ignora pontuacao desatualizada');
    // Testa indisponibilidade do banco sem inventar o total.
    mat9assert(PainelCientifico::consultar(null)['total']===null,'sem estimativas quando MySQL desligado');
    echo "[OK] Painel MAT 09: 9 grupos de verificacoes offline aprovados.\n";
}finally{
    foreach(glob($folder.'/*')?:[] as $item)@unlink($item);
    @rmdir($folder);
}
