<?php

namespace App\Filament\Superadmin\Resources\Users;

use App\Filament\Superadmin\Resources\Users\UserResource\Pages;
use App\Filament\Superadmin\Resources\Users\UserResource\RelationManagers\ManagedParishesRelationManager;
use App\Filament\Superadmin\Resources\Users\Schemas\UserForm;
use App\Filament\Superadmin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationLabel(): string
    {
        return 'Użytkownicy';
    }

    public static function getModelLabel(): string
    {
        return 'Użytkownik';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Użytkownicy';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ManagedParishesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

}
