<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntakeForm extends Model
{
    protected $fillable = [
        'customer',
        'email',
        'product',
        'urgency',
        'summary',
        'raw_text',
        'valid',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'valid' => 'boolean',
            'errors' => 'array',
        ];
    }
}
