<?php

namespace App\Filament\SuperAdmin\Resources\Parishes\Tables;

use App\Models\Parish;
use App\Models\User;
use App\Support\Notifications\ParishAudienceResolver;
use App\Support\Reports\ParishPriestWeeklyDigestSender;
use App\Support\SuperAdmin\InstantCommunicationService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ParishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('Avatar')
                    ->collection('avatar')
                    ->conversion('thumb')
                    ->circular(),

                TextColumn::make('name')
                    ->label('Parafia')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->city),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->copyable()
                    ->copyMessage('Skopiowano slug')
                    ->searchable(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktywna' : 'Nieaktywna')
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('parishioners_count')->label('Parafianie')->badge()->sortable(),
                TextColumn::make('admins_count')->label('Admini')->badge()->sortable(),
                TextColumn::make('office_conversations_count')->label('Konwersacje')->badge()->sortable(),

                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Aktywna')
                    ->boolean()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    self::sendPriestWeeklyDigestAction(),
                    self::sendInstantPushAction(),
                    self::sendInstantEmailAction(),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    self::sendPriestWeeklyDigestBulkAction(),
                    self::sendInstantPushBulkAction(),
                    self::sendInstantEmailBulkAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function sendPriestWeeklyDigestAction(): Action
    {
        return Action::make('send_priest_weekly_digest')
            ->label('Wyslij checklistę proboszcza')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->schema([
                Checkbox::make('copy_to_superadmin')
                    ->label('Wyslij kopie takze do superadmina')
                    ->default(true),
            ])
            ->action(function (
                Parish $record,
                array $data,
                ParishPriestWeeklyDigestSender $sender,
            ): void {
                $actor = Filament::auth()->user();
                $result = $sender->sendForParish(
                    parish: $record,
                    generatedAt: now(),
                    copyToSuperadmin: (bool) ($data['copy_to_superadmin'] ?? false),
                    actor: $actor instanceof User ? $actor : null,
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano checklistę proboszcza.')
                    ->body("Odbiorcy: {$result['recipients']} · kopie: {$result['copies']}")
                    ->send();
            });
    }

    protected static function sendInstantPushAction(): Action
    {
        return Action::make('send_parish_push_now')
            ->label('Wyslij push')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->schema([
                Select::make('audience')
                    ->label('Odbiorcy')
                    ->options([
                        'parishioners' => 'Parafianie',
                        'admins' => 'Administratorzy',
                        'all' => 'Parafianie + administratorzy',
                    ])
                    ->default('parishioners')
                    ->required(),
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default(fn (Parish $record): string => 'Wiadomosc: '.$record->name),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000),
            ])
            ->action(function (
                Parish $record,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $users = self::resolveParishRecipients($record, (string) $data['audience'], $audiences);
                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla parafii.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function sendInstantEmailAction(): Action
    {
        return Action::make('send_parish_email_now')
            ->label('Wyslij email')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->schema([
                Select::make('audience')
                    ->label('Odbiorcy')
                    ->options([
                        'parishioners' => 'Parafianie',
                        'admins' => 'Administratorzy',
                        'all' => 'Parafianie + administratorzy',
                    ])
                    ->default('parishioners')
                    ->required(),
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default(fn (Parish $record): string => 'Wiadomosc dotyczaca parafii '.$record->name),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000),
            ])
            ->action(function (
                Parish $record,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $actor = Filament::auth()->user();
                $users = self::resolveParishRecipients($record, (string) $data['audience'], $audiences);
                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla parafii.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            });
    }

    protected static function sendInstantPushBulkAction(): BulkAction
    {
        return BulkAction::make('send_parish_push_bulk')
            ->label('Wyslij push')
            ->icon('heroicon-o-device-phone-mobile')
            ->color('info')
            ->schema([
                Select::make('audience')
                    ->label('Odbiorcy')
                    ->options([
                        'parishioners' => 'Parafianie',
                        'admins' => 'Administratorzy',
                        'all' => 'Parafianie + administratorzy',
                    ])
                    ->default('parishioners')
                    ->required(),
                TextInput::make('title')
                    ->label('Tytul')
                    ->required()
                    ->maxLength(120)
                    ->default('Wiadomosc do zaznaczonych parafii'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(5)
                    ->maxLength(1000),
            ])
            ->action(function (
                $records,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $users = self::resolveBulkParishRecipients($records, (string) $data['audience'], $audiences);
                $result = $service->queuePushToUsers(
                    users: $users,
                    title: (string) $data['title'],
                    body: (string) $data['body'],
                    type: 'MANUAL_MESSAGE',
                    routingData: [
                        'scope' => 'parishes_bulk',
                        'parish_ids' => json_encode($users->pluck('home_parish_id')->filter()->unique()->values()->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'source' => 'superadmin_bulk',
                    ],
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano push dla zaznaczonych parafii.')
                    ->body("Uzytkownicy: {$result['users']} · urzadzenia: {$result['devices']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendPriestWeeklyDigestBulkAction(): BulkAction
    {
        return BulkAction::make('send_priest_weekly_digest_bulk')
            ->label('Wyslij checklisty proboszczow')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->schema([
                Checkbox::make('copy_to_superadmin')
                    ->label('Wyslij kopie takze do superadmina')
                    ->default(true),
            ])
            ->action(function (
                $records,
                array $data,
                ParishPriestWeeklyDigestSender $sender,
            ): void {
                $actor = Filament::auth()->user();
                $recipients = 0;
                $copies = 0;

                foreach ($records as $record) {
                    if (! $record instanceof Parish) {
                        continue;
                    }

                    $result = $sender->sendForParish(
                        parish: $record,
                        generatedAt: now(),
                        copyToSuperadmin: (bool) ($data['copy_to_superadmin'] ?? false),
                        actor: $actor instanceof User ? $actor : null,
                    );

                    $recipients += $result['recipients'];
                    $copies += $result['copies'];
                }

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano checklisty proboszczow.')
                    ->body("Odbiorcy: {$recipients} · kopie: {$copies}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function sendInstantEmailBulkAction(): BulkAction
    {
        return BulkAction::make('send_parish_email_bulk')
            ->label('Wyslij email')
            ->icon('heroicon-o-envelope')
            ->color('primary')
            ->schema([
                Select::make('audience')
                    ->label('Odbiorcy')
                    ->options([
                        'parishioners' => 'Parafianie',
                        'admins' => 'Administratorzy',
                        'all' => 'Parafianie + administratorzy',
                    ])
                    ->default('parishioners')
                    ->required(),
                TextInput::make('subject')
                    ->label('Temat')
                    ->required()
                    ->maxLength(200)
                    ->default('Wiadomosc do zaznaczonych parafii'),
                Textarea::make('body')
                    ->label('Tresc')
                    ->required()
                    ->rows(8)
                    ->maxLength(12000),
            ])
            ->action(function (
                $records,
                array $data,
                InstantCommunicationService $service,
                ParishAudienceResolver $audiences,
            ): void {
                $actor = Filament::auth()->user();
                $users = self::resolveBulkParishRecipients($records, (string) $data['audience'], $audiences);
                $result = $service->sendEmailToUsers(
                    users: $users,
                    subjectLine: (string) $data['subject'],
                    messageBody: (string) $data['body'],
                    actor: $actor instanceof User ? $actor : null,
                );

                Notification::make()
                    ->success()
                    ->title('Zakolejkowano email dla zaznaczonych parafii.')
                    ->body("Odbiorcy: {$result['users']} · queued: {$result['queued']} · skipped: {$result['skipped']}")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return \Illuminate\Support\Collection<int,User>
     */
    protected static function resolveParishRecipients(Parish $record, string $audience, ParishAudienceResolver $audiences)
    {
        $query = User::query()
            ->with('devices')
            ->where('status', 'active');

        return match ($audience) {
            'admins' => $query
                ->whereIn('role', [1, 2])
                ->where(function ($inner) use ($record): void {
                    $inner->where('home_parish_id', $record->getKey())
                        ->orWhereHas('managedParishes', fn ($managed) => $managed->where('parishes.id', $record->getKey()));
                })
                ->get(),
            'all' => $query
                ->where(function ($inner) use ($record): void {
                    $inner->where('home_parish_id', $record->getKey())
                        ->orWhereHas('managedParishes', fn ($managed) => $managed->where('parishes.id', $record->getKey()));
                })
                ->get(),
            default => $audiences->homeParishUsers((int) $record->getKey(), withDevices: true),
        };
    }

    /**
     * @param  iterable<int,mixed>  $records
     * @return \Illuminate\Support\Collection<int,User>
     */
    protected static function resolveBulkParishRecipients(iterable $records, string $audience, ParishAudienceResolver $audiences)
    {
        $parishIds = collect($records)
            ->filter(fn ($record): bool => $record instanceof Parish)
            ->map(fn (Parish $record): int => (int) $record->getKey())
            ->unique()
            ->values();

        if ($parishIds->isEmpty()) {
            return collect();
        }

        $query = User::query()
            ->with('devices')
            ->where('status', 'active');

        return match ($audience) {
            'admins' => $query
                ->whereIn('role', [1, 2])
                ->where(function ($inner) use ($parishIds): void {
                    $inner->whereIn('home_parish_id', $parishIds->all())
                        ->orWhereHas('managedParishes', fn ($managed) => $managed->whereIn('parishes.id', $parishIds->all()));
                })
                ->get(),
            'all' => $query
                ->where(function ($inner) use ($parishIds): void {
                    $inner->whereIn('home_parish_id', $parishIds->all())
                        ->orWhereHas('managedParishes', fn ($managed) => $managed->whereIn('parishes.id', $parishIds->all()));
                })
                ->get(),
            default => $audiences->homeParishUsers($parishIds, withDevices: true),
        };
    }
}
