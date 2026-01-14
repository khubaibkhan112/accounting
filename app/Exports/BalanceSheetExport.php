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

class BalanceSheetExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
            ['Balance Sheet Report'],
            ['Date: ' . ($this->summary['date'] ?? now()->format('Y-m-d'))],
            [],
            ['Account Code', 'Account Name', 'Balance'],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15,
            'B' => 40,
            'C' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A1:C1');
        $sheet->mergeCells('A2:C2');

        $sheet->getStyle('A4:C4')->applyFromArray([
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
            $sheet->getStyle('A5:C' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ]);

            $sheet->getStyle('C5:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        }

        // Add totals
        $totalRow = $lastRow + 1;
        $sheet->setCellValue('B' . $totalRow, 'TOTAL');
        $sheet->setCellValue('C' . $totalRow, $this->summary['total'] ?? 0);
        $sheet->getStyle('B' . $totalRow . ':C' . $totalRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D0D0D0'],
            ],
        ]);
        $sheet->getStyle('C' . $totalRow)->getNumberFormat()->setFormatCode('#,##0.00');
    }
}
