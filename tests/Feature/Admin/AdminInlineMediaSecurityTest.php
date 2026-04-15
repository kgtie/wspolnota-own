<?php

use App\Models\NewsPost;
use App\Models\Parish;
use App\Models\User;

test('inactive admin can not upload inline news media', function () {
    $parish = Parish::factory()->create();
    $admin = User::factory()->admin()->create([
        'status' => 'inactive',
    ]);

    $admin->managedParishes()->attach($parish->getKey(), [
        'is_active' => true,
        'assigned_at' => now(),
    ]);

    $post = NewsPost::query()->create([
        'parish_id' => $parish->getKey(),
        'title' => 'Bezpieczny wpis',
        'content' => 'Tresć testowa',
        'status' => 'draft',
        'comments_enabled' => true,
    ]);

    $response = $this
        ->actingAs($admin)
        ->post("/admin/news-posts/{$post->getKey()}/inline-image");

    $response->assertForbidden();
});
