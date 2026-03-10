<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $assignedRole = data_get($this->form->getState(), 'assigned_role');
        $defaultRole = Auth::user()?->can('roles.manage') ? ($assignedRole ?: 'member') : 'member';

        $this->record->syncRoles([$defaultRole]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
