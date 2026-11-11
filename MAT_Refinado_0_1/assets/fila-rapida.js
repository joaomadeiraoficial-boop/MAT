(function () {
  'use strict';
  // Somente assistência na interface. O servidor valida os 11 critérios, CSRF,
  // identidade, duplicatas, fontes e confirmação humana em cada decisão.
  for (const form of document.querySelectorAll('[data-fast-form]')) {
    form.addEventListener('submit', function (event) {
      const choice = event.submitter ? event.submitter.value : '';
      const checked = form.querySelector('[data-fast-checked]');
      const error = form.querySelector('[data-fast-error]');
      if ((choice === 'aprovar' || choice === 'homologar') && checked && !checked.checked) {
        event.preventDefault();
        if (error) {
          error.hidden = false;
          error.textContent = 'Marque a confirmação somente depois de conferir individualmente as 11 rubricas, fontes e datas.';
        }
        checked.focus();
      }
    });
  }
  const list = document.querySelector('.mat-fast-items');
  const active = list && list.querySelector('.mat-fast-item.active');
  if (list && active) {
    const top = active.offsetTop - list.offsetTop;
    if (top > list.clientHeight || top + active.offsetHeight > list.clientHeight)
      list.scrollTop = Math.max(0, top - list.clientHeight / 3);
  }
  const prev = document.querySelector('[data-fast-prev]');
  const next = document.querySelector('[data-fast-next]');
  document.addEventListener('keydown', function (event) {
    if (!event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) return;
    if (event.target && event.target.closest('input, textarea, select, [contenteditable="true"]')) return;
    const k = event.key.toLowerCase();
    const url = k === 'j' ? next : (k === 'k' ? prev : null);
    if (url && url.getAttribute('href')) {
      event.preventDefault();
      location.assign(url.href);
    }
  });
})();
// Recupera o ponto de revisão depois de salvar um ajuste no banco.
(function () {
  'use strict';
  const foco = new URLSearchParams(window.location.search).get('foco');
  if (!foco || !/^(mat-fast-edit-meta|mat-fast-add-source|mat-fast-criteria|mat-fast-criterion-[a-z_]+|decisao-rapida)$/.test(foco)) return;
  const target = document.getElementById(foco);
  if (!target) return;
  if (target.tagName === 'DETAILS') target.open = true;
  const childDetails = target.closest('.mat-fast-inline-edit');
  if (childDetails) childDetails.open = true;
  if (target.classList.contains('mat-fast-criterion')) {
    const details = target.querySelector('.mat-fast-inline-edit');
    if (details) details.open = true;
  }
  const feedback = document.getElementById('mat-fast-save-feedback');
  if(feedback) feedback.scrollIntoView({ block: 'center', behavior: 'instant' });
  else target.scrollIntoView({ block: 'start', behavior: 'instant' });
})();
// Mostra previamente a exigência de 60 caracteres ao declarar pendências resolvidas.
(function () {
  'use strict';
  for (const form of document.querySelectorAll('form[action="actions.php"]')) {
    const checks = form.querySelectorAll('input[name="pendencias_resolvidas[]"],input[name="duplicatas_resolvidas[]"],input[name="resolver_pendencias"],input[name="resolver_duplicatas"]');
    const reason = form.querySelector('textarea[name="motivo"]');
    if (!reason || !checks.length) continue;
    const update = () => {
      const resolving = [...checks].some(check => check.checked);
      reason.minLength = resolving ? 60 : 15;
      reason.title = resolving ? 'Para resolver itens, explique as fontes e a verificação em pelo menos 60 caracteres.' : '';
    };
    checks.forEach(check => check.addEventListener('change', update));
    update();
  }
  // Abrir a correção específica ao usar um link de impedimento.
  const expandTarget = () => {
    const id = location.hash.substring(1);
    if (!/^mat-fast-criterion-[a-z_]+$/.test(id)) return;
    const target = document.getElementById(id);
    if (!target) return;
    const editor = target.querySelector('.mat-fast-inline-edit');
    if (editor) editor.open = true;
  };
  window.addEventListener('hashchange',expandTarget);
  expandTarget();
})();
// Evita enviar duas vezes o mesmo parecer e gerar rejeicoes por hash desatualizado.
(function () {
  'use strict';
  for (const form of document.querySelectorAll('form[action="actions.php"]')) {
    if (!form.querySelector('input[name="action"][value="curadoria_metadados"]')) continue;
    let sending = false;
    form.addEventListener('submit', function (event) {
      if (sending) { event.preventDefault(); return; }
      sending = true;
      const button = form.querySelector('button[type="submit"]');
      if (button) { button.textContent = 'Gravando e conferindo no banco...'; button.setAttribute('aria-disabled', 'true'); }
    });
  }
})();
