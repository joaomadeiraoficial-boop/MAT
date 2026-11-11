<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/CuradoriaAuditoriaAutomatica.php';
$checks=0;
function ensure(bool $condition,string $name):void{global $checks;$checks++;if(!$condition)throw new RuntimeException('FAIL '.$name);echo '[OK] '.$name."\n";}
function recordBase(string $id='MCD-00001'):array{
    $criteria=[];foreach(CuradoriaRodadas::CRITERIOS as $code=>$description)$criteria[$code]=['nota'=>2,'justificativa'=>'Justificativa factual documentada com fontes independentes.','fontes_ids_json'=>'["BIBLIO","MUSEU"]'];
    return ['id_extracao'=>$id,'rodada'=>1,'titulo_proposto'=>'Tecnologia de teste 1901',
        'ano_original'=>1901,'ano_validado'=>1901,'estado_revisao_humana'=>'NAO_REVISADO','homologado'=>0,
        'criterios'=>$criteria,'fontes'=>[
            ['fonte_id'=>'BIBLIO','autor_instituicao'=>'Universidade de São Paulo','url'=>'https://example.org/referencias'],
            ['fonte_id'=>'MUSEU','autor_instituicao'=>'Museu de Ciência','url'=>'https://example.edu/referencias']],
        'pendencias'=>[],'duplicatas'=>[]];
}
$r=CuradoriaAuditoriaAutomatica::avaliar(recordBase());
ensure($r['categoria_auditoria']==='apto','Dossiê completo é apto à auditoria amostral');
ensure($r['notas_validas']===11,'Onze notas reconhecidas');
ensure($r['fontes_quantidade']===2 && $r['instituicoes_quantidade']===2,'Duas fontes distintas identificadas');
$bad=recordBase();$bad['criterios']['impacto_difusao']['nota']=null;
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='incompleto','Nota ausente é pendência');
$bad=recordBase();$bad['ano_validado']=null;
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='incompleto','Data não validada é pendência');
$bad=recordBase();$bad['fontes']=[['fonte_id'=>'BIBLIO','autor_instituicao'=>'Universidade','url'=>'https://example.org']];foreach($bad['criterios'] as &$c)$c['fontes_ids_json']='["BIBLIO"]';unset($c);
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='fontes','Uma fonte é sinalizada para revisão');
$bad=recordBase();$bad['fontes'][1]['autor_instituicao']='Universidade de São Paulo';
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='fontes','Duas referências do mesmo autor não provam independência');
$bad=recordBase();$bad['duplicatas']=['MCD-00002'];
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='duplicata','Possível duplicata exige investigação');
$bad=recordBase();$bad['pendencias']=['Estabelecer autoria'];
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='alerta','Pendências históricas têm prioridade');
$bad=recordBase();$bad['situacao_identificacao']='CONTEXTO_NAO_MARCO';
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='contexto','Contexto tem fila própria');
$bad=recordBase();$bad['situacao_identificacao']='CONTEXTO_NAO_MARCO';$bad['criterios']['impacto_difusao']['nota']=null;
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='contexto','Contexto prevalece sobre notas pendentes');
$bad=recordBase();$bad['ano_validado']=1920;
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='alerta','Divergência grande de ano dispara alerta');
$bad=recordBase();$bad['estado_revisao_humana']='HOMOLOGADO';
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='finalizado','Homologado não volta à fila automaticamente');
$bad=recordBase();$bad['estado_revisao_humana']='REJEITADO';
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='finalizado','Rejeitado não é excluído nem reaberto pela varredura');
$bad=recordBase();$bad['criterios']['impacto_difusao']['nota']=0;
ensure(CuradoriaAuditoriaAutomatica::avaliar($bad)['categoria_auditoria']==='apto','Nota zero explícita não é confundida com falta de evidência');
$items=[];for($i=1;$i<=20;$i++){
    $r=recordBase('MCD-'.str_pad((string)$i,5,'0',STR_PAD_LEFT));$r['rodada']=(int)ceil($i/10);$items[]=CuradoriaAuditoriaAutomatica::avaliar($r);
}
$sample=CuradoriaAuditoriaAutomatica::amostragem($items);
ensure(count($sample)===4,'Amostra de 20% em grupo de 20');
ensure($sample===CuradoriaAuditoriaAutomatica::amostragem($items),'Amostra reproduzível');
ensure(count(array_unique($sample))===count($sample),'Amostra sem repetição');
$rounds=[];foreach($sample as $id){$n=(int)substr($id,4);$rounds[$n<=10?1:2]=true;}
ensure(count($rounds)===2,'Amostra inclui diferentes rodadas');
$items[0]['categoria_auditoria']='finalizado';
ensure(count(CuradoriaAuditoriaAutomatica::amostragem($items))===4,'Finalizados são excluídos da amostragem');
$snap=['itens'=>array_combine(array_column($items,'id_extracao'),$items)];
ensure(count(CuradoriaAuditoriaAutomatica::filtro($snap,'amostra',0,''))===0,'Amostra só aparece quando marcada');
ensure(count(CuradoriaAuditoriaAutomatica::filtro($snap,'todos',2,''))===10,'Filtro por rodada funciona');
ensure(count(CuradoriaAuditoriaAutomatica::filtro($snap,'todos',0,'MCD-00015'))===1,'Busca por ID funciona');
ensure(CuradoriaAuditoriaAutomatica::amostragem([])===[],'Base vazia não gera amostra artificial');
echo "SUCESSO: $checks testes da lógica de auditoria automática.\n";
