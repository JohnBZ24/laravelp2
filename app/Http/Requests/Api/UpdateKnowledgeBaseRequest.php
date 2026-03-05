<?php

namespace App\Http\Requests\Api;

use App\Models\KnowledgeBase;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKnowledgeBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var KnowledgeBase $knowledgeBase */
        $knowledgeBase = $this->route('knowledgeBase');

        return $this->user()?->can('update', $knowledgeBase) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
