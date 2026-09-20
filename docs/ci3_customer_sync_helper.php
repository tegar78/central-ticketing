<?php
/**
 * CodeIgniter 3 Helper: Sync Customer to Central Ticket System Hub
 *
 * Sediakan potongan kode helper ini pada aplikasi CodeIgniter 3 billing lokal.
 * Panggil fungsi `sync_customer_to_central_hub()` pada Controller/Model CI3
 * saat event tambah/edit data pelanggan terjadi.
 */

if (!function_exists('sync_customer_to_central_hub')) {
    /**
     * Synchronize single or batch customer data to Central Ticket System Hub via cURL HTTP POST.
     *
     * @param array $customer_data Single customer array OR array of customer arrays
     * @param string|null $central_api_url Full API endpoint (default: http://127.0.0.1:8000/api/v1/sync-customer)
     * @param string|null $api_key API Key issued by Central Ticket System for this CI3 billing node
     * @return array Response payload ['success' => bool, 'message' => string, 'processed_count' => int, 'http_code' => int]
     */
    function sync_customer_to_central_hub(array $customer_data, $central_api_url = null, $api_key = null)
    {
        // 1. Resolve Configuration Defaults (dapat diambil dari $CI->config->item(...) di CI3)
        if (empty($central_api_url)) {
            $central_api_url = defined('CENTRAL_HUB_URL') 
                ? CENTRAL_HUB_URL 
                : 'http://127.0.0.1:8000/api/v1/sync-customer';
        }

        if (empty($api_key)) {
            $api_key = defined('CENTRAL_HUB_API_KEY') 
                ? CENTRAL_HUB_API_KEY 
                : 'key-bill-001-secret-12345';
        }

        // 2. Format Payload (Pastikan data bertipe bersih)
        $payload_json = json_encode($customer_data);

        // 3. Initialize cURL Session
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $central_api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload_json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // 5 seconds timeout to prevent blocking local billing operations
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $api_key,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // 4. Handle Network & Connection Errors
        if ($curl_error) {
            $error_msg = "Central Hub Sync cURL Error: " . $curl_error;
            if (function_exists('log_message')) {
                log_message('error', $error_msg);
            }
            return [
                'success' => false,
                'message' => $error_msg,
                'processed_count' => 0,
                'http_code' => 0
            ];
        }

        // 5. Decode Response JSON
        $decoded = json_decode($response_body, true);

        if ($http_code >= 200 && $http_code < 300 && isset($decoded['success']) && $decoded['success'] === true) {
            if (function_exists('log_message')) {
                log_message('info', "Central Hub Customer Sync Success: " . ($decoded['message'] ?? ''));
            }
            return [
                'success' => true,
                'message' => $decoded['message'] ?? 'Sinkronisasi pelanggan berhasil',
                'processed_count' => $decoded['processed_count'] ?? 1,
                'http_code' => $http_code
            ];
        }

        // 6. Handle HTTP Error Response (401, 422 validation, 500)
        $err_details = $decoded['message'] ?? $response_body;
        if (function_exists('log_message')) {
            log_message('error', "Central Hub Customer Sync Failed [HTTP {$http_code}]: " . $err_details);
        }

        return [
            'success' => false,
            'message' => "HTTP {$http_code}: " . $err_details,
            'processed_count' => 0,
            'http_code' => $http_code
        ];
    }
}

/* 
================================================================================
CONTOH PENGGUNAAN PADA CODEIGNITER 3 (CONTROLLER / MODEL):
================================================================================

// 1. Single Customer Insert/Update Event (misal di Customer_model.php CI3)
public function save_customer($id = null)
{
    $data = [
        'remote_customer_id' => $id ? $id : $this->db->insert_id(),
        'no_services'        => $this->input->post('no_layanan'),
        'name'               => $this->input->post('nama'),
        'phone'              => $this->input->post('no_wa'),
        'address'            => $this->input->post('alamat'),
        'odp_name'           => $this->input->post('odp_name'),
        'latitude'           => $this->input->post('lat'),
        'longitude'          => $this->input->post('lng'),
        'package_name'       => $this->input->post('paket_internet'),
        'monthly_fee'        => $this->input->post('harga_paket'),
        'status'             => $this->input->post('status_isolir') ? 'isolated' : 'active',
    ];

    // Panggil helper sync ke Central Ticket System Hub
    $sync_result = sync_customer_to_central_hub($data);

    return $sync_result['success'];
}

// 2. Batch Sync All Customers (misal untuk Cronjob Sync di CI3)
public function sync_all_customers_to_hub()
{
    $query = $this->db->get('tb_pelanggan')->result_array();
    $batch = [];

    foreach ($query as $row) {
        $batch[] = [
            'remote_customer_id' => $row['id'],
            'no_services'        => $row['no_layanan'],
            'name'               => $row['nama_pelanggan'],
            'phone'              => $row['no_hp'],
            'address'            => $row['alamat_rumah'],
            'odp_name'           => $row['kode_odp'],
            'latitude'           => $row['lat'],
            'longitude'          => $row['lng'],
            'package_name'       => $row['nama_paket'],
            'monthly_fee'        => $row['tarif_bulanan'],
            'status'             => ($row['is_isolir'] == 1) ? 'isolated' : 'active',
        ];
    }

    // Kirim batch 100 data per request
    $chunks = array_chunk($batch, 100);
    foreach ($chunks as $chunk) {
        sync_customer_to_central_hub($chunk);
    }
}
*/
