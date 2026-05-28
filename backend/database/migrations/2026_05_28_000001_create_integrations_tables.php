<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_providers', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('auth_type', 32)->default('token');
            $table->json('config_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('company_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider_slug');
            $table->string('status', 32)->default('disconnected');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'provider_slug']);
            $table->foreign('provider_slug')->references('slug')->on('integration_providers');
        });

        Schema::create('employee_integration_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained()->cascadeOnDelete();
            $table->string('external_user_id')->nullable();
            $table->string('external_login')->nullable();
            $table->string('external_email')->nullable();
            $table->timestamps();

            $table->unique(['company_integration_id', 'employee_id']);
        });

        Schema::create('work_progress_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider_slug');
            $table->date('period_from');
            $table->date('period_to');
            $table->json('payload');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['company_id', 'provider_slug', 'period_from', 'period_to'], 'work_progress_snapshots_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_progress_snapshots');
        Schema::dropIfExists('employee_integration_identities');
        Schema::dropIfExists('company_integrations');
        Schema::dropIfExists('integration_providers');
    }
};
