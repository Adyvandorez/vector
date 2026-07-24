<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Home_content_api extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Mobile_content_model', 'mobile_content');
    }

    private function image_url($path)
    {
        $path = trim((string)$path);
        if ($path === '') return null;
        if (preg_match('#^https?://#i', $path)) return $path;
        return base_url(trim($path, '/'));
    }

    public function index()
    {
        $settings = $this->mobile_content->public_settings();
        $banners = array_map(function ($row) {
            return [
                'id' => (int)$row->id,
                'title' => $row->title,
                'subtitle' => $row->subtitle,
                'button_text' => $row->button_text,
                'action_type' => $row->action_type,
                'action_value' => $row->action_value,
                'image_url' => $this->image_url($row->image_path),
                'sort_order' => (int)$row->sort_order,
            ];
        }, $this->mobile_content->banners(true));

        $promotions = array_map(function ($row) {
            return [
                'id' => (int)$row->id,
                'title' => $row->title,
                'description' => $row->description,
                'badge_text' => $row->badge_text,
                'button_text' => $row->button_text,
                'action_type' => $row->action_type,
                'action_value' => $row->action_value,
                'image_url' => $this->image_url($row->image_path),
                'starts_at' => $row->starts_at,
                'ends_at' => $row->ends_at,
            ];
        }, $this->mobile_content->promotions(true));

        $portfolios = array_map(function ($row) {
            return [
                'id' => (int)$row->id,
                'design_type_id' => $row->design_type_id ? (int)$row->design_type_id : null,
                'design_name' => $row->design_name,
                'title' => $row->title,
                'description' => $row->description,
                'image_url' => $this->image_url($row->image_path),
                'is_featured' => (bool)$row->is_featured,
                'sort_order' => (int)$row->sort_order,
            ];
        }, $this->mobile_content->portfolios(true));

        return $this->success([
            'settings' => $settings,
            'banners' => $banners,
            'promotions' => $promotions,
            'portfolios' => $portfolios,
        ]);
    }
}
