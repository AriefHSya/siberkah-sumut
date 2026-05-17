<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_telegram extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('admin.view');
        $this->data['active_menu'] = 'admin';
        $this->data['active_sub']  = 'telegram';
    }

    public function index()
    {
        $setting = $this->db->get_where('ref_app_setting', ['kode' => 'telegram_bot_token'])->row();
        $admins  = $this->db
            ->select('u.id, u.nama, u.username, u.telegram_chat_id, r.kode as role_kode')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id')
            ->where_in('r.kode', ['superadmin','admin_provinsi'])
            ->where('u.is_active', 1)
            ->order_by('u.nama', 'ASC')
            ->get()->result();

        $this->render('admin/telegram/index', array_merge($this->data, [
            'title'   => 'Pengaturan Notifikasi Telegram',
            'setting' => $setting,
            'admins'  => $admins,
        ]));
    }

    public function simpan_token()
    {
        $this->requirePerm('admin.view');
        $token = trim($this->input->post('telegram_bot_token', TRUE));
        $this->db->where('kode', 'telegram_bot_token')
                 ->update('ref_app_setting', [
                     'nilai'      => $token,
                     'updated_by' => $this->user_id,
                 ]);
        $this->log_aktivitas('admin.telegram', 'Update Telegram Bot Token');
        $this->session->set_flashdata('success', 'Bot Token berhasil disimpan.');
        redirect('admin/telegram');
    }

    public function test($user_id)
    {
        $this->requirePerm('admin.view');
        $setting = $this->db->get_where('ref_app_setting', ['kode' => 'telegram_bot_token'])->row();
        $token   = $setting ? trim($setting->nilai) : '';
        $user    = $this->db->get_where('users', ['id' => $user_id])->row();

        if (!$token) {
            $this->session->set_flashdata('error', 'Bot Token belum diisi.');
            redirect('admin/telegram'); return;
        }
        if (!$user || !$user->telegram_chat_id) {
            $this->session->set_flashdata('error', 'User tidak memiliki Telegram Chat ID.');
            redirect('admin/telegram'); return;
        }

        $msg  = "✅ <b>Test Notifikasi SIBERKAH SUMUT</b>\n\n";
        $msg .= "Halo <b>" . htmlspecialchars($user->nama) . "</b>!\n";
        $msg .= "Koneksi Telegram berhasil. Anda akan menerima notifikasi permohonan BKP di sini.\n\n";
        $msg .= "<i>" . date('d/m/Y H:i:s') . " WIB</i>";

        $ok = telegram_send($token, $user->telegram_chat_id, $msg);
        if ($ok) {
            $this->session->set_flashdata('success', 'Pesan test berhasil dikirim ke ' . $user->nama . '.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengirim. Cek kembali Bot Token dan Chat ID.');
        }
        redirect('admin/telegram');
    }
}
