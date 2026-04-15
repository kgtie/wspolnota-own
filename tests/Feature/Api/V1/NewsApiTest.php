<?php

use App\Models\NewsComment;
use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

it('lists threaded comments for a published news post without internal errors', function (): void {
    $parish = Parish::factory()->create();
    $author = User::factory()->create([
        'home_parish_id' => $parish->getKey(),
    ]);

    $post = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Aktualnosc testowa',
        'slug' => 'aktualnosc-testowa',
        'content' => 'Tresc wpisu',
        'status' => 'published',
        'published_at' => now()->subHour(),
        'is_pinned' => false,
        'comments_enabled' => true,
    ]);

    $root = NewsComment::query()->create([
        'news_post_id' => $post->getKey(),
        'user_id' => $author->getKey(),
        'body' => 'Komentarz glowny',
    ]);

    $reply = NewsComment::query()->create([
        'news_post_id' => $post->getKey(),
        'user_id' => $author->getKey(),
        'parent_id' => $root->getKey(),
        'body' => 'Odpowiedz',
    ]);

    NewsComment::query()->create([
        'news_post_id' => $post->getKey(),
        'user_id' => $author->getKey(),
        'parent_id' => $reply->getKey(),
        'body' => 'Odpowiedz drugiego poziomu',
    ]);

    $this->getJson('/api/v1/parishes/'.$parish->getKey().'/news/'.$post->getKey().'/comments')
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $root->getKey())
        ->assertJsonPath('data.0.depth', 0)
        ->assertJsonPath('data.0.replies.0.id', (string) $reply->getKey())
        ->assertJsonPath('data.0.replies.0.depth', 1)
        ->assertJsonPath('data.0.replies.0.replies.0.depth', 2)
        ->assertJsonPath('data.0.replies_count', 1);
});

it('allows posting a reply to an existing news comment', function (): void {
    $parish = Parish::factory()->create();
    $user = User::factory()->verified()->create([
        'email' => 'comments@example.com',
        'password' => Hash::make('Secret#2026'),
        'status' => 'active',
        'home_parish_id' => $parish->getKey(),
    ]);

    $post = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Aktualnosc z komentarzami',
        'slug' => 'aktualnosc-z-komentarzami',
        'content' => 'Tresc wpisu',
        'status' => 'published',
        'published_at' => now()->subHour(),
        'is_pinned' => false,
        'comments_enabled' => true,
    ]);

    $parent = NewsComment::query()->create([
        'news_post_id' => $post->getKey(),
        'user_id' => $user->getKey(),
        'body' => 'Komentarz rodzic',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'login' => $user->email,
        'password' => 'Secret#2026',
        'device' => [
            'platform' => 'android',
            'device_id' => 'comments-device-1234',
            'device_name' => 'Pixel',
            'app_version' => '1.0.0',
        ],
    ])->assertOk();

    $accessToken = $loginResponse->json('data.tokens.access_token');

    $this->withHeader('Authorization', "Bearer {$accessToken}")
        ->postJson('/api/v1/parishes/'.$parish->getKey().'/news/'.$post->getKey().'/comments', [
            'body' => 'To jest odpowiedz dziecka',
            'parent_id' => $parent->getKey(),
        ])
        ->assertCreated()
        ->assertJsonPath('data.comment.parent_id', (string) $parent->getKey())
        ->assertJsonPath('data.comment.depth', 1)
        ->assertJsonPath('data.comment.user.id', (string) $user->getKey())
        ->assertJsonPath('data.comment.replies_count', 0);
});

it('lists gallery media for a published news post', function (): void {
    Storage::fake('news');

    $parish = Parish::factory()->create();

    $post = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Galeria wpisu',
        'slug' => 'galeria-wpisu',
        'content' => 'Tresc',
        'status' => 'published',
        'published_at' => now()->subHour(),
        'is_pinned' => false,
        'comments_enabled' => true,
    ]);

    $post->addMedia(UploadedFile::fake()->image('foto-1.jpg', 1200, 800))
        ->toMediaCollection('gallery', 'news');
    $post->addMedia(UploadedFile::fake()->image('foto-2.jpg', 1200, 800))
        ->toMediaCollection('gallery', 'news');

    $this->getJson('/api/v1/parishes/'.$parish->getKey().'/news/'.$post->getKey().'/gallery')
        ->assertOk()
        ->assertJsonCount(2, 'data.gallery')
        ->assertJsonStructure([
            'data' => [
                'gallery' => [
                    '*' => ['id', 'file_name', 'mime_type', 'size', 'original_url', 'preview_url', 'thumb_url'],
                ],
            ],
        ]);
});

it('lists attachment media for a published news post', function (): void {
    Storage::fake('news');

    $parish = Parish::factory()->create();

    $post = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Zalaczniki wpisu',
        'slug' => 'zalaczniki-wpisu',
        'content' => 'Tresc',
        'status' => 'published',
        'published_at' => now()->subHour(),
        'is_pinned' => false,
        'comments_enabled' => true,
    ]);

    $post->addMedia(UploadedFile::fake()->createWithContent('biuletyn.pdf', '%PDF-1.4 test'))
        ->toMediaCollection('attachments', 'news');

    $this->getJson('/api/v1/parishes/'.$parish->getKey().'/news/'.$post->getKey().'/attachments')
        ->assertOk()
        ->assertJsonCount(1, 'data.attachments')
        ->assertJsonStructure([
            'data' => [
                'attachments' => [
                    '*' => ['id', 'file_name', 'mime_type', 'size', 'download_url'],
                ],
            ],
        ]);
});
