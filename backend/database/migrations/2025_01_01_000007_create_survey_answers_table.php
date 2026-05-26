<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_question_id')->constrained()->cascadeOnDelete();
            $table->text('answer');
            $table->decimal('score', 4, 2)->nullable();
            $table->date('check_in_date');
            $table->timestamps();

            $table->index(['company_id', 'check_in_date']);
            $table->index(['employee_id', 'check_in_date']);
            $table->index(['company_id', 'employee_id', 'check_in_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};
