<?php

namespace App\Filament\Superadmin\Resources\Users\Tables;

use App\Models\Parish;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;


class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Login')->searchable()->sortable(),
                TextColumn::make('full_name')->label('Imię i nazwisko')->searchable()->sortable(),
                TextColumn::make('email')->label('E-mail')->searchable()->sortable(),

                TextColumn::make('role')
                    ->label('Rola')
                    ->formatStateUsing(fn (int $state) => User::roleOptions()[$state] ?? (string) $state)
                    ->sortable(),

                TextColumn::make('homeParish.name')->label('Parafia domowa')->toggleable(),

                IconColumn::make('is_user_verified')->label('Zweryfikowany')->boolean()->sortable(),
                TextColumn::make('verification_code')->label('Kod')->copyable()->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('role')->label('Rola')->options(User::roleOptions()),

                SelectFilter::make('home_parish_id')
                    ->label('Parafia domowa')
                    ->options(fn () => Parish::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->headerActions([
                CreateAction::make()->label('Dodaj'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('regenerateCode')
                    ->label('Nowy kod 9 cyfr')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->regenerateVerificationCode();

                        Notification::make()
                            ->title('Wygenerowano nowy kod')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->disabled(fn (User $record) => auth()->id() === $record->id),

                RestoreAction::make(),

                ForceDeleteAction::make()
                    ->disabled(fn (User $record) => auth()->id() === $record->id),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records
                                ->reject(fn ($record) => $record->id === auth()->id())
                                ->each->softDelete();
                        }),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records
                                ->reject(fn ($record) => $record->id === auth()->id())
                                ->each->forceDelete();
                        }),
                ]),
            ]);
    }
}
