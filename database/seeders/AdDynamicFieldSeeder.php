<?php

namespace Database\Seeders;
use App\Models\AdDynamicField;
use App\Models\Category;
use App\Models\AdDynamicFieldOption;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdDynamicFieldSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyTypeField = AdDynamicField::where('field_name', 'Property Type')->first();

        AdDynamicFieldOption::insert([
            ['field_id' => $propertyTypeField->id, 'value' => 'Apartment'],
            ['field_id' => $propertyTypeField->id, 'value' => 'Villa'],
            ['field_id' => $propertyTypeField->id, 'value' => 'House'],
        ]);
    }
}
