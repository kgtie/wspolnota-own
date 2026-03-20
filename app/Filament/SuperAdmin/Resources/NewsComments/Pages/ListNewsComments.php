<?php

namespace App\Filament\SuperAdmin\Resources\NewsComments\Pages;

use App\Filament\SuperAdmin\Resources\NewsComments\NewsCommentResource;
use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use App\Filament\Support\NewsComments\Pages\ThreadedNewsCommentsPage;

class ListNewsComments extends ThreadedNewsCommentsPage
{
    protected static string $resource = NewsCommentResource::class;

    protected function canModerateFully(): bool
    {
        return true;
    }

    protected function getPostResourceClass(): string
    {
        return NewsPostResource::class;
    }
}
