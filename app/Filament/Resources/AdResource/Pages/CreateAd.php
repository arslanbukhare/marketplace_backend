<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\AdDynamicValue;

class CreateAd extends CreateRecord
{
    protected static string $resource = AdResource::class;

    

    // protected function afterCreate(): void
    // {
    //     $ad = $this->record;
    //     $formData = $this->form->getState();

    //     foreach ($formData as $key => $value) {
    //         if (str_starts_with($key, 'field_')) {
    //             $fieldId = str_replace('field_', '', $key);

    //             \App\Models\AdDynamicValue::create([
    //                 'ad_id' => $ad->id,
    //                 'field_id' => $fieldId,
    //                 'value' => $value,
    //             ]);
    //         }
    //     }
    // }

    protected function afterCreate(): void
    {
        $ad = $this->record;
        $ad->user_type = 'admin';
        $ad->save();
        $formData = $this->form->getState();

        // ✅ Save dynamic field values
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'field_')) {
                $fieldId = str_replace('field_', '', $key);

                \App\Models\AdDynamicValue::create([
                    'ad_id' => $ad->id,
                    'field_id' => $fieldId,
                    'value' => $value,
                ]);
            }
        }

        // ✅ Save uploaded images to AdImage model
        foreach ($formData['images'] ?? [] as $path) {
            \App\Models\AdImage::create([
                'ad_id' => $ad->id,
                'image_path' => $path,
            ]);
        }
    }




}
