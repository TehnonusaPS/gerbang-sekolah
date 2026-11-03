<?php

use App\Models\TeacherProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('student_profiles', 'religion')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->string('religion')->nullable()->after('gender');
            });
        }

        // Normalize existing legacy codes in teacher_profiles & parent_profiles
        foreach (TeacherProfile::AGAMA_MAP as $code => $label) {
            DB::table('teacher_profiles')->where('religion', $code)->update(['religion' => $label]);
            DB::table('parent_profiles')->where('religion', $code)->update(['religion' => $label]);
        }
        foreach (TeacherProfile::MARITAL_MAP as $code => $label) {
            DB::table('teacher_profiles')->where('marital_status', $code)->update(['marital_status' => $label]);
            DB::table('parent_profiles')->where('marital_status', $code)->update(['marital_status' => $label]);
        }
        foreach (TeacherProfile::EDUCATION_MAP as $code => $label) {
            DB::table('teacher_profiles')->where('last_education', $code)->update(['last_education' => $label]);
            DB::table('parent_profiles')->where('last_education', $code)->update(['last_education' => $label]);
        }
        foreach (TeacherProfile::POSITION_MAP as $code => $label) {
            DB::table('teacher_profiles')->where('position', $code)->update(['position' => $label]);
        }
        foreach (TeacherProfile::EMPLOYMENT_MAP as $code => $label) {
            DB::table('teacher_profiles')->where('employment_status', $code)->update(['employment_status' => $label]);
        }
        foreach (TeacherProfile::OCCUPATION_MAP as $code => $label) {
            DB::table('parent_profiles')->where('occupation', $code)->update(['occupation' => $label]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('student_profiles', 'religion')) {
            Schema::table('student_profiles', function (Blueprint $table) {
                $table->dropColumn('religion');
            });
        }
    }
};
