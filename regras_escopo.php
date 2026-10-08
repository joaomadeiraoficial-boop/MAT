<?php
require __DIR__ . '/includes/bootstrap.php';
$pageTitle='Dicionário de triagem'; $active='regras_escopo';

$allowedNatures=['tecnologico','descoberta_cientifica','metodo_infraestrutura','social_politico','biologico_evolutivo','cosmologico'];
$allowedScopes=['any','name','category','description'];
$allowedLanguages=['any','en','pt'];

if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=(string)($_POST['action']??'add');
    try{
        if($action==='add'){
            $pattern=trim((string)($_POST['pattern']??''));
            $nature=(string)($_POST['natureza']??'tecnologico');
            $weight=max(1,min(10,(int)($_POST['weight']??3)));
            $scope=(string)($_POST['field_scope']??'any');
            $language=(string)($_POST['language']??'any');
            $notes=trim((string)($_POST['notes']??''));
            if($pattern==='') throw new RuntimeException('Digite uma palavra ou expressão.');
            if(!in_array($nature,$allowedNatures,true)) throw new RuntimeException('Natureza inválida.');
            if(!in_array($scope,$allowedScopes,true)) $scope='any';
            if(!in_array($language,$allowedLanguages,true)) $language='any';
            $stmt=$pdo->prepare('INSERT INTO scope_keyword_rules (pattern,natureza,weight,field_scope,language,notes,enabled) VALUES (?,?,?,?,?,?,1) ON DUPLICATE KEY UPDATE weight=VALUES(weight),language=VALUES(language),notes=VALUES(notes),enabled=1');
            $stmt->execute([$pattern,$nature,$weight,$scope,$language,$notes?:null]);
            Audit::log($pdo,'scope_rule',(int)$pdo->lastInsertId(),'add_or_update',null,['pattern'=>$pattern,'natureza'=>$nature,'weight'=>$weight,'field_scope'=>$scope]);
            flash('success','Regra salva. Use “Recalcular sugestões automáticas” no Escopo científico para aplicar aos registros pendentes.');
        } elseif($action==='toggle'){
            $id=(int)($_POST['id']??0);
            $pdo->prepare('UPDATE scope_keyword_rules SET enabled=IF(enabled=1,0,1) WHERE id=?')->execute([$id]);
            flash('success','Regra atualizada.');
        } elseif($action==='delete'){
            $id=(int)($_POST['id']??0);
            $pdo->prepare('DELETE FROM scope_keyword_rules WHERE id=?')->execute([$id]);
            flash('success','Regra removida.');
        }
        redirect('regras_escopo.php');
    }catch(Throwable $e){ flash('error',$e->getMessage()); redirect('regras_escopo.php'); }
}

$rules=$pdo->query('SELECT * FROM scope_keyword_rules ORDER BY enabled DESC,weight DESC,id DESC')->fetchAll();
$builtin=ScopeClassifier::builtinRuleCount();
require __DIR__ . '/includes/header.php';
$scopeLabels=['any'=>'Qualquer campo','name'=>'Nome do marco','category'=>'Categoria','description'=>'Descrição'];
?>
<div class="grid cols-2 gap-lg">
  <div class="panel">
    <div class="panel-head"><div><h2>Adicionar palavra-chave</h2><p>Crie regras transparentes sem alterar o código do MAT.</p></div><span class="badge badge-info"><?= (int)$builtin ?> regras internas</span></div>
    <form method="post" class="stack"><?=csrf_field()?><input type="hidden" name="action" value="add">
      <div class="field"><label>Palavra ou expressão</label><input name="pattern" placeholder="ex.: optical computing" required></div>
      <div class="grid cols-2 gap-lg">
        <div class="field"><label>Classificação</label><select name="natureza"><?php foreach($allowedNatures as $n):?><option value="<?=e($n)?>"><?=e(scope_nature_label($n))?></option><?php endforeach;?></select></div>
        <div class="field"><label>Peso (1–10)</label><input type="number" name="weight" min="1" max="10" value="4"></div>
      </div>
      <div class="grid cols-2 gap-lg">
        <div class="field"><label>Onde procurar</label><select name="field_scope"><option value="any">Qualquer campo</option><option value="name">Nome do marco</option><option value="category">Categoria</option><option value="description">Descrição</option></select></div>
        <div class="field"><label>Idioma</label><select name="language"><option value="any">Qualquer</option><option value="en">Inglês</option><option value="pt">Português</option></select></div>
      </div>
      <div class="field"><label>Observação</label><input name="notes" placeholder="Opcional: origem ou motivo da regra"></div>
      <button class="btn primary" type="submit">Adicionar regra</button>
    </form>
  </div>
  <div class="panel">
    <div class="panel-head"><div><h2>Como a triagem v2 decide</h2><p>Mais rápida, mas ainda auditável para o TCC.</p></div></div>
    <div class="schema-list">
      <span><b>Categoria:</b> recebe peso alto. “Industrial”, “Medical”, “Communication”, “Transportation” etc. já indicam forte caráter tecnológico.</span>
      <span><b>Nome:</b> vale mais que a descrição. Termos como “integrated circuit”, “vaccine”, “engine” e “algorithm” têm maior influência.</span>
      <span><b>Descrição:</b> ajuda a desempatar e reconhecer sinônimos.</span>
      <span><b>Conflitos:</b> se houver sinais fortes de tecnologia e de evento social/natural ao mesmo tempo, o MAT manda para revisão.</span>
      <span><b>Confiança:</b> alta, média ou baixa conforme a margem entre sinais favoráveis e sinais fora do escopo.</span>
    </div>
    <div class="note warning"><b>Importante:</b> o dicionário altera apenas a sugestão automática. Ele nunca aprova um marco definitivamente.</div>
    <a class="btn primary full" href="escopo.php">Ir para Escopo científico</a>
  </div>
</div>

<div class="panel mt-lg">
  <div class="panel-head"><div><h2>Regras personalizadas</h2><p>Use principalmente para termos recorrentes das bases que você importar.</p></div><span class="badge badge-muted"><?=count($rules)?> personalizada(s)</span></div>
  <div class="table-wrap"><table><thead><tr><th>Expressão</th><th>Natureza</th><th>Peso</th><th>Campo</th><th>Idioma</th><th>Status</th><th></th></tr></thead><tbody>
  <?php if(!$rules):?><tr><td colspan="7" class="empty">Nenhuma regra personalizada. O classificador já possui um dicionário interno bilíngue.</td></tr><?php endif;?>
  <?php foreach($rules as $r):?><tr>
    <td><strong><?=e($r['pattern'])?></strong><?php if($r['notes']):?><small class="subline"><?=e($r['notes'])?></small><?php endif;?></td>
    <td><?=e(scope_nature_label($r['natureza']))?></td><td><?= (int)$r['weight'] ?></td><td><?=e($scopeLabels[$r['field_scope']]??$r['field_scope'])?></td><td><?=e(strtoupper($r['language']))?></td>
    <td><span class="badge <?=$r['enabled']?'badge-success':'badge-muted'?>"><?=$r['enabled']?'Ativa':'Pausada'?></span></td>
    <td><div class="scope-actions"><form method="post"><?=csrf_field()?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn ghost" type="submit"><?=$r['enabled']?'Pausar':'Ativar'?></button></form><form method="post" onsubmit="return confirm('Remover esta regra?');"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn danger ghost" type="submit">Excluir</button></form></div></td>
  </tr><?php endforeach;?>
  </tbody></table></div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
