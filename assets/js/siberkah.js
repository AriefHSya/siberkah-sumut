/* SIBERKAH SUMUT v4 — Main JS */

// ── Rupiah formatter ─────────────────────────────────────────
document.querySelectorAll('.rupiah-input').forEach(el => {
  el.addEventListener('input', function() {
    const v = this.value.replace(/\D/g, '');
    this.value = v ? parseInt(v).toLocaleString('id-ID') : '';
  });
  // Init jika sudah ada nilai
  if (el.value) {
    const v = el.value.replace(/\D/g, '');
    el.value = v ? parseInt(v).toLocaleString('id-ID') : '';
  }
});

// ── Konfirmasi hapus ──────────────────────────────────────────
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', function(e) {
    if (!confirm(this.dataset.confirm || 'Yakin?')) e.preventDefault();
  });
});

// ── Flash message auto-close ──────────────────────────────────
setTimeout(() => {
  document.querySelectorAll('.alert.auto-close').forEach(el => {
    el.style.transition = 'opacity 0.4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 400);
  });
}, 4000);

// ── Sidebar active ────────────────────────────────────────────
(function() {
  const path = window.location.pathname;
  document.querySelectorAll('.nav-item[href]').forEach(el => {
    if (path.startsWith(el.getAttribute('href'))) el.classList.add('on');
  });
})();

// ── Select all / deselect permissions ────────────────────────
function togglePermGroup(modul, checked) {
  document.querySelectorAll('.perm-check[data-modul="' + modul + '"]').forEach(cb => {
    cb.checked = checked;
  });
}

// ── Modal helper ──────────────────────────────────────────────
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
});

// ── Sidebar mobile toggle ─────────────────────────────────────
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar = document.querySelector('.sidebar');
if (sidebarToggle && sidebar) {
  sidebarToggle.addEventListener('click', () => sidebar.classList.toggle('open'));
}
