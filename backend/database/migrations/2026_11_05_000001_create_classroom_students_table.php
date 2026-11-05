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
        Schema::create('classroom_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->cascadeOnDelete();
            $table->string('status', 30)->default('active'); // active, promoted, retained, transferred, graduated
            $table->text('notes')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();

            $table->unique(['classroom_id', 'student_profile_id'], 'cr_student_unique');
            $table->index(['student_profile_id', 'status'], 'cr_student_status_idx');
            $table->index(['academic_year_id', 'status'], 'cr_student_year_idx');
        });

        // Backfill data dari student_profiles yang saat ini sudah memiliki classroom_id
        $profiles = DB::table('student_profiles')
            ->join('classrooms', 'student_profiles.classroom_id', '=', 'classrooms.id')
            ->select('student_profiles.id as student_profile_id', 'classrooms.id as classroom_id', 'classrooms.academic_year_id')
            ->whereNotNull('student_profiles.classroom_id')
            ->get();

        $now = now();
        $records = [];
        foreach ($profiles as $p) {
            $records[] = [
                'classroom_id'       => $p->classroom_id,
                'student_profile_id' => $p->student_profile_id,
                'academic_year_id'   => $p->academic_year_id,
                'status'             => 'active',
                'start_date'         => $now->toDateString(),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        if (!empty($records)) {
            DB::table('classroom_students')->insert($records);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_students');
    }
};

