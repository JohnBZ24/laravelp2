<?php

namespace App\Filament\Resources\Documents\Schemas;

use App\Models\KnowledgeBase;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('knowledge_base_id')
                ->label('Knowledge Base')
                ->options(KnowledgeBase::query()->pluck('name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('source')->maxLength(1000),
            Textarea::make('raw_text')->rows(10),
        ]);
    }
}
