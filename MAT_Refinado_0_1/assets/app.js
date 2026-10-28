(function () {
  const root = document.getElementById('rail');
  const open = document.getElementById('navOpen');
  const close = document.getElementById('navClose');
  function toggleNav(opened) { if (root) root.classList.toggle('open', opened); document.body.classList.toggle('nav-open', opened); }
  if (open) open.addEventListener('click', () => toggleNav(true));
  if (close) close.addEventListener('click', () => toggleNav(false));
  document.addEventListener('keydown', e => { if (e.key === 'Escape') toggleNav(false); });
  document.querySelectorAll('.nav-item').forEach(el => el.addEventListener('click', () => toggleNav(false)));
  const reveals = document.querySelectorAll('.record-card, .era-card, .method-entry, .archive-item');
  if ('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const obs = new IntersectionObserver(entries => entries.forEach(e => {if(e.isIntersecting) { e.target.classList.add('in-view'); obs.unobserve(e.target); }}), {threshold:.04});
    reveals.forEach(el=>{el.classList.add('reveal-ready');obs.observe(el);});
  }
})();
