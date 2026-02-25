<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Filament\Admin\Resources\Users\UserResource;
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
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
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
            ->recordUrl(fn (User $record, $livewire): ?string => static::isParishionersTab($livewire)
                ? UserResource::getUrl('view', ['record' => $record])
                : null)
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->imageSize(40),

                TextColumn::make('full_name')
                    ->label('Parafianin')
                    ->placeholder('Brak imienia i nazwiska')
                    ->searchable(['full_name', 'name', 'email'])
                    ->sortable()
                    ->description(fn (User $record): ?string => $record->name ? "@{$record->name}" : null),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Skopiowano email'),

                TextColumn::make('email_verified_at')
                    ->label('Weryfikacja email')
                    ->state(fn (User $record): string => $record->email_verified_at ? 'Zweryfikowany' : 'Niezweryfikowany')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Zweryfikowany' ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('is_user_verified')
                    ->label('Zatwierdzenie')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Zatwierdzony' : 'Oczekuje')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning')
                    ->sortable(),

                TextColumn::make('verifiedBy.full_name')
                    ->label('Zatwierdził')
                    ->placeholder('Brak')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('last_login_at')
                    ->label('Ostatnie logowanie')
                    ->since()
                    ->placeholder('Brak')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Utworzony')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Usunięty')
                    ->since()
                    ->placeholder('Nie')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
                    ->label('Zatwierdzony przez proboszcza')
                    ->boolean()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
                    EditAction::make()
                        ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
                    self::verifyParishionerWithCodeAction(),
                    self::unverifyParishionerAction(),
                    self::regenerateCodeAction(),
                    self::sendPasswordResetLinkAction(),
                    DeleteAction::make()
                        ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
                    ForceDeleteAction::make()
                        ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
                    RestoreAction::make()
                        ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton()
                    ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::unverifyParishionersBulkAction(),
                    self::regenerateCodesBulkAction(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])
                    ->visible(fn ($livewire): bool => static::isParishionersTab($livewire)),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }

    protected static function verifyParishionerWithCodeAction(): Action
    {
        return Action::make('verify_user_with_code')
            ->label('Zatwierdź kodem')
            ->icon('heroicon-o-shield-check')
            ->color('success')
            ->visible(fn (User $record, $livewire): bool => static::isParishionersTab($livewire) && ! $record->is_user_verified)
            ->schema([
                TextInput::make('provided_code')
                    ->label('Kod podany przez parafianina')
                    ->required()
                    ->minLength(9)
                    ->maxLength(9)
                    ->regex('/^\d{9}$/')
                    ->helperText('Wpisz dokładnie 9 cyfr podanych przez parafianina.'),
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
                        'provided_code' => 'Podany kod jest nieprawidłowy. Wygeneruj nowy kod i spróbuj ponownie.',
                    ]);
                }
            })
            ->successNotificationTitle('Parafianin został zatwierdzony.');
    }

    protected static function unverifyParishionerAction(): Action
    {
        return Action::make('unverify_user')
            ->label('Cofnij zatwierdzenie')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (User $record, $livewire): bool => static::isParishionersTab($livewire) && $record->is_user_verified)
            ->action(fn (User $record) => UserResource::unverifyRecord($record))
            ->successNotificationTitle('Zatwierdzenie zostało cofnięte.');
    }

    protected static function regenerateCodeAction(): Action
    {
        return Action::make('regenerate_code')
            ->label('Wygeneruj nowy kod 9-cyfrowy')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->requiresConfirmation()
            ->visible(fn ($livewire): bool => static::isParishionersTab($livewire))
            ->action(function (User $record): void {
                UserResource::regenerateVerificationCode($record);

                Notification::make()
                    ->success()
                    ->title('Wygenerowano nowy kod weryfikacyjny.')
                    ->body('Przekaż parafianinowi, aby użył nowego kodu.')
                    ->send();
            });
    }

    protected static function sendPasswordResetLinkAction(): Action
    {
        return Action::make('send_password_reset_link')
            ->label('Wyślij link resetu hasła')
            ->icon('heroicon-o-key')
            ->color('gray')
            ->requiresConfirmation()
            ->visible(fn ($livewire): bool => static::isParishionersTab($livewire))
            ->action(function (User $record): void {
                $status = Password::sendResetLink(['email' => $record->email]);

                if ($status !== Password::RESET_LINK_SENT) {
                    Notification::make()
                        ->danger()
                        ->title('Nie udało się wysłać linku resetu hasła.')
                        ->body(__($status))
                        ->send();

                    return;
                }

                $admin = Filament::auth()->user();

                if ($admin instanceof User) {
                    activity('admin-user-management')
                        ->causedBy($admin)
                        ->performedOn($record)
                        ->event('password_reset_link_sent')
                        ->withProperties([
                            'recipient_email' => $record->email,
                            'parish_id' => Filament::getTenant()?->getKey(),
                        ])
                        ->log('Proboszcz wysłał parafianinowi link resetu hasła.');
                }

                Notification::make()
                    ->success()
                    ->title('Wysłano link resetu hasła.')
                    ->body('Parafianin otrzyma wiadomość z instrukcją ustawienia nowego hasła.')
                    ->send();
            });
    }

    protected static function unverifyParishionersBulkAction(): BulkAction
    {
        return BulkAction::make('unverify_users_bulk')
            ->label('Cofnij zatwierdzenie parafian')
            ->icon('heroicon-o-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->action(function ($records): void {
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof User || ! $record->is_user_verified) {
                        continue;
                    }

                    UserResource::unverifyRecord($record);
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Cofnięto zatwierdzenia.')
                    ->body("Liczba zmienionych rekordów: {$updated}")
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
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof User) {
                        continue;
                    }

                    UserResource::regenerateVerificationCode($record);
                    $updated++;
                }

                Notification::make()
                    ->success()
                    ->title('Wygenerowano nowe kody.')
                    ->body("Liczba zmienionych rekordów: {$updated}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function isParishionersTab(mixed $livewire): bool
    {
        $activeTab = data_get($livewire, 'activeTab');

        return $activeTab !== 'admins';
    }
}
