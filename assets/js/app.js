(() => {
  const root = document.documentElement;
  const saved = localStorage.getItem('mat-theme');
  if (saved) root.dataset.theme = saved;
  document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    const next = root.dataset.theme === 'light' ? 'dark' : 'light';
    root.dataset.theme = next; localStorage.setItem('mat-theme', next);
  });

  async function post(url, payload) {
    const res = await fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':window.MAT.csrf}, body:JSON.stringify(payload)});
    const data = await res.json().catch(() => ({ok:false,message:'Resposta inválida'}));
    if (!res.ok || !data.ok) throw new Error(data.message || 'Falha na operação');
    return data;
  }

  document.querySelectorAll('.quick-action').forEach(btn => btn.addEventListener('click', async () => {
    try { btn.disabled=true; await post('api/marco_action.php',{id:btn.dataset.id,action:btn.dataset.action}); location.reload(); }
    catch(e){ alert(e.message); btn.disabled=false; }
  }));
  document.querySelectorAll('.scope-action').forEach(btn => btn.addEventListener('click', async () => {
    const action = btn.dataset.action;
    const message = action === 'approve'
      ? 'Confirmar este marco como ELEGÍVEL para o escopo científico-tecnológico? Isso ainda não aprova a pontuação nem inclui automaticamente o marco na Têmporis.'
      : 'Confirmar este marco como FORA DO ESCOPO? O registro será preservado no MAT como contexto, mas não poderá compor a Têmporis enquanto esta decisão não for alterada.';
    if (!confirm(message)) return;
    try {
      btn.disabled = true;
      await post('api/scope_action.php', {id:btn.dataset.id, action});
      location.reload();
    } catch(e) {
      alert(e.message);
      btn.disabled = false;
    }
  }));
  document.querySelectorAll('.dup-action').forEach(btn => btn.addEventListener('click', async () => {
    const label = btn.textContent.trim();
    if(!confirm(`Confirmar: ${label}?`)) return;
    try { btn.disabled=true; await post('api/duplicate_action.php',{id:btn.dataset.id,action:btn.dataset.action}); location.reload(); }
    catch(e){ alert(e.message); btn.disabled=false; }
  }));

  document.querySelector('.reclassify-scope')?.addEventListener('click', async (ev) => {
    const btn = ev.currentTarget;
    try {
      btn.disabled = true;
      btn.textContent = 'Classificando...';
      const data = await post('api/reclassify_scope.php', {});
      alert(`Triagem concluída: ${data.count} registro(s) atualizado(s).`);
      location.reload();
    } catch(e) {
      alert(e.message);
      btn.disabled = false;
      btn.textContent = 'Recalcular sugestões automáticas';
    }
  });

  document.querySelector('.rebuild-duplicates')?.addEventListener('click', async (ev) => {
    const btn = ev.currentTarget;
    try {
      btn.disabled = true;
      btn.textContent = 'Analisando...';
      const data = await post('api/rebuild_duplicates.php', {});
      alert(`Análise concluída: ${data.count} par(es) pendente(s).`);
      location.reload();
    } catch(e) {
      alert(e.message);
      btn.disabled = false;
      btn.textContent = 'Reanalisar duplicatas';
    }
  });

  const fileInput = document.querySelector('.dropzone input[type=file]');
  if(fileInput) fileInput.addEventListener('change', () => {
    const label = fileInput.closest('.dropzone')?.querySelector('strong');
    if(label && fileInput.files[0]) label.textContent = fileInput.files[0].name;
  });
})();
