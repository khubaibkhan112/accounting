<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreAccountRequest extends BaseFormRequest
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
            'account_code' => ['required', 'string', 'max:50', 'unique:accounts,account_code'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'parent_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'opening_balance' => ['nullable', 'numeric', 'min:-999999999.99', 'max:999999999.99'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'account_code.required' => 'Account code is required.',
            'account_code.unique' => 'This account code already exists.',
            'account_code.max' => 'Account code cannot exceed 50 characters.',
            'account_name.required' => 'Account name is required.',
            'account_name.max' => 'Account name cannot exceed 255 characters.',
            'account_type.required' => 'Account type is required.',
            'account_type.in' => 'Account type must be one of: asset, liability, equity, revenue, expense.',
            'parent_account_id.exists' => 'The selected parent account does not exist.',
            'opening_balance.numeric' => 'Opening balance must be a number.',
            'opening_balance.min' => 'Opening balance cannot be less than -999,999,999.99.',
            'opening_balance.max' => 'Opening balance cannot exceed 999,999,999.99.',
            'description.max' => 'Description cannot exceed 1000 characters.',
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
            'account_code' => 'account code',
            'account_name' => 'account name',
            'account_type' => 'account type',
            'parent_account_id' => 'parent account',
            'opening_balance' => 'opening balance',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        if (!$this->has('is_active')) {
            $this->merge(['is_active' => true]);
        }

        if (!$this->has('opening_balance') || $this->opening_balance === null) {
            $this->merge(['opening_balance' => 0]);
        }

        // Trim whitespace
        if ($this->has('account_code')) {
            $this->merge(['account_code' => trim($this->account_code)]);
        }

        if ($this->has('account_name')) {
            $this->merge(['account_name' => trim($this->account_name)]);
        }
    }
}
