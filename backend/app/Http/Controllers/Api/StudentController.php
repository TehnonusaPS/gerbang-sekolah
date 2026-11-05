<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\ParentProfile;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StudentController extends Controller
{
    /**
     * Hitung Cosine Similarity antara dua vektor float array (Facenet512: 512 dimensi)
     */
    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        
        $count = count($vecA);
        if ($count !== count($vecB) || $count === 0) {
            return 0.0;
        }
        
        for ($i = 0; $i < $count; $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }
        
        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }
        
        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    /**
     * Map frontend gender to database.
     */
    /**
     * Map frontend gender to database.
     */
    private function mapGenderToDb(?string $gender): ?string
    {
        if (!$gender) return null;
        $lower = strtolower($gender);
        if (in_array($lower, ['laki-laki', 'male', 'jk01'])) {
            return 'male';
        }
        if (in_array($lower, ['perempuan', 'female', 'jk02'])) {
            return 'female';
        }
        return null;
    }

    /**
     * Map database gender to frontend.
     */
    private function mapGenderToFe(?string $gender): string
    {
        if ($gender === 'male') return 'Laki-laki';
        if ($gender === 'female') return 'Perempuan';
        return '';
    }

    /**
     * Map frontend status to database.
     */
    private function mapStatusToDb(?string $status): string
    {
        if (!$status) return 'active';
        switch (strtolower($status)) {
            case '0':
            case 'nonaktif':
                return 'expelled';
            case '2':
            case 'pindah':
                return 'transferred';
            case '3':
            case 'lulus':
                return 'alumni';
            case '1':
            case 'aktif':
            default:
                return 'active';
        }
    }

    /**
     * Map database status to frontend.
     */
    private function mapStatusToFe(?string $status): string
    {
        switch ($status) {
            case 'expelled':
                return 'Nonaktif';
            case 'transferred':
                return 'Pindah';
            case 'alumni':
                return 'Lulus';
            case 'active':
            default:
                return 'Aktif';
        }
    }

    /**
     * Map frontend relationship to database.
     */
    private function mapRelationshipToDb(?string $relation): ?string
    {
        if (!$relation) return null;
        switch (strtolower($relation)) {
            case 'ayah':
                return 'father';
            case 'ibu':
                return 'mother';
            case 'wali':
            default:
                return 'guardian';
        }
    }

    /**
     * Map database relationship to frontend.
     */
    private function mapRelationshipToFe(?string $relation): string
    {
        switch ($relation) {
            case 'father':
                return 'Ayah';
            case 'mother':
                return 'Ibu';
            case 'guardian':
            default:
                return 'Wali';
        }
    }

    /**
     * Extract Ayah, Ibu, Wali, and Login Account data from StudentProfile.
     */
    private function extractParentsData(?StudentProfile $profile): array
    {
        $parents = $profile ? $profile->parents : collect();
        $father = $parents->firstWhere('relationship', 'father');
        $mother = $parents->firstWhere('relationship', 'mother');
        $guardian = $parents->first(fn($p) => !in_array($p->relationship, ['father', 'mother'], true));
        $primary = $parents->first(fn($p) => $p->user !== null) ?? $father ?? $mother ?? $guardian;

        $pName = fn(?ParentProfile $p) => $p ? ($p->name ?: ($p->user ? $p->user->name : '')) : '';
        $pEmail = fn(?ParentProfile $p) => $p ? ($p->email ?: ($p->user ? $p->user->email : '')) : '';
        $pPhone = fn(?ParentProfile $p) => $p ? ($p->phone ?: ($p->user ? $p->user->phone : '')) : '';

        return [
            'nama_ayah'      => $pName($father),
            'pekerjaan_ayah' => $father ? ($father->occupation ?? '') : '',
            'email_ayah'     => $pEmail($father),
            'no_hp_ayah'     => $pPhone($father),
            'alamat_ayah'    => $father ? ($father->address ?? '') : '',

            'nama_ibu'       => $pName($mother),
            'pekerjaan_ibu'  => $mother ? ($mother->occupation ?? '') : '',
            'email_ibu'      => $pEmail($mother),
            'no_hp_ibu'      => $pPhone($mother),
            'alamat_ibu'     => $mother ? ($mother->address ?? '') : '',

            'nama_wali'      => $pName($guardian),
            'hubungan_siswa' => $guardian && !in_array($guardian->relationship, ['guardian', 'Wali'], true) ? $guardian->relationship : '',
            'kelamin_wali'   => $guardian ? $this->mapGenderToFe($guardian->gender) : '',
            'pekerjaan_wali' => $guardian ? ($guardian->occupation ?? '') : '',
            'email_wali'     => $pEmail($guardian),
            'no_hp_wali'     => $pPhone($guardian),
            'alamat_wali'    => $guardian ? ($guardian->address ?? '') : '',

            'namaWali'       => $pName($primary),
            'emailLogin'     => $primary && $primary->user ? $primary->user->email : $pEmail($primary),
            'noHpLogin'      => $primary && $primary->user ? $primary->user->phone : $pPhone($primary),
        ];
    }

    /**
     * Sync Ayah, Ibu, Wali profiles and the Parent Login User account.
     */
    private function syncStudentParents(Request $request, StudentProfile $studentProfile, User $studentUser, ?Role $parentRole): array
    {
        $existingParents = $studentProfile->parents()->with('user')->get()->keyBy(
            fn($p) => in_array($p->relationship, ['father', 'mother'], true) ? $p->relationship : 'guardian'
        );
        $existingUser = $existingParents->map(fn($p) => $p->user)->filter()->first();

        $primaryName = $request->input('nama_ayah') ?: $request->input('nama_ibu') ?: $request->input('nama_wali') ?: ('Orang Tua ' . $studentUser->name);
        $loginEmail = $request->input('emailLogin')
            ?: $request->input('email_ayah')
            ?: $request->input('email_ibu')
            ?: $request->input('email_wali')
            ?: ($existingUser ? $existingUser->email : 'ortu-' . $studentProfile->nisn . '@school.com');
        $loginPhone = $request->input('noHpLogin')
            ?: $request->input('no_hp_ayah')
            ?: $request->input('no_hp_ibu')
            ?: $request->input('no_hp_wali')
            ?: ($existingUser ? $existingUser->phone : null);

        $parentUser = $existingUser ?: User::where('email', $loginEmail)->first();
        if ($parentUser) {
            $userUpdate = [
                'name'          => $primaryName,
                'email'         => $loginEmail,
                'phone'         => $loginPhone,
                'school_id'     => $studentUser->school_id,
                'foundation_id' => $studentUser->foundation_id,
            ];
            if ($request->filled('password')) {
                $userUpdate['password'] = Hash::make($request->input('password'));
            }
            $parentUser->update($userUpdate);
        } elseif ($parentRole) {
            $parentUser = User::create([
                'name'          => $primaryName,
                'email'         => $loginEmail,
                'password'      => Hash::make($request->input('password') ?: '123456'),
                'role_id'       => $parentRole->id,
                'school_id'     => $studentUser->school_id,
                'foundation_id' => $studentUser->foundation_id,
                'phone'         => $loginPhone,
                'is_active'     => true,
            ]);
        }

        $specs = [
            'father' => [
                'name'         => $request->input('nama_ayah'),
                'gender'       => 'male',
                'relationship' => 'father',
                'occupation'   => $request->input('pekerjaan_ayah'),
                'email'        => $request->input('email_ayah'),
                'phone'        => $request->input('no_hp_ayah'),
                'address'      => $request->input('alamat_ayah'),
            ],
            'mother' => [
                'name'         => $request->input('nama_ibu'),
                'gender'       => 'female',
                'relationship' => 'mother',
                'occupation'   => $request->input('pekerjaan_ibu'),
                'email'        => $request->input('email_ibu'),
                'phone'        => $request->input('no_hp_ibu'),
                'address'      => $request->input('alamat_ibu'),
            ],
            'guardian' => [
                'name'         => $request->input('nama_wali'),
                'gender'       => $this->mapGenderToDb($request->input('kelamin_wali')),
                'relationship' => $request->input('hubungan_siswa') ?: 'guardian',
                'occupation'   => $request->input('pekerjaan_wali'),
                'email'        => $request->input('email_wali'),
                'phone'        => $request->input('no_hp_wali'),
                'address'      => $request->input('alamat_wali'),
            ],
        ];

        $userAssigned = false;
        $parentIds = [];

        foreach ($specs as $rel => $data) {
            $hasData = !empty($data['name']) || !empty($data['phone']) || !empty($data['address']);
            $existing = $existingParents->get($rel);

            if (!$hasData) {
                if ($existing && $existing->user_id && $parentUser && $existing->user_id === $parentUser->id) {
                    $existing->update(['user_id' => null]);
                }
                continue;
            }

            $assignUserId = null;
            if (!$userAssigned && $parentUser) {
                $otherUsingUser = ParentProfile::where('user_id', $parentUser->id)
                    ->when($existing, fn($q) => $q->where('id', '!=', $existing->id))
                    ->first();
                if (!$otherUsingUser) {
                    $assignUserId = $parentUser->id;
                    $userAssigned = true;
                } elseif ($existing && $existing->user_id === $parentUser->id) {
                    $assignUserId = $parentUser->id;
                    $userAssigned = true;
                }
            }

            $payload = array_merge($data, ['user_id' => $assignUserId]);

            if ($existing) {
                $existing->update($payload);
                $parentIds[] = $existing->id;
            } else {
                $created = ParentProfile::create($payload);
                $parentIds[] = $created->id;
            }
        }

        if (!empty($parentIds)) {
            $studentProfile->parents()->sync($parentIds);
        }

        return [
            'email' => $loginEmail,
            'phone' => $loginPhone,
        ];
    }

    /**
     * Display a listing of the students.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = User::whereHas('role', function ($q) {
            $q->where('name', 'siswa');
        })->with(['school', 'studentProfile.classroom.school', 'studentProfile.parents.user', 'studentProfile.extracurriculars']);

        // Role Scope
        if ($user->isSuperAdmin()) {
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            $query->whereHas('school', function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id);
            });
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, guru
            $query->where('school_id', $user->school_id);
        }

        // Filtering by class
        if ($request->has('kelasId') && $request->kelasId !== 'all') {
            if ($request->kelasId === 'unassigned') {
                $query->whereHas('studentProfile', function ($q) {
                    $q->whereNull('classroom_id');
                });
            } else {
                $query->whereHas('studentProfile', function ($q) use ($request) {
                    $q->where('classroom_id', $request->kelasId);
                });
            }
        }

        // Filtering by status
        if ($request->has('status') && $request->status !== 'all') {
            $dbStatus = $this->mapStatusToDb($request->status);
            $query->whereHas('studentProfile', function ($q) use ($dbStatus) {
                $q->where('status', $dbStatus);
            });
        }

        // Search name or NISN
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhereHas('studentProfile', function ($sq) use ($search) {
                      $sq->where('nisn', 'ilike', "%{$search}%");
                  });
            });
        }

        $students = $query->latest('updated_at')->get();

        $formatted = $students->map(function ($student) {
            $profile = $student->studentProfile;
            $parentData = $this->extractParentsData($profile);
            $schoolName = $student->school
                ? $student->school->name
                : ($profile && $profile->classroom && $profile->classroom->school ? $profile->classroom->school->name : '-');
            
            return array_merge([
                'id'             => $student->id,
                'nama'           => $student->name,
                'email'          => $student->email,
                'no_hp'          => $student->phone,
                'foto'           => $student->photo ? (str_starts_with($student->photo, 'http') ? $student->photo : asset($student->photo)) : null,
                'nisn'           => $profile ? $profile->nisn : '',
                'sekolah'        => $schoolName,
                'kelasId'        => $profile ? $profile->classroom_id : null,
                'kelas'          => $profile && $profile->classroom ? $profile->classroom->name : '-',
                'tempat_lahir'   => $profile ? $profile->birth_place : '',
                'tanggal_lahir'  => $profile && $profile->birth_date ? $profile->birth_date->format('Y-m-d') : '',
                'jenisKelamin'   => $profile ? $this->mapGenderToFe($profile->gender) : '',
                'agama'          => $profile ? ($profile->religion ?? '') : '',
                'alamat'         => $profile ? $profile->address : '',
                'ekskul'         => $profile ? $profile->extracurriculars->map(fn($e) => ['id' => (string)$e->id, 'nama' => $e->name])->values()->toArray() : [],
                'tahunMasuk'     => $profile && $profile->enrollment_date ? $profile->enrollment_date->format('Y') : '',
                'status'         => $profile ? $this->mapStatusToFe($profile->status) : 'Aktif',
                'payment_status' => \App\Models\StudentBill::where('student_id', $student->id)
                                    ->whereIn('status', ['unpaid', 'partial'])
                                    ->exists() ? 'Belum Lunas' : 'Lunas',
            ], $parentData);
        });

        $statsQuery = User::whereHas('role', function ($q) {
            $q->where('name', 'siswa');
        });

        // Role Scope
        if ($user->isSuperAdmin()) {
            if ($request->has('school_id')) {
                $statsQuery->where('school_id', $request->input('school_id'));
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            $statsQuery->whereHas('school', function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id);
            });
            if ($request->has('school_id')) {
                $statsQuery->where('school_id', $request->input('school_id'));
            }
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, guru
            $statsQuery->where('school_id', $user->school_id);
        }

        $total = (clone $statsQuery)->count();
        $active = (clone $statsQuery)->whereHas('studentProfile', function ($q) {
            $q->where('status', 'active');
        })->count();
        $male = (clone $statsQuery)->whereHas('studentProfile', function ($q) {
            $q->where('gender', 'male');
        })->count();
        $female = (clone $statsQuery)->whereHas('studentProfile', function ($q) {
            $q->where('gender', 'female');
        })->count();

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
            'stats'  => [
                'total' => $total,
                'active' => $active,
                'male' => $male,
                'female' => $female,
            ]
        ]);
    }

    /**
     * Store a newly created student.
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $schoolId = $currentUser->isSuperAdmin() ? $request->input('sekolahId') : $currentUser->school_id;

        if (!$schoolId && $request->filled('kelas')) {
            $classroom = Classroom::find($request->input('kelas'));
            if ($classroom) {
                $schoolId = $classroom->school_id;
            }
        }

        if (!$schoolId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School ID is required.',
            ], 400);
        }

        $inputs = $request->all();
        foreach ($inputs as $key => $value) {
            if ($value === '') {
                $inputs[$key] = null;
            }
        }

        if (isset($inputs['foto']) && is_string($inputs['foto'])) {
            unset($inputs['foto']);
        }

        $validator = Validator::make($inputs, [
            'nama_depan'     => 'required|string|max:100',
            'nama_belakang'  => 'nullable|string|max:100',
            'nisn'           => 'required|string|max:50|unique:student_profiles,nisn',
            'nik'            => 'nullable|string|max:50',
            'kelas'          => 'nullable', // classroom_id
            'status'         => 'required|string',
            'jenis_kelamin'  => 'required|string',
            'tanggal_lahir'  => 'required|date',
            'email'          => 'nullable|email|unique:users,email',
            'no_hp'          => 'nullable|string',
            'foto'           => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ], [
            'foto.max' => 'Tolong masukkan foto kurang dari 10 MB.',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::error('Student Store Validation Failed: ' . json_encode($validator->errors()->all()));
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $studentRole = Role::where('name', 'siswa')->first();
        $parentRole = Role::where('name', 'orang_tua')->first();

        if (!$studentRole || !$parentRole) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Required user roles not configured in system.',
            ], 500);
        }

        DB::beginTransaction();
        try {
            $fullName = trim($request->nama_depan . ' ' . ($request->nama_belakang ?: ''));
            $studentEmail = $request->email ?: null;
            
            // Handle Photo Upload
            $photoPath = null;
            if ($request->hasFile('foto')) {
                $photoPath = $request->file('foto')->store('photos/students', 'public');
            }

            // 1. Create Student User
            $schoolObj = \App\Models\School::find($schoolId);
            $foundationId = $schoolObj ? $schoolObj->foundation_id : ($request->input('foundation_id') ?: $currentUser->foundation_id);

            $studentUser = User::create([
                'name'          => $fullName,
                'email'         => $studentEmail,
                'password'      => Hash::make('123456'),
                'role_id'       => $studentRole->id,
                'school_id'     => $schoolId,
                'foundation_id' => $foundationId,
                'phone'         => $request->no_hp,
                'is_active'     => true,
                'photo'         => $photoPath,
            ]);

            // Parse entry year
            $enrollYear = $request->tahun_masuk ? substr($request->tahun_masuk, 0, 4) : date('Y');
            $enrollDate = $enrollYear . '-07-01';

            // 2. Create Student Profile
            $studentProfile = StudentProfile::create([
                'user_id'         => $studentUser->id,
                'classroom_id'    => $request->kelas ?: null,
                'nisn'            => $request->nisn,
                'nik'             => $request->nik,
                'birth_place'     => $request->tempat_lahir,
                'birth_date'      => $request->tanggal_lahir,
                'gender'          => $this->mapGenderToDb($request->jenis_kelamin),
                'religion'        => $request->agama,
                'address'         => $request->alamat,
                'enrollment_date' => $enrollDate,
                'status'          => $this->mapStatusToDb($request->status),
            ]);

            // Catat keanggotaan kelas awal jika ada kelas dipilih
            if ($request->filled('kelas')) {
                $targetClass = Classroom::find($request->kelas);
                if ($targetClass) {
                    \App\Models\ClassroomStudent::create([
                        'classroom_id'       => $targetClass->id,
                        'student_profile_id' => $studentProfile->id,
                        'academic_year_id'   => $targetClass->academic_year_id,
                        'status'             => 'active',
                        'start_date'         => now()->toDateString(),
                        'notes'              => 'Pendaftaran awal',
                    ]);
                }
            }

            // Foto Profil & Registrasi Biometrik AI
            if ($request->hasFile('foto')) {
                $path = $request->file('foto')->store('student_faces', 'public');

                $embedding = null;
                try {
                    $response = Http::timeout(60)
                        ->attach('image', $request->file('foto')->get(), basename($path))
                        ->attach('name', $fullName)
                        ->post(config('services.ai.url') . '/api/represent');

                    if ($response->successful()) {
                        $resData = $response->json();
                        if (isset($resData['success']) && $resData['success'] === true) {
                            $embedding = $resData['embedding'];
                        } else {
                            Log::error("AI represent returned success false: " . json_encode($resData));
                        }
                    } else {
                        Log::error("AI represent HTTP failed (status " . $response->status() . "): " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("AI represent extraction failed: " . $e->getMessage());
                }

                if ($embedding) {
                    // Validasi Wajah Ganda
                    $otherStudents = StudentProfile::whereNotNull('embedding')->get();
                    foreach ($otherStudents as $other) {
                        if (is_array($other->embedding)) {
                            $sim = $this->cosineSimilarity($embedding, $other->embedding);
                            if ($sim >= 0.75) {
                                Storage::disk('public')->delete($path);
                                throw new \Exception("Foto wajah sudah terdaftar atas nama siswa lain (" . $other->user->name . "). Registrasi ditolak.");
                            }
                        }
                    }

                    $studentProfile->is_face_registered = true;
                    $studentProfile->embedding = $embedding;
                    $studentProfile->save();

                    $studentUser->photo = '/storage/' . $path;
                    $studentUser->save();
                    
                    Cache::forget('student_embeddings:global');
                } else {
                    Storage::disk('public')->delete($path);
                    throw new \Exception("Gagal mendeteksi wajah pada foto profil. Pastikan wajah tegak lurus.");
                }
            }

            // 3. Sync Parents (Ayah, Ibu, Wali) & Login Account
            $parentCreds = $this->syncStudentParents($request, $studentProfile, $studentUser, $parentRole);

            // 4. Sync Ekstrakurikuler
            if ($request->has('ekskul')) {
                $ekskulInput = $request->input('ekskul');
                $ekskulIds = is_array($ekskulInput) ? $ekskulInput : (is_string($ekskulInput) && strlen($ekskulInput) ? explode(',', $ekskulInput) : [$ekskulInput]);
                $ekskulIds = array_filter($ekskulIds, fn($val) => !empty($val));
                $studentProfile->extracurriculars()->sync($ekskulIds);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Student created successfully.',
                'data'    => [
                    'id'       => $studentUser->id,
                    'nisn'     => $studentProfile->nisn,
                    'email'    => $parentCreds['email'],
                    'phone'    => $parentCreds['phone'] ?: '-',
                    'password' => '123456',
                    'accounts' => [
                        [
                            'title'      => 'Akun Login Siswa',
                            'loginLabel' => 'NISN Login',
                            'loginValue' => $studentProfile->nisn,
                            'password'   => '123456',
                        ],
                        [
                            'title'      => 'Akun Login Orang Tua / Wali',
                            'loginLabel' => 'E-mail Login',
                            'loginValue' => $parentCreds['email'],
                            'password'   => '123456',
                        ],
                    ],
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Database transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified student.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $student = User::with(['school', 'studentProfile.classroom', 'studentProfile.parents.user', 'studentProfile.extracurriculars'])->find($id);

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student not found.',
            ], 404);
        }

        $profile = $student->studentProfile;
        $parentData = $this->extractParentsData($profile);
        $rawStudentEmail = $student->email && str_starts_with($student->email, 'siswa-') && str_ends_with($student->email, '@school.com') ? '' : ($student->email ?? '');

        $feForm = array_merge([
            'id'             => $student->id,
            'nama'           => $student->name,
            'nama_depan'     => explode(' ', $student->name)[0],
            'nama_belakang'  => count(explode(' ', $student->name)) > 1 ? implode(' ', array_slice(explode(' ', $student->name), 1)) : '',
            'nisn'           => $profile ? $profile->nisn : '',
            'nik'            => $profile ? $profile->nik : '',
            'tempat_lahir'   => $profile ? $profile->birth_place : '',
            'tanggal_lahir'  => $profile && $profile->birth_date ? $profile->birth_date->format('Y-m-d') : '',
            'jenis_kelamin'  => $profile ? $this->mapGenderToFe($profile->gender) : '',
            'agama'          => $profile ? ($profile->religion ?? '') : '',
            'alamat'         => $profile ? $profile->address : '',
            'foundation_id'  => $student->foundation_id ? (string) $student->foundation_id : ($student->school && $student->school->foundation_id ? (string) $student->school->foundation_id : ''),
            'sekolahId'      => $student->school_id ? (string) $student->school_id : ($profile && $profile->classroom && $profile->classroom->school_id ? (string) $profile->classroom->school_id : ''),
            'kelas'          => $profile && $profile->classroom_id ? (string)$profile->classroom_id : '',
            'kelas_nama'     => $profile && $profile->classroom ? $profile->classroom->name : '-',
            'ekskul'         => $profile ? $profile->extracurriculars->pluck('id')->map(fn($eid) => (string)$eid)->values()->toArray() : [],
            'ekskul_detail'  => $profile ? $profile->extracurriculars->map(fn($e) => ['id' => (string)$e->id, 'nama' => $e->name])->values()->toArray() : [],
            'status'         => $profile ? $this->mapStatusToFe($profile->status) : 'Aktif',
            'tahun_masuk'    => $profile && $profile->enrollment_date ? $profile->enrollment_date->format('Y-m-d') : '',
            'email'          => $rawStudentEmail,
            'no_hp'          => $student->phone ?? '',
            'foto'           => $student->photo ? (str_starts_with($student->photo, 'http') ? $student->photo : asset($student->photo)) : null,
            'riwayat_kelas'  => $profile ? $profile->classroomHistories()->with(['academicYear', 'homeroomTeacher'])->get()->map(function ($c) {
                return [
                    'classroom_id'       => $c->id,
                    'name'               => $c->name,
                    'grade'              => $c->grade,
                    'academic_year'      => $c->academicYear ? $c->academicYear->name : '-',
                    'semester'           => $c->academicYear ? ($c->academicYear->semester === 'odd' ? 'Ganjil' : 'Genap') : '-',
                    'wali_kelas'         => $c->homeroomTeacher ? $c->homeroomTeacher->name : 'Belum Ada',
                    'status_keanggotaan' => $c->pivot->status,
                    'start_date'         => !empty($c->pivot->start_date) ? substr((string)$c->pivot->start_date, 0, 10) : null,
                    'end_date'           => !empty($c->pivot->end_date) ? substr((string)$c->pivot->end_date, 0, 10) : null,
                    'notes'              => $c->pivot->notes,
                ];
            })->values()->toArray() : [],
        ], $parentData);

        return response()->json([
            'status' => 'success',
            'data'   => $feForm,
        ]);
    }

    /**
     * Update the specified student.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $studentUser = User::find($id);

        if (!$studentUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student not found.',
            ], 404);
        }

        $profile = $studentUser->studentProfile;

        $inputs = $request->all();
        foreach ($inputs as $key => $value) {
            if ($value === '') {
                $inputs[$key] = null;
            }
        }

        if (isset($inputs['foto']) && is_string($inputs['foto'])) {
            unset($inputs['foto']);
        }

        $validator = Validator::make($inputs, [
            'nama_depan'     => 'required|string|max:100',
            'nama_belakang'  => 'nullable|string|max:100',
            'nisn'           => 'required|string|max:50|unique:student_profiles,nisn,' . ($profile ? $profile->id : 'NULL'),
            'nik'            => 'nullable|string|max:50',
            'kelas'          => 'nullable',
            'status'         => 'required|string',
            'jenis_kelamin'  => 'required|string',
            'tanggal_lahir'  => 'required|date',
            'email'          => 'nullable|email|unique:users,email,' . $studentUser->id,
            'no_hp'          => 'nullable|string',
            'password'       => 'nullable|string|min:6',
            'foto'           => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
        ], [
            'foto.max' => 'Tolong masukkan foto kurang dari 10 MB.',
        ]);

        if ($validator->fails()) {
            \Illuminate\Support\Facades\Log::error('Student Update Validation Failed: ' . json_encode($validator->errors()->all()));
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $fullName = trim($request->nama_depan . ' ' . ($request->nama_belakang ?: ''));

            // Handle Photo Upload
            $photoPath = $studentUser->photo;
            if ($request->hasFile('foto')) {
                $photoPath = $request->file('foto')->store('photos/students', 'public');
            } elseif ($request->has('foto') && $request->foto === null) {
                $photoPath = null;
            }

            // 1. Update Student User
            $userUpdate = [
                'name'  => $fullName,
                'email' => $request->email ?: null,
                'phone' => $request->no_hp,
                'photo' => $photoPath,
            ];

            if ($request->user()->isSuperAdmin() && $request->filled('sekolahId')) {
                $newSchoolId = (int) $request->input('sekolahId');
                $schoolObj = \App\Models\School::find($newSchoolId);
                $userUpdate['school_id'] = $newSchoolId;
                if ($schoolObj) {
                    $userUpdate['foundation_id'] = $schoolObj->foundation_id;
                } elseif ($request->filled('foundation_id')) {
                    $userUpdate['foundation_id'] = (int) $request->input('foundation_id');
                }
            }

            $studentUser->update($userUpdate);

            // Parse entry year
            $enrollYear = $request->tahun_masuk ? substr($request->tahun_masuk, 0, 4) : date('Y');
            $enrollDate = $enrollYear . '-07-01';

            // 2. Update Student Profile
            if ($profile) {
                $dbStatus = $this->mapStatusToDb($request->status);
                $oldClassroomId = $profile->classroom_id;
                $newClassroomId = in_array($dbStatus, ['expelled', 'transferred', 'alumni']) ? null : ($request->kelas ?: null);

                $profile->update([
                    'classroom_id'    => $newClassroomId,
                    'nisn'            => $request->nisn,
                    'nik'             => $request->nik,
                    'birth_place'     => $request->tempat_lahir,
                    'birth_date'      => $request->tanggal_lahir,
                    'gender'          => $this->mapGenderToDb($request->jenis_kelamin),
                    'religion'        => $request->agama ?: $profile->religion,
                    'address'         => $request->alamat,
                    'enrollment_date' => $enrollDate,
                    'status'          => $dbStatus,
                ]);

                // Sinkronkan ke tabel riwayat kelas
                if ($oldClassroomId && ((string) $oldClassroomId !== (string) $newClassroomId)) {
                    $histStatus = $dbStatus === 'alumni' ? 'graduated' : ($dbStatus === 'transferred' ? 'transferred' : 'transferred');
                    \App\Models\ClassroomStudent::where('classroom_id', $oldClassroomId)
                        ->where('student_profile_id', $profile->id)
                        ->where('status', 'active')
                        ->update([
                            'status'   => $histStatus,
                            'end_date' => now()->toDateString(),
                            'notes'    => $newClassroomId ? 'Pindah kelas' : 'Status diperbarui: ' . $dbStatus,
                        ]);
                }

                if ($newClassroomId && ((string) $oldClassroomId !== (string) $newClassroomId)) {
                    $targetClass = Classroom::find($newClassroomId);
                    if ($targetClass) {
                        \App\Models\ClassroomStudent::updateOrCreate(
                            [
                                'classroom_id'       => $targetClass->id,
                                'student_profile_id' => $profile->id,
                            ],
                            [
                                'academic_year_id' => $targetClass->academic_year_id,
                                'status'           => 'active',
                                'start_date'       => now()->toDateString(),
                                'end_date'         => null,
                                'notes'            => 'Penempatan kelas',
                            ]
                        );
                    }
                }
            }

            // Foto Profil & Registrasi Biometrik AI
            if ($request->hasFile('foto') && $profile) {
                $path = $request->file('foto')->store('student_faces', 'public');

                $embedding = null;
                try {
                    $response = Http::timeout(60)
                        ->attach('image', $request->file('foto')->get(), basename($path))
                        ->attach('name', $fullName)
                        ->post(config('services.ai.url') . '/api/represent');

                    if ($response->successful()) {
                        $resData = $response->json();
                        if (isset($resData['success']) && $resData['success'] === true) {
                            $embedding = $resData['embedding'];
                        } else {
                            Log::error("AI represent returned success false: " . json_encode($resData));
                        }
                    } else {
                        Log::error("AI represent HTTP failed (status " . $response->status() . "): " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("AI represent extraction failed: " . $e->getMessage());
                }

                if ($embedding) {
                    // Validasi Wajah Ganda
                    $otherStudents = StudentProfile::where('id', '!=', $profile->id)
                        ->whereNotNull('embedding')
                        ->get();
                    foreach ($otherStudents as $other) {
                        if (is_array($other->embedding)) {
                            $sim = $this->cosineSimilarity($embedding, $other->embedding);
                            if ($sim >= 0.75) {
                                Storage::disk('public')->delete($path);
                                throw new \Exception("Foto wajah sudah terdaftar atas nama siswa lain (" . $other->user->name . "). Registrasi ditolak.");
                            }
                        }
                    }

                    // Hapus berkas wajah lama jika ada
                    if ($studentUser->photo) {
                        $oldPath = str_replace('/storage/', '', $studentUser->photo);
                        Storage::disk('public')->delete($oldPath);
                    }

                    $profile->is_face_registered = true;
                    $profile->embedding = $embedding;
                    $profile->save();

                    $studentUser->photo = '/storage/' . $path;
                    $studentUser->save();
                    
                    Cache::forget('student_embeddings:global');
                } else {
                    Storage::disk('public')->delete($path);
                    throw new \Exception("Gagal mendeteksi wajah pada foto profil. Pastikan wajah tegak lurus.");
                }
            }

            // 3. Sync Parents (Ayah, Ibu, Wali) & Login Account
            if ($profile) {
                $parentRole = Role::where('name', 'orang_tua')->first();
                $this->syncStudentParents($request, $profile, $studentUser, $parentRole);
            }

            // 4. Sync Ekstrakurikuler
            if ($profile && $request->has('ekskul')) {
                $ekskulInput = $request->input('ekskul');
                $ekskulIds = is_array($ekskulInput) ? $ekskulInput : (is_string($ekskulInput) && strlen($ekskulInput) ? explode(',', $ekskulInput) : [$ekskulInput]);
                $ekskulIds = array_filter($ekskulIds, fn($val) => !empty($val));
                $profile->extracurriculars()->sync($ekskulIds);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Student updated successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Database transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified student.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $studentUser = User::find($id);

        if (!$studentUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student not found.',
            ], 404);
        }

        // Scope check
        $currentUser = $request->user();
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->hasRole('admin_yayasan')) {
                if ($studentUser->foundation_id != $currentUser->foundation_id && ($studentUser->school && $studentUser->school->foundation_id != $currentUser->foundation_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($studentUser->school_id != $currentUser->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $profile = $studentUser->studentProfile;

        // Proteksi 1: Riwayat Keuangan / Pembayaran SPP
        $hasPayments = \App\Models\StudentPayment::where('student_id', $studentUser->id)->exists();
        $hasPaidBills = \App\Models\StudentBill::where('student_id', $studentUser->id)->where('status', 'paid')->exists();
        if ($hasPayments || $hasPaidBills) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Siswa tidak dapat dihapus permanen karena memiliki riwayat transaksi keuangan (SPP). Silakan ubah status siswa menjadi Nonaktif, Pindah, atau Alumni.',
            ], 422);
        }

        // Proteksi 2: Nilai Akademik
        if ($profile && \App\Models\AssessmentScore::where('student_id', $profile->id)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Siswa tidak dapat dihapus permanen karena sudah memiliki riwayat nilai akademik. Silakan ubah status siswa menjadi Nonaktif, Pindah, atau Alumni.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Bersihkan relasi orang tua dan ekskul jika ada
            if ($profile) {
                $profile->parents()->detach();
                $profile->extracurriculars()->detach();
                \App\Models\ClassroomStudent::where('student_profile_id', $profile->id)->delete();
                $profile->delete();
            }

            // Hapus tagihan unpaid (jika pendaftaran baru dibatalkan)
            \App\Models\StudentBill::where('student_id', $studentUser->id)->delete();

            $studentUser->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Siswa berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus siswa: ' . $e->getMessage(),
            ], 500);
        }
    }
}
