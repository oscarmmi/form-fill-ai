<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_forms', function (Blueprint $table) {
            $table->id();
            $table->string('customer')->nullable();
            $table->string('email')->nullable();
            $table->string('product')->nullable();
            $table->string('urgency')->nullable();
            $table->text('summary')->nullable();
            $table->text('raw_text');
            $table->boolean('valid')->default(false);
            $table->json('errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_forms');
    }
};
