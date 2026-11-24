<?php

namespace App\Http\Requests;

use App\Models\Coin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'denomination' => ['required', 'integer', Rule::in(Coin::VALID_DENOMINATIONS)],
            'deposited_at' => ['required', 'date', 'before_or_equal:+5 minutes'],
            'client_id' => ['nullable', 'string', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ];
    }
}
