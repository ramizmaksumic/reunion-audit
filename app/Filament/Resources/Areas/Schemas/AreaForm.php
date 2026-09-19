<?php

namespace App\Filament\Resources\Areas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Ključ')
                    ->helperText('Jedinstveni identifikator, npr. "digitalna_prisutnost". Koristi se u kodu — ne mijenjati nasumično.')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('name')
                    ->label('Naziv')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Redoslijed')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
