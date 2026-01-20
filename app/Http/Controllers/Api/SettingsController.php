<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    private const DEFAULT_SETTINGS = [
        'company_name' => [
            'value' => '',
            'type' => 'string',
            'description' => 'Company or organization name',
        ],
        'fiscal_year_start' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Fiscal year start date',
        ],
        'fiscal_year_end' => [
            'value' => null,
            'type' => 'string',
            'description' => 'Fiscal year end date',
        ],
        'currency' => [
            'value' => 'USD',
            'type' => 'string',
            'description' => 'Default currency for transactions',
        ],
        'default_account_type' => [
            'value' => 'asset',
            'type' => 'string',
            'description' => 'Default account type for new accounts',
        ],
        'auto_generate_reference' => [
            'value' => 'false',
            'type' => 'boolean',
            'description' => 'Automatically generate reference numbers for transactions',
        ],
    ];

    /**
     * Get all settings.
     */
    public function index(): JsonResponse
    {
        try {
            $this->ensureDefaultSettings();
            $settings = Setting::all()->map(function ($setting) {
                return [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => Setting::castValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                ];
            });

            return response()->json($settings);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific setting by key.
     */
    public function show(string $key): JsonResponse
    {
        try {
            $setting = Setting::where('key', $key)->first();

            return response()->json([
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => Setting::castValue($setting->value, $setting->type),
                'type' => $setting->type,
                'description' => $setting->description,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to load setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a setting.
     */
    public function update(Request $request, string $key): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'present',
            'type' => 'sometimes|in:string,boolean,integer,float,number,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $setting = Setting::where('key', $key)->first();

            $value = $request->input('value');
            $type = $request->input('type', $setting?->type ?? (self::DEFAULT_SETTINGS[$key]['type'] ?? 'string'));

            // Convert value to string for storage
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
                $type = 'json';
            } else {
                $value = (string) $value;
            }

            $description = $setting?->description ?? (self::DEFAULT_SETTINGS[$key]['description'] ?? null);
            $setting = Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'description' => $description,
                ]
            );

            return response()->json([
                'message' => 'Setting updated successfully',
                'setting' => [
                    'id' => $setting->id,
                    'key' => $setting->key,
                    'value' => Setting::castValue($setting->value, $setting->type),
                    'type' => $setting->type,
                    'description' => $setting->description,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update setting',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update multiple settings at once.
     */
    public function updateMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'present',
            'settings.*.type' => 'nullable|in:string,boolean,integer,float,number,json',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updated = [];

            foreach ($request->input('settings') as $settingData) {
                $key = $settingData['key'];
                $setting = Setting::where('key', $key)->first();
                $value = $settingData['value'];
                $type = $settingData['type'] ?? ($setting ? $setting->type : 'string');

                // Convert value to string for storage
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                    $type = 'json';
                } else {
                    $value = (string) $value;
                }

                $description = $setting?->description ?? (self::DEFAULT_SETTINGS[$key]['description'] ?? null);
                $setting = Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => $type,
                        'description' => $description,
                    ]
                );

                $updated[] = [
                    'key' => $setting->key,
                    'value' => Setting::castValue($setting->value, $setting->type),
                ];
            }

            return response()->json([
                'message' => 'Settings updated successfully',
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function ensureDefaultSettings(): void
    {
        $currentYear = now()->format('Y');
        $defaults = self::DEFAULT_SETTINGS;
        $defaults['fiscal_year_start']['value'] = $currentYear . '-01-01';
        $defaults['fiscal_year_end']['value'] = $currentYear . '-12-31';

        foreach ($defaults as $key => $data) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => (string) $data['value'],
                    'type' => $data['type'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
