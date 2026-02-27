<?php

namespace App\Filament\SuperAdmin\Resources\MailingLists\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MailingListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nazwa')->searchable()->sortable(),
                TextColumn::make('mails_count')->label('Subskrybenci')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Aktualizacja')->since()->sortable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
