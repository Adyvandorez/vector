<?php
defined('BASEPATH') or exit('No direct script access allowed');
class AuthHook
{
    public function check()
    {
        $ci=&get_instance();$ci->load->library('session');$ci->load->helper('url');
        $class=strtolower($ci->router->fetch_class());
        $uri=trim((string)$ci->uri->uri_string(),'/');
        // API memakai Bearer Token sendiri. Demo dan dokumentasi bersifat publik.
        if(strpos($uri,'api/')===0||$uri==='api'||$uri==='demo'||$class==='demo'||$class==='apidocs')return;
        if($class==='auth')return;
        if(!$ci->session->userdata('user_id'))redirect('login');
    }
}
