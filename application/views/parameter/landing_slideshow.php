<?php defined('BASEPATH') OR exit('No direct script access allowed');
$can_manage = $this->rbac->can('parameter.landing.manage');
?>

<div class="page-header">
  <div class="page-title"><i class="ti ti-slideshow"></i> Tampilan Landing — Slideshow Kinerja</div>
</div>

<!-- Sub-tab -->
<div class="sub-nav" style="margin-bottom:16px">
  <a href="<?= site_url('parameter/landing') ?>" class="sub-nav-item">
    <i class="ti ti-user-circle"></i> Foto Pejabat
  </a>
  <a href="<?= site_url('parameter/landing/slideshow') ?>" class="sub-nav-item on">
    <i class="ti ti-slideshow"></i> Slideshow Kinerja
  </a>
</div>

<?php if ($can_manage): ?>
<!-- Form Upload -->
<div class="card" style="margin-bottom:16px">
  <div class="card-title"><i class="ti ti-upload"></i> Upload Foto Baru</div>
  <?= form_open_multipart(site_url('parameter/landing/slideshow/tambah')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
  <div class="form-grid-2">
    <div class="form-group">
      <label class="form-label">Judul Foto</label>
      <input type="text" name="judul" class="form-control fc"
             placeholder="Contoh: Rapat Koordinasi BKP 2026">
    </div>
    <div class="form-group">
      <label class="form-label">File Foto <span class="text-danger">*</span></label>
      <input type="file" name="foto" accept="image/jpeg,image/png" required
             class="form-control" style="padding:6px">
      <small class="text-muted">JPG/PNG, maks 5 MB. Rasio landscape (16:9) direkomendasikan.</small>
    </div>
    <div class="form-group" style="grid-column:1/-1">
      <label class="form-label">Caption / Keterangan</label>
      <input type="text" name="caption" class="form-control fc"
             placeholder="Keterangan singkat yang tampil di slideshow...">
    </div>
  </div>
  <button type="submit" class="btn btn-primary btn-sm">
    <i class="ti ti-plus"></i> Tambah ke Slideshow
  </button>
  <?= form_close() ?>
</div>
<?php endif; ?>

<!-- Gallery List -->
<div class="card">
  <div class="card-title">
    <i class="ti ti-layout-grid"></i> Daftar Foto Slideshow
    <span class="text-muted" style="font-weight:400;font-size:12px;margin-left:6px"><?= count($list) ?> foto</span>
    <?php if ($can_manage && count($list) > 1): ?>
    <span class="text-muted" style="font-weight:400;font-size:12px;margin-left:auto">
      <i class="ti ti-drag-drop" style="font-size:14px"></i> Seret untuk mengubah urutan
    </span>
    <?php endif; ?>
  </div>

  <?php if (empty($list)): ?>
  <div style="text-align:center;padding:40px;color:var(--abu)">
    <i class="ti ti-photo-off" style="font-size:36px;display:block;margin-bottom:8px"></i>
    Belum ada foto slideshow. Upload foto pertama di atas.
  </div>
  <?php else: ?>

  <div id="gallery-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
    <?php foreach ($list as $item): ?>
    <div class="slide-item" data-id="<?= $item->id ?>"
         style="border:1px solid var(--border);border-radius:8px;overflow:hidden;
                background:#fff;<?= $can_manage ? 'cursor:grab' : '' ?>">

      <!-- Thumbnail -->
      <div style="height:130px;overflow:hidden;background:var(--bg)">
        <img src="<?= base_url($item->foto_path) ?>" alt="<?= htmlspecialchars($item->judul ?? '') ?>"
             style="width:100%;height:100%;object-fit:cover"
             onerror="this.style.display='none'">
      </div>

      <!-- Info -->
      <div style="padding:10px">
        <div style="font-size:12px;font-weight:600;color:var(--text);margin-bottom:3px;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($item->judul ?: 'Foto ' . $item->id) ?>
        </div>
        <?php if ($item->caption): ?>
        <div style="font-size:11px;color:var(--abu);margin-bottom:8px;
                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
          <?= htmlspecialchars($item->caption) ?>
        </div>
        <?php endif; ?>
        <?php if ($can_manage): ?>
        <a href="<?= site_url('parameter/landing/slideshow/hapus/'.$item->id) ?>"
           class="btn btn-danger btn-xs"
           onclick="return confirm('Hapus foto ini dari slideshow?')">
          <i class="ti ti-trash"></i> Hapus
        </a>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>
</div>

<?php if ($can_manage && count($list) > 1): ?>
<script>
// Drag & drop reorder sederhana
(function(){
  var grid    = document.getElementById('gallery-grid');
  var items   = grid ? Array.from(grid.children) : [];
  var dragSrc = null;

  items.forEach(function(item) {
    item.setAttribute('draggable', 'true');
    item.addEventListener('dragstart', function(e) {
      dragSrc = this;
      e.dataTransfer.effectAllowed = 'move';
      this.style.opacity = '0.4';
    });
    item.addEventListener('dragend',  function() { this.style.opacity = ''; });
    item.addEventListener('dragover', function(e) {
      e.preventDefault(); e.dataTransfer.dropEffect = 'move';
    });
    item.addEventListener('drop', function(e) {
      e.stopPropagation(); e.preventDefault();
      if (dragSrc !== this) {
        var allItems = Array.from(grid.children);
        var srcIdx   = allItems.indexOf(dragSrc);
        var tgtIdx   = allItems.indexOf(this);
        if (srcIdx < tgtIdx) grid.insertBefore(dragSrc, this.nextSibling);
        else                  grid.insertBefore(dragSrc, this);
        saveUrutan();
      }
    });
  });

  function saveUrutan() {
    var ids = Array.from(grid.children).map(function(el) { return el.getAttribute('data-id'); });
    var form = new FormData();
    ids.forEach(function(id, i) { form.append('ids['+i+']', id); });
    form.append('<?= $this->security->get_csrf_token_name() ?>', '<?= $this->security->get_csrf_hash() ?>');
    fetch('<?= site_url('parameter/landing/slideshow/urutan') ?>', { method:'POST', body:form });
  }
})();
</script>
<?php endif; ?>
