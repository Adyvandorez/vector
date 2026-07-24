<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Config_api extends MY_Controller
{
    public function index()
    {
        $settings = [];
        if ($this->db->table_exists('app_settings')) {
            foreach ($this->db->where('is_public', 1)->get('app_settings')->result() as $row) {
                $settings[$row->setting_key] = $row->setting_value;
            }
        }
        $methods = [];
        if ($this->db->table_exists('payment_methods')) {
            foreach ($this->db->where('is_active', 1)->order_by('sort_order', 'ASC')->get('payment_methods')->result() as $m) {
                $methods[] = [
                    'id' => (int)$m->id,
                    'type' => $m->type,
                    'name' => $m->name,
                    'account_number' => $m->account_number,
                    'account_holder' => $m->account_holder,
                    'instructions' => $m->instructions,
                    'qr_image_url' => $m->qr_image ? base_url(trim($m->qr_image, '/')) : null,
                ];
            }
        }
        return $this->success([
            'brand_name' => $settings['brand_name'] ?? 'Ady_vandorez',
            'app_name' => $settings['app_name'] ?? 'Vector Order',
            'tagline' => $settings['tagline'] ?? 'Vector Order & Invoice',
            'linktree_url' => $settings['linktree_url'] ?? 'https://linktr.ee/Ady_vandorez',
            'support_whatsapp' => $settings['support_whatsapp'] ?? '085236222785',
            'currency' => 'IDR',
            'payment_methods' => $methods,
            'order_statuses' => ['MASUK','PROSES','REVISI','SELESAI']
        ]);
    }
}
