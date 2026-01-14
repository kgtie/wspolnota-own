<?php

namespace Database\Seeders;

use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $parishes = Parish::query()->take(15)->get();

        foreach ($parishes as $parish) {
            $author = User::query()->where('role', 1)->inRandomOrder()->first();

            for ($i = 1; $i <= 8; $i++) {
                $title = "Aktualność {$i} – {$parish->name}";
                $post = NewsPost::create([
                    'parish_id' => $parish->id,
                    'author_user_id' => $author?->id ?? User::query()->first()->id,
                    'title' => $title,
                    'slug' => Str::slug($title).'-'.Str::random(6),
                    'excerpt' => 'Krótki wstęp do aktualności...',
                    'content' => '<p>Treść przykładowej aktualności…</p>',
                    'status' => $i % 3 === 0 ? 'published' : 'draft',
                    'published_at' => $i % 3 === 0 ? now()->subDays(rand(0, 30)) : null,
                ]);
            }
        }
        $anotherNewsPost = NewsPost::create([
            'parish_id' => '1',
            'author_user_id' => '1',
            'title' => 'Przykładowa aktualność dla parafii Wiskitki',
            'slug' => Str::slug('Przykladowa-aktualnosc').'-'.Str::random(6),
            'excerpt' => 'Krótki wstęp do aktualności...',
            'content' => '<p>Treść przykładowej aktualności…</p>',
            'status' => 'published',
            'published_at' => now()->subDays(rand(0, 30)),
        ]);
    }
}
