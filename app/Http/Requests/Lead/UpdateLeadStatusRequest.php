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
            'status_id' => ['nullable', 'integer', 'exists:statuses,id', 'prohibits:closeLead,openLead'],
            'closeLead' => ['nullable', 'boolean', 'prohibits:status_id,openLead'],
            'openLead'  => ['nullable', 'boolean', 'prohibits:status_id,closeLead'],
        ];
    }
}
