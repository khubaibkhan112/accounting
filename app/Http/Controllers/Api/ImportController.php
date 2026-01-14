<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Imports\TransactionImport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    /**
     * Import transactions from Excel file
     */
    public function import(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB max
            'sheet' => 'sometimes|integer|min:1',
            'default_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $sheetIndex = $request->input('sheet', 2) - 1; // Convert to 0-based index

            // Ensure imports directory exists
            $importsDir = storage_path('app/imports');
            if (!is_dir($importsDir)) {
                Storage::disk('local')->makeDirectory('imports');
            }

            // Store file temporarily
            $path = $file->store('imports', 'local');
            
            // Use Storage facade to get the correct path (works on all platforms)
            $fullPath = Storage::disk('local')->path($path);
            
            // Get real path to handle any symlinks or path issues
            $realPath = realpath($fullPath);
            if ($realPath === false) {
                // If realpath fails, try the original path
                $realPath = $fullPath;
            }
            
            // Verify file exists before importing
            if (!file_exists($realPath)) {
                throw new \Exception("File not found at path: {$realPath}. Original path: {$path}, Full path: {$fullPath}");
            }
            
            $fullPath = $realPath;

            // Create import instance with default date
            $defaultDate = $request->input('default_date', now()->format('Y-m-d'));
            $import = new TransactionImport(auth()->id(), $defaultDate);

            // Import from specific sheet
            Excel::import($import, $fullPath, null, \Maatwebsite\Excel\Excel::XLSX, [
                'sheet' => $sheetIndex,
            ]);

            // Get results
            $results = $import->getResults();

            // Clean up temporary file
            Storage::disk('local')->delete($path);

            return response()->json([
                'message' => 'Import completed',
                'results' => [
                    'imported' => $results['imported'],
                    'skipped' => $results['skipped'],
                    'total_errors' => count($results['errors']),
                    'errors' => array_slice($results['errors'], 0, 50), // Limit to first 50 errors
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Import failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview Excel file structure (first few rows)
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'sheet' => 'sometimes|integer|min:1',
            'rows' => 'sometimes|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('file');
            $sheetIndex = $request->input('sheet', 2) - 1;
            $rows = $request->input('rows', 5);

            // Ensure imports directory exists
            $importsDir = storage_path('app/imports');
            if (!is_dir($importsDir)) {
                Storage::disk('local')->makeDirectory('imports');
            }

            // Store file temporarily
            $path = $file->store('imports', 'local');
            
            // Use Storage facade to get the correct path (works on all platforms)
            $fullPath = Storage::disk('local')->path($path);
            
            // Get real path to handle any symlinks or path issues
            $realPath = realpath($fullPath);
            if ($realPath === false) {
                // If realpath fails, try the original path
                $realPath = $fullPath;
            }
            
            // Verify file exists before reading
            if (!file_exists($realPath)) {
                throw new \Exception("File not found at path: {$realPath}. Original path: {$path}, Full path: {$fullPath}");
            }
            
            $fullPath = $realPath;

            // Read first few rows
            $data = Excel::toArray([], $fullPath, null, \Maatwebsite\Excel\Excel::XLSX);
            
            // Clean up
            Storage::disk('local')->delete($path);

            if (!isset($data[$sheetIndex])) {
                return response()->json([
                    'message' => 'Sheet not found',
                ], 404);
            }

            $sheetData = $data[$sheetIndex];
            $preview = array_slice($sheetData, 0, $rows + 1); // +1 for header

            return response()->json([
                'headers' => $preview[0] ?? [],
                'rows' => array_slice($preview, 1),
                'total_rows' => count($sheetData) - 1, // Exclude header
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Preview failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
