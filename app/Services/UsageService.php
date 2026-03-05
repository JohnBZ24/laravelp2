<?php

namespace App\Services;

use App\Models\AiRun;
use App\Models\Conversation;
use App\Models\User;
use Carbon\CarbonInterface;

class UsageService
{
    public function exceedsQuota(User $user, ?CarbonInterface $month = null): bool
    {
        $month ??= now();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $quota = (int) config('ai.chat.monthly_token_quota', 250000);

        $used = (int) AiRun::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('input_tokens');

        $used += (int) AiRun::query()
            ->where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->sum('output_tokens');

        return $used >= $quota;
    }

    public function trackRun(
        User $user,
        ?Conversation $conversation,
        string $provider,
        string $model,
        int $inputTokens,
        int $outputTokens,
        int $latencyMs,
        string $status = 'completed'
    ): AiRun {
        $cost = $this->estimateCost($inputTokens, $outputTokens);

        return AiRun::query()->create([
            'conversation_id' => $conversation?->id,
            'user_id' => $user->id,
            'provider' => $provider,
            'model' => $model,
            'input_tokens' => max($inputTokens, 0),
            'output_tokens' => max($outputTokens, 0),
            'cost' => $cost,
            'latency_ms' => max($latencyMs, 0),
            'status' => $status,
        ]);
    }

    public function estimateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = $inputTokens * 0.000001;
        $outputCost = $outputTokens * 0.000002;

        return round($inputCost + $outputCost, 6);
    }
}
