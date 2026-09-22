<?php

namespace App\Filament\Resources\Leads\Tables;

use App\Models\Assessment;
use App\Services\QuickAuditScoringService;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LeadsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('company.name')
                    ->label('Kompanija')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.website')
                    ->label('Web')
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('score')
                    ->label('Ocjena')
                    ->badge()
                    ->state(function (Assessment $record): string {
                        $score = app(QuickAuditScoringService::class)->overallScore($record)['score'];

                        return $score !== null ? number_format($score, 0).'/100' : '—';
                    }),
                TextColumn::make('company.contact_name')
                    ->label('Kontakt osoba')
                    ->placeholder('—'),
                TextColumn::make('company.contact_email')
                    ->label('Email')
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('company.contact_phone')
                    ->label('Telefon')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('contact_requested_at')
                    ->label('Zatražen kontakt')
                    ->boolean()
                    ->getStateUsing(fn (Assessment $record) => $record->contact_requested_at !== null),
                TextColumn::make('created_at')
                    ->label('Popunjeno')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('contact_requested')
                    ->label('Zatražen kontakt')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('contact_requested_at'),
                        false: fn ($query) => $query->whereNull('contact_requested_at'),
                    ),
            ])
            ->recordActions([
                Action::make('view_result')
                    ->label('Pogledaj rezultat')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Assessment $record) => route('quick-audit.results', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
