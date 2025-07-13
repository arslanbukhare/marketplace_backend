<?php

namespace Database\Seeders;
use App\Models\Category;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::insert([
            ['name' => 'Vehicles', 'slug' => 'vehicles', 'has_dynamic_fields' => true],
            ['name' => 'Real Estate', 'slug' => 'real-estate', 'has_dynamic_fields' => true],
            ['name' => 'Electronics', 'slug' => 'electronics', 'has_dynamic_fields' => false],
        ]);
    }
}
