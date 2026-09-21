<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fallback stub untuk IDE / Intelephense saat file dibuka di luar environment CodeIgniter 3
 */
if (!class_exists('CI_Controller')) {
    class CI_Loader {
        public function database($params = '', $return = FALSE, $query_builder = NULL) {}
        public function model($model, $name = '', $db_conn = FALSE) {}
        public function view($view, $vars = array(), $return = FALSE) {}
    }
    class CI_DB_query_builder {
        public function select($select = '*', $escape = NULL) { return $this; }
        public function from($from) { return $this; }
        public function join($table, $cond, $type = '', $escape = NULL) { return $this; }
        public function where($key, $value = NULL, $escape = NULL) { return $this; }
        public function get($table = '', $limit = NULL, $offset = NULL) { return $this; }
        public function get_where($table = '', $where = NULL, $limit = NULL, $offset = NULL) { return $this; }
        public function result_array() { return array(); }
        public function row_array() { return array(); }
        public function insert($table = '', $set = NULL, $escape = NULL) { return TRUE; }
        public function update($table = '', $set = NULL, $where = NULL, $limit = NULL) { return TRUE; }
        public function insert_id() { return 0; }
        public function table_exists($table_name) { return TRUE; }
    }
    class CI_Input {
        /** @var string|null */
        public $raw_input_stream;
        public function post($index = NULL, $xss_clean = NULL) { return NULL; }
        public function get($index = NULL, $xss_clean = NULL) { return NULL; }
    }
    class CI_Output {
        public function set_content_type($mime_type, $charset = NULL) { return $this; }
        public function set_output($output) { return $this; }
        public function set_status_header($code = 200, $text = '') { return $this; }
    }
    class CI_Controller {
        /** @var CI_Loader */
        public $load;
        /** @var CI_DB_query_builder */
        public $db;
        /** @var CI_Input */
        public $input;
        /** @var CI_Output */
        public $output;
    }
}

/**
 * Controller Central: Penghubung Tunggal CI3 ke Central Ticket System
 *
 * Simpan file ini di: application/controllers/Central.php pada aplikasi CodeIgniter 3 Anda.
 *
 * @property CI_Loader $load
 * @property CI_DB_query_builder $db
 * @property CI_Input $input
 * @property CI_Output $output
 *
 * Fitur:
 * 1. GET  /central/customers    -> Menyediakan data pelanggan untuk ditarik oleh Central
 * 2. POST /central/send_ticket  -> Mengirim tiket gangguan dari CI3 ke Central
 * 3. POST /central/callback     -> Menerima pembaruan status tiket saat diselesaikan teknisi Central
 */
