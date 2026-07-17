<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Parent entity for a single employee's daily check-in. The unique
        // (employee_id, check_in_date) is what actually prevents duplicate
        // check-ins for the same day.
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('survey_id')->nullable()->constrained()->nullOnDelete();
            $table->date('check_in_date');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'check_in_date']);
            $table->index(['company_id', 'check_in_date']);
        });

        // Add the FK column nullable first so the backfill can populate it
        // before we enforce NOT NULL.
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('check_in_id')->nullable()->after('id');
        });

        $this->backfillCheckIns();

        Schema::table('survey_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('check_in_id')->nullable(false)->change();
            $table->foreign('check_in_id')->references('id')->on('check_ins')->cascadeOnDelete();
            // One row per question within a check-in (no duplicate answers).
            $table->unique(['check_in_id', 'survey_question_id']);
        });
    }

    public function down(): void
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropForeign(['check_in_id']);
            $table->dropUnique(['check_in_id', 'survey_question_id']);
            $table->dropColumn('check_in_id');
        });

        Schema::dropIfExists('check_ins');
    }

    /**
     * Group existing answers into one check_in per (employee, day) and link them,
     * so legacy data satisfies the new NOT NULL FK and unique constraints.
     */
    private function backfillCheckIns(): void
    {
        $groups = DB::table('survey_answers')
            ->select('company_id', 'employee_id', 'check_in_date')
            ->distinct()
            ->get();

        foreach ($groups as $group) {
            $checkInId = DB::table('check_ins')->insertGetId([
                'company_id' => $group->company_id,
                'employee_id' => $group->employee_id,
                'check_in_date' => $group->check_in_date,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('survey_answers')
                ->where('company_id', $group->company_id)
                ->where('employee_id', $group->employee_id)
                ->where('check_in_date', $group->check_in_date)
                ->update(['check_in_id' => $checkInId]);
        }
    }
};
