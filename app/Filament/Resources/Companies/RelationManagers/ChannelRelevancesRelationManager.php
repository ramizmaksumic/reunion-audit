<?php

namespace App\Filament\Resources\Companies\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChannelRelevancesRelationManager extends RelationManager
{
    protected static string $relationship = 'channelRelevances';

    protected static ?string $title = 'Relevantnost digitalnih kanala';

    /**
     * "Očekivani digitalni kanali" iz Profil kompanije upitnika
     * (docs/rds-methodology.md, sekcija 9).
     *
     * @var array<string, string>
     */
    private const CHANNELS = [
        'web_stranica' => 'Web stranica',
        'webshop' => 'Webshop',
        'google_business_profil' => 'Google Business profil',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'specijalizovane_platforme' => 'Booking / Airbnb / TripAdvisor ili druge specijalizovane platforme',
        'email_marketing' => 'Email marketing',
        'crm_sistem' => 'CRM sistem',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('channel_key')
                    ->label('Kanal')
                    ->options(self::CHANNELS)
                    ->required(),
                Select::make('relevance')
                    ->label('Relevantnost')
                    ->options([
                        'critical' => 'Kritičan',
                        'recommended' => 'Preporučen',
                        'not_relevant' => 'Nije relevantan',
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('channel_key')
            ->columns([
                TextColumn::make('channel_key')
                    ->label('Kanal')
                    ->formatStateUsing(fn (string $state): string => self::CHANNELS[$state] ?? $state),
                TextColumn::make('relevance')
                    ->label('Relevantnost')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'critical' => 'Kritičan',
                        'recommended' => 'Preporučen',
                        'not_relevant' => 'Nije relevantan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'critical' => 'danger',
                        'recommended' => 'warning',
                        'not_relevant' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