class Central extends CI_Controller
{
    // Konfigurasi Central Ticket System
    private $central_url = 'http://central-ticket-system.test/api/v1';
    private $api_key     = 'key-bill-001-secret-12345'; // Sesuaikan dengan api_key billing Anda di Central

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 1. SINKRONISASI PELANGGAN
     * Dipanggil oleh Central saat admin mengklik "Sync Data" di menu Pelanggan.
     * URL: http://domain-billing-anda/central/customers
     */
    public function customers()
    {
        // Ambil data pelanggan dari tabel customer lokal CI3
        $this->db->select([
            'c.customer_id',
            'c.no_services',
            'c.name',
            'c.no_wa as phone',
            'c.address',
            'c.latitude',
            'c.longitude',
            'c.user_profile as package_name',
            'c.cust_amount as monthly_fee',
            'c.c_status as status',
            'o.code_odp as odp_name'
        ]);
        $this->db->from('customer c');
        $this->db->join('m_odp o', 'o.id_odp = c.id_odp', 'left');
        $customers = $this->db->get()->result_array();

        // Normalisasi status ke format Central
        foreach ($customers as &$cust) {
            $status = strtolower($cust['status'] ?? '');
            if (strpos($status, 'aktif') !== false || strpos($status, 'active') !== false) {
                $cust['status'] = 'active';
            } elseif (strpos($status, 'isolir') !== false) {
                $cust['status'] = 'isolated';
            } else {
                $cust['status'] = 'inactive';
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'count'  => count($customers),
                'data'   => $customers
            ]));
    }

    /**
     * 2. KIRIM TIKET GANGGUAN KE CENTRAL
     * Cara panggil dari controller/model lain di CI3:
     *
     *   $this->load->controller('Central'); // atau panggil via cURL internal
     *   $res = $this->Central->kirim_tiket([
     *       'remote_ticket_id'    => 'TKT-101',
     *       'no_services'         => '10029384',
     *       'customer_name'       => 'Budi Santoso',
     *       'customer_phone'      => '081234567890',
     *       'customer_address'    => 'Jl. Melati No. 5',
     *       'category_name'       => 'Internet Lambat',
     *       'problem_description' => 'Lampu LOS merah berkedip',
     *       'created_by_name'     => 'CS Billing',
     *       'created_by_role'     => 'Operator'
     *   ]);
     */
    public function kirim_tiket(array $ticket_data)
    {
        $ch = curl_init($this->central_url . '/tickets');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($ticket_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $this->api_key
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Endpoint alternatif via HTTP POST jika ingin memicu kirim tiket via browser / AJAX
     * URL: http://domain-billing-anda/central/send_ticket
     */
    public function send_ticket()
    {
        $raw = $this->input->raw_input_stream ?: file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $this->input->post();

        if (empty($payload)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Data tiket tidak boleh kosong']));
            return;
        }

        $result = $this->kirim_tiket($payload);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($result));
    }

    /**
     * 3. WEBHOOK CALLBACK DARI CENTRAL
     * Dipanggil otomatis oleh Central saat:
     * - Tiket baru dibuat di Central (event: ticket_created)
     * - Status tiket diperbarui teknisi Central (event: status_updated)
     * URL: http://domain-billing-anda/central/callback
     */
    public function callback()
    {
        $raw = $this->input->raw_input_stream ?: file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (empty($data)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Empty payload']));
            return;
        }

        $ticket_number = $data['ticket_number'] ?? null;
        $remote_id     = $data['remote_ticket_id'] ?? null;
        $no_services   = $data['no_services'] ?? null;
        $status        = $data['status'] ?? 'pending';
        $remark        = $data['remark'] ?? ($data['problem_description'] ?? 'Tiket dari Central Ticket System');
        $event         = $data['event'] ?? 'status_updated';
        $tech_name     = $data['technician_name'] ?? null;

        // Pastikan tabel help ada di database CI3
        if ($this->db->table_exists('help')) {
            // Cek apakah tiket sudah ada di tabel help CI3
            $existing = null;
            if (!empty($ticket_number)) {
                $existing = $this->db->get_where('help', ['no_ticket' => $ticket_number])->row_array();
                if (!$existing && strpos($ticket_number, 'T-') === 0) {
                    $existing = $this->db->get_where('help', ['no_ticket' => substr($ticket_number, 2)])->row_array();
                }
            }
            if (!$existing && !empty($remote_id)) {
                if (is_numeric($remote_id)) {
                    $existing = $this->db->get_where('help', ['id' => $remote_id])->row_array();
                } else {
                    $existing = $this->db->get_where('help', ['no_ticket' => $remote_id])->row_array();
                    if (!$existing && strpos($remote_id, 'T-') === 0) {
                        $existing = $this->db->get_where('help', ['no_ticket' => substr($remote_id, 2)])->row_array();
                    }
                }
            }

            // Cari admin user yang valid di tabel user sebagai pembuat/pengupdate tiket
            $admin_user = $this->db->get_where('user', ['role_id' => 1])->row_array();
            if (empty($admin_user)) {
                $admin_user = $this->db->get('user', 1)->row_array();
            }
            $create_by_id = !empty($admin_user['id']) ? (int)$admin_user['id'] : 696;

            if ($existing) {
                // UPDATE status tiket yang sudah ada
                $this->db->where('id', $existing['id'])->update('help', [
                    'status' => $status,
                ]);
                $help_id = $existing['id'];

                if ($event === 'technician_assigned') {
                    $action_text = 'Penugasan Teknisi: ' . ($tech_name ?: 'Central');
                } else {
                    $action_text = 'Update Status: ' . strtoupper($status);
                }
            } else {
                // INSERT tiket baru yang dibuat dari Central
                $insert_data = [
                    'no_ticket'       => !empty($ticket_number) ? $ticket_number : (!empty($remote_id) ? $remote_id : 'TKT-' . date('Ymd') . '-' . rand(100, 999)),
                    'no_services'     => !empty($no_services) ? $no_services : '',
                    'description'     => !empty($data['problem_description']) ? $data['problem_description'] : $remark,
                    'date_created'    => time(),
                    'status'          => $status,
                    'help_type'       => 1,
                    'help_solution'   => 1,
                    'teknisi'         => $create_by_id,
                    'create_by'       => $create_by_id,
                    'action'          => 0,
                    'estimation'      => 0,
                    'picture'         => '',
                    'ticket_password' => '',
                ];

                $this->db->insert('help', $insert_data);
                $help_id = $this->db->insert_id();
                $action_text = 'Tiket Dibuat di Central Ticket System';
            }

            // Catat riwayat di help_timeline jika tabel tersedia
            if ($this->db->table_exists('help_timeline') && !empty($help_id)) {
                $updater_name = !empty($data['updated_by_name']) ? $data['updated_by_name'] : (!empty($data['technician_name']) ? $data['technician_name'] : (!empty($data['created_by_name']) ? $data['created_by_name'] : 'Central Ticket System'));
                $updater_role = !empty($data['updated_by_role']) ? ucfirst($data['updated_by_role']) : 'Admin';
                $action_user  = $updater_name . ' (' . $updater_role . ')';

                $this->db->insert('help_timeline', [
                    'help_id'      => $help_id,
                    'date_update'  => time(),
                    'remark'       => $remark,
                    'teknisi'      => 0,
                    'status'       => $status,
                    'date_created' => date('d-m-Y H:i:s'),
                    'action'       => $action_user
                ]);
            }

            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status'    => true,
                    'message'   => 'Ticket synchronized to CI3 help table',
                    'help_id'   => $help_id,
                    'no_ticket' => $existing ? $existing['no_ticket'] : ($insert_data['no_ticket'] ?? $ticket_number)
                ]));
            return;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => true, 'message' => 'Table help not found']));
    }
}
