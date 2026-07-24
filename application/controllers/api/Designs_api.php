<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Designs_api extends MY_Controller
{
    private function map_design($d)
    {
        $this->db->select('pm.id, pm.body_part_id, pm.base_price, bp.name AS body_part_name');
        $this->db->from('price_matrix pm')->join('body_parts bp', 'bp.id=pm.body_part_id');
        $this->db->where('pm.design_type_id', (int)$d->id)->where('bp.is_active', 1)->order_by('pm.base_price', 'ASC');
        $options = [];
        foreach ($this->db->get()->result() as $p) $options[] = [
            'price_id' => (int)$p->id, 'body_part_id' => (int)$p->body_part_id,
            'body_part_name' => $p->body_part_name, 'base_price' => (int)$p->base_price
        ];
        $prices = array_column($options, 'base_price');
        return [
            'id' => (int)$d->id, 'name' => $d->name,
            'description' => $d->description ?: 'Layanan desain dari Ady_vandorez.',
            'image_url' => vi_design_preview_url($d) ?: null,
            'min_price' => $prices ? min($prices) : 0,
            'options' => $options
        ];
    }

    public function index()
    {
        $q = trim((string)$this->input->get('q'));
        $this->db->where('is_active', 1);
        if ($q !== '') $this->db->like('name', $q);
        $rows = $this->db->order_by('name', 'ASC')->get('design_types')->result();
        return $this->success(array_map([$this, 'map_design'], $rows));
    }

    public function show($id)
    {
        $row = $this->db->get_where('design_types', ['id'=>(int)$id, 'is_active'=>1])->row();
        if (!$row) return $this->fail('Desain tidak ditemukan.', 404);
        return $this->success($this->map_design($row));
    }
}
