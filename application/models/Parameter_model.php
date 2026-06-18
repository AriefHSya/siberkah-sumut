<?php
/**
 * Parameter_model.php — Model Data Referensi & Konfigurasi
 *
 * Model terbesar — akses semua tabel referensi yang dikontrol Parameter_controller.
 *
 * TABEL YANG DIKELOLA:
 *   ref_tahun           — tahun anggaran multi-year
 *   ref_batas_waktu     — deadline pengajuan per jenis per tahun (KRITIS: memblokir submit OPD)
 *   ref_bkp             — data BKP per tahun per kab/kota
 *   ref_kabkota         — 33 Kab/Kota Sumatera Utara (read-only dari sini)
 *   ref_bidang          — 12 bidang kegiatan (read-only dari sini)
 *   ref_pemda_pejabat   — KDH, Kepala BKAD, Inspektur per kab per tahun
 *   ref_pemda_dokumen   — Perda/Pergub/Perkada per kab per tahun
 *   batas_waktu_log     — audit trail perubahan batas waktu
 *
 * SECTION:
 *   A. ref_tahun       — get_all_tahun(), get_tahun_aktif(), tahun_exists(), insert/hapus
 *   B. ref_batas_waktu — get_batas_waktu(), cek_deadline(), insert/update
 *   C. ref_bkp         — get_bkp(), bkp_exists(), insert/update/hapus, rekap
 *   D. ref_pemda       — get_pejabat(), get_dokumen(), simpan/hapus
 *   E. Shared          — get_kabkota(), get_bidang()
 *
 * CRITICAL: cek_deadline($tahun, $jenis) — dipanggil Pekerjaan::submit()
 *   Jika deadline sudah lewat, submit DIBLOKIR (hard block, tidak bisa di-override).
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Parameter_model extends CI_Model
{
    // ─── REF_TAHUN ────────────────────────────────────────────
    public function get_all_tahun() {
        return $this->db->order_by('tahun','DESC')->get('ref_tahun')->result();
    }
    public function get_tahun_aktif() {
        $r = $this->db->get_where('ref_tahun',['is_aktif'=>1])->row();
        return $r ? $r->tahun : date('Y');
    }
    public function tahun_exists($tahun) {
        return $this->db->get_where('ref_tahun',['tahun'=>$tahun])->num_rows() > 0;
    }
    public function insert_tahun($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ref_tahun', $data);
        return $this->db->insert_id();
    }
    public function set_tahun_aktif($tahun) {
        $this->db->update('ref_tahun', ['is_aktif'=>0]);
        return $this->db->where('tahun',$tahun)->update('ref_tahun',['is_aktif'=>1]);
    }
    public function hapus_tahun($id) {
        return $this->db->delete('ref_tahun',['id'=>$id]);
    }

    // ─── REF_BATAS_WAKTU ──────────────────────────────────────
    public function get_batas_waktu($tahun = NULL) {
        if ($tahun) $this->db->where('tahun', $tahun);
        return $this->db->order_by('jenis_penyaluran','ASC')->order_by('kode_tahap','ASC')->get('ref_batas_waktu')->result();
    }
    public function get_batas_waktu_by_id($id) {
        return $this->db->get_where('ref_batas_waktu',['id'=>$id])->row();
    }
    public function cek_deadline($tahun, $jenis_penyaluran, $kode_tahap) {
        $bw = $this->db->get_where('ref_batas_waktu',[
            'tahun'            => $tahun,
            'jenis_penyaluran' => $jenis_penyaluran,
            'kode_tahap'       => $kode_tahap,
            'is_active'        => 1,
        ])->row();
        if (!$bw) return ['ok' => TRUE, 'pesan' => '', 'bw' => NULL];
        $lewat = date('Y-m-d') > $bw->batas_pengajuan;
        return [
            'ok'   => !$lewat,
            'pesan'=> $lewat
                ? 'Batas waktu pengajuan <strong>'.$bw->label.'</strong> adalah <strong>'.tgl_indo($bw->batas_pengajuan).'</strong>. Pengajuan tidak dapat diproses.'
                : '',
            'bw'   => $bw,
        ];
    }
    public function insert_batas_waktu($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ref_batas_waktu', $data);
        return $this->db->insert_id();
    }
    public function update_batas_waktu($id, $data, $user_id) {
        $lama = $this->get_batas_waktu_by_id($id);
        $data['updated_by'] = $user_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id',$id)->update('ref_batas_waktu', $data);
        // Log setiap field yang berubah
        foreach (['batas_pengajuan','batas_penyaluran'] as $f) {
            if (isset($data[$f]) && $lama->$f != $data[$f]) {
                $this->db->insert('ref_batas_waktu_log',[
                    'batas_waktu_id' => $id,
                    'field_ubah'     => $f,
                    'nilai_lama'     => $lama->$f,
                    'nilai_baru'     => $data[$f],
                    'alasan'         => $data['alasan'] ?? '',
                    'user_id'        => $user_id,
                    'ip_address'     => $this->input->ip_address(),
                    'created_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
    public function get_log_batas_waktu($limit = 50) {
        return $this->db->select('l.*, u.nama as nama_user, b.label, b.tahun, b.jenis_penyaluran')
            ->from('ref_batas_waktu_log l')
            ->join('ref_batas_waktu b','b.id = l.batas_waktu_id','left')
            ->join('users u','u.id = l.user_id','left')
            ->order_by('l.created_at','DESC')->limit($limit)->get()->result();
    }

    // ─── REF_BKP ──────────────────────────────────────────────
    private function _bkp_filter($filters) {
        $this->db->from('ref_bkp r')
            ->join('ref_kabkota k','k.id = r.kabkota_id')
            ->join('ref_bidang b','b.id = r.bidang_id');
        if (!empty($filters['tahun']))      $this->db->where('r.tahun',$filters['tahun']);
        if (!empty($filters['kabkota_id'])) $this->db->where('r.kabkota_id',$filters['kabkota_id']);
        if (!empty($filters['bidang_id']))  $this->db->where('r.bidang_id',$filters['bidang_id']);
        if (!empty($filters['q']))          $this->db->like('r.uraian_bkp',$filters['q']);
        $this->db->where('r.is_active',1);
    }

    public function count_bkp($filters = []) {
        $this->_bkp_filter($filters);
        return $this->db->count_all_results();
    }

    public function get_bkp($filters = [], $limit = NULL, $offset = 0) {
        $this->db->select('r.*, k.nama as nama_kabkota, b.nama as nama_bidang');
        $this->_bkp_filter($filters);
        $this->db->order_by('r.kode_bkp','ASC');
        if ($limit !== NULL) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }
    public function get_bkp_by_id($id) {
        return $this->db->select('r.*, k.nama as nama_kabkota, b.nama as nama_bidang')
            ->from('ref_bkp r')
            ->join('ref_kabkota k','k.id = r.kabkota_id')
            ->join('ref_bidang b','b.id = r.bidang_id')
            ->where('r.id',$id)->get()->row();
    }
    public function bkp_exists($kode, $tahun, $exclude = NULL) {
        $this->db->where(['kode_bkp'=>$kode,'tahun'=>$tahun]);
        if ($exclude) $this->db->where('id !=',$exclude);
        return $this->db->count_all_results('ref_bkp') > 0;
    }
    public function insert_bkp($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('ref_bkp',$data);
        return $this->db->insert_id();
    }
    public function update_bkp($id, $data, $user_id) {
        $lama = $this->get_bkp_by_id($id);
        $data['updated_by'] = $user_id;
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where('id',$id)->update('ref_bkp',$data);
        foreach (['uraian_bkp','nilai'] as $f) {
            if (isset($data[$f]) && $lama->$f != $data[$f]) {
                $this->db->insert('ref_bkp_log',[
                    'ref_bkp_id'=>$id,'kode_bkp'=>$lama->kode_bkp,'tahun'=>$lama->tahun,
                    'field_ubah'=>$f,'nilai_lama'=>$lama->$f,'nilai_baru'=>$data[$f],
                    'user_id'=>$user_id,'ip_address'=>$this->input->ip_address(),'created_at'=>date('Y-m-d H:i:s'),
                ]);
            }
        }
    }
    public function hapus_bkp($id) { return $this->db->delete('ref_bkp',['id'=>$id]); }
    public function rekap_bkp($tahun, $kabkota_id = NULL) {
        $this->db->select('COUNT(*) as total, SUM(nilai) as total_nilai, COUNT(DISTINCT kabkota_id) as total_kabkota')
            ->where('tahun',$tahun)->where('is_active',1);
        if ($kabkota_id) $this->db->where('kabkota_id',$kabkota_id);
        return $this->db->get('ref_bkp')->row();
    }
    public function count_log_bkp($tahun = NULL) {
        if ($tahun) $this->db->where('l.tahun', $tahun);
        return $this->db->from('ref_bkp_log l')->count_all_results();
    }
    public function get_log_bkp($tahun = NULL, $limit = 50, $offset = 0) {
        if ($tahun) $this->db->where('l.tahun',$tahun);
        return $this->db->select('l.*, u.nama as nama_user')
            ->from('ref_bkp_log l')->join('users u','u.id = l.user_id','left')
            ->order_by('l.created_at','DESC')->limit($limit, $offset)->get()->result();
    }

    // ─── REF_PEMDA ────────────────────────────────────────────
    public function get_pejabat($kabkota_id, $tahun) {
        $rows = $this->db->get_where('ref_pemda_pejabat',['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])->result();
        $result = [];
        foreach ($rows as $r) { $result[$r->jenis] = $r; }
        return $result;
    }
    public function get_dokumen_pemda($kabkota_id, $tahun) {
        return $this->db->get_where('ref_pemda_dokumen',['kabkota_id'=>$kabkota_id,'tahun'=>$tahun])->result();
    }
    public function get_dokumen_by_id($id) {
        return $this->db->get_where('ref_pemda_dokumen',['id'=>$id])->row();
    }
    public function simpan_pejabat($data, $user_id) {
        $existing = $this->db->get_where('ref_pemda_pejabat',['kabkota_id'=>$data['kabkota_id'],'tahun'=>$data['tahun'],'jenis'=>$data['jenis']])->row();
        if ($existing) {
            foreach (['nama','nip','jabatan','pangkat'] as $f) {
                if (isset($data[$f]) && $existing->$f !== $data[$f]) {
                    $this->db->insert('ref_pemda_log',['kabkota_id'=>$data['kabkota_id'],'tahun'=>$data['tahun'],'tabel'=>'pejabat','record_id'=>$existing->id,'field_ubah'=>$f,'nilai_lama'=>$existing->$f,'nilai_baru'=>$data[$f],'user_id'=>$user_id,'ip_address'=>$this->input->ip_address(),'created_at'=>date('Y-m-d H:i:s')]);
                }
            }
            $data['updated_by'] = $user_id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where(['kabkota_id'=>$data['kabkota_id'],'tahun'=>$data['tahun'],'jenis'=>$data['jenis']])->update('ref_pemda_pejabat',$data);
        } else {
            $data['created_by'] = $user_id;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('ref_pemda_pejabat',$data);
        }
    }
    public function simpan_dokumen($data, $id_edit, $user_id) {
        if ($id_edit) {
            $data['updated_by'] = $user_id;
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id',$id_edit)->update('ref_pemda_dokumen',$data);
        } else {
            $data['created_by'] = $user_id;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('ref_pemda_dokumen',$data);
        }
    }
    public function hapus_dokumen($id) { return $this->db->delete('ref_pemda_dokumen',['id'=>$id]); }
    public function get_log_pemda($tahun = NULL, $limit = 50) {
        if ($tahun) $this->db->where('l.tahun',$tahun);
        return $this->db->select('l.*, u.nama as nama_user, k.nama as nama_kabkota')
            ->from('ref_pemda_log l')
            ->join('users u','u.id = l.user_id','left')
            ->join('ref_kabkota k','k.id = l.kabkota_id','left')
            ->order_by('l.created_at','DESC')->limit($limit)->get()->result();
    }

    // ─── PEJABAT BKAD PROVINSI ───────────────────────────────

    public function get_pejabat_bkad_prov($tahun)
    {
        $rows = $this->db->get_where('ref_pejabat_bkad_prov', ['tahun' => $tahun])->result();
        $map  = [];
        foreach ($rows as $r) $map[$r->jenis] = $r;
        return $map;
    }

    public function simpan_pejabat_bkad_prov($data)
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $ada = $this->db->get_where('ref_pejabat_bkad_prov',
            ['tahun' => $data['tahun'], 'jenis' => $data['jenis']])->row();
        if ($ada) {
            $this->db->where('id', $ada->id)->update('ref_pejabat_bkad_prov', $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('ref_pejabat_bkad_prov', $data);
        }
    }

    // ─── MASTER DROPDOWN ─────────────────────────────────────
    public function get_kabkota() { return $this->db->where('is_active',1)->order_by('nama','ASC')->get('ref_kabkota')->result(); }
    public function get_bidang()  { return $this->db->where('is_active',1)->get('ref_bidang')->result(); }
}
