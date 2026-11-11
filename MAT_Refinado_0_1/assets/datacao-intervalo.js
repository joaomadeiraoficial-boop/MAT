document.addEventListener('change',function(event){
  const editor=event.target.closest('[data-mat-date-editor]');
  if(!editor || event.target.name!=='modo_datacao')return;
  const range=editor.querySelector('[data-mat-date-range]');
  const point=editor.querySelector('[data-mat-date-point]');
  if(range)range.hidden=event.target.value!=='intervalo';
  if(point)point.hidden=event.target.value==='intervalo';
});
