<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Rekap BKP TA <?= $tahun ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Arial,sans-serif;font-size:12px;color:#000;padding:20px}
h2{font-size:15px;text-align:center;margin-bottom:4px}
p{text-align:center;margin-bottom:16px;font-size:11px;color:#555}
table{width:100%;border-collapse:collapse;font-size:11px}
th,td{border:1px solid #333;padding:6px 8px}
th{background:#f0f0f0;font-weight:600}
.right{text-align:right}
.total{font-weight:600;background:#f9f9f9}
@media print{button{display:none}}
</style></head>
<body>
<h2>REKAPITULASI DATA BKP TA <?= $tahun ?></h2>
<p>SIBERKAH SUMUT — <?= $kab ? htmlspecialchars($kab->nama) : 'Seluruh Kabupaten/Kota' ?> | Dicetak: <?= $tgl_cetak ?> oleh <?= htmlspecialchars($user_nama) ?></p>
<button onclick="window.print()" style="margin-bottom:12px;padding:6px 14px;cursor:pointer">Cetak PDF</button>
<table>
<thead><tr><th>#</th><th>Kode BKP</th><th>Kab/Kota</th><th>Uraian BKP</th><th>Bidang</th><th class="right">Nilai (Rp)</th></tr></thead>
<tbody>
<?php $no=1; foreach ($list as $b): ?>
<tr><td><?= $no++ ?></td><td style="font-family:monospace"><?= htmlspecialchars($b->kode_bkp) ?></td><td><?= htmlspecialchars($b->nama_kabkota) ?></td><td><?= htmlspecialchars($b->uraian_bkp) ?></td><td><?= htmlspecialchars($b->nama_bidang) ?></td><td class="right"><?= rupiah($b->nilai) ?></td></tr>
<?php endforeach; ?>
<tr class="total"><td colspan="5">Total (<?= $rekap->total ?> BKP)</td><td class="right"><?= rupiah($rekap->total_nilai??0) ?></td></tr>
</tbody>
</table>
</body>
</html>
