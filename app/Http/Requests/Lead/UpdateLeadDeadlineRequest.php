<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadDeadlineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'deadline_date' => 'required|date',
            'deadline_time' => 'nullable|date_format:H:i',
        ];
    }
}
