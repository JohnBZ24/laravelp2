<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'role' => $this->role,
            'content' => $this->content,
            'tool_name' => $this->tool_name,
            'tool_payload' => $this->tool_payload,
            'token_usage' => $this->token_usage,
            'created_at' => $this->created_at,
        ];
    }
}
