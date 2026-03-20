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

                Section::make('Naglowek wpisu')
                    ->description('Nadaj tytul. Szkic jest tworzony automatycznie, wiec od razu mozesz pracowac na pelnym edytorze.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--masthead',
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('Tytul wpisu')
                            ->live(debounce: 600)
                            ->maxLength(255)
                            ->placeholder('Np. Droga krzyzowa w najblizszy piatek')
                            ->helperText('Slug wygeneruje sie automatycznie. Nie trzeba go uzupelniac ani pilnowac recznie.')
                            ->extraInputAttributes([
                                'class' => 'text-lg md:text-2xl font-semibold',
                            ]),
                    ]),

                Section::make('Publikacja')
                    ->description('Sterujesz tylko stanem wpisu. Draft zapisuje sie w tle, publikacja i planowanie wymagaja juz swiadomego zapisu.')
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
                            ->label('Zezwol na komentarze')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Domyslnie komentarze sa wlaczone. Komentowac moga tylko zalogowani i zatwierdzeni uzytkownicy.'),

                        Placeholder::make('publication_hint')
                            ->label('Tryb pracy')
                            ->content('Nowy wpis powstaje od razu jako szkic. W tym trybie mozesz od razu dodawac obrazy do tresci i spokojnie wracac do edycji.'),

                        Placeholder::make('comments_shortcut')
                            ->label('Komentarze do wpisu')
                            ->content(fn (?NewsPost $record): HtmlString => new HtmlString($record
                                ? '<a href="'.e(\App\Filament\Admin\Resources\NewsComments\NewsCommentResource::getUrl('index', [
                                    'filters' => [
                                        'news_post_id' => ['value' => $record->getKey()],
                                    ],
                                ])).'" class="text-primary-600 underline">Otworz komentarze tego wpisu</a>'
                                : 'Link do komentarzy pojawi sie po utworzeniu wpisu.')),
                    ]),

                Section::make('Tresc wpisu')
                    ->description('Glowny obszar redakcyjny. Edytor obsluguje osadzanie zdjec wewnatrz tresci i zapisuje HTML wpisu.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--content',
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        QuillEditor::make('content')
                            ->label('Tresc')
                            ->minHeight(860)
                            ->maxLength(65000)
                            ->maxUploadSize(8192)
                            ->imageUploadUrl(fn (?NewsPost $record): ?string => $record
                                ? route('admin.news-posts.inline-image', ['newsPost' => $record])
                                : null)
                            ->placeholder('Napisz aktualnosc...')
                            ->helperText('Zdjecia osadzane przyciskiem obrazu trafiaja do Spatie Media Library i sa dostepne od razu, bez dodatkowego zapisywania szkicu.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media wpisu')
                    ->description('Media obslugiwane przez Spatie Media Library.')
                    ->extraAttributes([
                        'class' => 'news-post-form-card news-post-form-card--media',
                    ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->label('Zdjecie wyrozniajace')
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
                            ->label('Zalaczniki')
                            ->collection('attachments')
                            ->multiple()
                            ->appendFiles()
                            ->reorderable()
                            ->openable()
                            ->downloadable()
                            ->maxFiles(30)
                            ->maxSize(10240),

                        Placeholder::make('inline_media_hint')
                            ->label('Osadzanie zdjec w tresci')
                            ->content('Zdjecia dodane przyciskiem obrazu w edytorze zapisywane sa jako kolekcja "content_images" w Spatie Media Library.'),
                    ]),
            ]);
    }
}
