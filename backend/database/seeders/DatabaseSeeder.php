<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Foundation;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\Extracurricular;
use App\Models\TeacherSubjectAssignment;
use App\Models\SubjectMaterial;
use App\Models\Assessment;
use App\Models\AssessmentScore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ─────────────────────────────────────────────
        //  1. Seed Roles
        // ─────────────────────────────────────────────
        $this->call(RoleSeeder::class);
        $roles = Role::all()->keyBy('name');

        $defaultPassword = Hash::make('123456');

        // ─────────────────────────────────────────────
        //  2. Seed 2 Foundations (Yayasan)
        // ─────────────────────────────────────────────
        $foundationA = Foundation::create([
            'code'             => 'Y0001',
            'name'             => 'Yayasan Nusantara Pintar',
            'established_date' => '2010-01-15',
            'status'           => 'active',
            'address'          => 'Jl. Pendidikan No. 123, Jakarta Timur',
            'email'            => 'info@nusantarapintar.id',
            'phone'            => '021-12345678',
            'website'          => 'https://nusantarapintar.id',
            'deed_number'      => 'AHU-12345.AH.01.04',
            'deed_date'        => '2010-01-15',
            'decree_number'    => 'SK-001/YNP/2010',
            'decree_date'      => '2010-02-01',
        ]);

        $foundationB = Foundation::create([
            'code'             => 'Y0002',
            'name'             => 'Yayasan Harapan Bangsa',
            'established_date' => '2015-05-10',
            'status'           => 'active',
            'address'          => 'Jl. Harapan No. 88, Jakarta Selatan',
            'email'            => 'info@harapanbangsa.or.id',
            'phone'            => '021-87650000',
            'website'          => 'https://harapanbangsa.or.id',
            'deed_number'      => 'AHU-54321.AH.01.04',
            'deed_date'        => '2015-05-10',
            'decree_number'    => 'SK-002/YHB/2015',
            'decree_date'      => '2015-06-01',
        ]);

        // ─────────────────────────────────────────────
        //  3. Seed 4 Schools (2 under Yayasan A, 2 under Yayasan B)
        // ─────────────────────────────────────────────
        // School 1 (Yayasan A - SD)
        $school1 = School::create([
            'foundation_id'        => $foundationA->id,
            'name'                 => 'SD Nusantara Pintar Bekasi',
            'npsn'                 => '20100001',
            'level'                => 'SD',
            'established_date'     => '2012-07-01',
            'status'               => 'active',
            'address'              => 'Jl. Raya Bekasi No. 45, Bekasi',
            'email'                => 'info@sdnpbekasi.sch.id',
            'phone'                => '021-87654321',
            'website'              => 'https://sdnpbekasi.sch.id',
            'decree_number'        => 'SK-002/SDNP/2012',
            'decree_date'          => '2012-06-15',
            'permit_number'        => 'IZIN-001/2012',
            'permit_date'          => '2012-06-01',
            'accreditation'        => 'A',
            'accreditation_date'   => '2023-05-10',
            'accreditation_number' => 'AKR-2023-0001',
        ]);

        $school1B = School::create([
            'foundation_id'        => $foundationB->id,
            'name'                 => 'SMP Nusantara Pintar Bekasi',
            'npsn'                 => '20100002',
            'level'                => 'SMP',
            'established_date'     => '2014-07-01',
            'status'               => 'active',
            'address'              => 'Jl. Raya Bekasi No. 46, Bekasi',
            'email'                => 'info@smpnpbekasi.sch.id',
            'phone'                => '021-87654322',
            'website'              => 'https://smpnpbekasi.sch.id',
            'decree_number'        => 'SK-003/SMPNP/2014',
            'decree_date'          => '2014-06-15',
            'permit_number'        => 'IZIN-002/2014',
            'permit_date'          => '2014-06-01',
            'accreditation'        => 'A',
            'accreditation_date'   => '2023-05-10',
            'accreditation_number' => 'AKR-2023-0002',
        ]);

        // ─────────────────────────────────────────────
        //  4. Academic Year (Tahun Ajaran) & 5. Subjects (Mata Pelajaran) for all schools
        // ─────────────────────────────────────────────
        $academicYear = null;
        $academicYearOdd = null;

        $subjectsData = [
            ['code' => 'MTK', 'name' => 'Matematika'],
            ['code' => 'BIN', 'name' => 'Bahasa Indonesia'],
            ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam'],
            ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial'],
            ['code' => 'BIG', 'name' => 'Bahasa Inggris'],
            ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam'],
            ['code' => 'PJK', 'name' => 'Pendidikan Jasmani'],
            ['code' => 'SBD', 'name' => 'Seni Budaya'],
        ];

        $schools = [$school1, $school1B];
        foreach ($schools as $sch) {
            $ayEven = AcademicYear::create([
                'school_id'  => $sch->id,
                'name'       => '2024/2025',
                'semester'   => 'even',
                'start_date' => '2025-01-06',
                'end_date'   => '2025-06-30',
                'is_active'  => true,
            ]);

            $ayOdd = AcademicYear::create([
                'school_id'  => $sch->id,
                'name'       => '2024/2025',
                'semester'   => 'odd',
                'start_date' => '2024-07-15',
                'end_date'   => '2024-12-20',
                'is_active'  => false,
            ]);

            if ($sch->id === $school1->id) {
                $academicYear = $ayEven;
                $academicYearOdd = $ayOdd;
            }

            foreach ($subjectsData as $subj) {
                Subject::create([
                    'school_id'   => $sch->id,
                    'code'        => $subj['code'],
                    'name'        => $subj['name'],
                    'is_active'   => true,
                ]);
            }
        }
        
        $school2 = School::create([
            'foundation_id'        => $foundationB->id,
            'name'                 => 'SMP Harapan Bangsa Jakarta',
            'npsn'                 => '20200001',
            'level'                => 'SMP',
            'established_date'     => '2016-07-01',
            'status'               => 'active',
            'address'              => 'Jl. Harapan No. 89, Jakarta Selatan',
            'email'                => 'info@smphbjakarta.sch.id',
            'phone'                => '021-87650001',
            'website'              => 'https://smphbjakarta.sch.id',
            'decree_number'        => 'SK-004/SMPHB/2016',
            'decree_date'          => '2016-06-15',
            'permit_number'        => 'IZIN-003/2016',
            'permit_date'          => '2016-06-01',
            'accreditation'        => 'A',
            'accreditation_date'   => '2023-08-15',
            'accreditation_number' => 'AKR-2023-0002',
        ]);

        // School 3 (Yayasan B - SMA)
        $school3 = School::create([
            'foundation_id'        => $foundationB->id,
            'name'                 => 'SMA Cendekia Mulia Bandung',
            'npsn'                 => '20100003',
            'level'                => 'SMA',
            'established_date'     => '2016-07-01',
            'status'               => 'active',
            'address'              => 'Jl. Dago No. 102, Bandung',
            'email'                => 'info@smacmbandung.sch.id',
            'phone'                => '022-33445566',
            'website'              => 'https://smacmbandung.sch.id',
            'decree_number'        => 'SK-004/SMACM/2016',
            'decree_date'          => '2016-06-15',
            'permit_number'        => 'IZIN-003/2016',
            'permit_date'          => '2016-06-01',
            'accreditation'        => 'A',
            'accreditation_date'   => '2024-01-20',
            'accreditation_number' => 'AKR-2024-0003',
        ]);

        // School 4 (Yayasan B - SMK)
        $school4 = School::create([
            'foundation_id'        => $foundationB->id,
            'name'                 => 'SMK Cendekia Mulia Bogor',
            'npsn'                 => '20100004',
            'level'                => 'SMK',
            'established_date'     => '2018-07-01',
            'status'               => 'active',
            'address'              => 'Jl. Pajajaran No. 77, Bogor',
            'email'                => 'info@smkcmbogor.sch.id',
            'phone'                => '0251-11223344',
            'website'              => 'https://smkcmbogor.sch.id',
            'decree_number'        => 'SK-005/SMKCM/2018',
            'decree_date'          => '2018-06-15',
            'permit_number'        => 'IZIN-004/2018',
            'permit_date'          => '2018-06-01',
            'accreditation'        => 'B',
            'accreditation_date'   => '2024-03-10',
            'accreditation_number' => 'AKR-2024-0004',
        ]);

        $allSchools = [$school1, $school1B, $school3, $school4];

        // ─────────────────────────────────────────────
        //  4. Core System Users (Superadmin & Foundation Admins)
        // ─────────────────────────────────────────────
        // Superadmin
        User::create([
            'name'      => 'Super Admin',
            'email'     => 'superadmin@mail.com',
            'password'  => $defaultPassword,
            'role_id'   => $roles['superadmin']->id,
            'is_active' => true,
        ]);
        User::create([
            'name'      => 'Super Admin 2',
            'email'     => 'superadmin2@mail.com',
            'password'  => $defaultPassword,
            'role_id'   => $roles['superadmin']->id,
            'is_active' => true,
        ]);

        // Admin Yayasan A
        User::create([
            'name'          => 'Admin Yayasan Nusantara',
            'email'         => 'adminyayasan@mail.com',
            'password'      => $defaultPassword,
            'role_id'       => $roles['admin_yayasan']->id,
            'foundation_id' => $foundationA->id,
            'is_active'     => true,
        ]);
        User::create([
            'name'          => 'Admin Yayasan Nusantara 2',
            'email'         => 'adminyayasan2@mail.com',
            'password'      => $defaultPassword,
            'role_id'       => $roles['admin_yayasan']->id,
            'foundation_id' => $foundationA->id,
            'is_active'     => true,
        ]);

        // Admin Yayasan B
        User::create([
            'name'          => 'Admin Yayasan Cendekia',
            'email'         => 'adminyayasanb@mail.com',
            'password'      => $defaultPassword,
            'role_id'       => $roles['admin_yayasan']->id,
            'foundation_id' => $foundationB->id,
            'is_active'     => true,
        ]);
        User::create([
            'name'          => 'Admin Yayasan Cendekia 2',
            'email'         => 'adminyayasanb2@mail.com',
            'password'      => $defaultPassword,
            'role_id'       => $roles['admin_yayasan']->id,
            'foundation_id' => $foundationB->id,
            'is_active'     => true,
        ]);

        // ─────────────────────────────────────────────
        //  5. Seed School Admins, Principals, TU & Academic Infrastructure per School
        // ─────────────────────────────────────────────
        $schoolClassrooms = [];
        $schoolTeachers = [];
        $schoolAcademicYears = [];
        $schoolSubjects = [];

        foreach ($allSchools as $sIndex => $sch) {
            $sNum = $sIndex + 1;

            // 5.1 Principal (Kepala Sekolah)
            $pEmail = $sNum === 1 ? 'kepalasekolah@mail.com' : "kepalasekolah{$sNum}@mail.com";
            $principal = User::create([
                'name'      => "Kepala Sekolah {$sch->name}",
                'email'     => $pEmail,
                'password'  => $defaultPassword,
                'role_id'   => $roles['kepala_sekolah']->id,
                'school_id' => $sch->id,
                'is_active' => true,
            ]);
            TeacherProfile::create([
                'user_id'           => $principal->id,
                'nip_nuptk'         => '1980' . str_pad($sNum, 12, '0', STR_PAD_LEFT),
                'birth_place'       => 'Jakarta',
                'birth_date'        => '1980-01-0' . $sNum,
                'gender'            => 'male',
                'religion'          => 'Islam',
                'last_education'    => 'S2',
                'position'          => 'Kepala Sekolah',
                'employment_status' => 'Tetap',
                'join_date'         => '2005-01-01',
            ]);

            // 5.2 School Admin (Admin Sekolah)
            $adminEmail = $sNum === 1 ? 'adminsekolah@mail.com' : "adminsekolah{$sNum}@mail.com";
            $adminSekolah = User::create([
                'name'      => "Admin {$sch->name}",
                'email'     => $adminEmail,
                'password'  => $defaultPassword,
                'role_id'   => $roles['admin_sekolah']->id,
                'school_id' => $sch->id,
                'is_active' => true,
            ]);
            TeacherProfile::create([
                'user_id'           => $adminSekolah->id,
                'birth_place'       => 'Bekasi',
                'birth_date'        => '1985-05-1' . $sNum,
                'gender'            => 'female',
                'religion'          => 'Islam',
                'last_education'    => 'S1',
                'position'          => 'Admin Sekolah',
                'employment_status' => 'Tetap',
                'join_date'         => '2015-07-01',
            ]);

            // 5.3 Tata Usaha
            $tuEmail = $sNum === 1 ? 'tatausaha@mail.com' : "tatausaha{$sNum}@mail.com";
            $tataUsaha = User::create([
                'name'      => "Tata Usaha {$sch->name}",
                'email'     => $tuEmail,
                'password'  => $defaultPassword,
                'role_id'   => $roles['tata_usaha']->id,
                'school_id' => $sch->id,
                'is_active' => true,
            ]);
            TeacherProfile::create([
                'user_id'           => $tataUsaha->id,
                'birth_place'       => 'Depok',
                'birth_date'        => '1988-03-2' . $sNum,
                'gender'            => 'female',
                'religion'          => 'Islam',
                'last_education'    => 'D3',
                'position'          => 'Tata Usaha',
                'employment_status' => 'Kontrak',
                'join_date'         => '2018-01-15',
            ]);

            // 5.4 Academic Years (Tahun Ajaran)
            $academicYear = AcademicYear::create([
                'school_id'  => $sch->id,
                'name'       => '2024/2025',
                'semester'   => 'even',
                'start_date' => '2025-01-06',
                'end_date'   => '2025-06-30',
                'is_active'  => true,
            ]);
            $academicYearOdd = AcademicYear::create([
                'school_id'  => $sch->id,
                'name'       => '2024/2025',
                'semester'   => 'odd',
                'start_date' => '2024-07-15',
                'end_date'   => '2024-12-20',
                'is_active'  => false,
            ]);
            $schoolAcademicYears[$sch->id] = [$academicYear, $academicYearOdd];

            // 5.5 Subjects (Mata Pelajaran)
            $subjectsData = [
                ['code' => 'MTK', 'name' => 'Matematika'],
                ['code' => 'BIN', 'name' => 'Bahasa Indonesia'],
                ['code' => 'IPA', 'name' => 'Ilmu Pengetahuan Alam'],
                ['code' => 'IPS', 'name' => 'Ilmu Pengetahuan Sosial'],
                ['code' => 'BIG', 'name' => 'Bahasa Inggris'],
                ['code' => 'PAI', 'name' => 'Pendidikan Agama Islam'],
                ['code' => 'PJK', 'name' => 'Pendidikan Jasmani'],
                ['code' => 'SBD', 'name' => 'Seni Budaya'],
            ];
            $schoolSubjects[$sch->id] = [];
            foreach ($subjectsData as $subject) {
                $sub = Subject::create([
                    'school_id'   => $sch->id,
                    'code'        => $subject['code'],
                    'name'        => $subject['name'],
                    'is_active'   => true,
                ]);
                $schoolSubjects[$sch->id][$subject['code']] = $sub;
            }

            // 5.6 Extracurriculars
            $extracurriculars = [
                ['name' => 'Pramuka', 'description' => 'Kegiatan kepramukaan untuk mengembangkan karakter.', 'is_active' => true],
                ['name' => 'Paduan Suara', 'description' => 'Ekstrakulikuler paduan suara dan vokal.', 'is_active' => true],
                ['name' => 'Futsal', 'description' => 'Kegiatan olahraga futsal siswa.', 'is_active' => true],
                ['name' => 'Seni Tari', 'description' => 'Ekstrakulikuler tari tradisional.', 'is_active' => false],
                ['name' => 'Robotika', 'description' => 'Belajar perakitan robot sederhana.', 'is_active' => true],
            ];
            foreach ($extracurriculars as $ekskul) {
                Extracurricular::create([
                    'school_id'   => $sch->id,
                    'name'        => $ekskul['name'],
                    'description' => $ekskul['description'],
                    'is_active'   => $ekskul['is_active'],
                ]);
            }
        }

        // ─────────────────────────────────────────────
        //  6. Seed 20 Teachers / Staff (5 Teachers per School)
        // ─────────────────────────────────────────────
        $teacherNamesMaster = [
            // School 1 (SD) - 5 Teachers
            ['name' => 'Guru Pengajar', 'email' => 'guru@mail.com', 'role' => 'guru', 'position' => 'Guru MTK', 'gender' => 'male', 'school_index' => 0],
            ['name' => 'Guru Pengajar 2', 'email' => 'guru2@mail.com', 'role' => 'guru', 'position' => 'Guru IPA', 'gender' => 'female', 'school_index' => 0],
            ['name' => 'Wali Kelas 1-A', 'email' => 'walikelas@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas', 'gender' => 'female', 'school_index' => 0],
            ['name' => 'Wali Kelas 1-B', 'email' => 'walikelas2@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas', 'gender' => 'male', 'school_index' => 0],
            ['name' => 'Guru Agama SD', 'email' => 'guru.sd5@mail.com', 'role' => 'guru', 'position' => 'Guru PAI', 'gender' => 'male', 'school_index' => 0],

            // School 2 (SMP) - 5 Teachers
            ['name' => 'Guru SMP 1 (Wali 7-A)', 'email' => 'guru.smp1@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 7-A', 'gender' => 'female', 'school_index' => 1],
            ['name' => 'Guru SMP 2 (Wali 7-B)', 'email' => 'guru.smp2@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 7-B', 'gender' => 'male', 'school_index' => 1],
            ['name' => 'Guru Matematika SMP', 'email' => 'guru.smp3@mail.com', 'role' => 'guru', 'position' => 'Guru MTK', 'gender' => 'male', 'school_index' => 1],
            ['name' => 'Guru Bahasa Inggris SMP', 'email' => 'guru.smp4@mail.com', 'role' => 'guru', 'position' => 'Guru BIG', 'gender' => 'female', 'school_index' => 1],
            ['name' => 'Guru IPA SMP', 'email' => 'guru.smp5@mail.com', 'role' => 'guru', 'position' => 'Guru IPA', 'gender' => 'female', 'school_index' => 1],

            // School 3 (SMA) - 5 Teachers
            ['name' => 'Guru SMA 1 (Wali 10-MIPA)', 'email' => 'guru.sma1@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 10-MIPA', 'gender' => 'male', 'school_index' => 2],
            ['name' => 'Guru SMA 2 (Wali 10-IPS)', 'email' => 'guru.sma2@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 10-IPS', 'gender' => 'female', 'school_index' => 2],
            ['name' => 'Guru Fisika SMA', 'email' => 'guru.sma3@mail.com', 'role' => 'guru', 'position' => 'Guru Fisika', 'gender' => 'male', 'school_index' => 2],
            ['name' => 'Guru Kimia SMA', 'email' => 'guru.sma4@mail.com', 'role' => 'guru', 'position' => 'Guru Kimia', 'gender' => 'female', 'school_index' => 2],
            ['name' => 'Guru Biologi SMA', 'email' => 'guru.sma5@mail.com', 'role' => 'guru', 'position' => 'Guru Biologi', 'gender' => 'male', 'school_index' => 2],

            // School 4 (SMK) - 5 Teachers
            ['name' => 'Guru SMK 1 (Wali 10-TKJ)', 'email' => 'guru.smk1@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 10-TKJ', 'gender' => 'female', 'school_index' => 3],
            ['name' => 'Guru SMK 2 (Wali 10-RPL)', 'email' => 'guru.smk2@mail.com', 'role' => 'wali_kelas', 'position' => 'Wali Kelas 10-RPL', 'gender' => 'male', 'school_index' => 3],
            ['name' => 'Guru Produktif RPL', 'email' => 'guru.smk3@mail.com', 'role' => 'guru', 'position' => 'Guru RPL', 'gender' => 'male', 'school_index' => 3],
            ['name' => 'Guru Produktif TKJ', 'email' => 'guru.smk4@mail.com', 'role' => 'guru', 'position' => 'Guru TKJ', 'gender' => 'female', 'school_index' => 3],
            ['name' => 'Guru Kewirausahaan SMK', 'email' => 'guru.smk5@mail.com', 'role' => 'guru', 'position' => 'Guru KWU', 'gender' => 'female', 'school_index' => 3],
        ];

        $teacherUserObjects = [];

        foreach ($teacherNamesMaster as $tIdx => $tData) {
            $schObj = $allSchools[$tData['school_index']];

            $gUser = User::create([
                'name'      => $tData['name'],
                'email'     => $tData['email'],
                'password'  => $defaultPassword,
                'role_id'   => $roles[$tData['role']]->id,
                'school_id' => $schObj->id,
                'is_active' => true,
            ]);

            TeacherProfile::create([
                'user_id'           => $gUser->id,
                'nip_nuptk'         => '19900' . str_pad($tIdx + 1, 11, '0', STR_PAD_LEFT),
                'birth_place'       => 'Kota ' . ($tIdx + 1),
                'birth_date'        => '1990-05-' . str_pad(($tIdx % 28) + 1, 2, '0', STR_PAD_LEFT),
                'gender'            => $tData['gender'],
                'religion'          => 'Islam',
                'last_education'    => 'S1',
                'position'          => $tData['position'],
                'employment_status' => 'Tetap',
                'join_date'         => '2015-07-01',
            ]);

            $teacherUserObjects[$tData['email']] = $gUser;
        }

        // ─────────────────────────────────────────────
        //  7. Seed Classrooms per School (assigned to Wali Kelas)
        // ─────────────────────────────────────────────
        $classroomSpecs = [
            // School 1 (SD)
            ['school_index' => 0, 'name' => '2-D', 'grade' => 2, 'major' => 'MIPA', 'room' => 'R. 101', 'wali_email' => 'walikelas@mail.com'],
            ['school_index' => 0, 'name' => '2-E', 'grade' => 2, 'major' => 'IPS', 'room' => 'R. 102', 'wali_email' => 'walikelas2@mail.com'],

            // School 2 (SMP)
            ['school_index' => 1, 'name' => '7-A', 'grade' => 7, 'major' => 'UMUM', 'room' => 'R. 201', 'wali_email' => 'guru.smp1@mail.com'],
            ['school_index' => 1, 'name' => '7-B', 'grade' => 7, 'major' => 'UMUM', 'room' => 'R. 202', 'wali_email' => 'guru.smp2@mail.com'],

            // School 3 (SMA)
            ['school_index' => 2, 'name' => '10-MIPA-1', 'grade' => 10, 'major' => 'MIPA', 'room' => 'R. 301', 'wali_email' => 'guru.sma1@mail.com'],
            ['school_index' => 2, 'name' => '10-IPS-1', 'grade' => 10, 'major' => 'IPS', 'room' => 'R. 302', 'wali_email' => 'guru.sma2@mail.com'],

            // School 4 (SMK)
            ['school_index' => 3, 'name' => '10-TKJ-1', 'grade' => 10, 'major' => 'TKJ', 'room' => 'Lab Komputer 1', 'wali_email' => 'guru.smk1@mail.com'],
            ['school_index' => 3, 'name' => '10-RPL-1', 'grade' => 10, 'major' => 'RPL', 'room' => 'Lab Komputer 2', 'wali_email' => 'guru.smk2@mail.com'],
        ];

        $seededClassrooms = [];
        $seededClassroomsOdd = [];

        foreach ($classroomSpecs as $cSpec) {
            $schObj = $allSchools[$cSpec['school_index']];
            $ay = $schoolAcademicYears[$schObj->id][0]; // Even
            $ayOdd = $schoolAcademicYears[$schObj->id][1]; // Odd
            $wali = $teacherUserObjects[$cSpec['wali_email']] ?? null;

            $crEven = Classroom::create([
                'school_id'           => $schObj->id,
                'academic_year_id'    => $ay->id,
                'name'                => $cSpec['name'],
                'grade'               => $cSpec['grade'],
                'major'               => $cSpec['major'],
                'room'                => $cSpec['room'],
                'status'              => 'active',
                'homeroom_teacher_id' => $wali ? $wali->id : null,
                'capacity'            => 30,
            ]);

            $crOdd = Classroom::create([
                'school_id'           => $schObj->id,
                'academic_year_id'    => $ayOdd->id,
                'name'                => $cSpec['name'],
                'grade'               => $cSpec['grade'],
                'major'               => $cSpec['major'],
                'room'                => $cSpec['room'],
                'status'              => 'active',
                'homeroom_teacher_id' => $wali ? $wali->id : null,
                'capacity'            => 30,
            ]);

            $seededClassrooms[] = $crEven;
            $seededClassroomsOdd[] = $crOdd;
        }

        // ─────────────────────────────────────────────
        //  8. Seed Students (40 Students Total across 4 Schools)
        // ─────────────────────────────────────────────
        $studentNamesPool = [
            'Ahmad Wibowo', 'Budi Santoso', 'Citra Lestari', 'Dian Pratama', 'Eka Wijaya',
            'Fitri Handayani', 'Giri Nugroho', 'Hendra Kusuma', 'Indah Permata', 'Joko Susilo',
            'Kiki Amalia', 'Luki Hermawan', 'Maya Putri', 'Naufal Rizky', 'Olivia Tan',
            'Pratama Putra', 'Qori Aina', 'Rian Hidayat', 'Siti Rahma', 'Taufik Hidayat',
            'Umar Faruq', 'Vina Panduwinata', 'Wahyu Hidayat', 'Xavier Lee', 'Yuni Shara',
            'Zaky Mubarak', 'Aditya Saputra', 'Bela Cantika', 'Candra Wijaya', 'Daffa Al-Faris',
            'Elsa Manora', 'Faris Ramadhan', 'Gita Gutawa', 'Hafiz Azhari', 'Irfan Bachdim',
            'Jasmine Noor', 'Kevin Sanjaya', 'Larasati Putri', 'Muhammad Iqbal', 'Nabila Syakieb'
        ];

        $studentProfilesAll = [];

        foreach ($studentNamesPool as $idx => $sName) {
            $classIdx = $idx % count($seededClassrooms);
            $targetClassroom = $seededClassrooms[$classIdx];
            $schObj = School::find($targetClassroom->school_id);

            $num = $idx === 0 ? '' : ($idx + 1);
            $email = "siswa{$num}@mail.com";

            $sUser = User::create([
                'name'      => $sName,
                'email'     => $email,
                'password'  => $defaultPassword,
                'role_id'   => $roles['siswa']->id,
                'school_id' => $schObj->id,
                'is_active' => true,
            ]);

            $sProfile = StudentProfile::create([
                'user_id'         => $sUser->id,
                'classroom_id'    => $targetClassroom->id,
                'nisn'            => '00' . str_pad($idx + 12345, 8, '0', STR_PAD_LEFT),
                'birth_place'     => ($idx % 2 === 0) ? 'Jakarta' : 'Bandung',
                'birth_date'      => '2010-0' . (($idx % 9) + 1) . '-' . str_pad(($idx % 28) + 1, 2, '0', STR_PAD_LEFT),
                'gender'          => ($idx % 2 === 0) ? 'male' : 'female',
                'address'         => "Jl. Pendidikan No. " . ($idx + 1) . ", " . $schObj->name,
                'enrollment_date' => '2022-07-01',
                'status'          => 'active',
            ]);

            $studentProfilesAll[] = $sProfile;
        }

        // ─────────────────────────────────────────────
        //  9. Seed Parents (Orang Tua / Wali)
        // ─────────────────────────────────────────────
        $parentSpecs = [
            ['name' => 'Orang Tua / Wali', 'email' => 'orangtua@mail.com', 'child_idx' => 0],
            ['name' => 'Orang Tua / Wali 2', 'email' => 'orangtua2@mail.com', 'child_idx' => 1],
            ['name' => 'Orang Tua / Wali 3', 'email' => 'orangtua3@mail.com', 'child_idx' => 2],
            ['name' => 'Orang Tua Wali SMP', 'email' => 'orangtuasmp@mail.com', 'child_idx' => 5],
            ['name' => 'Orang Tua Wali SMA', 'email' => 'orangtuasma@mail.com', 'child_idx' => 10],
            ['name' => 'Orang Tua Wali SMK', 'email' => 'orangtuasmk@mail.com', 'child_idx' => 15],
        ];

        foreach ($parentSpecs as $pIdx => $pData) {
            $oUser = User::create([
                'name'      => $pData['name'],
                'email'     => $pData['email'],
                'password'  => $defaultPassword,
                'role_id'   => $roles['orang_tua']->id,
                'phone'     => '0812345678' . str_pad($pIdx, 2, '0', STR_PAD_LEFT),
                'is_active' => true,
            ]);

            $pProfile = ParentProfile::create([
                'user_id'        => $oUser->id,
                'nik'            => '32010101018000' . str_pad($pIdx + 1, 2, '0', STR_PAD_LEFT),
                'gender'         => ($pIdx % 2 === 0) ? 'male' : 'female',
                'birth_place'    => 'Jakarta',
                'birth_date'     => '1980-05-20',
                'religion'       => 'Islam',
                'last_education' => 'S1',
                'marital_status' => 'Menikah',
                'relationship'   => ($pIdx % 2 === 0) ? 'father' : 'mother',
                'occupation'     => 'Wiraswasta',
                'address'        => 'Jl. Mawar No. ' . ($pIdx + 10),
            ]);

            if (isset($studentProfilesAll[$pData['child_idx']])) {
                $pProfile->children()->attach($studentProfilesAll[$pData['child_idx']]->id);
            }
        }

        // ─────────────────────────────────────────────
        //  10. Subscriptions & Payments
        // ─────────────────────────────────────────────
        $this->call(SubscriptionSeeder::class);

        // ─────────────────────────────────────────────
        //  11. Academic Assignments, Materials & Assessments for School 1
        // ─────────────────────────────────────────────
        $sch1 = $school1;
        $subjectMtk = $schoolSubjects[$sch1->id]['MTK'] ?? null;
        $subjectBin = $schoolSubjects[$sch1->id]['BIN'] ?? null;
        $subjectIpa = $schoolSubjects[$sch1->id]['IPA'] ?? null;

        $guruUser = $teacherUserObjects['guru@mail.com'] ?? null;
        $guru2User = $teacherUserObjects['guru2@mail.com'] ?? null;

        $classroom1Obj = $seededClassrooms[0]; // 2-D
        $classroom2Obj = $seededClassrooms[1]; // 2-E
        $classroom1OddObj = $seededClassroomsOdd[0];

        $academicYear1 = $schoolAcademicYears[$sch1->id][0];
        $academicYear1Odd = $schoolAcademicYears[$sch1->id][1];

        if ($guruUser && $subjectMtk && $classroom1Obj) {
            TeacherSubjectAssignment::create([
                'school_id'        => $sch1->id,
                'teacher_id'       => $guruUser->id,
                'subject_id'       => $subjectMtk->id,
                'classroom_id'     => $classroom1Obj->id,
                'academic_year_id' => $academicYear1->id,
                'is_active'        => true,
            ]);
        }
        if ($guruUser && $subjectBin && $classroom1Obj) {
            TeacherSubjectAssignment::create([
                'school_id'        => $sch1->id,
                'teacher_id'       => $guruUser->id,
                'subject_id'       => $subjectBin->id,
                'classroom_id'     => $classroom1Obj->id,
                'academic_year_id' => $academicYear1->id,
                'is_active'        => true,
            ]);
        }
        if ($guru2User && $subjectIpa && $classroom2Obj) {
            TeacherSubjectAssignment::create([
                'school_id'        => $sch1->id,
                'teacher_id'       => $guru2User->id,
                'subject_id'       => $subjectIpa->id,
                'classroom_id'     => $classroom2Obj->id,
                'academic_year_id' => $academicYear1->id,
                'is_active'        => true,
            ]);
        }

        // Materials & Assessments
        if ($guruUser && $subjectMtk && $classroom1Obj) {
            $mtkMat1Even = SubjectMaterial::create([
                'school_id'        => $sch1->id,
                'subject_id'       => $subjectMtk->id,
                'classroom_id'     => $classroom1Obj->id,
                'academic_year_id' => $academicYear1->id,
                'uploaded_by'      => $guruUser->id,
                'title'            => 'Bab 1 : Pengenalan Aljabar Dasar',
                'file_path'        => 'materials/' . $sch1->id . '/' . $subjectMtk->id . '/aljabar_dasar.pdf',
                'file_name'        => 'Aljabar Dasar.pdf',
                'file_type'        => 'pdf',
                'file_size'        => 1024 * 1024 * 2,
                'uploaded_by_name' => $guruUser->name,
                'is_active'        => true,
            ]);

            $tugas = Assessment::create([
                'school_id'        => $sch1->id,
                'subject_id'       => $subjectMtk->id,
                'classroom_id'     => $classroom1Obj->id,
                'academic_year_id' => $academicYear1->id,
                'created_by'       => $guruUser->id,
                'material_id'      => $mtkMat1Even->id,
                'category'         => 'tugas',
                'type'             => 'tugas_sekolah',
                'title'            => 'Tugas I : Latihan Aljabar Dasar',
                'uploaded_by_name' => $guruUser->name,
                'is_active'        => true,
            ]);

            $studentsInClass = StudentProfile::where('classroom_id', $classroom1Obj->id)->get();
            foreach ($studentsInClass as $st) {
                AssessmentScore::create([
                    'assessment_id' => $tugas->id,
                    'student_id'    => $st->id,
                    'score'         => 88.00,
                ]);
            }
        }

        // ─────────────────────────────────────────────
        //  12. Additional Seeders (Certificates, News, SPP)
        // ─────────────────────────────────────────────
        $this->call(ActiveStudentCertificateSeeder::class);
        $this->call(StudentDispensationCertificateSeeder::class);
        $this->call(StudentWarningCertificateSeeder::class);
        $this->call(ActivityNewsSeeder::class);
        $this->call(SppSeeder::class);

        // ─────────────────────────────────────────────
        //  13. Perpustakaan
        // ─────────────────────────────────────────────
        \App\Models\Perpustakaan::create([
            'school_id' => $school1->id,
            'name' => 'Buku Fisika Dasar',
            'isbn' => '978-602-8256-07-0',
            'category' => 'Sains',
            'stock' => 36,
            'rack_location' => 'Rak A1',
            'publication_year' => 2021,
            'author' => 'Prof. Yohanes Surya',
            'publisher' => 'Bentang Pustaka',
            'description' => 'Buku pegangan wajib untuk siswa SMA tentang konsep dasar fisika.',
        ]);

        \App\Models\Perpustakaan::create([
            'school_id' => $school1->id,
            'name' => 'Sejarah Indonesia Modern',
            'isbn' => '978-979-1234-56-7',
            'category' => 'Sejarah',
            'stock' => 15,
            'rack_location' => 'Rak C3',
            'publication_year' => 2019,
            'author' => 'Ricklefs',
            'publisher' => 'Serambi',
            'description' => 'Buku yang membahas perkembangan sejarah Indonesia dari awal hingga masa modern.',
        ]);

        \App\Models\Perpustakaan::create([
            'school_id' => $school1->id,
            'name' => 'Belajar Coding dengan Python',
            'isbn' => '978-602-8888-99-9',
            'category' => 'TIK',
            'stock' => 24,
            'rack_location' => 'Rak B1',
            'publication_year' => 2023,
            'author' => 'Budi Raharjo',
            'publisher' => 'Informatika',
            'description' => 'Panduan lengkap belajar bahasa pemrograman Python untuk pemula.',
        ]);
        // ─────────────────────────────────────────────
        //  14. Aset
        // ─────────────────────────────────────────────
        \App\Models\Aset::create([
            'school_id' => $school1->id,
            'code'      => 'INV-ELK-001',
            'name'      => 'Proyektor Epson EB-X400',
            'category'  => 'Elektronik',
            'brand'     => 'Epson',
            'building'  => 'Gedung A',
            'floor'     => '1',
            'room'      => 'Ruang Kelas 2-D',
            'condition' => 'Baik',
            'date'      => '2023-01-10',
            'value'     => 4500000,
        ]);

        \App\Models\Aset::create([
            'school_id' => $school1->id,
            'code'      => 'INV-FURN-001',
            'name'      => 'Meja Guru Kayu Jati',
            'category'  => 'Furniture',
            'brand'     => 'Olympic',
            'building'  => 'Gedung Utama',
            'floor'     => '1',
            'room'      => 'Ruang Guru',
            'condition' => 'Rusak Ringan',
            'date'      => '2020-05-15',
            'value'     => 1200000,
        ]);

        \App\Models\Aset::create([
            'school_id' => $school1->id,
            'code'      => 'INV-LAB-001',
            'name'      => 'Mikroskop Binokuler',
            'category'  => 'Peralatan Laboratorium',
            'brand'     => 'Olympus',
            'building'  => 'Gedung Laboratorium',
            'floor'     => '2',
            'room'      => 'Lab Biologi',
            'condition' => 'Baik',
            'date'      => '2024-02-20',
            'value'     => 8500000,
        ]);
        // ─────────────────────────────────────────────
        //  15. Ruangan
        // ─────────────────────────────────────────────
        \App\Models\Ruangan::create([
            'school_id' => $school1->id,
            'code'      => 'LAB-01',
            'name'      => 'LAB BAHASA',
            'category'  => 'Laboratorium',
            'building'  => 'Gedung B',
            'capacity'  => 30,
            'area'      => 60,
            'facilities'=> ['ac', 'proyektor'],
        ]);

        \App\Models\Ruangan::create([
            'school_id' => $school1->id,
            'code'      => 'R-01',
            'name'      => 'KELAS 8A',
            'category'  => 'Ruang Kelas',
            'building'  => 'Gedung A',
            'capacity'  => 30,
            'area'      => 50,
            'facilities'=> ['ac', 'proyektor'],
        ]);

        \App\Models\Ruangan::create([
            'school_id' => $school1->id,
            'code'      => 'R-02',
            'name'      => 'RUANG MUSIK',
            'category'  => 'Ruang Fasilitas',
            'building'  => 'Gedung C',
            'capacity'  => 25,
            'area'      => 45,
            'facilities'=> ['ac', 'sound_system'],
        ]);
    }
}
