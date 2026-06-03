<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 32);
            $table->string('prompt_id', 64);
            $table->string('prompt_label');
            $table->date('period_from');
            $table->date('period_to');
            $table->json('provider_slugs')->nullable();
            $table->text('recommendation');
            $table->json('summary')->nullable();
            $table->string('llm_model')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_recommendations');
    }
};
