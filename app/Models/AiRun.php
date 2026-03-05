<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'provider',
        'model',
        'input_tokens',
        'output_tokens',
        'cost',
        'latency_ms',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cost' => 'decimal:6',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
