<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Design_model extends CI_Model
{
    /** Tampilkan desain aktif saja. Tombol Hapus memakai soft-delete agar riwayat order tetap aman. */
    public function all($q = null)
    {
        $this->db->where('is_active', 1);
        if ($q !== null && trim($q) !== '') {
            $this->db->like('name', trim($q), 'after');
        }
        return $this->db->order_by('id', 'DESC')->get('design_types')->result();
    }

    public function find($id)
    {
        return $this->db->get_where('design_types', ['id' => (int)$id])->row();
    }


    /** Cari desain berdasarkan nama tanpa membedakan huruf besar/kecil, termasuk data nonaktif. */
    public function find_by_name($name)
    {
        $name = strtolower(trim((string)$name));
        if ($name === '') return null;

        return $this->db
            ->where('LOWER(name)', $name)
            ->limit(1)
            ->get('design_types')
            ->row();
    }

    /** Aktifkan ulang data nonaktif agar tidak nyangkut sebagai duplikat tersembunyi. */
    public function reactivate($id, array $data = [])
    {
        $data['is_active'] = 1;
        return $this->db->where('id', (int)$id)->update('design_types', $data);
    }

    public function create($data)
    {
        return $this->db->insert('design_types', $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('id', (int)$id)->update('design_types', $data);
    }

    /** Soft delete agar histori order tidak rusak. */
    public function deactivate($id)
    {
        return $this->db->where('id', (int)$id)->update('design_types', ['is_active' => 0]);
    }

    /** Cek nama sudah ada atau belum (case-insensitive). */
    public function exists_name($name, $exclude_id = null)
    {
        $name = strtolower(trim($name));
        $this->db->where('LOWER(name)', $name);

        if ($exclude_id !== null) {
            $this->db->where('id !=', (int)$exclude_id);
        }

        return $this->db->count_all_results('design_types') > 0;
    }
}
