/* ============================================================
   NUR-PUSARA — Landing Page & Public Search JavaScript
   Applies to : index.php, modules/search/index.php
   Note       : Admin panel scripts are in main.js
============================================================ */

/* ── SVG icons for the theme toggle button ── */
var SVG_MOON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
var SVG_SUN  = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';

/* ── Apply theme: set data-theme attribute and update icon ── */
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('nur-pusara-theme', theme);
  var btn = document.getElementById('themeBtn');
  if (btn) btn.innerHTML = (theme === 'light') ? SVG_MOON : SVG_SUN;
}

/* ── Initialise theme immediately (before DOM ready to avoid flash) ── */
applyTheme(localStorage.getItem('nur-pusara-theme') || 'light');

/* ── Wire up toggle button after DOM is ready ── */
document.addEventListener('DOMContentLoaded', function () {
  var btn = document.getElementById('themeBtn');
  if (btn) {
    btn.addEventListener('click', function () {
      var current = document.documentElement.getAttribute('data-theme');
      applyTheme(current === 'dark' ? 'light' : 'dark');
    });
  }
});

/* ── Landing page: reveal login panel, hide choice cards ── */
function showLogin() {
  var choiceView = document.getElementById('choiceView');
  var loginPanel = document.getElementById('loginPanel');
  if (choiceView) choiceView.style.display = 'none';
  if (loginPanel) loginPanel.classList.add('show');
}

/* ── Landing page: go back to choice cards from login panel ── */
function hideLogin() {
  var choiceView = document.getElementById('choiceView');
  var loginPanel = document.getElementById('loginPanel');
  if (loginPanel) loginPanel.classList.remove('show');
  if (choiceView) choiceView.style.display = 'blok';
}
