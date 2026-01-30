<?php

namespace App\Filament\Resources\AwardTemplates;

use App\Filament\Resources\AwardTemplates\Pages\CreateAwardTemplate;
use App\Filament\Resources\AwardTemplates\Pages\EditAwardTemplate;
use App\Filament\Resources\AwardTemplates\Pages\ListAwardTemplates;
use App\Filament\Resources\AwardTemplates\Schemas\AwardTemplateForm;
use App\Filament\Resources\AwardTemplates\Tables\AwardTemplatesTable;
use App\Models\AwardTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AwardTemplateResource extends Resource
{
    protected static ?string $model = AwardTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return AwardTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AwardTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CriterionTemplatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAwardTemplates::route('/'),
            'create' => CreateAwardTemplate::route('/create'),
            'edit' => EditAwardTemplate::route('/{record}/edit'),
        ];
    }
}
