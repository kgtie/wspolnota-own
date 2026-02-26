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

                Section::make('Tytul')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 9,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('Tytul wpisu')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes([
                                'class' => 'text-base md:text-lg font-semibold',
                            ]),
                    ]),

                Section::make('Publikacja')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 3,
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
                    ]),

                Section::make('Tresc wpisu')
                    ->description('Szeroki obszar redakcyjny. Edytor obsluguje osadzanie zdjec.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 9,
                    ])
                    ->schema([
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
                            ->helperText('Aby osadzac zdjecia w tresci, najpierw zapisz wpis. Potem uzyj przycisku obrazu w pasku edytora.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media wpisu')
                    ->description('Media obslugiwane przez Spatie Media Library.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 3,
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
