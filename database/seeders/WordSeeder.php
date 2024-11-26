<?php

namespace Database\Seeders;

use App\Models\Word;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class WordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = storage_path('app/words_dictionary.json');
        $jsonData = File::get($jsonPath);

        $words = json_decode($jsonData, true);

        foreach (array_keys($words) as $word) {
            Word::create(['word' => $word]);
        }
    }
}
