<?php

namespace App\Filament\SuperAdmin\Resources\ActivityLogs;

use App\Filament\SuperAdmin\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Schemas\ActivityLogInfolist;
use App\Filament\SuperAdmin\Resources\ActivityLogs\Tables\ActivityLogsTable;
use App\Filament\SuperAdmin\Resources\AnnouncementSets\AnnouncementSetResource;
use App\Filament\SuperAdmin\Resources\Masses\MassResource;
use App\Filament\SuperAdmin\Resources\Media\MediaResource;
use App\Filament\SuperAdmin\Resources\NewsComments\NewsCommentResource;
use App\Filament\SuperAdmin\Resources\NewsPosts\NewsPostResource;
use App\Filament\SuperAdmin\Resources\OfficeConversations\OfficeConversationResource;
use App\Filament\SuperAdmin\Resources\Parishes\ParishResource;
use App\Filament\SuperAdmin\Resources\UserDevices\UserDeviceResource;
use App\Filament\SuperAdmin\Resources\Users\UserResource;
use App\Models\OfficeMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;
use UnitEnum;

/**
 * Zaawansowany eksplorator activity_log dla superadmina.
 * Resource grupuje techniczne i biznesowe logi w jeden audyt całej usługi.
 */
class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static ?string $modelLabel = 'wpis logu';

    protected static ?string $pluralModelLabel = 'wpisy logów';

    protected static ?string $navigationLabel = 'Logi aktywności';

    protected static string|UnitEnum|null $navigationGroup = 'System i diagnostyka';

    protected static ?int $navigationSort = 20;

    /**
     * @var array<string, array{label: string, color: string}>
     */
    private const LOG_NAME_META = [
        'api-auth' => ['label' => 'API auth', 'color' => 'danger'],
        'api-profile' => ['label' => 'Profil mobilny', 'color' => 'info'],
        'api-office' => ['label' => 'Kancelaria API', 'color' => 'warning'],
        'office-conversations' => ['label' => 'Kancelaria', 'color' => 'warning'],
        'api-parish-approvals' => ['label' => 'Zatwierdzenia parafian', 'color' => 'primary'],
        'admin-user-management' => ['label' => 'Zarządzanie użytkownikami (admin)', 'color' => 'primary'],
        'superadmin-user-management' => ['label' => 'Zarządzanie użytkownikami (superadmin)', 'color' => 'danger'],
        'parish-admin-management' => ['label' => 'Admini parafii', 'color' => 'warning'],
        'admin-announcement-management' => ['label' => 'Ogłoszenia', 'color' => 'info'],
        'announcements-ai' => ['label' => 'AI ogłoszeń', 'color' => 'gray'],
        'news-posts' => ['label' => 'Aktualności', 'color' => 'info'],
        'admin-mass-management' => ['label' => 'Msze i intencje', 'color' => 'success'],
        'scheduler-reports' => ['label' => 'Raporty schedulera', 'color' => 'gray'],
        'superadmin-daily-reports' => ['label' => 'Raporty superadmina', 'color' => 'gray'],
        'parish-weekly-digests' => ['label' => 'Digesty parafialne', 'color' => 'gray'],
        'superadmin-communication-center' => ['label' => 'Centrum komunikacji', 'color' => 'primary'],
        'default' => ['label' => 'Domyślny', 'color' => 'gray'],
    ];

    /**
     * @var array<string, string>
     */
    private const EVENT_LABELS = [
        'api_user_registered' => 'Rejestracja użytkownika przez API',
        'api_login_succeeded' => 'Logowanie udane',
        'api_login_failed' => 'Logowanie nieudane',
        'api_login_blocked_inactive_account' => 'Logowanie zablokowane',
        'api_email_verification_resent' => 'Ponowne wysłanie weryfikacji adresu e-mail',
        'api_email_verified' => 'Adres e-mail potwierdzony',
        'api_refresh_rotated' => 'Odnowienie sesji',
        'api_refresh_reuse_detected' => 'Wykryto ponowne użycie tokenu odświeżania',
        'api_logout' => 'Wylogowanie',
        'api_logout_all' => 'Wylogowanie wszystkich sesji',
        'api_password_reset_requested' => 'Prośba o reset hasła',
        'api_password_reset_completed' => 'Reset hasła zakończony',
        'api_profile_updated' => 'Aktualizacja profilu',
        'api_profile_updated_with_parish_change' => 'Zmiana profilu i parafii domyślnej',
        'api_email_changed' => 'Zmiana adresu e-mail',
        'api_password_changed' => 'Zmiana hasła',
        'api_avatar_uploaded' => 'Wgranie awatara',
        'api_avatar_deleted' => 'Usunięcie awatara',
        'api_parish_approval_code_regenerated' => 'Nowy kod parafialny',
        'api_parish_approval_lookup_succeeded' => 'Lookup parafianina po kodzie',
        'api_parish_approval_failed_invalid_code' => 'Nieudany lookup po kodzie',
        'user_verified_by_code_api' => 'Zatwierdzenie parafianina przez API',
        'api_office_chat_created' => 'Nowa rozmowa w kancelarii',
        'api_office_message_sent' => 'Nowa wiadomość w kancelarii',
        'api_office_attachments_sent' => 'Nowe załączniki w kancelarii',
        'office_attachment_downloaded_via_api' => 'Pobranie załącznika kancelarii przez API',
    ];

    /**
     * @var array<string, string>
     */
    private const CATEGORY_LABELS = [
        'security' => 'Bezpieczeństwo',
        'api_auth' => 'Autoryzacja API',
        'user_profile' => 'Profil i konto',
        'parish_approvals' => 'Zatwierdzanie parafian',
        'office' => 'Kancelaria online',
        'content' => 'Treści i komunikacja',
        'liturgy' => 'Liturgia',
        'system' => 'System i automaty',
        'other' => 'Pozostałe',
    ];

    /**
     * @var array<string, string>
     */
    private const CATEGORY_COLORS = [
        'security' => 'danger',
        'api_auth' => 'danger',
        'user_profile' => 'info',
        'parish_approvals' => 'primary',
        'office' => 'warning',
        'content' => 'info',
        'liturgy' => 'success',
        'system' => 'gray',
        'other' => 'gray',
    ];

    /**
     * @var list<string>
     */
    private const FAILURE_EVENT_NEEDLES = ['failed', 'invalid', 'blocked', 'denied', 'reuse', 'locked', 'revoked', 'error', 'skipped'];

    /**
     * @var list<string>
     */
    private const SECURITY_EVENT_NEEDLES = ['login', 'logout', 'password', 'verification', 'verified', 'approval', 'token', 'attachment_downloaded'];

    /**
     * @var list<string>
     */
    private const SECURITY_LOG_NAMES = [
        'api-auth',
        'api-parish-approvals',
        'superadmin-user-management',
        'admin-user-management',
        'parish-admin-management',
    ];

    public static function infolist(Schema $schema): Schema
    {
        return ActivityLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['causer', 'subject'])
            ->orderByDesc('created_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::applyFailureScope(static::getEloquentQuery()->where('created_at', '>=', now()->subHour()))->exists()
            ? 'danger'
            : (static::getEloquentQuery()->where('created_at', '>=', now()->subHour())->exists() ? 'warning' : 'success');
    }

    public static function relationLabel(?Model $model, ?string $type, string|int|null $id): string
    {
        if ($model instanceof Model) {
            $label = $model->getAttribute('full_name')
                ?? $model->getAttribute('name')
                ?? $model->getAttribute('title')
                ?? $model->getAttribute('subject')
                ?? null;

            $typeLabel = class_basename($type ?: $model::class);

            if (filled($label)) {
                return "{$typeLabel}: {$label} (#{$model->getKey()})";
            }

            return "{$typeLabel} #{$model->getKey()}";
        }

        if (blank($type) && blank($id)) {
            return 'Brak';
        }

        $typeLabel = class_basename((string) $type);

        return trim("{$typeLabel} #{$id}");
    }

    public static function relationUrl(?Model $model, ?string $type, string|int|null $id): ?string
    {
        if ($type === null && $model instanceof Model) {
            $type = $model::class;
        }

        $id ??= $model?->getKey();

        if (blank($type) || blank($id)) {
            return null;
        }

        return match ($type) {
            \App\Models\User::class => UserResource::getUrl('view', ['record' => $id]),
            \App\Models\Parish::class => ParishResource::getUrl('view', ['record' => $id]),
            \App\Models\OfficeConversation::class => OfficeConversationResource::getUrl('view', ['record' => $id]),
            \App\Models\NewsPost::class => NewsPostResource::getUrl('view', ['record' => $id]),
            \App\Models\Mass::class => MassResource::getUrl('view', ['record' => $id]),
            \App\Models\UserDevice::class => UserDeviceResource::getUrl('view', ['record' => $id]),
            \App\Models\AnnouncementSet::class => AnnouncementSetResource::getUrl('view', ['record' => $id]),
            \App\Models\NewsComment::class => NewsCommentResource::getUrl('edit', ['record' => $id]),
            \Spatie\MediaLibrary\MediaCollections\Models\Media::class => MediaResource::getUrl('view', ['record' => $id]),
            OfficeMessage::class => $model instanceof OfficeMessage
                ? OfficeConversationResource::getUrl('view', ['record' => $model->office_conversation_id])
                : null,
            default => null,
        };
    }

    public static function logNameLabel(?string $logName): string
    {
        $meta = static::LOG_NAME_META[$logName ?? ''] ?? null;

        if ($meta !== null) {
            return $meta['label'];
        }

        return filled($logName) ? str($logName)->replace('-', ' ')->headline()->toString() : static::LOG_NAME_META['default']['label'];
    }

    public static function logNameColor(?string $logName): string
    {
        return static::LOG_NAME_META[$logName ?? '']['color'] ?? static::LOG_NAME_META['default']['color'];
    }

    public static function eventLabel(?string $event): string
    {
        if (blank($event)) {
            return 'Brak eventu';
        }

        return static::EVENT_LABELS[$event] ?? str($event)->replace('_', ' ')->headline()->toString();
    }

    public static function eventColor(Activity $record): string
    {
        if (static::isFailureEvent($record)) {
            return 'danger';
        }

        if (static::isSecuritySensitive($record)) {
            return 'warning';
        }

        return static::CATEGORY_COLORS[static::eventCategoryKey($record)] ?? 'gray';
    }

    public static function eventCategoryLabel(Activity $record): string
    {
        $key = static::eventCategoryKey($record);

        return static::CATEGORY_LABELS[$key] ?? static::CATEGORY_LABELS['other'];
    }

    public static function eventCategoryColor(Activity $record): string
    {
        return static::CATEGORY_COLORS[static::eventCategoryKey($record)] ?? static::CATEGORY_COLORS['other'];
    }

    public static function eventCategoryKey(Activity $record): string
    {
        return match (true) {
            in_array($record->log_name, ['api-auth'], true) => 'api_auth',
            in_array($record->log_name, ['api-profile', 'admin-user-management', 'superadmin-user-management', 'parish-admin-management'], true) => 'user_profile',
            in_array($record->log_name, ['api-parish-approvals'], true) => 'parish_approvals',
            in_array($record->log_name, ['api-office', 'office-conversations'], true) => 'office',
            in_array($record->log_name, ['news-posts', 'admin-announcement-management', 'announcements-ai', 'superadmin-communication-center'], true) => 'content',
            in_array($record->log_name, ['admin-mass-management'], true) => 'liturgy',
            in_array($record->log_name, ['scheduler-reports', 'superadmin-daily-reports', 'parish-weekly-digests'], true) => 'system',
            static::isSecuritySensitive($record) => 'security',
            default => 'other',
        };
    }

    public static function outcomeLabel(Activity $record): string
    {
        if (static::isFailureEvent($record)) {
            return 'Niepowodzenie';
        }

        if (static::isSecuritySensitive($record)) {
            return 'Wrazliwe';
        }

        return 'OK';
    }

    public static function outcomeColor(Activity $record): string
    {
        return match (static::outcomeLabel($record)) {
            'Niepowodzenie' => 'danger',
            'Wrazliwe' => 'warning',
            default => 'success',
        };
    }

    public static function isFailureEvent(Activity|string|null $recordOrEvent): bool
    {
        $event = $recordOrEvent instanceof Activity ? ($recordOrEvent->event ?? '') : (string) $recordOrEvent;
        $event = str($event)->lower()->toString();

        foreach (static::FAILURE_EVENT_NEEDLES as $needle) {
            if (str_contains($event, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function isSecuritySensitive(Activity $record): bool
    {
        if (in_array($record->log_name, static::SECURITY_LOG_NAMES, true)) {
            return true;
        }

        $event = str((string) $record->event)->lower()->toString();

        foreach (static::SECURITY_EVENT_NEEDLES as $needle) {
            if (str_contains($event, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function propertiesPretty(Activity $record): string
    {
        $properties = static::propertiesArray($record);

        if ($properties === []) {
            return '{}';
        }

        $encoded = json_encode(
            $properties,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($encoded) ? $encoded : '{}';
    }

    public static function propertiesPreview(Activity $record, int $limit = 120): string
    {
        return (string) str(static::propertiesPretty($record))
            ->replace("\n", ' ')
            ->limit($limit);
    }

    public static function changesPretty(Activity $record): string
    {
        $changes = $record->changes()->toArray();

        if ($changes === []) {
            return '{}';
        }

        $encoded = json_encode(
            $changes,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return is_string($encoded) ? $encoded : '{}';
    }

    public static function contextSummary(Activity $record): string
    {
        $segments = [];

        if ($parishId = static::contextValue($record, 'parish_id')) {
            $segments[] = "Parafia #{$parishId}";
        }

        if ($deviceId = static::contextValue($record, 'device_id')) {
            $segments[] = 'Device '.str($deviceId)->limit(18)->toString();
        }

        if ($ip = static::contextValue($record, 'ip_address')) {
            $segments[] = "IP {$ip}";
        }

        if ($platform = static::contextValue($record, 'platform')) {
            $segments[] = str($platform)->headline()->toString();
        }

        if ($recipient = static::contextValue($record, 'recipient_user_id')) {
            $segments[] = "Odbiorca #{$recipient}";
        }

        if ($batchUuid = $record->batch_uuid) {
            $segments[] = 'Batch '.str($batchUuid)->limit(12)->toString();
        }

        return $segments !== [] ? implode(' · ', $segments) : 'Brak dodatkowego kontekstu';
    }

    /**
     * @return array<string, string>
     */
    public static function extractedContext(Activity $record): array
    {
        $keys = [
            'parish_id' => 'Parafia ID',
            'device_id' => 'Device ID',
            'platform' => 'Platforma',
            'app_version' => 'Wersja aplikacji',
            'ip_address' => 'IP',
            'user_agent' => 'User agent',
            'login' => 'Login',
            'email' => 'Email',
            'recipient_user_id' => 'Odbiorca ID',
            'approval_code_length' => 'Dlugosc kodu',
            'verification_method' => 'Metoda weryfikacji',
            'home_parish_id' => 'Parafia domyslna',
        ];

        $context = [];

        foreach ($keys as $key => $label) {
            $value = static::contextValue($record, $key);

            if ($value !== null && $value !== '') {
                $context[$label] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
            }
        }

        return $context;
    }

    public static function contextPretty(Activity $record): string
    {
        $context = static::extractedContext($record);

        if ($context === []) {
            return '{}';
        }

        $encoded = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return is_string($encoded) ? $encoded : '{}';
    }

    public static function formattedJsonBlock(string $json): string
    {
        return '<pre style="white-space: pre-wrap; font-size: 12px; line-height: 1.5;">'.e($json).'</pre>';
    }

    public static function applyFailureScope(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            foreach (static::FAILURE_EVENT_NEEDLES as $index => $needle) {
                if ($index === 0) {
                    $query->where('event', 'like', "%{$needle}%");

                    continue;
                }

                $query->orWhere('event', 'like', "%{$needle}%");
            }
        });
    }

    public static function applySecurityScope(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereIn('log_name', static::SECURITY_LOG_NAMES)
                ->orWhere(function (Builder $eventQuery): void {
                    foreach (static::SECURITY_EVENT_NEEDLES as $index => $needle) {
                        if ($index === 0) {
                            $eventQuery->where('event', 'like', "%{$needle}%");

                            continue;
                        }

                        $eventQuery->orWhere('event', 'like', "%{$needle}%");
                    }
                });
        });
    }

    public static function applyLogNamesScope(Builder $query, array $logNames): Builder
    {
        return $query->whereIn('log_name', $logNames);
    }

    public static function applyParishContextScope(Builder $query, string|int $parishId): Builder
    {
        $quotedParishId = str_replace('"', '\\"', (string) $parishId);

        return $query->where(function (Builder $query) use ($quotedParishId): void {
            $query->where('properties', 'like', "%\"parish_id\":{$quotedParishId}%")
                ->orWhere('properties', 'like', "%\"parish_id\":\"{$quotedParishId}\"%");
        });
    }

    private static function contextValue(Activity $record, string $key): mixed
    {
        $properties = static::propertiesArray($record);
        $changes = $record->changes()->toArray();

        return static::findValueRecursive($properties, $key)
            ?? static::findValueRecursive($changes, $key);
    }

    /**
     * @return array<string, mixed>
     */
    private static function propertiesArray(Activity $record): array
    {
        $properties = $record->properties;

        if ($properties instanceof Collection) {
            return $properties->toArray();
        }

        if (is_array($properties)) {
            return $properties;
        }

        return [];
    }

    private static function findValueRecursive(array $payload, string $key): mixed
    {
        foreach ($payload as $currentKey => $value) {
            if ((string) $currentKey === $key) {
                return $value;
            }

            if (is_array($value)) {
                $nested = static::findValueRecursive($value, $key);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
