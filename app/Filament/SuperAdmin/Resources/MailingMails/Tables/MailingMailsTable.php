<?php

namespace App\Filament\SuperAdmin\Resources\MailingMails\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MailingMailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->columns([
                TextColumn::make('email')->label('Email')->searchable()->copyable()->sortable(),
                TextColumn::make('mailingList.name')->label('Lista')->searchable()->sortable(),
                TextColumn::make('confirmed_at')->label('Potwierdzony')->dateTime('d.m.Y H:i')->placeholder('Nie')->sortable(),
                TextColumn::make('deleted_at')->label('Wypisany')->dateTime('d.m.Y H:i')->placeholder('Nie')->sortable(),
                TextColumn::make('created_at')->label('Dodany')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('mailing_list_id')
                    ->label('Lista')
                    ->relationship('mailingList', 'name'),

                TernaryFilter::make('confirmed_at')
                    ->label('Potwierdzony')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    ForceDeleteAction::make(),
                    RestoreAction::make(),
                ])
                    ->label('Akcje')
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
