<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Client_model extends CI_Model
{
    public function all($q = null, $active = null)
    {
        $this->db->select('c.*, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total),0) AS total_value');
        $this->db->from('clients c');
        $this->db->join('orders o', 'o.client_id=c.id', 'left');

        if ($q !== null && trim($q) !== '') {
            $q = trim($q);
            $this->db->group_start()
                ->like('c.name', $q)
                ->or_like('c.phone', $q)
                ->or_like('c.email', $q)
                ->group_end();
        }

        if ($active !== null && $active !== '') {
            $this->db->where('c.is_active', (int)$active);
        }

        $this->db->group_by('c.id');
        $this->db->order_by('c.id', 'DESC');
        return $this->db->get()->result();
    }

    public function active_options()
    {
        return $this->db
            ->where('is_active', 1)
            ->order_by('name', 'ASC')
            ->get('clients')
            ->result();
    }

    public function find_or_create($name, $phone = null, $email = null)
    {
        $name = trim((string)$name);
        $phone = normalize_phone($phone);
        $email = strtolower(trim((string)$email));
        if ($name === '') return null;

        if ($email !== '') {
            $row = $this->find_by_email($email);
            if ($row) return (int)$row->id;
        }

        if ($phone !== '') {
            $row = $this->find_by_phone($phone);
            if ($row) return (int)$row->id;
        }

        $data = [
            'name' => $name,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('clients', $data);
        return (int)$this->db->insert_id();
    }

    public function find($id)
    {
        return $this->db->get_where('clients', ['id' => (int)$id])->row();
    }

    public function find_with_summary($id)
    {
        $this->db->select('c.*, COUNT(o.id) AS total_orders, COALESCE(SUM(o.total),0) AS total_value, COALESCE(SUM(o.paid),0) AS total_paid');
        $this->db->from('clients c');
        $this->db->join('orders o', 'o.client_id=c.id', 'left');
        $this->db->where('c.id', (int)$id);
        $this->db->group_by('c.id');
        return $this->db->get()->row();
    }

    public function orders($id)
    {
        return $this->db
            ->where('client_id', (int)$id)
            ->order_by('created_at', 'DESC')
            ->get('orders')
            ->result();
    }

    public function create(array $data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return $this->db->insert('clients', $data);
    }

    public function update($id, array $data)
    {
        return $this->db->where('id', (int)$id)->update('clients', $data);
    }

    public function toggle_active($id, $active)
    {
        return $this->update($id, ['is_active' => (int)$active]);
    }

    public function find_by_name($name)
    {
        return $this->db
            ->where('LOWER(name)', strtolower(trim((string)$name)))
            ->limit(1)
            ->get('clients')
            ->row();
    }

    public function find_by_email($email)
    {
        $email = strtolower(trim((string)$email));
        if ($email === '') return null;
        return $this->db->where('LOWER(email)', $email)->limit(1)->get('clients')->row();
    }

    public function find_by_phone($phone)
    {
        $phone = normalize_phone($phone);
        if ($phone === '') return null;
        return $this->db->where('phone', $phone)->limit(1)->get('clients')->row();
    }

    public function email_exists($email, $exclude_id = null)
    {
        $email = strtolower(trim((string)$email));
        if ($email === '') return false;
        $this->db->where('LOWER(email)', $email);
        if ($exclude_id !== null) $this->db->where('id !=', (int)$exclude_id);
        return $this->db->count_all_results('clients') > 0;
    }
}
