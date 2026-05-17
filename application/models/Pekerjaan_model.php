<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pekerjaan_model — Sprint 2
 * Mengelola trx_pekerjaan, trx_tahapan_penyaluran, trx_dokumen_persyaratan,
 * trx_pekerjaan_log, trx_status_history
 */
class Pekerjaan_model extends CI_Model
{
    // ─── READ ─────────────────────────────────────────────────

    public function get_all($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('p.*, b.kode_bkp, b.uraian_bkp, b.nilai as nilai_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      u.nama as nama_opd');
        $this->_filter_pekerjaan($filters);
        $this->db->order_by('b.kode_bkp', 'ASC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->_filter_pekerjaan($filters);
        return $this->db->count_all_results();
    }

    private function _filter_pekerjaan($filters)
    {
        $this->db->from('trx_pekerjaan p')
            ->join('ref_bkp b',      'b.id = p.bkp_id')
            ->join('ref_kabkota k',  'k.id = b.kabkota_id')
            ->join('ref_bidang bid', 'bid.id = b.bidang_id')
            ->join('users u',        'u.id = p.created_by', 'left');
        if (!empty($filters['tahun']))
            $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['status']))
            $this->db->where('p.status', $filters['status']);
        if (!empty($filters['jenis_penyaluran']))
            $this->db->where('p.jenis_penyaluran', $filters['jenis_penyaluran']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('b.uraian_bkp', $filters['q'])
                ->or_like('p.nama_kegiatan_dok', $filters['q'])
                ->group_end();
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('p.*, b.kode_bkp, b.uraian_bkp, b.nilai as nilai_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      u.nama as nama_opd, u.opd_nama,
                      perda.nomor as no_perda, perda.tanggal as tgl_perda,
                      perkada.nomor as no_perkada, perkada.tanggal as tgl_perkada')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b',           'b.id = p.bkp_id')
            ->join('ref_kabkota k',       'k.id = b.kabkota_id')
            ->join('ref_bidang bid',      'bid.id = b.bidang_id')
            ->join('users u',             'u.id = p.created_by', 'left')
            ->join('ref_pemda_dokumen perda',   'perda.id = p.ref_perda_id',   'left')
            ->join('ref_pemda_dokumen perkada', 'perkada.id = p.ref_perkada_id','left')
            ->where('p.id', $id)
            ->get()->row();
    }

    public function get_by_bkp($bkp_id)
    {
        return $this->db->get_where('trx_pekerjaan', ['bkp_id' => $bkp_id])->row();
    }

    public function bkp_sudah_ada($bkp_id, $exclude_id = NULL)
    {
        $this->db->where('bkp_id', $bkp_id);
        if ($exclude_id) $this->db->where('id !=', $exclude_id);
        return $this->db->count_all_results('trx_pekerjaan') > 0;
    }

    // ─── WRITE ────────────────────────────────────────────────

