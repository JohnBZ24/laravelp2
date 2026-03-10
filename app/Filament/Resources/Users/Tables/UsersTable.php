<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Forms\Components\TextInput;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->sortable(false),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('id')
                    ->schema([
                        TextInput::make('id')
                            ->label('ID')
                            ->numeric(),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['id'] ?? null),
                        fn (Builder $query): Builder => $query->where('id', $data['id']),
                    )),
                Filter::make('name')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['name'] ?? null),
                        fn (Builder $query): Builder => $query->where('name', 'like', "%{$data['name']}%"),
                    )),
                Filter::make('email')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['email'] ?? null),
                        fn (Builder $query): Builder => $query->where('email', 'like', "%{$data['email']}%"),
                    )),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->visible(fn (): bool => Auth::user()?->can('users.update') ?? false),
                DeleteAction::make()->visible(fn (): bool => Auth::user()?->can('users.delete') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => Auth::user()?->can('users.delete') ?? false),
                ]),
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25);
    }
}
