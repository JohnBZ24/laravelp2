<?php

namespace App\Http\Requests\Api;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'knowledge_base_id' => ['required', 'integer', 'exists:knowledge_bases,id'],
            'title' => ['required', 'string', 'max:255'],
            'source' => ['nullable', 'string', 'max:1000'],
            'raw_text' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
