<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Demo extends CI_Controller{public function index(){$this->load->view('demo/index',['api'=>base_url('api/health'),'admin'=>base_url('login')]);}}
