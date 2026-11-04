<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Seed the 9 roles matching the frontend auth system.
     */
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'superadmin',
                'label'       => 'SUPERADMIN',
                'description' => 'Full access to all foundations, schools, and system settings.',
            ],
            [
                'name'        => 'admin_yayasan',
                'label'       => 'ADMIN YAYASAN',
                'description' => 'Manages all schools and staff under a specific foundation.',
            ],
            [
                'name'        => 'kepala_sekolah',
                'label'       => 'KEPALA SEKOLAH',
                'description' => 'School principal with oversight of school operations.',
            ],
            [
                'name'        => 'admin_sekolah',
                'label'       => 'ADMIN SEKOLAH',
                'description' => 'School administrator handling day-to-day school management.',
            ],
            [
                'name'        => 'tata_usaha',
                'label'       => 'TATA USAHA',
                'description' => 'School administrative staff for document and data processing.',
            ],
            [
                'name'        => 'guru',
                'label'       => 'GURU',
                'description' => 'Teacher with access to academic features like grading and attendance.',
            ],
            [
                'name'        => 'siswa',
                'label'       => 'SISWA',
                'description' => 'Student with access to view grades, schedule, and assignments.',
            ],
            [
                'name'        => 'orang_tua',
                'label'       => 'ORANG TUA/WALI',
                'description' => 'Parent/guardian with access to monitor their children\'s progress.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                $role
            );
        }
    }
}
