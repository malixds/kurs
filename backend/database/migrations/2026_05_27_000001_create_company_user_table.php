<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('admin');
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
        });

        $users = DB::table('users')
            ->whereNotNull('company_id')
            ->get(['id', 'company_id', 'role']);

        foreach ($users as $user) {
            DB::table('company_user')->insertOrIgnore([
                'user_id' => $user->id,
                'company_id' => $user->company_id,
                'role' => $user->role ?? 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
