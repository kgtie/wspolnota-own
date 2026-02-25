<?php

namespace App\Filament\Admin\Pages;

use App\Models\Parish;
use App\Settings\ParishSettings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EditParishProfile extends EditTenantProfile
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    public static function getLabel(): string
    {
        return 'Zarządzanie parafią';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('parish-settings')
                    ->persistTabInQueryString('tab')
                    ->tabs([
                        $this->basicInfoTab(),
                        $this->contactAndAddressTab(),
                        $this->mediaTab(),
                        $this->notificationsTab(),
                        $this->appSettingsTab(),
                    ]),
            ]);
    }

    // =========================================
    // TAB 1: DANE PODSTAWOWE
    // =========================================

    protected function basicInfoTab(): Tab
    {
        return Tab::make('Dane podstawowe')
            ->icon('heroicon-o-building-library')
            ->schema([
                Section::make('Status parafii')
                    ->description('Informacje o statusie i subskrypcji parafii.')
                    ->icon('heroicon-o-information-circle')
                    ->aside()
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->state(fn (Parish $record): string => $record->is_active ? 'Aktywna' : 'Nieaktywna')
                            ->color(fn (Parish $record): string => $record->is_active ? 'success' : 'danger'),

                        TextEntry::make('activated_at')
                            ->label('Data aktywacji')
                            ->date('d.m.Y')
                            ->placeholder('Brak daty'),

                        TextEntry::make('expiration_date')
                            ->label('Ważność subskrypcji')
                            ->date('d.m.Y')
                            ->placeholder('Bezterminowo')
                            ->color(fn (Parish $record): string => match (true) {
                                $record->expiration_date === null => 'gray',
                                $record->expiration_date->isPast() => 'danger',
                                $record->expiration_date->diffInDays(now()) <= 30 => 'warning',
                                default => 'success',
                            }),

                        TextEntry::make('subscription_fee')
                            ->label('Opłata subskrypcyjna')
                            ->money('PLN')
                            ->placeholder('Brak'),

                        TextEntry::make('created_at')
                            ->label('W systemie od')
                            ->since(),

                        TextEntry::make('parishioners_count')
                            ->label('Liczba parafian')
                            ->state(fn (Parish $record): int => $record->parishioners()->count()),

                        TextEntry::make('verified_parishioners_count')
                            ->label('Zatwierdzonych')
                            ->state(fn (Parish $record): int => $record->verifiedParishioners()->count())
                            ->color('success'),

                        TextEntry::make('pending_count')
                            ->label('Oczekujących')
                            ->state(fn (Parish $record): int => $record->parishioners()
                                ->where('is_user_verified', false)
                                ->whereNotNull('email_verified_at')
                                ->count())
                            ->color(fn ($state): string => $state > 0 ? 'warning' : 'gray'),

                        TextEntry::make('admins_list')
                            ->label('Administratorzy')
                            ->state(fn (Parish $record): string => DB::table('parish_user')
                                ->join('users', 'users.id', '=', 'parish_user.user_id')
                                ->where('parish_user.parish_id', $record->getKey())
                                ->where('parish_user.is_active', true)
                                ->whereNull('users.deleted_at')
                                ->orderBy('users.full_name')
                                ->orderBy('users.name')
                                ->selectRaw("COALESCE(NULLIF(users.full_name, ''), NULLIF(users.name, ''), users.email) as admin_label")
                                ->pluck('admin_label')
                                ->implode(', ') ?: 'Brak')
                            ->color('primary'),
                    ]),

                Section::make('Identyfikacja parafii')
                    ->description('Oficjalna nazwa, skrót i adres URL.')
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Pełna nazwa parafii')
                            ->placeholder('np. Parafia p.w. św. Stanisława biskupa i męczennika w Wiskitkach')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('short_name')
                            ->label('Krótka nazwa')
                            ->placeholder('np. Parafia Wiskitki')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('slug')
                            ->label('Adres URL (slug)')
                            ->prefix('/app/')
                            ->required()
                            ->maxLength(100)
                            ->unique(Parish::class, 'slug', ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('Zmiana slugu zmieni adres URL parafii w aplikacji.')
                            ->dehydrateStateUsing(fn (string $state): string => Str::lower($state)),
                    ]),

                Section::make('Przynależność kościelna')
                    ->description('Diecezja i dekanat.')
                    ->icon('heroicon-o-building-office')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('diocese')
                            ->label('Diecezja')
                            ->placeholder('np. Diecezja Łowicka')
                            ->maxLength(255),

                        TextInput::make('decanate')
                            ->label('Dekanat')
                            ->placeholder('np. Dekanat Wiskitki')
                            ->maxLength(255),
                    ]),
            ]);
    }

    // =========================================
    // TAB 2: KONTAKT I ADRES
    // =========================================

    protected function contactAndAddressTab(): Tab
    {
        return Tab::make('Kontakt i adres')
            ->icon('heroicon-o-map-pin')
            ->schema([
                Section::make('Dane kontaktowe')
                    ->description('Telefon, email i strona internetowa parafii.')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email parafii')
                            ->email()
                            ->placeholder('parafia@example.pl')
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->placeholder('+48 123 456 789')
                            ->maxLength(20),

                        TextInput::make('website')
                            ->label('Strona internetowa')
                            ->url()
                            ->placeholder('https://www.parafia-wiskitki.pl')
                            ->maxLength(255)
                            ->suffixIcon('heroicon-m-globe-alt'),
                    ]),

                Section::make('Adres')
                    ->description('Adres fizyczny parafii.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(3)
                    ->schema([
                        TextInput::make('street')
                            ->label('Ulica i numer')
                            ->placeholder('ul. Kościelna 1')
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('postal_code')
                            ->label('Kod pocztowy')
                            ->placeholder('96-315')
                            ->mask('99-999')
                            ->maxLength(10),

                        TextInput::make('city')
                            ->label('Miejscowość')
                            ->placeholder('Wiskitki')
                            ->required()
                            ->maxLength(255),
                    ]),
            ]);
    }

    // =========================================
    // TAB 3: MEDIA (GRAFIKI)
    // =========================================

    protected function mediaTab(): Tab
    {
        return Tab::make('Grafiki')
            ->icon('heroicon-o-photo')
            ->schema([
                Section::make('Logo i zdjęcia parafii')
                    ->description('Grafiki wyświetlane w aplikacji. Zalecane formaty: JPG, PNG lub WebP.')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('parish_avatar')
                            ->label('Logo / avatar parafii')
                            ->collection('avatar')
                            // Usunięto ->disk('profiles') oraz ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->circleCropper()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Kwadratowe zdjęcie, min. 150×150 px. Maks. 2 MB.')
                            ->columnSpan(1),

                        SpatieMediaLibraryFileUpload::make('parish_cover')
                            ->label('Zdjęcie w tle (cover)')
                            ->collection('cover')
                            // Usunięto ->disk('profiles') oraz ->visibility('public')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Szerokie zdjęcie, zalecane 1200×400 px. Maks. 5 MB.')
                            ->columnSpan(1),
                    ]),
            ]);
    }

    // =========================================
    // TAB 4: POWIADOMIENIA
    // =========================================

    protected function notificationsTab(): Tab
    {
        return Tab::make('Powiadomienia')
            ->icon('heroicon-o-bell-alert')
            ->schema([
                Section::make('Powiadomienia push')
                    ->description('Ustawienia powiadomień wysyłanych do parafian.')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ps_notifications_enabled')
                            ->label('Powiadomienia włączone')
                            ->helperText('Główny przełącznik powiadomień push dla tej parafii.')
                            ->columnSpanFull(),

                        TextInput::make('ps_mass_reminder_hours_before')
                            ->label('Przypomnienie o mszy (godziny przed)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(24)
                            ->suffix('godz.')
                            ->helperText('Na ile godzin przed mszą wysłać push do zapisanych parafian.'),

                        Toggle::make('ps_announcements_push_on_publish')
                            ->label('Push po publikacji ogłoszeń')
                            ->helperText('Wyślij powiadomienie do parafian po opublikowaniu nowych ogłoszeń.'),
                    ]),

                Section::make('Cotygodniowe przypomnienie')
                    ->description('Automatyczny email przypominający o uzupełnieniu mszy i ogłoszeń.')
                    ->icon('heroicon-o-calendar-days')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Toggle::make('ps_weekly_reminder_enabled')
                            ->label('Włącz cotygodniowe przypomnienie')
                            ->columnSpanFull(),

                        Select::make('ps_weekly_reminder_day')
                            ->label('Dzień wysyłki')
                            ->options([
                                'monday' => 'Poniedziałek',
                                'tuesday' => 'Wtorek',
                                'wednesday' => 'Środa',
                                'thursday' => 'Czwartek',
                                'friday' => 'Piątek',
                                'saturday' => 'Sobota',
                                'sunday' => 'Niedziela',
                            ]),

                        TextInput::make('ps_weekly_reminder_hour')
                            ->label('Godzina wysyłki')
                            ->placeholder('17:00')
                            ->maxLength(5),
                    ]),
            ]);
    }

    // =========================================
    // TAB 5: USTAWIENIA APLIKACJI
    // =========================================

    protected function appSettingsTab(): Tab
    {
        return Tab::make('Ustawienia aplikacji')
            ->icon('heroicon-o-cog-6-tooth')
            ->schema([
                Section::make('Aktualności i komentarze')
                    ->description('Jak działają aktualności i komentarze w aplikacji.')
                    ->icon('heroicon-o-newspaper')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ps_news_comments_enabled')
                            ->label('Komentarze pod aktualnościami')
                            ->helperText('Pozwól parafianom komentować wpisy.'),

                        Toggle::make('ps_news_comments_require_verification')
                            ->label('Tylko zatwierdzeni mogą komentować')
                            ->helperText('Wymaga zatwierdzenia konta przez proboszcza.'),
                    ]),

                Section::make('Ogłoszenia')
                    ->description('Ustawienia związane z ogłoszeniami mszalnymi.')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        Toggle::make('ps_announcements_ai_summary')
                            ->label('Streszczenie AI')
                            ->helperText('Automatycznie generuj streszczenie ogłoszeń za pomocą AI.'),
                    ]),

                Section::make('Kancelaria online')
                    ->description('Ustawienia modułu kancelarii.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ps_office_enabled')
                            ->label('Kancelaria włączona')
                            ->helperText('Udostępnij moduł kancelarii online parafianom.'),

                        Toggle::make('ps_office_file_upload_enabled')
                            ->label('Wysyłanie plików w kancelarii')
                            ->helperText('Pozwól na wymianę plików w rozmowach kancelarii.'),
                    ]),

                Section::make('Wygląd aplikacji')
                    ->description('Personalizacja wyglądu aplikacji dla parafian.')
                    ->icon('heroicon-o-paint-brush')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        ColorPicker::make('ps_primary_color')
                            ->label('Kolor główny'),

                        Select::make('ps_theme')
                            ->label('Motyw')
                            ->options([
                                'light' => 'Jasny',
                                'dark' => 'Ciemny',
                            ]),
                    ]),
            ]);
    }

    // =========================================
    // SETTINGS: prefix i mapowanie
    // =========================================

    private const SETTINGS_PREFIX = 'ps_';

    private function settingsKeys(): array
    {
        return array_keys(ParishSettings::defaults());
    }

    // =========================================
    // ŁADOWANIE DANYCH → FORMULARZ
    // =========================================

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $settings = ParishSettings::resolve($data['settings'] ?? null);

        foreach ($settings as $key => $value) {
            $data[self::SETTINGS_PREFIX . $key] = $value;
        }

        unset($data['settings']);

        return $data;
    }

    // =========================================
    // ZAPIS DANYCH → BAZA
    // =========================================

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $existingSettings = $record->settings ?? [];
        $newSettings = [];

        foreach ($this->settingsKeys() as $settingKey) {
            $formKey = self::SETTINGS_PREFIX . $settingKey;

            if (array_key_exists($formKey, $data)) {
                $newSettings[$settingKey] = $data[$formKey];
                unset($data[$formKey]);
            }
        }

        if (! empty($newSettings)) {
            $data['settings'] = array_merge($existingSettings, $newSettings);
        }

        $record->update($data);

        return $record;
    }

    // =========================================
    // POWIADOMIENIE PO ZAPISIE
    // =========================================

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Zapisano')
            ->body('Dane parafii zostały zaktualizowane.');
    }
}
