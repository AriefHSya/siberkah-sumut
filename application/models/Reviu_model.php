<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reviu_model.php — Model Reviu Inspektorat
 *
 * Akses data reviu oleh Inspektorat Kab/Kota.
 * Setiap tahapan penyaluran memiliki satu record reviu (UNIQUE per tahapan).
 *
 * TABEL UTAMA:
 *   trx_reviu_inspektorat  — record reviu per tahapan (1:1)
 *   trx_checklist_reviu    — isian 21-item checklist per reviu
 *   ref_checklist_item     — definisi item checklist (statis: CK-01 s/d CK-21)
 *
 * CHECKLIST:
 *   21 item statis di ref_checklist_item — berbeda per jenis_penyaluran dan tahap.
 *   Method get_checklist_items($jenis, $tahap) → filter item yang relevan.
 *   Method hitung_checklist($reviu_id) → hitung % kelengkapan (ya/tidak/na).
 *
 * POLA UPSERT:
 *   buat_atau_ambil($tahapan_id) — jika belum ada record reviu untuk tahapan ini,
 *   buat record baru. Jika sudah ada, kembalikan yang existing.
 *   Ini mencegah duplikat reviu untuk tahapan yang sama.
 *
 * METHOD UTAMA:
 *   get_antrian($filters)              — daftar tahapan menunggu reviu
 *   count_filtered($filters)           — hitung untuk pagination
 *   get_checklist_items($jenis, $tahap)— item checklist yang relevan
 *   get_isian($reviu_id)               — isian checklist per reviu
 *   simpan_checklist($reviu_id, $data) — bulk insert/update checklist
 *   hitung_checklist($reviu_id)        — skor kelengkapan
 *   update_lhr($reviu_id, $path)       — simpan path file LHR
 */
class Reviu_model extends CI_Model
{
    // ─── ANTRIAN REVIU ────────────────────────────────────────

