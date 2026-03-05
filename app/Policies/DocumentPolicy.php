<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function view(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function update(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage');
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->hasPermission('documents.manage');
    }
}
