<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Files_api extends MY_Controller
{
    public function __construct(){parent::__construct();$this->load->model('Order_model','orders');}
    public function upload_reference($orderId)
    {
        $client=$this->require_client();
        $order=$this->db->get_where('orders',['id'=>(int)$orderId,'client_id'=>(int)$client->id])->row();
        if(!$order) return $this->fail('Pesanan tidak ditemukan.',404);
        if(!isset($_FILES['file'])||$_FILES['file']['error']!==UPLOAD_ERR_OK) return $this->fail('Pilih file referensi terlebih dahulu.',422);
        $dir=FCPATH.'assets/uploads/references/'; if(!is_dir($dir)) @mkdir($dir,0775,true);
        $config=['upload_path'=>$dir,'allowed_types'=>'jpg|jpeg|png|webp|pdf','max_size'=>10240,'encrypt_name'=>true,'remove_spaces'=>true];
        $this->load->library('upload',$config);
        if(!$this->upload->do_upload('file')) return $this->fail(strip_tags($this->upload->display_errors('','')),422);
        $f=$this->upload->data();
        $this->orders->add_file($orderId,'REFERENCE',$f['file_name'],$f['client_name'],[],['file_size'=>$f['file_size']*1024,'mime_type'=>$f['file_type'],'uploaded_by'=>'CLIENT','is_visible_to_client'=>1]);
        return $this->success(['id'=>(int)$this->db->insert_id(),'name'=>$f['client_name'],'url'=>base_url('assets/uploads/references/'.rawurlencode($f['file_name']))],'File referensi berhasil diunggah.',201);
    }
}
