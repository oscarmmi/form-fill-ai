<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    /** @return array<string, mixed> */
    public function extract(string $message): array
    {
        $email = preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $message, $matches)
            ? $matches[0]
            : null;

        $customer = null;
        if (preg_match('/\b(?:my name is|i am|i’m|i\'m|this is)\s+([\p{L}][\p{L}\'’-]*(?:\s+[\p{L}][\p{L}\'’-]*){0,2})/iu', $message, $matches)) {
            $customer = trim($matches[1]);
        }

        $product = $this->findProduct($message);
        $urgency = $this->findUrgency($message);

        return [
            'customer' => $customer,
            'email' => $email,
            'product' => $product,
            'urgency' => $urgency,
            'summary' => mb_substr(trim(preg_replace('/\s+/', ' ', $message) ?? $message), 0, 240),
        ];
    }

    private function findProduct(string $message): ?string
    {
        $products = [
            'harbor desk mobile' => 'Harbor Desk Mobile',
            'atlas reader' => 'Atlas Reader',
            'beacon sensor' => 'Beacon Sensor',
            'beacon' => 'Beacon',
            'dockside' => 'Dockside',
        ];

        foreach ($products as $keyword => $name) {
            if (str_contains(mb_strtolower($message), $keyword)) {
                return $name;
            }
        }

        return null;
    }

    private function findUrgency(string $message): string
    {
        $text = mb_strtolower($message);

        foreach (['urgent', 'asap', 'outage', 'today', 'blocked', 'cannot work', "can't work"] as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'high';
            }
        }

        foreach (['when you can', 'not urgent', 'minor', 'no rush'] as $keyword) {
            if (str_contains($text, $keyword)) {
                return 'low';
            }
        }

        return 'normal';
    }
}
