<?php

namespace App\Filament\Resources\Awards\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AwardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('event.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray')
                    ->description(fn ($record) => $record->event->code),

                TextColumn::make('criteria_count')
                    ->counts('criteria')
                    ->label('Criteria')
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                TextColumn::make('judges_count')
                    ->counts('judges')
                    ->label('Judges')
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                IconColumn::make('is_ranked')
                    ->boolean()
                    ->alignCenter()
                    ->label('Ranked'),

                IconColumn::make('is_hierarchical')
                    ->boolean()
                    ->alignCenter()
                    ->label('Hierarchical')
                    ->tooltip('Award levels depend on team count (Inspire Award)'),

                IconColumn::make('is_locked')
                    ->boolean()
                    ->alignCenter()
                    ->label('Locked')
                    ->tooltip('Locked awards cannot be scored'),

                IconColumn::make('is_finalized')
                    ->boolean()
                    ->alignCenter()
                    ->label('Finalized')
                    ->tooltip('Finalized awards have official winners'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->relationship('event', 'name')
                    ->label('Event')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('award_template_id')
                    ->relationship('awardTemplate', 'name')
                    ->label('Template')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_locked')
                    ->options([
                        1 => 'Locked',
                        0 => 'Unlocked',
                    ])
                    ->label('Lock Status'),

                SelectFilter::make('is_finalized')
                    ->options([
                        1 => 'Finalized',
                        0 => 'Not Finalized',
                    ])
                    ->label('Finalization Status'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
