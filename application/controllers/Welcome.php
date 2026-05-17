<?php
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
