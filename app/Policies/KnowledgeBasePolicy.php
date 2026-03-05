<?php

namespace App\Policies;

use App\Models\KnowledgeBase;
use App\Models\User;

class KnowledgeBasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('knowledge_bases.view');
    }

    public function view(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->hasPermission('knowledge_bases.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('knowledge_bases.manage');
    }

    public function update(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->hasPermission('knowledge_bases.manage');
    }

    public function delete(User $user, KnowledgeBase $knowledgeBase): bool
    {
        return $user->hasPermission('knowledge_bases.manage');
    }
}
