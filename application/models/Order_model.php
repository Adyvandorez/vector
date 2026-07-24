<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Order_model extends CI_Model
{
    /** Daftar order dengan pencarian dan filter operasional. */
    public function all($q = null, $status = null, $source = null)
    {
        $this->db->select('o.*, c.name as client_name, c.phone as client_phone, c.email as client_email, dt.name as design_name, bp.name as body_name');
        $this->db->from('orders o');
        $this->db->join('clients c', 'c.id=o.client_id', 'left');
        $this->db->join('design_types dt', 'dt.id=o.design_type_id', 'left');
        $this->db->join('body_parts bp', 'bp.id=o.body_part_id', 'left');

        if ($q !== null && trim($q) !== '') {
            $q = trim($q);
            $this->db->group_start();
            $this->db->like('o.order_code', $q);
            $this->db->or_like('o.title', $q);
            $this->db->or_like('c.name', $q);
            $this->db->or_like('c.phone', $q);
            $this->db->group_end();
        }
        if ($status !== null && $status !== '') {
            $this->db->where('o.status', $status === 'LUNAS' ? 'SELESAI' : $status);
        }
        if ($source !== null && $source !== '') {
            $this->db->where('o.created_via', $source);
        }

        $this->db->order_by("CASE WHEN o.deadline IS NULL OR CAST(o.deadline AS CHAR) = '0000-00-00' OR CAST(o.deadline AS CHAR) = '' THEN 1 ELSE 0 END", 'ASC', false);
        $this->db->order_by("COALESCE(STR_TO_DATE(NULLIF(NULLIF(CAST(o.deadline AS CHAR), '0000-00-00'), ''), '%Y-%m-%d'), DATE(o.created_at))", 'DESC', false);
        $this->db->order_by('o.created_at', 'DESC');
        return $this->db->get()->result();
    }

    public function find($id)
    {
        return $this->db->get_where('orders', ['id' => (int)$id])->row();
    }

    /** Detail order untuk halaman view. */
    public function detail($id)
    {
        $this->db->select('o.*, c.name as client_name, c.phone as client_phone, c.email as client_email, c.address as client_address, dt.name as design_name, bp.name as body_name');
        $this->db->from('orders o');
        $this->db->join('clients c', 'c.id=o.client_id', 'left');
        $this->db->join('design_types dt', 'dt.id=o.design_type_id', 'left');
        $this->db->join('body_parts bp', 'bp.id=o.body_part_id', 'left');
        $this->db->where('o.id', (int)$id);
        return $this->db->get()->row();
    }

    public function create($data)
    {
        return $this->db->insert('orders', $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('id', (int)$id)->update('orders', $data);
    }

    /** Hapus order beserta relasi dan file fisik preview/final. */
    public function delete_with_files($id)
    {
        $id = (int)$id;
        $files = $this->files($id);

        $this->db->trans_start();
        if ($this->db->table_exists('order_payments')) {
            $this->db->where('order_id', $id)->delete('order_payments');
        }
        $this->db->where('order_id', $id)->delete('order_items');
        $this->db->where('order_id', $id)->delete('order_revisions');
        $this->db->where('order_id', $id)->delete('invoices');
        $this->db->where('order_id', $id)->delete('order_files');
        $this->db->where('id', $id)->delete('orders');
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return false;
        }

        $this->delete_physical_files($files);
        return true;
    }

    /** Hapus file fisik lokal dan file Drive jika tersedia. */
    public function delete_physical_files($files)
    {
        $ci =& get_instance();
        $ci->load->library('google_drive_storage');

        foreach ($files as $f) {
            if (!empty($f->file_name)) {
                $dir = ($f->file_type === 'FINAL') ? 'assets/uploads/finals/' : (($f->file_type === 'REFERENCE') ? 'assets/uploads/references/' : 'assets/uploads/previews/');
                $path = FCPATH . $dir . $f->file_name;
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            if (!empty($f->thumb_path)) {
                $thumb = FCPATH . trim((string)$f->thumb_path, '/');
                if (is_file($thumb)) {
                    @unlink($thumb);
                }
            }

            if (!empty($f->drive_file_id)) {
                $ci->google_drive_storage->delete_file($f->drive_file_id);
            }
        }
    }

    public function files($order_id)
    {
        return $this->db
            ->order_by('id', 'DESC')
            ->get_where('order_files', ['order_id' => (int)$order_id])
            ->result();
    }

    public function add_file($order_id, $file_type, $file_name, $original_name, $drive = [], array $extra = [])
    {
        $data = [
            'order_id' => (int)$order_id,
            'file_type' => $file_type,
            'file_name' => $file_name,
            'original_name' => $original_name,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($this->db->field_exists('thumb_path', 'order_files') && isset($extra['thumb_path'])) {
            $data['thumb_path'] = $extra['thumb_path'];
        }

        if ($this->db->field_exists('file_size', 'order_files') && isset($extra['file_size'])) {
            $data['file_size'] = (int)$extra['file_size'];
        }

        if ($this->db->field_exists('mime_type', 'order_files') && isset($extra['mime_type'])) {
            $data['mime_type'] = $extra['mime_type'];
        }

        if ($this->db->field_exists('uploaded_by', 'order_files')) {
            $data['uploaded_by'] = $extra['uploaded_by'] ?? 'ADMIN';
        }
        if ($this->db->field_exists('is_visible_to_client', 'order_files')) {
            $data['is_visible_to_client'] = isset($extra['is_visible_to_client']) ? (int)$extra['is_visible_to_client'] : ($file_type === 'SOURCE' ? 0 : 1);
        }

        if ($this->db->field_exists('storage', 'order_files')) {
            $data['storage'] = !empty($drive['id']) ? 'drive' : 'local';
            $data['drive_file_id'] = $drive['id'] ?? null;
            $data['drive_url'] = $drive['public_url'] ?? null;
        }

        return $this->db->insert('order_files', $data);
    }

    /* ================= PEMBAYARAN BERTAHAP ================= */

    public function payments($order_id)
    {
        if (!$this->db->table_exists('order_payments')) return [];
        return $this->db
            ->where('order_id', (int)$order_id)
            ->order_by('payment_date', 'DESC')
            ->order_by('id', 'DESC')
            ->get('order_payments')
            ->result();
    }

    public function payment_find($payment_id)
    {
        if (!$this->db->table_exists('order_payments')) return null;
        return $this->db->get_where('order_payments', ['id' => (int)$payment_id])->row();
    }

    public function add_payment($order_id, $amount, $note = '', $payment_date = null, $source = 'MANUAL')
    {
        if (!$this->db->table_exists('order_payments')) return false;
        $payment_date = $payment_date ?: date('Y-m-d');
        $ok = $this->db->insert('order_payments', [
            'order_id' => (int)$order_id,
            'amount' => (int)$amount,
            'note' => trim((string)$note),
            'payment_date' => $payment_date,
            'source' => $source,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        if ($ok) {
            $this->sync_paid_from_payments($order_id);
        }
        return $ok;
    }

    public function delete_payment($payment_id)
    {
        $payment = $this->payment_find($payment_id);
        if (!$payment) return false;

        $this->db->where('id', (int)$payment_id)->delete('order_payments');
        $this->sync_paid_from_payments((int)$payment->order_id);
        return true;
    }

    /** Sinkronkan orders.paid dari riwayat pembayaran agar query lama tetap kompatibel. */
    public function sync_paid_from_payments($order_id)
    {
        $order_id = (int)$order_id;
        $order = $this->find($order_id);
        if (!$order) return 0;

        if (!$this->db->table_exists('order_payments')) return (int)$order->paid;
        $row = $this->db->select_sum('amount')->get_where('order_payments', ['order_id' => $order_id])->row();
        $sum = (int)($row->amount ?? 0);
        $paid = min($sum, (int)$order->total);

        $status = $order->status === 'LUNAS' ? 'SELESAI' : ($order->status ?: 'MASUK');
        $this->db->where('id', $order_id)->update('orders', [
            'paid' => $paid,
            'status' => $status
        ]);
        return $paid;
    }

    public function payment_summary($order_id)
    {
        $order = $this->find($order_id);
        $paid = $order ? (int)$order->paid : 0;
        $total = $order ? (int)$order->total : 0;
        return [
            'total' => $total,
            'paid' => $paid,
            'remaining' => max(0, $total - $paid),
            'payment_status' => $paid <= 0 ? 'BELUM BAYAR' : ($paid >= $total && $total > 0 ? 'LUNAS' : 'BELUM LUNAS')
        ];
    }

    public function revisions($order_id)
    {
        return $this->db
            ->order_by('id', 'DESC')
            ->get_where('order_revisions', ['order_id' => (int)$order_id])
            ->result();
    }

    public function add_revision($order_id, $note, $fee, $source = 'ADMIN')
    {
        $order_id = (int)$order_id;
        $fee = (int)$fee;

        $this->db->insert('order_revisions', [
            'order_id' => $order_id,
            'note' => $note,
            'fee' => $fee,
            'source' => $source,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $this->db->set('revision_count', 'revision_count+1', false);
        $this->db->set('revision_fee', 'revision_fee + ' . $fee, false);
        $this->db->where('id', $order_id)->update('orders');
    }

    /** Buat invoice jika belum ada. Nomor invoice dibuat urut harian agar profesional. */
    public function ensure_invoice($order_id)
    {
        $order_id = (int)$order_id;
        $row = $this->db->get_where('invoices', ['order_id' => $order_id])->row();
        if ($row) return $row->id;

        $prefix = 'INV-' . date('Ymd') . '-';
        $last = $this->db
            ->like('invoice_no', $prefix, 'after')
            ->order_by('invoice_no', 'DESC')
            ->limit(1)
            ->get('invoices')
            ->row();

        $next = 1;
        if ($last && preg_match('/-(\d{4})$/', $last->invoice_no, $m)) {
            $next = (int)$m[1] + 1;
        }

        $no = $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
        $this->db->insert('invoices', [
            'invoice_no' => $no,
            'order_id' => $order_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->db->insert_id();
    }

    public function invoice($order_id)
    {
        $this->db->select('i.*, o.order_code');
        $this->db->from('invoices i');
        $this->db->join('orders o', 'o.id=i.order_id');
        $this->db->where('i.order_id', (int)$order_id);
        return $this->db->get()->row();
    }

    public function items($order_id)
    {
        $this->db->select('oi.*, dt.name as design_name, dt.preview_image, dt.preview_thumb, dt.preview_storage, dt.preview_drive_id, dt.preview_drive_url, bp.name as body_name');
        $this->db->from('order_items oi');
        $this->db->join('design_types dt', 'dt.id=oi.design_type_id', 'left');
        $this->db->join('body_parts bp', 'bp.id=oi.body_part_id', 'left');
        $this->db->where('oi.order_id', (int)$order_id);
        $this->db->order_by('oi.id', 'ASC');
        return $this->db->get()->result();
    }

    /** Ganti semua item order. */
    public function replace_items($order_id, $items)
    {
        $order_id = (int)$order_id;
        $this->db->where('order_id', $order_id)->delete('order_items');

        foreach ($items as $it) {
            $this->db->insert('order_items', [
                'order_id' => $order_id,
                'design_type_id' => (int)$it['design_type_id'],
                'body_part_id' => (int)$it['body_part_id'],
                'qty' => (int)$it['qty'],
                'price' => (int)$it['price'],
                'note' => $it['note'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function revision_find($rev_id)
    {
        return $this->db->get_where('order_revisions', ['id' => (int)$rev_id])->row();
    }

    public function revision_update($rev_id, $note, $fee)
    {
        return $this->db->where('id', (int)$rev_id)->update('order_revisions', [
            'note' => $note,
            'fee' => (int)$fee
        ]);
    }

    public function revision_delete($rev_id)
    {
        return $this->db->where('id', (int)$rev_id)->delete('order_revisions');
    }

    public function revision_sum_fee($order_id)
    {
        $row = $this->db->select_sum('fee')->get_where('order_revisions', ['order_id' => (int)$order_id])->row();
        return (int)($row->fee ?? 0);
    }

    public function revision_count($order_id)
    {
        return (int)$this->db->where('order_id', (int)$order_id)->count_all_results('order_revisions');
    }

    public function status_histories($order_id)
    {
        if (!$this->db->table_exists('order_status_histories')) return [];
        return $this->db->where('order_id', (int)$order_id)->order_by('created_at', 'DESC')->order_by('id', 'DESC')->get('order_status_histories')->result();
    }

    public function record_status_change($order_id, $old_status, $new_status, $changed_by_type = 'ADMIN', $changed_by_id = null, $note = null)
    {
        $old_status = $old_status === 'LUNAS' ? 'SELESAI' : $old_status;
        $new_status = $new_status === 'LUNAS' ? 'SELESAI' : $new_status;
        if ($old_status === $new_status || !$this->db->table_exists('order_status_histories')) return true;

        $ok = $this->db->insert('order_status_histories', [
            'order_id' => (int)$order_id,
            'old_status' => $old_status ?: null,
            'new_status' => $new_status,
            'changed_by_type' => $changed_by_type,
            'changed_by_id' => $changed_by_id !== null ? (int)$changed_by_id : null,
            'note' => $note,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        if ($ok) $this->create_status_notification($order_id, $new_status);
        return $ok;
    }

    private function create_status_notification($order_id, $status)
    {
        if (!$this->db->table_exists('client_notifications')) return;
        $order = $this->find($order_id);
        if (!$order) return;
        $messages = [
            'MASUK' => 'Pesanan telah diterima dan menunggu diproses.',
            'PROSES' => 'Pesanan sedang dalam proses pengerjaan.',
            'REVISI' => 'Pesanan berada pada tahap revisi.',
            'SELESAI' => 'Pesanan telah selesai dikerjakan.'
        ];
        $this->db->insert('client_notifications', [
            'client_id' => (int)$order->client_id,
            'order_id' => (int)$order_id,
            'type' => 'ORDER_STATUS',
            'title' => 'Status ' . $order->order_code . ': ' . $status,
            'message' => $messages[$status] ?? ('Status pesanan berubah menjadi ' . $status . '.'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
