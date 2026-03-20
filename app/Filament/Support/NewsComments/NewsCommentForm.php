<?php

namespace App\Filament\Support\NewsComments;

use App\Models\NewsComment;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsCommentForm
{
    public static function configure(Schema $schema, bool $isSuperAdmin): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->components([
                Section::make('Komentarz')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        Textarea::make('body')
                            ->label('Tresc komentarza')
                            ->rows(12)
                            ->required($isSuperAdmin)
                            ->disabled(! $isSuperAdmin)
                            ->helperText($isSuperAdmin
                                ? 'Superadmin moze poprawiac tresc komentarza, ale drzewo odpowiedzi pozostaje nienaruszalne.'
                                : 'Administrator nie edytuje tresci komentarza. Do dyspozycji pozostaje odpowiedz i ukrycie komentarza.'),

                        Toggle::make('is_hidden')
                            ->label('Komentarz ukryty')
                            ->disabled(! $isSuperAdmin)
                            ->helperText('Ukryty komentarz pozostaje w drzewie odpowiedzi, ale nie pokazuje swojej tresci.'),
                    ]),

                Section::make('Kontekst')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->schema([
                        Placeholder::make('news_post_label')
                            ->label('Wpis')
                            ->content(fn (?NewsComment $record): string => $record?->newsPost?->getDisplayTitle() ?? '-'),

                        Placeholder::make('author_label')
                            ->label('Autor')
                            ->content(fn (?NewsComment $record): string => $record?->user?->full_name ?: ($record?->user?->name ?? '-')),

                        Placeholder::make('thread_level')
                            ->label('Poziom w watku')
                            ->content(fn (?NewsComment $record): string => match ((int) ($record?->depth ?? 0)) {
                                1 => 'Odpowiedz na komentarz glowny',
                                2 => 'Odpowiedz drugiego poziomu',
                                default => 'Komentarz glowny',
                            }),

                        Placeholder::make('parent_preview')
                            ->label('Komentarz rodzica')
                            ->content(fn (?NewsComment $record): string => $record?->parent
                                ? str($record->parent->body)->squish()->limit(120)->toString()
                                : 'Brak - komentarz glowny'),

                        Placeholder::make('created_at_label')
                            ->label('Dodano')
                            ->content(fn (?NewsComment $record): string => $record?->created_at?->format('d.m.Y H:i') ?? '-'),
                    ]),
            ]);
    }
}
