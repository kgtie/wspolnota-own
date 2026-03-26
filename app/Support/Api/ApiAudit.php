<?php

namespace App\Support\Api;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Ujednolica zapisy audytowe dla API v1, aby krytyczne zdarzenia biznesowe
 * trafiały do activity_log w spójnym formacie i z przewidywalnymi nazwami.
 */
final class ApiAudit
{
    public static function log(
        string $logName,
        string $event,
        string $message,
        ?Authenticatable $causer = null,
        ?Model $subject = null,
        array $properties = [],
    ): void {
        $activity = activity($logName)->event($event);

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        if ($subject !== null) {
            $activity->performedOn($subject);
        }

        if ($properties !== []) {
            $activity->withProperties($properties);
        }

        $activity->log($message);
    }
}
