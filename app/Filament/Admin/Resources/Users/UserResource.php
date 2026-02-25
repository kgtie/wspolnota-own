<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Schemas\UserInfolist;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
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

    protected static ?string $tenantOwnershipRelationshipName = 'homeParish';

    protected static ?string $modelLabel = 'parafianin';

    protected static ?string $pluralModelLabel = 'parafianie';

    protected static ?string $navigationLabel = 'Parafianie';

    protected static string|UnitEnum|null $navigationGroup = 'Wspólnota';

    protected static ?int $navigationSort = 10;

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
            ->where('role', 0)
            ->with(['homeParish', 'verifiedBy']);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
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
        if (! Filament::getTenant()) {
            return null;
        }

        $pendingCount = static::getEloquentQuery()
            ->where('is_user_verified', false)
            ->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        if (! Filament::getTenant()) {
            return null;
        }

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
        $record->update([
            'is_user_verified' => true,
            'user_verified_at' => $record->user_verified_at ?? now(),
            'verified_by_user_id' => $verifiedBy?->id,
            'verification_code' => $record->verification_code ?: static::generateUniqueVerificationCode(),
        ]);
    }

    public static function verifyRecordWithCode(User $record, string $providedCode, ?User $verifiedBy = null): bool
    {
        $expectedCode = (string) ($record->verification_code ?? '');
        $normalizedCode = preg_replace('/\D+/', '', $providedCode) ?? '';

        if ($normalizedCode !== $expectedCode) {
            return false;
        }

        static::verifyRecord($record, $verifiedBy);

        return true;
    }

    public static function unverifyRecord(User $record): void
    {
        $record->update([
            'is_user_verified' => false,
            'user_verified_at' => null,
            'verified_by_user_id' => null,
        ]);
    }

    public static function regenerateVerificationCode(User $record): string
    {
        $code = static::generateUniqueVerificationCode();

        $record->update([
            'verification_code' => $code,
        ]);

        return $code;
    }
}
