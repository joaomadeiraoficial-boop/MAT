<?php
declare(strict_types=1);
const MAT_ROOT=__DIR__.'/..';
require_once __DIR__.'/../app/RevisedMethodology.php';
require_once __DIR__.'/../app/PontuacaoAcervoIA.php';
function check(bool $condition,string $label):void{
    if(!$condition)throw new RuntimeException('FALHOU: '.$label);
    echo "[OK] $label\n";
}
$raw=['criterios'=>[],'riscos'=>[]];
foreach(RevisedMethodology::ALL as $key=>$label)$raw['criterios'][$key]=['nota'=>null,'citacao'=>'','justificativa'=>''];
$raw['criterios']['impacto_difusao']=['nota'=>2,'citacao'=>'Trecho literal documental maior que vinte letras','justificativa'=>'Evidencia inicial'];
$p=PontuacaoAcervoIA::proposal($raw,1);
check(count($p['criterios'])===11,'As onze rubricas da metodologia revisada existem');
check($p['notas_citadas']===1,'Notas faltantes nao sao inventadas');
check($p['peso_provisorio_nao_validado']===null,'Sem onze notas nao calcula peso');
$f=PontuacaoAcervoIA::prefills(null,$p);
check($f['impacto_difusao']['nota']===2 && $f['impacto_magnitude']['nota']===null,'Pre-preenchimento parcial sem nota forcada');
$prior=['impacto_difusao'=>1,'evidencia_impacto_difusao'=>'Revisado anteriormente pelo pesquisador'];
$f2=PontuacaoAcervoIA::prefills($prior,$p);
check($f2['impacto_difusao']['nota']===1 && str_starts_with($f2['impacto_difusao']['evidencia'],'Revisado'),'Rascunho humano tem prioridade sobre IA');
$source=PontuacaoAcervoIA::usableSources([['trecho'=>'curto'],['trecho'=>'Este e um trecho suficientemente longo para analise preliminar.']]);
check(count($source)===1,'Nao utiliza fonte sem trecho documental');
foreach(RevisedMethodology::ALL as $k=>$label)$raw['criterios'][$k]=['nota'=>2,'citacao'=>'Trecho literal documental maior que vinte letras','justificativa'=>'Teste de componentes'];
$p2=PontuacaoAcervoIA::proposal($raw,1);
check($p2['notas_citadas']===11 && ($p2['peso_provisorio_nao_validado']['w']??0)>0,'Peso provisorio usa a formula oficial (nao validado)');
check(is_file(__DIR__.'/../assets/insignia.svg'),'Icone original do MAT presente');
check(is_file(__DIR__.'/../fila_pontuacao.php'),'Pagina de pontuacao instalada');
echo "MAT / PONTUACAO DO ACERVO: TESTES LOCAIS APROVADOS. SEM MYSQL OU OLLAMA.\n";
