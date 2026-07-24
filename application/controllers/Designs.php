<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Designs extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Design_model', 'designs');
        $this->load->library('google_drive_storage');
    }

    private function db_has_design_drive_columns()
    {
        return $this->db->field_exists('preview_drive_id', 'design_types')
            && $this->db->field_exists('preview_storage', 'design_types');
    }

    private function db_has_design_thumb_column()
    {
        return $this->db->field_exists('preview_thumb', 'design_types');
    }

    private function db_has_design_source_columns()
    {
        return $this->db->field_exists('source_drive_id', 'design_types')
            && $this->db->field_exists('source_original_name', 'design_types');
    }

    private function upload_preview($design_name = '')
    {
        if (empty($_FILES['preview']['name'])) return null;

        $res = vi_upload_image('preview', 'assets/uploads/design_previews', [
            'max_size_kb' => 8192,
            'max_width' => 900,
            'max_height' => 900,
            'quality' => 50,
            'thumb_dir' => 'assets/uploads/cache/designs',
            'thumb_width' => 360,
            'thumb_height' => 360,
            'thumb_quality' => 42,
        ]);

        if (empty($res['ok'])) {
            $this->session->set_flashdata('designs_err', $res['error'] ?? 'Gagal upload gambar.');
            return false;
        }

        $upload = [
            'file_name' => $res['file_name'],
            'original_name' => $res['original_name'] ?? $res['file_name'],
            'thumb_path' => $res['thumb_path'] ?? null,
            'drive' => null,
            'drive_error' => null,
        ];

        $localPath = FCPATH . 'assets/uploads/design_previews/' . $res['file_name'];
        $driveName = $this->google_drive_storage->safe_filename(($design_name ?: 'Jenis Desain') . '-' . $res['file_name']);
        $drive = $this->google_drive_storage->upload_to_path(
            $localPath,
            $driveName,
            ['Jenis Desain', $design_name ?: 'Tanpa Nama', 'Preview'],
            $this->google_drive_storage->detect_mime($localPath)
        );

        if ($drive) {
            $upload['drive'] = $drive;
        } elseif ($this->google_drive_storage->is_enabled()) {
            $upload['drive_error'] = $this->google_drive_storage->last_error();
        }

        return $upload;
    }

    private function upload_source_file($design_name = '')
    {
        if (empty($_FILES['source_file']['name'])) return null;

        if (!$this->google_drive_storage->is_enabled()) {
            $this->session->set_flashdata('designs_err', 'File master CDR hanya disimpan di Google Drive. Hubungkan Google Drive terlebih dahulu sebelum upload file master.');
            return false;
        }

        $res = vi_source_temp_file('source_file', ['cdr'], 204800);
        if (empty($res['ok'])) {
            $this->session->set_flashdata('designs_err', $res['error'] ?? 'Gagal membaca file master.');
            return false;
        }

        $safeOriginal = $this->google_drive_storage->safe_filename($res['original_name']);
        $driveName = $this->google_drive_storage->safe_filename(($design_name ?: 'Jenis Desain') . '-' . $safeOriginal);
        $drive = $this->google_drive_storage->upload_to_path(
            $res['tmp_path'],
            $driveName,
            ['Jenis Desain', $design_name ?: 'Tanpa Nama', 'File Master'],
            $res['mime_type'] ?: 'application/octet-stream'
        );

        if (!$drive) {
            $this->session->set_flashdata('designs_err', 'Gagal upload file master ke Google Drive: ' . $this->google_drive_storage->last_error());
            return false;
        }

        return [
            'original_name' => $res['original_name'],
            'size' => $res['size'],
            'mime_type' => $res['mime_type'],
            'drive' => $drive,
        ];
    }

    private function design_upload_data($upload)
    {
        $data = ['preview_image' => $upload['file_name']];

        if ($this->db_has_design_thumb_column()) {
            $data['preview_thumb'] = $upload['thumb_path'] ?? null;
        }

        if ($this->db_has_design_drive_columns()) {
            if (!empty($upload['drive']['id'])) {
                $data['preview_storage'] = 'drive';
                $data['preview_drive_id'] = $upload['drive']['id'];
                $data['preview_drive_url'] = $upload['drive']['public_url'];
            } else {
                $data['preview_storage'] = 'local';
                $data['preview_drive_id'] = null;
                $data['preview_drive_url'] = null;
            }
        }

        return $data;
    }

    private function source_upload_data($upload)
    {
        if (!$this->db_has_design_source_columns() || empty($upload['drive']['id'])) return [];
        return [
            'source_file_name' => $upload['drive']['name'] ?? $upload['original_name'],
            'source_original_name' => $upload['original_name'],
            'source_drive_id' => $upload['drive']['id'],
            'source_drive_url' => $upload['drive']['public_url'],
            'source_size' => (int)$upload['size'],
            'source_mime' => $upload['mime_type'] ?: 'application/octet-stream',
            'source_uploaded_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function delete_local_if_exists($relative_path)
    {
        $relative_path = trim((string)$relative_path, '/');
        if ($relative_path === '' || strpos($relative_path, '..') !== false) return;
        $path = FCPATH . $relative_path;
        if (is_file($path)) @unlink($path);
    }

    public function index()
    {
        $q = trim((string)$this->input->get('q', true));
        $data = [
            'title' => 'Jenis Desain',
            'rows' => $this->designs->all($q),
            'q' => $q,
            'page_css' => ['designs-mobile.css'],
            'page_js' => ['list-search.js']
        ];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('designs/index', $data);
        $this->load->view('layout/footer');
    }

    public function create()
    {
        if ($this->input->post()) {
            $name = trim((string)$this->input->post('name', true));

            if ($name === '') {
                $this->session->set_flashdata('designs_err', 'Nama jenis desain wajib diisi.');
                redirect('designs/create');
            }

            $existing = $this->designs->find_by_name($name);
            if ($existing && (int)$existing->is_active === 1) {
                $this->session->set_flashdata('designs_err', 'Nama jenis desain sudah aktif di daftar. Gunakan nama lain atau edit data yang sudah ada.');
                redirect('designs/create');
            }

            $upload = $this->upload_preview($name);
            if ($upload === false) redirect('designs/create');

            $source = $this->upload_source_file($name);
            if ($source === false) redirect('designs/create');

            $data = [
                'name' => $name,
                'description' => trim((string)$this->input->post('description', true)) ?: null,
                'is_active' => $this->input->post('is_active') ? 1 : 0,
            ];

            if (!$existing) {
                $data['preview_image'] = null;
                $data['created_at'] = date('Y-m-d H:i:s');
            }

            if ($upload) {
                if ($existing && !empty($existing->preview_image)) {
                    $old = FCPATH . 'assets/uploads/design_previews/' . $existing->preview_image;
                    if (is_file($old)) @unlink($old);
                }
                if ($existing && !empty($existing->preview_thumb)) {
                    $this->delete_local_if_exists($existing->preview_thumb);
                }
                if ($existing && !empty($existing->preview_drive_id)) {
                    $this->google_drive_storage->delete_file($existing->preview_drive_id);
                }
                $data = array_merge($data, $this->design_upload_data($upload));
            }

            if ($source) {
                if ($existing && !empty($existing->source_drive_id)) {
                    $this->google_drive_storage->delete_file($existing->source_drive_id);
                }
                $data = array_merge($data, $this->source_upload_data($source));
            }

            if ($existing) {
                // Data lama yang nonaktif dipakai ulang. Ini mencegah kasus database berisi nama lama
                // tetapi UI tidak menampilkannya, lalu input nama sama terbaca duplikat.
                $ok = $this->designs->reactivate($existing->id, $data);
            } else {
                $ok = $this->designs->create($data);
            }

            if (!$ok) {
                $this->session->set_flashdata('designs_err', 'Gagal menyimpan data. Pastikan nama tidak duplikat.');
                redirect('designs/create');
            }

            if ($upload && !empty($upload['drive_error'])) {
                $this->session->set_flashdata('designs_err', 'Data tersimpan lokal, tetapi upload preview Drive gagal: ' . $upload['drive_error']);
            } else {
                $msg = $existing ? 'Jenis desain lama berhasil diaktifkan ulang dan muncul kembali di daftar.' : 'Jenis desain tersimpan. Preview lokal dibuat ringan.';
                if ($upload && !empty($upload['drive'])) $msg .= ' Preview juga tersimpan di Google Drive.';
                if ($source && !empty($source['drive'])) $msg .= ' File master CDR tersimpan di Google Drive.';
                $this->session->set_flashdata('designs_ok', $msg);
            }

            redirect('designs');
        }

        $data = ['title' => 'Tambah Jenis Desain', 'row' => null, 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('designs/form', $data);
        $this->load->view('layout/footer');
    }

    public function edit($id)
    {
        $row = $this->designs->find($id);
        if (!$row) show_404();

        if ($this->input->post()) {
            $name = $this->input->post('name', true);

            if ($this->designs->exists_name($name, $id)) {
                $this->session->set_flashdata('designs_err', 'Nama jenis desain sudah dipakai. Gunakan nama lain.');
                redirect('designs/edit/' . $id);
            }

            $upload = $this->upload_preview($name);
            if ($upload === false) redirect('designs/edit/' . $id);

            $source = $this->upload_source_file($name);
            if ($source === false) redirect('designs/edit/' . $id);

            $data = [
                'name' => $name,
                'description' => trim((string)$this->input->post('description', true)) ?: null,
                'is_active' => $this->input->post('is_active') ? 1 : 0
            ];

            if ($upload) {
                if (!empty($row->preview_image)) {
                    $old = FCPATH . 'assets/uploads/design_previews/' . $row->preview_image;
                    if (is_file($old)) @unlink($old);
                }
                if (!empty($row->preview_thumb)) {
                    $this->delete_local_if_exists($row->preview_thumb);
                }
                if (!empty($row->preview_drive_id)) {
                    $this->google_drive_storage->delete_file($row->preview_drive_id);
                }
                $data = array_merge($data, $this->design_upload_data($upload));
            }

            if ($source) {
                if (!empty($row->source_drive_id)) {
                    $this->google_drive_storage->delete_file($row->source_drive_id);
                }
                $data = array_merge($data, $this->source_upload_data($source));
            }

            $ok = $this->designs->update($id, $data);
            if (!$ok) {
                $this->session->set_flashdata('designs_err', 'Gagal update data.');
                redirect('designs/edit/' . $id);
            }

            if ($upload && !empty($upload['drive_error'])) {
                $this->session->set_flashdata('designs_err', 'Data terupdate lokal, tetapi upload preview Drive gagal: ' . $upload['drive_error']);
            } else {
                $msg = 'Jenis desain berhasil diperbarui.';
                if ($upload) $msg .= ' Preview baru sudah dikompres ringan.';
                if ($source) $msg .= ' File master CDR baru tersimpan di Google Drive.';
                $this->session->set_flashdata('designs_ok', $msg);
            }

            redirect('designs');
        }

        $data = ['title' => 'Edit Jenis Desain', 'row' => $row, 'page_css' => ['admin.css']];
        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('designs/form', $data);
        $this->load->view('layout/footer');
    }

    public function delete_source($id)
    {
        require_post();

        $row = $this->designs->find($id);
        if (!$row) show_404();

        if (empty($row->source_drive_id)) {
            $this->session->set_flashdata('designs_err', 'Tidak ada file master CDR yang bisa dihapus.');
            redirect('designs/edit/' . $id);
        }

        $deleted = $this->google_drive_storage->delete_file($row->source_drive_id);
        if (!$deleted) {
            $error = $this->google_drive_storage->last_error();
            $this->session->set_flashdata('designs_err', 'Gagal menghapus file master dari Google Drive' . ($error ? ': ' . $error : '.'));
            redirect('designs/edit/' . $id);
        }

        $this->designs->update($id, [
            'source_file_name' => null,
            'source_original_name' => null,
            'source_drive_id' => null,
            'source_drive_url' => null,
            'source_size' => null,
            'source_mime' => null,
            'source_uploaded_at' => null,
        ]);

        $this->session->set_flashdata('designs_ok', 'File master berhasil dihapus dari Google Drive.');
        redirect('designs/edit/' . $id);
    }

    public function delete($id)
    {
        require_post();
        $this->designs->deactivate($id);
        $this->session->set_flashdata('designs_ok', 'Desain berhasil dihapus dari daftar aktif. Riwayat order tetap aman.');
        redirect('designs');
    }
}
