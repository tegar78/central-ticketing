<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tiket Gangguan - MANAGEMENT TICKET</title>
    <link rel="stylesheet" href="{{ public_path('css/pdf.css') }}">
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">MANAGEMENT TICKET</div>
                <div class="header-subtitle">Laporan Rekapitulasi Data Tiket Gangguan Pelanggan (Multi-Tenant 60 Billing)</div>
            </td>
            <td style="width: 30%; text-align: right;">
                <div style="font-size: 9px; color: #64748b;">
                    Dicetak: <strong>{{ date('d M Y, H:i') }} WIB</strong><br>
                    Oleh: <strong>{{ auth()->user()->name }} ({{ ucfirst(auth()->user()->role) }})</strong>
                </div>
            </td>
        </tr>
    </table>

    <!-- Filter Metadata Summary -->
    <table class="meta-table">
        <tr>
            <td style="width: 25%;">
                <strong>Filter Status:</strong> {{ request('status') ? ucfirst(request('status')) : 'Semua Status' }}
            </td>
            <td style="width: 25%;">
                <strong>Filter Billing:</strong> 
                @if(request('billing_instance_id') && isset($selectedTenant))
                    {{ $selectedTenant->name }} ({{ $selectedTenant->tenant_code }})
                @else
                    Semua Billing (60)
                @endif
            </td>
            <td style="width: 25%;">
                <strong>Filter Teknisi:</strong> 
                @if(request('technician_id') && isset($selectedTechnician))
                    {{ $selectedTechnician->name }}
                @else
                    Semua Teknisi
                @endif
            </td>
            <td style="width: 25%; text-align: right;">
                <strong>Total Data:</strong> {{ $tickets->count() }} Tiket
            </td>
        </tr>
    </table>

    <!-- Data Table -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;" class="text-center">No</th>
                <th style="width: 70px;">Tanggal</th>
                <th style="width: 95px;">No Tiket</th>
                <th style="width: 100px;">Billing Gayuh</th>
                <th style="width: 80px;">No Layanan</th>
                <th style="width: 110px;">Pelanggan</th>
                <th style="width: 80px;">No WA</th>
                <th>Laporan Gangguan</th>
                <th style="width: 90px;">Teknisi</th>
                <th style="width: 55px;" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($tickets as $index => $ticket)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                <td class="font-mono" style="font-weight: bold;">{{ $ticket->ticket_number }}</td>
                <td>
                    <span class="badge badge-tenant">{{ $ticket->billingInstance->name ?? '-' }}</span>
                </td>
                <td class="font-mono">{{ $ticket->no_services ?? '-' }}</td>
                <td>
                    <strong>{{ $ticket->customer_name }}</strong>
                    @if($ticket->customer_address)
                        <div style="font-size: 8px; color: #64748b; margin-top: 2px;">{{ Str::limit($ticket->customer_address, 40) }}</div>
                    @endif
                </td>
                <td class="font-mono">{{ $ticket->customer_phone ?? '-' }}</td>
                <td>
                    <strong>{{ $ticket->keterangan_laporan }}</strong>
                    @if($ticket->action_remark && $ticket->action_remark !== '-')
                        <div style="font-size: 8px; color: #15803d; margin-top: 2px;"><em>Action: {{ Str::limit($ticket->action_remark, 70) }}</em></div>
                    @endif
                </td>
                <td>{{ $ticket->assignedTechnician->name ?? 'Belum ditugaskan' }}</td>
                <td class="text-center">
                    @if($ticket->status === 'pending')
                        <span class="badge badge-pending">Pending</span>
                    @elseif($ticket->status === 'process')
                        <span class="badge badge-process">Proses</span>
                    @else
                        <span class="badge badge-close">Close</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="text-center" style="padding: 20px; color: #94a3b8;">
                    Tidak ada data tiket yang memenuhi filter pencarian.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: left;">MANAGEMENT TICKET &copy; {{ date('Y') }} - Dokumen ini dicetak secara otomatis dari sistem.</td>
                <td style="text-align: right;">Halaman <span class="page-number"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>
