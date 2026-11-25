<?php

namespace Database\Seeders;

use App\Models\CustomerCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /** @return array<int, array<string, string>> */
    private const CATEGORIES = [
        ['name' => 'Cliente ativo', 'slug' => 'cliente-ativo'],
        ['name' => 'Prospect', 'slug' => 'prospect'],
        ['name' => 'Lead qualificado', 'slug' => 'lead-qualificado'],
        ['name' => 'Inativo', 'slug' => 'inativo'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            CustomerCategory::firstOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'is_active' => true]
            );
        }
    }
}
