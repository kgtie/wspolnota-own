<?php

namespace App\Filament\SuperAdmin\Resources\Users;

use App\Filament\SuperAdmin\Resources\Users\Pages\CreateUser;
use App\Filament\SuperAdmin\Resources\Users\Pages\EditUser;
use App\Filament\SuperAdmin\Resources\Users\Pages\ListUsers;
use App\Filament\SuperAdmin\Resources\Users\Pages\ViewUser;
use App\Filament\SuperAdmin\Resources\Users\Schemas\UserForm;
use App\Filament\SuperAdmin\Resources\Users\Schemas\UserInfolist;
use App\Filament\SuperAdmin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'uzytkownik';

    protected static ?string $pluralModelLabel = 'uzytkownicy';

    protected static ?string $navigationLabel = 'Uzytkownicy';

    protected static string|UnitEnum|null $navigationGroup = 'Platforma';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['homeParish', 'currentParish', 'lastManagedParish', 'verifiedBy', 'media'])
            ->withCount(['managedParishes', 'registeredMasses']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getEloquentQuery()
            ->where('is_user_verified', false)
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->where('is_user_verified', false)->exists()
            ? 'warning'
            : 'success';
    }

    public static function generateUniqueVerificationCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (User::withTrashed()->where('verification_code', $code)->exists());

        return $code;
    }

    public static function verifyRecord(User $record, ?User $verifiedBy = null): void
    {
        $hadVerificationCode = filled($record->verification_code);
        $verificationCode = $record->verification_code ?: static::generateUniqueVerificationCode();

        $record->update([
            'is_user_verified' => true,
            'user_verified_at' => $record->user_verified_at ?? now(),
            'verified_by_user_id' => $verifiedBy?->id,
            'verification_code' => $verificationCode,
        ]);

        static::logSuperAdminUserManagementEvent(
            event: 'user_verified_by_code',
            record: $record,
            actor: $verifiedBy,
            description: 'Superadmin zatwierdzil uzytkownika kodem weryfikacyjnym.',
            properties: [
                'verification_method' => '9_digit_code',
                'verification_code_was_already_set' => $hadVerificationCode,
            ],
        );
    }

    public static function verifyRecordWithCode(User $record, string $providedCode, ?User $verifiedBy = null): bool
    {
        $expectedCode = (string) ($record->verification_code ?? '');
        $normalizedCode = preg_replace('/\D+/', '', $providedCode) ?? '';

        if ($normalizedCode !== $expectedCode) {
            static::logSuperAdminUserManagementEvent(
                event: 'user_verification_failed_invalid_code',
                record: $record,
                actor: $verifiedBy,
                description: 'Superadmin podal nieprawidlowy kod podczas zatwierdzania uzytkownika.',
                properties: [
                    'provided_code_length' => strlen($normalizedCode),
                    'expected_code_exists' => $expectedCode !== '',
                ],
            );

            return false;
        }

        static::verifyRecord($record, $verifiedBy);

        return true;
    }

    public static function unverifyRecord(User $record, ?User $performedBy = null): void
    {
        $record->update([
            'is_user_verified' => false,
            'user_verified_at' => null,
            'verified_by_user_id' => null,
        ]);

        static::logSuperAdminUserManagementEvent(
            event: 'user_verification_revoked',
            record: $record,
            actor: $performedBy,
            description: 'Superadmin cofnal zatwierdzenie uzytkownika.',
            properties: [
                'verification_method' => '9_digit_code',
            ],
        );
    }

    public static function regenerateVerificationCode(User $record, ?User $performedBy = null): string
    {
        $code = static::generateUniqueVerificationCode();
        $hadPreviousCode = filled($record->verification_code);

        $record->update([
            'verification_code' => $code,
        ]);

        static::logSuperAdminUserManagementEvent(
            event: 'user_verification_code_regenerated',
            record: $record,
            actor: $performedBy,
            description: 'Superadmin wygenerowal nowy kod weryfikacyjny uzytkownika.',
            properties: [
                'had_previous_code' => $hadPreviousCode,
            ],
        );

        return $code;
    }

    private static function resolveActor(?User $actor = null): ?User
    {
        if ($actor instanceof User) {
            return $actor;
        }

        $authUser = Filament::auth()->user();

        return $authUser instanceof User ? $authUser : null;
    }

    private static function logSuperAdminUserManagementEvent(
        string $event,
        User $record,
        string $description,
        ?User $actor = null,
        array $properties = [],
    ): void {
        $resolvedActor = static::resolveActor($actor);

        if (! $resolvedActor) {
            return;
        }

        activity('superadmin-user-management')
            ->causedBy($resolvedActor)
            ->performedOn($record)
            ->event($event)
            ->withProperties(array_merge([
                'target_user_id' => $record->getKey(),
                'target_user_email' => $record->email,
                'target_home_parish_id' => $record->home_parish_id,
            ], $properties))
            ->log($description);
    }
}
