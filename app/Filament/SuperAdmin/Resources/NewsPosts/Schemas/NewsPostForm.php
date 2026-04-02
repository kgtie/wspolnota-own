<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts\Schemas;

use App\Forms\Components\QuillEditor;
use App\Models\NewsPost;
use App\Models\Parish;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class NewsPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->components([
                Hidden::make('created_by_user_id'),
                Hidden::make('updated_by_user_id'),

                Section::make('Edytor wpisu')
                    ->description('Główny obszar redakcyjny jak w panelu CMS: tytuł, permalink i treść.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('Dodaj tytuł')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes([
                                'class' => 'text-lg md:text-xl font-semibold',
                                'placeholder' => 'np. Ogłoszenia parafialne na najbliższą niedzielę',
                            ]),

                        TextInput::make('slug')
                            ->label('Permalink (slug)')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug((string) $state) : null)
                            ->helperText('Opcjonalnie. Jeśli pole pozostanie puste, slug zostanie wygenerowany automatycznie z tytułu.'),

                        QuillEditor::make('content')
                            ->label('Treść')
                            ->required()
                            ->minHeight(760)
                            ->maxLength(65000)
                            ->maxUploadSize(8192)
                            ->imageUploadUrl(fn (?NewsPost $record): ?string => $record
                                ? route('admin.news-posts.inline-image', ['newsPost' => $record])
                                : null)
                            ->placeholder('Napisz aktualność...')
                            ->helperText('Aby osadzać zdjęcia bezpośrednio w treści, zapisz wpis i dodawaj obrazy z paska edytora.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Publikacja')
                    ->description('Status, parafia, harmonogram i priorytet wpisu.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('parish_id')
                            ->label('Parafia')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->options(fn (): array => Parish::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()),

                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(NewsPost::getStatusOptions())
                            ->default('draft')
                            ->native(false)
                            ->live(),

                        DateTimePicker::make('scheduled_for')
                            ->label('Data publikacji (zaplanowana)')
                            ->seconds(false)
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('status') === 'scheduled')
                            ->required(fn (Get $get): bool => $get('status') === 'scheduled'),

                        DateTimePicker::make('published_at')
                            ->label('Data publikacji')
                            ->seconds(false)
                            ->native(false)
                            ->visible(fn (Get $get): bool => in_array((string) $get('status'), ['published', 'archived'], true)),

                        Toggle::make('is_pinned')
                            ->label('Przypnij na górze listy')
                            ->inline(false),

                        Toggle::make('comments_enabled')
                            ->label('Zezwól na komentarze')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Komentarze są domyślnie włączone. Na stronie mogą komentować tylko zalogowani i zatwierdzeni użytkownicy.'),

                        Placeholder::make('comments_shortcut')
                            ->label('Komentarze do wpisu')
                            ->content(fn (?NewsPost $record): HtmlString => new HtmlString($record
                                ? '<a href="'.e(\App\Filament\SuperAdmin\Resources\NewsComments\NewsCommentResource::getUrl('index', [
                                    'filters' => [
                                        'news_post_id' => ['value' => $record->getKey()],
                                    ],
                                ])).'" class="text-primary-600 underline">Otwórz komentarze tego wpisu</a>'
                                : 'Link do komentarzy pojawi się po zapisaniu wpisu.')),
                    ]),

                Section::make('Obraz wyróżniający')
                    ->description('Zdjęcie miniatury wpisu wyświetlane na listach aktualności.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->label('Obraz główny')
                            ->collection('featured_image')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                    ]),

                Section::make('Galeria i załączniki')
                    ->description('Dodatkowe materiały do wpisu.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('Galeria')
                            ->collection('gallery')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->appendFiles()
                            ->reorderable()
                            ->maxFiles(30)
                            ->maxSize(6144),

                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Pliki do pobrania')
                            ->collection('attachments')
                            ->multiple()
                            ->appendFiles()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->maxFiles(30)
                            ->maxSize(10240),

                        Placeholder::make('inline_media_hint')
                            ->label('Wskazówka redakcyjna')
                            ->content('Grafiki osadzane bezpośrednio w edytorze zapisują się jako kolekcja "content_images".'),
                    ]),
            ]);
    }
}
