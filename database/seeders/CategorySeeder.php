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
        $categories = ['Category 1', 'Category 2', 'Category 3', 'Category 4', 'Category 5'];

        if(!Category::count()){
            foreach($categories as $category){
                Category::create(['name' => $category]);
            }
        }
    }
}
