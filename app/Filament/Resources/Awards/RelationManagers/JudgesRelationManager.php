<?php

namespace App\Filament\Resources\Awards\RelationManagers;

use App\Enums\UserRole;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class JudgesRelationManager extends RelationManager
{
    protected static string $relationship = 'judges';

    protected static ?string $title = 'Assigned Judges';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
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

                TextColumn::make('role')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function ($query) {
                        // Only show judges who are assigned to this event
                        $eventId = $this->getOwnerRecord()->event_id;

                        return $query->where('role', UserRole::Judge)
                            ->whereHas('events', function ($q) use ($eventId) {
                                $q->where('events.id', $eventId)
                                    ->where('event_user.can_score', true);
                            });
                    })
                    ->label('Assign Judge')
                    ->modalHeading('Assign Judge to Award')
                    ->modalDescription('Only judges assigned to this event with scoring permission are shown.'),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->description('Judges assigned to this award can score teams. They must first be assigned to the event.');
    }
}
