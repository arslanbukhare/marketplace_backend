<?php

namespace Database\Seeders;
use App\Models\Category;
use App\Models\Subcategory;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = Category::where('slug', 'vehicles')->first();
        $realEstate = Category::where('slug', 'real-estate')->first();
        $electronics = Category::where('slug', 'electronics')->first();

        Subcategory::insert([
            ['category_id' => $vehicles->id, 'name' => 'Cars', 'slug' => 'cars'],
            ['category_id' => $vehicles->id, 'name' => 'Motorcycles', 'slug' => 'motorcycles'],
            ['category_id' => $realEstate->id, 'name' => 'Apartments', 'slug' => 'apartments'],
            ['category_id' => $realEstate->id, 'name' => 'Villas', 'slug' => 'villas'],
            ['category_id' => $electronics->id, 'name' => 'Mobiles', 'slug' => 'mobiles'],
            ['category_id' => $electronics->id, 'name' => 'Laptops', 'slug' => 'laptops'],
        ]);
    }
}
