<?php

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Models\AwardTemplate;
use App\Services\EventService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('initializeAwards')
                ->label('Initialize Awards')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('success')
                ->form([
                    CheckboxList::make('template_ids')
                        ->label('Select Award Templates to Initialize')
                        ->options(
                            AwardTemplate::where('is_active', true)
                                ->orderBy('display_order')
                                ->pluck('name', 'id')
                        )
                        ->descriptions(
                            AwardTemplate::where('is_active', true)
                                ->orderBy('display_order')
                                ->pluck('description', 'id')
                        )
                        ->default(
                            AwardTemplate::where('is_active', true)->pluck('id')->toArray()
                        )
                        ->required()
                        ->helperText('All active templates are selected by default'),
                ])
                ->action(function (array $data, EventService $eventService) {
                    $eventService->initializeEvent($this->record, $data['template_ids']);

                    Notification::make()
                        ->title('Awards initialized successfully')
                        ->body(count($data['template_ids']).' award(s) added to this event')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Initialize Awards from Templates')
                ->modalDescription('This will create awards for this event based on the selected templates. Awards can be customized after initialization.')
                ->modalSubmitActionLabel('Initialize Awards')
                ->visible(fn () => $this->record->awards()->count() === 0),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
