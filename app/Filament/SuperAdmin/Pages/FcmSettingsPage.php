<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\PushDelivery;
use App\Models\User;
use App\Models\UserDevice;
use App\Settings\FcmSettings;
use App\Support\Push\PushDispatchService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FcmSettingsPage extends SettingsPage
{
    protected static string $settings = FcmSettings::class;

    protected static ?string $title = 'FCM i push';

    protected static ?string $navigationLabel = 'FCM i push';

    protected static ?string $slug = 'fcm-push';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string | \UnitEnum | null $navigationGroup = 'Komunikacja';

    protected static ?int $navigationSort = 2;

    protected ?string $subheading = 'Globalna konfiguracja Firebase Cloud Messaging, testy wysylki i kontrola stanu push.';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stan integracji')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('active_devices')
                            ->label('Aktywne urzadzenia')
                            ->content(fn (): string => (string) UserDevice::query()->pushable()->count()),
                        Placeholder::make('sent_24h')
                            ->label('Push sent 24h')
                            ->content(fn (): string => (string) PushDelivery::query()
                                ->where('status', PushDelivery::STATUS_SENT)
                                ->where('created_at', '>=', now()->subDay())
                                ->count()),
                        Placeholder::make('failed_24h')
                            ->label('Push failed 24h')
                            ->content(fn (): string => (string) PushDelivery::query()
                                ->where('status', PushDelivery::STATUS_FAILED)
                                ->where('created_at', '>=', now()->subDay())
                                ->count()),
                    ]),

                Section::make('Konfiguracja FCM')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('FCM aktywne')
                            ->inline(false),

                        TextInput::make('project_id')
                            ->label('Firebase project_id')
                            ->helperText('Mozesz zostawic puste, jesli project_id jest juz w service account JSON.')
                            ->maxLength(255),

                        TextInput::make('request_timeout_seconds')
                            ->label('Timeout requestu')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(60)
                            ->required(),

                        Textarea::make('service_account_json')
                            ->label('Service account JSON')
                            ->rows(18)
                            ->columnSpanFull()
                            ->helperText('Wklej caly JSON konta serwisowego Firebase / Google Cloud. Pole jest szyfrowane przez Spatie Settings.')
                            ->required(fn (callable $get): bool => (bool) $get('enabled')),
                    ]),

                Section::make('Strategia dostarczania')
                    ->columns(2)
                    ->schema([
                        TextInput::make('android_ttl_seconds')
                            ->label('Android TTL (sekundy)')
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(604800)
                            ->required(),

                        TextInput::make('ios_ttl_seconds')
                            ->label('iOS TTL (sekundy)')
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(604800)
                            ->required(),

                        Toggle::make('news_collapsible')
                            ->label('News: collapse')
                            ->inline(false),

                        Toggle::make('announcements_collapsible')
                            ->label('Ogloszenia: collapse')
                            ->inline(false),

                        Toggle::make('office_messages_collapsible')
                            ->label('Kancelaria: collapse')
                            ->inline(false),

                        Toggle::make('parish_approval_collapsible')
                            ->label('Zatwierdzenie parafii: collapse')
                            ->inline(false),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $serviceAccountJson = trim((string) ($data['service_account_json'] ?? ''));

        if ($serviceAccountJson !== '') {
            $decoded = json_decode($serviceAccountJson, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'data.service_account_json' => 'Service account JSON musi byc poprawnym JSON-em.',
                ]);
            }
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_test_push')
                ->label('Wyslij test push')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->schema([
                    Select::make('device_id')
                        ->label('Urzadzenie')
                        ->searchable()
                        ->preload()
                        ->options($this->deviceOptions()),

                    TextInput::make('manual_token')
                        ->label('FCM token reczny')
                        ->maxLength(4096)
                        ->helperText('Uzyj tylko wtedy, gdy nie wybierasz urzadzenia z listy.'),

                    Select::make('platform')
                        ->label('Platforma')
                        ->options([
                            'android' => 'Android',
                            'ios' => 'iOS',
                        ])
                        ->default('android')
                        ->required(),

                    Select::make('type')
                        ->label('Typ')
                        ->options([
                            'NEWS_CREATED' => 'NEWS_CREATED',
                            'ANNOUNCEMENTS_PACKAGE_PUBLISHED' => 'ANNOUNCEMENTS_PACKAGE_PUBLISHED',
                            'OFFICE_MESSAGE_RECEIVED' => 'OFFICE_MESSAGE_RECEIVED',
                            'PARISH_APPROVAL_STATUS_CHANGED' => 'PARISH_APPROVAL_STATUS_CHANGED',
                            'TEST_MESSAGE' => 'TEST_MESSAGE',
                        ])
                        ->default('TEST_MESSAGE')
                        ->required(),

                    TextInput::make('title')
                        ->label('Tytul')
                        ->required()
                        ->maxLength(120)
                        ->default('Test push z panelu superadmina'),

                    Textarea::make('body')
                        ->label('Tresc')
                        ->rows(4)
                        ->required()
                        ->default('Jesli widzisz ta wiadomosc, FCM dziala poprawnie.'),

                    Textarea::make('data_json')
                        ->label('Data (JSON)')
                        ->rows(6)
                        ->default("{\n  \"source\": \"superadmin\",\n  \"debug\": true\n}"),
                ])
                ->action(function (array $data, PushDispatchService $dispatcher): void {
                    $deviceId = $data['device_id'] ?? null;
                    $manualToken = trim((string) ($data['manual_token'] ?? ''));
                    $platform = (string) ($data['platform'] ?? 'android');

                    if (! $deviceId && $manualToken === '') {
                        throw ValidationException::withMessages([
                            'manual_token' => 'Wybierz urzadzenie albo podaj reczny token.',
                        ]);
                    }

                    $routingData = [];
                    $dataJson = trim((string) ($data['data_json'] ?? ''));

                    if ($dataJson !== '') {
                        $decoded = json_decode($dataJson, true);

                        if (! is_array($decoded)) {
                            throw ValidationException::withMessages([
                                'data_json' => 'Pole data musi byc poprawnym JSON-em.',
                            ]);
                        }

                        $routingData = Arr::dot($decoded);
                    }

                    $device = null;
                    $user = null;
                    $token = $manualToken;

                    if ($deviceId) {
                        $device = UserDevice::query()->with('user')->find($deviceId);

                        if (! $device instanceof UserDevice) {
                            throw ValidationException::withMessages([
                                'device_id' => 'Nie znaleziono urzadzenia.',
                            ]);
                        }

                        $user = $device->user;
                        $token = (string) $device->push_token;
                        $platform = (string) $device->platform;
                    }

                    $delivery = $dispatcher->sendTestPush(
                        token: $token,
                        platform: $platform,
                        title: (string) $data['title'],
                        body: (string) $data['body'],
                        type: (string) $data['type'],
                        routingData: $routingData,
                        device: $device,
                        user: $user instanceof User ? $user : null,
                    );

                    Notification::make()
                        ->title($delivery->status === PushDelivery::STATUS_SENT ? 'Test push wyslany.' : 'Test push nieudany.')
                        ->body($delivery->status === PushDelivery::STATUS_SENT
                            ? 'Sprawdz zasob Dostarczenia push po szczegoly odpowiedzi FCM.'
                            : ($delivery->error_message ?: 'Szczegoly sa zapisane w logu dostarczenia.'))
                        ->color($delivery->status === PushDelivery::STATUS_SENT ? 'success' : 'danger')
                        ->send();
                }),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->exists()
            ? 'danger'
            : 'success';
    }

    /**
     * @return array<int|string,string>
     */
    private function deviceOptions(): array
    {
        return UserDevice::query()
            ->with('user')
            ->pushable()
            ->orderByDesc('last_seen_at')
            ->limit(300)
            ->get()
            ->mapWithKeys(fn (UserDevice $device): array => [
                $device->getKey() => sprintf(
                    '#%s %s · %s · %s',
                    $device->getKey(),
                    $device->user?->email ?? 'brak usera',
                    strtoupper((string) $device->platform),
                    $device->device_name ?: $device->device_id,
                ),
            ])
            ->all();
    }
}
