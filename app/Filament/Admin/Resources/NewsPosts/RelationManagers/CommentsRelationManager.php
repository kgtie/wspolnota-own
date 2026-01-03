<?php

namespace App\Filament\Admin\Resources\NewsPosts\RelationManagers;

use App\Models\NewsComment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Komentarze';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('content')->label('Treść')->required()->rows(4),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('Autor')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'hidden' ? 'Ukryty' : 'Widoczny')
                    ->color(fn (string $state) => $state === 'hidden' ? 'gray' : 'success'),
                TextColumn::make('content')->label('Treść')->wrap(),
                TextColumn::make('created_at')->label('Data')->dateTime('d.m.Y H:i'),
            ])
            ->recordActions([
                // EditAction::make(),
                DeleteAction::make(),

                Action::make('hide')
                    ->label('Ukryj')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->visible(fn (NewsComment $record) => $record->status !== 'hidden')
                    ->action(fn (NewsComment $record) => $record->update(['status' => 'hidden'])),

                Action::make('show')
                    ->label('Pokaż')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (NewsComment $record) => $record->status === 'hidden')
                    ->action(fn (NewsComment $record) => $record->update(['status' => 'visible'])),
            ]);
    }
}
