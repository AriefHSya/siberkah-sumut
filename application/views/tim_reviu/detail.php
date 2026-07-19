<div class="page-header">
  <div>
    <div class="page-title"><i class="ti ti-users-group"></i> Tim Review — <?= htmlspecialchars($tim->no_sk) ?></div>
    <div style="margin-top:4px;color:var(--text-muted);font-size:13px">
      <i class="ti ti-calendar"></i> <?= tgl_indo($tim->tgl_sk) ?>
      &nbsp;·&nbsp;
      <i class="ti ti-users"></i> <?= count($anggota) ?> anggota
    </div>
  </div>
  <div class="aksi-row">
    <?php if ($tim->file_sk): ?>
    <a href="<?= site_url('berkas/unduh/sk-tim/'.$tim->id) ?>" target="_blank" class="btn btn-outline btn-sm">
      <i class="ti ti-download"></i> Unduh SK
    </a>
    <?php endif; ?>
    <?php if ($this->rbac->can('tim_reviu.manage')): ?>
    <a href="<?= site_url('tim-reviu/edit/'.$tim->id) ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-edit"></i> Edit
    </a>
    <?php if (!$is_used): ?>
    <?= form_open(site_url('tim-reviu/hapus/'.$tim->id)) ?>
    <?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>
    <button type="submit" class="btn btn-danger btn-sm"
            onclick="return confirm('Hapus tim ini? Tindakan tidak dapat dibatalkan.')">
      <i class="ti ti-trash"></i> Hapus
    </button>
    <?= form_close() ?>
    <?php else: ?>
    <span class="badge badge-hijau" title="Tim sudah digunakan pada reviu, tidak dapat dihapus">
      <i class="ti ti-lock"></i> Digunakan
    </span>
    <?php endif; ?>
    <?php endif; ?>
    <a href="<?= site_url('tim-reviu') ?>" class="btn btn-outline btn-sm">
      <i class="ti ti-arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="g2">
  <!-- Info SK -->
  <div class="card">
    <div class="card-title"><i class="ti ti-file-certificate"></i> Info Surat Tugas</div>
    <table class="tbl" style="font-size:13px">
      <tr><td style="width:40%" class="text-muted">No. SK</td><td class="fw-500"><?= htmlspecialchars($tim->no_sk) ?></td></tr>
      <tr><td class="text-muted">Tanggal SK</td><td><?= tgl_indo($tim->tgl_sk) ?></td></tr>
      <tr><td class="text-muted">Keterangan</td><td><?= $tim->keterangan ? htmlspecialchars($tim->keterangan) : '—' ?></td></tr>
      <tr><td class="text-muted">File SK</td><td>
        <?php if ($tim->file_sk): ?>
        <a href="<?= site_url('berkas/unduh/sk-tim/'.$tim->id) ?>" target="_blank">
          <i class="ti ti-download"></i> <?= htmlspecialchars($tim->nama_asli_sk ?? basename($tim->file_sk)) ?>
        </a>
        <?php else: ?>—<?php endif; ?>
      </td></tr>
      <tr><td class="text-muted">Dipakai di Reviu</td><td>
        <?php
        $jml = $this->db->where('tim_id',$tim->id)->count_all_results('trx_reviu_inspektorat');
        echo $jml > 0 ? '<span class="badge badge-hijau">'.$jml.' reviu</span>' : '<span class="text-muted">Belum digunakan</span>';
        ?>
      </td></tr>
    </table>
  </div>

  <!-- Anggota Tim -->
  <div class="card">
    <div class="card-title"><i class="ti ti-users"></i> Anggota Tim (<?= count($anggota) ?> orang)</div>
    <?php if (empty($anggota)): ?>
    <p class="text-muted text-sm">Belum ada anggota.</p>
    <?php else: ?>
    <table class="tbl" style="font-size:13px">
      <thead>
        <tr>
          <th style="width:35px">No.</th>
          <th>Nama</th>
          <th>NIP</th>
          <th>Jabatan</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($anggota as $a): ?>
      <tr>
        <td class="text-center text-muted"><?= $a->urutan ?></td>
        <td class="fw-500"><?= htmlspecialchars($a->nama) ?></td>
        <td class="mono text-sm"><?= $a->nip ? htmlspecialchars($a->nip) : '—' ?></td>
        <td><?= $a->jabatan ? htmlspecialchars($a->jabatan) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>
