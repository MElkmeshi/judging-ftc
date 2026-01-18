<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Enums\UserRole;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JudgesRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Assigned Judges';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Toggle::make('can_score')
                            ->label('Can Score Teams')
                            ->default(true)
                            ->helperText('Judge can score teams for this event'),

                        Toggle::make('can_deliberate')
                            ->label('Can Deliberate')
                            ->default(false)
                            ->helperText('Judge can view deliberation dashboard and assign awards'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('can_score')
                    ->label('Can Score')
                    ->boolean()
                    ->alignCenter(),

                IconColumn::make('can_deliberate')
                    ->label('Can Deliberate')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn ($query) => $query->where('role', UserRole::Judge))
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('can_score')
                                    ->label('Can Score Teams')
                                    ->default(true)
                                    ->helperText('Judge can score teams for this event'),

                                Toggle::make('can_deliberate')
                                    ->label('Can Deliberate')
                                    ->default(false)
                                    ->helperText('Judge can view deliberation dashboard'),
                            ]),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
