<?php

use App\Http\Controllers\IntakeFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IntakeFormController::class, 'index'])->name('home');
Route::post('/intake/preview', [IntakeFormController::class, 'preview'])->name('intake.preview');
Route::post('/intake-forms', [IntakeFormController::class, 'store'])->name('intake-forms.store');
