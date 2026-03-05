<?php

namespace App\AI\Contracts;

use App\Models\User;

interface Tool
{
    public function name(): string;

    public function description(): string;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function run(User $user, array $payload): array;
}
