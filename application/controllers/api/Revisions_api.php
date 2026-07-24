<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Revisions_api extends MY_Controller
{
    public function __construct(){parent::__construct();$this->load->model('Order_model','orders');}
    public function store($orderId)
    {
        $client=$this->require_client(); $order=$this->db->get_where('orders',['id'=>(int)$orderId,'client_id'=>(int)$client->id])->row();
        if(!$order)return $this->fail('Pesanan tidak ditemukan.',404);
        if(!in_array($order->status,['PROSES','REVISI'],true))return $this->fail('Revisi dapat diajukan ketika pesanan sedang diproses.',409);
        $note=trim((string)$this->request_value('note')); if(mb_strlen($note)<5)return $this->fail('Catatan revisi minimal 5 karakter.',422);
        $this->db->trans_start(); $this->orders->add_revision($orderId,$note,0,'CLIENT');
        if($order->status!=='REVISI'){$this->db->where('id',(int)$orderId)->update('orders',['status'=>'REVISI']);$this->orders->record_status_change($orderId,$order->status,'REVISI','CLIENT',(int)$client->id,'Pelanggan mengajukan revisi.');}
        $this->db->trans_complete();
        return $this->success(['revision_submitted'=>true],'Permintaan revisi berhasil dikirim.',201);
    }
}
