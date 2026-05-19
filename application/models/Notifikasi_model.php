<?php
/**
 * Notifikasi_model.php — Model Notifikasi In-App
 *
 * Notifikasi antar user dalam sistem (bukan email/SMS/Telegram).
 * Ditampilkan di top-bar — di-load setiap request via Auth_Controller.
 *
 * TABEL: trx_notifikasi
 * KOLOM: user_id (penerima), judul, pesan, jenis, url, pekerjaan_id,
 *        tahapan_id, is_read, created_at
 *
 * JENIS NOTIFIKASI: info | sukses | peringatan | error
 *
 * CARA KIRIM:
 *   $this->Notifikasi_model->kirim(
 *     $user_id,      // penerima
 *     'Judul',
 *     'Pesan...',
 *     'info',        // jenis
 *     site_url('url/tujuan'),
 *     $pekerjaan_id, // opsional, untuk link langsung
 *     $tahapan_id    // opsional
 *   );
 *
 * CATATAN: Notifikasi hanya muncul saat page refresh (tidak realtime).
 * Untuk realtime, perlu WebSocket atau polling JS (belum diimplementasi).
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifikasi_model extends CI_Model
{
    public function count_unread($user_id) {
        return $this->db->where(['user_id'=>$user_id,'is_read'=>0])->count_all_results('trx_notifikasi');
    }
    public function get_recent($user_id, $limit = 5) {
        return $this->db->where('user_id',$user_id)->order_by('created_at','DESC')->limit($limit)->get('trx_notifikasi')->result();
    }
    public function mark_read($id, $user_id) {
        $this->db->where(['id'=>$id,'user_id'=>$user_id])->update('trx_notifikasi',['is_read'=>1]);
    }
    public function mark_all_read($user_id) {
        $this->db->where(['user_id'=>$user_id,'is_read'=>0])->update('trx_notifikasi',['is_read'=>1]);
    }
    public function kirim($user_id, $judul, $pesan, $jenis = 'info', $url = NULL, $pekerjaan_id = NULL, $tahapan_id = NULL) {
        $this->db->insert('trx_notifikasi',[
            'user_id'     => $user_id,
            'judul'       => $judul,
            'pesan'       => $pesan,
            'jenis'       => $jenis,
            'url'         => $url,
            'pekerjaan_id'=> $pekerjaan_id,
            'tahapan_id'  => $tahapan_id,
            'is_read'     => 0,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}
