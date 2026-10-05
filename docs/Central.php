<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Fallback stub untuk IDE / Intelephense saat file dibuka di luar environment CodeIgniter 3
 */
if (!defined('APPPATH')) {
    define('APPPATH', dirname(__DIR__) . '/application/');
}
if (!function_exists('log_message')) {
    function log_message($level, $message, $php_error = FALSE) {}
}

if (!class_exists('CI_Controller')) {
    class CI_DB_result
    {
        /** @return array<int, array<string, mixed>> */
        public function result_array() { return array(); }
        /** @return array<string, mixed>|null */
        public function row_array() { return array(); }
        /** @return array<int, object> */
        public function result() { return array(); }
        /** @return object|null */
        public function row() { return null; }
        /** @return int */
        public function num_rows() { return 0; }
    }

    class CI_Loader
    {
        public function database($params = '', $return = FALSE, $query_builder = NULL) {}
        public function model($model, $name = '', $db_conn = FALSE) {}
        public function view($view, $vars = array(), $return = FALSE) {}
    }

    class CI_DB_query_builder
    {
        /**
         * @param string|array $select
         * @param bool|null $escape
         * @return $this
         */
        public function select($select = '*', $escape = NULL) { return $this; }

        /**
         * @param string $from
         * @return $this
         */
        public function from($from) { return $this; }

        /**
         * @param string $table
         * @param string $cond
         * @param string $type
         * @param bool|null $escape
         * @return $this
         */
        public function join($table, $cond, $type = '', $escape = NULL) { return $this; }

        /**
         * @param mixed $key
         * @param mixed $value
         * @param bool|null $escape
         * @return $this
         */
        public function where($key, $value = NULL, $escape = NULL) { return $this; }

        /**
         * @param string $table
         * @param int|null $limit
         * @param int|null $offset
         * @return CI_DB_result
         */
        public function get($table = '', $limit = NULL, $offset = NULL) { return new CI_DB_result(); }

        /**
         * @param string $table
         * @param array|null $where
         * @param int|null $limit
         * @param int|null $offset
         * @return CI_DB_result
         */
        public function get_where($table = '', $where = NULL, $limit = NULL, $offset = NULL) { return new CI_DB_result(); }

        /** @return array */
        public function result() { return array(); }
        /** @return array */
        public function result_array() { return array(); }
        /** @return array|null */
        public function row_array() { return array(); }
        /** @return object|null */
        public function row() { return null; }
        /** @return int */
        public function affected_rows() { return 0; }
        /** @return $this */
        public function set($key = '', $value = '', $escape = NULL) { return $this; }
        /** @return bool */
        public function delete($table = '', $where = '') { return TRUE; }
        /** @return CI_DB_result */
        public function query($sql = '', $binds = FALSE, $return_object = NULL) { return new CI_DB_result(); }
        /** @return $this */
        public function order_by($orderby = '', $direction = '', $escape = NULL) { return $this; }
        /** @return $this */
        public function limit($value = NULL, $offset = 0) { return $this; }
        /** @return int */
        public function num_rows() { return 0; }
        /** @return bool */
        public function insert($table = '', $set = NULL, $escape = NULL) { return TRUE; }
        /** @return bool */
        public function update($table = '', $set = NULL, $where = NULL, $limit = NULL) { return TRUE; }
        /** @return int */
        public function insert_id() { return 0; }
        /** @return bool */
        public function table_exists($table_name) { return TRUE; }
    }

    class CI_Input
    {
        /** @var string|null */
        public $raw_input_stream;
        public function post($index = NULL, $xss_clean = NULL) { return NULL; }
        public function get($index = NULL, $xss_clean = NULL) { return NULL; }
    }

    class CI_Output
    {
        public function set_content_type($mime_type, $charset = NULL) { return $this; }
        public function set_output($output) { return $this; }
        public function set_status_header($code = 200, $text = '') { return $this; }
    }

    class CI_Controller
    {
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
 * 1. GET  /central/customers    -> Menyediakan data pelanggan lengkap untuk ditarik oleh Central (otomatis IP MikroTik PPPoE & Simple Queue)
 * 2. GET  /central/odps         -> Menyediakan data master titik ODP & port
 * 3. GET  /central/customer_network/{no_services} -> Real-time status koneksi, router, IP, dan uptime
 * 4. POST /central/send_ticket  -> Mengirim tiket gangguan dari CI3 ke Central
 * 5. POST /central/callback     -> Menerima pembaruan status tiket saat diselesaikan teknisi Central
 */
class Central extends CI_Controller
{
    // Konfigurasi Central Ticket System
    private $central_url = 'https://central-ticketing.test/api/v1';
    private $api_key     = 'key-bill-001-secret-12345'; // Sesuaikan dengan api_key billing Anda di Central

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 1. SINKRONISASI PELANGGAN
     * Dipanggil oleh Central saat admin mengklik "Sync Data" atau sinkronisasi CLI.
     * URL: http://domain-billing-anda/central/customers
     *
     * Fitur:
     * - Menyediakan data pelanggan lengkap (identitas, kontak, paket, ODP & port).
     * - Memetakan PPPoE username / user MikroTik.
     * - Mendapatkan alamat IP aktif langsung dari router MikroTik (Active PPPoE & Simple Queue Static IP).
     * - Fallback alamat IP dari tabel modem (ip_public / ip_local) bila pelanggan belum terpetakan.
     */
    public function customers()
    {
        $no_services_filter = $this->input->get('no_services', TRUE);
        $skip_mikrotik      = (int) $this->input->get('skip_mikrotik', TRUE);
        $skip_cache         = (int) $this->input->get('nocache', TRUE);

        // Ambil data pelanggan dari tabel customer lokal CI3 + join ODP & Router
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
            'c.connection',
            'c.mode_user',
            'c.user_mikrotik',
            'c.user_mikrotik as pppoe_user',
            'c.router as router_id',
            'c.no_port_odp',
            'o.code_odp as odp_name',
            'r.alias as router_name',
        ]);
        $this->db->from('customer c');
        $this->db->join('m_odp o', 'o.id_odp = c.id_odp', 'left');
        $this->db->join('router r', 'r.id = c.router', 'left');

        if (!empty($no_services_filter)) {
            $this->db->where('c.no_services', $no_services_filter);
        }

        $customers = $this->db->get()->result_array();

        // 1. Dapatkan IP aktif dari MikroTik (kecuali jika parameter skip_mikrotik=1)
        $mikrotikIps = [];
        if (!$skip_mikrotik) {
            $mikrotikIps = $this->get_active_mikrotik_sessions((bool)$skip_cache);
        }

        // 2. Dapatkan IP cadangan dari tabel modem lokal jika ada
        $modemIps = [];
        if ($this->db->table_exists('modem')) {
            $modemRows = $this->db->select('customer_id, ip_local, ip_public')
                ->from('modem')
                ->get()
                ->result_array();
            foreach ($modemRows as $m) {
                $mIp = !empty($m['ip_public']) ? trim($m['ip_public']) : trim($m['ip_local'] ?? '');
                if (!empty($mIp)) {
                    $modemIps[(int)$m['customer_id']] = $mIp;
                }
            }
        }

        // 3. Normalisasi status, pppoe_user, dan sematkan IP address aktual
        foreach ($customers as &$cust) {
            $raw_status  = strtolower(trim($cust['status'] ?? ''));
            $conn_status = (int)($cust['connection'] ?? 0);

            if ($conn_status == 1 || strpos($raw_status, 'isolir') !== false) {
                $cust['status'] = 'isolated';
            } elseif (strpos($raw_status, 'non') !== false || strpos($raw_status, 'inactive') !== false) {
                $cust['status'] = 'inactive';
            } elseif (strpos($raw_status, 'free') !== false || strpos($raw_status, 'gratis') !== false) {
                $cust['status'] = 'free';
            } elseif (strpos($raw_status, 'menunggu') !== false || strpos($raw_status, 'waiting') !== false) {
                $cust['status'] = 'inactive';
            } elseif (strpos($raw_status, 'aktif') !== false || strpos($raw_status, 'active') !== false) {
                $cust['status'] = 'active';
            } else {
                $cust['status'] = 'active';
            }

            // Normalisasi PPPoE Username / User MikroTik
            $uMikrotik  = trim($cust['user_mikrotik'] ?? '');
            $noServices = trim($cust['no_services'] ?? '');
            $cName      = trim($cust['name'] ?? '');
            $cust['pppoe_user'] = !empty($uMikrotik) ? $uMikrotik : null;

            // Resolusi Alamat IP
            $rId = (int)($cust['router_id'] ?? 0);
            $cId = (int)$cust['customer_id'];
            $resolvedIp = null;

            // Step A: Cari berdasarkan user_mikrotik (exact, lowercase, clean)
            if (!empty($uMikrotik)) {
                $resolvedIp = $mikrotikIps[$rId][$uMikrotik] 
                    ?? ($mikrotikIps[$rId][strtolower($uMikrotik)] ?? null)
                    ?? ($mikrotikIps['global'][$uMikrotik] 
                    ?? ($mikrotikIps['global'][strtolower($uMikrotik)] ?? null));

                if (empty($resolvedIp)) {
                    $cleanU = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $uMikrotik));
                    if (!empty($cleanU)) {
                        $resolvedIp = $mikrotikIps['clean'][$cleanU] ?? null;
                    }
                }
            }

            // Step B: Cari berdasarkan no_services di antrean queue (contoh nama queue: 220627140790_rikisetiawan)
            if (empty($resolvedIp) && !empty($noServices)) {
                $resolvedIp = $mikrotikIps['by_no_services'][$noServices] ?? null;
            }

            // Step C: Cari berdasarkan nama pelanggan (clean)
            if (empty($resolvedIp) && !empty($cName)) {
                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cName));
                if (!empty($cleanName)) {
                    $resolvedIp = $mikrotikIps['clean'][$cleanName] ?? null;
                }
            }

            // Step D: Fallback ke tabel modem jika belum ditemukan dari MikroTik
            if (empty($resolvedIp) && isset($modemIps[$cId])) {
                $resolvedIp = $modemIps[$cId];
            }

            $cust['ip_address'] = $resolvedIp;
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
     * Endpoint API Sinkronisasi Master ODP
     * URL: http://domain-billing-anda/central/odps
     */
    public function odps()
    {
        $odps = $this->db->select([
            'id_odp as remote_odp_id',
            'code_odp as code',
            'latitude',
            'longitude',
            'total_port as total_ports',
            'remark as notes'
        ])
        ->from('m_odp')
        ->get()
        ->result_array();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'count'  => count($odps),
                'data'   => $odps
            ]));
    }

    /**
     * Endpoint Real-Time Diagnostik Jaringan Pelanggan (MikroTik Live Check)
     * URL: http://domain-billing-anda/central/customer_network/{no_services}
     */
    public function customer_network($no_services = null)
    {
        if (empty($no_services)) {
            $no_services = $this->input->get('no_services', TRUE);
        }

        if (empty($no_services)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Parameter no_services wajib disertakan']));
            return;
        }

        $customer = $this->db->get_where('customer', ['no_services' => $no_services])->row_array();
        if (empty($customer)) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Pelanggan tidak ditemukan']));
            return;
        }

        $router = $this->db->get_where('router', ['id' => $customer['router']])->row_array();
        $userMikrotik = trim($customer['user_mikrotik'] ?? '');
        $modeUser = strtoupper(trim($customer['mode_user'] ?? ''));

        $networkInfo = [
            'no_services'     => $customer['no_services'],
            'name'            => $customer['name'],
            'user_mikrotik'   => $userMikrotik,
            'pppoe_user'      => $userMikrotik,
            'mode_user'       => $modeUser ?: 'PPPOE',
            'router_id'       => $customer['router'],
            'router_alias'    => $router['alias'] ?? '-',
            'is_online'       => false,
            'ip_address'      => null,
            'uptime'          => null,
            'caller_id'       => null,
            'last_disconnect' => null,
            'bytes_in'        => 0,
            'bytes_out'       => 0,
            'raw_session'     => null,
        ];

        if (!empty($router) && !empty($router['ip_address']) && (!empty($userMikrotik) || !empty($customer['name']))) {
            if (!class_exists('Mikweb')) {
                @require_once APPPATH . 'libraries/Mikweb.php';
            }

            if (class_exists('Mikweb')) {
                $api = new Mikweb();
                $api->timeout = 3;

                $connected = @$api->connect(
                    $router['ip_address'],
                    $router['username'],
                    $router['password'],
                    (int)$router['port']
                );

                if ($connected) {
                    // 1. Cek Sesi PPPoE
                    if ($modeUser === 'PPPOE' || empty($modeUser)) {
                        $active = @$api->comm('/ppp/active/print', ['?name' => $userMikrotik]);
                        if (!empty($active[0])) {
                            $networkInfo['is_online']   = true;
                            $networkInfo['ip_address']  = $active[0]['address'] ?? null;
                            $networkInfo['uptime']      = $active[0]['uptime'] ?? null;
                            $networkInfo['caller_id']   = $active[0]['caller-id'] ?? null;
                            $networkInfo['bytes_in']    = (int)($active[0]['limit-bytes-in'] ?? 0);
                            $networkInfo['bytes_out']   = (int)($active[0]['limit-bytes-out'] ?? 0);
                            $networkInfo['raw_session'] = $active[0];
                        }

                        $secret = @$api->comm('/ppp/secret/print', ['?name' => $userMikrotik]);
                        if (!empty($secret[0])) {
                            if (empty($networkInfo['ip_address']) && !empty($secret[0]['remote-address'])) {
                                $networkInfo['ip_address'] = $secret[0]['remote-address'];
                            }
                            $networkInfo['last_disconnect'] = $secret[0]['last-logged-out'] ?? null;
                        }
                    }

                    // 2. Cek Sesi Hotspot
                    if (($modeUser === 'HOTSPOT' || empty($networkInfo['ip_address'])) && empty($networkInfo['is_online'])) {
                        $active = @$api->comm('/ip/hotspot/active/print', ['?user' => $userMikrotik]);
                        if (!empty($active[0])) {
                            $networkInfo['is_online']   = true;
                            $networkInfo['ip_address']  = $active[0]['address'] ?? null;
                            $networkInfo['uptime']      = $active[0]['uptime'] ?? null;
                            $networkInfo['caller_id']   = $active[0]['mac-address'] ?? null;
                            $networkInfo['raw_session'] = $active[0];
                        }
                    }

                    // 3. Cek Simple Queue (Static IP)
                    if (empty($networkInfo['ip_address']) || $modeUser === 'STATIC') {
                        $queue = @$api->comm('/queue/simple/print', ['?name' => $userMikrotik]);
                        
                        // Jika exact query tidak ketemu, cari secara fuzzy di simple queue
                        if (empty($queue) || empty($queue[0]['target'])) {
                            $allQueues = @$api->comm('/queue/simple/print', ['.proplist' => 'name,target,disabled,bytes']);
                            if (is_array($allQueues)) {
                                $cleanU = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $userMikrotik));
                                $cleanN = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $customer['name'] ?? ''));
                                $ns     = trim($customer['no_services']);

                                foreach ($allQueues as $qItem) {
                                    $qName = trim($qItem['name'] ?? '');
                                    $cleanQ = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $qName));
                                    
                                    if (strcasecmp($qName, $userMikrotik) === 0 
                                        || ($cleanU && $cleanQ === $cleanU)
                                        || ($cleanN && $cleanQ === $cleanN)
                                        || ($ns && strpos($qName, $ns) !== false)) {
                                        $queue = [$qItem];
                                        break;
                                    }
                                }
                            }
                        }

                        if (!empty($queue[0]['target'])) {
                            $rawTarget = trim($queue[0]['target']);
                            if (preg_match('/(?:^|[,\s])([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(?:\/32)?(?:[,\s]|$)/', $rawTarget, $tMatch)) {
                                $cleanIp = $tMatch[1];
                                if (filter_var($cleanIp, FILTER_VALIDATE_IP)) {
                                    $networkInfo['ip_address']  = $cleanIp;
                                    $networkInfo['is_online']   = ($queue[0]['disabled'] === 'false' || $queue[0]['disabled'] === false);
                                    $networkInfo['uptime']      = 'Static Active';
                                    $networkInfo['raw_session'] = $queue[0];
                                    if (isset($queue[0]['bytes'])) {
                                        $bytes = explode('/', $queue[0]['bytes']);
                                        $networkInfo['bytes_in']  = (int)($bytes[0] ?? 0);
                                        $networkInfo['bytes_out'] = (int)($bytes[1] ?? 0);
                                    }
                                }
                            }
                        }
                    }

                    @$api->disconnect();
                }
            }
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'data'   => $networkInfo
            ]));
    }

    /**
     * Helper Internal: Mengambil seluruh sesi aktif dari semua router MikroTik secara bulk
     * Sangat cepat (< 0.5s via cache / < 4s live) dan aman dengan timeout singkat serta proteksi error.
     *
     * @param bool $skipCache
     * @return array [router_id => [username => ip], 'global' => [username => ip], 'clean' => [...], 'by_no_services' => [...]]
     */
    private function get_active_mikrotik_sessions(bool $skipCache = false): array
    {
        $activeMap = [
            'global'         => [],
            'clean'          => [],
            'by_no_services' => [],
        ];

        $cacheFile = APPPATH . 'cache/mikrotik_sessions_cache.json';
        $cacheTtl  = 300; // 5 menit

        if (!$skipCache && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
            $cached = @json_decode(file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached) && !empty($cached['global'])) {
                return $cached;
            }
        }

        if (!class_exists('Mikweb')) {
            @require_once APPPATH . 'libraries/Mikweb.php';
        }

        if (!class_exists('Mikweb')) {
            return $activeMap;
        }

        // Ambil semua router yang ada di database
        $routers = $this->db->get('router')->result_array();
        if (empty($routers)) {
            return $activeMap;
        }

        foreach ($routers as $router) {
            $routerId = (int)$router['id'];
            $activeMap[$routerId] = [];

            if (empty($router['ip_address']) || empty($router['username'])) {
                continue;
            }

            try {
                $api = new Mikweb();
                $api->timeout = 3; // Timeout 3 detik agar tidak membebani proses sinkronisasi

                $connected = @$api->connect(
                    $router['ip_address'],
                    $router['username'],
                    $router['password'],
                    (int)$router['port']
                );

                if (!$connected) {
                    continue;
                }

                // 1. Bulk PPPoE Active Sessions (.proplist untuk hemat bandwidth & kecepatan maksimal)
                $pppoeActive = @$api->comm('/ppp/active/print', ['.proplist' => 'name,address']);
                if (is_array($pppoeActive)) {
                    foreach ($pppoeActive as $sess) {
                        $uname = trim($sess['name'] ?? '');
                        $uAddr = trim($sess['address'] ?? '');
                        if (!empty($uname) && !empty($uAddr)) {
                            $activeMap[$routerId][$uname]            = $uAddr;
                            $activeMap[$routerId][strtolower($uname)] = $uAddr;
                            $activeMap['global'][$uname]             = $uAddr;
                            $activeMap['global'][strtolower($uname)] = $uAddr;

                            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $uname));
                            if (!empty($cleanName)) {
                                $activeMap['clean'][$cleanName] = $uAddr;
                            }

                            if (preg_match('/([0-9]{4,15})/', $uname, $nsMatch)) {
                                $activeMap['by_no_services'][$nsMatch[1]] = $uAddr;
                            }
                        }
                    }
                }

                // 2. Bulk Hotspot Active Sessions
                $hotspotActive = @$api->comm('/ip/hotspot/active/print', ['.proplist' => 'user,name,address']);
                if (is_array($hotspotActive)) {
                    foreach ($hotspotActive as $sess) {
                        $uname = trim($sess['user'] ?? ($sess['name'] ?? ''));
                        $uAddr = trim($sess['address'] ?? '');
                        if (!empty($uname) && !empty($uAddr)) {
                            $activeMap[$routerId][$uname]            = $uAddr;
                            $activeMap[$routerId][strtolower($uname)] = $uAddr;
                            $activeMap['global'][$uname]             = $uAddr;
                            $activeMap['global'][strtolower($uname)] = $uAddr;

                            $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $uname));
                            if (!empty($cleanName)) {
                                $activeMap['clean'][$cleanName] = $uAddr;
                            }

                            if (preg_match('/([0-9]{4,15})/', $uname, $nsMatch)) {
                                $activeMap['by_no_services'][$nsMatch[1]] = $uAddr;
                            }
                        }
                    }
                }

                // 3. Bulk Simple Queue (Static IP target host /32 atau IP tunggal)
                $queues = @$api->comm('/queue/simple/print', ['.proplist' => 'name,target,disabled,comment']);
                if (is_array($queues)) {
                    foreach ($queues as $q) {
                        $uname   = trim($q['name'] ?? '');
                        $target  = trim($q['target'] ?? '');
                        $comment = trim($q['comment'] ?? '');

                        if (empty($uname) || empty($target)) {
                            continue;
                        }

                        // Abaikan antrean subnet agregat induk (/24, /22, /20, /16)
                        if (preg_match('/\/(?:[1-2][0-9]|3[0-1])$/', $target)) {
                            continue;
                        }

                        // Ekstrak IP host pelanggan
                        if (preg_match('/(?:^|[,\s])([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(?:\/32)?(?:[,\s]|$)/', $target, $m)) {
                            $ip = $m[1];
                            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                                $activeMap[$routerId][$uname]            = $ip;
                                $activeMap[$routerId][strtolower($uname)] = $ip;
                                $activeMap['global'][$uname]             = $ip;
                                $activeMap['global'][strtolower($uname)] = $ip;

                                $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $uname));
                                if (!empty($cleanName)) {
                                    $activeMap['clean'][$cleanName] = $ip;
                                }

                                if (preg_match('/([0-9]{4,15})/', $uname, $nsMatch)) {
                                    $activeMap['by_no_services'][$nsMatch[1]] = $ip;
                                }

                                if (!empty($comment) && preg_match('/([0-9]{4,15})/', $comment, $cmMatch)) {
                                    $activeMap['by_no_services'][$cmMatch[1]] = $ip;
                                }
                            }
                        }
                    }
                }

                @$api->disconnect();
            } catch (\Throwable $e) {
                // Abaikan error koneksi router agar proses sinkronisasi pelanggan tetap berjalan lancar
                log_message('error', 'Central Sync MikroTik connect error on router ID ' . $routerId . ': ' . $e->getMessage());
            }
        }

        if (!empty($activeMap['global'])) {
            @file_put_contents($cacheFile, json_encode($activeMap), LOCK_EX);
        }

        return $activeMap;
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
                // INSERT tiket baru yang dibuat dari Central (create_by = 0 menandakan dari Central Hub)
                $insert_data = [
                    'no_ticket'       => !empty($ticket_number) ? $ticket_number : (!empty($remote_id) ? $remote_id : 'TKT-' . date('Ymd') . '-' . rand(100, 999)),
                    'no_services'     => !empty($no_services) ? $no_services : '',
                    'description'     => !empty($data['problem_description']) ? $data['problem_description'] : $remark,
                    'date_created'    => time(),
                    'status'          => $status,
                    'help_type'       => 1,
                    'help_solution'   => 1,
                    'teknisi'         => 0,
                    'create_by'       => 0,
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
            ->set_output(json_encode(['status' => false, 'message' => 'Table help not found']));
    }
}