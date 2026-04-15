<?php

namespace App\Filament\Admin\Resources\NewsPosts\Schemas;

use App\Forms\Components\QuillEditor;
use App\Models\NewsPost;
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
                Hidden::make('parish_id'),
                Hidden::make('created_by_user_id'),
                Hidden::make('updated_by_user_id'),

                Section::make('Nagłówek wpisu')
                    ->description('Nadaj tytuł. Szkic jest tworzony automatycznie, więc od razu możesz pracować na pełnym edytorze.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--masthead',
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('Tytuł wpisu')
                            ->live(debounce: 600)
                            ->maxLength(255)
                            ->placeholder('Np. Droga krzyżowa w najbliższy piątek')
                            ->helperText('Slug wygeneruje się automatycznie. Nie trzeba go uzupełniać ani pilnować ręcznie.')
                            ->extraInputAttributes([
                                'class' => 'text-lg md:text-2xl font-semibold',
                            ]),
                    ]),

                Section::make('Publikacja')
                    ->description('Sterujesz tylko stanem wpisu. Szkic zapisuje się w tle, a publikacja i planowanie wymagają już świadomego zapisu.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--publication',
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(NewsPost::getStatusOptions())
                            ->default('draft')
                            ->native(false)
                            ->live(),

                        DateTimePicker::make('scheduled_for')
                            ->label('Zaplanowana publikacja')
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
                            ->label('Przypnij wpis')
                            ->inline(false),

                        Toggle::make('comments_enabled')
                            ->label('Zezwól na komentarze')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Domyślnie komentarze są włączone. Komentować mogą tylko zalogowani i zatwierdzeni użytkownicy.'),

                        Placeholder::make('publication_hint')
                            ->label('Tryb pracy')
                            ->content('Nowy wpis powstaje od razu jako szkic. W tym trybie możesz od razu dodawać obrazy do treści i spokojnie wracać do edycji.'),

                        Placeholder::make('comments_shortcut')
                            ->label('Komentarze do wpisu')
                            ->content(fn (?NewsPost $record): HtmlString => new HtmlString($record
                                ? '<a href="'.e(\App\Filament\Admin\Resources\NewsComments\NewsCommentResource::getUrl('index', [
                                    'filters' => [
                                        'news_post_id' => ['value' => $record->getKey()],
                                    ],
                                ])).'" class="text-primary-600 underline">Otwórz komentarze tego wpisu</a>'
                                : 'Link do komentarzy pojawi się po utworzeniu wpisu.')),
                    ]),

                Section::make('Treść wpisu')
                    ->description('Główny obszar redakcyjny. Edytor obsługuje osadzanie zdjęć wewnątrz treści i zapisuje HTML wpisu.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--content',
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        QuillEditor::make('content')
                            ->label('Treść')
                            ->minHeight(860)
                            ->maxLength(65000)
                            ->maxUploadSize(8192)
                            ->imageUploadUrl(fn (?NewsPost $record): ?string => $record
                                ? route('admin.news-posts.inline-image', ['newsPost' => $record])
                                : null)
                            ->placeholder('Napisz aktualność...')
                            ->helperText('Zdjęcia osadzane przyciskiem obrazu trafiają do Spatie Media Library i są dostępne od razu, bez dodatkowego zapisywania szkicu.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media wpisu')
                    ->description('Media obsługiwane przez Spatie Media Library.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--media',
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->label('Zdjęcie wyróżniające')
                            ->collection('featured_image')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),

                        SpatieMediaLibraryFileUpload::make('gallery')
                            ->label('Galeria wpisu')
                            ->collection('gallery')
                            ->multiple()
                            ->image()
                            ->imageEditor()
                            ->appendFiles()
                            ->reorderable()
                            ->maxFiles(30)
                            ->maxSize(6144),

                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->label('Załączniki')
                            ->collection('attachments')
                            ->multiple()
                            ->appendFiles()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->maxFiles(30)
                            ->maxSize(10240),

                        Placeholder::make('inline_media_hint')
                            ->label('Osadzanie zdjęć w treści')
                            ->content('Zdjęcia dodane przyciskiem obrazu w edytorze są zapisywane jako kolekcja "content_images" w Spatie Media Library.'),
                    ]),
            ]);
    }
}
