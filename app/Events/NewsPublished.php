<?php

namespace App\Events;

use App\Models\NewsPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewsPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly NewsPost $newsPost) {}
}
