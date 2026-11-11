<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRevisao.php';

/** Uma ação humana por dossiê. Notas mínimas não são evidência e não contam como homologadas. */
final class CuradoriaNormalizacaoMinima {
    /** Ação administrativa explícita: 25 dossiês com fonte, ainda sem decisão final. */
    public static function proximoLote(PDO $pdo,array $usuario,string $motivo): array {
        $sql="SELECT c.id_extracao FROM cur_candidatos c
          WHERE c.homologado=0 AND c.estado_revisao_humana IN ('NAO_REVISADO','EM_REVISAO')
            AND (c.situacao_identificacao IS NULL OR c.situacao_identificacao NOT IN ('CONTEXTO_NAO_MARCO','POSSIVEL_DUPLICATA'))
            AND (c.categoria IS NULL OR c.categoria NOT IN ('CONTEXTO_HISTORICO','POSSIVEL_DUPLICATA'))
            AND EXISTS(SELECT 1 FROM cur_fontes f WHERE f.id_extracao=c.id_extracao)
            AND ((SELECT COUNT(*) FROM cur_criterios t WHERE t.id_extracao=c.id_extracao)<11
                 OR EXISTS(SELECT 1 FROM cur_criterios t WHERE t.id_extracao=c.id_extracao AND (t.nota IS NULL OR t.nota=0)))
          ORDER BY c.rodada,c.id_extracao LIMIT 25";
        $ids=$pdo->query($sql)->fetchAll(PDO::FETCH_COLUMN);
        $concluidos=[];$falhas=[];$notas=0;
        foreach($ids as $id){
            try{$ret=self::aplicar($pdo,(string)$id,$usuario,$motivo);$concluidos[]=(string)$id;$notas+=$ret['total'];}
            catch(Throwable $e){$falhas[(string)$id]=$e->getMessage();}
        }
        return ['marcos'=>count($concluidos),'notas'=>$notas,'falhas'=>$falhas,'ids'=>$concluidos];
    }
    public static function aplicar(PDO $pdo,string $id,array $usuario,string $motivo): array {
        if(!CuradoriaRodadas::validId($id))throw new InvalidArgumentException('Identificador inválido.');
        if(strlen(trim($motivo))<15 || strlen(trim($motivo))>2500)throw new InvalidArgumentException('Informe justificativa de 15 a 2.500 caracteres.');
        $pdo->beginTransaction();
        try{
            $lock=$pdo->prepare('SELECT id_extracao FROM cur_candidatos WHERE id_extracao=? FOR UPDATE');
            $lock->execute([$id]);
            if(!$lock->fetchColumn())throw new InvalidArgumentException('Candidato não encontrado.');
            $r=CuradoriaRodadas::record($pdo,$id);
            if(!$r)throw new RuntimeException('Dossiê indisponível.');
            if(($r['estado_revisao_humana']??'')==='HOMOLOGADO' || (int)($r['homologado']??0)!==0)
                throw new InvalidArgumentException('Dossiê homologado: reabra a avaliação antes de editar.');
            if(($r['estado_revisao_humana']??'')==='REJEITADO')
                throw new InvalidArgumentException('Dossiê rejeitado: reabra a revisão antes de pontuar.');
            if(!$r['fontes'])throw new InvalidArgumentException('Cadastre uma fonte histórica do acontecimento antes de aplicar notas mínimas.');
            $alterados=[];
            $update=$pdo->prepare('INSERT INTO cur_criterios (id_extracao,codigo,nota,situacao,justificativa,evidencias_json,fontes_ids_json,o_que_falta) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nota=VALUES(nota),situacao=VALUES(situacao),justificativa=VALUES(justificativa),evidencias_json=VALUES(evidencias_json),fontes_ids_json=VALUES(fontes_ids_json),o_que_falta=VALUES(o_que_falta)');
            foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$rotulo){
                $old=$r['criterios'][$codigo]??null;
                $nota=$old['nota']??null;
                if($nota!==null && (string)$nota!=='0')continue;
                $descricao='Nota 1/3 atribuída PROVISORIAMENTE por convenção metodológica, por insuficiência de evidência específica para pontuação maior. Não significa impacto baixo comprovado. Necessita revisão antes de homologação.';
                $evidencias=($nota!==null && !empty($old['evidencias_json']))?(string)$old['evidencias_json']:'[]';
                $fontesIds=($nota!==null && !empty($old['fontes_ids_json']))?(string)$old['fontes_ids_json']:'[]';
                $just=($nota!==null && trim((string)($old['justificativa']??''))!=='')?(string)$old['justificativa']." | ".$descricao:$descricao;
                $update->execute([$id,$codigo,1,CuradoriaPolitica::SITUACAO_MINIMA,$just,$evidencias,$fontesIds,'Investigar fontes específicas, difusão/efeitos e independência documental antes de homologar.']);
                $alterados[$codigo]=['antes'=>$old,'nova'=>1,'situacao'=>CuradoriaPolitica::SITUACAO_MINIMA];
            }
            if($alterados){
                // Nao anunciar notas gravadas sem reler os valores nesta transacao.
                $novo=CuradoriaRodadas::record($pdo,$id);
                if(!$novo)throw new RuntimeException('Nao foi possivel confirmar o dossie apos preencher as notas.');
                foreach(array_keys($alterados) as $codigo){
                    $gravado=$novo['criterios'][$codigo]??null;
                    if(!$gravado || (string)($gravado['nota']??'')!=='1' ||
                        (string)($gravado['situacao']??'')!==CuradoriaPolitica::SITUACAO_MINIMA)
                        throw new RuntimeException('Falhou a conferencia da nota 1 no banco. Esta tentativa foi revertida.');
                }
                $pdo->prepare("UPDATE cur_candidatos SET estado_revisao_humana='EM_REVISAO',homologado=0 WHERE id_extracao=?")->execute([$id]);
                $auditoria=['usuario_id'=>$usuario['id']??null,'revisor'=>$usuario['nome']??'Pesquisador','motivo'=>$motivo,'acao'=>'PREENCHIMENTO_MINIMO_PROVISORIO','alteracoes'=>$alterados,'nota'=>'Nota 1 não comprova impacto; fase preliminar apenas.'];
                $pdo->prepare('INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (?,?,?)')
                    ->execute([$id,'NOTAS_MINIMAS_PROVISORIAS',json_encode($auditoria,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
            }
            $pdo->commit();
            return ['total'=>count($alterados),'criterios'=>array_keys($alterados)];
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
