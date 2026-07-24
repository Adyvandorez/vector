<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class DriveStorage extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('google_drive_storage');
    }

    private function has_drive_columns()
    {
        return $this->db->field_exists('preview_drive_id', 'design_types')
            && $this->db->field_exists('preview_thumb', 'design_types')
            && $this->db->field_exists('storage', 'order_files')
            && $this->db->field_exists('drive_file_id', 'order_files')
            && $this->db->field_exists('thumb_path', 'order_files');
    }

    private function design_local_path($fileName)
    {
        $fileName = trim((string)$fileName);
        if ($fileName === '' || strpos($fileName, '..') !== false) return false;
        $primary = FCPATH . 'assets/uploads/design_previews/' . $fileName;
        if (is_file($primary)) return $primary;
        $backup = FCPATH . 'assets/backup/' . $fileName;
        return is_file($backup) ? $backup : $primary;
    }

    private function order_local_path($fileName)
    {
        $fileName = trim((string)$fileName);
        if ($fileName === '' || strpos($fileName, '..') !== false) return false;
        $primary = FCPATH . 'assets/uploads/previews/' . $fileName;
        if (is_file($primary)) return $primary;
        $backup = FCPATH . 'assets/backup/' . $fileName;
        return is_file($backup) ? $backup : $primary;
    }

    private function create_design_thumb($sourcePath)
    {
        $thumb = vi_create_image_thumb($sourcePath, 'assets/uploads/cache/designs', [
            'max_width' => 360,
            'max_height' => 360,
            'quality' => 42,
        ]);
        return !empty($thumb['ok']) ? $thumb['relative_path'] : null;
    }

    private function create_order_thumb($sourcePath)
    {
        $thumb = vi_create_image_thumb($sourcePath, 'assets/uploads/cache/previews', [
            'max_width' => 360,
            'max_height' => 360,
            'quality' => 42,
        ]);
        return !empty($thumb['ok']) ? $thumb['relative_path'] : null;
    }


    private function drive_name_candidates(array $names)
    {
        $result = [];
        foreach ($names as $name) {
            $name = trim((string)$name);
            if ($name === '') continue;

            $base = basename(str_replace('\\', '/', $name));
            foreach ([$name, $base, $this->google_drive_storage->safe_filename($name), $this->google_drive_storage->safe_filename($base)] as $candidate) {
                $candidate = trim((string)$candidate);
                if ($candidate === '') continue;
                $result[$candidate] = true;
            }
        }
        return array_keys($result);
    }

    private function find_existing_drive_file(array $candidateNames, array $folderSegments = [])
    {
        $candidateNames = $this->drive_name_candidates($candidateNames);
        if (!$candidateNames) return false;

        if ($folderSegments) {
            foreach ($candidateNames as $name) {
                $found = $this->google_drive_storage->find_file_in_path($name, $folderSegments);
                if ($found && !empty($found['id'])) return $found;
            }
        }

        foreach ($candidateNames as $name) {
            $found = $this->google_drive_storage->find_file_by_name($name);
            if ($found && !empty($found['id'])) return $found;
        }

        return false;
    }

    private function design_drive_candidates($row)
    {
        $name = $row->name ?? 'Jenis Desain';
        $preview = $row->preview_image ?? '';
        return $this->drive_name_candidates([
            $name . '-' . $preview,
            $preview,
        ]);
    }

    private function order_drive_candidates($row, $folderOrder)
    {
        $fileName = $row->file_name ?? '';
        $original = $row->original_name ?? '';
        return $this->drive_name_candidates([
            $folderOrder . '-' . $fileName,
            $folderOrder . '-' . $original,
            $fileName,
            $original,
        ]);
    }

    private function count_cleanable_designs()
    {
        if (!$this->has_drive_columns()) return 0;

        $rows = $this->db
            ->select('id, preview_image, preview_drive_id')
            ->where('preview_image IS NOT NULL', null, false)
            ->where("preview_image != ''", null, false)
            ->where('preview_drive_id IS NOT NULL', null, false)
            ->where("preview_drive_id != ''", null, false)
            ->get('design_types')
            ->result();

        $count = 0;
        foreach ($rows as $row) {
            $path = $this->design_local_path($row->preview_image);
            if ($path && is_file($path)) $count++;
        }
        return $count;
    }

    private function count_cleanable_orders()
    {
        if (!$this->has_drive_columns()) return 0;

        $rows = $this->db
            ->select('id, file_name, drive_file_id')
            ->where('file_type', 'PREVIEW')
            ->where('file_name IS NOT NULL', null, false)
            ->where("file_name != ''", null, false)
            ->where('drive_file_id IS NOT NULL', null, false)
            ->where("drive_file_id != ''", null, false)
            ->get('order_files')
            ->result();

        $count = 0;
        foreach ($rows as $row) {
            $path = $this->order_local_path($row->file_name);
            if ($path && is_file($path)) $count++;
        }
        return $count;
    }


    public function index()
    {
        $design_pending = 0;
        $order_pending = 0;
        $design_drive_pending = 0;
        $design_thumb_pending = 0;
        $order_drive_pending = 0;
        $order_thumb_pending = 0;

        if ($this->has_drive_columns()) {
            $design_drive_pending = $this->db
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->group_start()
                ->where('preview_drive_id IS NULL', null, false)
                ->or_where('preview_drive_id', '')
                ->group_end()
                ->count_all_results('design_types');

            $design_thumb_pending = $this->db
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->group_start()
                ->where('preview_thumb IS NULL', null, false)
                ->or_where('preview_thumb', '')
                ->group_end()
                ->count_all_results('design_types');

            $design_pending = $this->db
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->group_start()
                ->where('preview_drive_id IS NULL', null, false)
                ->or_where('preview_drive_id', '')
                ->or_where('preview_thumb IS NULL', null, false)
                ->or_where('preview_thumb', '')
                ->group_end()
                ->count_all_results('design_types');

            $order_drive_pending = $this->db
                ->where('file_type', 'PREVIEW')
                ->where('file_name IS NOT NULL', null, false)
                ->where("file_name != ''", null, false)
                ->group_start()
                ->where('drive_file_id IS NULL', null, false)
                ->or_where('drive_file_id', '')
                ->group_end()
                ->count_all_results('order_files');

            $order_thumb_pending = $this->db
                ->where('file_type', 'PREVIEW')
                ->where('file_name IS NOT NULL', null, false)
                ->where("file_name != ''", null, false)
                ->group_start()
                ->where('thumb_path IS NULL', null, false)
                ->or_where('thumb_path', '')
                ->group_end()
                ->count_all_results('order_files');

            $order_pending = $this->db
                ->where('file_type', 'PREVIEW')
                ->where('file_name IS NOT NULL', null, false)
                ->where("file_name != ''", null, false)
                ->group_start()
                ->where('drive_file_id IS NULL', null, false)
                ->or_where('drive_file_id', '')
                ->or_where('thumb_path IS NULL', null, false)
                ->or_where('thumb_path', '')
                ->group_end()
                ->count_all_results('order_files');
        }

        $configured = $this->google_drive_storage->is_configured();
        $authorized = $configured ? $this->google_drive_storage->is_authorized() : false;

        $data = [
            'title' => 'Google Drive Storage',
            'drive_configured' => $configured,
            'drive_authorized' => $authorized,
            'drive_enabled' => $configured && $authorized,
            'drive_error' => $this->google_drive_storage->last_error(),
            'has_drive_columns' => $this->has_drive_columns(),
            'has_token_table' => $this->google_drive_storage->has_token_table(),
            'design_pending' => $design_pending,
            'order_pending' => $order_pending,
            'design_drive_pending' => $design_drive_pending,
            'design_thumb_pending' => $design_thumb_pending,
            'order_drive_pending' => $order_drive_pending,
            'order_thumb_pending' => $order_thumb_pending,
            'design_cleanable' => $this->count_cleanable_designs(),
            'order_cleanable' => $this->count_cleanable_orders(),
            'last_result' => $this->session->flashdata('drive_storage_result'),
            'page_css' => ['drive-storage.css', 'drive-storage-mobile.css'],
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('drive_storage/index', $data);
        $this->load->view('layout/footer');
    }

    public function guide()
    {
        $data = [
            'title' => 'Panduan Google Drive Storage',
            'page_css' => ['drive-storage.css', 'drive-storage-mobile.css'],
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('drive_storage/guide', $data);
        $this->load->view('layout/footer');
    }

    public function connect()
    {
        $url = $this->google_drive_storage->auth_url();
        if (!$url) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Gagal membuat URL OAuth: ' . $this->google_drive_storage->last_error(),
            ]);
            redirect('drive-storage');
        }
        redirect($url);
    }

    public function oauth_callback()
    {
        $error = $this->input->get('error', true);
        if ($error) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Google Drive tidak jadi dihubungkan: ' . $error,
            ]);
            redirect('drive-storage');
        }

        $code = $this->input->get('code', false);
        $state = $this->input->get('state', true);

        if (!$code) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Kode OAuth tidak ditemukan. Silakan ulangi proses Hubungkan Google Drive.',
            ]);
            redirect('drive-storage');
        }

        $ok = $this->google_drive_storage->handle_callback($code, $state);
        $this->session->set_flashdata('drive_storage_result', [
            'ok' => $ok ? true : false,
            'message' => $ok
                ? 'Google Drive berhasil dihubungkan. Upload preview baru dan migrasi preview lama sekarang memakai akun Google kamu.'
                : 'Gagal menghubungkan Google Drive: ' . $this->google_drive_storage->last_error(),
        ]);
        redirect('drive-storage');
    }

    public function disconnect()
    {
        require_post();
        $this->google_drive_storage->disconnect();
        $this->session->set_flashdata('drive_storage_result', [
            'ok' => true,
            'message' => 'Koneksi Google Drive berhasil diputus. File yang sudah terupload di Drive tidak dihapus.',
        ]);
        redirect('drive-storage');
    }


    public function image($file_id = null)
    {
        $file_id = trim((string)$file_id);
        if ($file_id === '') {
            show_404();
        }

        try {
            $file = $this->google_drive_storage->get_file_media($file_id);
            if (!$file || empty($file['content'])) {
                log_message('error', 'Drive image preview gagal: ' . $this->google_drive_storage->last_error());
                show_404();
            }

            $mime = !empty($file['mime_type']) ? $file['mime_type'] : 'image/jpeg';
            if (strpos($mime, 'image/') !== 0) {
                $mime = 'application/octet-stream';
            }

            $this->output
                ->set_header('Cache-Control: private, max-age=86400')
                ->set_header('X-Content-Type-Options: nosniff')
                ->set_content_type($mime)
                ->set_output($file['content']);
        } catch (Throwable $e) {
            log_message('error', 'Drive image preview exception: ' . $e->getMessage());
            show_404();
        }
    }


    public function sync_existing()
    {
        require_post();

        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        if (!$this->has_drive_columns()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Kolom Google Drive/thumbnail belum ada di database. Import SQL final atau jalankan SQL patch terlebih dahulu.',
            ]);
            redirect('drive-storage');
        }

        if (!$this->google_drive_storage->is_authorized()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Google Drive belum terhubung. Klik Hubungkan Google Drive terlebih dahulu.',
            ]);
            redirect('drive-storage');
        }

        $this->config->load('google_drive', true, true);
        $cfg = $this->config->item('google_drive');
        $limit = (int)($cfg['google_drive_max_sync_per_run'] ?? 10);
        if ($limit <= 0) $limit = 10;

        $result = [
            'ok' => true,
            'design_synced' => 0,
            'order_synced' => 0,
            'thumb_created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $designs = $this->db
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->group_start()
                ->where('preview_drive_id IS NULL', null, false)
                ->or_where('preview_drive_id', '')
                ->or_where('preview_thumb IS NULL', null, false)
                ->or_where('preview_thumb', '')
                ->group_end()
                ->limit($limit)
                ->get('design_types')
                ->result();

            foreach ($designs as $d) {
                $update = [];
                $path = $this->design_local_path($d->preview_image);

                if (empty($d->preview_thumb) && $path && is_file($path)) {
                    $thumb = $this->create_design_thumb($path);
                    if ($thumb) {
                        $update['preview_thumb'] = $thumb;
                        $result['thumb_created']++;
                    }
                }

                if (empty($d->preview_drive_id)) {
                    $candidates = $this->design_drive_candidates($d);
                    $found = $this->find_existing_drive_file($candidates, ['Jenis Desain', $d->name, 'Preview']);

                    if ($found && !empty($found['id'])) {
                        $this->google_drive_storage->make_file_public($found['id']);
                        $drive = $this->google_drive_storage->file_result_to_drive_payload($found);
                        $update['preview_storage'] = 'drive';
                        $update['preview_drive_id'] = $drive['id'];
                        $update['preview_drive_url'] = $drive['public_url'];
                        $result['design_synced']++;
                    } else {
                        $result['skipped']++;
                        $result['errors'][] = 'Drive belum cocok untuk desain: ' . $d->name . ' (dicari: ' . implode(', ', array_slice($candidates, 0, 4)) . ')';
                    }
                }

                if ($update) {
                    $this->db->where('id', (int)$d->id)->update('design_types', $update);
                }
            }

            $processed = count($designs);
            $remaining = max(0, $limit - $processed);
            if ($remaining > 0) {
                $files = $this->db
                    ->select('of.*, o.order_code, o.deadline, c.name as client_name')
                    ->from('order_files of')
                    ->join('orders o', 'o.id=of.order_id', 'left')
                    ->join('clients c', 'c.id=o.client_id', 'left')
                    ->where('of.file_type', 'PREVIEW')
                    ->where('of.file_name IS NOT NULL', null, false)
                    ->where("of.file_name != ''", null, false)
                    ->group_start()
                    ->where('of.drive_file_id IS NULL', null, false)
                    ->or_where('of.drive_file_id', '')
                    ->or_where('of.thumb_path IS NULL', null, false)
                    ->or_where('of.thumb_path', '')
                    ->group_end()
                    ->limit($remaining)
                    ->get()
                    ->result();

                foreach ($files as $f) {
                    $update = [];
                    $path = $this->order_local_path($f->file_name);

                    if (empty($f->thumb_path) && $path && is_file($path)) {
                        $thumb = $this->create_order_thumb($path);
                        if ($thumb) {
                            $update['thumb_path'] = $thumb;
                            $result['thumb_created']++;
                        }
                    }

                    if (empty($f->drive_file_id)) {
                        $year = !empty($f->deadline) ? date('Y', strtotime($f->deadline)) : date('Y');
                        $month = !empty($f->deadline) ? date('m F', strtotime($f->deadline)) : date('m F');
                        $folderOrder = trim(($f->order_code ?: ('ORD-' . $f->order_id)) . ' - ' . ($f->client_name ?: 'Client'));
                        $candidates = $this->order_drive_candidates($f, $folderOrder);
                        $found = $this->find_existing_drive_file($candidates, ['Orders', $year, $month, $folderOrder, 'Preview']);

                        if ($found && !empty($found['id'])) {
                            $this->google_drive_storage->make_file_public($found['id']);
                            $drive = $this->google_drive_storage->file_result_to_drive_payload($found);
                            $update['storage'] = 'drive';
                            $update['drive_file_id'] = $drive['id'];
                            $update['drive_url'] = $drive['public_url'];
                            if (!empty($drive['size'])) $update['file_size'] = (int)$drive['size'];
                            if (!empty($drive['mimeType'])) $update['mime_type'] = $drive['mimeType'];
                            $result['order_synced']++;
                        } else {
                            $result['skipped']++;
                            $result['errors'][] = 'Drive belum cocok untuk order file: ' . $f->file_name . ' (dicari: ' . implode(', ', array_slice($candidates, 0, 4)) . ')';
                        }
                    }

                    if ($update) {
                        $this->db->where('id', (int)$f->id)->update('order_files', $update);
                    }
                }
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['skipped']++;
            $result['errors'][] = $e->getMessage();
        }

        $result['message'] = 'Sinkronisasi selesai untuk batch ini. Desain tersambung: ' . $result['design_synced'] . ', Order tersambung: ' . $result['order_synced'] . ', Thumbnail dibuat: ' . $result['thumb_created'] . ', Skip: ' . $result['skipped'] . '.';
        if (!$result['ok']) {
            $result['message'] = 'Sinkronisasi belum selesai karena ada error. Cek catatan di bawah.';
        }

        $this->session->set_flashdata('drive_storage_result', $result);
        redirect('drive-storage');
    }

    public function migrate()
    {
        require_post();

        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        if (!$this->has_drive_columns()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Kolom Google Drive/thumbnail belum ada di database. Import SQL final atau jalankan SQL patch terlebih dahulu.',
            ]);
            redirect('drive-storage');
        }

        if (!$this->google_drive_storage->is_configured()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Konfigurasi Google Drive belum lengkap: ' . $this->google_drive_storage->last_error(),
            ]);
            redirect('drive-storage');
        }

        if (!$this->google_drive_storage->is_authorized()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Google Drive belum terhubung. Klik tombol Hubungkan Google Drive terlebih dahulu.',
            ]);
            redirect('drive-storage');
        }

        $this->config->load('google_drive', true, true);
        $cfg = $this->config->item('google_drive');
        $limit = (int)($cfg['google_drive_max_migrate_per_run'] ?? 1);
        if ($limit <= 0) $limit = 1;

        $result = [
            'ok' => true,
            'design_uploaded' => 0,
            'order_uploaded' => 0,
            'thumb_created' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        try {
            $designs = $this->db
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->group_start()
                ->where('preview_drive_id IS NULL', null, false)
                ->or_where('preview_drive_id', '')
                ->or_where('preview_thumb IS NULL', null, false)
                ->or_where('preview_thumb', '')
                ->group_end()
                ->limit($limit)
                ->get('design_types')
                ->result();

            foreach ($designs as $d) {
                $path = $this->design_local_path($d->preview_image);
                if (!is_file($path)) {
                    $result['skipped']++;
                    $result['errors'][] = 'File desain tidak ditemukan: ' . $d->preview_image;
                    continue;
                }

                $update = [];

                if (empty($d->preview_thumb)) {
                    $thumb = $this->create_design_thumb($path);
                    if ($thumb) {
                        $update['preview_thumb'] = $thumb;
                        $result['thumb_created']++;
                    }
                }

                if (empty($d->preview_drive_id)) {
                    $fileName = $this->google_drive_storage->safe_filename($d->name . '-' . $d->preview_image);
                    $drive = $this->google_drive_storage->upload_to_path($path, $fileName, ['Jenis Desain', $d->name, 'Preview'], $this->google_drive_storage->detect_mime($path));
                    if (!$drive) {
                        $result['skipped']++;
                        $result['errors'][] = 'Gagal upload desain ' . $d->name . ': ' . $this->google_drive_storage->last_error();
                        if ($update) $this->db->where('id', (int)$d->id)->update('design_types', $update);
                        continue;
                    }

                    $update['preview_storage'] = 'drive';
                    $update['preview_drive_id'] = $drive['id'];
                    $update['preview_drive_url'] = $drive['public_url'];
                    $result['design_uploaded']++;
                }

                if ($update) {
                    $this->db->where('id', (int)$d->id)->update('design_types', $update);
                }
            }

            $processed = count($designs);
            $remaining = max(0, $limit - $processed);
            if ($remaining > 0) {
                $files = $this->db
                    ->select('of.*, o.order_code, o.deadline, c.name as client_name')
                    ->from('order_files of')
                    ->join('orders o', 'o.id=of.order_id', 'left')
                    ->join('clients c', 'c.id=o.client_id', 'left')
                    ->where('of.file_type', 'PREVIEW')
                    ->where('of.file_name IS NOT NULL', null, false)
                    ->where("of.file_name != ''", null, false)
                    ->group_start()
                    ->where('of.drive_file_id IS NULL', null, false)
                    ->or_where('of.drive_file_id', '')
                    ->or_where('of.thumb_path IS NULL', null, false)
                    ->or_where('of.thumb_path', '')
                    ->group_end()
                    ->limit($remaining)
                    ->get()
                    ->result();

                foreach ($files as $f) {
                    $path = $this->order_local_path($f->file_name);
                    if (!is_file($path)) {
                        $result['skipped']++;
                        $result['errors'][] = 'File order tidak ditemukan: ' . $f->file_name;
                        continue;
                    }

                    $update = [];

                    if (empty($f->thumb_path)) {
                        $thumb = $this->create_order_thumb($path);
                        if ($thumb) {
                            $update['thumb_path'] = $thumb;
                            $result['thumb_created']++;
                        }
                    }

                    if (empty($f->drive_file_id)) {
                        $year = !empty($f->deadline) ? date('Y', strtotime($f->deadline)) : date('Y');
                        $month = !empty($f->deadline) ? date('m F', strtotime($f->deadline)) : date('m F');
                        $folderOrder = trim(($f->order_code ?: ('ORD-' . $f->order_id)) . ' - ' . ($f->client_name ?: 'Client'));
                        $fileName = $this->google_drive_storage->safe_filename($folderOrder . '-' . $f->file_name);
                        $drive = $this->google_drive_storage->upload_to_path($path, $fileName, ['Orders', $year, $month, $folderOrder, 'Preview'], $this->google_drive_storage->detect_mime($path));

                        if (!$drive) {
                            $result['skipped']++;
                            $result['errors'][] = 'Gagal upload order file ' . $f->file_name . ': ' . $this->google_drive_storage->last_error();
                            if ($update) $this->db->where('id', (int)$f->id)->update('order_files', $update);
                            continue;
                        }

                        $update['storage'] = 'drive';
                        $update['drive_file_id'] = $drive['id'];
                        $update['drive_url'] = $drive['public_url'];
                        $update['file_size'] = is_file($path) ? filesize($path) : null;
                        $update['mime_type'] = $this->google_drive_storage->detect_mime($path);
                        $result['order_uploaded']++;
                    }

                    if ($update) {
                        $this->db->where('id', (int)$f->id)->update('order_files', $update);
                    }
                }
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['skipped']++;
            $result['errors'][] = $e->getMessage();
        }

        $result['message'] = 'Migrasi selesai untuk batch ini. Desain: ' . $result['design_uploaded'] . ', Order preview: ' . $result['order_uploaded'] . ', Thumbnail: ' . $result['thumb_created'] . ', Skip/error: ' . $result['skipped'] . '.';
        if (!$result['ok']) {
            $result['message'] = 'Migrasi belum selesai karena ada error. Cek catatan di bawah.';
        }
        $this->session->set_flashdata('drive_storage_result', $result);
        redirect('drive-storage');
    }

    public function cleanup_local()
    {
        require_post();

        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');

        if (!$this->has_drive_columns()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Kolom Google Drive belum tersedia di database. Import SQL final atau jalankan SQL patch terlebih dahulu.',
            ]);
            redirect('drive-storage');
        }

        if (!$this->google_drive_storage->is_authorized()) {
            $this->session->set_flashdata('drive_storage_result', [
                'ok' => false,
                'message' => 'Google Drive belum terhubung. Klik Hubungkan Google Drive terlebih dahulu sebelum membersihkan file lokal.',
            ]);
            redirect('drive-storage');
        }

        $this->config->load('google_drive', true, true);
        $cfg = $this->config->item('google_drive');
        $limit = (int)($cfg['google_drive_max_cleanup_per_run'] ?? 10);
        if ($limit <= 0) $limit = 10;

        $result = [
            'ok' => true,
            'deleted' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $processedPaths = [];

        try {
            $designs = $this->db
                ->select('id, name, preview_image, preview_drive_id')
                ->where('preview_image IS NOT NULL', null, false)
                ->where("preview_image != ''", null, false)
                ->where('preview_drive_id IS NOT NULL', null, false)
                ->where("preview_drive_id != ''", null, false)
                ->get('design_types')
                ->result();

            foreach ($designs as $d) {
                if ($result['deleted'] >= $limit) break;

                $path = $this->design_local_path($d->preview_image);
                if (!$path || !is_file($path)) continue;

                $pathKey = realpath($path) ?: $path;
                if (isset($processedPaths[$pathKey])) continue;

                $usedByUnmigrated = $this->db
                    ->where('preview_image', $d->preview_image)
                    ->group_start()
                    ->where('preview_drive_id IS NULL', null, false)
                    ->or_where('preview_drive_id', '')
                    ->group_end()
                    ->count_all_results('design_types');

                if ($usedByUnmigrated > 0) {
                    $result['skipped']++;
                    $result['errors'][] = 'Skip desain ' . $d->name . ': file lokal masih dipakai data lain yang belum punya Drive ID.';
                    continue;
                }

                $meta = $this->google_drive_storage->file_metadata($d->preview_drive_id);
                if (!$meta) {
                    $result['skipped']++;
                    $result['errors'][] = 'Skip desain ' . $d->name . ': file Drive belum valid. ' . $this->google_drive_storage->last_error();
                    continue;
                }

                if (@unlink($path)) {
                    $processedPaths[$pathKey] = true;
                    $result['deleted']++;
                } else {
                    $result['skipped']++;
                    $result['errors'][] = 'Gagal menghapus file lokal desain: ' . $d->preview_image;
                }
            }

            if ($result['deleted'] < $limit) {
                $remaining = $limit - $result['deleted'];
                $files = $this->db
                    ->select('id, file_name, drive_file_id')
                    ->where('file_type', 'PREVIEW')
                    ->where('file_name IS NOT NULL', null, false)
                    ->where("file_name != ''", null, false)
                    ->where('drive_file_id IS NOT NULL', null, false)
                    ->where("drive_file_id != ''", null, false)
                    ->get('order_files')
                    ->result();

                foreach ($files as $f) {
                    if ($result['deleted'] >= $limit) break;

                    $path = $this->order_local_path($f->file_name);
                    if (!$path || !is_file($path)) continue;

                    $pathKey = realpath($path) ?: $path;
                    if (isset($processedPaths[$pathKey])) continue;

                    $usedByUnmigrated = $this->db
                        ->where('file_type', 'PREVIEW')
                        ->where('file_name', $f->file_name)
                        ->group_start()
                        ->where('drive_file_id IS NULL', null, false)
                        ->or_where('drive_file_id', '')
                        ->group_end()
                        ->count_all_results('order_files');

                    if ($usedByUnmigrated > 0) {
                        $result['skipped']++;
                        $result['errors'][] = 'Skip order file ' . $f->file_name . ': file lokal masih dipakai data lain yang belum punya Drive ID.';
                        continue;
                    }

                    $meta = $this->google_drive_storage->file_metadata($f->drive_file_id);
                    if (!$meta) {
                        $result['skipped']++;
                        $result['errors'][] = 'Skip order file ' . $f->file_name . ': file Drive belum valid. ' . $this->google_drive_storage->last_error();
                        continue;
                    }

                    if (@unlink($path)) {
                        $processedPaths[$pathKey] = true;
                        $result['deleted']++;
                    } else {
                        $result['skipped']++;
                        $result['errors'][] = 'Gagal menghapus file lokal order: ' . $f->file_name;
                    }
                }
            }
        } catch (Throwable $e) {
            $result['ok'] = false;
            $result['errors'][] = $e->getMessage();
        }

        $result['message'] = 'Pembersihan file lokal selesai untuk batch ini. Terhapus: ' . $result['deleted'] . ', Skip/error: ' . $result['skipped'] . '.';
        if (!$result['ok']) {
            $result['message'] = 'Pembersihan file lokal belum selesai karena ada error. Cek catatan di bawah.';
        }

        $this->session->set_flashdata('drive_storage_result', $result);
        redirect('drive-storage');
    }

}
