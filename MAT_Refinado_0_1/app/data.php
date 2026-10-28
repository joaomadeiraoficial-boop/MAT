<?php
declare(strict_types=1);
function sample_markers(): array {
    $events = [
        [-3200,'Os primeiros sistemas de escrita','Comunicação','Antiguidade','Mesopotâmia','A escrita inaugura novas possibilidades de registro e transmissão de conhecimento.'],
        [-2600,'Engenharia monumental no Egito','Engenharia','Antiguidade','Egito','Construções de grande escala evidenciam técnicas de planejamento, medição e logística.'],
        [1450,'Impressão com tipos móveis na Europa','Comunicação','Idade Média','Europa','Ampliação da reprodução de textos e difusão de obras impressas.'],
        [1769,'Aperfeiçoamentos da máquina a vapor','Energia','Idade Moderna','Reino Unido','Avanços na conversão da energia térmica em trabalho mecânico.'],
        [1928,'Descoberta da penicilina','Medicina','Era Industrial','Reino Unido','Descoberta que posteriormente abriria caminho para o uso terapêutico de antibióticos.'],
        [1946,'Computadores eletrônicos de propósito geral','Computação','Era Digital','Estados Unidos','Máquinas eletrônicas consolidam novas formas de processamento de dados.'],
        [1969,'Primeiros nós da ARPANET','Comunicação','Era Digital','Estados Unidos','Experimentos de interconexão entre computadores antecedem a internet moderna.'],
        [1989,'Proposta da World Wide Web','Computação','Era Digital','CERN','Uma arquitetura de documentos interligados é proposta para compartilhar informação.'],
        [2007,'Popularização do smartphone multitoque','Computação','Era Contemporânea','Global','Interfaces móveis ganham crescente projeção pública e comercial.'],
        [2017,'Arquitetura Transformer','Computação','Era Contemporânea','Pesquisa científica','Uma arquitetura de aprendizado de máquina impulsiona novas abordagens de linguagem.'],
    ];
    $out=[];
    foreach($events as $k=>$e) $out[]=[
       'id'=>$k+1,'ano'=>$e[0],'titulo'=>$e[1],'categoria'=>$e[2],'periodo'=>$e[3],
       'local'=>$e[4],'descricao'=>$e[5], 'detalhes'=>'', 'criador'=>'', 'civilizacao'=>'',
       'fonte'=>'Registro ilustrativo: conferir fontes antes de aprovar', 'referencia_url'=>'',
       'criterio_inclusao'=>'Demonstração visual. Não integra a base científica.',
       'impacto'=>null,'confiabilidade'=>null,'fator_fundacional'=>null,
       'status'=>'demonstracao','precisao_data'=>'aproximada','etiquetas'=>'demonstração',
       'imagem_url'=>'','ano_fim'=>null,'fonte_pagina'=>'','criado_em'=>null
    ];
    return $out;
}
