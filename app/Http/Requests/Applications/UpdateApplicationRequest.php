<?php

namespace App\Http\Requests\Applications;

use Illuminate\Support\Arr;

class UpdateApplicationRequest extends StoreApplicationRequest
{
    // PATCH: every field optional, same constraints when present.
    public function rules(): array
    {
        return Arr::map(parent::rules(), fn (array $rules) => ['sometimes', ...array_diff($rules, ['sometimes'])]);
    }
}
