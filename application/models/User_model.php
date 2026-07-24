<?php
defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function all()
    {
        return $this->db->order_by('id', 'ASC')->get('users')->result();
    }

    public function find($id)
    {
        return $this->db->get_where('users', ['id' => (int)$id])->row();
    }

    public function find_by_username($username)
    {
        return $this->db->get_where('users', ['username' => $username, 'is_active' => 1])->row();
    }

    public function find_by_login_identity($identity)
    {
        return $this->db
            ->group_start()->where('username', $identity)->or_where('email', $identity)->group_end()
            ->where('is_active', 1)
            ->limit(1)
            ->get('users')
            ->row();
    }

    public function create(array $data)
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        return $this->db->insert('users', $data);
    }

    public function update($id, array $data)
    {
        return $this->db->where('id', (int)$id)->update('users', $data);
    }

    public function username_exists($username, $exclude_id = null)
    {
        $this->db->where('LOWER(username)', strtolower(trim((string)$username)));
        if ($exclude_id !== null) $this->db->where('id !=', (int)$exclude_id);
        return $this->db->count_all_results('users') > 0;
    }

    public function email_exists($email, $exclude_id = null)
    {
        $this->db->where('LOWER(email)', strtolower(trim((string)$email)));
        if ($exclude_id !== null) $this->db->where('id !=', (int)$exclude_id);
        return $this->db->count_all_results('users') > 0;
    }

    public function count_active_owners()
    {
        return (int)$this->db->where(['role' => 'OWNER', 'is_active' => 1])->count_all_results('users');
    }

    public function update_last_login($id)
    {
        return $this->update($id, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function save_remember_token($id, $token)
    {
        return $this->update($id, ['remember_token' => hash('sha256', $token)]);
    }

    public function find_by_remember_token($token)
    {
        return $this->db->get_where('users', ['remember_token' => hash('sha256', $token), 'is_active' => 1])->row();
    }

    public function clear_remember_token($id)
    {
        return $this->update($id, ['remember_token' => null]);
    }

    public function find_by_email($email)
    {
        return $this->db->get_where('users', ['email' => $email, 'is_active' => 1])->row();
    }

    public function find_by_email_or_username($identity)
    {
        return $this->find_by_login_identity($identity);
    }

    public function save_reset_token($id, $token, $expired)
    {
        return $this->update($id, ['reset_token' => hash('sha256', $token), 'reset_expired' => $expired]);
    }

    public function find_valid_reset_token($token)
    {
        return $this->db
            ->where('reset_token', hash('sha256', $token))
            ->where('reset_expired >', date('Y-m-d H:i:s'))
            ->where('is_active', 1)
            ->limit(1)->get('users')->row();
    }

    public function update_password($id, $password_hash)
    {
        return $this->update($id, [
            'password_hash' => $password_hash,
            'remember_token' => null,
            'reset_token' => null,
            'reset_expired' => null
        ]);
    }
}
