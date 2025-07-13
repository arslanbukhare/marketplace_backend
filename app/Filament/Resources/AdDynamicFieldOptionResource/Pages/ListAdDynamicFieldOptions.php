<?php

namespace App\Filament\Resources\AdDynamicFieldOptionResource\Pages;

use App\Filament\Resources\AdDynamicFieldOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdDynamicFieldOptions extends ListRecords
{
    protected static string $resource = AdDynamicFieldOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
