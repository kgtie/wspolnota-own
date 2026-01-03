<?php

namespace App\Filament\Superadmin\Resources\NewsPosts;

use App\Filament\Superadmin\Resources\NewsPosts\Pages;
use App\Filament\Superadmin\Resources\NewsPosts\RelationManagers\CommentsRelationManager;
use App\Filament\Superadmin\Resources\NewsPosts\RelationManagers\MediaRelationManager;
use App\Models\NewsPost;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

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
            Select::make('parish_id')
                ->label('Parafia')
                ->relationship('parish', 'name')
                ->searchable()
                ->required(),

            Select::make('author_user_id')
                ->label('Autor')
                ->relationship('author', 'name')
                ->searchable()
                ->required(),

            TextInput::make('title')
                ->label('Tytuł')
                ->required()
                ->maxLength(255),

            TextInput::make('slug')
                ->label('Slug')
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

            TextInput::make('excerpt')
                ->label('Zajawka')
                ->maxLength(500)
                ->columnSpanFull(),

            RichEditor::make('content')
                ->label('Treść')
                ->required()
                ->extraInputAttributes(['style' => 'min-height: 400px;'])
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                TextColumn::make('parish.name')->label('Parafia')->searchable()->wrap(),
                TextColumn::make('title')->label('Tytuł')->searchable()->wrap(),

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

                TextColumn::make('author.name')->label('Autor')->toggleable(),
                TextColumn::make('published_at')->label('Publikacja')->dateTime('d.m.Y H:i')->sortable(),
                TextColumn::make('comments_count')->label('Komentarze')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Szkic',
                        'published' => 'Opublikowany',
                        'pending' => 'Oczekuje',
                    ]),
                SelectFilter::make('parish')
                    ->label('Parafia')
                    ->relationship('parish', 'name')
                    ->searchable(),
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
                    ->action(fn (NewsPost $record) => $record->update(['status' => 'draft'])),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('comments');
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
