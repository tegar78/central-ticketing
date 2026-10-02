<?php

namespace Database\Seeders;

use App\Models\BillingInstance;
use App\Models\Odp;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class OdpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $billing = BillingInstance::first();
        $billingId = $billing ? $billing->id : null;
        $admin = User::where('role', 'admin')->first();
        $adminId = $admin ? $admin->id : null;

        // Ensure storage directory exists
        if (!Storage::disk('public')->exists('odps')) {
            Storage::disk('public')->makeDirectory('odps');
        }

        // Generate clean sample SVG photo for ODP illustration
        $sampleSvgPhoto = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="400" viewBox="0 0 600 400">
  <defs>
    <linearGradient id="boxGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#334155;stop-opacity:1" />
      <stop offset="100%" style="stop-color:#0f172a;stop-opacity:1" />
    </linearGradient>
  </defs>
  <rect width="600" height="400" fill="#1e293b"/>
  <rect x="120" y="40" width="360" height="320" rx="20" fill="url(#boxGrad)" stroke="#475569" stroke-width="4"/>
  <rect x="150" y="70" width="300" height="50" rx="8" fill="#0284c7" opacity="0.9"/>
  <text x="300" y="102" fill="#ffffff" font-family="monospace" font-size="18" font-weight="bold" text-anchor="middle">OPTICAL DISTRIBUTION POINT</text>
  <circle cx="210" cy="170" r="14" fill="#10b981"/>
  <circle cx="270" cy="170" r="14" fill="#10b981"/>
  <circle cx="330" cy="170" r="14" fill="#10b981"/>
  <circle cx="390" cy="170" r="14" fill="#10b981"/>
  <circle cx="210" cy="230" r="14" fill="#10b981"/>
  <circle cx="270" cy="230" r="14" fill="#10b981"/>
  <circle cx="330" cy="230" r="14" fill="#f59e0b"/>
  <circle cx="390" cy="230" r="14" fill="#64748b"/>
  <path d="M 210 184 Q 240 290 300 340" stroke="#facc15" stroke-width="3" fill="none"/>
  <path d="M 270 184 Q 280 290 300 340" stroke="#38bdf8" stroke-width="3" fill="none"/>
  <rect x="200" y="290" width="200" height="30" rx="6" fill="#0f172a" stroke="#334155" stroke-width="2"/>
  <text x="300" y="310" fill="#38bdf8" font-family="monospace" font-size="12" font-weight="bold" text-anchor="middle">SPLITTER 1:8 - -19.4 dBm</text>
</svg>
SVG;

        $samplePhotoPath = 'odps/sample_odp_splitter.svg';
        Storage::disk('public')->put($samplePhotoPath, $sampleSvgPhoto);

        $odps = [
            [
                'code_odp'        => 'ODP-TNG-W6-01',
                'name'            => 'ODP Daan Mogot Km 21',
                'billing_node_id' => $billingId,
                'latitude'        => '-6.178210',
                'longitude'       => '106.631820',
                'total_ports'     => 16,
                'used_ports'      => 6,
                'status'          => 'active',
                'photo_path'      => $samplePhotoPath,
                'address'         => 'Jl. Daan Mogot Km 21, Tiang Telkom 04 sebelah ruko',
                'notes'           => 'Redaman normal -19.2 dBm, Splitter 1:8',
                'created_by'      => $adminId,
            ],
            [
                'code_odp'        => 'ODP-TEKO-S15-02',
                'name'            => 'ODP Benteng Betawi (Alfamart)',
                'billing_node_id' => $billingId,
                'latitude'        => '-6.182450',
                'longitude'       => '106.638910',
                'total_ports'     => 16,
                'used_ports'      => 8,
                'status'          => 'full',
                'photo_path'      => $samplePhotoPath,
                'address'         => 'Jl. Benteng Betawi, Tiang Depan Alfamart',
                'notes'           => 'Port penuh 8/8. Perlu penambahan ODP ekspansi.',
                'created_by'      => $adminId,
            ],
            [
                'code_odp'        => 'ODP-TNG-V6-03',
                'name'            => 'ODP Simpang Sudirman',
                'billing_node_id' => $billingId,
                'latitude'        => '-6.171120',
                'longitude'       => '106.645310',
                'total_ports'     => 16,
                'used_ports'      => 11,
                'status'          => 'active',
                'photo_path'      => $samplePhotoPath,
                'address'         => 'Jl. Sudirman No. 45, Tiang PLN Simpang Empat',
                'notes'           => 'Splitter 1:16, Redaman -20.1 dBm',
                'created_by'      => $adminId,
            ],
            [
                'code_odp'        => 'ODP-TNG-U11-04',
                'name'            => 'ODP Poris Indah Blok C3',
                'billing_node_id' => $billingId,
                'latitude'        => '-6.165400',
                'longitude'       => '106.627800',
                'total_ports'     => 16,
                'used_ports'      => 3,
                'status'          => 'maintenance',
                'photo_path'      => null,
                'address'         => 'Perum Poris Indah Blok C3 No. 8',
                'notes'           => 'Jadwal rekabeling dropcore karena redaman tinggi (-28dBm)',
                'created_by'      => $adminId,
            ],
            [
                'code_odp'        => 'ODP-KBD-01',
                'name'            => 'ODP Kebon Dalem',
                'billing_node_id' => $billingId,
                'latitude'        => '-6.189500',
                'longitude'       => '106.621200',
                'total_ports'     => 16,
                'used_ports'      => 0,
                'status'          => 'damaged',
                'photo_path'      => null,
                'address'         => 'Jl. Kebon Dalem No. 10',
                'notes'           => 'Tiang condong tertabrak truk, menunggu penggantian tiang',
                'created_by'      => $adminId,
            ],
        ];

        foreach ($odps as $data) {
            Odp::updateOrCreate(
                ['code_odp' => $data['code_odp'], 'billing_node_id' => $data['billing_node_id']],
                $data
            );
        }
    }
}
