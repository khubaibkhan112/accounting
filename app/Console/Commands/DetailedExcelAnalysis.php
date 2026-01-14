<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class DetailedExcelAnalysis extends Command
{
    protected $signature = 'excel:detailed {file} {--sheet=2} {--start=1} {--end=20}';
    protected $description = 'Detailed analysis of Excel file showing specific row range';

    public function handle()
    {
        $filePath = $this->argument('file');
        $sheetIndex = (int) $this->option('sheet') - 1;
        $startRow = (int) $this->option('start');
        $endRow = (int) $this->option('end');

        try {
            $data = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::XLSX);
            $sheetData = $data[$sheetIndex];

            $this->info("Rows {$startRow} to {$endRow}:");
            $this->newLine();

            for ($i = $startRow - 1; $i < min($endRow, count($sheetData)); $i++) {
                $this->info("=== Row " . ($i + 1) . " ===");
                $row = $sheetData[$i];
                
                // Show non-empty columns
                foreach ($row as $colIndex => $cell) {
                    if (!empty(trim($cell))) {
                        $column = $this->numberToColumn($colIndex);
                        $value = $cell;
                        if (strlen($value) > 100) {
                            $value = substr($value, 0, 100) . '...';
                        }
                        $this->line("  {$column} ({$colIndex}): {$value}");
                    }
                }
                $this->newLine();
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }

    protected function numberToColumn($number)
    {
        $column = '';
        while ($number >= 0) {
            $column = chr(65 + ($number % 26)) . $column;
            $number = floor($number / 26) - 1;
        }
        return $column;
    }
}
