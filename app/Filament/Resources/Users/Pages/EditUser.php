<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        if (! (Auth::user()?->can('roles.manage') ?? false)) {
            return;
        }

        $assignedRole = data_get($this->form->getState(), 'assigned_role');

        if ($assignedRole) {
            $this->record->syncRoles([$assignedRole]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
}
