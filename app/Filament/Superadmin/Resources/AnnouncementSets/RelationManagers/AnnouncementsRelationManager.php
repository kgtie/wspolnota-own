<?php

namespace App\Filament\Superadmin\Resources\AnnouncementSets\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

class AnnouncementsRelationManager extends RelationManager
{
    protected static string $relationship = 'announcements';

    protected static ?string $title = 'Punkty ogłoszeń';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            RichEditor::make('content')
                ->label('Treść ogłoszenia')
                ->required()
                ->columnSpanFull(),

            Toggle::make('is_highlighted')
                ->label('Wyróżnij jako ważne'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('short_content')
                    ->label('Treść')
                    ->wrap(),

                IconColumn::make('is_highlighted')
                    ->label('Ważne')
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $set = $this->getOwnerRecord();

                        $max = $set->announcements()->max('sort_order') ?? 0;
                        $data['sort_order'] = $max + 1;

                        return $data;
                    }),
            ]);
    }
}
