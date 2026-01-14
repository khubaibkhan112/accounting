<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // For now, allow all authenticated users. You can add role checks here later.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'description' => ['required', 'string', 'max:1000'],
            'debit_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'credit_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'transaction_type' => ['nullable', 'string', 'max:50'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $debit = (float) ($this->debit_amount ?? 0);
            $credit = (float) ($this->credit_amount ?? 0);

            // At least one of debit or credit must be provided and greater than 0
            if ($debit == 0 && $credit == 0) {
                $validator->errors()->add('debit_amount', 'Either debit amount or credit amount must be greater than 0.');
                $validator->errors()->add('credit_amount', 'Either debit amount or credit amount must be greater than 0.');
            }

            // Both debit and credit cannot be greater than 0 (this is a single-entry transaction)
            if ($debit > 0 && $credit > 0) {
                $validator->errors()->add('debit_amount', 'Both debit and credit amounts cannot be greater than 0. Use only one.');
                $validator->errors()->add('credit_amount', 'Both debit and credit amounts cannot be greater than 0. Use only one.');
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Transaction date is required.',
            'date.date' => 'Transaction date must be a valid date.',
            'account_id.required' => 'Account is required.',
            'account_id.exists' => 'The selected account does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'employee_id.exists' => 'The selected employee does not exist.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'debit_amount.numeric' => 'Debit amount must be a number.',
            'debit_amount.min' => 'Debit amount cannot be negative.',
            'debit_amount.max' => 'Debit amount cannot exceed 999,999,999.99.',
            'credit_amount.numeric' => 'Credit amount must be a number.',
            'credit_amount.min' => 'Credit amount cannot be negative.',
            'credit_amount.max' => 'Credit amount cannot exceed 999,999,999.99.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'transaction_type.max' => 'Transaction type cannot exceed 50 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date' => 'transaction date',
            'account_id' => 'account',
            'customer_id' => 'customer',
            'employee_id' => 'employee',
            'description' => 'description',
            'debit_amount' => 'debit amount',
            'credit_amount' => 'credit amount',
            'reference_number' => 'reference number',
            'transaction_type' => 'transaction type',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('debit_amount') || $this->debit_amount === null || $this->debit_amount === '') {
            $this->merge(['debit_amount' => 0]);
        }

        if (!$this->has('credit_amount') || $this->credit_amount === null || $this->credit_amount === '') {
            $this->merge(['credit_amount' => 0]);
        }

        // Trim whitespace
        if ($this->has('description')) {
            $this->merge(['description' => trim($this->description)]);
        }

        if ($this->has('reference_number')) {
            $this->merge(['reference_number' => trim($this->reference_number)]);
        }
    }
}
