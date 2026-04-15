<?php

namespace App\Filament\Admin\Resources\NewsComments\Pages;

use App\Filament\Admin\Resources\NewsComments\NewsCommentResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Filament\Support\NewsComments\Pages\ThreadedNewsCommentsPage;

class ListNewsComments extends ThreadedNewsCommentsPage
{
    protected static string $resource = NewsCommentResource::class;

    protected function canModerateFully(): bool
    {
        return false;
    }

    protected function getPostResourceClass(): string
    {
        return NewsPostResource::class;
    }
}
