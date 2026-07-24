<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Team extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        require_owner();
        $this->load->model('User_model', 'users');
    }

    public function index()
    {
        $data = ['title' => 'Tim Admin', 'rows' => $this->users->all(), 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('team/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->method(true) === 'POST') {
            $payload = $this->payload();
            if ($payload === false) redirect('team/create');
            $password = (string)$this->input->post('password', false);
            if (strlen($password) < 8) {
                $this->session->set_flashdata('team_err', 'Password minimal 8 karakter.');
                redirect('team/create');
            }
            $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $this->users->create($payload);
            $this->session->set_flashdata('team_ok', 'Akun tim berhasil dibuat.');
            redirect('team');
        }
        $this->render_form(null, 'Tambah Tim');
    }

    public function edit($id)
    {
        $row = $this->users->find($id);
        if (!$row) show_404();
        if ($this->input->method(true) === 'POST') {
            $payload = $this->payload((int)$id);
            if ($payload === false) redirect('team/edit/' . (int)$id);
            $password = (string)$this->input->post('password', false);
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $this->session->set_flashdata('team_err', 'Password minimal 8 karakter.');
                    redirect('team/edit/' . (int)$id);
                }
                $payload['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
                $payload['remember_token'] = null;
            }
            if ((int)$id === current_user_id()) $payload['is_active'] = 1;
            $this->users->update($id, $payload);
            $this->session->set_flashdata('team_ok', 'Akun tim berhasil diperbarui.');
            redirect('team');
        }
        $this->render_form($row, 'Edit Tim');
    }

    public function toggle($id)
    {
        require_post();
        $row = $this->users->find($id);
        if (!$row) show_404();
        if ((int)$id === current_user_id()) {
            $this->session->set_flashdata('team_err', 'Akun yang sedang digunakan tidak dapat dinonaktifkan.');
            redirect('team');
        }
        if ($row->role === 'OWNER' && (int)$row->is_active === 1 && $this->users->count_active_owners() <= 1) {
            $this->session->set_flashdata('team_err', 'Minimal satu akun OWNER harus tetap aktif.');
            redirect('team');
        }
        $this->users->update($id, ['is_active' => (int)$row->is_active ? 0 : 1, 'remember_token' => null]);
        $this->session->set_flashdata('team_ok', 'Status akun tim berhasil diubah.');
        redirect('team');
    }

    private function payload($exclude_id = null)
    {
        $name = trim((string)$this->input->post('name', true));
        $username = strtolower(trim((string)$this->input->post('username', true)));
        $email = strtolower(trim((string)$this->input->post('email', true)));
        $role = strtoupper((string)$this->input->post('role', true));
        if ($name === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('team_err', 'Nama, username, dan email valid wajib diisi.');
            return false;
        }
        if (!in_array($role, ['OWNER', 'ADMIN', 'STAFF'], true)) $role = 'STAFF';
        if ($this->users->username_exists($username, $exclude_id)) {
            $this->session->set_flashdata('team_err', 'Username sudah digunakan.');
            return false;
        }
        if ($this->users->email_exists($email, $exclude_id)) {
            $this->session->set_flashdata('team_err', 'Email sudah digunakan.');
            return false;
        }
        return ['name' => $name, 'username' => $username, 'email' => $email, 'role' => $role, 'is_active' => $this->input->post('is_active') ? 1 : 0];
    }

    private function render_form($row, $title)
    {
        $data = ['title' => $title, 'row' => $row, 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('team/form', $data);
        $this->load->view('layout/footer');
    }
}
