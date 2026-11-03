<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->foreignId('classroom_id')->nullable()->change();
            $table->foreignId('academic_year_id')->nullable()->change();
        });

        // Backfill default active academic year dynamically based on current date for any school missing one
        $schoolIdsWithoutAy = DB::table('schools')
            ->whereNotIn('id', DB::table('academic_years')->select('school_id')->whereNotNull('school_id'))
            ->pluck('id');

        $now = now();
        $defaults = \App\Models\AcademicYear::defaultAttributesForDate($now);
        foreach ($schoolIdsWithoutAy as $schoolId) {
            DB::table('academic_years')->insert(array_merge($defaults, [
                'school_id'  => $schoolId,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to preserve nullable assignments
    }
};

