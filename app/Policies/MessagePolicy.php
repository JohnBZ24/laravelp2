<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->conversation->user_id;
    }

    public function create(User $user, int $conversationUserId): bool
    {
        return $user->id === $conversationUserId;
    }
}
