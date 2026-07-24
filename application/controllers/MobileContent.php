<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MobileContent extends CI_Controller
{
    private $public_setting_keys = [
        'brand_name','app_name','mobile_header_name','tagline','home_greeting','home_question','hero_title','hero_button_text',
        'support_whatsapp','support_email','linktree_url','terms_url','privacy_url','dp_percent',
        'revision_free_limit','extra_revision_fee','cdr_fee_per_design','exclusive_license_fee_per_head',
        'complex_background_fee','normal_duration_text','express_fee','portfolio_section_title','promotion_section_title'
    ];

    public function __construct()
    {
        parent::__construct();
        require_owner();
        $this->load->model('Mobile_content_model', 'mobile_content');
    }

    private function render($extra = [])
    {
        $data = array_merge([
            'title' => 'Konten Aplikasi',
            'settings' => $this->mobile_content->all_settings(),
            'banners' => $this->mobile_content->banners(),
            'promotions' => $this->mobile_content->promotions(),
            'portfolios' => $this->mobile_content->portfolios(),
            'designs' => $this->db->where('is_active', 1)->order_by('name', 'ASC')->get('design_types')->result(),
            'page_css' => ['admin.css', 'mobile-content.css'],
            'page_js' => ['mobile-content.js'],
        ], $extra);
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('mobile_content/index', $data);
        $this->load->view('layout/footer');
    }

    public function index()
    {
        $this->render();
    }

    private function upload_image($field, $folder)
    {
        if (empty($_FILES[$field]['name'])) return null;
        $result = vi_upload_image($field, $folder, [
            'max_size_kb' => 10240,
            'max_width' => 1600,
            'max_height' => 1600,
            'quality' => 78,
        ]);
        if (empty($result['ok'])) return ['error' => $result['error'] ?? 'Gagal upload gambar.'];
        return ['path' => trim($folder, '/') . '/' . $result['file_name']];
    }

    private function remove_local_file($path)
    {
        $path = trim((string)$path, '/');
        if ($path === '' || strpos($path, '..') !== false) return;
        $full = FCPATH . $path;
        if (is_file($full)) @unlink($full);
    }

    private function redirect_with($type, $message, $anchor = '')
    {
        $this->session->set_flashdata($type, $message);
        redirect('app-content' . ($anchor ? '#' . $anchor : ''));
    }

    public function save_settings()
    {
        require_post();
        $fields = $this->public_setting_keys;
        $data = [];
        foreach ($fields as $key) $data[$key] = trim((string)$this->input->post($key, true));
        $data['dp_percent'] = (string)max(1, min(100, (int)$data['dp_percent']));
        $data['revision_free_limit'] = (string)max(0, min(20, (int)$data['revision_free_limit']));
        foreach (['extra_revision_fee','cdr_fee_per_design','exclusive_license_fee_per_head','complex_background_fee','express_fee'] as $money) {
            $data[$money] = (string)max(0, (int)preg_replace('/\D+/', '', $data[$money]));
        }
        $this->mobile_content->save_settings($data, $this->public_setting_keys);
        $this->redirect_with('app_content_ok', 'Pengaturan tampilan dan harga aplikasi berhasil disimpan.', 'settings');
    }

    public function save_banner($id = 0)
    {
        require_post();
        $row = $id ? $this->mobile_content->banner($id) : null;
        $title = trim((string)$this->input->post('title', true));
        if ($title === '') $this->redirect_with('app_content_err', 'Judul banner wajib diisi.', 'banners');
        $upload = $this->upload_image('image', 'assets/uploads/mobile/banners');
        if (isset($upload['error'])) $this->redirect_with('app_content_err', $upload['error'], 'banners');
        $data = [
            'title' => $title,
            'subtitle' => trim((string)$this->input->post('subtitle', true)) ?: null,
            'button_text' => trim((string)$this->input->post('button_text', true)) ?: 'Pesan Sekarang',
            'action_type' => $this->normalize_action($this->input->post('action_type', true), 'ORDER'),
            'action_value' => trim((string)$this->input->post('action_value', true)) ?: null,
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->input->post('sort_order'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($upload && !empty($upload['path'])) {
            if ($row && !empty($row->image_path)) $this->remove_local_file($row->image_path);
            $data['image_path'] = $upload['path'];
        }
        $this->mobile_content->save_banner((int)$id, $data);
        $this->redirect_with('app_content_ok', 'Banner berhasil disimpan.', 'banners');
    }

    private function normalize_datetime($value)
    {
        $value = trim((string)$value);
        if ($value === '') return null;
        $timestamp = strtotime(str_replace('T', ' ', $value));
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private function normalize_action($value, $fallback)
    {
        $value = strtoupper(trim((string)$value));
        return in_array($value, ['ORDER','CATALOG','PORTFOLIO','URL','NONE'], true) ? $value : $fallback;
    }

    public function save_promotion($id = 0)
    {
        require_post();
        $row = $id ? $this->mobile_content->promotion($id) : null;
        $title = trim((string)$this->input->post('title', true));
        if ($title === '') $this->redirect_with('app_content_err', 'Judul promosi wajib diisi.', 'promotions');
        $upload = $this->upload_image('image', 'assets/uploads/mobile/promotions');
        if (isset($upload['error'])) $this->redirect_with('app_content_err', $upload['error'], 'promotions');
        $data = [
            'title' => $title,
            'description' => trim((string)$this->input->post('description', true)) ?: null,
            'badge_text' => trim((string)$this->input->post('badge_text', true)) ?: null,
            'button_text' => trim((string)$this->input->post('button_text', true)) ?: 'Lihat Promo',
            'action_type' => $this->normalize_action($this->input->post('action_type', true), 'CATALOG'),
            'action_value' => trim((string)$this->input->post('action_value', true)) ?: null,
            'starts_at' => $this->normalize_datetime($this->input->post('starts_at', true)),
            'ends_at' => $this->normalize_datetime($this->input->post('ends_at', true)),
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->input->post('sort_order'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($upload && !empty($upload['path'])) {
            if ($row && !empty($row->image_path)) $this->remove_local_file($row->image_path);
            $data['image_path'] = $upload['path'];
        }
        $this->mobile_content->save_promotion((int)$id, $data);
        $this->redirect_with('app_content_ok', 'Promosi berhasil disimpan.', 'promotions');
    }

    public function save_portfolio($id = 0)
    {
        require_post();
        $row = $id ? $this->mobile_content->portfolio($id) : null;
        $title = trim((string)$this->input->post('title', true));
        if ($title === '') $this->redirect_with('app_content_err', 'Judul portofolio wajib diisi.', 'portfolios');
        $upload = $this->upload_image('image', 'assets/uploads/mobile/portfolios');
        if (isset($upload['error'])) $this->redirect_with('app_content_err', $upload['error'], 'portfolios');
        if (!$row && (!$upload || empty($upload['path']))) $this->redirect_with('app_content_err', 'Gambar portofolio wajib diunggah.', 'portfolios');
        $data = [
            'design_type_id' => (int)$this->input->post('design_type_id') ?: null,
            'title' => $title,
            'description' => trim((string)$this->input->post('description', true)) ?: null,
            'is_featured' => $this->input->post('is_featured') ? 1 : 0,
            'is_active' => $this->input->post('is_active') ? 1 : 0,
            'sort_order' => (int)$this->input->post('sort_order'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($upload && !empty($upload['path'])) {
            if ($row && !empty($row->image_path)) $this->remove_local_file($row->image_path);
            $data['image_path'] = $upload['path'];
        }
        $this->mobile_content->save_portfolio((int)$id, $data);
        $this->redirect_with('app_content_ok', 'Portofolio berhasil disimpan.', 'portfolios');
    }

    public function delete($type, $id)
    {
        require_post();
        $map = ['banner' => 'mobile_banners', 'promotion' => 'mobile_promotions', 'portfolio' => 'mobile_portfolios'];
        if (!isset($map[$type])) show_404();
        $row = $this->db->get_where($map[$type], ['id' => (int)$id])->row();
        if ($row && !empty($row->image_path)) $this->remove_local_file($row->image_path);
        $this->mobile_content->delete_row($map[$type], (int)$id);
        $this->redirect_with('app_content_ok', ucfirst($type) . ' berhasil dihapus.');
    }
}
