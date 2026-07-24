<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google Drive Storage untuk Vector Invoice.
 *
 * Versi ini memakai OAuth 2.0 user, bukan Service Account.
 * Alasannya: Service Account tidak punya storage quota pada Drive pribadi.
 */
class Google_drive_storage
{
    protected $CI;
    protected $enabled = false;
    protected $rootFolderId = '';
    protected $oauthClientPath = '';
    protected $makePublic = true;
    protected $scope = 'https://www.googleapis.com/auth/drive';
    protected $client = null;
    protected $accessToken = null;
    protected $lastError = '';
    protected $sslVerify = false;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->config->load('google_drive', true, true);

        $cfg = $this->CI->config->item('google_drive');
        $this->enabled = !empty($cfg['google_drive_enabled']);
        $this->rootFolderId = (string)($cfg['google_drive_root_folder_id'] ?? '');
        $this->oauthClientPath = (string)($cfg['google_drive_oauth_client_path'] ?? '');
        $this->makePublic = !empty($cfg['google_drive_make_public']);
        $this->scope = (string)($cfg['google_drive_oauth_scope'] ?? $this->scope);
        $this->sslVerify = !empty($cfg['google_drive_ssl_verify']);
    }

    public function last_error()
    {
        return $this->lastError;
    }

    protected function set_error($message)
    {
        $this->lastError = (string)$message;
        log_message('error', 'Google Drive Storage: ' . $this->lastError);
        return false;
    }

    public function is_configured()
    {
        if (!$this->enabled) {
            $this->set_error('Google Drive Storage belum diaktifkan pada config.');
            return false;
        }
        if ($this->rootFolderId === '') {
            $this->set_error('Folder ID Google Drive belum diisi.');
            return false;
        }
        if (!is_file($this->oauthClientPath)) {
            $this->set_error('File oauth-client.json tidak ditemukan di ' . $this->oauthClientPath);
            return false;
        }
        if (!$this->client()) return false;
        return true;
    }

    public function has_token_table()
    {
        return $this->CI->db->table_exists('drive_oauth_tokens');
    }

    public function is_authorized()
    {
        if (!$this->is_configured()) return false;
        if (!$this->has_token_table()) {
            $this->set_error('Tabel drive_oauth_tokens belum tersedia. Import SQL final atau jalankan SQL patch.');
            return false;
        }
        $token = $this->token_row();
        if (!$token || empty($token->refresh_token)) {
            $this->set_error('Google Drive belum dihubungkan. Klik tombol Hubungkan Google Drive.');
            return false;
        }
        return true;
    }

    public function is_enabled()
    {
        return $this->is_configured() && $this->is_authorized();
    }

    protected function client()
    {
        if ($this->client !== null) return $this->client;

        if (!is_file($this->oauthClientPath)) {
            return $this->set_error('File oauth-client.json tidak ditemukan.');
        }

        $json = json_decode(file_get_contents($this->oauthClientPath), true);
        if (isset($json['web'])) $json = $json['web'];

        if (!is_array($json) || empty($json['client_id']) || empty($json['client_secret']) || empty($json['auth_uri']) || empty($json['token_uri'])) {
            return $this->set_error('Format oauth-client.json tidak valid. Pastikan file berasal dari OAuth Client ID tipe Web Application.');
        }

        return $this->client = $json;
    }

    protected function redirect_uri()
    {
        return base_url('drive-storage/oauth-callback');
    }

    public function auth_url()
    {
        if (!$this->is_configured()) return false;

        $client = $this->client();
        if (!$client) return false;

        $state = bin2hex(random_bytes(16));
        $this->CI->session->set_userdata('drive_oauth_state', $state);

        $params = [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => $this->redirect_uri(),
            'scope' => $this->scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ];

        return $client['auth_uri'] . '?' . http_build_query($params, '', '&');
    }

    public function handle_callback($code, $state)
    {
        if (!$this->is_configured()) return false;
        if (!$this->has_token_table()) {
            return $this->set_error('Tabel drive_oauth_tokens belum tersedia. Import SQL final atau jalankan SQL patch.');
        }

        $savedState = (string)$this->CI->session->userdata('drive_oauth_state');
        $this->CI->session->unset_userdata('drive_oauth_state');

        if ($savedState === '' || !hash_equals($savedState, (string)$state)) {
            return $this->set_error('State OAuth tidak valid. Silakan ulangi proses Hubungkan Google Drive.');
        }

        $client = $this->client();
        if (!$client) return false;

        $body = http_build_query([
            'code' => $code,
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'redirect_uri' => $this->redirect_uri(),
            'grant_type' => 'authorization_code',
        ], '', '&');

        $res = $this->request_raw('POST', $client['token_uri'], [
            'Content-Type: application/x-www-form-urlencoded',
        ], $body, false);

        if (!$res || empty($res['body'])) {
            return $this->set_error('Tidak ada respons token dari Google.');
        }

        $json = json_decode($res['body'], true);
        if (empty($json['access_token'])) {
            return $this->set_error('Gagal menghubungkan Google Drive: ' . $res['body']);
        }

        if (empty($json['refresh_token']) && !$this->token_row()) {
            return $this->set_error('Refresh token tidak diterima. Klik Hubungkan ulang dan pastikan prompt izin Google disetujui.');
        }

        $this->save_tokens($json);
        $this->accessToken = $json['access_token'];
        return true;
    }

    protected function token_row()
    {
        if (!$this->has_token_table()) return null;
        return $this->CI->db
            ->where('provider', 'google_drive')
            ->limit(1)
            ->get('drive_oauth_tokens')
            ->row();
    }

    protected function save_tokens(array $json)
    {
        $old = $this->token_row();
        $refresh = $json['refresh_token'] ?? ($old->refresh_token ?? '');
        $expiresIn = isset($json['expires_in']) ? (int)$json['expires_in'] : 3600;

        $data = [
            'provider' => 'google_drive',
            'access_token' => $json['access_token'] ?? ($old->access_token ?? ''),
            'refresh_token' => $refresh,
            'token_type' => $json['token_type'] ?? 'Bearer',
            'expires_at' => time() + max(60, $expiresIn) - 60,
            'scope' => $json['scope'] ?? ($old->scope ?? $this->scope),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($old) {
            $this->CI->db->where('provider', 'google_drive')->update('drive_oauth_tokens', $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->CI->db->insert('drive_oauth_tokens', $data);
        }
    }

    protected function access_token()
    {
        if ($this->accessToken !== null) return $this->accessToken;
        if (!$this->is_authorized()) return false;

        $row = $this->token_row();
        if (!$row || empty($row->refresh_token)) {
            return $this->set_error('Google Drive belum dihubungkan.');
        }

        if (!empty($row->access_token) && (int)$row->expires_at > time() + 30) {
            return $this->accessToken = $row->access_token;
        }

        return $this->refresh_access_token($row->refresh_token);
    }

    protected function refresh_access_token($refreshToken)
    {
        $client = $this->client();
        if (!$client) return false;

        $body = http_build_query([
            'client_id' => $client['client_id'],
            'client_secret' => $client['client_secret'],
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ], '', '&');

        $res = $this->request_raw('POST', $client['token_uri'], [
            'Content-Type: application/x-www-form-urlencoded',
        ], $body, false);

        if (!$res || empty($res['body'])) {
            return $this->set_error('Tidak ada respons refresh token dari Google.');
        }

        $json = json_decode($res['body'], true);
        if (empty($json['access_token'])) {
            return $this->set_error('Gagal refresh access token: ' . $res['body']);
        }

        $json['refresh_token'] = $refreshToken;
        $this->save_tokens($json);
        return $this->accessToken = $json['access_token'];
    }

    public function disconnect()
    {
        if (!$this->has_token_table()) return true;
        $this->CI->db->where('provider', 'google_drive')->delete('drive_oauth_tokens');
        $this->accessToken = null;
        return true;
    }

    protected function request_raw($method, $url, $headers = [], $body = null, $auth = true)
    {
        if (!function_exists('curl_init')) {
            $this->set_error('Extension cURL PHP belum aktif. Aktifkan extension=curl di php.ini.');
            return false;
        }

        if ($auth) {
            $token = $this->access_token();
            if (!$token) return false;
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sslVerify);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            $this->set_error('cURL error: ' . $err);
            return false;
        }

        if ($code < 200 || $code >= 300) {
            $this->set_error('HTTP ' . $code . ' dari Google Drive: ' . $response);
            return false;
        }

        return ['code' => $code, 'body' => $response];
    }

    protected function json_request($method, $url, array $payload = null)
    {
        $headers = ['Content-Type: application/json; charset=UTF-8'];
        $body = $payload === null ? null : json_encode($payload);
        $res = $this->request_raw($method, $url, $headers, $body, true);
        if (!$res) return false;
        return json_decode($res['body'], true);
    }

    protected function drive_query($q)
    {
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ], '', '&');

        $res = $this->request_raw('GET', $url, [], null, true);
        if (!$res) return false;
        return json_decode($res['body'], true);
    }


    protected function escape_query_value($value)
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], (string)$value);
    }

    public function find_folder($name, $parentId = null)
    {
        if (!$this->is_enabled()) return false;
        $parentId = $parentId ?: $this->rootFolderId;
        $name = $this->safe_name($name ?: 'Tanpa Nama');
        $escaped = $this->escape_query_value($name);
        $q = "name='{$escaped}' and mimeType='application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed=false";
        $found = $this->drive_query($q);
        if (is_array($found) && !empty($found['files'][0]['id'])) {
            return $found['files'][0]['id'];
        }
        return false;
    }

    public function find_file_in_folder($fileName, $folderId)
    {
        if (!$this->is_enabled()) return false;
        $fileName = $this->safe_filename($fileName ?: 'file');
        $escaped = $this->escape_query_value($fileName);
        $q = "name='{$escaped}' and '{$folderId}' in parents and trashed=false";
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
            'q' => $q,
            'fields' => 'files(id,name,webViewLink,webContentLink,thumbnailLink,mimeType,size)',
            'pageSize' => 1,
        ], '', '&');

        $res = $this->request_raw('GET', $url, [], null, true);
        if (!$res) return false;
        $json = json_decode($res['body'], true);
        return !empty($json['files'][0]) ? $json['files'][0] : false;
    }

    public function find_file_in_path($fileName, array $folderSegments)
    {
        if (!$this->is_enabled()) return false;
        $parent = $this->rootFolderId;
        foreach ($folderSegments as $segment) {
            $parent = $this->find_folder($segment, $parent);
            if (!$parent) return false;
        }
        return $this->find_file_in_folder($fileName, $parent);
    }

    public function find_file_by_name($fileName)
    {
        if (!$this->is_enabled()) return false;
        $fileName = $this->safe_filename($fileName ?: 'file');
        $escaped = $this->escape_query_value($fileName);
        $q = "name='{$escaped}' and trashed=false";
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query([
            'q' => $q,
            'fields' => 'files(id,name,webViewLink,webContentLink,thumbnailLink,mimeType,size)',
            'pageSize' => 1,
        ], '', '&');

        $res = $this->request_raw('GET', $url, [], null, true);
        if (!$res) return false;
        $json = json_decode($res['body'], true);
        return !empty($json['files'][0]) ? $json['files'][0] : false;
    }

    public function file_result_to_drive_payload(array $file)
    {
        if (empty($file['id'])) return [];
        return [
            'id' => $file['id'],
            'name' => $file['name'] ?? '',
            'webViewLink' => $file['webViewLink'] ?? '',
            'webContentLink' => $file['webContentLink'] ?? '',
            'thumbnailLink' => $file['thumbnailLink'] ?? '',
            'mimeType' => $file['mimeType'] ?? '',
            'size' => $file['size'] ?? null,
            'public_url' => 'https://drive.google.com/uc?export=view&id=' . rawurlencode($file['id']),
        ];
    }

    public function ensure_folder($name, $parentId = null)
    {
        $parentId = $parentId ?: $this->rootFolderId;
        $name = $this->safe_name($name ?: 'Tanpa Nama');
        $escaped = str_replace("'", "\\'", $name);
        $q = "name='{$escaped}' and mimeType='application/vnd.google-apps.folder' and '{$parentId}' in parents and trashed=false";
        $found = $this->drive_query($q);

        if (is_array($found) && !empty($found['files'][0]['id'])) {
            return $found['files'][0]['id'];
        }

        $created = $this->json_request('POST', 'https://www.googleapis.com/drive/v3/files?fields=id,name', [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        return !empty($created['id']) ? $created['id'] : false;
    }

    public function ensure_path(array $segments)
    {
        $parent = $this->rootFolderId;
        foreach ($segments as $segment) {
            $parent = $this->ensure_folder($segment, $parent);
            if (!$parent) return false;
        }
        return $parent;
    }

    public function upload_to_path($localPath, $fileName, array $folderSegments, $mimeType = null)
    {
        if (!$this->is_enabled()) return false;
        if (!is_file($localPath)) {
            return $this->set_error('File lokal tidak ditemukan: ' . $localPath);
        }

        $folderId = $this->ensure_path($folderSegments);
        if (!$folderId) return false;

        return $this->upload_file($localPath, $fileName, $folderId, $mimeType);
    }

    public function upload_file($localPath, $fileName, $folderId, $mimeType = null)
    {
        if (!$this->is_enabled()) return false;
        if (!is_file($localPath)) {
            return $this->set_error('File lokal tidak ditemukan: ' . $localPath);
        }

        $mimeType = $mimeType ?: $this->detect_mime($localPath);
        $fileName = $this->safe_filename($fileName ?: basename($localPath));

        // Cegah duplikat: jika file dengan nama yang sama sudah ada di folder tujuan,
        // pakai file itu dan jangan upload ulang. Ini aman untuk tombol migrasi yang ditekan berkali-kali.
        $existing = $this->find_file_in_folder($fileName, $folderId);
        if ($existing && !empty($existing['id'])) {
            if ($this->makePublic) {
                $this->make_file_public($existing['id']);
            }
            return $this->file_result_to_drive_payload($existing);
        }

        $metadata = [
            'name' => $fileName,
            'parents' => [$folderId],
        ];

        $boundary = 'vi_drive_' . md5(uniqid('', true));
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata) . "\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$mimeType}\r\n\r\n";
        $body .= file_get_contents($localPath) . "\r\n";
        $body .= "--{$boundary}--";

        $url = 'https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,name,webViewLink,webContentLink,thumbnailLink';
        $res = $this->request_raw('POST', $url, [
            'Content-Type: multipart/related; boundary=' . $boundary,
        ], $body, true);

        if (!$res) return false;
        $json = json_decode($res['body'], true);
        if (empty($json['id'])) {
            return $this->set_error('Upload berhasil tetapi file_id tidak diterima.');
        }

        if ($this->makePublic) {
            $this->make_file_public($json['id']);
        }

        return [
            'id' => $json['id'],
            'name' => $json['name'] ?? $fileName,
            'webViewLink' => $json['webViewLink'] ?? '',
            'webContentLink' => $json['webContentLink'] ?? '',
            'thumbnailLink' => $json['thumbnailLink'] ?? '',
            'public_url' => 'https://drive.google.com/uc?export=view&id=' . rawurlencode($json['id']),
        ];
    }

    public function make_file_public($fileId)
    {
        if (!$fileId) return false;
        $res = $this->json_request('POST', 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '/permissions', [
            'role' => 'reader',
            'type' => 'anyone',
        ]);
        return $res !== false;
    }

    public function delete_file($fileId)
    {
        if (!$fileId || !$this->is_enabled()) return false;
        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId);
        return $this->request_raw('DELETE', $url, [], null, true) !== false;
    }



    public function file_metadata($fileId)
    {
        $fileId = trim((string)$fileId);
        if ($fileId === '') {
            return $this->set_error('File ID Google Drive kosong.');
        }

        if (!$this->is_enabled()) return false;

        $url = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?' . http_build_query([
            'fields' => 'id,name,mimeType,size,trashed',
        ], '', '&');

        $res = $this->request_raw('GET', $url, [], null, true);
        if (!$res || empty($res['body'])) return false;

        $meta = json_decode($res['body'], true);
        if (empty($meta['id'])) {
            return $this->set_error('Metadata file Google Drive tidak ditemukan.');
        }

        if (!empty($meta['trashed'])) {
            return $this->set_error('File Google Drive sudah berada di sampah: ' . $fileId);
        }

        return $meta;
    }

    public function get_file_media($fileId)
    {
        $fileId = trim((string)$fileId);
        if ($fileId === '') {
            return $this->set_error('File ID Google Drive kosong.');
        }

        if (!$this->is_enabled()) return false;

        $metaUrl = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?' . http_build_query([
            'fields' => 'id,name,mimeType,size',
        ], '', '&');

        $metaRes = $this->request_raw('GET', $metaUrl, [], null, true);
        if (!$metaRes || empty($metaRes['body'])) return false;

        $meta = json_decode($metaRes['body'], true);
        if (empty($meta['id'])) {
            return $this->set_error('Metadata file Google Drive tidak ditemukan.');
        }

        $mediaUrl = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?alt=media';
        $mediaRes = $this->request_raw('GET', $mediaUrl, [], null, true);
        if (!$mediaRes || $mediaRes['body'] === '') return false;

        return [
            'id' => $meta['id'],
            'name' => $meta['name'] ?? '',
            'mime_type' => $meta['mimeType'] ?? 'image/jpeg',
            'size' => $meta['size'] ?? null,
            'content' => $mediaRes['body'],
        ];
    }

    public function safe_name($name)
    {
        $name = trim((string)$name);
        $name = preg_replace('#[\\\/:*?"<>|]+#u', '-', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        $name = $name ?: 'Tanpa Nama';
        return function_exists('mb_substr') ? mb_substr($name, 0, 120, 'UTF-8') : substr($name, 0, 120);
    }

    public function safe_filename($name)
    {
        $name = trim((string)$name);
        $name = preg_replace('#[\\\/:*?"<>|]+#u', '-', $name);
        $name = preg_replace('/\s+/', '_', $name);
        $name = $name ?: ('file_' . date('YmdHis'));
        return function_exists('mb_substr') ? mb_substr($name, 0, 160, 'UTF-8') : substr($name, 0, 160);
    }

    public function detect_mime($path)
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $path);
            finfo_close($finfo);
            if ($mime) return $mime;
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'png') return 'image/png';
        if ($ext === 'webp') return 'image/webp';
        if ($ext === 'jpg' || $ext === 'jpeg') return 'image/jpeg';
        if ($ext === 'cdr') return 'application/octet-stream';
        if ($ext === 'ai') return 'application/postscript';
        if ($ext === 'eps') return 'application/postscript';
        if ($ext === 'pdf') return 'application/pdf';
        return 'application/octet-stream';
    }
}
