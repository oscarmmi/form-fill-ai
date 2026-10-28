<?php

namespace App\Http\Controllers;

use App\Models\DemoMessage;
use App\Models\IntakeForm;
use App\Services\Forms\FormFiller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IntakeFormController
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'examples' => DemoMessage::query()->orderBy('id')->get(['id', 'title', 'raw_text']),
            'recentForms' => IntakeForm::query()->where('valid', true)->latest()->limit(4)->get([
                'id', 'customer', 'email', 'product', 'urgency', 'summary', 'created_at',
            ]),
            'llmMode' => filled(config('services.gemini.key')) ? 'Gemini · 12s timeout' : 'Local extraction · no API key',
        ]);
    }

    public function preview(Request $request, FormFiller $filler): JsonResponse
    {
        $data = $request->validate([
            'raw_text' => ['required', 'string', 'max:10000'],
        ]);

        return response()->json($filler->fill($data['raw_text']));
    }

    public function store(Request $request, FormFiller $filler): JsonResponse
    {
        $data = $request->validate([
            'customer' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'product' => ['required', 'string', 'max:255'],
            'urgency' => ['required', 'in:low,normal,high'],
            'summary' => ['required', 'string', 'max:1000'],
            'raw_text' => ['required', 'string', 'max:10000'],
        ]);

        $errors = $filler->validateFields($data);
        if ($errors !== []) {
            return response()->json(['message' => 'Please correct the form fields.', 'errors' => $errors], 422);
        }

        $intakeForm = IntakeForm::query()->create([
            ...$data,
            'valid' => true,
            'errors' => [],
        ]);

        return response()->json([
            'message' => 'Intake form saved.',
            'intakeForm' => $intakeForm->only(['id', 'customer', 'email', 'product', 'urgency', 'summary', 'created_at']),
        ], 201);
    }
}
