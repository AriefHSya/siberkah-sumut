<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin_logs.php — Controller Log Aktivitas (read-only)
 *
 * Menampilkan audit trail dari:
 *   - user_logs           → tab "Log Aktivitas"
 *   - trx_status_history  → tab "Riwayat Status"
 *
 * ROUTES:
 *   GET /admin/logs               → index()          — log aktivitas user
 *   GET /admin/logs/status        → status_history() — riwayat perubahan status
 *   GET /admin/logs/export        → export_aktivitas() — CSV log aktivitas (filter aktif)
 *   GET /admin/logs/export-status → export_status()    — CSV riwayat status (filter aktif)
 *
 * AKSES: admin.logs.view (default: superadmin & admin_provinsi)
 * Halaman ini READ-ONLY — tidak ada aksi ubah/hapus.
 */
class Admin_logs extends Auth_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requirePerm('admin.logs.view');
        $this->load->model(['Admin_logs_model', 'Parameter_model']);
        $this->data['active_menu'] = 'admin';
        $this->data['active_sub']  = 'logs';
    }

    // ─── TAB 1: LOG AKTIVITAS ──────────────────────────────────

    public function index()
    {
        $per_page = 50;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tanggal_dari'   => $this->input->get('tanggal_dari'),
            'tanggal_sampai' => $this->input->get('tanggal_sampai'),
            'user_id'        => $this->input->get('user_id'),
            'aksi'           => $this->input->get('aksi'),
            'q'              => $this->input->get('q'),
        ];

        $total  = $this->Admin_logs_model->count_aktivitas($filters);
        $offset = ($page - 1) * $per_page;

        $d = $this->data;
        $d['title']      = 'Log Aktivitas — SIBERKAH SUMUT';
        $d['list']       = $this->Admin_logs_model->get_aktivitas($filters, $per_page, $offset);
        $d['aksi_list']  = $this->Admin_logs_model->get_distinct_aksi();
        $d['users_list'] = $this->Admin_logs_model->get_users_for_filter();
        $d['filters']    = $filters;
        $d['paging']     = ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'admin/logs'];
        $this->render('admin/logs/index', $d);
    }

    // ─── TAB 2: RIWAYAT STATUS ─────────────────────────────────

    public function status_history()
    {
        $per_page = 50;
        $page     = max(1, (int)$this->input->get('page'));

        $filters = [
            'tanggal_dari'   => $this->input->get('tanggal_dari'),
            'tanggal_sampai' => $this->input->get('tanggal_sampai'),
            'kabkota_id'     => $this->input->get('kabkota_id'),
            'status_baru'    => $this->input->get('status_baru'),
            'q'              => $this->input->get('q'),
        ];

        $total  = $this->Admin_logs_model->count_status_history($filters);
        $offset = ($page - 1) * $per_page;

        $d = $this->data;
        $d['title']        = 'Riwayat Status — SIBERKAH SUMUT';
        $d['list']         = $this->Admin_logs_model->get_status_history($filters, $per_page, $offset);
        $d['status_list']  = $this->Admin_logs_model->get_distinct_status();
        $d['kabkota_list'] = $this->Parameter_model->get_kabkota();
        $d['filters']      = $filters;
        $d['paging']       = ['total'=>$total,'per_page'=>$per_page,'page'=>$page,'base_url'=>'admin/logs/status'];
        $this->render('admin/logs/status_history', $d);
    }

    // ─── EXPORT CSV ─────────────────────────────────────────────

    public function export_aktivitas()
    {
        $filters = [
            'tanggal_dari'   => $this->input->get('tanggal_dari'),
            'tanggal_sampai' => $this->input->get('tanggal_sampai'),
            'user_id'        => $this->input->get('user_id'),
            'aksi'           => $this->input->get('aksi'),
            'q'              => $this->input->get('q'),
        ];
        $list = $this->Admin_logs_model->get_aktivitas($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="log_aktivitas_'.date('Ymd_His').'.csv"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF"; // BOM UTF-8 agar Excel terbaca

        $f = fopen('php://output', 'w');
        fputcsv($f, ['Waktu','Nama User','Username','Role','Aksi','Keterangan','IP Address'], ';');
        foreach ($list as $row) {
            fputcsv($f, [
                $row->created_at,
                $row->nama_user ?? '-',
                $row->username ?? '-',
                $row->role_nama ?? '-',
                $row->aksi,
                $row->keterangan,
                $row->ip_address,
            ], ';');
        }
        fclose($f);

        $this->log_aktivitas('admin.logs.export', 'Export CSV log aktivitas');
        exit;
    }

    public function export_status()
    {
        $filters = [
            'tanggal_dari'   => $this->input->get('tanggal_dari'),
            'tanggal_sampai' => $this->input->get('tanggal_sampai'),
            'kabkota_id'     => $this->input->get('kabkota_id'),
            'status_baru'    => $this->input->get('status_baru'),
            'q'              => $this->input->get('q'),
        ];
        $list = $this->Admin_logs_model->get_status_history($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="riwayat_status_'.date('Ymd_His').'.csv"');
        header('Pragma: no-cache');
        echo "\xEF\xBB\xBF";

        $f = fopen('php://output', 'w');
        fputcsv($f, ['Waktu','Kode BKP','Uraian BKP','Kab/Kota','Status Lama','Status Baru','Catatan','Diubah Oleh'], ';');
        foreach ($list as $row) {
            fputcsv($f, [
                $row->created_at,
                $row->kode_bkp,
                $row->uraian_bkp,
                $row->nama_kabkota,
                $row->status_lama ?? '-',
                $row->status_baru,
                $row->catatan,
                $row->nama_user ?? '-',
            ], ';');
        }
        fclose($f);

        $this->log_aktivitas('admin.logs.export', 'Export CSV riwayat status');
        exit;
    }
}
