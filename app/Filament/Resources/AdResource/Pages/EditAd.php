<?php

namespace App\Filament\Resources\AdResource\Pages;

use App\Filament\Resources\AdResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\AdDynamicValue;

class EditAd extends EditRecord
{
    protected static string $resource = AdResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }


    // protected function afterSave(): void
    // {
    //     $ad = $this->record;
    //     $formData = $this->form->getState();

    //     foreach ($formData as $key => $value) {
    //         if (str_starts_with($key, 'field_')) {
    //             $fieldId = str_replace('field_', '', $key);

    //             \App\Models\AdDynamicValue::updateOrCreate(
    //                 ['ad_id' => $ad->id, 'field_id' => $fieldId],
    //                 ['value' => $value],
    //             );
    //         }
    //     }
    // }

    protected function afterSave(): void
    {
        $ad = $this->record;
        $ad->user_type = 'admin';
        $ad->save();
        $formData = $this->form->getState();

        // ✅ Save or update dynamic field values
        foreach ($formData as $key => $value) {
            if (str_starts_with($key, 'field_')) {
                $fieldId = str_replace('field_', '', $key);

                \App\Models\AdDynamicValue::updateOrCreate(
                    ['ad_id' => $ad->id, 'field_id' => $fieldId],
                    ['value' => $value],
                );
            }
        }

        // ✅ Replace existing ad images
        \App\Models\AdImage::where('ad_id', $ad->id)->delete();

        foreach ($formData['images'] ?? [] as $path) {
            \App\Models\AdImage::create([
                'ad_id' => $ad->id,
                'image_path' => $path,
            ]);
        }
    }




    
}
