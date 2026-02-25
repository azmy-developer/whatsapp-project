<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('phones')->nullable();
            $table->string('primary_phone')->nullable()->index();
            $table->string('email')->nullable();
            $table->string('tags')->nullable();
            $table->text('notes')->nullable();
            $table->longText('ai_summary')->nullable();
            $table->timestamp('ai_summary_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};

