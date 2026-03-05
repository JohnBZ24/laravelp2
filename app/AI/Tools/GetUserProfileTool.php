<?php

namespace App\AI\Tools;

use App\AI\Contracts\Tool;
use App\Models\User as UserModel;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class GetUserProfileTool implements Tool
{
    public function name(): string
    {
        return 'get_user_profile';
    }

    public function description(): string
    {
        return 'Returns profile details for the authenticated user.';
    }

    public function run(User $user, array $payload): array
    {
        $requestedUserId = (int) ($payload['user_id'] ?? $user->id);

        if ($requestedUserId !== $user->id && ! $user->hasPermission('users.view.any')) {
            throw new AuthorizationException('Not allowed to access this user profile.');
        }

        $targetUser = $requestedUserId === $user->id
            ? $user
            : UserModel::query()->findOrFail($requestedUserId);

        return [
            'id' => $targetUser->id,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
        ];
    }
}
