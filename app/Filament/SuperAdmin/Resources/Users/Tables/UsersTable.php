<?php

namespace App\Filament\SuperAdmin\Resources\Users\Tables;

use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->imageSize(40),

                TextColumn::make('full_name')
                    ->label('Uzytkownik')
                    ->placeholder('Brak imienia i nazwiska')
                    ->searchable(['full_name', 'name', 'email'])
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->name ? "@{$record->name}" : null),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Skopiowano email'),

                TextColumn::make('role')
                    ->label('Rola')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        2 => 'Superadministrator',
                        1 => 'Administrator',
                        default => 'Uzytkownik',
                    })
                    ->color(fn (int $state): string => match ($state) {
                        2 => 'danger',
                        1 => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'banned' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('homeParish.short_name')
                    ->label('Parafia domowa')
                    ->placeholder('Brak')
                    ->toggleable(),

                TextColumn::make('managed_parishes_count')
                    ->label('Parafie admin')
                    ->badge()
                    ->sortable(),

                TextColumn::make('is_user_verified')
                    ->label('Zatwierdzenie')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Zatwierdzony' : 'Oczekuje')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('verification_code')
                    ->label('Kod weryfikacyjny')
                    ->placeholder('Brak')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('verifiedBy.full_name')
                    ->label('Zatwierdzil')
                    ->placeholder('Brak')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_login_at')
                    ->label('Ostatnie logowanie')
                    ->since()
                    ->placeholder('Brak')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Utworzono')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Usuniety')
                    ->since()
                    ->placeholder('Nie')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Rola')
                    ->options([
                        0 => 'Uzytkownik',
                        1 => 'Administrator',
                        2 => 'Superadministrator',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktywne',
                        'inactive' => 'Nieaktywne',
                        'banned' => 'Zablokowane',
                    ]),

                TernaryFilter::make('email_verified_at')
                    ->label('Email zweryfikowany')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TernaryFilter::make('is_user_verified')
                    ->label('Zatwierdzony')
                    ->boolean()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::verifyUserWithCodeAction(),
                    self::unverifyUserAction(),
                    self::regenerateCodeAction(),
                    self::sendPasswordResetLinkAction(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::unverifyUsersBulkAction(),
                    self::regenerateCodesBulkAction(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }

    protected static function verifyUserWithCodeAction(): Action
    {
        return Action::make('verify_user_with_code')
            ->label('Zatwierdz kodem')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->visible(fn (User $record): bool => ! $record->is_user_verified)
            ->schema([
                TextInput::make('provided_code')
                    ->label('Kod podany przez uzytkownika')
                    ->required()
                    ->minLength(9)
                    ->maxLength(9)
                    ->regex('/^\d{9}$/'),
            ])
            ->action(function (User $record, array $data): void {
                $verifiedBy = Filament::auth()->user();
                $providedCode = (string) ($data['provided_code'] ?? '');

                $wasVerified = UserResource::verifyRecordWithCode(
                    $record,
                    $providedCode,
                    $verifiedBy instanceof User ? $verifiedBy : null,
                );

                if (! $wasVerified) {
                    throw ValidationException::withMessages([
                        'provided_code' => 'Podany kod jest nieprawidlowy.',
                    ]);
                }
            })
            ->successNotificationTitle('Uzytkownik zostal zatwierdzony.');
    }

    protected static function unverifyUserAction(): Action
    {
        return Action::make('unverify_user')
            ->label('Cofnij zatwierdzenie')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (User $record): bool => $record->is_user_verified)
            ->action(function (User $record): void {
                $admin = Filament::auth()->user();

                UserResource::unverifyRecord(
                    $record,
                    $admin instanceof User ? $admin : null,
                );
            })
            ->successNotificationTitle('Zatwierdzenie zostalo cofniete.');
    }

    protected static function regenerateCodeAction(): Action
    {
        return Action::make('regenerate_code')
            ->label('Wygeneruj nowy kod 9-cyfrowy')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $admin = Filament::auth()->user();

                UserResource::regenerateVerificationCode(
                    $record,
                    $admin instanceof User ? $admin : null,
                );

                Notification::make()
                    ->success()
                    ->title('Wygenerowano nowy kod weryfikacyjny.')
                    ->send();
            });
    }

    protected static function sendPasswordResetLinkAction(): Action
    {
        return Action::make('send_password_reset_link')
            ->label('Wyslij link resetu hasla')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $status = Password::sendResetLink(['email' => $record->email]);

                if ($status !== Password::RESET_LINK_SENT) {
                    Notification::make()
                        ->danger()
                        ->title('Nie udalo sie wyslac linku resetu hasla.')
                        ->body(__($status))
                        ->send();

                    return;
                }

                $admin = Filament::auth()->user();

                if ($admin instanceof User) {
                    activity('superadmin-user-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('password_reset_link_sent')
                        ->withProperties([
                            'recipient_email' => $record->email,
                        ])
                        ->log('Superadmin wyslal uzytkownikowi link resetu hasla.');
                }

                Notification::make()
                    ->success()
                    ->title('Wyslano link resetu hasla.')
                    ->send();
            });
    }

    protected static function unverifyUsersBulkAction(): BulkAction
    {
        return BulkAction::make('unverify_users_bulk')
            ->label('Cofnij zatwierdzenie')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->action(function ($records): void {
                $admin = Filament::auth()->user();
                $adminUser = $admin instanceof User ? $admin : null;
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof User || ! $record->is_user_verified) {
                        continue;
                    }

                    UserResource::unverifyRecord($record, $adminUser);
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Cofnieto zatwierdzenia.')
                    ->body("Liczba zmienionych rekordow: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function regenerateCodesBulkAction(): BulkAction
    {
        return BulkAction::make('regenerate_codes_bulk')
            ->label('Wygeneruj nowe kody 9-cyfrowe')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->action(function ($records): void {
                $admin = Filament::auth()->user();
                $adminUser = $admin instanceof User ? $admin : null;
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof User) {
                        continue;
                    }

                    UserResource::regenerateVerificationCode($record, $adminUser);
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Wygenerowano nowe kody.')
                    ->body("Liczba zmienionych rekordow: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
