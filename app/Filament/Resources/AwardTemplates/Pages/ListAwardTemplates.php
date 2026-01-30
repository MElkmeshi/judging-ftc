<?php

namespace App\Filament\Resources\AwardTemplates\Pages;

use App\Filament\Resources\AwardTemplates\AwardTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAwardTemplates extends ListRecords
{
    protected static string $resource = AwardTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
