<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LedgerExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $account;
    protected $summary;

    public function __construct(array $data, array $account, array $summary)
    {
        $this->data = $data;
        $this->account = $account;
        $this->summary = $summary;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['Ledger Report'],
            ['Account: ' . $this->account['account_code'] . ' - ' . $this->account['account_name']],
            ['Date Range: ' . ($this->summary['date_from'] ?? 'N/A') . ' to ' . ($this->summary['date_to'] ?? 'N/A')],
            [],
            ['Date', 'Description', 'Reference', 'Debit', 'Credit', 'Balance'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 20,
            'D' => 15,
            'E' => 15,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style header rows
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:F2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        $sheet->getStyle('A3:F3')->applyFromArray([
            'font' => ['size' => 10],
        ]);

        // Style column headers
        $sheet->getStyle('A5:F5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Merge cells for header
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');

        // Style data rows
        $lastRow = count($this->data) + 5;
        if ($lastRow > 5) {
            $sheet->getStyle('A6:F' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            // Format number columns
            $sheet->getStyle('D6:F' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Add summary row
        $summaryRow = $lastRow + 2;
        $sheet->setCellValue('A' . $summaryRow, 'Opening Balance:');
        $sheet->setCellValue('D' . $summaryRow, $this->summary['opening_balance']);
        $sheet->getStyle('D' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $summaryRow++;
        $sheet->setCellValue('A' . $summaryRow, 'Closing Balance:');
        $sheet->setCellValue('D' . $summaryRow, $this->summary['closing_balance']);
        $sheet->getStyle('D' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $summaryRow++;
        $sheet->setCellValue('A' . $summaryRow, 'Total Debit:');
        $sheet->setCellValue('D' . $summaryRow, $this->summary['total_debit']);
        $sheet->getStyle('D' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $summaryRow++;
        $sheet->setCellValue('A' . $summaryRow, 'Total Credit:');
        $sheet->setCellValue('E' . $summaryRow, $this->summary['total_credit']);
        $sheet->getStyle('E' . $summaryRow)->getNumberFormat()->setFormatCode('#,##0.00');

        $sheet->getStyle('A' . ($lastRow + 2) . ':F' . $summaryRow)->applyFromArray([
            'font' => ['bold' => true],
        ]);
    }
}
