<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Apidocs extends CI_Controller{public function index(){$this->load->view('demo/api_docs',['base'=>rtrim(base_url(),'\/').'/api']);}}