    public function insert($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('trx_pekerjaan', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data, $user_id = NULL)
    {
        // Log field yang berubah
        if ($user_id) {
            $lama = $this->db->get_where('trx_pekerjaan', ['id' => $id])->row();
            $monitored = ['nama_kegiatan_dok','nilai_kontrak','jenis_penyaluran',
                          'no_dok_pekerjaan','tgl_dok_pekerjaan','no_spmk','tgl_spmk'];
            foreach ($monitored as $f) {
                if (isset($data[$f]) && isset($lama->$f) && $lama->$f != $data[$f]) {
                    $this->db->insert('trx_pekerjaan_log', [
                        'pekerjaan_id' => $id,
                        'field_ubah'   => $f,
                        'nilai_lama'   => $lama->$f,
                        'nilai_baru'   => $data[$f],
                        'user_id'      => $user_id,
                        'ip_address'   => $this->input->ip_address(),
                        'created_at'   => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
        $data['updated_by'] = $user_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('trx_pekerjaan', $data);
    }

    public function set_status($id, $status_baru, $user_id, $catatan = '')
    {
        $lama = $this->db->get_where('trx_pekerjaan', ['id' => $id])->row();
        if (!$lama) return FALSE;

        $this->db->where('id', $id)->update('trx_pekerjaan', [
            'status'     => $status_baru,
            'updated_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Audit trail
        $this->db->insert('trx_status_history', [
            'pekerjaan_id' => $id,
            'status_lama'  => $lama->status,
            'status_baru'  => $status_baru,
            'catatan'      => $catatan,
            'user_id'      => $user_id,
            'created_at'   => date('Y-m-d H:i:s'),
        ]);
        return TRUE;
    }

    // ─── TAHAPAN ──────────────────────────────────────────────

    public function get_tahapan($pekerjaan_id)
    {
        return $this->db
            ->select('t.*, bw.batas_pengajuan, bw.batas_penyaluran, bw.label as bw_label')
            ->from('trx_tahapan_penyaluran t')
            ->join('ref_batas_waktu bw', 'bw.id = t.batas_waktu_id', 'left')
            ->where('t.pekerjaan_id', $pekerjaan_id)
            ->order_by('t.urutan', 'ASC')
            ->get()->result();
    }

    public function get_tahapan_by_id($id)
    {
        return $this->db
            ->select('t.*, bw.batas_pengajuan, bw.batas_penyaluran')
            ->from('trx_tahapan_penyaluran t')
            ->join('ref_batas_waktu bw', 'bw.id = t.batas_waktu_id', 'left')
            ->where('t.id', $id)->get()->row();
    }

    /**
     * Buat record tahapan sesuai jenis penyaluran
     * Dipanggil saat pekerjaan pertama kali di-submit
     */
    public function buat_tahapan($pekerjaan_id, $jenis_penyaluran, $nilai_kontrak, $tahun, $user_id)
    {
        $this->db->delete('trx_tahapan_penyaluran', ['pekerjaan_id' => $pekerjaan_id]);

        $def = $this->_definisi_tahapan($jenis_penyaluran);
        foreach ($def as $i => $t) {
            // Ambil batas waktu dari ref
            $bw = $this->db->get_where('ref_batas_waktu', [
                'tahun'            => $tahun,
                'jenis_penyaluran' => $jenis_penyaluran,
                'kode_tahap'       => $t['kode_tahap'],
                'is_active'        => 1,
            ])->row();

            $nilai = (int)($nilai_kontrak * $t['persen'] / 100);

            $this->db->insert('trx_tahapan_penyaluran', [
                'pekerjaan_id'      => $pekerjaan_id,
                'batas_waktu_id'    => $bw ? $bw->id : NULL,
                'kode_tahap'        => $t['kode_tahap'],
                'urutan'            => $i + 1,
                'label_tahap'       => $t['label'],
                'persen_nilai'      => $t['persen'],
                'nilai_diajukan'    => $nilai,
                'persen_fisik_syarat'=> $t['persen_fisik_syarat'] ?? NULL,
                'batas_tgl_pengajuan'=> $bw ? $bw->batas_pengajuan : date('Y-12-31'),
                'status'            => 'belum',
                'user_input'        => $user_id,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function _definisi_tahapan($jenis)
    {
        $map = [
            'bertahap' => [
                ['kode_tahap'=>'tahap_1', 'label'=>'Tahap I (50%)',  'persen'=>50, 'persen_fisik_syarat'=>0],
                ['kode_tahap'=>'tahap_2', 'label'=>'Tahap II (50%)', 'persen'=>50, 'persen_fisik_syarat'=>50],
            ],
            'sekaligus' => [
                ['kode_tahap'=>'sekaligus','label'=>'Sekaligus (100%)','persen'=>100,'persen_fisik_syarat'=>NULL],
            ],
            'khusus_mendesak' => [
                ['kode_tahap'=>'khusus','label'=>'Kondisi Mendesak (100%)','persen'=>100,'persen_fisik_syarat'=>NULL],
            ],
            'khusus_bencana' => [
                ['kode_tahap'=>'khusus','label'=>'Darurat Bencana (100%)','persen'=>100,'persen_fisik_syarat'=>NULL],
            ],
        ];
        return $map[$jenis] ?? [];
    }

    public function set_status_tahapan($tahapan_id, $status, $user_id)
    {
        return $this->db->where('id', $tahapan_id)->update('trx_tahapan_penyaluran', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ─── DOKUMEN ──────────────────────────────────────────────

    public function get_dokumen($tahapan_id)
    {
        return $this->db
            ->where('tahapan_id', $tahapan_id)
            ->order_by('created_at', 'ASC')
            ->get('trx_dokumen_persyaratan')->result();
    }

    public function get_semua_dokumen_pekerjaan($pekerjaan_id)
    {
        return $this->db
            ->select('d.*, t.label_tahap, t.kode_tahap')
            ->from('trx_dokumen_persyaratan d')
            ->join('trx_tahapan_penyaluran t', 't.id = d.tahapan_id')
            ->where('t.pekerjaan_id', $pekerjaan_id)
            ->order_by('d.created_at', 'DESC')
            ->get()->result();
    }

    public function get_dokumen_by_id($id)
    {
        return $this->db->get_where('trx_dokumen_persyaratan', ['id' => $id])->row();
    }

    public function insert_dokumen($data)
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('trx_dokumen_persyaratan', $data);
        return $this->db->insert_id();
    }

    public function hapus_dokumen($id)
    {
        $dok = $this->get_dokumen_by_id($id);
        if ($dok && file_exists(FCPATH . $dok->file_path)) {
            unlink(FCPATH . $dok->file_path);
        }
        return $this->db->delete('trx_dokumen_persyaratan', ['id' => $id]);
    }

    // ─── STATUS HISTORY ───────────────────────────────────────

    public function get_status_history($pekerjaan_id)
    {
        return $this->db
            ->select('h.*, u.nama as nama_user, u.role_id,
                      r.nama as nama_role')
            ->from('trx_status_history h')
            ->join('users u', 'u.id = h.user_id', 'left')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->where('h.pekerjaan_id', $pekerjaan_id)
            ->order_by('h.created_at', 'DESC')
            ->get()->result();
    }

    // ─── STATISTIK ────────────────────────────────────────────

    public function count_by_status($tahun, $kabkota_id = NULL)
    {
        $this->db
            ->select('p.status, COUNT(*) as total')
            ->from('trx_pekerjaan p')
            ->join('ref_bkp b', 'b.id = p.bkp_id')
            ->where('b.tahun', $tahun);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        $rows = $this->db->group_by('p.status')->get()->result();
        $map = [];
        foreach ($rows as $r) $map[$r->status] = (int)$r->total;
        return $map;
    }
}
