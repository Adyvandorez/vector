<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('vi_file_url_if_exists')) {
    function vi_file_url_if_exists($relative_path)
    {
        $relative_path = trim((string)$relative_path, '/');
        if ($relative_path === '' || strpos($relative_path, '..') !== false) return '';
        return is_file(FCPATH . $relative_path) ? base_url($relative_path) : '';
    }
}

if (!function_exists('vi_backup_url_if_exists')) {
    function vi_backup_url_if_exists($file_name)
    {
        $file_name = trim((string)$file_name);
        if ($file_name === '' || strpos($file_name, '..') !== false) return '';
        $relative = 'assets/backup/' . $file_name;
        return vi_file_url_if_exists($relative);
    }
}

if (!function_exists('vi_drive_public_url')) {
    function vi_drive_public_url($file_id)
    {
        $file_id = trim((string)$file_id);
        return $file_id !== '' ? base_url('drive-storage/image/' . rawurlencode($file_id)) : '';
    }
}

if (!function_exists('vi_design_preview_full_url')) {
    /** URL gambar preview ukuran penuh untuk lightbox/detail. */
    function vi_design_preview_full_url($row)
    {
        if ($row && !empty($row->preview_image)) {
            $url = vi_file_url_if_exists('assets/uploads/design_previews/' . $row->preview_image);
            if ($url) return $url;

            $url = vi_backup_url_if_exists($row->preview_image);
            if ($url) return $url;
        }

        if ($row && !empty($row->preview_drive_id)) {
            return vi_drive_public_url($row->preview_drive_id);
        }

        if ($row && !empty($row->preview_drive_url)) {
            return $row->preview_drive_url;
        }

        if ($row && !empty($row->preview_thumb)) {
            $url = vi_file_url_if_exists($row->preview_thumb);
            if ($url) return $url;
        }

        return '';
    }
}

if (!function_exists('vi_design_preview_url')) {
    function vi_design_preview_url($row)
    {
        if ($row && !empty($row->preview_thumb)) {
            $url = vi_file_url_if_exists($row->preview_thumb);
            if ($url) return $url;
        }

        if ($row && !empty($row->preview_image)) {
            $base = pathinfo($row->preview_image, PATHINFO_FILENAME);
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                $url = vi_file_url_if_exists('assets/uploads/cache/designs/thumb_' . $base . '.' . $ext);
                if ($url) return $url;
            }

            $url = vi_file_url_if_exists('assets/uploads/design_previews/' . $row->preview_image);
            if ($url) return $url;

            $url = vi_backup_url_if_exists($row->preview_image);
            if ($url) return $url;
        }

        if ($row && !empty($row->preview_drive_id)) {
            return vi_drive_public_url($row->preview_drive_id);
        }

        if ($row && !empty($row->preview_drive_url)) {
            return $row->preview_drive_url;
        }

        return '';
    }
}

if (!function_exists('vi_order_file_thumb_url')) {
    function vi_order_file_thumb_url($file)
    {
        if ($file && !empty($file->thumb_path)) {
            $url = vi_file_url_if_exists($file->thumb_path);
            if ($url) return $url;
        }

        if ($file && !empty($file->file_name)) {
            $base = pathinfo($file->file_name, PATHINFO_FILENAME);
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                $url = vi_file_url_if_exists('assets/uploads/cache/previews/thumb_' . $base . '.' . $ext);
                if ($url) return $url;
            }

            $dir = ($file->file_type === 'FINAL') ? 'assets/uploads/finals/' : (($file->file_type === 'REFERENCE') ? 'assets/uploads/references/' : 'assets/uploads/previews/');
            $url = vi_file_url_if_exists($dir . $file->file_name);
            if ($url) return $url;

            $url = vi_backup_url_if_exists($file->file_name);
            if ($url) return $url;
        }

        if ($file && !empty($file->drive_file_id) && (string)$file->file_type !== 'SOURCE') {
            return vi_drive_public_url($file->drive_file_id);
        }

        return '';
    }
}

if (!function_exists('vi_order_file_url')) {
    function vi_order_file_url($file)
    {
        if ($file && !empty($file->drive_file_id)) {
            return vi_drive_public_url($file->drive_file_id);
        }

        if ($file && !empty($file->drive_url)) {
            return $file->drive_url;
        }

        if ($file && !empty($file->file_name)) {
            $dir = ($file->file_type === 'FINAL') ? 'assets/uploads/finals/' : (($file->file_type === 'REFERENCE') ? 'assets/uploads/references/' : 'assets/uploads/previews/');
            $url = vi_file_url_if_exists($dir . $file->file_name);
            if ($url) return $url;

            $url = vi_backup_url_if_exists($file->file_name);
            if ($url) return $url;
        }

        return '';
    }
}

if (!function_exists('vi_storage_badge')) {
    function vi_storage_badge($storage)
    {
        return strtolower((string)$storage) === 'drive' ? 'Drive Backup' : 'Local';
    }
}

if (!function_exists('vi_design_storage_status')) {
    function vi_design_storage_status($row)
    {
        $hasDrive = $row && !empty($row->preview_drive_id);
        $hasThumb = false;

        if ($row && !empty($row->preview_thumb)) {
            $hasThumb = vi_file_url_if_exists($row->preview_thumb) !== '';
        }

        if (!$hasThumb && $row && !empty($row->preview_image)) {
            $base = pathinfo($row->preview_image, PATHINFO_FILENAME);
            foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
                if (vi_file_url_if_exists('assets/uploads/cache/designs/thumb_' . $base . '.' . $ext)) {
                    $hasThumb = true;
                    break;
                }
            }
        }

        if ($hasThumb && $hasDrive) return 'Local Thumb + Drive Backup';
        if ($hasThumb) return 'Local Thumb';
        if ($hasDrive) return 'Drive Backup';
        if ($row && !empty($row->preview_image)) return 'Local Only';
        return 'No Preview';
    }
}

if (!function_exists('vi_source_file_label')) {
    function vi_source_file_label($name, $storage = 'drive')
    {
        $name = trim((string)$name);
        if ($name === '') $name = 'File master';
        return $name . ' • ' . vi_storage_badge($storage);
    }
}
