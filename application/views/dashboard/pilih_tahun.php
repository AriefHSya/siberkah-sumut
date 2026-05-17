<div class="page-header"><div class="page-title"><i class="ti ti-calendar"></i> Pilih Tahun Anggaran</div></div>
<div class="card" style="max-width:480px">
  <?= form_open(site_url('dashboard/set-tahun')) ?>
  <?= form_hidden($this->security->get_csrf_token_name(),$this->security->get_csrf_hash()) ?>
  <div class="form-group mb-3"><label>Tahun Anggaran</label>
    <select name="tahun" class="form-control">
      <?php foreach ($tahun_list as $t): ?><option value="<?= $t->tahun ?>" <?= ($t->tahun==$tahun_anggaran)?'selected':'' ?>><?= $t->tahun ?><?= $t->is_aktif ? ' (Aktif)' : '' ?></option><?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Terapkan</button>
  <?= form_close() ?>
</div>
