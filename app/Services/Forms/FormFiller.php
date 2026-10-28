<?php

namespace App\Services\Forms;

use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use Throwable;

class FormFiller
{
    public function __construct(
        private readonly LlmClient $llm,
        private readonly FakeLlmClient $fallback,
    ) {}

    /** @return array{form: array<string, string|null>, errors: array<string, list<string>>, valid: bool} */
    public function fill(string $message): array
    {
        try {
            $fields = $this->llm->extract($message);
        } catch (Throwable $exception) {
            report($exception);
            $fields = $this->fallback->extract($message);
        }

        $form = [
            'customer' => $this->stringOrNull($fields['customer'] ?? null),
            'email' => $this->stringOrNull($fields['email'] ?? null),
            'product' => $this->stringOrNull($fields['product'] ?? null),
            'urgency' => $this->normalizeUrgency($fields['urgency'] ?? null),
            'summary' => $this->stringOrNull($fields['summary'] ?? null),
            'raw_text' => $message,
        ];

        $errors = $this->validateFields($form);

        return [
            'form' => $form,
            'errors' => $errors,
            'valid' => $errors === [],
        ];
    }

    /** @param array<string, mixed> $fields
     * @return array<string, list<string>>
     */
    public function validateFields(array $fields): array
    {
        $errors = [];

        foreach (['customer' => 'Customer', 'email' => 'Email', 'product' => 'Product', 'summary' => 'Summary'] as $field => $label) {
            if (! is_string($fields[$field] ?? null) || trim($fields[$field]) === '') {
                $errors[$field][] = "{$label} is required.";
            }
        }

        if (is_string($fields['email'] ?? null) && trim($fields['email']) !== '' && ! filter_var($fields['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Enter a valid email address.';
        }

        if (! in_array($fields['urgency'] ?? null, ['low', 'normal', 'high'], true)) {
            $errors['urgency'][] = 'Choose low, normal, or high urgency.';
        }

        return $errors;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 1000);
    }

    private function normalizeUrgency(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $urgency = mb_strtolower(trim($value));

        return in_array($urgency, ['low', 'normal', 'high'], true) ? $urgency : null;
    }
}
