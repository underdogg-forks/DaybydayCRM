<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->can('user-update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'                  => ['required'],
            'email'                 => ['required', 'email'],
            'address'               => ['nullable', 'string'],
            'primary_number'        => ['nullable', 'numeric'],
            'secondary_number'      => ['nullable', 'numeric'],
            'password'              => ['sometimes', 'min:6', 'confirmed'],
            'password_confirmation' => ['sometimes', 'min:6'],
            'image_path'            => ['nullable', 'file'],
            'role'                  => ['required', 'integer', 'exists:roles,id'],
            'department'            => ['required', 'integer', 'exists:departments,id'],
        ];
    }
}
