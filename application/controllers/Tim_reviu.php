<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tim_reviu.php — Manajemen Tim Review & Surat Tugas
 *
 * Dikelola oleh Inspektorat Kab/Kota. Tim yang dibuat dapat dipilih
 * saat proses reviu sehingga tidak perlu input ulang per pekerjaan.
 *
 * ROUTES:
 *   GET  /tim-reviu              → index()       — daftar tim
 *   GET  /tim-reviu/tambah       → tambah()      — form tim baru
 *   POST /tim-reviu/simpan       → simpan()      — proses simpan
 *   GET  /tim-reviu/{id}         → detail($id)   — detail + anggota
 *   GET  /tim-reviu/edit/{id}    → edit($id)     — form edit
 *   POST /tim-reviu/update/{id}  → update($id)   — proses update
 *   POST /tim-reviu/hapus/{id}   → hapus($id)    — hapus (jika belum dipakai)
 */
class Tim_reviu extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('tim_reviu.view');
        $this->load->model('Tim_reviu_model');
        $this->load->model('Parameter_model');
        $this->data['active_menu'] = 'tim_reviu';
    }

    public function index()
    {
        $kabkota_id  = $this->kabkota_id ?: NULL;
        $is_provinsi = $this->rbac->isProvinsi() || $this->role_kode === 'pengawas';

        $list = $this->Tim_reviu_model->get_all($kabkota_id, $this->tahun);

        $this->render('tim_reviu/index', array_merge($this->data, [
            'title'       => 'Tim Review — SIBERKAH SUMUT',
            'list'        => $list,
            'tahun'       => $this->tahun,
            'is_provinsi' => $is_provinsi,
        ]));
    }

    public function tambah()
    {
        $this->requirePerm('tim_reviu.manage');
        if (!$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Pembuatan Tim Review hanya dapat dilakukan oleh Inspektorat Kab/Kota.');
            redirect('tim-reviu'); return;
        }

        $this->render('tim_reviu/form', array_merge($this->data, [
            'title' => 'Tambah Tim Review',
            'edit'  => FALSE,
            'tim'   => NULL,
            'anggota' => [],
        ]));
    }

    public function simpan()
    {
        $this->requirePerm('tim_reviu.manage');
        if (!$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Pembuatan Tim Review hanya dapat dilakukan oleh Inspektorat Kab/Kota.');
            redirect('tim-reviu'); return;
        }

        $no_sk  = trim($this->input->post('no_sk', TRUE));
        $tgl_sk = $this->input->post('tgl_sk', TRUE);

        if (empty($no_sk) || empty($tgl_sk)) {
            $this->session->set_flashdata('error', 'No. SK dan tanggal wajib diisi.');
            redirect('tim-reviu/tambah'); return;
        }

        // Validasi anggota — minimal 1
        $nama_arr    = $this->input->post('nama')    ?? [];
        $nip_arr     = $this->input->post('nip')     ?? [];
        $jabatan_arr = $this->input->post('jabatan') ?? [];

        $anggota_valid = [];
        foreach ($nama_arr as $i => $nama) {
            $nama = trim($nama);
            if (empty($nama)) continue;
            if (count($anggota_valid) >= 7) break;
            $anggota_valid[] = [
                'urutan'  => count($anggota_valid) + 1,
                'nama'    => $nama,
                'nip'     => trim($nip_arr[$i] ?? ''),
                'jabatan' => trim($jabatan_arr[$i] ?? ''),
            ];
        }

        if (empty($anggota_valid)) {
            $this->session->set_flashdata('error', 'Minimal 1 anggota tim wajib diisi.');
            redirect('tim-reviu/tambah'); return;
        }

        // Upload SK jika ada
        $file_sk      = NULL;
        $nama_asli_sk = NULL;
        if (!empty($_FILES['file_sk']['name'])) {
            $result = $this->_upload_sk();
            if (!$result['ok']) {
                $this->session->set_flashdata('error', $result['pesan']);
                redirect('tim-reviu/tambah'); return;
            }
            $file_sk      = $result['path'];
            $nama_asli_sk = $result['nama_asli'];
        }

        $tim_id = $this->Tim_reviu_model->insert([
            'kabkota_id'   => $this->kabkota_id,
            'tahun'        => $this->tahun,
            'no_sk'        => $no_sk,
            'tgl_sk'       => $tgl_sk,
            'file_sk'      => $file_sk,
            'nama_asli_sk' => $nama_asli_sk,
            'keterangan'   => trim($this->input->post('keterangan', TRUE)),
            'created_by'   => $this->user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        foreach ($anggota_valid as &$row) { $row['tim_id'] = $tim_id; }
        $this->Tim_reviu_model->insert_anggota_batch($anggota_valid);

        $this->log_aktivitas('tim_reviu.tambah', 'Tambah tim review id='.$tim_id.' no_sk='.$no_sk);
        $this->session->set_flashdata('success', 'Tim review berhasil disimpan.');
        redirect('tim-reviu/' . $tim_id);
    }

    public function detail($id)
    {
        $tim = $this->Tim_reviu_model->get_by_id($id);
        if (!$tim) { show_404(); return; }

        // Guard kabkota
        if ($this->kabkota_id && (int)$tim->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('tim-reviu'); return;
        }

        $anggota  = $this->Tim_reviu_model->get_anggota($id);
        $is_used  = $this->Tim_reviu_model->is_used($id);

        $this->render('tim_reviu/detail', array_merge($this->data, [
            'title'   => 'Detail Tim Review — ' . $tim->no_sk,
            'tim'     => $tim,
            'anggota' => $anggota,
            'is_used' => $is_used,
        ]));
    }

    public function edit($id)
    {
        $this->requirePerm('tim_reviu.manage');

        $tim = $this->Tim_reviu_model->get_by_id($id);
        if (!$tim) { show_404(); return; }

        if ($this->kabkota_id && (int)$tim->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('tim-reviu'); return;
        }

        $anggota = $this->Tim_reviu_model->get_anggota($id);

        $this->render('tim_reviu/form', array_merge($this->data, [
            'title'   => 'Edit Tim Review',
            'edit'    => TRUE,
            'tim'     => $tim,
            'anggota' => $anggota,
        ]));
    }

    public function update($id)
    {
        $this->requirePerm('tim_reviu.manage');

        $tim = $this->Tim_reviu_model->get_by_id($id);
        if (!$tim) { show_404(); return; }

        if ($this->kabkota_id && (int)$tim->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('tim-reviu'); return;
        }

        $no_sk  = trim($this->input->post('no_sk', TRUE));
        $tgl_sk = $this->input->post('tgl_sk', TRUE);

        if (empty($no_sk) || empty($tgl_sk)) {
            $this->session->set_flashdata('error', 'No. SK dan tanggal wajib diisi.');
            redirect('tim-reviu/edit/' . $id); return;
        }

        $nama_arr    = $this->input->post('nama')    ?? [];
        $nip_arr     = $this->input->post('nip')     ?? [];
        $jabatan_arr = $this->input->post('jabatan') ?? [];

        $anggota_valid = [];
        foreach ($nama_arr as $i => $nama) {
            $nama = trim($nama);
            if (empty($nama)) continue;
            if (count($anggota_valid) >= 7) break;
            $anggota_valid[] = [
                'tim_id'  => $id,
                'urutan'  => count($anggota_valid) + 1,
                'nama'    => $nama,
                'nip'     => trim($nip_arr[$i] ?? ''),
                'jabatan' => trim($jabatan_arr[$i] ?? ''),
            ];
        }

        if (empty($anggota_valid)) {
            $this->session->set_flashdata('error', 'Minimal 1 anggota tim wajib diisi.');
            redirect('tim-reviu/edit/' . $id); return;
        }

        // Upload SK baru jika ada
        $upd = [
            'no_sk'      => $no_sk,
            'tgl_sk'     => $tgl_sk,
            'keterangan' => trim($this->input->post('keterangan', TRUE)),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($_FILES['file_sk']['name'])) {
            $result = $this->_upload_sk();
            if (!$result['ok']) {
                $this->session->set_flashdata('error', $result['pesan']);
                redirect('tim-reviu/edit/' . $id); return;
            }
            $upd['file_sk']      = $result['path'];
            $upd['nama_asli_sk'] = $result['nama_asli'];
        }

        $this->Tim_reviu_model->update($id, $upd);
        $this->Tim_reviu_model->delete_anggota($id);
        $this->Tim_reviu_model->insert_anggota_batch($anggota_valid);

        $this->log_aktivitas('tim_reviu.update', 'Edit tim review id='.$id);
        $this->session->set_flashdata('success', 'Tim review berhasil diperbarui.');
        redirect('tim-reviu/' . $id);
    }

    public function hapus($id)
    {
        $this->requirePerm('tim_reviu.manage');

        $tim = $this->Tim_reviu_model->get_by_id($id);
        if (!$tim) { show_404(); return; }

        if ($this->kabkota_id && (int)$tim->kabkota_id !== (int)$this->kabkota_id) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('tim-reviu'); return;
        }

        if ($this->Tim_reviu_model->is_used($id)) {
            $this->session->set_flashdata('error',
                'Tim ini tidak dapat dihapus karena sudah digunakan pada proses reviu.');
            redirect('tim-reviu/' . $id); return;
        }

        $this->Tim_reviu_model->delete_anggota($id);
        $this->Tim_reviu_model->hapus($id);
        $this->log_aktivitas('tim_reviu.hapus', 'Hapus tim review id='.$id.' no_sk='.$tim->no_sk);
        $this->session->set_flashdata('success', 'Tim review berhasil dihapus.');
        redirect('tim-reviu');
    }

    // ─── HELPER UPLOAD SK ────────────────────────────────────────

    private function _upload_sk()
    {
        $mime_ok = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        if (!$this->_mime_valid($_FILES['file_sk']['tmp_name'], $mime_ok)) {
            return ['ok' => FALSE, 'pesan' => 'File SK harus berformat PDF, DOC, atau DOCX.'];
        }

        $dir = FCPATH . 'uploads/lhr/sk/';
        if (!is_dir($dir)) mkdir($dir, 0755, TRUE);

        $orig = basename($_FILES['file_sk']['name']);
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $nama = $this->_random_filename($ext);

        $this->load->library('upload', [
            'upload_path'   => $dir,
            'allowed_types' => 'pdf|doc|docx',
            'max_size'      => 10240,
            'file_name'     => $nama,
        ]);

        if (!$this->upload->do_upload('file_sk')) {
            return ['ok' => FALSE, 'pesan' => 'Upload SK gagal: ' . $this->upload->display_errors('', '')];
        }

        $up = $this->upload->data();
        return [
            'ok'        => TRUE,
            'path'      => 'uploads/lhr/sk/' . $up['file_name'],
            'nama_asli' => $orig,
        ];
    }
}
