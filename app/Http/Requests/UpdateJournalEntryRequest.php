<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateJournalEntryRequest extends FormRequest
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
        $journalEntryId = $this->route('journal_entry')->id ?? null;

        return [
            'entry_date' => ['sometimes', 'required', 'date'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'reference_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('journal_entries', 'reference_number')->ignore($journalEntryId),
            ],
            'items' => ['sometimes', 'required', 'array', 'min:2'],
            'items.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'items.*.debit_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.credit_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Only validate items if they are being updated
            if (!$this->has('items')) {
                return;
            }

            $items = $this->input('items', []);
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($items as $index => $item) {
                $debit = (float) ($item['debit_amount'] ?? 0);
                $credit = (float) ($item['credit_amount'] ?? 0);

                // Each item must have either debit or credit (not both, not neither)
                if ($debit == 0 && $credit == 0) {
                    $validator->errors()->add("items.{$index}.debit_amount", "Item " . ($index + 1) . ": Either debit or credit amount must be greater than 0.");
                    $validator->errors()->add("items.{$index}.credit_amount", "Item " . ($index + 1) . ": Either debit or credit amount must be greater than 0.");
                }

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("items.{$index}.debit_amount", "Item " . ($index + 1) . ": Both debit and credit amounts cannot be greater than 0.");
                    $validator->errors()->add("items.{$index}.credit_amount", "Item " . ($index + 1) . ": Both debit and credit amounts cannot be greater than 0.");
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            // Total debits must equal total credits (double-entry principle)
            $difference = abs($totalDebit - $totalCredit);
            if ($difference >= 0.01) {
                $validator->errors()->add('items', sprintf(
                    'Journal entry is not balanced. Total debits: %s, Total credits: %s, Difference: %s',
                    number_format($totalDebit, 2),
                    number_format($totalCredit, 2),
                    number_format($difference, 2)
                ));
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
            'entry_date.required' => 'Entry date is required.',
            'entry_date.date' => 'Entry date must be a valid date.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'reference_number.unique' => 'This reference number already exists.',
            'reference_number.max' => 'Reference number cannot exceed 100 characters.',
            'items.required' => 'At least 2 items are required for a journal entry.',
            'items.min' => 'At least 2 items are required for a journal entry (double-entry bookkeeping).',
            'items.*.account_id.required' => 'Account is required for each item.',
            'items.*.account_id.exists' => 'One or more selected accounts do not exist.',
            'items.*.debit_amount.numeric' => 'Debit amount must be a number.',
            'items.*.debit_amount.min' => 'Debit amount cannot be negative.',
            'items.*.debit_amount.max' => 'Debit amount cannot exceed 999,999,999.99.',
            'items.*.credit_amount.numeric' => 'Credit amount must be a number.',
            'items.*.credit_amount.min' => 'Credit amount cannot be negative.',
            'items.*.credit_amount.max' => 'Credit amount cannot exceed 999,999,999.99.',
            'items.*.description.max' => 'Item description cannot exceed 500 characters.',
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
            'entry_date' => 'entry date',
            'description' => 'description',
            'reference_number' => 'reference number',
            'items' => 'items',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values for items if provided
        if ($this->has('items') && is_array($this->items)) {
            $preparedItems = [];
            foreach ($this->items as $item) {
                $preparedItems[] = [
                    'account_id' => $item['account_id'] ?? null,
                    'debit_amount' => isset($item['debit_amount']) && $item['debit_amount'] !== '' ? $item['debit_amount'] : 0,
                    'credit_amount' => isset($item['credit_amount']) && $item['credit_amount'] !== '' ? $item['credit_amount'] : 0,
                    'description' => isset($item['description']) ? trim($item['description']) : null,
                ];
            }
            $this->merge(['items' => $preparedItems]);
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
