<?php

namespace App\Policies;

use App\Models\User;
use Filament\Facades\Filament;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return match ($panelId) {
            'superadmin' => $user->role === User::ROLE_SUPERADMIN,
            'admin' => $user->role >= User::ROLE_ADMIN,
            default => false,
        };
    }

    public function view(User $user, User $record): bool
    {
        // Analogicznie jak viewAny – dostęp do rekordów kontrolujemy w Resource query (tenant scoping).
        return $this->viewAny($user);
    }

    public function update(User $user, User $record): bool
    {
        $panelId = Filament::getCurrentPanel()?->getId();

        return match ($panelId) {
            'superadmin' => $user->role === User::ROLE_SUPERADMIN,
            'admin' => $user->role >= User::ROLE_ADMIN, // tylko akcje w Resource, nie pełny edit
            default => false,
        };
    }
}
