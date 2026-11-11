<?php
/** Variável da view pai: $registroDatacao. Não executa operação no banco. */
$dat=CuradoriaDatacao::intervalo($registroDatacao);
$modoDat=$dat?'intervalo':'pontual';
// Caso haja falha de validacao, recuperar o que foi digitado antes do POST.
if(isset($datacaoDraft) && is_array($datacaoDraft) && $datacaoDraft){
    if(in_array(($datacaoDraft['modo_datacao']??''),['pontual','intervalo'],true))$modoDat=$datacaoDraft['modo_datacao'];
    if(array_key_exists('ano_validado',$datacaoDraft))$registroDatacao['ano_validado']=$datacaoDraft['ano_validado'];
    if($modoDat==='intervalo'){
        $dat=['tipo'=>$datacaoDraft['intervalo_tipo']??($dat['tipo']??'incerteza'),
              'inicio'=>$datacaoDraft['intervalo_inicio']??($dat['inicio']??''),
              'fim'=>$datacaoDraft['intervalo_fim']??($dat['fim']??''),
              'fonte_id'=>$datacaoDraft['intervalo_fonte_id']??($dat['fonte_id']??''),
              'justificativa'=>$datacaoDraft['intervalo_justificativa']??($dat['justificativa']??'')];
    }
}
?>
<fieldset class="mat-date-editor" data-mat-date-editor>
 <legend>Data histórica do acontecimento</legend>
 <label>Como a fonte informa a data?
  <select name="modo_datacao" data-mat-date-mode>
   <option value="pontual" <?=$modoDat==='pontual'?'selected':''?>>Ano único (continuar como antes)</option>
   <option value="intervalo" <?=$modoDat==='intervalo'?'selected':''?>>Intervalo documentado (opcional)</option>
  </select>
 </label>
 <div data-mat-date-point <?= $modoDat==='intervalo'?'hidden':'' ?>>
  <label>Ano histórico revisado (negativo = a.C.)
   <input type="number" name="ano_validado" step="1" min="-5000000" max="2100" value="<?=h((string)($registroDatacao['ano_validado']??''))?>" placeholder="Ano conferido na fonte">
  </label>
  <small>Os demais marcos não precisam ser modificados. Ano extraído não é automaticamente validado.</small>
 </div>
 <div data-mat-date-range <?= $modoDat==='pontual'?'hidden':'' ?>>
  <label>Natureza do intervalo
   <select name="intervalo_tipo"><option value="incerteza" <?=($dat['tipo']??'')==='incerteza'?'selected':''?>>Incerteza sobre a data do marco</option><option value="processo" <?=($dat['tipo']??'')==='processo'?'selected':''?>>Processo tecnológico desenvolvido ao longo do período</option></select>
  </label>
  <div class="mat-date-years">
   <label>Ano inicial <input type="number" name="intervalo_inicio" min="-5000000" max="2100" step="1" value="<?=h((string)($dat['inicio']??''))?>" placeholder="Ex.: 750"></label>
   <label>Ano final <input type="number" name="intervalo_fim" min="-5000000" max="2100" step="1" value="<?=h((string)($dat['fim']??''))?>" placeholder="Ex.: 850"></label>
  </div>
  <label>Fonte cadastrada que sustenta o intervalo
   <select name="intervalo_fonte_id"><option value="">Selecione fonte conferida...</option><?php foreach(($registroDatacao['fontes']??[]) as $fon):?><option value="<?=h((string)$fon['fonte_id'])?>" <?=($dat['fonte_id']??'')===$fon['fonte_id']?'selected':''?>><?=h((string)$fon['fonte_id'])?> — <?=h((string)($fon['titulo']??''))?></option><?php endforeach;?></select>
  </label>
  <label>Justificativa arqueológica / documental do intervalo
   <textarea name="intervalo_justificativa" rows="3" maxlength="2000" placeholder="Indique por que a fonte sustenta esses limites e se o período é de desenvolvimento contínuo."><?=h((string)($dat['justificativa']??''))?></textarea>
  </label>
  <p><strong>Importante:</strong> o ponto médio servirá apenas à visualização e ao teste; não será salvo como um ano histórico exato. A análise de sensibilidade também testará o início e o fim.</p>
 </div>
</fieldset>
