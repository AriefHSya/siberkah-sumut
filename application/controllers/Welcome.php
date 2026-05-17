<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Welcome extends Guest_Controller {
    public function index() {
        $this->load->view('landing/index', $this->data);
    }
}
