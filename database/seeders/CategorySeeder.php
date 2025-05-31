<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Программирование'],
            ['name' => 'Дизайн'],
            ['name' => 'Администрирование'],
            ['name' => 'Мобильная разработка']
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
