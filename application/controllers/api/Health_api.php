<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Health_api extends MY_Controller
{
    public function index()
    {
        $dbOk = false;
        try { $dbOk = (bool)$this->db->query('SELECT 1 AS ok')->row(); } catch (Throwable $e) { $dbOk = false; }
        return $this->success([
            'service' => 'Ady_vandorez Vector Order API',
            'version' => '1.0.0',
            'database' => $dbOk ? 'connected' : 'disconnected',
            'server_time' => date(DATE_ATOM)
        ], 'API aktif');
    }
}
