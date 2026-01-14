<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzeExcelFile extends Command
{
    protected $signature = 'excel:analyze {file} {--sheet=2}';
    protected $description = 'Analyze Excel file structure to understand column mapping';

    public function handle()
    {
        $filePath = $this->argument('file');
        $sheetIndex = (int) $this->option('sheet') - 1;

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        try {
            $this->info("Analyzing file: {$filePath}");
            $this->info("Sheet index: " . ($sheetIndex + 1));
            $this->newLine();

            // Read the Excel file
            $data = Excel::toArray([], $filePath, null, \Maatwebsite\Excel\Excel::XLSX);

            if (!isset($data[$sheetIndex])) {
                $this->error("Sheet not found at index {$sheetIndex}");
                return 1;
            }

            $sheetData = $data[$sheetIndex];
            $totalRows = count($sheetData);

            $this->info("Total rows in sheet: {$totalRows}");
            $this->newLine();

            // Display header row
            if (isset($sheetData[0])) {
                $this->info("Header Row (Row 1):");
                $headers = $sheetData[0];
                foreach ($headers as $index => $header) {
                    $column = $this->numberToColumn($index);
                    $this->line("  Column {$column} (Index {$index}): " . ($header ?: '(empty)'));
                }
                $this->newLine();
            }

            // Display first 5 data rows
            $this->info("Sample Data Rows (first 5):");
            for ($i = 1; $i <= min(5, $totalRows - 1); $i++) {
                if (isset($sheetData[$i])) {
                    $this->info("Row " . ($i + 1) . ":");
                    foreach ($sheetData[$i] as $index => $cell) {
                        $column = $this->numberToColumn($index);
                        $value = $cell ?: '(empty)';
                        if (strlen($value) > 50) {
                            $value = substr($value, 0, 50) . '...';
                        }
                        $this->line("  {$column}: {$value}");
                    }
                    $this->newLine();
                }
            }

            // Analyze column types
            $this->info("Column Analysis:");
            if (isset($sheetData[0])) {
                $headers = $sheetData[0];
                for ($colIndex = 0; $colIndex < count($headers); $colIndex++) {
                    $column = $this->numberToColumn($colIndex);
                    $header = $headers[$colIndex] ?: "Column {$column}";
                    
                    // Sample values from first few rows
                    $samples = [];
                    for ($rowIndex = 1; $rowIndex <= min(5, $totalRows - 1); $rowIndex++) {
                        if (isset($sheetData[$rowIndex][$colIndex])) {
                            $samples[] = $sheetData[$rowIndex][$colIndex];
                        }
                    }
                    
                    $this->line("  {$column} ({$header}): " . implode(', ', array_slice($samples, 0, 3)));
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error analyzing file: " . $e->getMessage());
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
