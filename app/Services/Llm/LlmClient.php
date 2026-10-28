<?php

namespace App\Services\Llm;

interface LlmClient
{
    /** @return array<string, mixed> */
    public function extract(string $message): array;
}
