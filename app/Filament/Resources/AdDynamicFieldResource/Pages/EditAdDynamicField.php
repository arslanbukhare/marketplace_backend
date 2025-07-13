<?php

namespace App\Filament\Resources\AdDynamicFieldResource\Pages;

use App\Filament\Resources\AdDynamicFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdDynamicField extends EditRecord
{
    protected static string $resource = AdDynamicFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
