<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Clients extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Client_model', 'clients');
    }

    public function index()
    {
        $q = trim((string)$this->input->get('q', true));
        $active = $this->input->get('active', true);
        $data = [
            'title' => 'Pelanggan',
            'rows' => $this->clients->all($q, $active),
            'q' => $q,
            'active' => $active,
            'page_css' => ['admin.css']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('clients/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->method(true) === 'POST') {
            $data = $this->validated_payload();
            if ($data === false) redirect('clients/create');

            $password = (string)$this->input->post('password', false);
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $this->session->set_flashdata('clients_err', 'Password pelanggan minimal 8 karakter.');
                    redirect('clients/create');
                }
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->clients->create($data);
            $this->session->set_flashdata('clients_ok', 'Pelanggan berhasil ditambahkan.');
            redirect('clients');
        }

        $this->render_form(null, 'Tambah Pelanggan');
    }

    public function edit($id)
    {
        $row = $this->clients->find($id);
        if (!$row) show_404();

        if ($this->input->method(true) === 'POST') {
            $data = $this->validated_payload((int)$id);
            if ($data === false) redirect('clients/edit/' . (int)$id);

            $password = (string)$this->input->post('password', false);
            if ($password !== '') {
                if (strlen($password) < 8) {
                    $this->session->set_flashdata('clients_err', 'Password pelanggan minimal 8 karakter.');
                    redirect('clients/edit/' . (int)$id);
                }
                $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $this->clients->update($id, $data);
            $this->session->set_flashdata('clients_ok', 'Data pelanggan berhasil diperbarui.');
            redirect('clients/view/' . (int)$id);
        }

        $this->render_form($row, 'Edit Pelanggan');
    }

    public function view($id)
    {
        $row = $this->clients->find_with_summary($id);
        if (!$row) show_404();

        $data = [
            'title' => 'Detail Pelanggan',
            'row' => $row,
            'orders' => $this->clients->orders($id),
            'page_css' => ['admin.css']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('clients/view', $data);
        $this->load->view('layout/footer');
    }

    public function toggle($id)
    {
        require_post();
        $row = $this->clients->find($id);
        if (!$row) show_404();

        $active = (int)$row->is_active === 1 ? 0 : 1;
        $this->clients->toggle_active($id, $active);
        $this->session->set_flashdata('clients_ok', $active ? 'Pelanggan diaktifkan.' : 'Pelanggan dinonaktifkan tanpa menghapus riwayat.');
        redirect('clients');
    }

    private function validated_payload($exclude_id = null)
    {
        $name = trim((string)$this->input->post('name', true));
        $phone = normalize_phone($this->input->post('phone', true));
        $email = strtolower(trim((string)$this->input->post('email', true)));

        if ($name === '') {
            $this->session->set_flashdata('clients_err', 'Nama pelanggan wajib diisi.');
            return false;
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('clients_err', 'Format email pelanggan tidak valid.');
            return false;
        }
        if ($email !== '' && $this->clients->email_exists($email, $exclude_id)) {
            $this->session->set_flashdata('clients_err', 'Email sudah digunakan pelanggan lain.');
            return false;
        }

        return [
            'name' => $name,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'address' => trim((string)$this->input->post('address', true)) ?: null,
            'notes' => trim((string)$this->input->post('notes', true)) ?: null,
            'is_active' => $this->input->post('is_active') ? 1 : 0
        ];
    }

    private function render_form($row, $title)
    {
        $data = ['title' => $title, 'row' => $row, 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('clients/form', $data);
        $this->load->view('layout/footer');
    }
}
