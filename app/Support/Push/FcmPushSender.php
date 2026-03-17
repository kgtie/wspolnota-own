<?php

namespace App\Support\Push;

use App\Contracts\PushSender;
use App\Settings\FcmSettings;
use Google\Auth\Credentials\ServiceAccountCredentials;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

class FcmPushSender implements PushSender
{
    public function __construct(
        private readonly FcmSettings $settings,
        private readonly Client $http = new Client,
    ) {}

    public function send(PushMessage $message, bool $validateOnly = false): PushSendResult
    {
        if (! $this->settings->enabled) {
            return PushSendResult::failure('FCM_DISABLED', 'FCM jest wylaczone w ustawieniach.');
        }

        $projectId = trim($this->settings->resolvedProjectId());
        $credentialsJson = $this->settings->decodedServiceAccount();

        if ($projectId === '') {
            return PushSendResult::failure('FCM_PROJECT_ID_MISSING', 'Brak Firebase project_id.');
        }

        if ($credentialsJson === []) {
            return PushSendResult::failure('FCM_CREDENTIALS_MISSING', 'Brak service account JSON.');
        }

        $accessToken = $this->resolveAccessToken($credentialsJson);

        $payload = [
            'validate_only' => $validateOnly,
            'message' => $this->buildMessagePayload($message),
        ];

        try {
            $response = $this->http->post(
                "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$accessToken,
                        'Content-Type' => 'application/json; charset=utf-8',
                    ],
                    'json' => $payload,
                    'timeout' => max(2, (int) $this->settings->request_timeout_seconds),
                ],
            );

            $decoded = json_decode((string) $response->getBody(), true);

            return PushSendResult::success(
                messageId: is_array($decoded) ? (string) ($decoded['name'] ?? '') : null,
                response: is_array($decoded) ? $decoded : [],
            );
        } catch (ClientException $exception) {
            $body = json_decode((string) $exception->getResponse()->getBody(), true);
            $errorCode = (string) (data_get($body, 'error.status') ?? 'FCM_HTTP_ERROR');
            $errorMessage = (string) (data_get($body, 'error.message') ?? $exception->getMessage());
            $firebaseError = $this->extractFirebaseErrorCode($body);

            return PushSendResult::failure(
                errorCode: $firebaseError ?: $errorCode,
                errorMessage: $errorMessage,
                shouldDisableDevice: in_array($firebaseError, ['UNREGISTERED', 'INVALID_ARGUMENT'], true),
                response: is_array($body) ? $body : [],
            );
        } catch (Throwable $exception) {
            return PushSendResult::failure(
                errorCode: 'FCM_SEND_EXCEPTION',
                errorMessage: $exception->getMessage(),
            );
        }
    }

    /**
     * @param  array<string,mixed>  $credentialsJson
     */
    private function resolveAccessToken(array $credentialsJson): string
    {
        $cacheKey = 'fcm.oauth.'.md5(json_encode($credentialsJson, JSON_UNESCAPED_SLASHES) ?: '');

        return (string) Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentialsJson): string {
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $credentialsJson,
            );

            $token = $credentials->fetchAuthToken();
            $accessToken = (string) ($token['access_token'] ?? '');

            if ($accessToken === '') {
                throw new RuntimeException('Nie udalo sie pobrac access tokena Google.');
            }

            return $accessToken;
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildMessagePayload(PushMessage $message): array
    {
        $ttl = max(60, (int) ($message->ttlSeconds ?? 3600));
        $notification = [
            'title' => $message->title,
            'body' => $message->body,
        ];

        return [
            'token' => $message->token,
            'notification' => $notification,
            'data' => $message->data,
            'android' => array_filter([
                'priority' => $message->priority === 'high' ? 'HIGH' : 'NORMAL',
                'ttl' => "{$ttl}s",
                'collapse_key' => $message->collapseKey,
                'notification' => [
                    'channel_id' => 'default',
                    'sound' => 'default',
                ],
            ], static fn (mixed $value): bool => $value !== null),
            'apns' => [
                'headers' => array_filter([
                    'apns-priority' => $message->priority === 'high' ? '10' : '5',
                    'apns-collapse-id' => $message->collapseKey,
                ], static fn (mixed $value): bool => $value !== null),
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $message->title,
                            'body' => $message->body,
                        ],
                        'sound' => 'default',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string,mixed>|null  $body
     */
    private function extractFirebaseErrorCode(?array $body): ?string
    {
        if (! is_array($body)) {
            return null;
        }

        $details = data_get($body, 'error.details');

        if (! is_array($details)) {
            return null;
        }

        foreach ($details as $detail) {
            $errorCode = data_get($detail, 'errorCode');

            if (is_string($errorCode) && $errorCode !== '') {
                return $errorCode;
            }
        }

        return null;
    }
}
