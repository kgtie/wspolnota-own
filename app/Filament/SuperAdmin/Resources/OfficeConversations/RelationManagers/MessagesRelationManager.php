<?php

namespace App\Filament\SuperAdmin\Resources\OfficeConversations\RelationManagers;

use App\Models\OfficeConversation;
use App\Models\OfficeMessage;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Wiadomości';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at')
            ->recordTitleAttribute('body')
            ->columns([
                TextColumn::make('sender.full_name')
                    ->label('Nadawca')
                    ->state(fn (OfficeMessage $record): string => $record->sender?->full_name
                        ?: $record->sender?->name
                        ?: $record->sender?->email
                        ?: 'Użytkownik usunięty')
                    ->description(fn (OfficeMessage $record): string => $record->sender?->email ?: 'Brak adresu e-mail')
                    ->sortable()
                    ->searchable(['users.full_name', 'users.name', 'users.email']),

                TextColumn::make('body')
                    ->label('Treść')
                    ->limit(120)
                    ->placeholder('Brak treści')
                    ->wrap(),

                IconColumn::make('has_attachments')
                    ->label('Załączniki')
                    ->boolean(),

                TextColumn::make('attachments_list')
                    ->label('Pliki')
                    ->state(function (OfficeMessage $record): string {
                        $links = $record->getMedia('attachments')
                            ->map(fn ($media): string => sprintf(
                                '<a class="text-primary-600 hover:underline" href="%s" target="_blank">%s</a>',
                                e(route('office.attachments.download', ['media' => $media])),
                                e($media->file_name),
                            ))
                            ->all();

                        return $links === [] ? 'Brak' : implode('<br>', $links);
                    })
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('read_by_priest_at')
                    ->label('Przeczytane po stronie admina')
                    ->state(fn (OfficeMessage $record): string => $record->read_by_priest_at
                        ? $record->read_by_priest_at->format('d.m.Y H:i')
                        : 'Nie')
                    ->badge()
                    ->color(fn (OfficeMessage $record): string => $record->read_by_priest_at ? 'success' : 'warning'),

                TextColumn::make('read_by_parishioner_at')
                    ->label('Przeczytane przez parafianina')
                    ->state(fn (OfficeMessage $record): string => $record->read_by_parishioner_at
                        ? $record->read_by_parishioner_at->format('d.m.Y H:i')
                        : 'Nie')
                    ->badge()
                    ->color(fn (OfficeMessage $record): string => $record->read_by_parishioner_at ? 'success' : 'warning'),

                TextColumn::make('created_at')
                    ->label('Wysłana')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Dodaj wiadomość')
                    ->schema(self::messageSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['has_attachments'] = false;

                        return $data;
                    })
                    ->after(function (OfficeMessage $record): void {
                        $conversation = $this->getOwnerRecord();

                        if (! $conversation instanceof OfficeConversation) {
                            return;
                        }

                        $hasAttachments = $record->getMedia('attachments')->isNotEmpty();

                        if ($hasAttachments && ! $record->has_attachments) {
                            $record->update(['has_attachments' => true]);
                        }

                        $conversation->update([
                            'last_message_at' => now(),
                            'status' => OfficeConversation::STATUS_OPEN,
                            'closed_at' => null,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->schema(self::messageSchema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['has_attachments'] = false;

                        return $data;
                    })
                    ->after(function (OfficeMessage $record): void {
                        $hasAttachments = $record->getMedia('attachments')->isNotEmpty();

                        if ($hasAttachments && ! $record->has_attachments) {
                            $record->update(['has_attachments' => true]);
                        }

                        if (! $hasAttachments && $record->has_attachments) {
                            $record->update(['has_attachments' => false]);
                        }
                    }),

                DeleteAction::make()
                    ->after(function (): void {
                        $conversation = $this->getOwnerRecord();

                        if (! $conversation instanceof OfficeConversation) {
                            return;
                        }

                        $latestAt = $conversation->messages()->max('created_at');
                        $messageCount = $conversation->messages()->count();

                        $conversation->update([
                            'last_message_at' => $latestAt,
                            'status' => $messageCount > 0 ? $conversation->status : OfficeConversation::STATUS_OPEN,
                            'closed_at' => $messageCount > 0 ? $conversation->closed_at : null,
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    protected static function messageSchema(): array
    {
        return [
            Select::make('sender_user_id')
                ->label('Nadawca')
                ->required()
                ->searchable()
                ->preload()
                ->options(fn (): array => User::query()
                    ->orderByRaw("COALESCE(NULLIF(full_name, ''), name, email)")
                    ->get()
                    ->mapWithKeys(fn (User $user): array => [
                        $user->getKey() => $user->full_name ?: $user->name ?: $user->email,
                    ])
                    ->all()),

            Textarea::make('body')
                ->label('Treść wiadomości')
                ->rows(6)
                ->maxLength(12000)
                ->columnSpanFull(),

            Toggle::make('has_attachments')
                ->label('Czy zawiera załączniki')
                ->dehydrated(false)
                ->disabled()
                ->helperText('Zarządzaj plikami przez moduł Media (model: OfficeMessage).'),

            DateTimePicker::make('read_by_priest_at')
                ->label('Przeczytane po stronie admina')
                ->seconds(false)
                ->native(false),

            DateTimePicker::make('read_by_parishioner_at')
                ->label('Przeczytane przez parafianina')
                ->seconds(false)
                ->native(false),
        ];
    }
}
