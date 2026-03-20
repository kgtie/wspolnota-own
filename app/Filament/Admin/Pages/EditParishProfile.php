<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\Admin\Resources\Masses\MassResource;
use App\Filament\Admin\Resources\NewsPosts\NewsPostResource;
use App\Models\Parish;
use App\Settings\ParishSettings;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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
use Illuminate\Support\HtmlString;

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
                        $this->servicesStatusTab(),
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
                            ->maxLength(100)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Slug jest ustalany przy wdrożeniu parafii i nie podlega edycji z poziomu panelu proboszcza.'),
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

                Section::make('Osoby w parafii')
                    ->description('Lista osób widocznych publicznie przy parafii. Nie musi być powiązana z kontami użytkowników.')
                    ->icon('heroicon-o-user-group')
                    ->schema([
                        Repeater::make('ps_staff_members')
                            ->label('Skład duszpasterski')
                            ->default([])
                            ->addActionLabel('Dodaj osobę')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                $name = trim((string) ($state['name'] ?? ''));
                                $title = trim((string) ($state['title'] ?? ''));

                                if ($name === '' && $title === '') {
                                    return null;
                                }

                                if ($name !== '' && $title !== '') {
                                    return "{$name} - {$title}";
                                }

                                return $name !== '' ? $name : $title;
                            })
                            ->schema([
                                TextInput::make('name')
                                    ->label('Imię i nazwisko')
                                    ->placeholder('np. ks. Piotr Nowak')
                                    ->required()
                                    ->maxLength(120),

                                TextInput::make('title')
                                    ->label('Tytuł / rola')
                                    ->placeholder('np. wikariusz')
                                    ->required()
                                    ->maxLength(120),
                            ])
                            ->columns(2),
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
                    ->description('Te dane są wymagane dla pełnego profilu parafii. Widoczność publiczną ustawisz osobno poniżej.')
                    ->icon('heroicon-o-phone')
                    ->columns(3)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email parafii')
                            ->email()
                            ->placeholder('parafia@example.pl')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Telefon')
                            ->tel()
                            ->placeholder('+48 123 456 789')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('website')
                            ->label('Strona internetowa')
                            ->url()
                            ->placeholder('https://www.parafia-wiskitki.pl')
                            ->required()
                            ->maxLength(255)
                            ->suffixIcon('heroicon-m-globe-alt'),
                    ]),

                Section::make('Widoczność danych na stronie publicznej')
                    ->description('Każdy element możesz udostępnić publicznie albo zostawić wyłącznie do użytku wewnętrznego.')
                    ->icon('heroicon-o-eye')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ps_public_email')
                            ->label('Email publiczny')
                            ->default(true)
                            ->helperText('Pozwala pokazywać adres email na publicznej stronie parafii.'),

                        Toggle::make('ps_public_phone')
                            ->label('Telefon publiczny')
                            ->default(true)
                            ->helperText('Pozwala pokazywać numer telefonu na publicznej stronie parafii.'),

                        Toggle::make('ps_public_website')
                            ->label('Strona WWW publiczna')
                            ->default(true)
                            ->helperText('Pozwala pokazywać link do zewnętrznej strony internetowej parafii.'),

                        Toggle::make('ps_public_address')
                            ->label('Adres publiczny')
                            ->default(true)
                            ->helperText('Pozwala pokazywać ulicę i kod pocztowy parafii na stronie publicznej.'),
                    ]),

                Section::make('Adres')
                    ->description('Adres fizyczny parafii. Dane są wymagane, ale ich publikację kontroluje przełącznik powyżej.')
                    ->icon('heroicon-o-map-pin')
                    ->columns(3)
                    ->schema([
                        TextInput::make('street')
                            ->label('Ulica i numer')
                            ->placeholder('ul. Kościelna 1')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('postal_code')
                            ->label('Kod pocztowy')
                            ->placeholder('96-315')
                            ->mask('99-999')
                            ->required()
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
    // TAB 4: STAN USŁUG
    // =========================================

    protected function servicesStatusTab(): Tab
    {
        return Tab::make('Stan usług')
            ->icon('heroicon-o-bolt')
            ->schema([
                Section::make('Jak działa usługa dla tej parafii')
                    ->description('To jest ekran informacyjny. Konkretne działania wykonujesz w odpowiednich modułach panelu.')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Placeholder::make('service_status_intro')
                            ->hiddenLabel()
                            ->content(fn (): HtmlString => new HtmlString(
                                '<div class="rounded-2xl border border-primary-200/70 bg-primary-50/70 px-4 py-4 text-sm leading-7 text-gray-700">'.
                                '<p class="font-semibold text-gray-950">Usługa Wspólnota działa tutaj warstwowo.</p>'.
                                '<p class="mt-2">Publikacje, przypomnienia i automaty uruchamiają się z harmonogramu systemowego, a proboszcz zarządza treściami bezpośrednio w modułach: ogłoszenia, msze, aktualności i kancelaria online.</p>'.
                                '</div>'
                            )),
                    ]),

                Section::make('Automatyczny harmonogram')
                    ->description('Najważniejsze procesy zaplanowane dla parafii oraz ich rzeczywiste godziny działania.')
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('service_push_and_email')
                            ->label('Push i email do parafian')
                            ->content(fn (): HtmlString => $this->renderBulletList([
                                'Przypomnienia o zapisanych mszach wychodzą automatycznie 24h, 8h i 1h przed celebracją.',
                                'Poranny email z dzisiejszymi mszami wychodzi codziennie o 05:00.',
                                'Po publikacji ogłoszeń i aktualności system czeka około 1 godziny, a następnie uruchamia powiadomienia push i email.',
                            ])),

                        Placeholder::make('service_editorial_schedule')
                            ->label('Publikacje i automaty redakcyjne')
                            ->content(function (): HtmlString {
                                $record = $this->tenant;

                                return $this->renderBulletList([
                                    'Zaplanowane aktualności publikują się automatycznie co 5 minut.',
                                    'Streszczenia AI dla opublikowanych ogłoszeń bez podsumowania są generowane codziennie o 00:07.'
                                        .($record->getSetting('announcements_ai_summary', true) ? '' : ' W tej parafii automatyczne streszczenia AI są obecnie wyłączone.'),
                                    'Cotygodniowa checklista dla proboszcza jest wysyłana w sobotę o 12:00.',
                                ]);
                            }),
                    ]),

                Section::make('Bieżący stan funkcji')
                    ->description('Najważniejsze przełączniki działania usługi dla tej parafii, bez warstwy edycji.')
                    ->icon('heroicon-o-signal')
                    ->columns(2)
                    ->schema([
                        Placeholder::make('service_feature_flags')
                            ->label('Aktywne moduły')
                            ->content(function (): HtmlString {
                                $record = $this->tenant;

                                return $this->renderBulletList([
                                    'Komentarze pod aktualnościami: '.$this->formatStatusLabel((bool) $record->getSetting('news_comments_enabled', true), 'aktywne', 'wyłączone').'.',
                                    'Komentowanie tylko przez zatwierdzonych parafian: '.$this->formatStatusLabel((bool) $record->getSetting('news_comments_require_verification', true), 'tak', 'nie').'.',
                                    'Kancelaria online: '.$this->formatStatusLabel((bool) $record->getSetting('office_enabled', true), 'aktywna', 'wyłączona').'.',
                                    'Załączniki w kancelarii: '.$this->formatStatusLabel((bool) $record->getSetting('office_file_upload_enabled', true), 'aktywne', 'wyłączone').'.',
                                ]);
                            }),

                        Placeholder::make('service_where_to_manage')
                            ->label('Gdzie zarządzać poszczególnymi obszarami')
                            ->content(fn (): HtmlString => new HtmlString(
                                '<div class="space-y-3 text-sm leading-7">'.
                                $this->renderAdminLinkRow(
                                    AnnouncementSetResource::getUrl('index'),
                                    'Ogłoszenia duszpasterskie',
                                    'tutaj przygotowujesz, publikujesz i poprawiasz zestawy ogłoszeń.'
                                ).
                                $this->renderAdminLinkRow(
                                    MassResource::getUrl('index'),
                                    'Msze święte',
                                    'tutaj uzupełniasz kalendarz celebracji i intencji.'
                                ).
                                $this->renderAdminLinkRow(
                                    NewsPostResource::getUrl('index'),
                                    'Aktualności parafii',
                                    'tutaj publikujesz wpisy, planujesz publikację i zarządzasz komentarzami.'
                                ).
                                $this->renderAdminLinkRow(
                                    OfficeInbox::getUrl(),
                                    'Kancelaria online',
                                    'tutaj odpowiadasz parafianom i prowadzisz rozmowy kancelaryjne.'
                                ).
                                '</div>'
                            )),
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
                $newSettings[$settingKey] = $settingKey === 'staff_members'
                    ? ParishSettings::normalizeStaffMembers($data[$formKey])
                    : $data[$formKey];
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

    private function renderBulletList(array $items): HtmlString
    {
        $itemsHtml = collect($items)
            ->map(function (string $item): string {
                return '<li class="flex items-start gap-3">'.
                    '<span class="mt-2 h-2 w-2 shrink-0 rounded-full bg-primary-500"></span>'.
                    '<span>'.e($item).'</span>'.
                    '</li>';
            })
            ->implode('');

        return new HtmlString('<ul class="space-y-3 text-sm leading-7 text-gray-700">'.$itemsHtml.'</ul>');
    }

    private function formatStatusLabel(bool $enabled, string $enabledLabel, string $disabledLabel): string
    {
        return $enabled ? $enabledLabel : $disabledLabel;
    }

    private function renderAdminLinkRow(string $url, string $label, string $description): string
    {
        return '<div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">'.
            '<a href="'.e($url).'" class="font-semibold text-primary-700 hover:text-primary-600">'.e($label).'</a>'.
            '<p class="mt-1 text-gray-600">'.e(ucfirst($description)).'</p>'.
            '</div>';
    }
}
