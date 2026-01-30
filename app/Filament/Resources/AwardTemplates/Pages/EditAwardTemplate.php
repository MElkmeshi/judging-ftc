<?php

namespace App\Filament\Resources\AwardTemplates\Pages;

use App\Filament\Resources\AwardTemplates\AwardTemplateResource;
use Filament\Actions\DeleteAction;
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
}
