<?php
declare(strict_types=1);
require_once __DIR__.'/CuradoriaRodadas.php';

/**
 * Confere declaracoes de possivel duplicidade vindas dos SQLs de importacao.
 * Vinculo importado NAO e prova de duplicidade. A analise textual/temporal
 * abaixo e explicativa, nao toma decisoes historicas nem homologa marcos.
 */
final class CuradoriaDuplicatas
{
    public static function chave(string $a,string $b): string
    {
        $ids=[$a,$b];sort($ids,SORT_STRING);return implode('|',$ids);
    }

    private static function entradas(?string $json): array
    {
        return CuradoriaRodadas::decodeArray((string)$json);
    }

    private static function idItem(mixed $item): ?string
    {
        $id=is_array($item)?($item['id_extracao']??$item['id']??null):$item;
        return is_string($id) && CuradoriaRodadas::validId($id)?$id:null;
    }

    public static function idsDeJson(?string $json): array
    {
        $ids=[];
        foreach(self::entradas($json) as $item){
            $id=self::idItem($item);
            if($id!==null)$ids[$id]=true;
        }
        return array_keys($ids);
    }

    /** Retira somente a referencia decidida, preservando outras suspeitas. */
    public static function semReferencia(?string $json,string $other): string
    {
        $restantes=[];
        foreach(self::entradas($json) as $item){
            if(self::idItem($item)!==$other)$restantes[]=$item;
        }
        return json_encode($restantes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }

    /** Se o importador guardar um motivo junto do ID, apresenta o texto original. */
    private static function motivoInformado(?string $json,string $other): array
    {
        $out=[];
        foreach(self::entradas($json) as $item){
            if(!is_array($item) || self::idItem($item)!==$other)continue;
            foreach(['justificativa','motivo','razao','razão','evidencia','evidência','fundamento'] as $k){
                $v=$item[$k]??null;
                if(is_string($v) && trim($v)!=='')$out[]=trim($v);
            }
        }
        return array_values(array_unique($out));
    }

    private static function palavras(string $s): array
    {
        $s=function_exists('mb_strtolower')?mb_strtolower($s,'UTF-8'):strtolower($s);
        if(function_exists('iconv')){
            $ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
            if($ascii!==false)$s=strtolower($ascii);
        }
        preg_match_all('/[a-z0-9]+/i',$s,$match);
        $stop=array_fill_keys(explode(' ','a as o os de da das do dos em no na nos nas por para pelo pela com como e ou um uma uns umas sobre entre ao aos a.c c d.c d cerca aproximada associado associada historia historica historico sistema uso desenvolvimento criacao pesquisa teoria metodo estudo origem registro registros exemplo sec'),true);
        $tokens=[];
        foreach($match[0] as $v){
            $v=strtolower($v);
            if(strlen($v)>=3 && !isset($stop[$v]) && !ctype_digit($v))$tokens[$v]=true;
        }
        return array_keys($tokens);
    }

    public static function diagnostico(array $a,array $b,bool $aApontaB,bool $bApontaA): array
    {
        $nomeA=(string)($a['titulo_proposto']??'');
        $nomeB=(string)($b['titulo_proposto']??'');
        $tokensA=self::palavras($nomeA);$tokensB=self::palavras($nomeB);
        $comuns=array_values(array_intersect($tokensA,$tokensB));
        $uniao=array_unique(array_merge($tokensA,$tokensB));
        $similaridade=count($uniao)>0?count($comuns)/count($uniao):0.0;
        $igual=$tokensA!==[] && $tokensB!==[] && implode(' ', $tokensA)===implode(' ',$tokensB);
        $catA=trim((string)($a['categoria']??''));$catB=trim((string)($b['categoria']??''));
        $catDif=$catA!=='' && $catB!=='' && strcasecmp($catA,$catB)!==0;
        $valA=$a['ano_validado']??null;$valB=$b['ano_validado']??null;
        $ambosValidados=$valA!==null && $valB!==null;
        $anoA=$valA??($a['ano_original']??null);
        $anoB=$valB??($b['ano_original']??null);
        $intervalo=$anoA!==null&&$anoB!==null?abs((int)$anoA-(int)$anoB):null;
        $motivos=array_values(array_unique(array_merge(
            self::motivoInformado($a['duplicatas_json']??null,(string)($b['id_extracao']??'')),
            self::motivoInformado($b['duplicatas_json']??null,(string)($a['id_extracao']??''))
        )));
        $pistas=[];
        if($igual)$pistas[]='Os títulos, descontando palavras genéricas, são equivalentes; conferir se representam o mesmo acontecimento.';
        elseif(count($comuns)===0)$pistas[]='Os títulos não compartilham palavras históricas relevantes.';
        else $pistas[]='Palavras em comum: '.implode(', ',array_slice($comuns,0,7)).'. Isso não comprova identidade.';
        if($catDif)$pistas[]='As categorias cadastradas são diferentes: '.$catA.' × '.$catB.'.';
        if($intervalo!==null){
            $pistas[]='Diferença de '.$intervalo.' ano(s) entre os anos usados. '.($ambosValidados?'Ambos constam como validados.':'Pelo menos uma data ainda é extraída/provisória; não usar essa diferença como prova histórica.');
        }else $pistas[]='Faltam datas para uma comparação cronológica.';
        $origem=(!$aApontaB&&!$bApontaA)
            ?'Nenhum dos dois dossiês mantém esta referência; trata-se de um parecer já encerrado.'
            :($aApontaB && $bApontaA?'O vínculo consta nos dois dossiês.':($aApontaB?'O registro A aponta o ID do registro B.':'O registro B aponta o ID do registro A.'));
        $forte=$igual && ($intervalo===null || $intervalo<=5);
        $incompativel=!$igual && count($comuns)===0 && ($catDif || ($intervalo!==null && $intervalo>100));
        if($incompativel){
            $nivel='alerta';
            $titulo='ALERTA — vínculo sem semelhança histórica aparente';
            $resumo='Há sinais de que esta associação importada seja um falso positivo. Não tratar como duplicata sem documentação de um mesmo evento.';
        }elseif($forte){
            $nivel='indicio';
            $titulo='INDÍCIO — títulos próximos; confirmação documental necessária';
            $resumo='Existe coincidência dos títulos e proximidade temporal, mas ainda é necessário verificar se descrevem o mesmo acontecimento.';
        }else{
            $nivel='incerto';
            $titulo='VÍNCULO IMPORTADO — motivo histórico não demonstrado';
            $resumo='Há uma referência cruzada no banco, mas os dados desta tela não são suficientes para declarar os eventos idênticos.';
        }
        if($motivos===[])$resumo.=' O vínculo foi salvo sem justificativa textual específica no campo de importação.';
        return [
            'nivel'=>$nivel,'titulo'=>$titulo,'resumo'=>$resumo,'pistas'=>$pistas,
            'origem'=>$origem,'motivos_importados'=>$motivos,
            'sem_justificativa'=>$motivos===[], 'similaridade_palavras'=>$similaridade,
            'tipo_data'=>$ambosValidados?'ambas validadas':'parcialmente extraídas',
        ];
    }

    public static function listar(PDO $pdo): array
    {
        $rows=$pdo->query('SELECT id_extracao,titulo_proposto,ano_original,ano_validado,categoria,situacao_identificacao,duplicatas_json,observacao_metodologica FROM cur_candidatos ORDER BY rodada,id_extracao')->fetchAll(PDO::FETCH_ASSOC);
        $map=[];foreach($rows as $r)$map[(string)$r['id_extracao']]=$r;
        $sources=[];
        foreach($pdo->query('SELECT id_extracao,fonte_id,titulo,autor_instituicao FROM cur_fontes ORDER BY id_extracao,fonte_id') as $f){
            $id=(string)$f['id_extracao'];
            if(count($sources[$id]??[])<3)$sources[$id][]=trim((string)$f['titulo']).' — '.trim((string)$f['autor_instituicao']);
        }
        foreach($map as $id=>&$r)$r['fontes']=$sources[$id]??[];
        unset($r);
        $decisions=[];
        foreach($pdo->query("SELECT descricao FROM cur_log WHERE acao='PARECER_DUPLICATA' ORDER BY id DESC LIMIT 5000") as $r){
            $data=json_decode((string)$r['descricao'],true);
            if(is_array($data)&&isset($data['par'])&&!isset($decisions[(string)$data['par']]))$decisions[(string)$data['par']]=$data;
        }
        $declarados=[];
        foreach($map as $id=>$r){
            foreach(self::idsDeJson($r['duplicatas_json']??null) as $other){
                if($id===$other)continue;
                $key=self::chave($id,$other);
                $declarados[$key]=true;
            }
        }
        // Preserva a visualizacao de pareceres "distintos" mesmo depois da baixa
        // auditavel das duas referencias; eles nao devem reaparecer como pendentes.
        foreach($decisions as $key=>$decision){
            if(($decision['decisao']??'')==='nao_duplicado')$declarados[$key]=true;
        }
        $pairs=[];
        foreach(array_keys($declarados) as $key){
            $parts=explode('|',$key);
            if(count($parts)!==2)continue;
            $a=$map[$parts[0]]??null;$b=$map[$parts[1]]??null;
            $empty=static fn(string $id)=>['id_extracao'=>$id,'titulo_proposto'=>'Não importado no banco da curadoria','ano_original'=>null,'ano_validado'=>null,'categoria'=>'Desconhecida','observacao_metodologica'=>'','duplicatas_json'=>'[]','fontes'=>[]];
            $a=$a??$empty($parts[0]);$b=$b??$empty($parts[1]);
            $ab=in_array($parts[1],self::idsDeJson($a['duplicatas_json']??null),true);
            $ba=in_array($parts[0],self::idsDeJson($b['duplicatas_json']??null),true);
            $pairs[$key]=[
                'chave'=>$key,'a'=>$a,'b'=>$b,
                'ambos_presentes'=>isset($map[$parts[0]],$map[$parts[1]]),
                'vinculo_ativo'=>$ab||$ba,'decisao'=>$decisions[$key]??null,
                'diagnostico'=>self::diagnostico($a,$b,$ab,$ba)
            ];
        }
        uasort($pairs,static function(array $a,array $b):int {
            $da=(string)($a['decisao']['decisao']??'');$db=(string)($b['decisao']['decisao']??'');
            $closed=static fn(string $d):int=>in_array($d,['manter_a','manter_b','nao_duplicado'],true)?1:0;
            $state=$closed($da)<=>$closed($db);if($state!==0)return $state;
            $priority=['alerta'=>0,'incerto'=>1,'indicio'=>2];
            $p=($priority[$a['diagnostico']['nivel']]??3)<=>($priority[$b['diagnostico']['nivel']]??3);
            return $p!==0?$p:strcmp($a['chave'],$b['chave']);
        });
        return array_values($pairs);
    }

    public static function registrar(PDO $pdo,array $post,array $user): string
    {
        $a=strtoupper(trim((string)($post['a']??'')));
        $b=strtoupper(trim((string)($post['b']??'')));
        $acao=(string)($post['decision']??'');
        if(!CuradoriaRodadas::validId($a)||!CuradoriaRodadas::validId($b)||$a===$b)
            throw new InvalidArgumentException('Par de candidatos inválido.');
        if(!in_array($acao,['manter_a','manter_b','nao_duplicado','revisao_manual'],true))
            throw new InvalidArgumentException('Decisão inválida.');
        $motivo=trim((string)($post['motivo']??''));
        $min=$acao==='revisao_manual'?15:40;
        if(strlen($motivo)<$min || strlen($motivo)>1000)
            throw new InvalidArgumentException('Explique sua decisão com '.$min.' a 1.000 caracteres. Informe as diferenças ou evidências relevantes.');
        if(empty($post['confirmacao_comparacao']))
            throw new InvalidArgumentException('Confirme que conferiu os dois eventos antes de registrar a decisão.');
        $pair=self::chave($a,$b);
        $pdo->beginTransaction();
        try{
            $stmt=$pdo->prepare('SELECT id_extracao,duplicatas_json FROM cur_candidatos WHERE id_extracao IN (?,?) FOR UPDATE');
            $stmt->execute([$a,$b]);$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)!==2)throw new InvalidArgumentException('Os dois registros precisam estar importados.');
            $links=[];$originais=[];
            foreach($rows as $r){
                $id=(string)$r['id_extracao'];$originais[$id]=(string)($r['duplicatas_json']??'[]');
                $links[$id]=self::idsDeJson($originais[$id]);
            }
            if(!in_array($b,$links[$a]??[],true)&&!in_array($a,$links[$b]??[],true))
                throw new InvalidArgumentException('A relação já foi encerrada ou não está cadastrada. Atualize a tela.');
            $principal=$acao==='manter_a'?$a:($acao==='manter_b'?$b:null);
            $atualizados=[];
            if($acao==='nao_duplicado'){
                $up=$pdo->prepare('UPDATE cur_candidatos SET duplicatas_json=? WHERE id_extracao=?');
                foreach([[$a,$b],[$b,$a]] as [$id,$other]){
                    $novo=self::semReferencia($originais[$id],$other);
                    if($novo!==$originais[$id]){$up->execute([$novo,$id]);$atualizados[$id]=['antes'=>$originais[$id],'depois'=>$novo];}
                }
            }
            $json=json_encode([
                'par'=>$pair,'decisao'=>$acao,'principal_id'=>$principal,
                'secundario_id'=>$principal===null?null:($principal===$a?$b:$a),
                'motivo'=>$motivo,'confirmacao_comparacao'=>true,'usuario_id'=>$user['id']??null,
                'revisor'=>$user['nome']??'Pesquisador','referencias_removidas'=>$atualizados,
                'observacao'=>$acao==='nao_duplicado'?'Referencias cruzadas removidas apos parecer humano; sem exclusao, sem homologacao e sem alteracao das notas.':'Parecer preservado; sem exclusao, sem homologacao e sem alteracao das notas.'
            ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $pdo->prepare("INSERT INTO cur_log (id_extracao,acao,descricao) VALUES (?,'PARECER_DUPLICATA',?)")->execute([$a,$json]);
            $pdo->commit();
            return $acao==='nao_duplicado'
                ? 'Os dois eventos foram registrados como distintos. Apenas o vínculo de duplicidade entre eles foi retirado dos dois dossiês, com histórico no MySQL.'
                : 'Parecer registrado com justificativa. Os possíveis vínculos permanecem para conferência e não houve homologação.';
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
    }
}
