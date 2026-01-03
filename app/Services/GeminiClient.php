<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiClient
{
    public function generateText(string $prompt): string
    {
        $apiKey = config('gemini.api_key');
        $model = config('gemini.model');

        if (! $apiKey) {
            throw new RuntimeException('Brak GEMINI_API_KEY w konfiguracji.');
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

        $response = Http::timeout(config('gemini.timeout'))
            ->withQueryParameters(['key' => $apiKey])
            ->post($url, [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                // Opcjonalnie: kontrola stylu
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 600,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API error: '.$response->status().' '.$response->body());
        }

        // Zgodnie z formatem odpowiedzi generateContent:
        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new RuntimeException('Gemini API: brak tekstu w odpowiedzi.');
        }

        return trim($text);
    }
}
