<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Osnovni podaci')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Naziv kompanije')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('industry')
                            ->label('Djelatnost'),
                        Select::make('b2b_or_b2c')
                            ->label('B2B ili B2C')
                            ->options([
                                'b2b' => 'B2B',
                                'b2c' => 'B2C',
                                'both' => 'Kombinovano',
                            ]),
                        Select::make('market_scope')
                            ->label('Geografsko tržište')
                            ->options([
                                'local' => 'Lokalno',
                                'regional' => 'Regionalno',
                                'national' => 'Nacionalno',
                                'international' => 'Međunarodno',
                            ]),
                        Textarea::make('business_model_notes')
                            ->label('Napomene o poslovnom modelu')
                            ->columnSpanFull(),
                    ]),

                Section::make('Poslovni model')
                    ->description('Određuje koji digitalni kanali/kriteriji su relevantni za ovu kompaniju.')
                    ->columns(2)
                    ->components([
                        Toggle::make('has_physical_location')
                            ->label('Ima fizičku lokaciju za korisnike'),
                        Toggle::make('sells_online')
                            ->label('Prodaje proizvode online'),
                        Toggle::make('provides_online_services')
                            ->label('Pruža usluge online'),
                        Toggle::make('works_by_appointment')
                            ->label('Radi po rezervacijama ili terminima'),
                        Toggle::make('has_multiple_locations')
                            ->label('Posluje kroz više poslovnica'),
                    ]),
            ]);
    }
}
