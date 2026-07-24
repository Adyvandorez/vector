<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Payments_api extends MY_Controller
{
    public function methods()
    {
        $rows=$this->db->where('is_active',1)->order_by('sort_order','ASC')->get('payment_methods')->result();
        return $this->success(array_map(function($m){return ['id'=>(int)$m->id,'type'=>$m->type,'name'=>$m->name,'account_number'=>$m->account_number,'account_holder'=>$m->account_holder,'instructions'=>$m->instructions,'qr_image_url'=>$m->qr_image?base_url(trim($m->qr_image,'/')):null];},$rows));
    }
    public function confirmations()
    {
        $c=$this->require_client();$rows=$this->db->select('pc.*,o.order_code,pm.name payment_method_name')->from('payment_confirmations pc')->join('orders o','o.id=pc.order_id')->join('payment_methods pm','pm.id=pc.payment_method_id')->where('pc.client_id',(int)$c->id)->order_by('pc.id','DESC')->get()->result();
        return $this->success(array_map(function($r){return ['id'=>(int)$r->id,'order_id'=>(int)$r->order_id,'order_code'=>$r->order_code,'payment_method'=>$r->payment_method_name,'amount'=>(int)$r->amount,'status'=>$r->status,'proof_url'=>base_url(trim($r->proof_path,'/')),'note'=>$r->note,'admin_note'=>$r->admin_note,'created_at'=>$r->created_at];},$rows));
    }
    public function submit($orderId)
    {
        $c=$this->require_client();$order=$this->db->get_where('orders',['id'=>(int)$orderId,'client_id'=>(int)$c->id])->row();if(!$order)return $this->fail('Pesanan tidak ditemukan.',404);
        $methodId=(int)$this->input->post('payment_method_id');$amount=(int)preg_replace('/\D/','',(string)$this->input->post('amount'));$remaining=max(0,(int)$order->total-(int)$order->paid);
        if(!$this->db->get_where('payment_methods',['id'=>$methodId,'is_active'=>1])->row())return $this->fail('Metode pembayaran tidak valid.',422);
        if($amount<=0||$amount>$remaining)return $this->fail('Nominal pembayaran tidak valid atau melebihi sisa tagihan.',422);
        if(!isset($_FILES['proof'])||$_FILES['proof']['error']!==UPLOAD_ERR_OK)return $this->fail('Bukti pembayaran wajib diunggah.',422);
        $dir=FCPATH.'assets/uploads/payment_proofs/';if(!is_dir($dir))@mkdir($dir,0775,true);
        $this->load->library('upload',['upload_path'=>$dir,'allowed_types'=>'jpg|jpeg|png|webp','max_size'=>5120,'encrypt_name'=>true]);if(!$this->upload->do_upload('proof'))return $this->fail(strip_tags($this->upload->display_errors('','')),422);
        $f=$this->upload->data();$path='assets/uploads/payment_proofs/'.$f['file_name'];
        $this->db->insert('payment_confirmations',['order_id'=>(int)$orderId,'client_id'=>(int)$c->id,'payment_method_id'=>$methodId,'amount'=>$amount,'proof_path'=>$path,'note'=>trim((string)$this->input->post('note'))?:null,'status'=>'PENDING','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $id=(int)$this->db->insert_id();
        $this->db->insert('client_notifications',['client_id'=>(int)$c->id,'order_id'=>(int)$orderId,'type'=>'PAYMENT','title'=>'Bukti pembayaran dikirim','message'=>'Bukti pembayaran sedang menunggu verifikasi admin.','created_at'=>date('Y-m-d H:i:s')]);
        return $this->success(['id'=>$id,'status'=>'PENDING','proof_url'=>base_url($path)],'Bukti pembayaran berhasil dikirim.',201);
    }
}
