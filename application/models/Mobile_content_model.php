<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Mobile_content_model extends CI_Model
{
    public function public_settings()
    {
        $result = [];
        if (!$this->db->table_exists('app_settings')) return $result;
        foreach ($this->db->where('is_public', 1)->order_by('setting_key', 'ASC')->get('app_settings')->result() as $row) {
            $result[$row->setting_key] = $row->setting_value;
        }
        return $result;
    }

    public function all_settings()
    {
        $result = [];
        if (!$this->db->table_exists('app_settings')) return $result;
        foreach ($this->db->order_by('setting_key', 'ASC')->get('app_settings')->result() as $row) {
            $result[$row->setting_key] = $row->setting_value;
        }
        return $result;
    }

    public function save_settings(array $settings, array $public_keys = [])
    {
        if (!$this->db->table_exists('app_settings')) return false;
        $now = date('Y-m-d H:i:s');
        foreach ($settings as $key => $value) {
            $key = trim((string)$key);
            if ($key === '') continue;
            $row = $this->db->get_where('app_settings', ['setting_key' => $key])->row();
            $data = [
                'setting_value' => is_scalar($value) ? trim((string)$value) : json_encode($value),
                'is_public' => in_array($key, $public_keys, true) ? 1 : 0,
                'updated_at' => $now,
            ];
            if ($row) $this->db->where('id', (int)$row->id)->update('app_settings', $data);
            else {
                $data['setting_key'] = $key;
                $this->db->insert('app_settings', $data);
            }
        }
        return true;
    }

    public function banners($active_only = false)
    {
        if (!$this->db->table_exists('mobile_banners')) return [];
        if ($active_only) $this->db->where('is_active', 1);
        return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'DESC')->get('mobile_banners')->result();
    }

    public function banner($id)
    {
        return $this->db->table_exists('mobile_banners')
            ? $this->db->get_where('mobile_banners', ['id' => (int)$id])->row()
            : null;
    }

    public function save_banner($id, array $data)
    {
        if (!$this->db->table_exists('mobile_banners')) return false;
        if ($id) return $this->db->where('id', (int)$id)->update('mobile_banners', $data);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('mobile_banners', $data);
    }

    public function promotions($active_only = false)
    {
        if (!$this->db->table_exists('mobile_promotions')) return [];
        if ($active_only) {
            $now = date('Y-m-d H:i:s');
            $this->db->where('is_active', 1);
            $this->db->group_start()->where('starts_at IS NULL', null, false)->or_where('starts_at <=', $now)->group_end();
            $this->db->group_start()->where('ends_at IS NULL', null, false)->or_where('ends_at >=', $now)->group_end();
        }
        return $this->db->order_by('sort_order', 'ASC')->order_by('id', 'DESC')->get('mobile_promotions')->result();
    }

    public function promotion($id)
    {
        return $this->db->table_exists('mobile_promotions')
            ? $this->db->get_where('mobile_promotions', ['id' => (int)$id])->row()
            : null;
    }

    public function save_promotion($id, array $data)
    {
        if (!$this->db->table_exists('mobile_promotions')) return false;
        if ($id) return $this->db->where('id', (int)$id)->update('mobile_promotions', $data);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('mobile_promotions', $data);
    }

    public function portfolios($active_only = false)
    {
        if (!$this->db->table_exists('mobile_portfolios')) return [];
        $this->db->select('mp.*, dt.name AS design_name');
        $this->db->from('mobile_portfolios mp');
        $this->db->join('design_types dt', 'dt.id=mp.design_type_id', 'left');
        if ($active_only) $this->db->where('mp.is_active', 1);
        return $this->db->order_by('mp.sort_order', 'ASC')->order_by('mp.id', 'DESC')->get()->result();
    }

    public function portfolio($id)
    {
        return $this->db->table_exists('mobile_portfolios')
            ? $this->db->get_where('mobile_portfolios', ['id' => (int)$id])->row()
            : null;
    }

    public function save_portfolio($id, array $data)
    {
        if (!$this->db->table_exists('mobile_portfolios')) return false;
        if ($id) return $this->db->where('id', (int)$id)->update('mobile_portfolios', $data);
        $data['created_at'] = date('Y-m-d H:i:s');
        return $this->db->insert('mobile_portfolios', $data);
    }

    public function delete_row($table, $id)
    {
        $allowed = ['mobile_banners', 'mobile_promotions', 'mobile_portfolios'];
        if (!in_array($table, $allowed, true) || !$this->db->table_exists($table)) return false;
        return $this->db->where('id', (int)$id)->delete($table);
    }
}
