<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Upload + validasi + kompres gambar untuk CI3.
 * Preview lokal sengaja dibuat kecil agar halaman list tetap ringan.
 */
function vi_upload_image($field, $relative_dir, array $options = [])
{
    $CI =& get_instance();

    if (empty($_FILES[$field]['name'])) {
        return ['ok' => false, 'empty' => true, 'error' => 'Tidak ada file yang dipilih.'];
    }

    $maxSizeKb = isset($options['max_size_kb']) ? (int)$options['max_size_kb'] : 8192;
    $maxWidth  = isset($options['max_width']) ? (int)$options['max_width'] : 900;
    $maxHeight = isset($options['max_height']) ? (int)$options['max_height'] : 900;
    $quality   = isset($options['quality']) ? (int)$options['quality'] : 50;

    $relative_dir = trim($relative_dir, '/');
    $upload_path = FCPATH . $relative_dir . '/';
    if (!is_dir($upload_path)) {
        @mkdir($upload_path, 0755, true);
    }

    $config = [
        'upload_path'   => $upload_path,
        'allowed_types' => 'jpg|jpeg|png|webp',
        'max_size'      => $maxSizeKb,
        'encrypt_name'  => true,
        'detect_mime'   => true,
        'mod_mime_fix'  => true,
    ];

    $CI->load->library('upload');
    $CI->upload->initialize($config, true);

    if (!$CI->upload->do_upload($field)) {
        return [
            'ok' => false,
            'empty' => false,
            'error' => strip_tags($CI->upload->display_errors('', '')),
        ];
    }

    $data = $CI->upload->data();
    $before = is_file($data['full_path']) ? filesize($data['full_path']) : 0;
    $opt = vi_optimize_image($data['full_path'], $maxWidth, $maxHeight, $quality);
    clearstatcache(true, $data['full_path']);
    $after = is_file($data['full_path']) ? filesize($data['full_path']) : $before;

    $thumb = vi_create_image_thumb($data['full_path'], $options['thumb_dir'] ?? 'assets/uploads/cache/general', [
        'max_width' => $options['thumb_width'] ?? 360,
        'max_height' => $options['thumb_height'] ?? 360,
        'quality' => $options['thumb_quality'] ?? 45,
    ]);

    return [
        'ok' => true,
        'empty' => false,
        'file_name' => $data['file_name'],
        'original_name' => $data['orig_name'],
        'full_path' => $data['full_path'],
        'before' => $before,
        'after' => $after,
        'optimized' => !empty($opt['ok']),
        'message' => $opt['msg'] ?? '',
        'thumb' => $thumb,
        'thumb_path' => !empty($thumb['relative_path']) ? $thumb['relative_path'] : null,
    ];
}

/** Resize proporsional + kompres ulang gambar memakai GD jika tersedia. */
function vi_optimize_image($full_path, $max_width = 900, $max_height = 900, $quality = 50)
{
    if (!is_file($full_path)) return ['ok' => false, 'msg' => 'File tidak ditemukan.'];
    if (!function_exists('getimagesize')) return ['ok' => false, 'msg' => 'Ekstensi gambar PHP tidak tersedia.'];

    $loaded = vi_gd_load_image($full_path);
    if (empty($loaded['ok'])) return ['ok' => false, 'msg' => $loaded['msg'] ?? 'Gagal membaca gambar.'];

    $src = $loaded['image'];
    $mime = $loaded['mime'];
    $width = $loaded['width'];
    $height = $loaded['height'];

    $ratio = min($max_width / $width, $max_height / $height, 1);
    $new_w = max(1, (int)floor($width * $ratio));
    $new_h = max(1, (int)floor($height * $ratio));

    if ($new_w !== $width || $new_h !== $height) {
        $dst = imagecreatetruecolor($new_w, $new_h);
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
        imagedestroy($src);
        $src = $dst;
    }

    $quality = max(10, min(90, (int)$quality));
    if ($mime === 'image/jpeg') {
        $ok = @imagejpeg($src, $full_path, $quality);
    } elseif ($mime === 'image/png') {
        $ok = @imagepng($src, $full_path, 9);
    } else {
        $ok = @imagewebp($src, $full_path, $quality);
    }

    imagedestroy($src);
    return ['ok' => (bool)$ok, 'msg' => $ok ? 'Gambar berhasil dikompres.' : 'Gagal menulis gambar kompres.'];
}

function vi_gd_load_image($full_path)
{
    if (!is_file($full_path)) return ['ok' => false, 'msg' => 'File tidak ditemukan.'];
    $info = @getimagesize($full_path);
    if (!$info) return ['ok' => false, 'msg' => 'File bukan gambar valid.'];

    $mime = isset($info['mime']) ? $info['mime'] : '';
    $width = (int)$info[0];
    $height = (int)$info[1];
    if ($width <= 0 || $height <= 0) return ['ok' => false, 'msg' => 'Resolusi gambar tidak valid.'];

    if ($mime === 'image/jpeg') {
        if (!function_exists('imagecreatefromjpeg')) return ['ok' => false, 'msg' => 'GD JPEG tidak tersedia.'];
        $src = @imagecreatefromjpeg($full_path);
    } elseif ($mime === 'image/png') {
        if (!function_exists('imagecreatefrompng')) return ['ok' => false, 'msg' => 'GD PNG tidak tersedia.'];
        $src = @imagecreatefrompng($full_path);
    } elseif ($mime === 'image/webp') {
        if (!function_exists('imagecreatefromwebp')) return ['ok' => false, 'msg' => 'GD WEBP tidak tersedia.'];
        $src = @imagecreatefromwebp($full_path);
    } else {
        return ['ok' => false, 'msg' => 'Format gambar tidak didukung.'];
    }

    if (!$src) return ['ok' => false, 'msg' => 'Gagal membaca gambar.'];
    return ['ok' => true, 'image' => $src, 'mime' => $mime, 'width' => $width, 'height' => $height];
}

