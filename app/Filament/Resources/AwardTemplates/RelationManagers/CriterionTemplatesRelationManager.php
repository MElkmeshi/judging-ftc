<?php

namespace App\Filament\Resources\AwardTemplates\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CriterionTemplatesRelationManager extends RelationManager
{
    protected static string $relationship = 'criteriaTemplates';

    protected static ?string $title = 'Criteria Templates';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Innovation')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Describe what judges should evaluate...'),

                        TextInput::make('weight')
                            ->numeric()
                            ->required()
                            ->default(1.00)
                            ->step(0.01)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Weight for calculating total score'),

                        TextInput::make('max_score')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Maximum score a judge can give'),

                        TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->reorderable('display_order')
            ->defaultSort('display_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('weight')
                    ->numeric(decimalPlaces: 2)
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('max_score')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('display_order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
