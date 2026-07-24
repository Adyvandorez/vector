<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Order dianggap VALID hanya jika masih punya item.
     */
    private function valid_orders_where($alias = 'orders')
    {
        return "EXISTS (
            SELECT 1 FROM order_items oi
            WHERE oi.order_id = {$alias}.id
        )";
    }

    /**
     * Semua statistik dashboard memakai tanggal input/deadline.
     * Jika deadline kosong, sistem fallback ke created_at agar order baru tetap terbaca.
     */
    private function dashboard_date_sql($alias = 'orders')
    {
        /*
         * HOTFIX 500:
         * Beberapa database lama masih menyimpan deadline = 0000-00-00.
         * Pada MySQL mode ketat, DATE(NULLIF(deadline, '0000-00-00')) bisa error:
         * Incorrect DATE value: '0000-00-00'.
         * Solusi: deadline di-cast ke CHAR dulu, nilai 0000-00-00/kosong dijadikan NULL,
         * lalu fallback ke created_at. Jadi dashboard tetap aman tanpa menghapus data lama.
         */
        return "COALESCE(STR_TO_DATE(NULLIF(NULLIF(CAST({$alias}.deadline AS CHAR), '0000-00-00'), ''), '%Y-%m-%d'), DATE({$alias}.created_at))";
    }


    /**
     * Label tanggal dashboard dalam format Indonesia sederhana.
     */
    private function format_date_id($date = null)
    {
        $date = $date ?: date('Y-m-d');
        $months = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];

        $ts = strtotime($date);
        if (!$ts) return date('d M Y');

        return date('d', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }

    private function sum_paid_between($startDate, $endDate)
    {
        $dateSql = $this->dashboard_date_sql('orders');
        return (int)$this->db->query("
            SELECT COALESCE(SUM(paid),0) AS s
            FROM orders
            WHERE {$dateSql} >= ? AND {$dateSql} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startDate, $endDate])->row()->s;
    }

    private function percent_change($current, $previous)
    {
        $current = (float)$current;
        $previous = (float)$previous;

        if ($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function index()
    {
        $orderDate = $this->dashboard_date_sql('orders');

        // =========================
        // RANGE BERDASARKAN DEADLINE/TANGGAL INPUT ORDER
        // =========================
        $startMonth = date('Y-m-01');
        $endMonth   = date('Y-m-t');
        $startYear  = date('Y-01-01');
        $endYear    = date('Y-12-31');

        $prevMonthStart = date('Y-m-01', strtotime('first day of previous month'));
        $prevMonthEnd   = date('Y-m-t', strtotime('last day of previous month'));
        $prevYearStart  = date('Y-01-01', strtotime('-1 year'));
        $prevYearEnd    = date('Y-12-31', strtotime('-1 year'));

        $recent30Start = date('Y-m-d', strtotime('-29 days'));
        $recent30End   = date('Y-m-d');
        $prev30Start   = date('Y-m-d', strtotime('-59 days'));
        $prev30End     = date('Y-m-d', strtotime('-30 days'));

        // =========================
        // TOTAL ORDER BULAN INI (VALID)
        // =========================
        $total_orders = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startMonth, $endMonth])->row()->c;

        // =========================
        // PAID BULAN INI (VALID)
        // =========================
        $income = (int)$this->db->query("
            SELECT COALESCE(SUM(paid),0) AS s
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startMonth, $endMonth])->row()->s;

        // =========================
        // PAID TAHUN INI & SELURUH PAID (VALID)
        // =========================
        $income_year = (int)$this->db->query("
            SELECT COALESCE(SUM(paid),0) AS s
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startYear, $endYear])->row()->s;

        $income_all = (int)$this->db->query("
            SELECT COALESCE(SUM(paid),0) AS s
            FROM orders
            WHERE {$this->valid_orders_where('orders')}
        ")->row()->s;

        // =========================
        // TOTAL BULAN INI (VALID)
        // =========================
        $monthTotal = (int)$this->db->query("
            SELECT COALESCE(SUM(total),0) AS s
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startMonth, $endMonth])->row()->s;

        // =========================
        // BELUM DIBAYAR BULAN INI / TAHUN INI / SEMUA
        // =========================
        $monthUnpaid = (int)$this->db->query("
            SELECT COALESCE(SUM(GREATEST(total - paid, 0)), 0) AS s
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startMonth, $endMonth])->row()->s;

        $unpaid_orders_month = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND (total - paid) > 0
              AND {$this->valid_orders_where('orders')}
        ", [$startMonth, $endMonth])->row()->c;

        $yearUnpaid = (int)$this->db->query("
            SELECT COALESCE(SUM(GREATEST(total - paid, 0)), 0) AS s
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startYear, $endYear])->row()->s;

        $unpaid_orders_year = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND (total - paid) > 0
              AND {$this->valid_orders_where('orders')}
        ", [$startYear, $endYear])->row()->c;

        $allUnpaid = (int)$this->db->query("
            SELECT COALESCE(SUM(GREATEST(total - paid, 0)), 0) AS s
            FROM orders
            WHERE {$this->valid_orders_where('orders')}
        ")->row()->s;

        $unpaid_orders_all = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE (total - paid) > 0
              AND {$this->valid_orders_where('orders')}
        ")->row()->c;

        // =========================
        // BELUM LUNAS (GLOBAL)
        // =========================
        $unpaid = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE status != 'LUNAS'
              AND {$this->valid_orders_where('orders')}
        ")->row()->c;

        // =========================
        // TOTAL ORDER (SEMUA VALID)
        // =========================
        $total_orders_all = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE {$this->valid_orders_where('orders')}
        ")->row()->c;

        $total_orders_year = (int)$this->db->query("
            SELECT COUNT(*) AS c
            FROM orders
            WHERE {$orderDate} >= ? AND {$orderDate} <= ?
              AND {$this->valid_orders_where('orders')}
        ", [$startYear, $endYear])->row()->c;

        // =========================
        // PERSENTASE AKTIF CARD PAID
        // =========================
        $recent30Paid = $this->sum_paid_between($recent30Start, $recent30End);
        $prev30Paid   = $this->sum_paid_between($prev30Start, $prev30End);
        $prevYearPaid = $this->sum_paid_between($prevYearStart, $prevYearEnd);
        $prevMonthPaid = $this->sum_paid_between($prevMonthStart, $prevMonthEnd);

        $paid_trend_all   = $this->percent_change($recent30Paid, $prev30Paid);
        $paid_trend_year  = $this->percent_change($income_year, $prevYearPaid);
        $paid_trend_month = $this->percent_change($income, $prevMonthPaid);

        // =========================
        // JUMLAH MASTER (AMAN)
        // =========================
        $design_count = (int)$this->db
            ->where('is_active', 1)
            ->count_all_results('design_types');

        $price_count = (int)$this->db->count_all('price_matrix');
        $client_count = (int)$this->db->where('is_active', 1)->count_all_results('clients');
        $registered_client_count = (int)$this->db->where('is_active', 1)->where('email IS NOT NULL', null, false)->where('password_hash IS NOT NULL', null, false)->count_all_results('clients');
        $android_order_count = $this->db->field_exists('created_via', 'orders')
            ? (int)$this->db->where('created_via', 'ANDROID')->count_all_results('orders')
            : 0;

        // =========================
        // GOAL %
        // =========================
        $pct = ($monthTotal > 0)
            ? min(100, (int)round(($income / $monthTotal) * 100))
            : 0;

        // =========================
        // TREND 7 HARI (VALID, BERDASARKAN DEADLINE)
        // =========================
        $days   = [];
        $counts = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $days[] = $day;

            $c = (int)$this->db->query("
                SELECT COUNT(*) AS c
                FROM orders
                WHERE {$orderDate} = ?
                  AND {$this->valid_orders_where('orders')}
            ", [$day])->row()->c;

            $counts[] = $c;
        }

        $maxCount = max($counts);
        if ($maxCount < 1) $maxCount = 1;

        // =========================
        // LATEST ORDERS (VALID ONLY)
        // =========================
        // Latest orders memakai alias `o`, jadi ekspresi tanggal juga wajib memakai alias `o`.
        // Jika memakai `$orderDate` di query ini, MySQL akan mencari `orders.deadline`
        // padahal tabel sudah diberi alias `o`, sehingga muncul error Unknown column.
        $latestOrderDate = $this->dashboard_date_sql('o');
        $latest = $this->db->query("
            SELECT o.id,o.order_code,o.title,o.status,o.total,o.deadline,c.name AS client_name
            FROM orders o
            JOIN clients c ON c.id=o.client_id
            WHERE EXISTS (
                SELECT 1 FROM order_items oi
                WHERE oi.order_id = o.id
            )
            ORDER BY {$latestOrderDate} DESC, o.created_at DESC, o.id DESC
            LIMIT 4
        ")->result();

        $data = [
            'title'                => 'Dashboard',

            'total_orders'         => $total_orders,
            'income'               => $income,
            'income_year'          => $income_year,
            'income_all'           => $income_all,
            'paid_trend_all'       => $paid_trend_all,
            'paid_trend_year'      => $paid_trend_year,
            'paid_trend_month'     => $paid_trend_month,
            'monthTotal'           => $monthTotal,
            'monthUnpaid'          => $monthUnpaid,
            'yearUnpaid'           => $yearUnpaid,
            'allUnpaid'            => $allUnpaid,
            'unpaid_orders_month'  => $unpaid_orders_month,
            'unpaid_orders_year'   => $unpaid_orders_year,
            'unpaid_orders_all'    => $unpaid_orders_all,

            'unpaid'               => $unpaid,
            'pct'                  => $pct,

            'total_orders_all'     => $total_orders_all,
            'total_orders_year'    => $total_orders_year,
            'design_count'         => $design_count,
            'price_count'          => $price_count,
            'client_count'         => $client_count,
            'registered_client_count' => $registered_client_count,
            'android_order_count'  => $android_order_count,

            'days'                 => $days,
            'counts'               => $counts,
            'maxCount'             => $maxCount,

            'latest'               => $latest,
            'dashboard_date_label' => $this->format_date_id(date('Y-m-d')),

            'page_css'             => ['dashboard.css', 'dashboard-mobile.css'],
            'page_js'              => ['dashboard.js']
        ];

        $this->load->view('layout/header', $data);
        $this->load->view('layout/sidebar');
        $this->load->view('dashboard', $data);
        $this->load->view('layout/footer');
    }
}
