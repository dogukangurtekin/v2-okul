<?php

namespace Database\Seeders;

use App\Models\Avatar;
use App\Models\Badge;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $avatars = [
            ['Robot', '🤖', 'basic', 0, 0], ['Kedi', '🐱', 'basic', 50, 0], ['Köpek', '🐶', 'basic', 50, 0],
            ['Roket', '🚀', 'rare', 150, 100], ['Ejderha', '🐲', 'rare', 250, 200],
            ['Uzaylı', '👽', 'epic', 500, 400], ['Gökkuşağı Tek Boynuz', '🦄', 'epic', 750, 600],
            ['Altın Kral', '👑', 'premium', 1500, 1200],
        ];
        foreach ($avatars as [$name, $emoji, $tier, $price, $req]) {
            Avatar::firstOrCreate(['name' => $name], compact('emoji', 'tier', 'price') + ['required_xp' => $req]);
        }

        $badges = [
            ['İlk Adım', '🌱', 'İlk 10 XP', 10], ['Meraklı', '🔎', '100 XP topladın', 100],
            ['Kodcu', '💻', '300 XP topladın', 300], ['Usta', '🏅', '1000 XP topladın', 1000],
            ['Efsane', '🏆', '3000 XP topladın', 3000],
        ];
        foreach ($badges as [$name, $emoji, $description, $xp]) {
            Badge::firstOrCreate(['name' => $name], compact('emoji', 'description') + ['xp_threshold' => $xp]);
        }
    }
}
