<?php
declare(strict_types=1);
/** Curadoria inicial conservadora baseada no SQL de 300 fragmentos. SEM NOTAS. */
final class SelecaoLote300 {
  public const VERSION = 'mat-selecao-fragmentos-300-v1';
  private const MAP = [
    'MCD-00019' => ['titulo'=>'Parafuso de Arquimedes para elevacao de agua','ano'=>-300,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00034' => ['titulo'=>'Emissao de papel-moeda pela dinastia Song','ano'=>1024,'alerta'=>'Data extraida 1201 difere da data sugerida 1024. ','status'=>'CONFERIR_DATA'],
    'MCD-00040' => ['titulo'=>'Livro impresso por xilogravura na China (868)','ano'=>868,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00049' => ['titulo'=>'Mecanismo de escape para regulacao de relogios (atrib. Liang Ling-Tsan)','ano'=>725,'alerta'=>'Atribuicao de invencao e data contestaveis: revisar fonte','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00069' => ['titulo'=>'Sistema de irrigacao Hohokam no rio Gila','ano'=>800,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00083' => ['titulo'=>'Elaboracao das Tabuas Alfonsinas','ano'=>1275,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00086' => ['titulo'=>'Carta sobre o magnetismo de Petrus Peregrinus','ano'=>1269,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00118' => ['titulo'=>'Publicacao das duas primeiras leis de Kepler','ano'=>1609,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00144' => ['titulo'=>'Fundacao da Royal Society','ano'=>1660,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00191' => ['titulo'=>'Primeira maquina a vapor pratica de Thomas Newcomen','ano'=>1712,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00194' => ['titulo'=>'Primeira maquina a vapor pratica de Thomas Newcomen','ano'=>1712,'alerta'=>'Possivel repeticao de MCD-00191','status'=>'POSSIVEL_DUPLICATA'],
    'MCD-00196' => ['titulo'=>'Primeira locomotiva a vapor de George Stephenson','ano'=>1814,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00197' => ['titulo'=>'Abertura da ferrovia Liverpool-Manchester','ano'=>1830,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00199' => ['titulo'=>'Invencao da lancadeira volante por John Kay','ano'=>1733,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00202' => ['titulo'=>'Condensador separado na maquina a vapor de James Watt','ano'=>1765,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00214' => ['titulo'=>'Invencao do pirometro por Wedgwood','ano'=>1782,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00220' => ['titulo'=>'Descoberta de Netuno','ano'=>1846,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00231' => ['titulo'=>'Identificacao do gas fixo por Joseph Black','ano'=>1756,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00239' => ['titulo'=>'Descoberta da inducao eletromagnetica por Michael Faraday','ano'=>1831,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00242' => ['titulo'=>'Publicacao do tratado termodinamico de Sadi Carnot','ano'=>1824,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00252' => ['titulo'=>'Fundacao da Ecole Polytechnique','ano'=>1794,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00278' => ['titulo'=>'Abertura de trecho da Ferrovia Transiberiana','ano'=>1903,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00283' => ['titulo'=>'Abertura da ferrovia Toquio-Yokohama','ano'=>1872,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00288' => ['titulo'=>'Ligacao de telegrafo transcontinental Nova York-Sao Francisco','ano'=>1857,'alerta'=>'Ano do lote pode corresponder a intervalo de 1857-1858; verificar','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00289' => ['titulo'=>'Primeira ligacao telefonica transcontinental','ano'=>1915,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00291' => ['titulo'=>'Demonstracao de ondas de radio por Heinrich Hertz','ano'=>1887,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00294' => ['titulo'=>'Primeiras transmissoes de radio comerciais','ano'=>1920,'alerta'=>'Acontecimento plural e decenal: precisar primeira transmissao','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00295' => ['titulo'=>'Introducao da anestesia em cirurgias e odontologia','ano'=>1840,'alerta'=>'Ano aproximado/decada, sem data pontual','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00301' => ['titulo'=>'Publicacao de Theory of the Earth por James Hutton','ano'=>1795,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00309' => ['titulo'=>'Invencao da partida eletrica de automoveis','ano'=>1912,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00318' => ['titulo'=>'Primeiro voo dos irmaos Wright','ano'=>1903,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00321' => ['titulo'=>'Primeiro voo dos irmaos Wright','ano'=>1903,'alerta'=>'Possivel repeticao de MCD-00318','status'=>'POSSIVEL_DUPLICATA'],
    'MCD-00329' => ['titulo'=>'Fundacao da rede radiofonica BBC','ano'=>1922,'alerta'=>'Fundacao da rede BBC pode ser desdobrada da criacao do servico BBC','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00332' => ['titulo'=>'Desenvolvimento da radio FM','ano'=>1939,'alerta'=>'Verificar cronologia e atribuicao do radio FM','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00358' => ['titulo'=>'Publicacao de artigos de Einstein em 1905','ano'=>1905,'alerta'=>'Varios artigos em 1905: separar em eventos especificos','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00361' => ['titulo'=>'Publicacao da relatividade geral por Einstein','ano'=>1915,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00376' => ['titulo'=>'Descoberta da radiacao cosmica de fundo','ano'=>1965,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00382' => ['titulo'=>'Descoberta da estrutura de dupla helice do DNA','ano'=>1953,'alerta'=>'Possivel repeticao de MCD-00413','status'=>'POSSIVEL_DUPLICATA'],
    'MCD-00385' => ['titulo'=>'Descoberta da estrutura de dupla helice do DNA','ano'=>1953,'alerta'=>'Possivel repeticao de MCD-00413','status'=>'POSSIVEL_DUPLICATA'],
    'MCD-00388' => ['titulo'=>'Laboratorio de psicologia experimental por Stanley Hall','ano'=>1883,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00391' => ['titulo'=>'Desenvolvimento dos testes de inteligencia de Binet','ano'=>1905,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00405' => ['titulo'=>'Aperfeicoamento de cirurgia cardiaca com circulacao extracorporea','ano'=>1950,'alerta'=>'Acontecimento de toda uma decada, especificar tecnica/equipe','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00407' => ['titulo'=>'Descoberta da penicilina por Alexander Fleming','ano'=>1928,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00413' => ['titulo'=>'Descoberta da estrutura de dupla helice do DNA','ano'=>1953,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00416' => ['titulo'=>'Inicio do Projeto Genoma Humano','ano'=>1990,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00417' => ['titulo'=>'Primeiro rascunho de sequenciamento do genoma humano','ano'=>2001,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00420' => ['titulo'=>'Tear automatico de Jacquard com cartoes perfurados','ano'=>1801,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00422' => ['titulo'=>'Criacao do transistor de estado solido','ano'=>1947,'alerta'=>'','status'=>'PRIORIZADO_PARA_PONTUACAO'],
    'MCD-00426' => ['titulo'=>'Introducao dos computadores pessoais','ano'=>1970,'alerta'=>'Computador pessoal na decada de 1970: especificar equipamento/marco','status'=>'APROFUNDAR_DEFINICAO'],
    'MCD-00428' => ['titulo'=>'Criacao da rede ARPANET','ano'=>1969,'alerta'=>'','status'=>'TRECHO_CURTO'],
    'MCD-00440' => ['titulo'=>'Lancamento do Sputnik','ano'=>1957,'alerta'=>'','status'=>'TRECHO_CURTO'],
  ];
  /** A atribuicao inicial nao valida o fato nem confirma as 11 notas. */
  public static function encontrar(array $row): ?array {
    $raw=json_decode((string)($row['bruto_json']??''),true);
    if(!is_array($raw))return null;
    $id=(string)($raw['id_extracao']??'');
    $item=self::MAP[$id]??null;
    if(!$item)return null;
    if(trim((string)($row['fonte']??''))==='' || strpos((string)$row['fonte'],'McClellan')===false)return null;
    $text=(string)($row['titulo']??'');
    if(stripos($text,'A revisar:')!==0)return null;
    $item['id_extracao']=$id;
    return $item;
  }
  public static function totalCurados(): int {return count(self::MAP);}
}
