<?php

namespace App\Filament\Resources\AwardTemplates\Pages;

use App\Filament\Resources\AwardTemplates\AwardTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAwardTemplate extends EditRecord
{
    protected static string $resource = AwardTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $linkedCount = $this->record->getLinkedEventsCount();

        if ($linkedCount > 0) {
            $syncedCount = $this->record->syncToLinkedAwards();

            Notification::make()
                ->title('Template saved and synced')
                ->body("Updated {$syncedCount} award(s) across all linked events. All scores have been reset.")
                ->warning()
                ->send();
        }
    }

    protected function getSaveFormAction(): Action
    {
        $linkedCount = $this->record->getLinkedEventsCount();

        if ($linkedCount > 0) {
            return Action::make('save')
                ->label(__('filament-panels::resources/pages/edit-record.form.actions.save.label'))
                ->requiresConfirmation()
                ->modalHeading('Sync to Linked Events?')
                ->modalDescription("This template is used by {$linkedCount} event(s). Saving will update all linked awards and DELETE ALL EXISTING SCORES. This action cannot be undone.")
                ->modalSubmitActionLabel('Yes, Save & Sync All')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->action(fn () => $this->save())
                ->keyBindings(['mod+s']);
        }

        return parent::getSaveFormAction();
    }
}
