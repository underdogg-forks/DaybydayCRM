<?php

namespace App\Http\Requests\Offer;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;

class CreateOfferRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->can(PermissionName::OFFER_CREATE->value);
    }

    public function rules()
    {
        return [
            '*.title'    => 'required|string',
            '*.type'     => 'required|string',
            '*.price'    => 'required|numeric',
            '*.quantity' => 'required|numeric|min:1',
            '*.comment'  => 'nullable|string',
            '*.product'  => 'nullable|string',
        ];
    }
}
