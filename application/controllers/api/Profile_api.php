<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Profile_api extends MY_Controller
{
    public function index(){ $c=$this->require_client(); return $this->success($this->client_payload($c)); }
    public function update()
    {
        $c=$this->require_client(); $body=$this->json_body(); $data=[]; $errors=[];
        if(isset($body['name'])){ $name=trim((string)$body['name']); if(mb_strlen($name)<3)$errors['name']='Nama minimal 3 karakter.';else$data['name']=$name; }
        if(isset($body['phone'])){ $phone=normalize_phone($body['phone']); if(strlen(preg_replace('/\D/','',$phone))<9)$errors['phone']='Nomor WhatsApp tidak valid.';else$data['phone']=$phone; }
        if(array_key_exists('address',$body))$data['address']=trim((string)$body['address'])?:null;
        if(!empty($body['new_password'])){ if(strlen($body['new_password'])<8)$errors['new_password']='Password minimal 8 karakter.';elseif(!password_verify((string)($body['current_password']??''),$c->password_hash))$errors['current_password']='Password saat ini salah.';else$data['password_hash']=password_hash($body['new_password'],PASSWORD_BCRYPT); }
        if($errors)return $this->fail('Data profil belum valid.',422,$errors);
        if($data)$this->clients->update($c->id,$data);
        return $this->success($this->client_payload($this->clients->find($c->id)),'Profil berhasil diperbarui.');
    }
}