    /**
     * Ambil daftar pekerjaan yang perlu direviu oleh Inspektorat
     * Filter per kabkota + status opd_input/inspektorat_reviu/revisi
     */
    public function get_antrian($filters = [], $limit = 0, $offset = 0)
    {
        $this->db->select('t.*, p.id as pekerjaan_id, p.jenis_penyaluran, p.nama_kegiatan_dok,
                      p.nilai_kontrak, p.status as status_pekerjaan,
                      b.kode_bkp, b.uraian_bkp, b.tahun,
                      k.nama as nama_kabkota, k.id as kabkota_id,
                      bid.nama as nama_bidang,
                      r.id as reviu_id, r.hasil_reviu, r.no_lhr');
        $this->_filter_reviu($filters);
        $this->db->order_by('t.tgl_pengajuan', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_filtered($filters = [])
    {
        $this->_filter_reviu($filters);
        return $this->db->count_all_results();
    }

    private function _filter_reviu($filters)
    {
        $this->db->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p',         'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',               'b.id = p.bkp_id')
            ->join('ref_kabkota k',           'k.id = b.kabkota_id')
            ->join('ref_bidang bid',          'bid.id = b.bidang_id')
            ->join('trx_reviu_inspektorat r', 'r.tahapan_id = t.id', 'left')
            ->group_start()
                ->where_in('t.status', ['opd_input','inspektorat_reviu','inspektorat_revisi','inspektorat_approved'])
                ->or_where('r.hasil_reviu', 'disetujui')
            ->group_end();
        if (!empty($filters['kabkota_id']))
            $this->db->where('b.kabkota_id', $filters['kabkota_id']);
        if (!empty($filters['tahun']))
            $this->db->where('b.tahun', $filters['tahun']);
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'inspektorat_approved') {
                $this->db->where('r.hasil_reviu', 'disetujui');
            } else {
                $this->db->where('t.status', $filters['status']);
            }
        }
        if (!empty($filters['jenis']))
            $this->db->where('p.jenis_penyaluran', $filters['jenis']);
        if (!empty($filters['q']))
            $this->db->group_start()
                ->like('b.kode_bkp', $filters['q'])
                ->or_like('p.nama_kegiatan_dok', $filters['q'])
                ->group_end();
    }

    // ─── REVIU RECORD ─────────────────────────────────────────

    public function get_by_tahapan($tahapan_id)
    {
        return $this->db
            ->select('r.*, pj.nama as nama_inspektur, pj.nip as nip_inspektur, pj.pangkat')
            ->from('trx_reviu_inspektorat r')
            ->join('ref_pemda_pejabat pj', 'pj.id = r.ref_inspektur_id', 'left')
            ->where('r.tahapan_id', $tahapan_id)
            ->get()->row();
    }

    public function get_by_id($id)
    {
        return $this->db
            ->select('r.*, pj.nama as nama_inspektur, pj.nip as nip_inspektur')
            ->from('trx_reviu_inspektorat r')
            ->join('ref_pemda_pejabat pj', 'pj.id = r.ref_inspektur_id', 'left')
            ->where('r.id', $id)->get()->row();
    }

    /**
     * Buat atau ambil record reviu untuk tahapan
     * Dipanggil saat Inspektorat pertama kali membuka halaman reviu
     */
    public function buat_atau_ambil($tahapan_id, $user_id)
    {
        $ada = $this->db->get_where('trx_reviu_inspektorat', ['tahapan_id' => $tahapan_id])->row();
        if ($ada) return $ada->id;

        $this->db->insert('trx_reviu_inspektorat', [
            'tahapan_id'       => $tahapan_id,
            'tgl_reviu_mulai'  => date('Y-m-d'),
            'user_inspektorat' => $user_id,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->where('id', $id)->update('trx_reviu_inspektorat', $data);
    }

    // ─── CHECKLIST ────────────────────────────────────────────

    /**
     * Ambil item checklist sesuai jenis penyaluran + kode tahap
     * Logika:
     *   - NULL (semua jenis)    → selalu masuk
     *   - 'bertahap'            → masuk jika jenis=bertahap
     *   - 'bertahap_tahap2'     → masuk jika jenis=bertahap AND kode_tahap=tahap_2
     *   - 'sekaligus'           → masuk jika jenis=sekaligus
     */
    public function get_checklist_items($jenis_penyaluran, $kode_tahap)
    {
        // Tahap II hanya menggunakan item khusus bertahap_tahap2 (CK-17 s/d CK-22)
        // CK-01..CK-16 tidak diulang karena sudah diverifikasi di Tahap I
        if ($jenis_penyaluran === 'bertahap' && $kode_tahap === 'tahap_2') {
            return $this->db
                ->where('is_active', 1)
                ->where('jenis_penyaluran', 'bertahap_tahap2')
                ->order_by('urutan', 'ASC')
                ->get('ref_checklist_item')->result();
        }

        $this->db->where('is_active', 1);

        // Bangun filter jenis yang berlaku
        $valid_jenis = [NULL]; // selalu masuk
        if ($jenis_penyaluran === 'bertahap') {
            $valid_jenis[] = 'bertahap';
        } elseif ($jenis_penyaluran === 'sekaligus') {
            $valid_jenis[] = 'sekaligus';
        }
        // khusus_mendesak dan khusus_bencana hanya pakai yang NULL (umum)

        $this->db->group_start();
        $this->db->where('jenis_penyaluran IS NULL', NULL, FALSE);
        foreach (array_filter($valid_jenis) as $j) {
            $this->db->or_where('jenis_penyaluran', $j);
        }
        $this->db->group_end();

        return $this->db->order_by('urutan', 'ASC')->get('ref_checklist_item')->result();
    }

    /**
     * Ambil isian checklist yang sudah ada untuk reviu ini
     * Return array: [checklist_item_id => row]
     */
    public function get_isian($reviu_id)
    {
        $rows = $this->db
            ->select('c.*, ci.kode, ci.uraian_item')
            ->from('trx_checklist_reviu c')
            ->join('ref_checklist_item ci', 'ci.id = c.checklist_item_id')
            ->where('c.reviu_id', $reviu_id)
            ->get()->result();
        $map = [];
        foreach ($rows as $r) $map[$r->checklist_item_id] = $r;
        return $map;
    }

    /**
     * Simpan batch isian checklist (upsert)
     */
    public function simpan_checklist($reviu_id, array $isian)
    {
        // $isian = ['item_id' => ['nilai'=>'sesuai','catatan'=>'...']]
        foreach ($isian as $item_id => $data) {
            $exists = $this->db->get_where('trx_checklist_reviu', [
                'reviu_id'          => $reviu_id,
                'checklist_item_id' => $item_id,
            ])->row();

            $row = [
                'nilai'   => in_array($data['nilai'], ['sesuai','tidak_sesuai','tidak_berlaku'])
                             ? $data['nilai'] : 'tidak_berlaku',
                'catatan' => substr($data['catatan'] ?? '', 0, 500),
            ];

            if ($exists) {
                $this->db->where(['reviu_id'=>$reviu_id,'checklist_item_id'=>$item_id])
                         ->update('trx_checklist_reviu', $row);
            } else {
                $this->db->insert('trx_checklist_reviu', array_merge($row, [
                    'reviu_id'          => $reviu_id,
                    'checklist_item_id' => $item_id,
                    'created_at'        => date('Y-m-d H:i:s'),
                ]));
            }
        }
    }

    /**
     * Hitung statistik checklist: sesuai, tidak_sesuai, tidak_berlaku
     */
    public function hitung_checklist($reviu_id)
    {
        $rows = $this->db->select('nilai, COUNT(*) as total')
            ->where('reviu_id', $reviu_id)
            ->group_by('nilai')
            ->get('trx_checklist_reviu')->result();
        $stat = ['sesuai' => 0, 'tidak_sesuai' => 0, 'tidak_berlaku' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $stat[$r->nilai] = (int)$r->total;
            $stat['total']  += (int)$r->total;
        }
        return $stat;
    }

    // ─── UPLOAD LHR ───────────────────────────────────────────

    public function update_lhr($reviu_id, $no_lhr, $tgl_lhr, $file_path, $ref_inspektur_id, $nama_lhr_asli = NULL)
    {
        $data = [
            'no_lhr'           => $no_lhr,
            'tgl_lhr'          => $tgl_lhr,
            'file_lhr_path'    => $file_path,
            'ref_inspektur_id' => $ref_inspektur_id ?: NULL,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
        if ($nama_lhr_asli !== NULL) {
            $data['nama_lhr_asli'] = $nama_lhr_asli;
        }
        return $this->db->where('id', $reviu_id)->update('trx_reviu_inspektorat', $data);
    }

    // ─── STATISTIK ────────────────────────────────────────────

    public function count_by_status($tahun, $kabkota_id = NULL)
    {
        // Hitung status aktif berdasarkan t.status
        $this->db
            ->select('t.status, COUNT(*) as total')
            ->from('trx_tahapan_penyaluran t')
            ->join('trx_pekerjaan p', 'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',       'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->where_in('t.status', ['opd_input','inspektorat_reviu','inspektorat_revisi']);
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        $rows = $this->db->group_by('t.status')->get()->result();
        $map  = [];
        foreach ($rows as $r) $map[$r->status] = (int)$r->total;

        // Hitung reviu selesai dari r.hasil_reviu — terlepas dari t.status saat ini
        $this->db
            ->from('trx_reviu_inspektorat r')
            ->join('trx_tahapan_penyaluran t', 't.id = r.tahapan_id')
            ->join('trx_pekerjaan p',          'p.id = t.pekerjaan_id')
            ->join('ref_bkp b',                'b.id = p.bkp_id')
            ->where('b.tahun', $tahun)
            ->where('r.hasil_reviu', 'disetujui');
        if ($kabkota_id) $this->db->where('b.kabkota_id', $kabkota_id);
        $map['inspektorat_approved'] = (int)$this->db->count_all_results();

        return $map;
    }
}
