<?php
/**
 * Komponen Pagination
 * Dipakai via: $this->load->view('partials/pagination', ['paging' => $paging, 'filters' => $filters])
 *
 * $paging: ['total', 'per_page', 'page', 'base_url']
 * $filters: array GET params yang harus dipertahankan di URL (opsional)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

$total    = (int)($paging['total']    ?? 0);
$per_page = (int)($paging['per_page'] ?? 20);
$page     = (int)($paging['page']     ?? 1);
$base_url = $paging['base_url'] ?? '';

if ($total <= $per_page) return; // tidak perlu pagination jika cukup 1 halaman

$total_pages = (int)ceil($total / $per_page);
$page        = max(1, min($page, $total_pages));

// Bangun query string dari filters (tanpa 'page')
$params = [];
if (!empty($filters)) {
    foreach ($filters as $k => $v) {
        if ($v !== NULL && $v !== '' && $k !== 'tahun') {
            $params[$k] = $v;
        }
    }
}

function _paging_url($base, $p, $params) {
    $q = $params;
    if ($p > 1) $q['page'] = $p;
    $qs = http_build_query($q);
    return site_url($base) . ($qs ? '?' . $qs : '');
}

$from = ($page - 1) * $per_page + 1;
$to   = min($page * $per_page, $total);
?>

<div class="pagination-wrap">
  <div class="paging-info">
    Menampilkan <strong><?= $from ?>–<?= $to ?></strong> dari <strong><?= number_format($total) ?></strong> data
  </div>

  <?php if ($total_pages > 1): ?>
  <nav class="paging-nav" aria-label="Navigasi halaman">

    <?php // Tombol Prev
    if ($page > 1): ?>
      <a href="<?= _paging_url($base_url, $page - 1, $params) ?>" class="paging-btn" title="Halaman sebelumnya">
        <i class="ti ti-chevron-left"></i>
      </a>
    <?php else: ?>
      <span class="paging-btn paging-disabled"><i class="ti ti-chevron-left"></i></span>
    <?php endif; ?>

    <?php
    // Tentukan range halaman yang ditampilkan
    $window = 2; // tampilkan ±2 dari halaman aktif
    $start  = max(1, $page - $window);
    $end    = min($total_pages, $page + $window);

    // Selalu tampilkan halaman 1
    if ($start > 1): ?>
      <a href="<?= _paging_url($base_url, 1, $params) ?>" class="paging-btn">1</a>
      <?php if ($start > 2): ?><span class="paging-ellipsis">…</span><?php endif; ?>
    <?php endif; ?>

    <?php for ($i = $start; $i <= $end; $i++): ?>
      <?php if ($i === $page): ?>
        <span class="paging-btn paging-active"><?= $i ?></span>
      <?php else: ?>
        <a href="<?= _paging_url($base_url, $i, $params) ?>" class="paging-btn"><?= $i ?></a>
      <?php endif; ?>
    <?php endfor; ?>

    <?php // Selalu tampilkan halaman terakhir
    if ($end < $total_pages): ?>
      <?php if ($end < $total_pages - 1): ?><span class="paging-ellipsis">…</span><?php endif; ?>
      <a href="<?= _paging_url($base_url, $total_pages, $params) ?>" class="paging-btn"><?= $total_pages ?></a>
    <?php endif; ?>

    <?php // Tombol Next
    if ($page < $total_pages): ?>
      <a href="<?= _paging_url($base_url, $page + 1, $params) ?>" class="paging-btn" title="Halaman berikutnya">
        <i class="ti ti-chevron-right"></i>
      </a>
    <?php else: ?>
      <span class="paging-btn paging-disabled"><i class="ti ti-chevron-right"></i></span>
    <?php endif; ?>

  </nav>
  <?php endif; ?>
</div>

<style>
.pagination-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0 4px;
    flex-wrap: wrap;
    gap: 8px;
}
.paging-info {
    font-size: 13px;
    color: var(--abu);
}
.paging-nav {
    display: flex;
    align-items: center;
    gap: 4px;
}
.paging-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 32px;
    height: 32px;
    padding: 0 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    color: var(--biru);
    background: #fff;
    text-decoration: none;
    transition: background .12s, border-color .12s;
}
.paging-btn:hover { background: var(--biru-light); border-color: var(--biru); }
.paging-active    { background: var(--biru) !important; color: #fff !important; border-color: var(--biru) !important; font-weight: 600; }
.paging-disabled  { color: #bbb; cursor: default; pointer-events: none; border-color: #eee; }
.paging-ellipsis  { font-size: 13px; color: var(--abu); padding: 0 4px; }
</style>
