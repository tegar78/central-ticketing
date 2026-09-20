<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tiket Gangguan - Central Ticket System</title>
    <style>
        @page {
            margin: 12mm 10mm 15mm 10mm;
            size: a4 landscape;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 8px;
        }
        .header-title {
            font-size: 16px;
            font-weight: bold;
            color: #0f172a;
            margin: 0;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .meta-table td {
            padding: 5px 10px;
            font-size: 9px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .data-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 6px;
            border: 1px solid #1e3a8a;
            text-align: left;
        }
        .data-table td {
            padding: 6px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-process {
            background-color: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-close {
            background-color: #d1fae5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-tenant {
            background-color: #ede9fe;
            color: #6d28d9;
            border: 1px solid #ddd6fe;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; font-size: 9px; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #94a3b8;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                <div class="header-title">CENTRAL TICKET SYSTEM</div>
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
                <th style="width: 100px;">Billing Mitra</th>
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
                    <strong>{{ $ticket->issue_category }}</strong>
                    @if($ticket->issue_description)
                        <div style="font-size: 8px; color: #475569; margin-top: 2px;">{{ Str::limit($ticket->issue_description, 70) }}</div>
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
                <td style="text-align: left;">Central Ticket System &copy; {{ date('Y') }} - Dokumen ini dicetak secara otomatis dari sistem.</td>
                <td style="text-align: right;">Halaman <span class="page-number"></span></td>
            </tr>
        </table>
    </div>
</body>
</html>
