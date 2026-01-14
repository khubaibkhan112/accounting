<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     * This method sanitizes input data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->sanitizeInput();
    }

    /**
     * Sanitize input data.
     */
    protected function sanitizeInput(): void
    {
        $input = $this->all();

        foreach ($input as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace("\0", '', $value);
                
                // For specific fields, apply additional sanitization
                if (in_array($key, ['email', 'user_email'])) {
                    $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                } elseif (in_array($key, ['phone', 'emergency_contact_phone'])) {
                    // Remove non-numeric characters except +, -, spaces, and parentheses
                    $value = preg_replace('/[^0-9+\-() ]/', '', $value);
                } elseif (in_array($key, ['account_code', 'employee_id', 'customer_code'])) {
                    // Remove special characters, keep alphanumeric, dash, underscore
                    $value = preg_replace('/[^a-zA-Z0-9\-_]/', '', $value);
                } elseif (in_array($key, ['description', 'notes', 'address'])) {
                    // Allow more characters but remove script tags
                    $value = strip_tags($value);
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                } elseif (Str::contains($key, ['name', 'title', 'first_name', 'last_name'])) {
                    // Remove HTML tags and special characters except spaces, hyphens, apostrophes
                    $value = strip_tags($value);
                    $value = preg_replace('/[^\p{L}\p{N}\s\-\']/u', '', $value);
                }
                
                $input[$key] = $value;
            } elseif (is_array($value)) {
                // Recursively sanitize arrays
                $input[$key] = $this->sanitizeArray($value);
            }
        }

        $this->merge($input);
    }

    /**
     * Recursively sanitize array values.
     */
    protected function sanitizeArray(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = trim(str_replace("\0", '', $value));
            } elseif (is_array($value)) {
                $array[$key] = $this->sanitizeArray($value);
            }
        }
        return $array;
    }
}
