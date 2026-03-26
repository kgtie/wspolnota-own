<?php

namespace App\Filament\SuperAdmin\Resources\NewsPosts\Schemas;

use App\Models\NewsPost;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Odczyt wpisu w panelu superadmina.
 *
 * Widok ma byc redakcyjny, ale nie moze renderowac surowego HTML z bazy bez
 * sanizacji, bo wpisy pochodza z edytora rich text i moga stac sie nosnikiem XSS.
 */
class NewsPostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aktualnosc')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('featured_image')
                            ->label('Zdjecie wyrozniajace')
                            ->collection('featured_image')
                            ->conversion('preview')
                            ->columnSpanFull(),

                        TextEntry::make('title')
                            ->label('Tytul')
                            ->columnSpanFull(),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'published' => 'success',
                                'scheduled' => 'warning',
                                'archived' => 'gray',
                                default => 'info',
                            })
                            ->formatStateUsing(fn (string $state): string => NewsPost::getStatusOptions()[$state] ?? $state),

                        TextEntry::make('parish.name')
                            ->label('Parafia')
                            ->placeholder('Brak'),

                        TextEntry::make('is_pinned')
                            ->label('Przypiety')
                            ->badge()
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Tak' : 'Nie')
                            ->color(fn (bool $state): string => $state ? 'info' : 'gray'),

                        TextEntry::make('scheduled_for')
                            ->label('Zaplanowano na')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Brak'),

                        TextEntry::make('published_at')
                            ->label('Opublikowano')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Nie opublikowano'),

                        TextEntry::make('push_notification_sent_at')
                            ->label('Push dispatch')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('email_notification_sent_at')
                            ->label('Email dispatch')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('content')
                            ->label('Pelna tresc')
                            ->state(fn (NewsPost $record): string => static::sanitizeRenderedContent($record->content))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('gallery_media_count')
                            ->label('Zdjecia w galerii')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('gallery')->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('attachments_media_count')
                            ->label('Zalaczniki')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('attachments')->count())
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('content_images_media_count')
                            ->label('Obrazy osadzone w tresci')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('content_images')->count())
                            ->badge()
                            ->color('warning'),
                    ]),

                Section::make('Historia wpisu')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('createdBy.full_name')
                            ->label('Utworzyl')
                            ->placeholder('System'),

                        TextEntry::make('updatedBy.full_name')
                            ->label('Edytowal')
                            ->placeholder('Brak'),

                        TextEntry::make('created_at')
                            ->label('Utworzono')
                            ->dateTime('d.m.Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Aktualizacja')
                            ->since(),
                    ]),
            ]);
    }

    /**
     * Sanityzuje HTML tylko na potrzeby panelu administracyjnego.
     * Front redakcyjny nadal moze korzystac z bogatszego renderu, ale w Filamencie
     * odcinamy potencjalnie wykonywalny markup i zostawiamy bezpieczne elementy.
     */
    protected static function sanitizeRenderedContent(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowRelativeLinks()
            ->allowRelativeMedias();

        return (new HtmlSanitizer($config))->sanitize((string) $html);
    }
}
