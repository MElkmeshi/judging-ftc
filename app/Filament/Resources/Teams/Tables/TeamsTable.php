<?php

namespace App\Filament\Resources\Teams\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeamsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('team_number')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('team_name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->school_organization)
                    ->weight('medium'),

                TextColumn::make('events.name')
                    ->badge()
                    ->color('gray')
                    ->separator(', ')
                    ->label('Events'),

                TextColumn::make('location')
                    ->state(function ($record) {
                        $parts = array_filter([
                            $record->city,
                            $record->state_province,
                            $record->country !== 'USA' ? $record->country : null,
                        ]);

                        return implode(', ', $parts) ?: '-';
                    })
                    ->placeholder('-')
                    ->searchable(['city', 'state_province', 'country']),

                IconColumn::make('is_rookie')
                    ->boolean()
                    ->alignCenter()
                    ->label('Rookie'),

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
                SelectFilter::make('events')
                    ->relationship('events', 'name')
                    ->label('Event')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('is_rookie')
                    ->options([
                        1 => 'Rookie Teams',
                        0 => 'Veteran Teams',
                    ])
                    ->label('Team Experience'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('team_number', 'asc');
    }
}
