<?php

namespace App\Http\Requests\Setting;

use App\Repositories\Currency\Currency;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingOverallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->hasRole('administrator') || auth()->user()?->hasRole('owner');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $validCurrencies = array_keys(Currency::getAllCurrencies());
        $validLanguages  = ['en', 'dk'];

        return [
            'client_number'  => ['required', 'integer', 'min:1'],
            'invoice_number' => ['required', 'integer', 'min:1'],
            'company'        => ['nullable', 'string', 'max:255'],
            'country'        => ['nullable', 'string', 'size:2'],
            'language'       => ['nullable', 'string', 'in:' . implode(',', $validLanguages)],
            'currency'       => ['nullable', 'string', 'in:' . implode(',', $validCurrencies)],
            'vat'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'start_time'     => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'end_time'       => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'client_number.required'  => __('Client number is required.'),
            'client_number.integer'   => __('Client number must be an integer.'),
            'client_number.min'       => __('Client number must be at least 1.'),
            'invoice_number.required' => __('Invoice number is required.'),
            'invoice_number.integer'  => __('Invoice number must be an integer.'),
            'invoice_number.min'      => __('Invoice number must be at least 1.'),
            'currency.in'             => __('The selected currency is invalid.'),
            'language.in'             => __('The selected language is invalid.'),
            'country.size'            => __('Country must be a 2-letter ISO code.'),
            'vat.numeric'             => __('VAT must be a number.'),
            'vat.min'                 => __('VAT must be at least 0.'),
            'vat.max'                 => __('VAT cannot exceed 100%.'),
            'start_time.regex'        => __('Start time must be in HH:MM format.'),
            'end_time.regex'          => __('End time must be in HH:MM format.'),
        ];
    }
}
