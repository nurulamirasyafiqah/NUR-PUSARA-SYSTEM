/* ========================================
   NUR-PUSARA SYSTEM — Main JavaScript
======================================== */

// ── Theme Toggle ──
const themeToggle = document.querySelectorAll('.theme-toggle, .theme-toggle-login');
const html = document.documentElement;

const savedTheme = localStorage.getItem('nur-pusara-theme') || 'light';
html.setAttribute('data-theme', savedTheme);
updateThemeIcons(savedTheme);

themeToggle.forEach(btn => {
  btn.addEventListener('click', () => {
    const current = html.getAttribute('data-theme');
    const next = current === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', next);
    localStorage.setItem('nur-pusara-theme', next);
    updateThemeIcons(next);
  });
});

function updateThemeIcons(theme) {
  themeToggle.forEach(btn => {
    btn.innerHTML = theme === 'light'
      ? `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>`
      : `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>`;
  });
}

// ── Sidebar Toggle (Desktop collapse + Mobile open/close) ──
const hamburger = document.querySelector('.hamburger');
const sidebar = document.querySelector('.sidebar');
const overlay = document.querySelector('.sidebar-overlay');
const mainContent = document.querySelector('.main-content');

const isMobile = () => window.innerWidth <= 768;

// Restore sidebar state on desktop
const savedSidebarState = localStorage.getItem('nur-pusara-sidebar');
if (!isMobile() && savedSidebarState === 'collapsed') {
  sidebar?.classList.add('collapsed');
}

if (hamburger) {
  hamburger.addEventListener('click', () => {
    if (isMobile()) {
      // Mobile: slide in/out
      sidebar.classList.toggle('open');
      overlay.classList.toggle('active');
      hamburger.classList.toggle('open'); // toggle X icon
    } else {
      // Desktop: collapse/expand
      sidebar.classList.toggle('collapsed');
      const state = sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded';
      localStorage.setItem('nur-pusara-sidebar', state);
      // Update main content margin
      if (mainContent) {
        mainContent.style.marginLeft = sidebar.classList.contains('collapsed') ? '64px' : '260px';
      }
    }
  });
}

if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    hamburger?.classList.remove('open');
  });
}

// Handle resize
window.addEventListener('resize', () => {
  if (!isMobile()) {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('active');
    if (mainContent) {
      mainContent.style.marginLeft = sidebar?.classList.contains('collapsed') ? '64px' : '260px';
    }
  } else {
    if (mainContent) mainContent.style.marginLeft = '0';
  }
});

// ── Cemetery Lot Map ──
const lotCells = document.querySelectorAll('.lot-cell');
const lotPanel = document.querySelector('.lot-panel');
const lotPanelClose = document.querySelector('.lot-panel-close');

const statusMap = {
  tersedia:        '<span class="badge badge-tersedia">Tersedia</span>',
  diduduki:        '<span class="badge badge-diduduki">Diduduki</span>',
  penyelenggaraan: '<span class="badge badge-penyelenggaraan">Penyelenggaraan</span>',
};

