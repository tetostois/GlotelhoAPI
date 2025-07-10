<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShortUrl;

class ShortUrlSeeder extends Seeder
{
    public function run(): void
    {
        ShortUrl::create([
            'original_url' => 'https://www.example.com',
            'short_code' => 'exmpl1',
            'click_count' => 0,
        ]);
    }
}