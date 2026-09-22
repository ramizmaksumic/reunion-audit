<?php

namespace App\Filament\Resources\Leads;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Models\Assessment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Read-only view onto quick-scan submissions from the public /brzi-audit
 * lead generator (see App\Livewire\QuickAudit). Not a general Assessment
 * CRUD — leads are only ever created by a visitor completing the public
 * wizard, never here.
 */
class LeadResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?string $navigationLabel = 'Brzi audit leadovi';

    protected static ?string $modelLabel = 'lead';

    protected static ?string $pluralModelLabel = 'leadovi';

    protected static string|UnitEnum|null $navigationGroup = 'Klijenti';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('mode', 'quick_scan')->with('company');
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
        ];
    }
}
