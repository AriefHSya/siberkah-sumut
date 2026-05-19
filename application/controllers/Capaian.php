<?php
/**
 * Capaian.php — Controller Input Capaian Output Fisik
 *
 * Menangani input realisasi capaian output fisik pekerjaan setelah
 * Tahap I dikonfirmasi. Diperlukan sebelum pengajuan Tahap II (jenis bertahap).
 *
 * ALUR:
 *   Dana Tahap I dikonfirmasi (status 'dikonfirmasi')
 *   → OPD Teknis input capaian (% fisik, volume, keterangan)
 *   → Notifikasi ke SKPKD Kab bahwa capaian sudah diisi
 *
 * ROUTES:
 *   GET  /capaian                   → index()        — daftar pekerjaan perlu input capaian
 *   GET  /capaian/form/{pekerjaan_id} → form()       — form input capaian
 *   POST /capaian/simpan/{id}       → simpan()       — proses simpan capaian
 *
 * DATA MODEL: trx_capaian_output (kolom: persen_fisik, volume_realisasi, keterangan)
 * AKSES: opd_teknis (input), skpkd_kabkota dan provinsi (view)
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Capaian extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('capaian.view');
        $this->load->model(['Capaian_model', 'Pekerjaan_model', 'Parameter_model', 'Notifikasi_model']);
        $this->data['active_menu'] = 'capaian';
    }

    // ─── INDEX: Daftar pekerjaan perlu input capaian ──────────

    public function index()
    {
        $tahun    = $this->tahun;
        $per_page = 20;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tahun'      => $tahun,
            'kabkota_id' => $this->input->get('kabkota_id'),
            'status'     => $this->input->get('status'),
            'q'          => $this->input->get('q'),
        ];

        // OPD hanya lihat kabkota sendiri
        if ($this->role_kode === 'opd_teknis' && $this->kabkota_id) {
            $filters['kabkota_id'] = $this->kabkota_id;
        }

        $total        = $this->Capaian_model->count_filtered($filters);
        $list         = $this->Capaian_model->get_list($filters, $per_page, ($page - 1) * $per_page);
        $kabkota_list = $this->rbac->isProvinsi() ? $this->Parameter_model->get_kabkota() : [];

        $this->render('capaian/index', array_merge($this->data, [
            'title'        => 'Capaian Output Fisik — SIBERKAH SUMUT',
            'list'         => $list,
            'filters'      => $filters,
            'kabkota_list' => $kabkota_list,
            'tahun'        => $tahun,
            'paging'       => ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'capaian'],
        ]));
    }

    // ─── FORM: Input / Edit Capaian ──────────────────────────

    public function form($pekerjaan_id)
    {
        $this->requirePerm('capaian.input');

        $detail = $this->Capaian_model->get_detail($pekerjaan_id);
        if (!$detail) { show_404(); return; }

        // Guard: OPD hanya bisa input capaian miliknya
        if ($this->role_kode === 'opd_teknis'
            && $detail->created_by != $this->user_id
            && $detail->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('capaian'); return;
        }

        // Guard status: hanya pekerjaan dengan status tepat
        if (!in_array($detail->status, ['dikonfirmasi_tahap1', 'opd_capaian_tahap1'])) {
            $this->session->set_flashdata('error',
                'Capaian output hanya dapat diinput setelah dana Tahap I dikonfirmasi diterima.');
            redirect('capaian'); return;
        }

        $this->render('capaian/form', array_merge($this->data, [
            'title'  => 'Input Capaian Output — ' . $detail->kode_bkp,
            'detail' => $detail,
        ]));
    }

    // ─── SIMPAN: Proses POST ──────────────────────────────────

    public function simpan($pekerjaan_id)
    {
        $this->requirePerm('capaian.input');

        $detail = $this->Capaian_model->get_detail($pekerjaan_id);
        if (!$detail) { show_404(); return; }

        if ($this->role_kode === 'opd_teknis' && $detail->kabkota_id != $this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('capaian'); return;
        }

        if (!in_array($detail->status, ['dikonfirmasi_tahap1', 'opd_capaian_tahap1'])) {
            $this->session->set_flashdata('error', 'Status pekerjaan tidak memungkinkan input capaian.');
            redirect('capaian'); return;
        }

        $persen = (float)$this->input->post('persen_fisik');
        if ($persen < 0 || $persen > 100) {
            $this->session->set_flashdata('error', 'Persentase capaian fisik harus antara 0–100.');
            redirect('capaian/form/' . $pekerjaan_id); return;
        }

        // Handle upload foto dokumentasi
        $foto_path = $detail->foto_path ?? NULL;
        if (!empty($_FILES['foto_dokumentasi']['name'])) {
            $dir = FCPATH . 'uploads/capaian/' . $pekerjaan_id . '/';
            if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

            $this->load->library('upload', [
                'upload_path'   => $dir,
                'allowed_types' => 'jpg|jpeg|png|pdf',
                'max_size'      => 5120,
                'file_name'     => 'capaian_' . $pekerjaan_id . '_' . time(),
            ]);

            if (!$this->upload->do_upload('foto_dokumentasi')) {
                $this->session->set_flashdata('error',
                    'Upload foto gagal: ' . $this->upload->display_errors('', ''));
                redirect('capaian/form/' . $pekerjaan_id); return;
            }
            $up        = $this->upload->data();
            $foto_path = 'uploads/capaian/' . $pekerjaan_id . '/' . $up['file_name'];
        }

        $data_capaian = [
            'persen_fisik'      => $persen,
            'tgl_realisasi'     => $this->input->post('tgl_realisasi') ?: NULL,
            'no_ba_kemajuan'    => $this->input->post('no_ba_kemajuan', TRUE),
            'tgl_ba_kemajuan'   => $this->input->post('tgl_ba_kemajuan') ?: NULL,
            'keterangan'        => $this->input->post('keterangan', TRUE),
            'foto_path'         => $foto_path,
        ];

        $this->Capaian_model->simpan($detail->tahapan_id, $data_capaian, $this->user_id);

        // Update status pekerjaan → opd_capaian_tahap1
        if ($detail->status === 'dikonfirmasi_tahap1') {
            $this->Pekerjaan_model->set_status(
                $pekerjaan_id,
                'opd_capaian_tahap1',
                $this->user_id,
                'OPD menginput capaian output fisik Tahap I. Persentase: ' . $persen . '%'
            );

            // Notif ke Admin Provinsi + SKPKD Kab
            $notif_targets = $this->db->select('u.id')
                ->from('users u')->join('roles r', 'r.id = u.role_id')
                ->where('u.kabkota_id', $detail->kabkota_id)
                ->where_in('r.kode', ['skpkd_kabkota'])
                ->where('u.is_active', 1)->get()->result();

            $admin_prov = $this->db->select('u.id')
                ->from('users u')->join('roles r', 'r.id = u.role_id')
                ->where_in('r.kode', ['superadmin', 'admin_provinsi'])
                ->where('u.is_active', 1)->get()->result();

            $all_notif = array_merge($notif_targets, $admin_prov);
            foreach ($all_notif as $u) {
                $this->Notifikasi_model->kirim(
                    $u->id,
                    'Capaian Output Tahap I Diinput',
                    'OPD ' . $detail->kabkota_nama . ' telah menginput capaian fisik ' .
                    $persen . '% untuk ' . $detail->kode_bkp . '.',
                    'info',
                    site_url('capaian/form/' . $pekerjaan_id),
                    $pekerjaan_id
                );
            }
        }

        $this->log_aktivitas('capaian.input',
            'Input capaian pekerjaan_id=' . $pekerjaan_id . ' persen=' . $persen . '%');
        $this->session->set_flashdata('success',
            'Capaian output berhasil disimpan. Persentase fisik: <strong>' . $persen . '%</strong>');
        redirect('capaian/form/' . $pekerjaan_id);
    }
}
