/* ═══════════════════════════════════════════════════════
   SGI3D – Gestion du thème (sombre / clair / auto)
   Applique immédiatement data-theme sur <html> pour éviter
   le flash, puis injecte le bouton de bascule dans la nav.
   ═══════════════════════════════════════════════════════ */

(function () {
  var MODES  = ['auto', 'light', 'dark'];
  var ICONS  = { auto: '🔄', light: '☀️', dark: '🌙' };
  var LABELS = { auto: 'Auto', light: 'Clair', dark: 'Sombre' };
  var KEY    = 'sgi3d-theme';
  var mq     = window.matchMedia('(prefers-color-scheme: dark)');

  function getMode() {
    return localStorage.getItem(KEY) || 'auto';
  }

  function effectiveTheme(mode) {
    if (mode === 'auto') return mq.matches ? 'dark' : 'light';
    return mode;
  }

  function applyMode(mode) {
    document.documentElement.setAttribute('data-theme', effectiveTheme(mode));
    var btn = document.getElementById('theme-toggle');
    if (btn) {
      btn.innerHTML = ICONS[mode] + ' <span>' + LABELS[mode] + '</span>';
      btn.title = 'Thème : ' + LABELS[mode];
    }
  }

  function cycleMode() {
    var next = MODES[(MODES.indexOf(getMode()) + 1) % MODES.length];
    localStorage.setItem(KEY, next);
    applyMode(next);
  }

  /* ── Application immédiate (avant le rendu du body) ── */
  applyMode(getMode());

  /* ── Injection du bouton dès que le DOM est prêt ── */
  document.addEventListener('DOMContentLoaded', function () {
    var navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;

    var btn = document.createElement('button');
    btn.id        = 'theme-toggle';
    btn.className = 'theme-toggle';
    var mode = getMode();
    btn.innerHTML = ICONS[mode] + ' <span>' + LABELS[mode] + '</span>';
    btn.title     = 'Changer le thème';
    btn.onclick   = cycleMode;

    /* Insère avant le dernier élément (bouton Connexion / nom utilisateur) */
    var last = navLinks.lastElementChild;
    navLinks.insertBefore(btn, last);

    /* Met à jour si le thème système change (mode Auto actif) */
    mq.addEventListener('change', function () {
      if (getMode() === 'auto') applyMode('auto');
    });
  });
})();
