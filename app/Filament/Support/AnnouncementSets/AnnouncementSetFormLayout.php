<?php

namespace App\Filament\Support\AnnouncementSets;

use App\Models\AnnouncementSet;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

class AnnouncementSetFormLayout
{
    /**
     * @param  array<int, mixed>  $leadingComponents
     * @return array<int, mixed>
     */
    public static function components(array $leadingComponents = []): array
    {
        return [
            ...$leadingComponents,
            self::overviewSection(),
            self::leadSection(),
            self::itemsSection(),
            self::finalizationSection(),
            self::automationSection(),
        ];
    }

    protected static function overviewSection(): Section
    {
        return Section::make('Zestaw ogłoszeń')
            ->description('Najpierw ustaw nazwę, opis tygodnia oraz okres obowiązywania zestawu.')
            ->columns(2)
            ->schema([
                TextInput::make('title')
                    ->label('Nazwa zestawu')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('np. Ogłoszenia parafialne - XII tydzień zwykły')
                    ->helperText('To nazwa widoczna na liście ogłoszeń oraz na wydruku PDF.')
                    ->columnSpanFull(),

                TextInput::make('week_label')
                    ->label('Opis tygodnia')
                    ->placeholder('np. XII tydzień zwykły')
                    ->helperText('Pole opcjonalne, ale bardzo pomaga w późniejszym wyszukiwaniu zestawów.')
                    ->maxLength(255)
                    ->columnSpanFull(),

                DatePicker::make('effective_from')
                    ->label('Obowiązuje od')
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                        if (blank($state) || filled($get('effective_to'))) {
                            return;
                        }

                        try {
                            $set('effective_to', Carbon::parse($state)->addDays(6)->format('Y-m-d'));
                        } catch (Throwable) {
                            return;
                        }
                    }),

                DatePicker::make('effective_to')
                    ->label('Obowiązuje do')
                    ->native(false)
                    ->live()
                    ->rule('after_or_equal:effective_from')
                    ->helperText('Jeśli to standardowy tydzień, formularz sam podpowie datę 7 dni później.'),

                Placeholder::make('range_summary')
                    ->label('Zakres zestawu')
                    ->content(fn (Get $get): string => self::buildRangeSummary(
                        $get('effective_from'),
                        $get('effective_to'),
                    ))
                    ->columnSpanFull(),
            ]);
    }

    protected static function leadSection(): Section
    {
        return Section::make('Wprowadzenie')
            ->description('Krótki wstęp jest opcjonalny, ale porządkuje ogłoszenia i będzie widoczny na wydruku.')
            ->schema([
                Textarea::make('lead')
                    ->label('Wstęp')
                    ->rows(3)
                    ->autosize()
                    ->maxLength(5000)
                    ->helperText('Jeśli wpiszesz wprowadzenie, pokaże się przed listą pojedynczych ogłoszeń.')
                    ->columnSpanFull(),
            ]);
    }

    protected static function itemsSection(): Section
    {
        return Section::make('Pojedyncze ogłoszenia')
            ->description('Tutaj tworzysz właściwą treść zestawu. Kolejność z formularza przejdzie do aplikacji i do PDF.')
            ->schema([
                Placeholder::make('items_overview')
                    ->label('Szybka ocena zestawu')
                    ->content(fn (Get $get): string => self::buildItemsOverview($get('items'))),

                self::itemsRepeater(),
            ]);
    }

    protected static function finalizationSection(): Section
    {
        return Section::make('Finalizacja i publikacja')
            ->description('Na końcu możesz dopisać zakończenie, ustawić status i sprawdzić, czy zestaw jest gotowy do publikacji.')
            ->columns(2)
            ->schema([
                Textarea::make('footer_notes')
                    ->label('Słowo końcowe')
                    ->rows(3)
                    ->autosize()
                    ->maxLength(5000)
                    ->helperText('Opcjonalne zakończenie zestawu, przydatne np. przy życzeniach lub podziękowaniach.')
                    ->columnSpanFull(),

                Select::make('status')
                    ->label('Status')
                    ->required()
                    ->options(AnnouncementSet::getStatusOptions())
                    ->default('draft')
                    ->native(false)
                    ->live(),

                DateTimePicker::make('published_at')
                    ->label('Data publikacji')
                    ->seconds(false)
                    ->native(false)
                    ->visible(fn (Get $get): bool => in_array($get('status'), ['published', 'archived'], true)),

                Placeholder::make('publication_readiness')
                    ->label('Gotowość do publikacji')
                    ->content(fn (Get $get): string => self::buildPublicationReadiness(
                        $get('status'),
                        $get('items'),
                    ))
                    ->columnSpanFull(),
            ]);
    }

    protected static function automationSection(): Section
    {
        return Section::make('AI i komunikacja')
            ->description('To sekcja informacyjna. Pokazuje, co system zrobił już automatycznie po publikacji.')
            ->columns(2)
            ->collapsible()
            ->collapsed()
            ->schema([
                Textarea::make('summary_ai')
                    ->label('Streszczenie AI')
                    ->rows(4)
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Streszczenie zostanie wygenerowane automatycznie dla opublikowanego zestawu.')
                    ->columnSpanFull(),

                Placeholder::make('summary_generated_at_info')
                    ->label('Data streszczenia')
                    ->content(fn ($record): string => $record?->summary_generated_at?->format('d.m.Y H:i') ?? 'Brak'),

                Placeholder::make('notifications_sent_at_info')
                    ->label('Wysyłka e-maili')
                    ->content(fn ($record): string => $record?->notifications_sent_at?->format('d.m.Y H:i') ?? 'Nie wysłano'),

                Placeholder::make('notifications_recipients_info')
                    ->label('Liczba odbiorców')
                    ->content(fn ($record): string => (string) ($record?->notifications_recipients_count ?? 0)),
            ]);
    }

    protected static function itemsRepeater(): Repeater
    {
        return Repeater::make('items')
            ->relationship('items')
            ->hiddenLabel()
            ->defaultItems(1)
            ->minItems(1)
            ->addActionLabel('Dodaj kolejne ogłoszenie')
            ->helperText('Dodawaj ogłoszenia w kolejności od najważniejszych do końcowych. Możesz przeciągać pozycje i klonować podobne komunikaty.')
            ->itemNumbers()
            ->truncateItemLabel(false)
            ->labelBetweenItems('Kolejne ogłoszenie')
            ->collapsible()
            ->reorderableWithDragAndDrop()
            ->reorderableWithButtons()
            ->orderColumn('position')
            ->cloneable()
            ->itemLabel(fn (array $state): string => self::buildItemLabel($state))
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::mutateItemData($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::mutateItemData($data))
            ->schema([
                TextInput::make('title')
                    ->label('Krótki nagłówek')
                    ->placeholder('Opcjonalny, np. Zbiórka charytatywna')
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->helperText('Nagłówek ułatwia późniejsze odnalezienie pozycji, ale nie jest obowiązkowy.')
                    ->columnSpanFull(),

                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])->schema([
                    Toggle::make('is_important')
                        ->label('Wazne')
                        ->default(false)
                        ->inline(false)
                        ->live()
                        ->helperText('Ważne ogłoszenia zostaną wyróżnione w aplikacji i na wydruku.'),

                    Toggle::make('is_active')
                        ->label('Aktywne')
                        ->default(true)
                        ->inline(false)
                        ->live()
                        ->helperText('Możesz tymczasowo ukryć pozycję bez jej usuwania.'),
                ])->columnSpanFull(),

                Textarea::make('content')
                    ->label('Treść ogłoszenia')
                    ->required()
                    ->rows(4)
                    ->autosize()
                    ->live(onBlur: true)
                    ->maxLength(5000)
                    ->placeholder('Wpisz pełną treść ogłoszenia. To pole trafi do aplikacji i na wydruk PDF.')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mutateItemData(array $data): array
    {
        $admin = Filament::auth()->user();

        $data['title'] = filled($data['title'] ?? null) ? trim((string) $data['title']) : null;
        $data['content'] = trim((string) ($data['content'] ?? ''));
        $data['is_important'] = (bool) ($data['is_important'] ?? false);
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true;

        if ($admin instanceof User) {
            $data['created_by_user_id'] = $data['created_by_user_id'] ?? $admin->id;
            $data['updated_by_user_id'] = $admin->id;
        }

        return $data;
    }

    protected static function buildRangeSummary(mixed $effectiveFrom, mixed $effectiveTo): string
    {
        if (blank($effectiveFrom)) {
            return 'Wybierz datę rozpoczęcia, a formularz od razu podpowie tygodniowy zakres obowiązywania.';
        }

        $from = self::formatDate($effectiveFrom);

        if (blank($effectiveTo)) {
            return "Zestaw zacznie obowiązywać {$from}. Data końcowa jest opcjonalna.";
        }

        $to = self::formatDate($effectiveTo);

        return "Zestaw obejmuje okres od {$from} do {$to}.";
    }

    protected static function buildPublicationReadiness(mixed $status, mixed $items): string
    {
        $normalizedItems = self::normalizeItems($items);
        $completedItems = self::completedItemsCount($normalizedItems);
        $activeItems = self::activeItemsCount($normalizedItems);

        if ($completedItems === 0) {
            return 'Zestaw nie ma jeszcze żadnego kompletnego ogłoszenia. Najpierw dodaj treść przynajmniej jednej pozycji.';
        }

        if ($activeItems === 0) {
            return 'Wszystkie ogłoszenia są obecnie nieaktywne. Taki zestaw nie pokaże się w aplikacji ani na wydruku.';
        }

        if ($status === 'published') {
            return "Zestaw jest gotowy do publikacji: {$completedItems} kompletne pozycje, {$activeItems} aktywne.";
        }

        return "Zestaw wygląda sensownie do dalszej pracy: {$completedItems} kompletne pozycje, {$activeItems} aktywne.";
    }

    protected static function buildItemsOverview(mixed $items): string
    {
        $normalizedItems = self::normalizeItems($items);

        if ($normalizedItems === []) {
            return 'Nie dodano jeszcze żadnych pojedynczych ogłoszeń. Zacznij od pierwszej pozycji i ułóż kolejność tak, jak ma wyglądać w publikacji.';
        }

        $totalItems = count($normalizedItems);
        $completedItems = self::completedItemsCount($normalizedItems);
        $activeItems = self::activeItemsCount($normalizedItems);
        $importantItems = count(array_filter(
            $normalizedItems,
            fn (array $item): bool => (bool) ($item['is_important'] ?? false),
        ));
        $draftItems = $totalItems - $completedItems;

        $summary = "W zestawie jest {$totalItems} pozycji. Kompletnych: {$completedItems}, aktywnych: {$activeItems}, waznych: {$importantItems}.";

        if ($draftItems > 0) {
            $summary .= " {$draftItems} pozycji wymaga jeszcze uzupełnienia treści.";
        }

        return $summary;
    }

    /**
     * @param  array<string, mixed>  $state
     */
    protected static function buildItemLabel(array $state): string
    {
        $title = filled($state['title'] ?? null)
            ? trim((string) $state['title'])
            : trim((string) ($state['content'] ?? ''));

        if ($title === '') {
            $title = 'Nowe ogłoszenie';
        }

        $flags = [];

        if ((bool) ($state['is_important'] ?? false)) {
            $flags[] = 'wazne';
        }

        if (! ((bool) ($state['is_active'] ?? true))) {
            $flags[] = 'ukryte';
        }

        $label = str($title)->limit(70)->toString();

        if ($flags === []) {
            return $label;
        }

        return $label.' ['.implode(', ', $flags).']';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function normalizeItems(mixed $items): array
    {
        if (! is_array($items)) {
            return [];
        }

        return array_values(array_filter(
            $items,
            fn (mixed $item): bool => is_array($item),
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected static function completedItemsCount(array $items): int
    {
        return count(array_filter(
            $items,
            fn (array $item): bool => filled(trim((string) ($item['content'] ?? ''))),
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected static function activeItemsCount(array $items): int
    {
        return count(array_filter(
            $items,
            fn (array $item): bool => ((bool) ($item['is_active'] ?? true)) && filled(trim((string) ($item['content'] ?? ''))),
        ));
    }

    protected static function formatDate(mixed $value): string
    {
        try {
            return Carbon::parse((string) $value)->format('d.m.Y');
        } catch (Throwable) {
            return (string) $value;
        }
    }
}
