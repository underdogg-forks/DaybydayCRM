<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'closeLead' => ['nullable', 'boolean'],
            'openLead' => ['nullable', 'boolean'],
        ];
    }
}
