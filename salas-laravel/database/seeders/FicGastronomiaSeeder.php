<?php

namespace Database\Seeders;

use App\Models\FicArea;
use Illuminate\Database\Seeder;

class FicGastronomiaSeeder extends Seeder
{
    public function run(): void
    {
        if (FicArea::query()->where('slug', 'gastronomia')->exists()) {
            return;
        }

        FicArea::query()->create([
            'name' => 'Gastronomia',
            'slug' => 'gastronomia',
            'kiosk_after_graduation' => true,
            'sort_order' => 0,
        ]);
    }
}
