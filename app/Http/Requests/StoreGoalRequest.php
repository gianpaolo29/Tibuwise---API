<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:60'],
            'target_amount' => ['required', 'integer', 'min:1', 'max:1000000'],
            'deadline' => ['nullable', 'date', 'after:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => strip_tags(trim($this->input('name')))]);
        }
    }
}
