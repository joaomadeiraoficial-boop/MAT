<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST'){http_response_code(405);exit('Metodo nao permitido');}
verify_csrf();
$action=(string)($_POST['action']??'');
if($action==='login') {
    if(!db()){flash('error','Configure o banco antes de acessar a administração.');redirect_to('login');}
    $email=trim((string)($_POST['email']??''));$password=(string)($_POST['senha']??'');
    $q=db()->prepare('SELECT id,nome,email,senha_hash FROM usuarios WHERE email=? LIMIT 1');$q->execute([$email]);$u=$q->fetch();
    if($u && password_verify($password,$u['senha_hash'])) {session_regenerate_id(true);$_SESSION['mat_user']=['id'=>$u['id'],'nome'=>$u['nome'],'email'=>$u['email']];flash('success','Sessão iniciada.');redirect_to('acervo');}
    flash('error','Credenciais inválidas.');redirect_to('login');
}
if($action==='logout'){$_SESSION=[];session_regenerate_id(true);flash('success','Sessão encerrada.');redirect_to('observatorio');}
require_admin();
if(!db()){http_response_code(503);exit('Banco de dados indisponivel.');}

// Modulo cientifico importado de forma nativa na estrutura do Observatorio.
if($action==='generate_scope'){
    $id=(int)($_POST['id']??0);$m=find_marker($id);
    if(!$m){flash('error','Dossiê não encontrado.');redirect_to('triagem');}
    $result=scope_preview($m);
    $q=db()->prepare('UPDATE marcos SET natureza=?,sugestao_elegibilidade=?,classificador_versao=?,scope_score=?,scope_confidence=? WHERE id=?');
    $q->execute([$result['natureza'],$result['sugestao_elegibilidade'],$result['classificador_versao'],$result['scope_score'],$result['scope_confidence'],$id]);
    science_log($id,'sugestao_automatica',[],['versao'=>$result['classificador_versao'],'sugestao'=>$result['sugestao_elegibilidade'],'motivo'=>$result['motivo_escopo']]);
    flash('success','Sugestão registrada. A aprovação continua exclusivamente humana.');redirect_to('triagem',['id'=>$id]);
}
if($action==='scope_review'){
    $id=(int)($_POST['id']??0);$m=find_marker($id);
    if(!$m){flash('error','Dossiê não encontrado.');redirect_to('triagem');}
    $decision=(string)($_POST['elegibilidade_temporis']??'revisar');
    if(!in_array($decision,['sim','nao','revisar'],true))$decision='revisar';
    $reason=trim((string)($_POST['motivo_escopo']??''));
    if(strlen($reason)<15){flash('error','Justifique o parecer em pelo menos 15 caracteres.');redirect_to('triagem',['id'=>$id]);}
    $include=!empty($_POST['incluir_temporis']);
    if($include && ($decision!=='sim'||($m['status']??'')!=='aprovado'||count(linked_sources($id))<1 || marker_score(array_merge($m,['elegibilidade_temporis'=>'sim','escopo_revisado'=>1,'incluir_temporis'=>1,'fonte_vinculada'=>1]))===null)){
        flash('error','A inclusão exige escopo SIM, status validado, fonte vinculada e notas completas. Registre o parecer sem ativar inclusão.');redirect_to('triagem',['id'=>$id]);
    }
    db()->beginTransaction();
    try{
        $q=db()->prepare('UPDATE marcos SET elegibilidade_temporis=?, motivo_escopo=?, escopo_revisado=1, incluir_temporis=? WHERE id=?');
        $q->execute([$decision,$reason,($include && $decision==='sim')?1:0,$id]);
        science_log($id,'parecer_escopo',['decision'=>$m['elegibilidade_temporis'],'reason'=>$m['motivo_escopo']],['decision'=>$decision,'reason'=>$reason,'incluir_temporis'=>(int)($include&&$decision==='sim')]);
        db()->commit();flash('success','Parecer humano registrado no histórico.');
    }catch(Throwable $e){db()->rollBack();error_log($e->getMessage());flash('error','Não foi possível registrar o parecer.');}
    redirect_to('triagem',['id'=>$id]);
}
if($action==='source_add'){
    $title=trim((string)($_POST['titulo']??''));$authors=trim((string)($_POST['autores']??''));$type=(string)($_POST['tipo']??'outra');
    if($title===''||strlen($title)>255){flash('error','Título inválido.');redirect_to('fontes');}
    if(!in_array($type,['livro','artigo','instituicao','catalogo','outra'],true))$type='outra';
    $url=trim((string)($_POST['url']??''));if($url && (!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with(strtolower($url),'https://'))){flash('error','A URL deve começar por https://');redirect_to('fontes');}
    $year=trim((string)($_POST['ano_publicacao']??''));$year=($year!==''&&preg_match('/^-?\d{1,5}$/',$year))?(int)$year:null;
    $q=db()->prepare('INSERT INTO fontes(titulo,autores,ano_publicacao,tipo,url) VALUES(?,?,?,?,?)');
    $q->execute([$title,$authors?:null,$year,$type,$url?:null]);flash('success','Referência incluída. Vincule-a a um dossiê para oferecer evidência.');redirect_to('fontes');
}
if($action==='source_link'){
    $id=(int)($_POST['marco_id']??0);$source=(int)($_POST['fonte_id']??0);$role=(string)($_POST['papel']??'confirmacao');
    if(!in_array($role,['origem','confirmacao','data','impacto','fundacional','confiabilidade','contexto'],true))$role='confirmacao';
    if(!find_marker($id)){flash('error','Dossiê inexistente.');redirect_to('fontes');}
    $chk=db()->prepare('SELECT id FROM fontes WHERE id=?');$chk->execute([$source]);if(!$chk->fetchColumn()){flash('error','Fonte inexistente.');redirect_to('fontes');}
    $page=substr(trim((string)($_POST['pagina']??'')),0,70);$excerpt=trim((string)($_POST['trecho']??''));
    db()->beginTransaction();try{
        $q=db()->prepare('INSERT INTO marco_fontes(marco_id,fonte_id,papel,pagina,trecho) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE pagina=VALUES(pagina),trecho=VALUES(trecho)');
        $q->execute([$id,$source,$role,$page,$excerpt]);
        science_log($id,'vinculo_fonte',[],['fonte_id'=>$source,'papel'=>$role,'pagina'=>$page]);
        db()->commit();flash('success','Fonte vinculada ao dossiê.');
    }catch(Throwable $e){db()->rollBack();error_log($e->getMessage());flash('error','Não foi possível vincular a fonte.');}
    redirect_to('fontes',['marco_id'=>$id]);
}
if($action==='duplicates_scan'){
    try{$result=rescan_duplicates();flash('success',sprintf('Análise de %d registros: %d pares pendentes.%s',$result['checked'],$result['pending'],$result['limited']?' Resultado parcial (limite de 1500).':''));}
    catch(Throwable $e){error_log($e->getMessage());flash('error','Falha na comparação de registros.');}
    redirect_to('duplicatas');
}
if($action==='duplicate_decision'){
    $id=(int)($_POST['id']??0);$decision=(string)($_POST['decision']??'');
    if(!in_array($decision,['nao_duplicado','revisao_manual'],true)){flash('error','Decisão inválida.');redirect_to('duplicatas');}
    $q=db()->prepare("UPDATE duplicate_candidates SET status=?,revisado_em=NOW() WHERE id=? AND status='pendente'");$q->execute([$decision,$id]);
    flash('success',$q->rowCount()?'Parecer registrado. Nenhum marco foi apagado.':'O par não está pendente.');redirect_to('duplicatas');
}

