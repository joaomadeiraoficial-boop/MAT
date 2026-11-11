<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRevisao.php';

/** Fila humana para TODOS os dossiês (0–11 notas). Nenhuma aprovação é automática. */
final class CuradoriaFilaRapida {
    public const FILTROS = [
        'pendentes' => 'Aguardando decisão',
        'incompletos' => 'Notas incompletas',
        'aptos' => 'Prontos para revisar',
        'bloqueados' => 'Precisam de correção',
        'aprovados' => 'Aprovados',
        'homologados' => 'Homologados',
        'todos' => 'Todos',
    ];
    public const PROBLEMAS = [
        '' => 'Todos os problemas',
        'ano' => 'Data histórica',
        'pendencias' => 'Pendências',
        'duplicatas' => 'Duplicatas',
        'fontes' => 'Fontes',
        'rubricas' => 'Rubricas',
    ];
    public static function problema(string $value): string {
        return array_key_exists($value,self::PROBLEMAS) ? $value : '';
    }
    public static function tiposProblema(array $r): array {
        $types=[];
        $simple=CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO;
        if((!CuradoriaDatacao::temData($r) && (!$simple || !CuradoriaDatacao::anoValido($r['ano_original']??null))) || (!$simple && !CuradoriaDatacao::conferirFonte($r))) $types[]='ano';
        if(!$simple && !empty($r['pendencias']))$types[]='pendencias';
        if(!$simple && !empty($r['duplicatas']))$types[]='duplicatas';
        $fontes=[];
        foreach(($r['fontes']??[]) as $f) if(!empty($f['fonte_id']))$fontes[$f['fonte_id']]=true;
        if(!$fontes)$types[]='fontes';
        foreach(CuradoriaRodadas::CRITERIOS as $code=>$nome){
            $c=$r['criterios'][$code]??null;
            if(!$c || ($c['nota']??null)===null || !preg_match($simple?'/^[1-3]$/D':'/^[0-3]$/D',(string)$c['nota']) || (!$simple && trim((string)($c['justificativa']??''))==='')){
                $types[]='rubricas';continue;
            }
            $ids=CuradoriaRodadas::decodeArray((string)($c['fontes_ids_json']??''));
            if(!$ids){if(!$simple)$types[]='rubricas';continue;}
            foreach($ids as $id)if(!isset($fontes[(string)$id])){$types[]='rubricas';break;}
        }
        return array_values(array_unique($types));
    }
    public static function filtro(string $value): string {
        return isset(self::FILTROS[$value]) ? $value : 'pendentes';
    }
    /** Para mostrar o que está completo, carregamos todos os onze critérios, sem N+1 consultas. */
    public static function consultar(PDO $pdo, string $filtro='pendentes', string $busca='', string $id='', string $problema=''): array {
        $filtro=self::filtro($filtro);
        $sql='SELECT c.id_extracao,c.titulo_proposto,c.ano_original,c.ano_validado,c.rodada,c.estado_revisao_humana,c.homologado,'
            .'c.situacao_identificacao,c.categoria,c.pendencias_json,c.duplicatas_json,c.analise_historica_json '
            .'FROM cur_candidatos c '
            .'ORDER BY c.rodada ASC,c.id_extracao ASC';
        $candidatos=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        if(!$candidatos) return self::organizar([], $filtro, $busca, $id, $problema);
        $ids=array_column($candidatos,'id_extracao');
        $placeholders=implode(',',array_fill(0,count($ids),'?'));
        $fontes=[];$criterios=[];
        $stmt=$pdo->prepare('SELECT id_extracao,fonte_id FROM cur_fontes WHERE id_extracao IN ('.$placeholders.')');
        $stmt->execute($ids);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $fontes[$row['id_extracao']][]=$row;
        $stmt=$pdo->prepare('SELECT id_extracao,codigo,nota,situacao,justificativa,fontes_ids_json FROM cur_criterios WHERE id_extracao IN ('.$placeholders.')');
        $stmt->execute($ids);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $criterios[$row['id_extracao']][$row['codigo']]=$row;
        $items=[];
        foreach($candidatos as $c){
            $key=$c['id_extracao'];
            $c['pendencias']=CuradoriaRodadas::decodeArray((string)($c['pendencias_json']??''));
            $c['duplicatas']=CuradoriaRodadas::decodeArray((string)($c['duplicatas_json']??''));
            $c['fontes']=$fontes[$key]??[];
            $c['criterios']=$criterios[$key]??[];
            $c['quantidade_notas']=0;
            foreach(CuradoriaRodadas::CRITERIOS as $code=>$label) {
                $value=$c['criterios'][$code]['nota']??null;
                if($value!==null && preg_match('/^[0-3]$/D',(string)$value))$c['quantidade_notas']++;
            }
            $c['proxima_acao']=self::proximaAcao($c);
            $c['bloqueios']=CuradoriaRevisao::impedimentos($c);
            $c['tipos_problema']=self::tiposProblema($c);
            $c['quantidade_fontes']=count($c['fontes']);
            $c['quantidade_bloqueios']=count($c['bloqueios']);
            // Não transportar todas as notas na lista: a ficha selecionada será carregada do banco.
            unset($c['fontes'],$c['criterios']);
            $items[]=$c;
        }
        return self::organizar($items,$filtro,$busca,$id,$problema);
    }
    /** Orientação operacional; não é avaliação histórica nem sugestão de nota. */
    public static function proximaAcao(array $r): string {
        if(trim((string)($r['titulo_proposto']??''))==='')return 'Identificar o acontecimento e conferir o trecho original.';
        if(CuradoriaPolitica::modo()===CuradoriaPolitica::SIMPLIFICADO){
            if(!($r['fontes']??[]))return 'Cadastrar ao menos uma fonte do acontecimento.';
            foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$nome){$nota=$r['criterios'][$codigo]['nota']??null;if($nota===null || (string)$nota==='0')return 'Preencher nota mínima 1 provisória, documentando a limitação.';}
            return 'Conferir alertas e decidir a aprovação preliminar.';
        }
        if(!empty($r['duplicatas']))return 'Investigar possível duplicidade antes de pontuar.';
        if(!($r['fontes']??[]))return 'Cadastrar fonte documental consultada.';
        if(!CuradoriaDatacao::temData($r) || !CuradoriaDatacao::conferirFonte($r))
            return 'Conferir data histórica e fonte da datação.';
        if(!empty($r['pendencias']))return 'Investigar pendências históricas individualmente.';
        foreach(CuradoriaRodadas::CRITERIOS as $codigo=>$nome){
            $crit=$r['criterios'][$codigo]??null;
            if(!$crit || ($crit['nota']??null)===null || !preg_match('/^[0-3]$/D',(string)$crit['nota']))
                return 'Pesquisar evidências para a rubrica: '.$nome.'.';
        }
        if(CuradoriaRodadas::memory($r['criterios']??[],$r['fontes']??[])['completa']!==true)
            return 'Corrigir justificativas e vínculos documentais das rubricas.';
        return 'Conferir pessoalmente o dossiê e registrar parecer.';
    }

    /** Função pura, testável mesmo quando o MySQL não está disponível. */
    public static function organizar(array $items, string $filtro, string $busca, string $id, string $problema=''): array {
        $problema=self::problema($problema);
        $filtro=self::filtro($filtro);
        $busca=substr(trim($busca),0,100);
        $contagens=['todos'=>count($items),'pendentes'=>0,'incompletos'=>0,'aptos'=>0,'bloqueados'=>0,'aprovados'=>0,'homologados'=>0];
        foreach($items as $r) {
            $state=(string)($r['estado_revisao_humana']??'NAO_REVISADO');
            if(in_array($state,['NAO_REVISADO','EM_REVISAO'],true)){
                $contagens['pendentes']++;
                if((int)($r['quantidade_notas']??11)<11)$contagens['incompletos']++;
                $contagens[empty($r['bloqueios'])?'aptos':'bloqueados']++;
            } elseif($state==='APROVADO')$contagens['aprovados']++;
            elseif($state==='HOMOLOGADO')$contagens['homologados']++;
        }
        $problemaCounts=array_fill_keys(array_keys(self::PROBLEMAS),0);
        $out=[];
        foreach($items as $r) {
            $state=(string)($r['estado_revisao_humana']??'NAO_REVISADO');
            $pending=in_array($state,['NAO_REVISADO','EM_REVISAO'],true);
            $ok=match($filtro){
                'pendentes'=>$pending,
                'incompletos'=>$pending && (int)($r['quantidade_notas']??11)<11,
                'aptos'=>$pending && empty($r['bloqueios']),
                'bloqueados'=>$pending && !empty($r['bloqueios']),
                'aprovados'=>$state==='APROVADO',
                'homologados'=>$state==='HOMOLOGADO',
                default=>true,
            };
            if(!$ok)continue;
            if($filtro==='bloqueados'){
                foreach(($r['tipos_problema']??[]) as $tipo)if(isset($problemaCounts[$tipo]))$problemaCounts[$tipo]++;
                $problemaCounts['']++;
            }
            if($problema!=='' && $filtro==='bloqueados' && !in_array($problema,$r['tipos_problema']??[],true))continue;
            if($busca!=='' && stripos((string)$r['id_extracao'].' '.(string)$r['titulo_proposto'],$busca)===false)continue;
            $out[]=$r;
        }
        // Nos bloqueados, resolver primeiro o que demanda menos ações costuma ser mais eficiente.
        if($filtro==='incompletos')usort($out,static function(array $a,array $b):int {
            return (int)($b['quantidade_notas']??0) <=> (int)($a['quantidade_notas']??0)
                ?: strcmp((string)$a['id_extracao'],(string)$b['id_extracao']);
        });
        if($filtro==='bloqueados')usort($out,static function(array $a,array $b):int {
            return count($a['bloqueios']??[]) <=> count($b['bloqueios']??[])
                ?: ((int)($a['rodada']??0) <=> (int)($b['rodada']??0))
                ?: strcmp((string)$a['id_extracao'],(string)$b['id_extracao']);
        });
        $active=-1;
        foreach($out as $n=>$row) if($row['id_extracao']===$id){$active=$n;break;}
        if($active===-1 && count($out)>0)$active=0;
        return ['rows'=>$out,'selected'=>$active>=0?$out[$active]:null,
            'position'=>$active>=0?$active+1:0,'total'=>count($out),
            'prev'=>$active>0?$out[$active-1]['id_extracao']:null,
            'next'=>$active>=0&&$active<count($out)-1?$out[$active+1]['id_extracao']:null,
            'counts'=>$contagens,'filter'=>$filtro,'search'=>$busca,
            'problema'=>$problema,'problema_counts'=>$problemaCounts];
    }
}
