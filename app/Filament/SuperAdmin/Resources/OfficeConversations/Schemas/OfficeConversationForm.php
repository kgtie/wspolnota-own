<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\Schemas;

use App\Models\OfficeConversation;
use App\Models\Parish;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OfficeConversationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konwersacja online')
                    ->columns(2)
                    ->schema([
                        TextInput::make('uuid')
                            ->label('UUID')
                            ->maxLength(36)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Nadawany automatycznie')
                            ->columnSpanFull(),

                        Select::make('parish_id')
                            ->label('Parafia')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Parish::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),

                        Select::make('parishioner_user_id')
                            ->label('Parafianin')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => User::query()
                                ->where('role', 0)
                                ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->getKey() => $user->full_name ?: $user->name ?: $user->email,
                                ])
                                ->all()),

                        Select::make('priest_user_id')
                            ->label('Administrator prowadzacy')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => User::query()
                                ->where('role', '>=', 1)
                                ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->getKey() => $user->full_name ?: $user->name ?: $user->email,
                                ])
                                ->all()),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(OfficeConversation::getStatusOptions())
                            ->native(false)
                            ->default(OfficeConversation::STATUS_OPEN),

                        DateTimePicker::make('last_message_at')
                            ->label('Ostatnia wiadomosc')
                            ->seconds(false)
                            ->native(false),

                        DateTimePicker::make('closed_at')
                            ->label('Zamknieta od')
                            ->seconds(false)
                            ->native(false),
                    ]),
            ]);
    }
}
