<?php

namespace App\Http\Requests\Applications;

use App\Enums\ApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company' => ['required', 'string', 'max:120'],
            'position' => ['required', 'string', 'max:120'],
            'url' => ['nullable', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'required', Rule::enum(ApplicationStatus::class)],
            'applied_at' => ['nullable', 'date_format:Y-m-d'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
