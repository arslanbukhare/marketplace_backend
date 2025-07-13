<?php

namespace App\Filament\Resources\FeaturedAdPlanResource\Pages;

use App\Filament\Resources\FeaturedAdPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeaturedAdPlans extends ListRecords
{
    protected static string $resource = FeaturedAdPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
