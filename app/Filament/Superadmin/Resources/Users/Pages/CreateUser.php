<?php

namespace App\Filament\Superadmin\Resources\Users\UserResource\Pages;

use App\Filament\Superadmin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
