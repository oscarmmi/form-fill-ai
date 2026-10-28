<?php

use App\Models\DemoMessage;
use App\Models\IntakeForm;
use App\Services\Forms\FormFiller;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;

it('fills customer, email, and product from a complete support message', function () {
    $response = $this->postJson(route('intake.preview'), [
        'raw_text' => 'Hi, I’m Maya Chen (maya.chen@example.test). My Beacon Sensor stopped syncing after the update.',
    ]);

    $response->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('form.customer', 'Maya Chen')
        ->assertJsonPath('form.email', 'maya.chen@example.test')
        ->assertJsonPath('form.product', 'Beacon Sensor');
});

it('marks an intake invalid when an email is missing', function () {
    $response = $this->postJson(route('intake.preview'), [
        'raw_text' => 'Hi, I’m Riley Morgan. Our Atlas Reader will not connect to the office network.',
    ]);

    $response->assertOk()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('form.email', null)
        ->assertJsonPath('errors.email.0', 'Email is required.');
});

it('marks an invalid email format as an error', function () {
    $this->app->instance(LlmClient::class, new class implements LlmClient
    {
        public function extract(string $message): array
        {
            return [
                'customer' => 'Maya Chen',
                'email' => 'maya-at-example',
                'product' => 'Beacon Sensor',
                'urgency' => 'normal',
                'summary' => 'The sensor stopped syncing.',
            ];
        }
    });

    $this->postJson(route('intake.preview'), ['raw_text' => 'My Beacon Sensor stopped syncing.'])
        ->assertOk()
        ->assertJsonPath('valid', false)
        ->assertJsonPath('errors.email.0', 'Enter a valid email address.');
});

it('binds the fake llm when no gemini api key is configured', function () {
    config(['services.gemini.key' => null]);

    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

it('falls back to local extraction when the configured provider fails', function () {
    $this->app->instance(LlmClient::class, new class implements LlmClient
    {
        public function extract(string $message): array
        {
            throw new RuntimeException('Provider unavailable.');
        }
    });

    $result = app(FormFiller::class)->fill(
        'Hi, I’m Maya Chen (maya.chen@example.test). My Beacon Sensor stopped syncing.',
    );

    expect($result['valid'])->toBeTrue()
        ->and($result['form']['customer'])->toBe('Maya Chen')
        ->and($result['form']['product'])->toBe('Beacon Sensor');
});

it('allows a guest to preview an intake over http', function () {
    $this->postJson(route('intake.preview'), [
        'raw_text' => 'My name is Avery Patel, avery.patel@example.test. I have a question about Dockside reports.',
    ])->assertOk();
});

it('only saves a confirmed and valid intake form', function () {
    $this->postJson(route('intake-forms.store'), [
        'customer' => 'Maya Chen',
        'email' => 'maya.chen@example.test',
        'product' => 'Beacon Sensor',
        'urgency' => 'normal',
        'summary' => 'The sensor stopped syncing after an update.',
        'raw_text' => 'Hi, I’m Maya Chen (maya.chen@example.test). My Beacon Sensor stopped syncing after the update.',
    ])->assertCreated()
        ->assertJsonPath('intakeForm.customer', 'Maya Chen');

    expect(IntakeForm::query()->count())->toBe(1)
        ->and(IntakeForm::query()->first()->valid)->toBeTrue();
});

it('provides four seeded fictional messages', function () {
    $this->seed();

    expect(DemoMessage::query()->count())->toBe(4)
        ->and(DemoMessage::query()->where('raw_text', 'not like', '%@%')->exists())->toBeTrue();
});
