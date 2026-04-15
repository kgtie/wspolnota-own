<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Resources\PushDeliveries\PushDeliveryResource;
use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
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

/**
 * Globalna konfiguracja FCM i diagnostyka push.
 *
 * Strona łączy ustawienia integracji Firebase z szybkimi metrykami oraz
 * ręcznym testem push, żeby superadmin mógł obsłużyć cały obszar
 * mobilnych notyfikacji z jednego miejsca.
 */
class FcmSettingsPage extends SettingsPage
{
    protected static string $settings = FcmSettings::class;

    protected static ?string $title = 'FCM i push';

    protected static ?string $navigationLabel = 'FCM i push';

    protected static ?string $slug = 'fcm-push';

    protected static string | \BackedEnum | null $navigationIcon = null;

    protected static string | \UnitEnum | null $navigationGroup = 'Push i urządzenia';

    protected static ?int $navigationSort = 2;

    protected ?string $subheading = 'Globalna konfiguracja Firebase Cloud Messaging, testy wysyłki i kontrola stanu push.';

    public static function getNavigationBadge(): ?string
    {
        $failed24h = PushDelivery::query()
            ->where('status', PushDelivery::STATUS_FAILED)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($failed24h > 0) {
            return (string) $failed24h;
        }

        $devices = UserDevice::query()->pushable()->count();

        return $devices > 0 ? (string) $devices : null;
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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Stan integracji')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('active_devices')
                            ->label('Aktywne urządzenia')
                            ->content(fn (): string => (string) UserDevice::query()->pushable()->count()),
                        Placeholder::make('saved_preferences')
                            ->label('Użytkownicy ze zgodami')
                            ->content(fn (): string => (string) User::query()->whereHas('notificationPreference')->count()),
                        Placeholder::make('missing_preferences')
                            ->label('Brak zapisanych zgod')
                            ->content(fn (): string => (string) User::query()->whereDoesntHave('notificationPreference')->count()),
                        Placeholder::make('sent_24h')
                            ->label('Push wysłane w 24 h')
                            ->content(fn (): string => (string) PushDelivery::query()
                                ->where('status', PushDelivery::STATUS_SENT)
                                ->where('created_at', '>=', now()->subDay())
                                ->count()),
                        Placeholder::make('failed_24h')
                            ->label('Błędne push w 24 h')
                            ->content(fn (): string => (string) PushDelivery::query()
                                ->where('status', PushDelivery::STATUS_FAILED)
                                ->where('created_at', '>=', now()->subDay())
                                ->count()),
                        Placeholder::make('permission_status_mix')
                            ->label('Status uprawnień')
                            ->content(fn (): string => sprintf(
                                'autoryzowane: %d · tymczasowe: %d · odrzucone: %d · nieustalone: %d',
                                UserDevice::query()->where('permission_status', 'authorized')->count(),
                                UserDevice::query()->where('permission_status', 'provisional')->count(),
                                UserDevice::query()->where('permission_status', 'denied')->count(),
                                UserDevice::query()->where('permission_status', 'not_determined')->orWhereNull('permission_status')->count(),
                            )),
                    ]),

                Section::make('Konfiguracja FCM')
                    ->columns(2)
                    ->schema([
                        Toggle::make('enabled')
                            ->label('FCM aktywne')
                            ->inline(false),

                        TextInput::make('project_id')
                            ->label('Firebase project_id')
                            ->helperText('Możesz zostawić puste, jeśli `project_id` jest już w JSON-ie konta usługi.')
                            ->maxLength(255),

                        TextInput::make('request_timeout_seconds')
                            ->label('Limit czasu żądania')
                            ->numeric()
                            ->minValue(2)
                            ->maxValue(60)
                            ->required(),

                        Textarea::make('service_account_json')
                            ->label('Service account JSON')
                            ->rows(18)
                            ->columnSpanFull()
                            ->helperText('Wklej cały JSON konta usługi Firebase / Google Cloud. Pole jest szyfrowane przez Spatie Settings.')
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
                            ->label('Ogłoszenia: grupowanie')
                            ->inline(false),

                        Toggle::make('office_messages_collapsible')
                            ->label('Kancelaria: collapse')
                            ->inline(false),

                        Toggle::make('parish_approval_collapsible')
                            ->label('Zatwierdzenie parafii: grupowanie')
                            ->inline(false),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $serviceAccountJson = trim((string) ($data['service_account_json'] ?? ''));

        // Walidujemy JSON przed zapisem, bo błąd w tym polu unieruchamia cały mechanizm wysyłki FCM.
        if ($serviceAccountJson !== '') {
            $decoded = json_decode($serviceAccountJson, true);

            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'data.service_account_json' => 'JSON konta usługi musi być poprawnym JSON-em.',
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
                            'MASS_PENDING' => 'MASS_PENDING',
                            'OFFICE_MESSAGE_RECEIVED' => 'OFFICE_MESSAGE_RECEIVED',
                            'PARISH_APPROVAL_STATUS_CHANGED' => 'PARISH_APPROVAL_STATUS_CHANGED',
                            'TEST_MESSAGE' => 'TEST_MESSAGE',
                        ])
                        ->default('TEST_MESSAGE')
                        ->required(),

                    TextInput::make('title')
                        ->label('Tytuł')
                        ->required()
                        ->maxLength(120)
                        ->default('Test push z panelu superadmina'),

                    Textarea::make('body')
                        ->label('Tresc')
                        ->rows(4)
                        ->required()
                        ->default('Jeśli widzisz tę wiadomość, FCM działa poprawnie.'),

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
            Action::make('open_user_devices')
                ->label('Urzadzenia push')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('gray')
                ->url(UserDeviceResource::getUrl()),
            Action::make('open_push_deliveries')
                ->label('Dostarczenia push')
                ->icon('heroicon-o-inbox-stack')
                ->color('gray')
                ->url(PushDeliveryResource::getUrl()),
            Action::make('open_dispatch_center')
                ->label('Centrum dispatchu')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->url(NotificationDispatchCenter::getUrl()),
            Action::make('open_failed_jobs')
                ->label('Failed jobs')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('gray')
                ->url(FailedJobsCenter::getUrl()),
        ];
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
                    $device->user?->email ?? 'brak użytkownika',
                    strtoupper((string) $device->platform),
                    $device->device_name ?: $device->device_id,
                ),
            ])
            ->all();
    }
}