/** Buat thumbnail lokal kecil. Default WEBP agar ukuran ringan. */
function vi_create_image_thumb($source_path, $relative_dir, array $options = [])
{
    $relative_dir = trim((string)$relative_dir, '/');
    if ($relative_dir === '') $relative_dir = 'assets/uploads/cache/general';

    $dir = FCPATH . $relative_dir . '/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $loaded = vi_gd_load_image($source_path);
    if (empty($loaded['ok'])) return ['ok' => false, 'error' => $loaded['msg'] ?? 'Gagal membuat thumbnail.'];

    $src = $loaded['image'];
    $width = $loaded['width'];
    $height = $loaded['height'];
    $maxWidth = isset($options['max_width']) ? (int)$options['max_width'] : 360;
    $maxHeight = isset($options['max_height']) ? (int)$options['max_height'] : 360;
    $quality = isset($options['quality']) ? (int)$options['quality'] : 45;

    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $new_w = max(1, (int)floor($width * $ratio));
    $new_h = max(1, (int)floor($height * $ratio));

    $dst = imagecreatetruecolor($new_w, $new_h);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $new_w, $new_h, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
    imagedestroy($src);

    $base = pathinfo($source_path, PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9_\-]+/', '_', $base);
    $base = trim($base, '_') ?: 'thumb';

    if (function_exists('imagewebp')) {
        $file = 'thumb_' . $base . '.webp';
        $full = $dir . $file;
        $ok = @imagewebp($dst, $full, max(10, min(90, $quality)));
    } else {
        $file = 'thumb_' . $base . '.jpg';
        $full = $dir . $file;
        $white = imagecreatetruecolor($new_w, $new_h);
        $bg = imagecolorallocate($white, 255, 255, 255);
        imagefilledrectangle($white, 0, 0, $new_w, $new_h, $bg);
        imagecopy($white, $dst, 0, 0, 0, 0, $new_w, $new_h);
        $ok = @imagejpeg($white, $full, max(10, min(90, $quality)));
        imagedestroy($white);
    }

    imagedestroy($dst);

    return [
        'ok' => (bool)$ok,
        'file_name' => $file,
        'relative_path' => $relative_dir . '/' . $file,
        'full_path' => $full,
        'size' => is_file($full) ? filesize($full) : 0,
    ];
}

/** Upload file master/source seperti CDR ke Google Drive tanpa disimpan permanen di lokal. */
function vi_source_temp_file($field, array $allowed_exts = ['cdr'], $max_size_kb = 102400)
{
    if (empty($_FILES[$field]['name'])) {
        return ['ok' => false, 'empty' => true, 'error' => 'Tidak ada file master yang dipilih.'];
    }

    $file = $_FILES[$field];
    if (!empty($file['error'])) {
        return ['ok' => false, 'empty' => false, 'error' => 'Upload file master gagal. Kode error: ' . $file['error']];
    }

    $original = basename((string)$file['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = array_map('strtolower', $allowed_exts);
    if (!in_array($ext, $allowed, true)) {
        return ['ok' => false, 'empty' => false, 'error' => 'Format file master tidak didukung. Gunakan: ' . strtoupper(implode(', ', $allowed)) . '.'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > ((int)$max_size_kb * 1024)) {
        return ['ok' => false, 'empty' => false, 'error' => 'Ukuran file master terlalu besar. Maksimal ' . vi_human_size((int)$max_size_kb * 1024) . '.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'empty' => false, 'error' => 'File master tidak valid.'];
    }

    $mime = function_exists('finfo_open') ? null : 'application/octet-stream';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
    }

    return [
        'ok' => true,
        'empty' => false,
        'tmp_path' => $file['tmp_name'],
        'original_name' => $original,
        'extension' => $ext,
        'size' => $size,
        'mime_type' => $mime ?: 'application/octet-stream',
    ];
}

function vi_human_size($bytes)
{
    $bytes = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return number_format($bytes, $i === 0 ? 0 : 2, ',', '.') . ' ' . $units[$i];
}

// Kompatibilitas dengan helper lama.
function compress_image_force_debug($full_path, $jpg_quality = 60, $webp_quality = 60, $png_level = 9)
{
    $before = is_file($full_path) ? filesize($full_path) : 0;
    $res = vi_optimize_image($full_path, 900, 900, $jpg_quality);
    clearstatcache(true, $full_path);
    $after = is_file($full_path) ? filesize($full_path) : $before;
    return [
        'ok' => !empty($res['ok']),
        'msg' => $res['msg'] ?? '',
        'before' => $before,
        'after' => $after,
        'saved' => $before - $after,
    ];
}