lotCells.forEach(cell => {
  cell.addEventListener('click', () => {
    // Read all info from data attributes — no hardcoded data needed
    const lotId    = cell.dataset.lot      || '—';
    const status   = cell.dataset.status   || 'diduduki';
    const deceased = cell.dataset.deceased || '';
    const date     = cell.dataset.date     || '';
    const blok    = cell.dataset.section  || '';
    const lotDbId  = cell.dataset.lotId    || '';
    const burialId = cell.dataset.burialId || '';
    const lat      = cell.dataset.lat      || '';
    const lng      = cell.dataset.lng      || '';
    const cemetery = cell.dataset.cemetery || '';

    lotCells.forEach(c => c.classList.remove('selected'));
    cell.classList.add('selected');

    if (!lotPanel) return;

    // Update panel header
    const panelId = document.querySelector('.lot-panel-id');
    if (panelId) panelId.textContent = lotId;

    const panelStatus = document.querySelector('.lot-panel-status');
    if (panelStatus) panelStatus.innerHTML = statusMap[status] || '';

    const panelDeceased = document.querySelector('.lot-panel-deceased');
    if (panelDeceased) panelDeceased.textContent = deceased || '—';

    const panelDate = document.querySelector('.lot-panel-date');
    if (panelDate) panelDate.textContent = date || '—';

    const panelSection = document.querySelector('.lot-panel-section');
    if (panelSection) panelSection.textContent = (blok ? 'Blok ' + blok : '—') + (cemetery ? ' · ' + cemetery : '');

    // Google Maps link if coordinates available
    const existingMap = document.querySelector('.lot-panel-map');
    if (existingMap) existingMap.remove();

    if (lat && lng) {
      const mapsUrl  = `https://www.google.com/maps?q=${lat},${lng}&z=21&hl=ms`;
      const mapDiv   = document.createElement('div');
      mapDiv.className = 'lot-panel-map';
      mapDiv.style.cssText = 'margin-top:0.8rem;padding-top:0.8rem;border-top:1px solid var(--border-color);';
      mapDiv.innerHTML = `
        <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:0.4rem;">📍 Koordinat GPS</div>
        <div style="font-size:0.75rem;color:var(--text-primary);font-family:monospace;margin-bottom:0.5rem;">${parseFloat(lat).toFixed(6)}, ${parseFloat(lng).toFixed(6)}</div>
        <a href="${mapsUrl}" target="_blank" style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.75rem;font-weight:600;color:var(--clr-1);text-decoration:none;">
          🗺️ Buka Google Maps
        </a>`;
      const panelBody = document.querySelector('.lot-panel-body') || lotPanel;
      panelBody.appendChild(mapDiv);
    }

    // Action buttons
    const BASE = document.documentElement.dataset.baseurl || '';
    let actions = '';
    if (status === 'diduduki') {
      actions += `<a href="${BASE}/modules/burial/add.php" class="btn-save" style="text-align:center;padding:0.6rem;">+ Daftar Pengebumian</a>`;
    }
    if (burialId) {
      actions += `<a href="${BASE}/modules/burial/view.php?id=${burialId}" class="btn-outline" style="text-align:center;">Lihat Rekod Pengebumian</a>`;
    }
    if (lotDbId) {
      actions += `<a href="${BASE}/modules/lot/edit.php?id=${lotDbId}" class="btn-outline" style="text-align:center;">Kemaskini Lot</a>`;
    }
    const panelActions = document.querySelector('#panelActions');
    if (panelActions) panelActions.innerHTML = actions;

    lotPanel.classList.add('open');
  });
});

if (lotPanelClose) {
  lotPanelClose.addEventListener('click', () => {
    lotPanel.classList.remove('open');
    lotCells.forEach(c => c.classList.remove('selected'));
    const existingMap = document.querySelector('.lot-panel-map');
    if (existingMap) existingMap.remove();
  });
}

// ── Search Table ──
const searchInputs = document.querySelectorAll('.search-box input');
searchInputs.forEach(input => {
  input.addEventListener('input', (e) => {
    const query = e.target.value.toLowerCase();
    const table = e.target.closest('.card').querySelector('tbody');
    if (!table) return;

    table.querySelectorAll('tr').forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(query) ? '' : 'none';
    });
  });
});

// ── Alert / Toast ──
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.style.cssText = `
    position: fixed; bottom: 1.5rem; right: 1.5rem;
    background: ${type === 'success' ? 'var(--clr-6)' : '#dc2626'};
    color: white; padding: 0.8rem 1.2rem;
    border-radius: var(--radius-sm); font-size: 0.85rem;
    font-family: var(--font-body); font-weight: 500;
    box-shadow: var(--shadow-lg); z-index: 9999;
    animation: slideIn 0.3s ease;
  `;
  toast.textContent = message;
  document.body.appendChild(toast);
  setTimeout(() => toast.remove(), 3000);
}

// ── Confirm Delete ──
document.querySelectorAll('.btn-delete').forEach(btn => {
  btn.addEventListener('click', (e) => {
    if (!confirm('Adakah anda pasti untuk memadam rekod ini?')) {
      e.preventDefault();
    }
  });
});

// ── Active Nav Item ──
const currentPath = window.location.pathname;
document.querySelectorAll('.nav-item').forEach(item => {
  if (item.getAttribute('href') && currentPath.includes(item.getAttribute('href'))) {
    item.classList.add('active');
  }
});
