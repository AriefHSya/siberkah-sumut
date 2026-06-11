<?php
/**
 * Parameter.php — Controller Manajemen Data Referensi & Konfigurasi
 *
 * Controller terbesar — mengelola semua data referensi yang diperlukan
 * sistem: tahun anggaran, batas waktu pengajuan, data BKP, data pemda
 * pejabat, dan tampilan landing page.
 *
 * SECTION:
 *   A. Tahun Anggaran   — CRUD tahun, set aktif
 *   B. Batas Waktu      — kelola deadline per jenis penyaluran per tahun
 *   C. Data BKP         — CRUD BKP, import Excel/CSV, cetak rekap
 *   D. Data Pemda       — pejabat KDH & dokumen Perda per kab/kota
 *   E. Tampilan Landing — foto pejabat + slideshow kinerja
 *   F. Log              — log perubahan parameter
 *
 * ROUTES (lihat config/routes.php untuk mapping lengkap):
 *   /parameter/tahun           → CRUD tahun anggaran
 *   /parameter/batas-waktu     → kelola deadline
 *   /parameter/bkp             → CRUD + import BKP
 *   /parameter/pemda           → pejabat & dokumen pemda
 *   /parameter/landing         → tampilan landing page
 *   /parameter/log             → log aktivitas
 *
 * IMPORT BKP:
 *   Upload .xlsx atau .csv → preview validasi → konfirmasi → proses import
 *   Duplikat dapat di-skip atau di-update per baris
 *   Library: application/libraries/XlsxReader.php (native, tanpa Composer)
 *
 * UPLOAD FOTO:
 *   Foto pejabat → uploads/landing/pejabat/{jenis}.jpg
 *   Foto slideshow → uploads/landing/slideshow/{timestamp}.jpg
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Parameter extends Auth_Controller
{
    public function __construct() {
        parent::__construct();
        $this->requirePerm('parameter.view');
        $this->load->model('Parameter_model');
        $this->data['active_menu'] = 'parameter';
    }

    private function _d($title, $sub = '') {
        return array_merge($this->data, [
            'title'      => $title . ' — SIBERKAH SUMUT',
            'active_sub' => $sub,
            'tahun_list' => $this->Parameter_model->get_all_tahun(),
        ]);
    }

    public function index() { redirect('parameter/tahun'); }

    // ─── TAHUN ────────────────────────────────────────────────
    public function tahun() {
        $this->requirePerm('parameter.tahun.view');
        $d = $this->_d('Data Tahun Anggaran','tahun');
        $list = $this->Parameter_model->get_all_tahun();
        // Hitung BKP per tahun di controller (bukan di view)
        $jml_bkp = [];
        foreach ($list as $t) {
            $jml_bkp[$t->tahun] = $this->db
                ->where('tahun', $t->tahun)->where('is_active', 1)
                ->count_all_results('ref_bkp');
        }
        $d['list']    = $list;
        $d['jml_bkp'] = $jml_bkp;
        $this->render('parameter/tahun', $d);
    }

    public function tahun_simpan() {
        $this->requirePerm('parameter.tahun.manage');
        $tahun = (int)$this->input->post('tahun');
        if ($tahun < 2000 || $tahun > 2099) {
            $this->session->set_flashdata('error','Tahun tidak valid.'); redirect('parameter/tahun'); return;
        }
        if ($this->Parameter_model->tahun_exists($tahun)) {
            $this->session->set_flashdata('error','Tahun '.$tahun.' sudah terdaftar.'); redirect('parameter/tahun'); return;
        }
        $set_aktif = $this->input->post('set_aktif') == '1';
        if ($set_aktif) $this->Parameter_model->set_tahun_aktif($tahun);
        $this->Parameter_model->insert_tahun(['tahun'=>$tahun,'is_aktif'=>$set_aktif?1:0,'created_by'=>$this->user_id]);
        $this->log_aktivitas('parameter.tahun.tambah','Tambah tahun '.$tahun);
        $this->session->set_flashdata('success','Tahun '.$tahun.' berhasil ditambahkan.');
        redirect('parameter/tahun');
    }

    public function tahun_set_aktif($id) {
        $this->requirePerm('parameter.tahun.manage');
        $t = $this->db->get_where('ref_tahun',['id'=>$id])->row();
        if ($t) $this->Parameter_model->set_tahun_aktif($t->tahun);
        $this->session->set_flashdata('success','Tahun aktif diubah.'); redirect('parameter/tahun');
    }

    public function tahun_hapus($id) {
        $this->requirePerm('parameter.tahun.manage');
        $t = $this->db->get_where('ref_tahun',['id'=>$id])->row();
        if (!$t) { redirect('parameter/tahun'); return; }
        if ($t->is_aktif) {
            $this->session->set_flashdata('error','Tahun aktif tidak dapat dihapus.'); redirect('parameter/tahun'); return;
        }
        $jml = $this->db->where('tahun',$t->tahun)->count_all_results('ref_bkp');
        if ($jml > 0) {
            $this->session->set_flashdata('error','Tahun '.$t->tahun.' memiliki '.$jml.' data BKP, tidak dapat dihapus.'); redirect('parameter/tahun'); return;
        }
        $this->Parameter_model->hapus_tahun($id);
        $this->session->set_flashdata('success','Tahun '.$t->tahun.' berhasil dihapus.'); redirect('parameter/tahun');
    }

    // ─── BATAS WAKTU ──────────────────────────────────────────
    public function batas_waktu() {
        $this->requirePerm('parameter.batas_waktu.view');
        $tahun = $this->input->get('tahun') ?? $this->tahun;
        $d = $this->_d('Batas Waktu Pengajuan','batas_waktu');
        $d['list']  = $this->Parameter_model->get_batas_waktu($tahun);
        $d['tahun'] = $tahun;
        $this->render('parameter/batas_waktu', $d);
    }

    public function batas_waktu_simpan() {
        $this->requirePerm('parameter.batas_waktu.manage');
        $data = [
            'tahun'            => $this->input->post('tahun',TRUE),
            'jenis_penyaluran' => $this->input->post('jenis_penyaluran',TRUE),
            'kode_tahap'       => $this->input->post('kode_tahap',TRUE),
            'label'            => $this->input->post('label',TRUE),
            'batas_pengajuan'  => $this->input->post('batas_pengajuan',TRUE),
            'batas_penyaluran' => $this->input->post('batas_penyaluran',TRUE),
            'keterangan'       => $this->input->post('keterangan',TRUE),
            'created_by'       => $this->user_id,
        ];
        // Cek duplikat
        $exists = $this->db->get_where('ref_batas_waktu',['tahun'=>$data['tahun'],'jenis_penyaluran'=>$data['jenis_penyaluran'],'kode_tahap'=>$data['kode_tahap']])->row();
        if ($exists) {
            $this->session->set_flashdata('error','Batas waktu untuk kombinasi ini sudah ada. Gunakan tombol Edit.'); redirect('parameter/batas-waktu?tahun='.$data['tahun']); return;
        }
        $this->Parameter_model->insert_batas_waktu($data);
        $this->log_aktivitas('parameter.batas_waktu.tambah','Tambah batas waktu '.$data['jenis_penyaluran'].' '.$data['tahun']);
        $this->session->set_flashdata('success','Batas waktu berhasil ditambahkan.');
        redirect('parameter/batas-waktu?tahun='.$data['tahun']);
    }

    public function batas_waktu_update($id) {
        $this->requirePerm('parameter.batas_waktu.manage');
        $data = [
            'batas_pengajuan'  => $this->input->post('batas_pengajuan',TRUE),
            'batas_penyaluran' => $this->input->post('batas_penyaluran',TRUE),
            'keterangan'       => $this->input->post('keterangan',TRUE),
            'alasan'           => $this->input->post('alasan',TRUE),
        ];
        $bw = $this->Parameter_model->get_batas_waktu_by_id($id);
        $this->Parameter_model->update_batas_waktu($id, $data, $this->user_id);
        $this->log_aktivitas('parameter.batas_waktu.edit','Edit batas waktu id='.$id);
        $this->session->set_flashdata('success','Batas waktu berhasil diperbarui.');
        redirect('parameter/batas-waktu?tahun='.($bw->tahun ?? $this->tahun));
    }

    public function batas_waktu_log() {
        $this->requirePerm('parameter.batas_waktu.view');
        $d = $this->_d('Log Perubahan Batas Waktu','batas_waktu');
        $d['log_list'] = $this->Parameter_model->get_log_batas_waktu(100);
        $this->render('parameter/batas_waktu_log', $d);
    }

    // ─── BKP ──────────────────────────────────────────────────
    public function bkp() {
        $this->requirePerm('parameter.bkp.view');
        $per_page      = 30;
        $page          = max(1, (int)$this->input->get('page'));
        $force_kabkota = $this->rbac->isKabkota() ? (int)$this->kabkota_id : NULL;
        $filters = [
            'tahun'      => $this->input->get('tahun') ?? $this->tahun,
            'kabkota_id' => $force_kabkota ?? $this->input->get('kabkota_id'),
            'bidang_id'  => $this->input->get('bidang_id'),
            'q'          => $this->input->get('q'),
        ];
        $total  = $this->Parameter_model->count_bkp($filters);
        $offset = ($page - 1) * $per_page;
        $d = $this->_d('Data Referensi BKP','bkp');
        $d['list']          = $this->Parameter_model->get_bkp($filters, $per_page, $offset);
        $d['rekap']         = $this->Parameter_model->rekap_bkp($filters['tahun'], $filters['kabkota_id']);
        $d['kabkota_list']  = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];
        $d['bidang_list']   = $this->Parameter_model->get_bidang();
        $d['filters']       = $filters;
        $d['is_provinsi']   = $this->rbac->isProvinsi();
        $d['paging']        = ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'parameter/bkp'];
        $this->render('parameter/bkp', $d);
    }

    public function bkp_simpan() {
        $this->requirePerm('parameter.bkp.manage');
        $nilai = (int)str_replace(['.','Rp',' ',','],'',$this->input->post('nilai'));
        // Role kab/kota tidak boleh pilih kabkota lain — paksa pakai kabkota sendiri
        $kabkota_id = $this->rbac->isKabkota()
            ? (int)$this->kabkota_id
            : (int)$this->input->post('kabkota_id', TRUE);
        $data = [
            'kode_bkp'   => strtoupper(trim($this->input->post('kode_bkp',TRUE))),
            'tahun'      => $this->input->post('tahun',TRUE),
            'kabkota_id' => $kabkota_id,
            'bidang_id'  => $this->input->post('bidang_id',TRUE),
            'uraian_bkp' => $this->input->post('uraian_bkp',TRUE),
            'nilai'      => $nilai,
            'nilai_awal' => $nilai,
            'created_by' => $this->user_id,
        ];
        if ($this->Parameter_model->bkp_exists($data['kode_bkp'], $data['tahun'])) {
            $this->session->set_flashdata('error','Kode BKP '.$data['kode_bkp'].' sudah ada untuk tahun '.$data['tahun'].'.'); redirect('parameter/bkp'); return;
        }
        $this->Parameter_model->insert_bkp($data);
        $this->log_aktivitas('parameter.bkp.tambah','Tambah BKP '.$data['kode_bkp'].' TA '.$data['tahun']);
        $this->session->set_flashdata('success','Data BKP berhasil ditambahkan.');
        redirect('parameter/bkp?tahun='.$data['tahun']);
    }

    public function bkp_update($id) {
        $this->requirePerm('parameter.bkp.manage');
        // Guard IDOR: kab/kota tidak bisa edit BKP milik kab/kota lain
        if ($this->rbac->isKabkota()) {
            $bkp_cek = $this->Parameter_model->get_bkp_by_id($id);
            if (!$bkp_cek || (int)$bkp_cek->kabkota_id !== (int)$this->kabkota_id) {
                $this->session->set_flashdata('error','Anda tidak berwenang mengubah data BKP ini.');
                redirect('parameter/bkp'); return;
            }
        }
        $nilai = (int)str_replace(['.','Rp',' ',','],'',$this->input->post('nilai'));
        $data  = ['uraian_bkp' => $this->input->post('uraian_bkp',TRUE), 'nilai' => $nilai];
        $this->Parameter_model->update_bkp($id, $data, $this->user_id);
        $this->log_aktivitas('parameter.bkp.edit','Edit BKP id='.$id);
        $this->session->set_flashdata('success','Data BKP berhasil diperbarui.');
        $bkp = $this->Parameter_model->get_bkp_by_id($id);
        redirect('parameter/bkp?tahun='.($bkp->tahun ?? $this->tahun));
    }

    public function bkp_hapus($id) {
        $this->requirePerm('parameter.bkp.manage');
        $bkp = $this->Parameter_model->get_bkp_by_id($id);
        // Guard IDOR: kab/kota tidak bisa hapus BKP milik kab/kota lain
        if ($this->rbac->isKabkota()) {
            if (!$bkp || (int)$bkp->kabkota_id !== (int)$this->kabkota_id) {
                $this->session->set_flashdata('error','Anda tidak berwenang menghapus data BKP ini.');
                redirect('parameter/bkp'); return;
            }
        }
        $punya_pekerjaan = $this->db->where('bkp_id',$id)->count_all_results('trx_pekerjaan');
        if ($punya_pekerjaan) {
            $this->session->set_flashdata('error','BKP ini sudah memiliki data pekerjaan dan tidak dapat dihapus.'); redirect('parameter/bkp'); return;
        }
        $this->Parameter_model->hapus_bkp($id);
        $this->session->set_flashdata('success','Data BKP berhasil dihapus.');
        redirect('parameter/bkp?tahun='.($bkp->tahun ?? $this->tahun));
    }

    public function bkp_import() {
        $this->requirePerm('parameter.bkp.manage');
        $d = $this->_d('Import Data BKP', 'bkp');
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['tahun_list']   = $this->Parameter_model->get_all_tahun();
        $this->render('parameter/bkp_import', $d);
    }

    /** POST: parse file Excel → tampilkan preview + deteksi duplikat */
    public function bkp_preview_import() {
        $this->requirePerm('parameter.bkp.manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('parameter/bkp/import'); return;
        }

        $tmp_dir = APPPATH . 'cache/import_tmp/';
        if (!is_dir($tmp_dir)) mkdir($tmp_dir, 0755, TRUE);

        $this->load->library('upload', [
            'upload_path'   => $tmp_dir,
            'allowed_types' => 'xlsx|csv',
            'max_size'      => 5120,
            'file_name'     => 'bkp_import_' . $this->user_id . '_' . time(),
        ]);

        if (!$this->upload->do_upload('file_import')) {
            $this->session->set_flashdata('error', $this->upload->display_errors('', ''));
            redirect('parameter/bkp/import'); return;
        }

        $upload       = $this->upload->data();
        $filepath     = $tmp_dir . $upload['file_name'];
        $tahun_import = (int)$this->input->post('tahun_import');

        try {
            $rows = $this->_parse_import_file($filepath, $upload['file_ext']);
        } catch (Exception $e) {
            @unlink($filepath);
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('parameter/bkp/import'); return;
        }

        if (empty($rows)) {
            @unlink($filepath);
            $this->session->set_flashdata('error', 'File kosong atau tidak ada data yang dapat dibaca.');
            redirect('parameter/bkp/import'); return;
        }

        // ── Bangun lookup referensi ──────────────────────────────
        $all_kabkota = $this->db->get('ref_kabkota')->result();
        $all_bidang  = $this->db->get('ref_bidang')->result();

        // ── Preview per baris ────────────────────────────────────
        $preview = [];
        $row_num = 1; // baris 1 = header, sudah di-skip di parser

        foreach ($rows as $row) {
            $row_num++;

            // Format: [No Urut, Tahun, Kab/Kota, Bidang, Uraian, Nilai]
            $no_urut   = trim($row[0] ?? '');
            $tahun_row = trim($row[1] ?? '');
            $nama_kab  = trim($row[2] ?? '');
            $nama_bid  = trim($row[3] ?? '');
            $uraian    = trim($row[4] ?? '');
            $nilai_raw = trim($row[5] ?? '0');

            // Lewati baris "dst", baris kosong, atau baris sub-header
            if (empty($nama_kab) && empty($uraian)) continue;
            if (strtolower($no_urut) === 'dst' || strtolower($no_urut) === 'no urut') continue;

            $tahun = ($tahun_row && is_numeric($tahun_row))
                   ? (int)$tahun_row
                   : $tahun_import;

            $row_errors = [];
            if (!$tahun) $row_errors[] = 'Tahun tidak valid';
            if (empty($uraian)) $row_errors[] = 'Uraian/nama kegiatan kosong';

            // Cocokkan Kab/Kota
            $kabkota        = $this->_match_kabkota($nama_kab, $all_kabkota);
            $kabkota_mapped = $kabkota ? $kabkota->nama : NULL;
            if (!$kabkota) $row_errors[] = 'Kab/Kota "' . htmlspecialchars($nama_kab) . '" tidak dikenali';

            // Cocokkan Bidang (dengan fuzzy + alias)
            $bidang_result  = $this->_match_bidang($nama_bid, $all_bidang);
            $bidang         = $bidang_result['obj'];
            $bidang_warning = $bidang_result['warning']; // peringatan jika pakai fuzzy
            if (!$bidang) $row_errors[] = 'Bidang "' . htmlspecialchars($nama_bid) . '" tidak dikenali';

            // Validasi nilai harus angka — bersihkan format umum (Rp, titik, koma, spasi)
            $nilai_bersih = str_replace(['Rp', '.', ',', ' '], '', $nilai_raw);
            if ($nilai_bersih !== '' && !is_numeric($nilai_bersih)) {
                $row_errors[] = 'Nilai "' . htmlspecialchars($nilai_raw) . '" bukan angka';
                $nilai = 0;
            } else {
                $nilai = (int)$nilai_bersih;
            }

            // Cek duplikat berdasarkan (tahun + kabkota_id + uraian)
            $existing = NULL;
            if ($kabkota && $tahun && $uraian) {
                $existing = $this->db
                    ->where('tahun',      $tahun)
                    ->where('kabkota_id', $kabkota->id)
                    ->where('uraian_bkp', $uraian)
                    ->get('ref_bkp')->row();
            }

            // Generate preview kode BKP (akan dikonfirmasi saat proses)
            $preview_kode = $kabkota
                ? $this->_preview_kode_bkp($tahun, $kabkota->nama)
                : 'BKP-' . $tahun . '-???-???';

            $preview[] = [
                'row'             => $row_num,
                'tahun'           => $tahun,
                'kabkota_id'      => $kabkota ? $kabkota->id   : NULL,
                'kabkota_nama'    => $kabkota ? $kabkota->nama  : htmlspecialchars($nama_kab),
                'kabkota_input'   => htmlspecialchars($nama_kab),
                'kabkota_mapped'  => $kabkota_mapped,
                'bidang_id'       => $bidang  ? $bidang->id    : NULL,
                'bidang_nama'     => $bidang  ? $bidang->nama   : htmlspecialchars($nama_bid),
                'bidang_input'    => htmlspecialchars($nama_bid),
                'bidang_warning'  => $bidang_warning,
                'uraian_bkp'      => htmlspecialchars($uraian),
                'nilai'           => $nilai,
                'preview_kode'    => $preview_kode,
                'status'          => $existing ? 'duplikat' : 'baru',
                'existing_id'     => $existing ? $existing->id         : NULL,
                'existing_kode'   => $existing ? $existing->kode_bkp   : NULL,
                'existing_nilai'  => $existing ? $existing->nilai       : NULL,
                'errors'          => $row_errors,
                'aksi'            => $existing ? 'skip' : 'import',
            ];
        }

        @unlink($filepath); // file sementara tidak lagi dibutuhkan

        $d = $this->_d('Preview Import BKP', 'bkp');
        $d['preview']        = $preview;
        $d['tahun_import']   = $tahun_import;
        $d['total_baru']     = count(array_filter($preview, function($r){ return $r['status']==='baru'     && empty($r['errors']); }));
        $d['total_duplikat'] = count(array_filter($preview, function($r){ return $r['status']==='duplikat' && empty($r['errors']); }));
        $d['total_error']    = count(array_filter($preview, function($r){ return !empty($r['errors']); }));
        $this->render('parameter/bkp_import', $d);
    }

    /** POST: eksekusi import berdasarkan keputusan user (skip/update per baris) */
    public function bkp_proses_import() {
        $this->requirePerm('parameter.bkp.manage');

        $rows_json = $this->input->post('rows_data');
        if (!$rows_json) {
            $this->session->set_flashdata('error', 'Data preview tidak ditemukan. Ulangi upload.');
            redirect('parameter/bkp/import'); return;
        }

        $rows = json_decode($rows_json, TRUE);
        if (!is_array($rows) || empty($rows)) {
            $this->session->set_flashdata('error', 'Data tidak valid.');
            redirect('parameter/bkp/import'); return;
        }

        $aksi_map = $this->input->post('aksi') ?? [];
        $n_import = $n_update = $n_skip = $n_error = 0;

        // Cache counter per (tahun+abbrev) untuk generate kode unik dalam satu batch
        $seq_cache = [];

        $this->db->trans_start();

        foreach ($rows as $idx => $row) {
            if (!empty($row['errors']) || !$row['kabkota_id'] || !$row['bidang_id']) {
                $n_error++; continue;
            }

            $row_key = $idx . '_' . $row['tahun'] . '_' . $row['kabkota_id'];
            $aksi    = $aksi_map[$row_key] ?? $row['aksi'];

            if ($aksi === 'skip') { $n_skip++; continue; }

            if ($row['status'] === 'duplikat' && $aksi === 'update' && $row['existing_id']) {
                // Update record yang sudah ada
                $this->db->where('id', $row['existing_id'])->update('ref_bkp', [
                    'bidang_id'  => (int)$row['bidang_id'],
                    'uraian_bkp' => $row['uraian_bkp'],
                    'nilai'      => (int)$row['nilai'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $this->db->insert('ref_bkp_log', [
                    'bkp_id'     => $row['existing_id'],
                    'field_ubah' => 'import_update',
                    'nilai_lama' => $row['existing_kode'] . ' / nilai=' . $row['existing_nilai'],
                    'nilai_baru' => 'uraian=' . $row['uraian_bkp'] . ' / nilai=' . $row['nilai'],
                    'user_id'    => $this->user_id,
                    'ip_address' => $this->input->ip_address(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $n_update++;

            } elseif ($row['status'] === 'baru') {
                // Generate kode BKP unik
                $kode_bkp = $this->_generate_kode_bkp(
                    (int)$row['tahun'],
                    $row['kabkota_nama'],
                    $seq_cache
                );

                $this->db->insert('ref_bkp', [
                    'kode_bkp'   => $kode_bkp,
                    'tahun'      => (int)$row['tahun'],
                    'kabkota_id' => (int)$row['kabkota_id'],
                    'bidang_id'  => (int)$row['bidang_id'],
                    'uraian_bkp' => $row['uraian_bkp'],
                    'nilai'      => (int)$row['nilai'],
                    'is_active'  => 1,
                    'created_by' => $this->user_id,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $n_import++;
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan saat menyimpan data.');
            redirect('parameter/bkp/import'); return;
        }

        $this->log_aktivitas('parameter.bkp.import',
            "Import BKP: {$n_import} baru, {$n_update} diperbarui, {$n_skip} dilewati, {$n_error} error");
        $this->session->set_flashdata('success',
            "Import selesai: <strong>{$n_import}</strong> data baru, " .
            "<strong>{$n_update}</strong> diperbarui, " .
            "<strong>{$n_skip}</strong> dilewati.");
        redirect('parameter/bkp');
    }

    /** Download template CSV sesuai format yang diharapkan */
    public function bkp_template() {
        $this->requirePerm('parameter.bkp.manage');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_import_bkp.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fputs($out, "\xEF\xBB\xBF"); // BOM UTF-8

        fputcsv($out, ['No Urut', 'Tahun', 'Kab/Kota', 'Bidang Pekerjaan', 'Uraian', 'Nilai'], ';');
        fputcsv($out, ['1', '2026', 'Kota Medan',       'Infrastruktur', 'Pembangunan Jalan Lingkungan Kec. Medan Kota',    '1500000000'], ';');
        fputcsv($out, ['2', '2026', 'Kota Medan',       'Pendidikan',    'Pengadaan Sarana Pendidikan Dasar Kota Medan',      '800000000'], ';');
        fputcsv($out, ['3', '2026', 'Kota Binjai',      'Kesehatan',     'Peningkatan Fasilitas Puskesmas Kota Binjai',       '600000000'], ';');
        fputcsv($out, ['4', '2026', 'Kab. Deli Serdang','Pertanian',     'Pengembangan Irigasi Teknis Deli Serdang',          '450000000'], ';');
        fputcsv($out, ['5', '2026', 'Kab. Karo',        'Pariwisata',    'Pengembangan Destinasi Wisata Berastagi',           '350000000'], ';');
        fclose($out);
        exit;
    }

    /** Download template XLSX sesuai format yang diharapkan */
    public function bkp_template_xlsx() {
        $this->requirePerm('parameter.bkp.manage');

        require_once APPPATH . 'libraries/XlsxWriter.php';
        $headers = [
            ['label'=>'No Urut',          'format'=>'number'],
            ['label'=>'Tahun',            'format'=>'number'],
            ['label'=>'Kab/Kota',         'format'=>'string'],
            ['label'=>'Bidang Pekerjaan', 'format'=>'string'],
            ['label'=>'Uraian',           'format'=>'string'],
            ['label'=>'Nilai',            'format'=>'number'],
        ];
        $rows = [
            [1, 2026, 'Kota Medan',        'Infrastruktur', 'Pembangunan Jalan Lingkungan Kec. Medan Kota', 1500000000],
            [2, 2026, 'Kota Medan',        'Pendidikan',    'Pengadaan Sarana Pendidikan Dasar Kota Medan', 800000000],
            [3, 2026, 'Kota Binjai',       'Kesehatan',     'Peningkatan Fasilitas Puskesmas Kota Binjai',  600000000],
            [4, 2026, 'Kab. Deli Serdang', 'Pertanian',     'Pengembangan Irigasi Teknis Deli Serdang',     450000000],
            [5, 2026, 'Kab. Karo',         'Pariwisata',    'Pengembangan Destinasi Wisata Berastagi',      350000000],
        ];

        $writer = new XlsxWriter();
        $writer->writeSheet('Template Import BKP', $rows, $headers);
        $writer->download('template_import_bkp.xlsx');
    }

    // ─── PRIVATE HELPERS ─────────────────────────────────────────

    /** Cocokkan nama Kab/Kota ke referensi: exact → strip prefix → partial */
    private function _match_kabkota($input, $all_kabkota)
    {
        if (empty($input)) return NULL;
        $inp = strtolower(trim($input));

        // 1. Exact match
        foreach ($all_kabkota as $k) {
            if (strtolower($k->nama) === $inp) return $k;
        }

        // 2. Strip prefix "Kab." / "Kota" / "Kabupaten" lalu coba lagi
        $stripped = preg_replace('/^(kab\.|kota|kabupaten)\s+/i', '', $inp);
        if ($stripped !== $inp) {
            foreach ($all_kabkota as $k) {
                $kn = strtolower(preg_replace('/^(kab\.|kota|kabupaten)\s+/i', '', $k->nama));
                if ($kn === $stripped) return $k;
            }
        }

        // 3. Partial: input contained in nama, atau nama contained in input
        foreach ($all_kabkota as $k) {
            $kn = strtolower($k->nama);
            if (strpos($kn, $inp) !== FALSE || strpos($inp, $stripped) !== FALSE && strpos($kn, $stripped) !== FALSE) {
                return $k;
            }
        }

        // 4. Fuzzy similarity (threshold 70%)
        $best = NULL; $best_score = 0;
        foreach ($all_kabkota as $k) {
            similar_text($inp, strtolower($k->nama), $pct);
            if ($pct > $best_score) { $best_score = $pct; $best = $k; }
        }
        return ($best_score >= 70) ? $best : NULL;
    }

    /**
     * Cocokkan nama Bidang ke referensi: exact → alias → partial → fuzzy
     * Return: ['obj' => object|NULL, 'warning' => string|NULL]
     */
    private function _match_bidang($input, $all_bidang)
    {
        if (empty($input)) return ['obj' => NULL, 'warning' => NULL];
        $inp = strtolower(trim($input));

        // Alias umum nama bidang yang tidak persis sama
        $aliases = [
            'pangan'            => 'pertanian',
            'ketahanan pangan'  => 'pertanian',
            'pertanian & pangan'=> 'pertanian',
            'perkebunan'        => 'pertanian',
            'peternakan'        => 'pertanian',
            'irigasi'           => 'infrastruktur',
            'jalan'             => 'infrastruktur',
            'jembatan'          => 'infrastruktur',
            'gedung'            => 'infrastruktur',
            'air minum'         => 'sanitasi & air bersih',
            'air bersih'        => 'sanitasi & air bersih',
            'sanitasi'          => 'sanitasi & air bersih',
            'umkm'              => 'ekonomi & umkm',
            'koperasi'          => 'ekonomi & umkm',
            'perikanan'         => 'perikanan & kelautan',
            'kelautan'          => 'perikanan & kelautan',
            'lingkungan'        => 'lingkungan hidup',
            'persampahan'       => 'lingkungan hidup',
            'digital'           => 'teknologi informasi',
            'it'                => 'teknologi informasi',
            'teknologi'         => 'teknologi informasi',
            'bansos'            => 'sosial',
            'sosial kemasyarakatan' => 'sosial',
        ];

        // Resolusi alias
        $lookup = $aliases[$inp] ?? $inp;

        // 1. Exact match (pada nama atau kode)
        foreach ($all_bidang as $b) {
            if (strtolower($b->nama) === $lookup || strtolower($b->kode) === $inp) {
                $warning = ($lookup !== $inp)
                    ? 'Dipetakan dari "' . $input . '" ke "' . $b->nama . '"'
                    : NULL;
                return ['obj' => $b, 'warning' => $warning];
            }
        }

        // 2. Partial: lookup terkandung dalam nama bidang
        foreach ($all_bidang as $b) {
            $bn = strtolower($b->nama);
            if (strpos($bn, $lookup) !== FALSE || strpos($lookup, $bn) !== FALSE) {
                return ['obj' => $b, 'warning' => 'Dipetakan dari "' . $input . '" ke "' . $b->nama . '"'];
            }
        }

        // 3. Fuzzy similarity (threshold 65%)
        $best = NULL; $best_score = 0;
        foreach ($all_bidang as $b) {
            similar_text($lookup, strtolower($b->nama), $pct);
            if ($pct > $best_score) { $best_score = $pct; $best = $b; }
        }
        if ($best_score >= 65) {
            return ['obj' => $best, 'warning' => 'Dipetakan dari "' . $input . '" ke "' . $best->nama . '" (kemiripan ' . round($best_score) . '%)'];
        }

        return ['obj' => NULL, 'warning' => NULL];
    }

    /**
     * Generate kode BKP unik: BKP-{TAHUN}-{ABV3}-{SEQ3}
     * $seq_cache dipakai agar dalam satu batch tidak ada kode yang sama
     */
    private function _generate_kode_bkp($tahun, $kabkota_nama, &$seq_cache)
    {
        // Ambil 3 huruf dari nama kota/kab (strip prefix dulu)
        $nama   = preg_replace('/^(Kab\.|Kota|Kabupaten)\s+/i', '', $kabkota_nama);
        $letters= preg_replace('/[^a-zA-Z]/', '', $nama);
        $abbrev = strtoupper(substr($letters, 0, 3));
        if (strlen($abbrev) < 3) $abbrev = str_pad($abbrev, 3, 'X');

        $cache_key = $tahun . '_' . $abbrev;

        // Ambil sekuens terakhir dari DB + cache batch ini
        if (!isset($seq_cache[$cache_key])) {
            $pattern  = 'BKP-' . $tahun . '-' . $abbrev . '-%';
            $last_row = $this->db
                ->select_max('kode_bkp')
                ->like('kode_bkp', $pattern, 'after')
                ->where('tahun', $tahun)
                ->get('ref_bkp')->row();

            $last_seq = 0;
            if ($last_row && $last_row->kode_bkp) {
                $parts = explode('-', $last_row->kode_bkp);
                $last_seq = (int)end($parts);
            }
            $seq_cache[$cache_key] = $last_seq;
        }

        $seq_cache[$cache_key]++;
        return 'BKP-' . $tahun . '-' . $abbrev . '-' . str_pad($seq_cache[$cache_key], 3, '0', STR_PAD_LEFT);
    }

    /** Preview kode BKP (estimasi, belum cek duplikat di DB) */
    private function _preview_kode_bkp($tahun, $kabkota_nama)
    {
        $nama   = preg_replace('/^(Kab\.|Kota|Kabupaten)\s+/i', '', $kabkota_nama);
        $letters= preg_replace('/[^a-zA-Z]/', '', $nama);
        $abbrev = strtoupper(substr($letters, 0, 3));
        if (strlen($abbrev) < 3) $abbrev = str_pad($abbrev, 3, 'X');
        return 'BKP-' . $tahun . '-' . $abbrev . '-###';
    }

    /** Helper: parse file xlsx atau csv menjadi array rows (skip baris header) */
    private function _parse_import_file($filepath, $ext)
    {
        $ext = strtolower(ltrim($ext, '.'));

        if ($ext === 'xlsx') {
            $this->load->library('XlsxReader');
            $rows_raw = $this->xlsxreader->load($filepath)->getRows();
        } elseif ($ext === 'csv') {
            $rows_raw = [];
            if (($fh = fopen($filepath, 'r')) !== FALSE) {
                // Deteksi delimiter: ; atau ,
                $first = fgets($fh);
                rewind($fh);
                $delim = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
                while (($row = fgetcsv($fh, 0, $delim)) !== FALSE) {
                    $rows_raw[] = $row;
                }
                fclose($fh);
            }
        } else {
            throw new RuntimeException('Format file tidak didukung. Gunakan .xlsx atau .csv');
        }

        // Hapus baris header (baris pertama)
        if (!empty($rows_raw)) {
            array_shift($rows_raw);
        }

        // Filter baris kosong
        return array_values(array_filter($rows_raw, function($r) {
            return !empty(array_filter($r, fn($v) => trim($v) !== ''));
        }));
    }

    public function bkp_cetak() {
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id');
        $list  = $this->Parameter_model->get_bkp(['tahun'=>$tahun,'kabkota_id'=>$kabkota_id]);
        $rekap = $this->Parameter_model->rekap_bkp($tahun, $kabkota_id);
        $kab   = $kabkota_id ? $this->db->get_where('ref_kabkota',['id'=>$kabkota_id])->row() : NULL;
        $this->render_plain('parameter/bkp_cetak',['list'=>$list,'rekap'=>$rekap,'tahun'=>$tahun,'kab'=>$kab,'tgl_cetak'=>tgl_indo(date('Y-m-d')),'user_nama'=>$this->data['current_user']->nama]);
    }

    /**
     * AJAX: Generate preview kode BKP berikutnya
     * GET parameter/bkp/generate-kode?tahun=2026&kabkota_id=5
     * Return JSON: { kode: "BKP-2026-MED-001" }
     */
    public function bkp_generate_kode() {
        $this->requirePerm('parameter.bkp.manage');
        $tahun      = (int)$this->input->get('tahun');
        $kabkota_id = (int)$this->input->get('kabkota_id');

        if (!$tahun || !$kabkota_id) {
            $this->json(['kode' => '']);
            return;
        }

        $kabkota = $this->db->get_where('ref_kabkota', ['id' => $kabkota_id])->row();
        if (!$kabkota) { $this->json(['kode' => '']); return; }

        // Gunakan logika yang sama dengan _generate_kode_bkp
        $nama    = preg_replace('/^(Kab\.|Kota|Kabupaten)\s+/i', '', $kabkota->nama);
        $letters = preg_replace('/[^a-zA-Z]/', '', $nama);
        $abbrev  = strtoupper(substr($letters, 0, 3));
        if (strlen($abbrev) < 3) $abbrev = str_pad($abbrev, 3, 'X');

        $pattern  = 'BKP-' . $tahun . '-' . $abbrev . '-';
        $last_row = $this->db
            ->select_max('kode_bkp')
            ->like('kode_bkp', $pattern, 'after')
            ->where('tahun', $tahun)
            ->get('ref_bkp')->row();

        $last_seq = 0;
        if ($last_row && $last_row->kode_bkp) {
            $parts    = explode('-', $last_row->kode_bkp);
            $last_seq = (int)end($parts);
        }

        $kode = 'BKP-' . $tahun . '-' . $abbrev . '-' . str_pad($last_seq + 1, 3, '0', STR_PAD_LEFT);
        $this->json(['kode' => $kode]);
    }

    // ─── PEMDA ────────────────────────────────────────────────
    public function pemda() {
        $this->requirePerm('parameter.pemda.view');
        $tahun      = $this->input->get('tahun') ?? $this->tahun;
        $kabkota_id = $this->input->get('kabkota_id') ?? ($this->rbac->isKabkota() ? $this->kabkota_id : NULL);
        $d = $this->_d('Data Umum Pemda','pemda');
        $d['tahun_sel']    = $tahun;
        $d['kabkota_sel']  = $kabkota_id;
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['pejabat']      = $kabkota_id ? $this->Parameter_model->get_pejabat($kabkota_id, $tahun) : [];
        $d['dokumen']      = $kabkota_id ? $this->Parameter_model->get_dokumen_pemda($kabkota_id, $tahun) : [];
        $this->render('parameter/pemda', $d);
    }

    public function pemda_simpan_pejabat() {
        $this->requirePerm('parameter.pemda.manage');
        $data = [
            'kabkota_id' => $this->input->post('kabkota_id',TRUE),
            'tahun'      => $this->input->post('tahun',TRUE),
            'jenis'      => $this->input->post('jenis',TRUE),
            'nama'       => $this->input->post('nama',TRUE),
            'nip'        => $this->input->post('nip',TRUE),
            'jabatan'    => $this->input->post('jabatan',TRUE),
            'pangkat'    => $this->input->post('pangkat',TRUE),
        ];
        $this->Parameter_model->simpan_pejabat($data, $this->user_id);
        $this->session->set_flashdata('success','Data pejabat berhasil disimpan.');
        redirect('parameter/pemda?tahun='.$data['tahun'].'&kabkota_id='.$data['kabkota_id']);
    }

    public function pemda_simpan_dokumen() {
        $this->requirePerm('parameter.pemda.manage');
        $data = [
            'kabkota_id' => $this->input->post('kabkota_id',TRUE),
            'tahun'      => $this->input->post('tahun',TRUE),
            'jenis'      => $this->input->post('jenis',TRUE),
            'nomor'      => $this->input->post('nomor',TRUE),
            'tanggal'    => $this->input->post('tanggal',TRUE),
            'keterangan' => $this->input->post('keterangan',TRUE),
        ];
        $id_edit = $this->input->post('id_edit');
        $this->Parameter_model->simpan_dokumen($data, $id_edit, $this->user_id);
        $this->session->set_flashdata('success','Dokumen berhasil disimpan.');
        redirect('parameter/pemda?tahun='.$data['tahun'].'&kabkota_id='.$data['kabkota_id']);
    }

    public function pemda_hapus_dokumen($id) {
        $this->requirePerm('parameter.pemda.manage');
        $dok = $this->Parameter_model->get_dokumen_by_id($id);
        $this->Parameter_model->hapus_dokumen($id);
        $this->session->set_flashdata('success','Dokumen berhasil dihapus.');
        redirect('parameter/pemda'.($dok ? '?tahun='.$dok->tahun.'&kabkota_id='.$dok->kabkota_id : ''));
    }

    // ─── PEJABAT BKAD PROVINSI ───────────────────────────────

    public function pejabat_provinsi()
    {
        $this->requirePerm('parameter.pemda.view');
        if (!$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Akses hanya untuk Admin Provinsi.');
            redirect('parameter'); return;
        }
        $tahun = $this->input->get('tahun') ?: $this->tahun;
        $d = $this->_d('Pejabat BKAD Provinsi', 'parameter');
        $d['tahun_sel']  = $tahun;
        $d['tahun_list'] = $this->Parameter_model->get_all_tahun();
        $d['pejabat']    = $this->Parameter_model->get_pejabat_bkad_prov($tahun);
        $this->render('parameter/pejabat_provinsi', $d);
    }

    public function pejabat_provinsi_simpan()
    {
        $this->requirePerm('parameter.pemda.manage');
        if (!$this->rbac->isProvinsi()) { show_404(); return; }

        $tahun = $this->input->post('tahun', TRUE);
        $jenis_list = ['kepala_badan', 'kabid_anggaran', 'bendahara_pengeluaran'];
        foreach ($jenis_list as $jenis) {
            $nama = $this->input->post('nama_'.$jenis, TRUE);
            if ($nama === NULL) continue;
            $this->Parameter_model->simpan_pejabat_bkad_prov([
                'tahun'   => $tahun,
                'jenis'   => $jenis,
                'nama'    => $nama,
                'nip'     => $this->input->post('nip_'.$jenis, TRUE),
                'jabatan' => $this->input->post('jabatan_'.$jenis, TRUE),
            ]);
        }
        $this->log_aktivitas('parameter.pejabat_provinsi', 'Simpan pejabat BKAD provinsi tahun='.$tahun);
        $this->session->set_flashdata('success', 'Data pejabat BKAD Provinsi berhasil disimpan.');
        redirect('parameter/pejabat-provinsi?tahun='.$tahun);
    }

    // ─── LOGO PROVINSI ────────────────────────────────────────
    // Hanya superadmin dan admin_provinsi yang bisa upload

    public function logo_provinsi() {
        if (!$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('parameter'); return;
        }
        $d = $this->_d('Logo Provinsi', 'logo');
        $current = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        $d['logo_path'] = $current ? $current->nilai : NULL;
        $this->render('parameter/logo_provinsi', $d);
    }

    public function logo_provinsi_upload() {
        if (!$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('parameter/logo'); return;
        }
        if ($_FILES['file_logo']['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'Pilih file logo terlebih dahulu.');
            redirect('parameter/logo'); return;
        }
        $dir = FCPATH . 'uploads/logo/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $dir,
            'allowed_types' => 'jpg|jpeg|png|svg|webp',
            'max_size'      => 2048,
            'file_name'     => 'logo_provinsi_' . time(),
        ]);
        if (!$this->upload->do_upload('file_logo')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('parameter/logo'); return;
        }
        $info     = $this->upload->data();
        $new_path = 'uploads/logo/' . $info['file_name'];

        // Hapus file lama jika ada
        $current = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        if ($current && $current->nilai) {
            @unlink(FCPATH . $current->nilai);
        }

        // Upsert: update jika row ada, insert jika belum ada
        if ($current) {
            $this->db->where('kode', 'logo_provinsi')->update('ref_app_setting', [
                'nilai'      => $new_path,
                'updated_by' => $this->user_id,
            ]);
        } else {
            $this->db->insert('ref_app_setting', [
                'kode'       => 'logo_provinsi',
                'nilai'      => $new_path,
                'keterangan' => 'Path file logo Pemerintah Provinsi',
                'updated_by' => $this->user_id,
            ]);
        }
        $this->log_aktivitas('parameter.logo.upload', 'Upload logo provinsi');
        $this->session->set_flashdata('success', 'Logo provinsi berhasil diupload.');
        redirect('parameter/logo');
    }

    public function logo_provinsi_hapus() {
        if (!$this->rbac->isProvinsi()) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('parameter/logo'); return;
        }
        $current = $this->db->get_where('ref_app_setting', ['kode' => 'logo_provinsi'])->row();
        if ($current && $current->nilai) {
            @unlink(FCPATH . $current->nilai);
        }
        if ($current) {
            $this->db->where('kode', 'logo_provinsi')->update('ref_app_setting', [
                'nilai'      => '',
                'updated_by' => $this->user_id,
            ]);
        }
        $this->session->set_flashdata('success', 'Logo provinsi dihapus.');
        redirect('parameter/logo');
    }

    // ─── LOG ──────────────────────────────────────────────────
    public function log() {
        $tahun    = $this->input->get('tahun') ?? $this->tahun;
        $per_page = 50;
        $page     = max(1, (int)$this->input->get('page'));
        $offset   = ($page - 1) * $per_page;
        $total    = $this->Parameter_model->count_log_bkp($tahun);
        $d = $this->_d('Log Perubahan Parameter','log');
        $d['log_bkp']  = $this->Parameter_model->get_log_bkp($tahun, $per_page, $offset);
        $d['log_pemda'] = $this->Parameter_model->get_log_pemda($tahun, 50);
        $d['log_bw']    = $this->Parameter_model->get_log_batas_waktu(50);
        $d['tahun']     = $tahun;
        $d['paging']    = ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'parameter/log'];
        $this->render('parameter/log', $d);
    }

    // ─── LANDING PAGE — FOTO PEJABAT ─────────────────────────

    public function landing_pejabat()
    {
        $this->requirePerm('parameter.landing.view');
        $pejabat_rows = $this->db->get('ref_landing_pejabat')->result();
        $pejabat = [];
        foreach ($pejabat_rows as $p) $pejabat[$p->jenis] = $p;

        $d = $this->_d('Tampilan Landing — Foto Pejabat', 'landing');
        $d['pejabat'] = $pejabat;
        $this->render('parameter/landing_pejabat', $d);
    }

    public function landing_pejabat_simpan($jenis)
    {
        $this->requirePerm('parameter.landing.manage');

        $valid_jenis = ['gubernur','wakil_gubernur','sekda','kepala_bkad'];
        if (!in_array($jenis, $valid_jenis)) { show_404(); return; }

        $nama    = $this->input->post('nama', TRUE);
        $jabatan = $this->input->post('jabatan', TRUE);

        $upload_dir = FCPATH . 'uploads/landing/pejabat/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, TRUE);

        $foto_path = NULL;
        if (!empty($_FILES['foto']['name'])) {
            // Tangani error PHP-level lebih dulu (file terlalu besar, dll)
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $php_errors = [
                    UPLOAD_ERR_INI_SIZE   => 'File melebihi batas upload_max_filesize PHP (' . ini_get('upload_max_filesize') . '). Restart server dengan: php -S localhost:8080 -c php.ini router.php',
                    UPLOAD_ERR_FORM_SIZE  => 'File melebihi batas MAX_FILE_SIZE form.',
                    UPLOAD_ERR_PARTIAL    => 'File hanya terupload sebagian. Coba lagi.',
                    UPLOAD_ERR_NO_FILE    => 'Tidak ada file yang dipilih.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Folder temp tidak ditemukan.',
                    UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
                ];
                $err_msg = $php_errors[$_FILES['foto']['error']] ?? 'Error upload kode: ' . $_FILES['foto']['error'];
                $this->session->set_flashdata('error', $err_msg);
                redirect('parameter/landing'); return;
            }

            $this->load->library('upload');
            $this->upload->initialize([
                'upload_path'   => $upload_dir,
                'allowed_types' => 'jpg|jpeg|png',
                'max_size'      => 20480, // 20MB — PHP limit dikontrol oleh php.ini
                'file_name'     => 'pejabat_' . $jenis . '_' . time(),
            ]);
            if (!$this->upload->do_upload('foto')) {
                $this->session->set_flashdata('error',
                    'Upload foto gagal: ' . $this->upload->display_errors('', ''));
                redirect('parameter/landing'); return;
            }
            $up = $this->upload->data();

            // Hapus foto lama jika ada
            $lama = $this->db->get_where('ref_landing_pejabat', ['jenis' => $jenis])->row();
            if ($lama && $lama->foto_path && file_exists(FCPATH . $lama->foto_path)) {
                @unlink(FCPATH . $lama->foto_path);
            }
            $foto_path = 'uploads/landing/pejabat/' . $up['file_name'];
        }

        $data = ['nama' => $nama, 'jabatan' => $jabatan, 'updated_by' => $this->user_id];
        if ($foto_path) $data['foto_path'] = $foto_path;

        $this->db->where('jenis', $jenis)->update('ref_landing_pejabat', $data);
        $this->log_aktivitas('parameter.landing.pejabat', 'Update foto ' . $jenis);
        $this->session->set_flashdata('success', 'Data ' . ucfirst(str_replace('_',' ',$jenis)) . ' berhasil disimpan.');
        redirect('parameter/landing');
    }

    public function landing_pejabat_hapus_foto($jenis)
    {
        $this->requirePerm('parameter.landing.manage');
        $row = $this->db->get_where('ref_landing_pejabat', ['jenis' => $jenis])->row();
        if ($row && $row->foto_path && file_exists(FCPATH . $row->foto_path)) {
            @unlink(FCPATH . $row->foto_path);
        }
        $this->db->where('jenis', $jenis)->update('ref_landing_pejabat', ['foto_path' => NULL]);
        $this->session->set_flashdata('success', 'Foto berhasil dihapus.');
        redirect('parameter/landing');
    }

    // ─── LANDING PAGE — SLIDESHOW ─────────────────────────────

    public function landing_slideshow()
    {
        $this->requirePerm('parameter.landing.view');
        $list = $this->db->where('is_active', 1)
                         ->order_by('urutan', 'ASC')
                         ->get('ref_landing_slideshow')->result();

        $d = $this->_d('Tampilan Landing — Slideshow', 'landing');
        $d['list'] = $list;
        $this->render('parameter/landing_slideshow', $d);
    }

    public function landing_slideshow_tambah()
    {
        $this->requirePerm('parameter.landing.manage');

        $upload_dir = FCPATH . 'uploads/landing/slideshow/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, TRUE);

        // Cek PHP-level error
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_OK && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            $php_errors = [
                UPLOAD_ERR_INI_SIZE  => 'File melebihi batas upload_max_filesize PHP (' . ini_get('upload_max_filesize') . '). Restart server dengan: php -S localhost:8080 -c php.ini router.php',
                UPLOAD_ERR_PARTIAL   => 'File hanya terupload sebagian.',
                UPLOAD_ERR_NO_TMP_DIR=> 'Folder temp tidak ditemukan.',
                UPLOAD_ERR_CANT_WRITE=> 'Gagal menulis file ke disk.',
            ];
            $this->session->set_flashdata('error', $php_errors[$_FILES['foto']['error']] ?? 'Error upload kode: ' . $_FILES['foto']['error']);
            redirect('parameter/landing/slideshow'); return;
        }

        $this->load->library('upload');
        $this->upload->initialize([
            'upload_path'   => $upload_dir,
            'allowed_types' => 'jpg|jpeg|png',
            'max_size'      => 20480, // 20MB
            'file_name'     => 'slide_' . time(),
        ]);

        if (!$this->upload->do_upload('foto')) {
            $this->session->set_flashdata('error',
                'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('parameter/landing/slideshow'); return;
        }

        $up  = $this->upload->data();
        $max = $this->db->select_max('urutan')->get('ref_landing_slideshow')->row();

        $this->db->insert('ref_landing_slideshow', [
            'judul'      => $this->input->post('judul', TRUE),
            'foto_path'  => 'uploads/landing/slideshow/' . $up['file_name'],
            'caption'    => $this->input->post('caption', TRUE),
            'urutan'     => ($max->urutan ?? 0) + 1,
            'created_by' => $this->user_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log_aktivitas('parameter.landing.slideshow', 'Tambah foto slideshow');
        $this->session->set_flashdata('success', 'Foto slideshow berhasil ditambahkan.');
        redirect('parameter/landing/slideshow');
    }

    public function landing_slideshow_hapus($id)
    {
        $this->requirePerm('parameter.landing.manage');
        $row = $this->db->get_where('ref_landing_slideshow', ['id' => $id])->row();
        if (!$row) { show_404(); return; }

        if ($row->foto_path && file_exists(FCPATH . $row->foto_path)) {
            @unlink(FCPATH . $row->foto_path);
        }
        $this->db->delete('ref_landing_slideshow', ['id' => $id]);

        $this->log_aktivitas('parameter.landing.slideshow', 'Hapus foto slideshow id=' . $id);
        $this->session->set_flashdata('success', 'Foto berhasil dihapus.');
        redirect('parameter/landing/slideshow');
    }

    public function landing_slideshow_urutan()
    {
        $this->requirePerm('parameter.landing.manage');
        $ids = $this->input->post('ids');
        if (!is_array($ids)) { $this->json(['ok' => FALSE]); return; }

        foreach ($ids as $urutan => $id) {
            $this->db->where('id', (int)$id)
                     ->update('ref_landing_slideshow', ['urutan' => (int)$urutan]);
        }
        $this->json(['ok' => TRUE]);
    }
}
