<?php

namespace App\Filament\Admin\Resources\NewsPosts;

use App\Filament\Admin\Resources\NewsPosts\Pages;
use App\Filament\Admin\Resources\NewsPosts\RelationManagers\CommentsRelationManager;
use App\Filament\Admin\Resources\NewsPosts\RelationManagers\MediaRelationManager;
use App\Models\NewsPost;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Forms\Components\Hidden;

class NewsPostResource extends Resource
{
    protected static ?string $model = NewsPost::class;

    public static function getNavigationGroup(): ?string
    {
        return 'Treści';
    }

    public static function getNavigationLabel(): string
    {
        return 'Aktualności';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-newspaper';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $set, ?NewsPost $record) {
                    if ($record?->exists) {
                        return;
                    }
                    $set('slug', Str::slug((string) $state));
                })
                ->maxLength(255),

            TextInput::make('slug')
                ->label('Slug')
                ->helperText('Unikalny w obrębie parafii.')
                ->disabled()
                ->required()
                ->maxLength(255),

            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Szkic',
                    'published' => 'Opublikowany',
                    'pending' => 'Oczekuje',
                ])
                ->default('draft')
                ->required(),

            DateTimePicker::make('published_at')
                ->label('Data publikacji')
                ->seconds(false)
                ->visible(fn ($get) => $get('status') === 'published')
                ->default(now()),

            Hidden::make('excerpt_locked')
                ->default(false)
                ->dehydrated(false),

            TextInput::make('excerpt')
                ->label('Zajawka')
                ->maxLength(500)
                ->columnSpanFull()
                ->afterStateUpdated(fn ($state, callable $set) => $set('excerpt_locked', filled($state))),

            RichEditor::make('content')
                ->label('Treść')
                ->required()
                ->columnSpanFull()
                ->live()
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if ($get('excerpt_locked')) {
                        return;
                    }

                    $plain = trim(strip_tags((string) $state));
                    $excerpt = Str::words($plain, 30, '…');

                    $set('excerpt', $excerpt);
                })
                ->extraInputAttributes(['style' => 'min-height: 400px;']),

            Placeholder::make('meta')
                ->label('Informacje')
                ->content(fn (?NewsPost $record) => $record
                    ? "Autor ID: {$record->author_user_id}, Utworzono: {$record->created_at?->format('d.m.Y H:i')}"
                    : '—')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Tytuł')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'draft' => 'Szkic',
                        'published' => 'Opublikowany',
                        'pending' => 'Oczekuje',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label('Publikacja')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                TextColumn::make('comments_count')
                    ->label('Komentarze')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Szkic',
                        'published' => 'Opublikowany',
                        'pending' => 'Oczekuje',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),

                Action::make('publishNow')
                    ->label('Opublikuj')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (NewsPost $record) => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function (NewsPost $record) {
                        $record->status = 'published';
                        $record->published_at = $record->published_at ?? now();
                        $record->save();
                    }),

                Action::make('toDraft')
                    ->label('Cofnij do szkicu')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn (NewsPost $record) => $record->status === 'published')
                    ->requiresConfirmation()
                    ->action(function (NewsPost $record) {
                        $record->status = 'draft';
                        $record->save();
                    }),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->where('parish_id', $tenant->id)
            ->withCount('comments');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsPosts::route('/'),
            'create' => Pages\CreateNewsPost::route('/create'),
            'edit' => Pages\EditNewsPost::route('/{record}/edit'),
        ];
    }
}
