<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Prices extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Price_model', 'prices');
    }

    public function index()
    {
        $q = trim((string)$this->input->get('q', true));
        $data = [
            'title' => 'Harga (Price Matrix)',
            'rows' => $this->prices->all($q),
            'designs' => $this->prices->design_types(),
            'parts' => $this->prices->body_parts(),
            'q' => $q,
            'page_css' => ['prices-mobile.css'],
            'page_js' => ['list-search.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('prices/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->post()) {
            $this->prices->upsert(
                (int)$this->input->post('design_type_id'),
                (int)$this->input->post('body_part_id'),
                rupiah_number($this->input->post('base_price'))
            );
            redirect('prices');
        }
        $data = [
            'title' => 'Tambah Harga',
            'designs' => $this->prices->design_types(),
            'parts' => $this->prices->body_parts(),
            'row' => null,
            'page_css' => ['prices-mobile.css'],
            'page_js' => ['rupiah-format.js', 'custom-select.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('prices/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $row = $this->prices->find($id);
        if (!$row) show_404();

        if ($this->input->post()) {
            $design_type_id = (int)$this->input->post('design_type_id');
            $body_part_id   = (int)$this->input->post('body_part_id');
            $base_price     = rupiah_number($this->input->post('base_price'));

            $dup = $this->db->get_where('price_matrix', [
                'design_type_id' => $design_type_id,
                'body_part_id'   => $body_part_id
            ])->row();

            if ($dup && (int)$dup->id !== (int)$id) {
                $this->session->set_flashdata('err', 'Kombinasi jenis & bagian itu sudah ada.');
                redirect('prices/edit/' . $id);
            }

            $this->prices->update_by_id($id, [
                'design_type_id' => $design_type_id,
                'body_part_id'   => $body_part_id,
                'base_price'     => $base_price,
                'created_at'     => date('Y-m-d H:i:s')
            ]);

            redirect('prices');
        }

        $data = [
            'title' => 'Edit Harga',
            'designs' => $this->prices->design_types(),
            'parts' => $this->prices->body_parts(),
            'row' => $row,
            'page_css' => ['prices-mobile.css'],
            'page_js' => ['rupiah-format.js', 'custom-select.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('prices/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete($id)
    {
        require_post();
        $this->prices->delete($id);
        redirect('prices');
    }

    public function get_base_price()
    {
        $design_type_id = (int)$this->input->get('design_type_id');
        $body_part_id = (int)$this->input->get('body_part_id');
        $base = $this->prices->get_base_price($design_type_id, $body_part_id);
        $this->output->set_content_type('application/json')->set_output(json_encode(['base_price' => $base]));
    }
}
