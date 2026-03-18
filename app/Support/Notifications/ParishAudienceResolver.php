<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Support\Collection;

class ParishAudienceResolver
{
    /**
     * @param  int|array<int,int|string>|Collection<int,int|string>  $parishIds
     * @return Collection<int,User>
     */
    public function homeParishUsers(int|array|Collection $parishIds, bool $withDevices = false): Collection
    {
        $ids = collect(is_int($parishIds) ? [$parishIds] : $parishIds)
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $query = User::query()
            ->where('status', 'active')
            ->whereIn('home_parish_id', $ids->all())
            ->with('notificationPreference');

        if ($withDevices) {
            $query->with('devices');
        }

        return $query->get();
    }
}
