<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\Award;
use App\Models\Event;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('role')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'danger' => UserRole::SuperAdmin->value,
                        'warning' => UserRole::Admin->value,
                        'success' => UserRole::Judge->value,
                    ]),
                TextColumn::make('email_verified_at')
                    ->label('Verified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(UserRole::class)
                    ->multiple()
                    ->native(false),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('addToEventAwards')
                        ->label('Add to Event Awards')
                        ->icon('heroicon-o-trophy')
                        ->color('primary')
                        ->form([
                            Select::make('event_id')
                                ->label('Select Event')
                                ->options(Event::query()->pluck('name', 'id'))
                                ->required()
                                ->native(false)
                                ->searchable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $event = Event::query()->find($data['event_id']);
                            $awards = $event->awards;
                            $judgeCount = $records->count();
                            $awardCount = $awards->count();

                            if ($awardCount === 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('No Awards Found')
                                    ->body("The event \"{$event->name}\" has no awards to assign judges to.")
                                    ->send();

                                return;
                            }

                            $totalAssignments = 0;

                            foreach ($awards as $award) {
                                foreach ($records as $judge) {
                                    if (! $award->judges()->where('user_id', $judge->id)->exists()) {
                                        $award->judges()->attach($judge->id);
                                        $totalAssignments++;
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Judges Added Successfully')
                                ->body("Added {$judgeCount} judge(s) to {$awardCount} award(s) in \"{$event->name}\" with {$totalAssignments} new assignment(s).")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('addToAllAwards')
                        ->label('Add to All Awards')
                        ->icon('heroicon-o-trophy')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Add Judges to All Awards')
                        ->modalDescription('This will add the selected judges to ALL awards across ALL events. Existing judge assignments will be preserved.')
                        ->modalSubmitActionLabel('Add to All Awards')
                        ->action(function (Collection $records) {
                            $judgeCount = $records->count();
                            $awards = Award::query()->get();
                            $awardCount = $awards->count();

                            if ($awardCount === 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('No Awards Found')
                                    ->body('There are no awards in any events to assign judges to.')
                                    ->send();

                                return;
                            }

                            $totalAssignments = 0;

                            foreach ($awards as $award) {
                                foreach ($records as $judge) {
                                    if (! $award->judges()->where('user_id', $judge->id)->exists()) {
                                        $award->judges()->attach($judge->id);
                                        $totalAssignments++;
                                    }
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Judges Added Successfully')
                                ->body("Added {$judgeCount} judge(s) to {$awardCount} award(s) with {$totalAssignments} new assignment(s).")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
