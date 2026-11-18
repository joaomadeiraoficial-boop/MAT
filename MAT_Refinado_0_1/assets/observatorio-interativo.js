/* MAT — Observatório: máquina do tempo opcional.
   Sem listeners, timers ou animação até o botão "Ativar modo interativo".
   Ao desligar, todos os listeners do recurso são removidos. */
(() => {
  const root = document.querySelector('[data-tm]');
  if (!root) return;

  const toggle = root.querySelector('[data-tm-toggle]');
  const stage = root.querySelector('[data-tm-stage]');
  const label = root.querySelector('[data-tm-toggle-label]');
  const status = root.querySelector('[data-tm-status]');
  const note = root.querySelector('[data-tm-note]');
  const range = root.querySelector('[data-tm-range]');
  const ticks = Array.from(root.querySelectorAll('[data-tm-tick]'));
  const scene = root.querySelector('.mat-tm-scene');
  const reduceMotion = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false;
  const out = {
    count: root.querySelector('[data-tm-count]'),
    name: root.querySelector('[data-tm-name]'),
    dates: root.querySelector('[data-tm-dates]'),
    total: root.querySelector('[data-tm-total]'),
    approved: root.querySelector('[data-tm-approved]'),
    link: root.querySelector('[data-tm-link]')
  };

  if (!toggle || !stage || !range || !ticks.length || !out.name || !out.link) return;

  const nf = new Intl.NumberFormat('pt-BR');
  const eras = ticks.map((tick) => ({
    era: tick.dataset.tmEra || '',
    name: tick.dataset.tmName || '',
    dates: tick.dataset.tmDates || '',
    total: Number(tick.dataset.tmTotal || 0),
    approved: Number(tick.dataset.tmApproved || 0),
    url: tick.dataset.tmUrl || ''
  }));

  let active = false;
  let tickHandlers = [];

  const render = (index) => {
    const era = eras[index];
    if (!era) return;

    range.value = String(index);
    if (era.era) root.dataset.era = era.era;
    resetParallax();
    out.count.textContent = String(index + 1).padStart(2, '0') + ' / ' + String(eras.length).padStart(2, '0');
    out.name.textContent = era.name;
    out.dates.textContent = era.dates;
    if (out.total) out.total.textContent = nf.format(era.total);
    if (out.approved) out.approved.textContent = nf.format(era.approved);
    out.link.href = era.url;

    ticks.forEach((tick, i) => {
      const selected = i === index;
      tick.classList.toggle('is-active', selected);
      tick.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  };

  const onInput = () => render(Number(range.value));

  const resetParallax = () => {
    if (!scene) return;
    scene.querySelectorAll('.mat-tm-scene-pane [data-depth]').forEach((layer) => {
      layer.style.removeProperty('transform');
    });
  };

  const onPointerMove = (event) => {
    if (!scene) return;
    const rect = root.getBoundingClientRect();
    if (!rect.width || !rect.height) return;
    const pane = scene.querySelector('.scene-' + (root.dataset.era || ''));
    if (!pane) return;
    const x = ((event.clientX - rect.left) / rect.width - 0.5) * 2;
    const y = ((event.clientY - rect.top) / rect.height - 0.5) * 2;
    pane.querySelectorAll('[data-depth]').forEach((layer) => {
      const depth = Number(layer.dataset.depth) || 0.5;
      layer.style.transform = 'translate3d(' + (x * 24 * depth).toFixed(2) + 'px, ' + (y * 14 * depth).toFixed(2) + 'px, 0)';
    });
  };

  const activate = () => {
    if (active) return;
    active = true;
    stage.hidden = false;
    root.classList.add('is-on');
    toggle.setAttribute('aria-pressed', 'true');
    if (label) label.textContent = 'Desligar modo interativo';
    if (status) status.textContent = 'ligada';
    if (note) note.textContent = 'Interativo ligado: a cena acompanha o mouse e o marcador atualiza ao arrastar. Desligue para voltar à arte estática e encerrar os listeners.';
    range.addEventListener('input', onInput);
    tickHandlers = ticks.map((tick, index) => {
      const handler = () => render(index);
      tick.addEventListener('click', handler);
      return [tick, handler];
    });
    if (!reduceMotion) {
      root.addEventListener('pointermove', onPointerMove);
      root.addEventListener('pointerleave', resetParallax);
    }
    render(Number(range.value));
  };

  const deactivate = () => {
    if (!active) return;
    active = false;
    stage.hidden = true;
    root.classList.remove('is-on');
    toggle.setAttribute('aria-pressed', 'false');
    if (label) label.textContent = 'Ativar modo interativo';
    if (status) status.textContent = 'desligada';
    if (note) note.textContent = 'Desligado: a cena fica estática, sem parallax nem listeners. A lista abaixo continua funcionando normalmente.';
    range.removeEventListener('input', onInput);
    tickHandlers.forEach(([tick, handler]) => tick.removeEventListener('click', handler));
    tickHandlers = [];
    root.removeEventListener('pointermove', onPointerMove);
    root.removeEventListener('pointerleave', resetParallax);
    resetParallax();
  };

  toggle.addEventListener('click', () => {
    if (active) deactivate();
    else activate();
  });
})();
