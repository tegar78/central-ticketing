<?php
/**
 * CodeIgniter 3 Ticket Integration Helper for bill-gyh.gayuh.net.id
 *
 * Sediakan potongan helper ini di aplikasi CodeIgniter 3 billing lokal (bill-gyh.gayuh.net.id).
 * Panggil fungsi `send_ticket_to_central_hub()` saat pelanggan / operator membuat tiket gangguan di CI3.
 */

if (!function_exists('send_ticket_to_central_hub')) {
    /**
     * Send ticket created in CI3 billing server (bill-gyh.gayuh.net.id) to Central Ticket System.
     *
     * @param array $ticket_data Data tiket dari CI3
     * @param string|null $central_api_url URL API Central (default: http://127.0.0.1:8000/api/v1/tickets)
     * @param string|null $api_key API Key untuk bill-gyh.gayuh.net.id (default: key-bill-001-secret-12345)
     * @return array Status respon
     */
    function send_ticket_to_central_hub(array $ticket_data, $central_api_url = null, $api_key = null)
    {
        if (empty($central_api_url)) {
            $central_api_url = 'http://127.0.0.1:8000/api/v1/tickets';
        }

        if (empty($api_key)) {
            $api_key = 'key-bill-001-secret-12345'; // API key untuk BILL-001 (bill-gyh.gayuh.net.id)
        }

        $payload = [
            'remote_ticket_id'    => $ticket_data['id_tiket_ci3'] ?? $ticket_data['remote_ticket_id'] ?? null,
            'no_services'         => $ticket_data['no_layanan'] ?? $ticket_data['no_services'],
            'customer_name'       => $ticket_data['nama_pelanggan'] ?? $ticket_data['customer_name'],
            'customer_phone'      => $ticket_data['no_hp'] ?? $ticket_data['customer_phone'] ?? null,
            'customer_address'    => $ticket_data['alamat'] ?? $ticket_data['customer_address'] ?? null,
            'latitude'            => $ticket_data['lat'] ?? $ticket_data['latitude'] ?? null,
            'longitude'           => $ticket_data['lng'] ?? $ticket_data['longitude'] ?? null,
            'category_name'       => $ticket_data['kategori'] ?? $ticket_data['category_name'] ?? null,
            'problem_description' => $ticket_data['deskripsi_masalah'] ?? $ticket_data['problem_description'],
            'created_by_name'     => $ticket_data['pembuat'] ?? $ticket_data['created_by_name'] ?? 'Pelanggan Direct',
            'created_by_role'     => $ticket_data['role_pembuat'] ?? $ticket_data['created_by_role'] ?? 'Client',
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $central_api_url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . $api_key,
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response_body = curl_exec($ch);
        $http_code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error    = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            if (function_exists('log_message')) {
                log_message('error', "Central Ticket Creation cURL Error: " . $curl_error);
            }
            return ['success' => false, 'message' => $curl_error];
        }

        $decoded = json_decode($response_body, true);

        if ($http_code >= 200 && $http_code < 300 && isset($decoded['success']) && $decoded['success'] === true) {
            return [
                'success'       => true,
                'ticket_number' => $decoded['data']['ticket_number'] ?? null,
                'ticket_id'     => $decoded['data']['ticket_id'] ?? null,
                'message'       => $decoded['message'] ?? 'Tiket berhasil terdaftar di Central Hub'
            ];
        }

        return ['success' => false, 'message' => "HTTP {$http_code}: " . ($decoded['message'] ?? $response_body)];
    }
}
