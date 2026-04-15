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
 * Widok ma być redakcyjny, ale nie może renderować surowego HTML z bazy bez
 * sanityzacji, bo wpisy pochodzą z edytora rich text i mogą stać się nośnikiem XSS.
 */
class NewsPostInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aktualność')
                    ->columns(2)
                    ->schema([
                        SpatieMediaLibraryImageEntry::make('featured_image')
                            ->label('Zdjęcie wyróżniające')
                            ->collection('featured_image')
                            ->conversion('preview')
                            ->columnSpanFull(),

                        TextEntry::make('title')
                            ->label('Tytuł')
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
                            ->label('Przypięty')
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
                            ->label('Wysyłka push')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('email_notification_sent_at')
                    ->label('Wysyłka e-maili')
                            ->dateTime('d.m.Y H:i')
                            ->placeholder('Oczekuje'),

                        TextEntry::make('content')
                            ->label('Pełna treść')
                            ->state(fn (NewsPost $record): string => static::sanitizeRenderedContent($record->content))
                            ->html()
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('gallery_media_count')
                            ->label('Zdjęcia w galerii')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('gallery')->count())
                            ->badge()
                            ->color('info'),

                        TextEntry::make('attachments_media_count')
                            ->label('Załączniki')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('attachments')->count())
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('content_images_media_count')
                            ->label('Obrazy osadzone w treści')
                            ->state(fn (NewsPost $record): string => (string) $record->getMedia('content_images')->count())
                            ->badge()
                            ->color('warning'),
                    ]),

                Section::make('Historia wpisu')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('createdBy.full_name')
                            ->label('Utworzył')
                            ->placeholder('System'),

                        TextEntry::make('updatedBy.full_name')
                            ->label('Edytował')
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
     * Front redakcyjny nadal może korzystać z bogatszego renderu, ale w Filamencie
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
