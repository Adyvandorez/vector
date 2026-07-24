<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Base controller REST API Vector Order.
 * Semua response menggunakan JSON konsisten dan autentikasi Bearer Token.
 */
class MY_Controller extends CI_Controller
{
    protected $api_client = null;
    protected $api_token_row = null;
    private $json_body_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['url', 'auth', 'storage']);
        $this->load->model('Client_model', 'clients');

        $origin = $this->config->item('vi_cors_origin') ?: '*';
        $this->output
            ->set_header('Access-Control-Allow-Origin: ' . $origin)
            ->set_header('Access-Control-Allow-Headers: Authorization, X-Authorization, Content-Type, Accept, X-Requested-With')
            ->set_header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->set_header('Access-Control-Max-Age: 86400')
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        if (strtoupper($this->input->method(true)) === 'OPTIONS') {
            $this->output->set_status_header(204)->set_output('')->_display();
            exit;
        }
    }

    protected function json_body()
    {
        if ($this->json_body_cache !== null) return $this->json_body_cache;
        $raw = (string)$this->input->raw_input_stream;
        $decoded = json_decode($raw, true);
        $this->json_body_cache = is_array($decoded) ? $decoded : [];
        return $this->json_body_cache;
    }

    protected function request_value($key, $default = null)
    {
        $json = $this->json_body();
        if (array_key_exists($key, $json)) return $json[$key];
        $post = $this->input->post($key, true);
        return $post !== null ? $post : $default;
    }

    protected function respond($payload, $status = 200)
    {
        return $this->output
            ->set_content_type('application/json', 'utf-8')
            ->set_status_header((int)$status)
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    protected function success($data = null, $message = 'Berhasil', $status = 200, array $meta = [])
    {
        $payload = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta) $payload['meta'] = $meta;
        return $this->respond($payload, $status);
    }

    protected function fail($message, $status = 400, $errors = null)
    {
        $payload = ['success' => false, 'message' => $message, 'data' => null];
        if ($errors !== null) $payload['errors'] = $errors;
        return $this->respond($payload, $status);
    }

    protected function bearer_token()
    {
        $header = $this->input->get_request_header('Authorization', true);
        if (!$header) $header = $this->input->get_request_header('X-Authorization', true);
        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization']
                ?? $headers['authorization']
                ?? $headers['X-Authorization']
                ?? $headers['x-authorization']
                ?? '';
        }
        if (!$header && isset($_SERVER['HTTP_AUTHORIZATION'])) $header = $_SERVER['HTTP_AUTHORIZATION'];
        if (!$header && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (!$header && isset($_SERVER['HTTP_X_AUTHORIZATION'])) $header = $_SERVER['HTTP_X_AUTHORIZATION'];
        return preg_match('/Bearer\s+(\S+)/i', (string)$header, $m) ? trim($m[1]) : '';
    }

    protected function require_client()
    {
        if ($this->api_client) return $this->api_client;
        $plain = $this->bearer_token();
        if ($plain === '') {
            $this->fail('Token autentikasi tidak ditemukan.', 401);
            $this->output->_display(); exit;
        }
        $hash = hash('sha256', $plain);
        $now = date('Y-m-d H:i:s');
        $this->db->select('t.*, c.name, c.phone, c.email, c.profile_photo, c.address, c.notes, c.is_active');
        $this->db->from('api_tokens t');
        $this->db->join('clients c', 'c.id=t.client_id');
        $this->db->where('t.token_hash', $hash);
        $this->db->where('t.revoked_at IS NULL', null, false);
        $this->db->group_start()->where('t.expires_at IS NULL', null, false)->or_where('t.expires_at >', $now)->group_end();
        $row = $this->db->limit(1)->get()->row();
        if (!$row || !(int)$row->is_active) {
            $this->fail('Sesi telah berakhir. Silakan login kembali.', 401);
            $this->output->_display(); exit;
        }
        $this->db->where('id', (int)$row->id)->update('api_tokens', ['last_used_at' => $now]);
        $this->api_token_row = $row;
        $this->api_client = $this->clients->find((int)$row->client_id);
        return $this->api_client;
    }

    protected function issue_token($client_id, $device_name = 'Android')
    {
        $plain = bin2hex(random_bytes(32));
        $this->db->insert('api_tokens', [
            'client_id' => (int)$client_id,
            'token_name' => 'android',
            'token_hash' => hash('sha256', $plain),
            'abilities' => '*',
            'device_name' => substr(trim((string)$device_name), 0, 120),
            'ip_address' => substr((string)$this->input->ip_address(), 0, 45),
            'user_agent' => substr((string)$this->input->user_agent(), 0, 255),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
            'created_at' => date('Y-m-d H:i:s')
        ]);
        return $plain;
    }

    protected function client_payload($client)
    {
        return [
            'id' => (int)$client->id,
            'name' => (string)$client->name,
            'phone' => (string)$client->phone,
            'email' => (string)$client->email,
            'profile_photo_url' => !empty($client->profile_photo) ? base_url(trim($client->profile_photo, '/')) : null,
            'address' => $client->address,
            'notes' => $client->notes,
            'is_active' => (bool)$client->is_active,
        ];
    }

    protected function pagination($default = 20, $max = 100)
    {
        $page = max(1, (int)$this->input->get('page'));
        $perPage = (int)$this->input->get('per_page');
        $perPage = $perPage > 0 ? min($max, $perPage) : $default;
        return [$page, $perPage, ($page - 1) * $perPage];
    }
}
