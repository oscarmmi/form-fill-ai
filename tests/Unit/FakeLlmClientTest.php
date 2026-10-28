<?php

use App\Services\Llm\FakeLlmClient;

it('extracts known intake fields and urgency without a remote model', function () {
    $fields = (new FakeLlmClient)->extract(
        'Hi, I’m Maya Chen (maya.chen@example.test). My Beacon Sensor is offline and blocking work today.',
    );

    expect($fields['customer'])->toBe('Maya Chen')
        ->and($fields['email'])->toBe('maya.chen@example.test')
        ->and($fields['product'])->toBe('Beacon Sensor')
        ->and($fields['urgency'])->toBe('high');
});
