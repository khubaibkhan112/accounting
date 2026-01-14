<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings.
     */
    public function index(): JsonResponse
    {
        try {
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

            if (!$setting) {
                return response()->json([
                    'message' => 'Setting not found',
                ], 404);
            }

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
            'value' => 'required',
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

            if (!$setting) {
                return response()->json([
                    'message' => 'Setting not found',
                ], 404);
            }

            $value = $request->input('value');
            $type = $request->input('type', $setting->type);

            // Convert value to string for storage
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
                $type = 'json';
            } else {
                $value = (string) $value;
            }

            $setting->update([
                'value' => $value,
                'type' => $type,
            ]);

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
            'settings.*.value' => 'required',
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
                $setting = Setting::where('key', $settingData['key'])->first();

                if ($setting) {
                    $value = $settingData['value'];
                    $type = $settingData['type'] ?? $setting->type;

                    // Convert value to string for storage
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                        $type = 'json';
                    } else {
                        $value = (string) $value;
                    }

                    $setting->update([
                        'value' => $value,
                        'type' => $type,
                    ]);

                    $updated[] = [
                        'key' => $setting->key,
                        'value' => Setting::castValue($setting->value, $setting->type),
                    ];
                }
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
}
