<?php
/**
 * Welcome.php — Controller Landing Page Publik
 *
 * Halaman publik yang ditampilkan sebelum login.
 * Extends Guest_Controller → redirect ke dashboard jika sudah login.
 *
 * Data yang diambil dari DB:
 *   - ref_landing_pejabat  : foto 4 pejabat (gubernur, wakil, sekda, kepala bkad)
 *   - ref_landing_slideshow: foto slideshow hasil kinerja Pemprovsu
 *
 * Foto dikelola admin via Parameter → Tampilan Landing.
 * Jika belum ada foto, box pejabat dan slideshow tidak ditampilkan.
 */
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends Guest_Controller
{
    public function index()
    {
        // Foto pejabat landing (gubernur, wakil_gubernur, sekda, kepala_bkad)
        $pejabat_rows = $this->db->get('ref_landing_pejabat')->result();
        $pejabat = [];
        foreach ($pejabat_rows as $p) $pejabat[$p->jenis] = $p;

        // Slideshow foto kinerja (aktif saja, urut)
        $slideshow = $this->db
            ->where('is_active', 1)
            ->order_by('urutan', 'ASC')
            ->get('ref_landing_slideshow')->result();

        $this->load->view('landing/index', array_merge($this->data, [
            'pejabat'   => $pejabat,
            'slideshow' => $slideshow,
        ]));
    }
}
