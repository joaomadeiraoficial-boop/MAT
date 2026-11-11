(function () {
  'use strict';
  const rail = document.getElementById('rail');
  const navOpen = document.getElementById('navOpen');
  const navClose = document.getElementById('navClose');
  const main = document.querySelector('.main-column');
  const mobileBreakpoint = window.matchMedia('(max-width: 760px)');

  function toggleNav(opened) {
    if (!rail) return;
    rail.classList.toggle('open', opened);
    document.body.classList.toggle('nav-open', opened);
    if (navOpen) navOpen.setAttribute('aria-expanded', String(opened));
    if (main) main.inert = opened && mobileBreakpoint.matches;
    if (opened && navClose) navClose.focus();
    if (!opened && document.activeElement === navClose && navOpen) navOpen.focus();
  }
  if (navOpen) {
    navOpen.setAttribute('aria-controls', 'rail');
    navOpen.setAttribute('aria-expanded', 'false');
    navOpen.addEventListener('click', () => toggleNav(true));
  }
  if (navClose) navClose.addEventListener('click', () => toggleNav(false));
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && rail && rail.classList.contains('open')) toggleNav(false);
  });
  document.addEventListener('pointerdown', e => {
    if (rail && rail.classList.contains('open') && !rail.contains(e.target) && e.target !== navOpen) toggleNav(false);
  });
  mobileBreakpoint.addEventListener('change', () => { if (!mobileBreakpoint.matches) toggleNav(false); });

  // Apenas um grupo aberto por vez, mantendo o grupo da rota ativo no carregamento.
  const groups = Array.from(document.querySelectorAll('.mat-nav-group'));
  groups.forEach(group => group.addEventListener('toggle', () => {
    if (group.open) groups.filter(other => other !== group).forEach(other => { other.open = false; });
  }));
  document.querySelectorAll('.mat-nav .nav-item').forEach(el => el.addEventListener('click', () => {
    if (mobileBreakpoint.matches) toggleNav(false);
  }));

  // Navegação entre as abas do MAT: preservar a posição do conteúdo e do menu.
  // Aplica-se somente aos links internos do menu; abrir uma ficha/âncora funciona normalmente.
  const key = 'mat:nav:scroll:v1';
  let restore = null;
  try {
    const saved = sessionStorage.getItem(key);
    if (saved) {
      sessionStorage.removeItem(key); // utilização única, não altera recarregamentos comuns
      const parsed = JSON.parse(saved);
      if (parsed && parsed.path === location.pathname && Date.now() - parsed.at < 30000)
        restore = parsed;
    }
  } catch (_) { /* armazenamento privado pode estar bloqueado */ }
  if (restore) {
    const restorePosition = () => {
      const oldBehavior = document.documentElement.style.scrollBehavior;
      document.documentElement.style.scrollBehavior = 'auto';
      window.scrollTo({top: Math.max(0, Number(restore.y) || 0), behavior: 'instant'});
      if (rail) rail.scrollTop = Math.max(0, Number(restore.railY) || 0);
      document.documentElement.style.scrollBehavior = oldBehavior;
    };
    restorePosition();
    requestAnimationFrame(restorePosition);
    window.addEventListener('load', restorePosition, {once:true});
  }
  document.querySelectorAll('.mat-nav a.nav-item[href]').forEach(link => {
    link.addEventListener('click', e => {
      if (e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
      const dest = new URL(link.href, location.href);
      if (dest.origin !== location.origin || dest.pathname !== location.pathname) return;
      try { sessionStorage.setItem(key, JSON.stringify({path:dest.pathname,
         y:window.scrollY || document.documentElement.scrollTop || 0,
         railY:rail ? rail.scrollTop : 0, at:Date.now()})); } catch (_) {}
    });
  });

  const reveals = document.querySelectorAll('.record-card, .era-card, .method-entry, .archive-item');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const obs = new IntersectionObserver(entries => entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('in-view'); obs.unobserve(e.target); }
    }), { threshold: .04 });
    reveals.forEach(el => { el.classList.add('reveal-ready'); obs.observe(el); });
  }
})();

// Filtros locais de duplicatas: não recarregam nem movimentam a página.
(function () {
  'use strict';
  for (const root of document.querySelectorAll('[data-dup-filters]')) {
  const board=root.closest('.mat-dup-board');
  const cases=Array.from(board.querySelectorAll('.mat-dup-case'));
  const empty=board.querySelector('[data-dup-empty]');
  const applyFilter=(filter) => {
    let showing=0;
    for(const item of cases){
      const visible=filter==='all'||(filter==='alerts'?item.dataset.dupState==='pending'&&item.dataset.dupRisk==='alerta':item.dataset.dupState===filter);
      item.hidden=!visible;
      if(visible)showing++;
    }
    for(const btn of root.querySelectorAll('button[data-dup-filter]'))
      btn.setAttribute('aria-pressed',String(btn.dataset.dupFilter===filter));
    if(empty)empty.hidden=showing!==0;
  };
  root.addEventListener('click', e=>{
    const button=e.target.closest('button[data-dup-filter]');
    if(button)applyFilter(button.dataset.dupFilter);
  });
  applyFilter('pending');
  }

  // Retorno de um parecer POST: manter a mesma área da tela após o redirect.
  for(const form of document.querySelectorAll('.mat-dup-decision-form')){
    form.addEventListener('submit', () => {
      try { sessionStorage.setItem('mat:nav:scroll:v1', JSON.stringify({
        path:new URL('index.php',location.href).pathname,
        y:window.scrollY||0,railY:document.getElementById('rail')?.scrollTop||0,at:Date.now()
      })); } catch (_) {}
    });
  }
})();
