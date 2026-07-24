<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model', 'users');
    }

    public function index()
    {
        $row = $this->users->find(current_user_id());
        if (!$row) redirect('logout');

        if ($this->input->method(true) === 'POST') {
            $name = trim((string)$this->input->post('name', true));
            $username = strtolower(trim((string)$this->input->post('username', true)));
            $email = strtolower(trim((string)$this->input->post('email', true)));
            if ($name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->session->set_flashdata('profile_err', 'Nama, username, dan email valid wajib diisi.');
                redirect('profile');
            }
            if ($this->users->username_exists($username, $row->id) || $this->users->email_exists($email, $row->id)) {
                $this->session->set_flashdata('profile_err', 'Username atau email sudah digunakan akun lain.');
                redirect('profile');
            }
            $data = ['name' => $name, 'username' => $username, 'email' => $email];
            $newPassword = (string)$this->input->post('new_password', false);
            if ($newPassword !== '') {
                $currentPassword = (string)$this->input->post('current_password', false);
                if (!password_verify($currentPassword, $row->password_hash)) {
                    $this->session->set_flashdata('profile_err', 'Password saat ini tidak sesuai.');
                    redirect('profile');
                }
                if (strlen($newPassword) < 8) {
                    $this->session->set_flashdata('profile_err', 'Password baru minimal 8 karakter.');
                    redirect('profile');
                }
                $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
                $data['remember_token'] = null;
            }
            $this->users->update($row->id, $data);
            $this->session->set_userdata(['user_name' => $name, 'username' => $username, 'user_email' => $email]);
            $this->session->set_flashdata('profile_ok', 'Profil berhasil diperbarui.');
            redirect('profile');
        }

        $data = ['title' => 'Profil Saya', 'row' => $row, 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('profile/index', $data);
        $this->load->view('layout/footer');
    }
}
