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
                    ->description('Główny obszar redakcyjny jak w panelu CMS: tytul, permalink i tresc.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('Dodaj tytul')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes([
                                'class' => 'text-lg md:text-xl font-semibold',
                                'placeholder' => 'np. Ogloszenia parafialne na najblizsza niedziele',
                            ]),

                        TextInput::make('slug')
                            ->label('Permalink (slug)')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Str::slug((string) $state) : null)
                            ->helperText('Opcjonalnie. Jesli puste, slug zostanie wygenerowany automatycznie z tytulu.'),

                        QuillEditor::make('content')
                            ->label('Tresc')
                            ->required()
                            ->minHeight(760)
                            ->maxLength(65000)
                            ->maxUploadSize(8192)
                            ->imageUploadUrl(fn (?NewsPost $record): ?string => $record
                                ? route('admin.news-posts.inline-image', ['newsPost' => $record])
                                : null)
                            ->placeholder('Napisz aktualnosc...')
                            ->helperText('Aby osadzac zdjecia bezposrednio w tresci, zapisz wpis i dodawaj obrazy z paska edytora.')
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
                            ->label('Przypnij na gorze listy')
                            ->inline(false),
                    ]),

                Section::make('Obraz wyrozniajacy')
                    ->description('Zdjecie miniatury wpisu wyswietlane na listach aktualnosci.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('featured_image')
                            ->label('Obraz glowny')
                            ->collection('featured_image')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120),
                    ]),

                Section::make('Galeria i zalaczniki')
                    ->description('Dodatkowe materialy do wpisu.')
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
                            ->label('Wskazowka redakcyjna')
                            ->content('Grafiki osadzane bezposrednio w edytorze zapisuja sie jako kolekcja "content_images".'),
                    ]),
            ]);
    }
}
