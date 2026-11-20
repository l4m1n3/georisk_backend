<?php

namespace App\Http\Requests\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.entity_type' => ['required', 'string', 'in:risk_zone,drainage_structure,report'],
            'items.*.entity_id' => ['required', 'uuid'],
            'items.*.operation' => ['required', 'string', 'in:create,update,delete'],
            'items.*.payload' => ['required', 'array'],
        ];
    }
}
