<?php

namespace App\Filament\Admin\Resources\AnnouncementSets\RelationManagers;

use App\Models\AnnouncementItem;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Pojedyncze ogloszenia';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('content')
            ->defaultSort('position')
            ->reorderable('position')
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering): Action => $action
                ->label($isReordering ? 'Zakoncz sortowanie' : 'Sortuj drag-and-drop')
                ->tooltip($isReordering
                    ? 'Kliknij i zakoncz tryb sortowania po ustawieniu kolejnosci.'
                    : 'Wlacz tryb przeciagania i upuszczania pojedynczych ogloszen.'))
            ->columns([
                TextColumn::make('position')
                    ->label('Lp.')
                    ->badge()
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('content')
                    ->label('Tresc')
                    ->html()
                    ->wrap()
                    ->searchable()
                    ->state(function (AnnouncementItem $record): string {
                        $short = (string) str($record->content)->limit(250);
                        $safe = nl2br(e($short));

                        if ($record->is_important) {
                            return "<strong>{$safe}</strong>";
                        }

                        return $safe;
                    })
                    ->description(fn (AnnouncementItem $record): ?string => $record->title ?: null),

                IconColumn::make('is_important')
                    ->label('Wazne')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktywne')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Aktualizacja')
                    ->dateTime('d.m.Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_important')
                    ->label('Wazne')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TernaryFilter::make('is_active')
                    ->label('Aktywne')
                    ->nullable()
                    ->trueLabel('Tak')
                    ->falseLabel('Nie'),

                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj ogloszenie')
                    ->schema(self::getItemFormSchema()),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('move_up')
                        ->label('Przesun wyzej')
                        ->icon('heroicon-o-chevron-up')
                        ->color('gray')
                        ->action(fn (AnnouncementItem $record) => $record->moveUp())
                        ->disabled(fn (AnnouncementItem $record): bool => $record->position <= 1),

                    Action::make('move_down')
                        ->label('Przesun nizej')
                        ->icon('heroicon-o-chevron-down')
                        ->color('gray')
                        ->action(fn (AnnouncementItem $record) => $record->moveDown())
                        ->disabled(function (AnnouncementItem $record): bool {
                            return ! AnnouncementItem::query()
                                ->where('announcement_set_id', $record->announcement_set_id)
                                ->where('position', '>', $record->position)
                                ->exists();
                        }),

                    Action::make('toggle_important')
                        ->label(fn (AnnouncementItem $record): string => $record->is_important
                            ? 'Oznacz jako zwykle'
                            : 'Oznacz jako wazne')
                        ->icon('heroicon-o-exclamation-circle')
                        ->color(fn (AnnouncementItem $record): string => $record->is_important ? 'gray' : 'danger')
                        ->action(function (AnnouncementItem $record): void {
                            $record->update([
                                'is_important' => ! $record->is_important,
                            ]);
                        }),

                    Action::make('toggle_active')
                        ->label(fn (AnnouncementItem $record): string => $record->is_active
                            ? 'Ukryj ogloszenie'
                            : 'Pokaz ogloszenie')
                        ->icon('heroicon-o-eye')
                        ->color(fn (AnnouncementItem $record): string => $record->is_active ? 'warning' : 'success')
                        ->action(function (AnnouncementItem $record): void {
                            $record->update([
                                'is_active' => ! $record->is_active,
                            ]);
                        }),

                    EditAction::make()
                        ->schema(self::getItemFormSchema()),

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
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function getItemFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Naglowek')
                ->maxLength(255),

            Textarea::make('content')
                ->label('Tresc ogloszenia')
                ->required()
                ->rows(6)
                ->maxLength(8000)
                ->columnSpanFull(),

            Toggle::make('is_important')
                ->label('Ogloszenie wazne')
                ->default(false),

            Toggle::make('is_active')
                ->label('Widoczne')
                ->default(true),
        ];
    }
}
