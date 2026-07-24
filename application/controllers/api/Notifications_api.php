<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Notifications_api extends MY_Controller
{
    public function index(){ $c=$this->require_client(); $rows=$this->db->where('client_id',(int)$c->id)->order_by('created_at','DESC')->limit(100)->get('client_notifications')->result(); return $this->success(array_map(function($n){return ['id'=>(int)$n->id,'order_id'=>$n->order_id?(int)$n->order_id:null,'type'=>$n->type,'title'=>$n->title,'message'=>$n->message,'is_read'=>(bool)$n->is_read,'created_at'=>$n->created_at];},$rows)); }
    public function read($id){ $c=$this->require_client(); $this->db->where(['id'=>(int)$id,'client_id'=>(int)$c->id])->update('client_notifications',['is_read'=>1,'read_at'=>date('Y-m-d H:i:s')]); return $this->success(['read'=>true],'Notifikasi ditandai dibaca.'); }
    public function read_all(){ $c=$this->require_client(); $this->db->where('client_id',(int)$c->id)->update('client_notifications',['is_read'=>1,'read_at'=>date('Y-m-d H:i:s')]); return $this->success(['read_all'=>true],'Semua notifikasi ditandai dibaca.'); }
}
