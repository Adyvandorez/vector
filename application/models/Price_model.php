<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Price_model extends CI_Model
{
    public function body_parts()
    {
        return $this->db->where('is_active', 1)->order_by('name', 'ASC')->get('body_parts')->result();
    }

    /** Form order hanya menampilkan desain aktif. */
    public function design_types()
    {
        return $this->db->where('is_active', 1)->order_by('name', 'ASC')->get('design_types')->result();
    }

    /** LEFT JOIN menjaga data harga tetap tampil meski master data pernah berubah. */
    public function all($q = null)
    {
        $this->db->select('pm.*, dt.name as design_name, dt.preview_image, dt.preview_thumb, dt.preview_storage, dt.preview_drive_id, dt.preview_drive_url, bp.name as body_name');
        $this->db->from('price_matrix pm');
        $this->db->join('design_types dt', 'dt.id=pm.design_type_id', 'left');
        $this->db->join('body_parts bp', 'bp.id=pm.body_part_id', 'left');

        if ($q !== null && trim($q) !== '') {
            $q = trim($q);
            $this->db->group_start();
            $this->db->like('dt.name', $q, 'after');
            $this->db->or_like('bp.name', $q, 'after');
            if (ctype_digit(preg_replace('/[^0-9]/', '', $q))) {
                $this->db->or_where('pm.base_price', rupiah_number($q));
            }
            $this->db->group_end();
        }

        $this->db->order_by('pm.id', 'DESC');
        return $this->db->get()->result();
    }

    public function find($id)
    {
        return $this->db->get_where('price_matrix', ['id' => (int)$id])->row();
    }

    public function upsert($design_type_id, $body_part_id, $base_price)
    {
        $row = $this->db->get_where('price_matrix', [
            'design_type_id' => (int)$design_type_id,
            'body_part_id' => (int)$body_part_id
        ])->row();

        if ($row) {
            return $this->db->where('id', $row->id)->update('price_matrix', [
                'base_price' => (int)$base_price,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        return $this->db->insert('price_matrix', [
            'design_type_id' => (int)$design_type_id,
            'body_part_id' => (int)$body_part_id,
            'base_price' => (int)$base_price,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function delete($id)
    {
        return $this->db->where('id', (int)$id)->delete('price_matrix');
    }

    public function get_base_price($design_type_id, $body_part_id)
    {
        $row = $this->db->get_where('price_matrix', [
            'design_type_id' => (int)$design_type_id,
            'body_part_id' => (int)$body_part_id
        ])->row();
        return $row ? (int)$row->base_price : 0;
    }

    public function update_by_id($id, $data)
    {
        return $this->db->where('id', (int)$id)->update('price_matrix', $data);
    }
}
