<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Orders_api extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Order_model', 'orders');
    }

    private function order_code()
    {
        do { $code = 'ORD-' . date('Ymd') . '-' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT); }
        while ($this->db->where('order_code', $code)->count_all_results('orders') > 0);
        return $code;
    }

    private function owned_order($id)
    {
        $client = $this->require_client();
        return $this->db->get_where('orders', ['id'=>(int)$id, 'client_id'=>(int)$client->id])->row();
    }

    private function map_file($f)
    {
        $url = vi_order_file_url($f);
        if ($f->file_type === 'REFERENCE' && !empty($f->file_name)) $url = base_url('assets/uploads/references/' . rawurlencode($f->file_name));
        return ['id'=>(int)$f->id,'type'=>$f->file_type,'name'=>$f->original_name ?: $f->file_name,'url'=>$url ?: null,'mime_type'=>$f->mime_type,'created_at'=>$f->created_at];
    }

    private function map_order($o, $detail = false)
    {
        $payment = $this->orders->payment_summary($o->id);
        $data = [
            'id'=>(int)$o->id,'order_code'=>$o->order_code,'title'=>$o->title,
            'customer_notes'=>$o->customer_notes,'admin_notes'=>$o->admin_notes,
            'status'=>$o->status === 'LUNAS' ? 'SELESAI' : $o->status,
            'created_via'=>$o->created_via,'deadline'=>$o->deadline,
            'subtotal'=>(int)$o->subtotal,'addons'=>(int)$o->addons,'revision_fee'=>(int)$o->revision_fee,
            'discount'=>(int)$o->discount,'total'=>(int)$o->total,'paid'=>(int)$o->paid,
            'remaining'=>(int)$payment['remaining'],'payment_status'=>$payment['payment_status'],
            'created_at'=>$o->created_at,'updated_at'=>$o->updated_at ?? $o->created_at
        ];
        if (!$detail) return $data;
        $data['items'] = array_map(function($it){ return [
            'id'=>(int)$it->id,'design_type_id'=>(int)$it->design_type_id,'design_name'=>$it->design_name,
            'body_part_id'=>(int)$it->body_part_id,'body_part_name'=>$it->body_name,
            'qty'=>(int)$it->qty,'unit_price'=>(int)$it->price,'line_total'=>(int)$it->price*(int)$it->qty,'note'=>$it->note
        ];}, $this->orders->items($o->id));
        $files = $this->db->where('order_id',(int)$o->id)->where('is_visible_to_client',1)->order_by('id','DESC')->get('order_files')->result();
        $data['files'] = array_map([$this,'map_file'],$files);
        $data['payments'] = array_map(function($p){ return ['id'=>(int)$p->id,'amount'=>(int)$p->amount,'note'=>$p->note,'payment_date'=>$p->payment_date,'source'=>$p->source];},$this->orders->payments($o->id));
        $data['revisions'] = array_map(function($r){ return ['id'=>(int)$r->id,'note'=>$r->note,'fee'=>(int)$r->fee,'source'=>$r->source,'created_at'=>$r->created_at];},$this->orders->revisions($o->id));
        $hist = array_reverse($this->orders->status_histories($o->id));
        $data['status_history'] = array_map(function($h){ return ['old_status'=>$h->old_status,'new_status'=>$h->new_status,'note'=>$h->note,'created_at'=>$h->created_at];},$hist);
        if ($this->db->table_exists('payment_confirmations')) {
            $data['payment_confirmations'] = array_map(function($c){ return ['id'=>(int)$c->id,'amount'=>(int)$c->amount,'status'=>$c->status,'admin_note'=>$c->admin_note,'proof_url'=>base_url(trim($c->proof_path,'/')),'created_at'=>$c->created_at];},$this->db->where('order_id',(int)$o->id)->where('client_id',(int)$o->client_id)->order_by('id','DESC')->get('payment_confirmations')->result());
        }
        return $data;
    }

    public function index()
    {
        $client = $this->require_client();
        [$page,$perPage,$offset] = $this->pagination();
        $status = strtoupper(trim((string)$this->input->get('status')));
        $this->db->where('client_id',(int)$client->id);
        if (in_array($status,['MASUK','PROSES','REVISI','SELESAI'],true)) $this->db->where('status',$status);
        $total = $this->db->count_all_results('orders');
        $this->db->where('client_id',(int)$client->id);
        if (in_array($status,['MASUK','PROSES','REVISI','SELESAI'],true)) $this->db->where('status',$status);
        $rows=$this->db->order_by('created_at','DESC')->limit($perPage,$offset)->get('orders')->result();
        return $this->success(array_map(function($o){return $this->map_order($o);},$rows),'Berhasil',200,['page'=>$page,'per_page'=>$perPage,'total'=>$total,'last_page'=>(int)ceil($total/$perPage)]);
    }

    public function show($id)
    {
        $row=$this->owned_order($id); if(!$row) return $this->fail('Pesanan tidak ditemukan.',404);
        return $this->success($this->map_order($row,true));
    }

    public function store()
    {
        $client=$this->require_client();
        if(strtoupper($this->input->method(true))!=='POST') return $this->fail('Method tidak diizinkan.',405);
        $body=$this->json_body(); $items=$body['items']??[];
        $title=trim((string)($body['title']??'')); $notes=trim((string)($body['customer_notes']??''));
        $deadline=trim((string)($body['deadline']??''));
        if($title===''||!is_array($items)||count($items)<1) return $this->fail('Judul dan minimal satu item desain wajib diisi.',422);
        $clean=[]; $subtotal=0;
        foreach($items as $it){
            $did=(int)($it['design_type_id']??0); $bid=(int)($it['body_part_id']??0); $qty=max(1,min(50,(int)($it['qty']??1)));
            $this->db->select('pm.base_price,dt.name design_name,bp.name body_name')->from('price_matrix pm')->join('design_types dt','dt.id=pm.design_type_id')->join('body_parts bp','bp.id=pm.body_part_id')->where(['pm.design_type_id'=>$did,'pm.body_part_id'=>$bid,'dt.is_active'=>1,'bp.is_active'=>1]);
            $price=$this->db->get()->row(); if(!$price) return $this->fail('Salah satu pilihan desain atau harga tidak valid.',422);
            $clean[]=['design_type_id'=>$did,'body_part_id'=>$bid,'qty'=>$qty,'price'=>(int)$price->base_price,'note'=>substr(trim((string)($it['note']??'')),0,255)];
            $subtotal+=(int)$price->base_price*$qty;
        }
        if($deadline!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/',$deadline)) return $this->fail('Format deadline harus YYYY-MM-DD.',422);
        $now=date('Y-m-d H:i:s');
        $this->db->trans_start();
        $this->db->insert('orders',['order_code'=>$this->order_code(),'client_id'=>(int)$client->id,'title'=>$title,'customer_notes'=>$notes?:null,'admin_notes'=>null,'design_type_id'=>$clean[0]['design_type_id'],'body_part_id'=>$clean[0]['body_part_id'],'base_price'=>$subtotal,'addons'=>0,'revision_count'=>0,'revision_fee'=>0,'subtotal'=>$subtotal,'discount'=>0,'total'=>$subtotal,'paid'=>0,'status'=>'MASUK','created_via'=>'ANDROID','deadline'=>$deadline?:null,'created_at'=>$now,'updated_at'=>$now]);
        $orderId=(int)$this->db->insert_id();
        foreach($clean as $it){$it['order_id']=$orderId;$it['created_at']=$now;$this->db->insert('order_items',$it);}
        $this->orders->record_status_change($orderId,null,'MASUK','CLIENT',(int)$client->id,'Pesanan dibuat melalui aplikasi Android.');
        $this->db->trans_complete();
        if($this->db->trans_status()===false) return $this->fail('Gagal menyimpan pesanan.',500);
        return $this->success($this->map_order($this->owned_order($orderId),true),'Pesanan berhasil dibuat.',201);
    }

    public function update($id)
    {
        $row=$this->owned_order($id); if(!$row) return $this->fail('Pesanan tidak ditemukan.',404);
        if($row->status!=='MASUK') return $this->fail('Pesanan hanya dapat diubah sebelum diproses admin.',409);
        $body=$this->json_body(); $data=[];
        if(isset($body['title'])&&trim($body['title'])!=='') $data['title']=trim($body['title']);
        if(array_key_exists('customer_notes',$body)) $data['customer_notes']=trim((string)$body['customer_notes'])?:null;
        if(isset($body['deadline'])) $data['deadline']=preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$body['deadline'])?$body['deadline']:null;
        if(!$data) return $this->fail('Tidak ada data yang diubah.',422);
        $this->db->where('id',(int)$id)->update('orders',$data);
        return $this->success($this->map_order($this->owned_order($id),true),'Pesanan diperbarui.');
    }
}
