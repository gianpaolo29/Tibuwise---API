<?php

namespace App\Http\Requests;

use App\Models\Coin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoinBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coins' => ['required', 'array', 'min:1', 'max:100'],
            'coins.*.denomination' => ['required', 'integer', Rule::in(Coin::VALID_DENOMINATIONS)],
            'coins.*.age_seconds' => ['required', 'integer', 'min:0', 'max:604800'],
            'coins.*.client_id' => ['required', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ];
    }
}
