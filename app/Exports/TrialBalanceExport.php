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

class TrialBalanceExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $summary;

    public function __construct(array $data, array $summary)
    {
        $this->data = $data;
        $this->summary = $summary;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ['Trial Balance Report'],
            ['Date: ' . ($this->summary['date'] ?? now()->format('Y-m-d'))],
            [],
            ['Account Code', 'Account Name', 'Account Type', 'Debit', 'Credit'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 15,
            'D' => 15,
            'E' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');

        $sheet->getStyle('A4:E4')->applyFromArray([
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

        $lastRow = count($this->data) + 4;
        if ($lastRow > 4) {
            $sheet->getStyle('A5:E' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            $sheet->getStyle('D5:E' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Add totals row
        $totalRow = $lastRow + 1;
        $sheet->setCellValue('C' . $totalRow, 'TOTALS');
        $sheet->setCellValue('D' . $totalRow, $this->summary['total_debit']);
        $sheet->setCellValue('E' . $totalRow, $this->summary['total_credit']);
        $sheet->getStyle('C' . $totalRow . ':E' . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D0D0D0'],
            ],
        ]);
        $sheet->getStyle('D' . $totalRow . ':E' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }
}
