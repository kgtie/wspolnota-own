<?php

namespace App\Support\Gemini;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    /**
     * @param  array<int, array<string, mixed>>  $parts
     */
    public function generate(array $parts, ?string $model = null): string
    {
        $apiKey = (string) config('gemini.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Brak GEMINI_API_KEY w konfiguracji.');
        }

        $resolvedModel = $model ?: (string) config('gemini.model', 'gemini-2.0-flash');
        $timeout = (int) config('gemini.timeout', 30);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $apiKey,
            ])
            ->post(
                sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent', $resolvedModel),
                [
                    'contents' => [
                        [
                            'parts' => $parts,
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.2,
                        'topP' => 0.9,
                        'maxOutputTokens' => 250,
                    ],
                ],
            );

        if (! $response->successful()) {
            $status = $response->status();
            $body = $response->json();
            $errorMessage = is_array($body) ? (string) data_get($body, 'error.message', '') : '';

            throw new RuntimeException("Gemini API zwrocilo blad HTTP {$status}. {$errorMessage}");
        }

        $candidateText = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        if (trim($candidateText) === '') {
            throw new RuntimeException('Gemini API nie zwrocilo tresci odpowiedzi.');
        }

        return trim($candidateText);
    }
}