if($action==='save'){
    $id=(int)($_POST['id']??0);$existing=$id?find_marker($id):null;
    $title=trim((string)($_POST['titulo']??''));$yearText=trim((string)($_POST['ano']??''));
    if($title==='' || strlen($title)>250 || !preg_match('/^-?\d{1,7}$/',$yearText)) {flash('error','Informe título e ano válido.');redirect_to('editar',$id?['id'=>$id]:[]);}
    $year=(int)$yearText;
    $category=(string)($_POST['categoria']??'Outros');if(!in_array($category,get_categories(),true))$category='Outros';
    $status=(string)($_POST['status']??'pendente');if(!in_array($status,['demonstracao','importado','pendente','aprovado','rejeitado'],true))$status='pendente';
    $impact=($_POST['impacto']??'')!==''?(float)$_POST['impacto']:null;
    $conf=($_POST['confiabilidade']??'')!==''?(float)$_POST['confiabilidade']:null;
    $foundation=($_POST['fator_fundacional']??'')!==''?(float)$_POST['fator_fundacional']:null;
    if(($impact!==null&&($impact<1||$impact>5))||($conf!==null&&($conf<1||$conf>5))||($foundation!==null&&($foundation<1||$foundation>1.5))) {flash('error','Pontuações fora da escala experimental definida.');redirect_to('editar',$id?['id'=>$id]:[]);}
    $linked=$id?count(linked_sources($id)):0;
    if($status==='aprovado' && ($impact===null || $conf===null || $foundation===null || trim((string)($_POST['criterio_inclusao']??''))==='' || $linked<1 || ($existing['elegibilidade_temporis']??'')!=='sim' || empty($existing['escopo_revisado']))){
        flash('error','Para validar: fonte bibliográfica vinculada, escopo revisado como SIM, notas e justificativa são obrigatórios. Salve antes em revisão.');redirect_to('editar',$id?['id'=>$id]:[]);
    }
    $url=trim((string)($_POST['referencia_url']??''));if($url && (!filter_var($url,FILTER_VALIDATE_URL)||!preg_match('~^https?://~i',$url))){$url='';}
    $img=trim((string)($_POST['imagem_url']??''));if($img && (!filter_var($img,FILTER_VALIDATE_URL)||!preg_match('~^https://~i',$img)))$img='';
    $endYear=trim((string)($_POST['ano_fim']??''));$endYear=($endYear!==''&&preg_match('/^-?\d+$/',$endYear))?(int)$endYear:null;
    $precision=(string)($_POST['precisao_data']??'aproximada');if(!in_array($precision,['exata','aproximada','intervalo','incerta'],true))$precision='aproximada';
    if($endYear!==null && $endYear<$year){flash('error','O fim do intervalo não pode ser anterior ao início.');redirect_to('editar',$id?['id'=>$id]:[]);}
    $fields=[
        'titulo'=>$title,'ano'=>$year,'ano_fim'=>$endYear,'precisao_data'=>$precision,'periodo'=>period_from_year($year),'categoria'=>$category,
        'descricao'=>trim((string)($_POST['descricao']??'')), 'detalhes'=>trim((string)($_POST['detalhes']??'')),
        'local'=>trim((string)($_POST['local']??'')), 'civilizacao'=>trim((string)($_POST['civilizacao']??'')),
        'criador'=>trim((string)($_POST['criador']??'')), 'fonte'=>trim((string)($_POST['fonte']??'')),
        'referencia_url'=>$url, 'fonte_pagina'=>trim((string)($_POST['fonte_pagina']??'')),
        'criterio_inclusao'=>trim((string)($_POST['criterio_inclusao']??'')), 'etiquetas'=>trim((string)($_POST['etiquetas']??'')),
        'imagem_url'=>$img, 'impacto'=>$impact,'confiabilidade'=>$conf,'fator_fundacional'=>$foundation,'status'=>$status,'incluir_temporis'=>$status==='aprovado'?($existing['incluir_temporis']??0):0
    ];
    if($fields['descricao']===''){flash('error','A descrição é obrigatória.');redirect_to('editar',$id?['id'=>$id]:[]);}
    try {
      db()->beginTransaction();
      if($existing){$set=implode(', ',array_map(fn($k)=>"`$k`=:$k",array_keys($fields)));$stmt=db()->prepare("UPDATE marcos SET $set WHERE id=:id");$stmt->execute($fields+['id'=>$id]);}
      else{$keys=array_keys($fields);$cols=implode(',',array_map(fn($k)=>"`$k`",$keys));$holders=implode(',',array_map(fn($k)=>':'.$k,$keys));$stmt=db()->prepare("INSERT INTO marcos($cols) VALUES($holders)");$stmt->execute($fields);$id=(int)db()->lastInsertId();}
      $log=db()->prepare('INSERT INTO revisoes(marco_id,usuario_id,operacao,dados_anteriores,dados_novos) VALUES(?,?,?,?,?)');
      $log->execute([$id,current_user()['id'],$existing?'atualizacao':'criacao',json_encode($existing,JSON_UNESCAPED_UNICODE),json_encode($fields,JSON_UNESCAPED_UNICODE)]);
      db()->commit();flash('success','Marco registrado e alteração documentada.');redirect_to('marco',['id'=>$id]);
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();error_log($e->getMessage());flash('error','Não foi possível salvar. Verifique se já existe um marco com o mesmo nome e ano.');redirect_to('editar',$id?['id'=>$id]:[]);}
}
if($action==='delete'){
    $id=(int)($_POST['id']??0);$m=find_marker($id);
    if($m){$stmt=db()->prepare('UPDATE marcos SET status=? WHERE id=?');$stmt->execute(['rejeitado',$id]);
    $log=db()->prepare('INSERT INTO revisoes(marco_id,usuario_id,operacao,dados_anteriores,dados_novos) VALUES(?,?,?,?,?)');
    $log->execute([$id,current_user()['id'],'exclusao_logica',json_encode($m,JSON_UNESCAPED_UNICODE),'{"status":"rejeitado"}']);}
    flash('success','Marco retirado da seleção, preservado no histórico.');redirect_to('acervo');
}
if($action==='import_csv'){
    if(!isset($_FILES['arquivo']) || ($_FILES['arquivo']['error']??1)!==UPLOAD_ERR_OK || ($_FILES['arquivo']['size']??0)>2*1024*1024){flash('error','Envie um CSV de até 2 MB.');redirect_to('importar');}
    $file=$_FILES['arquivo']['tmp_name'];$name=(string)$_FILES['arquivo']['name'];
    if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='csv'){flash('error','Somente CSV é permitido.');redirect_to('importar');}
    $digest=hash_file('sha256',$file);$f=fopen($file,'rb');
    if(!$f){flash('error','Falha ao ler CSV.');redirect_to('importar');}
    $first=fgets($f);rewind($f);$delimiter=substr_count((string)$first,';')>substr_count((string)$first,',')?';':',';
    $header=fgetcsv($f,0,$delimiter,'"','');if(!$header){fclose($f);flash('error','CSV vazio.');redirect_to('importar');}
    $header=array_map(static function($key){$key=preg_replace('/^\xEF\xBB\xBF/','',trim((string)$key));return strtolower($key);},$header);
    if((!in_array('titulo',$header,true)&&!in_array('nome',$header,true))||!in_array('ano',$header,true)){fclose($f);flash('error','Cabeçalho obrigatório: titulo (ou nome) e ano.');redirect_to('importar');}
    try{
       db()->beginTransaction();$stmt=db()->prepare('INSERT INTO import_lotes(arquivo_nome,arquivo_sha256) VALUES(?,?)');$stmt->execute([substr($name,0,255),$digest]);$batch=(int)db()->lastInsertId();
       $stmt=db()->prepare('INSERT INTO import_itens(lote_id,numero_linha,bruto_json,titulo,ano,categoria,descricao,fonte,status,erro) VALUES(?,?,?,?,?,?,?,?,?,?)');
       $num=1;$count=0;
       while(($row=fgetcsv($f,0,$delimiter,'"',''))!==false){$num++;if(!array_filter($row,fn($x)=>trim((string)$x)!==''))continue;
          if(count($row)!==count($header)){$row=array_slice(array_pad($row,count($header),''),0,count($header));}
          $assoc=array_combine($header,$row);$title=trim((string)($assoc['titulo']??$assoc['nome']??''));$year=trim((string)($assoc['ano']??''));
          $valid=$title!==''&&strlen($title)<=250&&preg_match('/^-?\d{1,7}$/',$year);
          $stmt->execute([$batch,$num,json_encode($assoc,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),substr($title,0,250),$valid?(int)$year:null,substr((string)($assoc['categoria']??'Outros'),0,90),$assoc['descricao']??'',substr((string)($assoc['fonte']??''),0,350),$valid?'pendente':'invalido',$valid?null:'Título/ano inválidos']);
          $count++;if($count>=5000)break;
       }
       fclose($f);$up=db()->prepare('UPDATE import_lotes SET total_itens=? WHERE id=?');$up->execute([$count,$batch]);db()->commit();
       flash('success',"Lote #$batch recebido: $count linhas na área de preparação. Nada foi aprovado automaticamente.");redirect_to('importar');
    }catch(Throwable $e){if(is_resource($f))fclose($f);if(db()->inTransaction())db()->rollBack();error_log($e->getMessage());flash('error','Não foi possível importar: lote possivelmente repetido.');redirect_to('importar');}
}
if($action==='promote'){
    $id=(int)($_POST['id']??0);$stmt=db()->prepare('SELECT * FROM import_itens WHERE id=? AND status=? FOR UPDATE');
    try{db()->beginTransaction();$stmt->execute([$id,'pendente']);$r=$stmt->fetch();if(!$r)throw new RuntimeException('Item não localizado.');
        $check=db()->prepare('SELECT id FROM marcos WHERE titulo=? AND ano=?');$check->execute([$r['titulo'],$r['ano']]);$found=$check->fetchColumn();
        if($found){$st=db()->prepare('UPDATE import_itens SET status=?,marco_id=? WHERE id=?');$st->execute(['duplicado',$found,$id]);db()->commit();flash('error','Duplicata identificada: registro já existe.');redirect_to('importar');}
        $category=trim((string)$r['categoria']); if($category==='')$category='Outros';
        $raw=json_decode((string)$r['bruto_json'],true);if(!is_array($raw))$raw=[];
        $location=trim((string)($raw['cidade']??$raw['local']??$raw['pais']??''));
        $civil=trim((string)($raw['civilizacao']??''));$author=trim((string)($raw['inventores']??$raw['criador']??''));
        $details='Registro importado de um lote histórico. Confira o dado bruto de origem e todas as notas antes de validar.';
        $st=db()->prepare('INSERT INTO marcos(titulo,ano,periodo,categoria,descricao,detalhes,local,civilizacao,criador,fonte,status,import_item_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([$r['titulo'],$r['ano'],period_from_year((int)$r['ano']),$category,trim((string)$r['descricao'])?:'Registro importado, descrição pendente.',$details,$location,$civil,$author,(string)$r['fonte'],'importado',$id]);$newId=(int)db()->lastInsertId();
        $up=db()->prepare('UPDATE import_itens SET status=?,marco_id=? WHERE id=?');$up->execute(['convertido',$newId,$id]);db()->commit();flash('success','Registro promovido ao acervo, ainda não validado.');redirect_to('editar',['id'=>$newId]);
    }catch(Throwable $e){if(db()->inTransaction())db()->rollBack();error_log($e->getMessage());flash('error','Não foi possível promover o item.');redirect_to('importar');}
}
http_response_code(400);exit('Acao desconhecida');
