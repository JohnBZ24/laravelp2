<?php

namespace App\AI\Tools;

use App\AI\Contracts\Tool;
use App\Models\User;

class GetOrdersTool implements Tool
{
    public function name(): string
    {
        return 'get_orders';
    }

    public function description(): string
    {
        return 'Returns recent order summary for the authenticated user.';
    }

    public function run(User $user, array $payload): array
    {
        if (! $user->hasPermission('orders.view')) {
            return ['orders' => []];
        }

        return [
            'orders' => [
                [
                    'order_number' => 'DEMO-1001',
                    'status' => 'processing',
                    'total' => 129.99,
                ],
            ],
        ];
    }
}
