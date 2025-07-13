<?php

namespace App\Filament\Resources\AdDynamicFieldOptionResource\Pages;

use App\Filament\Resources\AdDynamicFieldOptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdDynamicFieldOption extends EditRecord
{
    protected static string $resource = AdDynamicFieldOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
