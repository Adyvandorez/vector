<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Auth_api extends MY_Controller
{
    public function register()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') return $this->fail('Method tidak diizinkan.', 405);
        $name = trim((string)$this->request_value('name'));
        $phone = normalize_phone($this->request_value('phone'));
        $email = strtolower(trim((string)$this->request_value('email')));
        $password = (string)$this->request_value('password');
        $confirmation = (string)$this->request_value('password_confirmation', $password);
        $errors = [];
        if (mb_strlen($name) < 3) $errors['name'] = 'Nama minimal 3 karakter.';
        if (strlen(preg_replace('/\D/', '', $phone)) < 9) $errors['phone'] = 'Nomor WhatsApp tidak valid.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Format email tidak valid.';
        if (strlen($password) < 8) $errors['password'] = 'Password minimal 8 karakter.';
        if ($password !== $confirmation) $errors['password_confirmation'] = 'Konfirmasi password tidak sama.';
        if ($this->clients->email_exists($email)) $errors['email'] = 'Email sudah terdaftar.';
        if ($errors) return $this->fail('Data registrasi belum valid.', 422, $errors);

        $now = date('Y-m-d H:i:s');
        $this->db->insert('clients', [
            'name' => $name, 'phone' => $phone, 'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'is_active' => 1, 'email_verified_at' => $now,
            'created_at' => $now, 'updated_at' => $now
        ]);
        $id = (int)$this->db->insert_id();
        $token = $this->issue_token($id, $this->request_value('device_name', 'Android'));
        $client = $this->clients->find($id);
        return $this->success(['token' => $token, 'token_type' => 'Bearer', 'client' => $this->client_payload($client)], 'Registrasi berhasil.', 201);
    }

    public function login()
    {
        if (strtoupper($this->input->method(true)) !== 'POST') return $this->fail('Method tidak diizinkan.', 405);
        $identity = trim((string)$this->request_value('email'));
        $password = (string)$this->request_value('password');
        if ($identity === '' || $password === '') return $this->fail('Email/nomor WhatsApp dan password wajib diisi.', 422);

        if (filter_var(strtolower($identity), FILTER_VALIDATE_EMAIL)) {
            $client = $this->clients->find_by_email(strtolower($identity));
        } else {
            $phone = normalize_phone($identity);
            $client = strlen(preg_replace('/\D/', '', $phone)) >= 9
                ? $this->clients->find_by_phone($phone)
                : null;
        }

        if (!$client || empty($client->password_hash) || !password_verify($password, $client->password_hash)) return $this->fail('Email/nomor WhatsApp atau password salah.', 401);
        if (!(int)$client->is_active) return $this->fail('Akun tidak aktif. Hubungi admin.', 403);
        $token = $this->issue_token((int)$client->id, $this->request_value('device_name', 'Android'));
        $this->clients->update($client->id, ['last_login' => date('Y-m-d H:i:s')]);
        return $this->success(['token' => $token, 'token_type' => 'Bearer', 'client' => $this->client_payload($client)], 'Login berhasil.');
    }

    public function logout()
    {
        $this->require_client();
        $this->db->where('id', (int)$this->api_token_row->id)->update('api_tokens', ['revoked_at' => date('Y-m-d H:i:s')]);
        return $this->success(['logged_out' => true], 'Logout berhasil.');
    }

    public function forgot_password()
    {
        $email = strtolower(trim((string)$this->request_value('email')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $this->fail('Masukkan email yang valid.', 422);
        $client = $this->clients->find_by_email($email);
        if ($client && $this->db->table_exists('password_reset_requests')) {
            $this->db->insert('password_reset_requests', [
                'client_id' => (int)$client->id, 'email' => $email,
                'status' => 'PENDING', 'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        return $this->success(['support_url' => 'https://linktr.ee/Ady_vandorez'], 'Permintaan reset dicatat. Hubungi admin melalui menu bantuan.');
    }
}
