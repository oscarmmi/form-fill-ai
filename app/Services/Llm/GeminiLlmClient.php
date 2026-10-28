<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiLlmClient implements LlmClient
{
    /** @return array<string, mixed> */
    public function extract(string $message): array
    {
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $response = Http::timeout((int) config('services.gemini.timeout', 12))
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=".config('services.gemini.key'), [
                'contents' => [[
                    'parts' => [[
                        'text' => "Extract a Harbor Desk support intake form from the message below. Return only a JSON object with string fields customer, email, product, urgency, and summary. Use an empty string for unknown values. urgency must be low, normal, or high. Do not invent an email address or customer name.\n\nMessage:\n{$message}",
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'customer' => ['type' => 'STRING'],
                            'email' => ['type' => 'STRING'],
                            'product' => ['type' => 'STRING'],
                            'urgency' => ['type' => 'STRING', 'enum' => ['low', 'normal', 'high']],
                            'summary' => ['type' => 'STRING'],
                        ],
                        'required' => ['customer', 'email', 'product', 'urgency', 'summary'],
                    ],
                ],
            ])
            ->throw();

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned an invalid intake form response.');
        }

        return $decoded;
    }
}
