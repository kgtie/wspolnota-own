<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('appoint_parish_admin')
                    ->label('Ustanów administratora')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->schema([
                        Select::make('user_id')
                            ->label('Użytkownik (zweryfikowany email)')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => static::getAssignableUsersSearchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getUserLabelById($value))
                            ->helperText('Wyszukaj użytkownika (z dowolnej parafii) po imieniu, nazwie użytkownika lub emailu.'),
                    ])
                    ->action(function (array $data): void {
                        $tenant = Filament::getTenant();
                        $actor = Filament::auth()->user();

                        if (! $tenant || ! $actor instanceof User) {
                            Notification::make()
                                ->danger()
                                ->title('Nie udało się nadać uprawnień administratora.')
                                ->send();

                            return;
                        }

                        $target = static::getAssignableUsersQuery()
                            ->whereKey((int) ($data['user_id'] ?? 0))
                            ->first();

                        if (! $target instanceof User) {
                            throw ValidationException::withMessages([
                                'user_id' => 'Wskazany użytkownik nie istnieje lub nie ma zweryfikowanego emaila.',
                            ]);
                        }

                        DB::transaction(function () use ($tenant, $target): void {
                            $timestamp = now();

                            $pivotQuery = DB::table('parish_user')
                                ->where('parish_id', $tenant->getKey())
                                ->where('user_id', $target->getKey());

                            if ($pivotQuery->exists()) {
                                $pivotQuery->update([
                                    'is_active' => true,
                                    'assigned_at' => $timestamp,
                                    'note' => 'Uprawnienia administratora parafii aktywne.',
                                    'updated_at' => $timestamp,
                                ]);
                            } else {
                                DB::table('parish_user')->insert([
                                    'parish_id' => $tenant->getKey(),
                                    'user_id' => $target->getKey(),
                                    'is_active' => true,
                                    'assigned_at' => $timestamp,
                                    'note' => 'Nadano uprawnienia administratora parafii.',
                                    'created_at' => $timestamp,
                                    'updated_at' => $timestamp,
                                ]);
                            }

                            $updates = [];

                            if ($target->role < 1) {
                                $updates['role'] = 1;
                            }

                            if (blank($target->last_managed_parish_id)) {
                                $updates['last_managed_parish_id'] = $tenant->getKey();
                            }

                            if ($updates !== []) {
                                $target->update($updates);
                            }
                        });

                        activity('parish-admin-management')
                            ->causedBy($actor)
                            ->performedOn($target)
                            ->event('parish_admin_assigned')
                            ->withProperties([
                                'parish_id' => $tenant->getKey(),
                                'parish_name' => $tenant->name,
                                'assigned_user_email' => $target->email,
                            ])
                            ->log('Proboszcz ustanowił użytkownika administratorem parafii.');

                        Notification::make()
                            ->success()
                            ->title('Ustanowiono administratora parafii.')
                            ->body(static::formatUserLabel($target))
                            ->send();
                    }),
                Action::make('revoke_parish_admin')
                    ->label('Odwołaj administratora')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->schema([
                        Select::make('user_id')
                            ->label('Administrator do odwołania')
                            ->required()
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => static::getCurrentParishAdminsSearchResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => static::getCurrentParishAdminLabelById($value)),
                    ])
                    ->action(function (array $data): void {
                        $tenant = Filament::getTenant();
                        $actor = Filament::auth()->user();

                        if (! $tenant || ! $actor instanceof User) {
                            Notification::make()
                                ->danger()
                                ->title('Nie udało się odwołać administratora.')
                                ->send();

                            return;
                        }

                        $target = static::getCurrentParishAdminsQuery()
                            ->whereKey((int) ($data['user_id'] ?? 0))
                            ->first();

                        if (! $target instanceof User) {
                            throw ValidationException::withMessages([
                                'user_id' => 'Wskazany administrator nie jest aktywnie przypisany do tej parafii.',
                            ]);
                        }

                        $activeAdminsCount = static::getCurrentParishAdminsQuery()->count();

                        if (($target->id === $actor->id) && ($activeAdminsCount <= 1)) {
                            throw ValidationException::withMessages([
                                'user_id' => 'Nie możesz odwołać ostatniego aktywnego administratora tej parafii.',
                            ]);
                        }

                        DB::transaction(function () use ($tenant, $target): void {
                            $timestamp = now();

                            DB::table('parish_user')
                                ->where('parish_id', $tenant->getKey())
                                ->where('user_id', $target->getKey())
                                ->update([
                                    'is_active' => false,
                                    'note' => 'Uprawnienia administratora parafii odwołane.',
                                    'updated_at' => $timestamp,
                                ]);

                            $hasOtherActiveParishes = DB::table('parish_user')
                                ->where('user_id', $target->getKey())
                                ->where('is_active', true)
                                ->exists();

                            if (($target->role === 1) && ! $hasOtherActiveParishes) {
                                $target->update([
                                    'role' => 0,
                                    'last_managed_parish_id' => null,
                                ]);

                                return;
                            }

                            if (
                                ((int) $target->last_managed_parish_id === (int) $tenant->getKey()) &&
                                $hasOtherActiveParishes
                            ) {
                                $target->update([
                                    'last_managed_parish_id' => DB::table('parish_user')
                                        ->where('user_id', $target->getKey())
                                        ->where('is_active', true)
                                        ->orderByDesc('assigned_at')
                                        ->orderByDesc('id')
                                        ->value('parish_id'),
                                ]);
                            }
                        });

                        activity('parish-admin-management')
                            ->causedBy($actor)
                            ->performedOn($target)
                            ->event('parish_admin_revoked')
                            ->withProperties([
                                'parish_id' => $tenant->getKey(),
                                'parish_name' => $tenant->name,
                                'revoked_user_email' => $target->email,
                                'demoted_to_regular_user' => ($target->role === 0),
                            ])
                            ->log('Proboszcz odwołał administratora parafii.');

                        Notification::make()
                            ->success()
                            ->title('Odwołano administratora parafii.')
                            ->body(static::formatUserLabel($target))
                            ->send();
                    }),
            ])
                ->label('Administratorzy parafii')
                ->icon('heroicon-o-users')
                ->visible(fn (): bool => $this->activeTab === 'admins'),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $baseQuery = $this->getTableQuery();

        return [
            'parishioners' => Tab::make('Parafianie')
                ->icon('heroicon-o-users')
                ->badge((clone $baseQuery)->tap(fn (Builder $query) => static::scopeParishionersQuery($query))->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => static::scopeParishionersQuery($query)),
            'admins' => Tab::make('Administratorzy parafii')
                ->icon('heroicon-o-shield-check')
                ->badge((clone $baseQuery)->tap(fn (Builder $query) => static::scopeParishAdminsQuery($query))->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => static::scopeParishAdminsQuery($query)),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'parishioners';
    }

    protected function getTableQuery(): Builder
    {
        $query = User::query()->with(['homeParish', 'verifiedBy']);

        if ($tenancyScope = Filament::getCurrentPanel()?->getTenancyScopeName()) {
            $query->withoutGlobalScope($tenancyScope);
        }

        return $query;
    }

    protected static function getAssignableUsersQuery(): Builder
    {
        return User::query()
            ->withoutGlobalScopes()
            ->whereNull('users.deleted_at')
            ->whereNotNull('email_verified_at');
    }

    protected static function getCurrentParishAdminsQuery(): Builder
    {
        $query = static::scopeParishAdminsQuery(User::query());

        if ($tenancyScope = Filament::getCurrentPanel()?->getTenancyScopeName()) {
            $query->withoutGlobalScope($tenancyScope);
        }

        return $query;
    }

    protected static function getAssignableUsersSearchResults(string $search): array
    {
        return static::getAssignableUsersQuery()
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => static::formatUserLabel($user)])
            ->all();
    }

    protected static function getCurrentParishAdminsSearchResults(string $search): array
    {
        return static::getCurrentParishAdminsQuery()
            ->where(function (Builder $query) use ($search): void {
                $query
                    ->where('full_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->limit(50)
            ->get()
            ->mapWithKeys(fn (User $user): array => [$user->id => static::formatUserLabel($user)])
            ->all();
    }

    protected static function getUserLabelById(int | string | null $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        $user = static::getAssignableUsersQuery()->find($id);

        return $user instanceof User ? static::formatUserLabel($user) : null;
    }

    protected static function getCurrentParishAdminLabelById(int | string | null $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        $user = static::getCurrentParishAdminsQuery()->find($id);

        return $user instanceof User ? static::formatUserLabel($user) : null;
    }

    protected static function formatUserLabel(User $user): string
    {
        $name = $user->full_name ?: $user->name;

        return "{$name} ({$user->email})";
    }

    protected static function scopeParishionersQuery(Builder $query): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('users.role', 0)
            ->where('users.home_parish_id', $tenantId);
    }

    protected static function scopeParishAdminsQuery(Builder $query): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        if (! $tenantId) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('managedParishes', fn (Builder $query): Builder => $query
                ->where('parishes.id', $tenantId)
                ->where('parish_user.is_active', true));
    }
}
