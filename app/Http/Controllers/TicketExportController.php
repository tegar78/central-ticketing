<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\BillingInstance;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Barryvdh\DomPDF\Facade\Pdf;

class TicketExportController extends Controller
{
    /**
     * Retrieve tickets matching current filters and user role permissions.
     */
    protected function getFilteredTickets(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::with(['billingInstance', 'assignedTechnician']);

        // Strict role scoping: Technicians can only export tickets assigned to them
        if ($user->role === 'technician') {
            $query->where('assigned_technician_id', $user->id);
        } else {
            // Admin & Operator filters
            if ($request->filled('billing_instance_id')) {
                $query->where('billing_instance_id', $request->billing_instance_id);
            }
            if ($request->filled('technician_id')) {
                $query->where('assigned_technician_id', $request->technician_id);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('no_services', 'like', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }

    /**
     * Export tickets to CSV with UTF-8 BOM for Microsoft Excel compatibility.
     */
    public function exportCsv(Request $request)
    {
        $tickets = $this->getFilteredTickets($request);
        $filename = 'tickets_export_' . date('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($tickets) {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM so Excel displays Indonesian characters/accents correctly
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header Row
            fputcsv($handle, [
                'No',
                'Tanggal Lapor',
                'No Tiket',
                'Billing',
                'No Layanan',
                'Nama Pelanggan',
                'No WA / HP',
                'Alamat Pelanggan',
                'Kategori Masalah',
                'Deskripsi Masalah',
                'Teknisi',
                'Status',
                'Waktu Ditutup'
            ]);

            // Data Rows
            foreach ($tickets as $index => $ticket) {
                fputcsv($handle, [
                    $index + 1,
                    $ticket->created_at->format('Y-m-d H:i:s'),
                    $ticket->ticket_number,
                    $ticket->billingInstance->name ?? '-',
                    $ticket->no_services ?? '-',
                    $ticket->customer_name,
                    $ticket->customer_phone ?? '-',
                    $ticket->customer_address ?? '-',
                    $ticket->issue_category,
                    $ticket->issue_description ?? '-',
                    $ticket->assignedTechnician->name ?? 'Belum Ditugaskan',
                    strtoupper($ticket->status),
                    $ticket->closed_at ? $ticket->closed_at->format('Y-m-d H:i:s') : '-'
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * Export tickets to formatted Excel (.xlsx) using PhpSpreadsheet.
     */
    public function exportExcel(Request $request)
    {
        $tickets = $this->getFilteredTickets($request);
        $filename = 'tickets_export_' . date('Ymd_His') . '.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Tiket');

        // Title Block
        $sheet->setCellValue('A1', 'CENTRAL TICKET SYSTEM - REKAPITULASI TIKET GANGGUAN');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1E3A8A'));

        $sheet->setCellValue('A2', 'Dicetak pada: ' . date('d M Y, H:i') . ' WIB | Oleh: ' . Auth::user()->name . ' (' . ucfirst(Auth::user()->role) . ')');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('64748B'));

        // Table Header
        $headers = [
            'A4' => 'No',
            'B4' => 'Tanggal Lapor',
            'C4' => 'No Tiket',
            'D4' => 'Billing',
            'E4' => 'No Layanan',
            'F4' => 'Nama Pelanggan',
            'G4' => 'No WA / HP',
            'H4' => 'Alamat Pelanggan',
            'I4' => 'Kategori Masalah',
            'J4' => 'Deskripsi Masalah',
            'K4' => 'Teknisi',
            'L4' => 'Status',
            'M4' => 'Waktu Selesai'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        // Header Styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 10,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A8A'], // Deep Blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '0F172A'],
                ],
            ],
        ];
        $sheet->getStyle('A4:M4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(26);

        // Populate Data Rows
        $row = 5;
        foreach ($tickets as $index => $ticket) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $ticket->created_at->format('d/m/Y H:i'));
            $sheet->setCellValueExplicit('C' . $row, $ticket->ticket_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $ticket->billingInstance->name ?? '-');
            $sheet->setCellValueExplicit('E' . $row, $ticket->no_services ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('F' . $row, $ticket->customer_name);
            $sheet->setCellValueExplicit('G' . $row, $ticket->customer_phone ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('H' . $row, $ticket->customer_address ?? '-');
            $sheet->setCellValue('I' . $row, $ticket->issue_category);
            $sheet->setCellValue('J' . $row, $ticket->issue_description ?? '-');
            $sheet->setCellValue('K' . $row, $ticket->assignedTechnician->name ?? 'Belum Ditugaskan');
            $sheet->setCellValue('L' . $row, strtoupper($ticket->status));
            $sheet->setCellValue('M' . $row, $ticket->closed_at ? $ticket->closed_at->format('d/m/Y H:i') : '-');

            // Row zebra background
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':M' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }

            // Alignments
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('L' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('M' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Status Badge Color
            $statusCell = 'L' . $row;
            if ($ticket->status === 'pending') {
                $sheet->getStyle($statusCell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('B45309'));
            } elseif ($ticket->status === 'process') {
                $sheet->getStyle($statusCell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('1D4ED8'));
            } else {
                $sheet->getStyle($statusCell)->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('047857'));
            }

            $row++;
        }

        // Apply Borders to Entire Table
        $lastRow = max(5, $row - 1);
        $sheet->getStyle('A4:M' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Auto-fit Column Widths
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Stream output
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Export tickets to print-ready PDF using Barryvdh/DomPDF (A4 Landscape).
     */
    public function exportPdf(Request $request)
    {
        $tickets = $this->getFilteredTickets($request);
        $filename = 'tickets_laporan_' . date('Ymd_His') . '.pdf';

        $selectedTenant = null;
        if ($request->filled('billing_instance_id')) {
            $selectedTenant = BillingInstance::find($request->billing_instance_id);
        }

        $selectedTechnician = null;
        if ($request->filled('technician_id')) {
            $selectedTechnician = User::find($request->technician_id);
        }

        $pdf = Pdf::loadView('tickets.pdf', compact('tickets', 'selectedTenant', 'selectedTechnician'))
            ->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }
}
