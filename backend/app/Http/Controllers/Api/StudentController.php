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
                return 'ayah';
            case 'mother':
                return 'ibu';
            case 'guardian':
            default:
                return 'wali';
        }
    }

    /**
     * Display a listing of the students.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = User::whereHas('role', function ($q) {
            $q->where('name', 'siswa');
        })->with(['studentProfile.classroom', 'studentProfile.parents.user']);

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
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, wali_kelas
            $query->where('school_id', $user->school_id);
        }

        // Filtering by class
        if ($request->has('kelasId') && $request->kelasId !== 'all') {
            $query->whereHas('studentProfile', function ($q) use ($request) {
                $q->where('classroom_id', $request->kelasId);
            });
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
            $parent = $profile && $profile->parents->isNotEmpty() ? $profile->parents->first() : null;
            
            return [
                'id'             => $student->id,
                'nama'           => $student->name,
                'email'          => $student->email,
                'no_hp'          => $student->phone,
                'foto'           => $student->photo ? (str_starts_with($student->photo, 'http') ? $student->photo : asset($student->photo)) : null,
                'nisn'           => $profile ? $profile->nisn : '',
                'kelasId'        => $profile ? $profile->classroom_id : null,
                'kelas'          => $profile && $profile->classroom ? $profile->classroom->name : '-',
                'tempat_lahir'   => $profile ? $profile->birth_place : '',
                'tanggal_lahir'  => $profile && $profile->birth_date ? $profile->birth_date->format('Y-m-d') : '',
                'jenisKelamin'   => $profile ? $this->mapGenderToFe($profile->gender) : '',
                'alamat'         => $profile ? $profile->address : '',
                'tahunMasuk'     => $profile && $profile->enrollment_date ? $profile->enrollment_date->format('Y') : '',
                'status'         => $profile ? $this->mapStatusToFe($profile->status) : 'Aktif',
                'payment_status' => \App\Models\StudentBill::where('student_id', $student->id)
                                    ->whereIn('status', ['unpaid', 'partial'])
                                    ->exists() ? 'Belum Lunas' : 'Lunas',
                'namaWali'       => $parent && $parent->user ? $parent->user->name : '',
                'hubungan_siswa' => $parent ? $this->mapRelationshipToFe($parent->relationship) : '',
                'kelamin_wali'   => $parent ? $this->mapGenderToFe($parent->gender) : '',
                'pekerjaan_wali' => $parent ? $parent->occupation : '',
                'email_wali'     => $parent && $parent->user ? $parent->user->email : '',
                'no_hp_wali'     => $parent && $parent->user ? $parent->user->phone : '',
            ];
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
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, wali_kelas
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
            'kelas'          => 'required', // classroom_id
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
            $studentEmail = $request->email ?: 'siswa-' . $request->nisn . '@school.com';
            
            // Handle Photo Upload
            $photoPath = null;
            if ($request->hasFile('foto')) {
                $photoPath = $request->file('foto')->store('photos/students', 'public');
            }

            // 1. Create Student User
            $studentUser = User::create([
                'name'      => $fullName,
                'email'     => $studentEmail,
                'password'  => Hash::make('123456'),
                'role_id'   => $studentRole->id,
                'school_id' => $schoolId,
                'phone'     => $request->no_hp,
                'is_active' => true,
                'photo'     => $photoPath,
            ]);

            // Parse entry year
            $enrollYear = $request->tahun_masuk ? substr($request->tahun_masuk, 0, 4) : date('Y');
            $enrollDate = $enrollYear . '-07-01';

            // 2. Create Student Profile
            $studentProfile = StudentProfile::create([
                'user_id'         => $studentUser->id,
                'classroom_id'    => $request->kelas,
                'nisn'            => $request->nisn,
                'nik'             => $request->nik,
                'birth_place'     => $request->tempat_lahir,
                'birth_date'      => $request->tanggal_lahir,
                'gender'          => $this->mapGenderToDb($request->jenis_kelamin),
                'address'         => $request->alamat,
                'enrollment_date' => $enrollDate,
                'status'          => $this->mapStatusToDb($request->status),
            ]);

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

            // 3. Create Parent (if provided)
            if ($request->has('nama_wali') && !empty($request->nama_wali)) {
                $parentEmail = $request->emailLogin ?: $request->email_wali ?: 'ortu-' . $request->nisn . '@school.com';
                $parentPhone = $request->noHpLogin ?: $request->no_hp_wali;

                // Create or find parent user account
                $parentUser = User::where('email', $parentEmail)->first();
                if (!$parentUser) {
                    $parentUser = User::create([
                        'name'      => $request->nama_wali,
                        'email'     => $parentEmail,
                        'password'  => Hash::make('123456'),
                        'role_id'   => $parentRole->id,
                        'school_id' => $schoolId,
                        'phone'     => $parentPhone,
                        'is_active' => true,
                    ]);
                }

                // Create parent profile
                $parentProfile = ParentProfile::updateOrCreate(
                    ['user_id' => $parentUser->id],
                    [
                        'gender'       => $this->mapGenderToDb($request->kelamin_wali),
                        'relationship' => $this->mapRelationshipToDb($request->hubungan_siswa),
                        'occupation'   => $request->pekerjaan_wali,
                        'address'      => $request->alamat,
                    ]
                );

                // Pivot association
                $studentProfile->parents()->syncWithoutDetaching([$parentProfile->id]);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Student created successfully.',
                'data'    => [
                    'id'       => $studentUser->id,
                    'email'    => $studentEmail,
                    'phone'    => $request->no_hp ?: '-',
                    'password' => '123456'
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
        $student = User::with(['studentProfile.classroom', 'studentProfile.parents.user'])->find($id);

        if (!$student) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Student not found.',
            ], 404);
        }

        $profile = $student->studentProfile;
        $parent = $profile && $profile->parents->isNotEmpty() ? $profile->parents->first() : null;

        $feForm = [
            'id'             => $student->id,
            'nama'           => $student->name,
            'nama_depan'     => explode(' ', $student->name)[0],
            'nama_belakang'  => count(explode(' ', $student->name)) > 1 ? implode(' ', array_slice(explode(' ', $student->name), 1)) : '',
            'nisn'           => $profile ? $profile->nisn : '',
            'nik'            => $profile ? $profile->nik : '',
            'tempat_lahir'   => $profile ? $profile->birth_place : '',
            'tanggal_lahir'  => $profile && $profile->birth_date ? $profile->birth_date->format('Y-m-d') : '',
            'jenis_kelamin'  => $profile ? ($profile->gender === 'male' ? 'JK01' : 'JK02') : '',
            'agama'          => 'Islam', // default or custom mapping if exist
            'alamat'         => $profile ? $profile->address : '',
            'kelas'          => $profile ? (string)$profile->classroom_id : '',
            'kelas_nama'     => $profile && $profile->classroom ? $profile->classroom->name : '-',
            'status'         => $profile ? ($profile->status === 'active' ? '1' : ($profile->status === 'expelled' ? '0' : ($profile->status === 'transferred' ? '2' : '3'))) : '1',
            'tahun_masuk'    => $profile && $profile->enrollment_date ? $profile->enrollment_date->format('Y-m-d') : '',
            'email'          => $student->email,
            'no_hp'          => $student->phone,
            'foto'           => $student->photo ? (str_starts_with($student->photo, 'http') ? $student->photo : asset($student->photo)) : null,
            
            // Parent
            'nama_wali'      => $parent && $parent->user ? $parent->user->name : '',
            'hubungan_siswa' => $parent ? $this->mapRelationshipToFe($parent->relationship) : '',
            'kelamin_wali'   => $parent ? ($parent->gender === 'male' ? 'JK01' : 'JK02') : '',
            'pekerjaan_wali' => $parent ? $parent->occupation : '',
            'email_wali'     => $parent && $parent->user ? $parent->user->email : '',
            'no_hp_wali'     => $parent && $parent->user ? $parent->user->phone : '',
            'emailLogin'     => $parent && $parent->user ? $parent->user->email : '',
            'noHpLogin'      => $parent && $parent->user ? $parent->user->phone : '',
        ];

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
            'kelas'          => 'required',
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
            $studentUser->update([
                'name'  => $fullName,
                'email' => $request->email ?: $studentUser->email,
                'phone' => $request->no_hp,
                'photo' => $photoPath,
            ]);

            // Parse entry year
            $enrollYear = $request->tahun_masuk ? substr($request->tahun_masuk, 0, 4) : date('Y');
            $enrollDate = $enrollYear . '-07-01';

            // 2. Update Student Profile
            if ($profile) {
                $profile->update([
                    'classroom_id'    => $request->kelas,
                    'nisn'            => $request->nisn,
                    'nik'             => $request->nik,
                    'birth_place'     => $request->tempat_lahir,
                    'birth_date'      => $request->tanggal_lahir,
                    'gender'          => $this->mapGenderToDb($request->jenis_kelamin),
                    'address'         => $request->alamat,
                    'enrollment_date' => $enrollDate,
                    'status'          => $this->mapStatusToDb($request->status),
                ]);
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

            // 3. Update parent (if provided)
            if ($request->has('nama_wali') && !empty($request->nama_wali) && $profile) {
                $parentEmail = $request->emailLogin ?: $request->email_wali ?: 'ortu-' . $request->nisn . '@school.com';
                $parentPhone = $request->noHpLogin ?: $request->no_hp_wali;

                $parent = $profile->parents->first();
                if ($parent) {
                    $parentUpdate = [
                        'name'  => $request->nama_wali,
                        'email' => $parentEmail,
                        'phone' => $parentPhone,
                    ];
                    if ($request->has('password') && $request->filled('password')) {
                        $parentUpdate['password'] = Hash::make($request->input('password'));
                    }
                    // Update parent user account
                    if ($parent->user) {
                        $parent->user->update($parentUpdate);
                    }

                    // Update parent profile
                    $parent->update([
                        'gender'       => $this->mapGenderToDb($request->kelamin_wali),
                        'relationship' => $this->mapRelationshipToDb($request->hubungan_siswa),
                        'occupation'   => $request->pekerjaan_wali,
                        'address'      => $request->alamat,
                    ]);
                } else {
                    // Create new parent user and profile
                    $parentRole = Role::where('name', 'orang_tua')->first();
                    if ($parentRole) {
                        $parentUser = User::create([
                            'name'      => $request->nama_wali,
                            'email'     => $parentEmail,
                            'password'  => Hash::make('123456'),
                            'role_id'   => $parentRole->id,
                            'school_id' => $studentUser->school_id,
                            'phone'     => $parentPhone,
                            'is_active' => true,
                        ]);

                        $newParentProfile = ParentProfile::create([
                            'user_id'      => $parentUser->id,
                            'gender'       => $this->mapGenderToDb($request->kelamin_wali),
                            'relationship' => $this->mapRelationshipToDb($request->hubungan_siswa),
                            'occupation'   => $request->pekerjaan_wali,
                            'address'      => $request->alamat,
                        ]);

                        $profile->parents()->syncWithoutDetaching([$newParentProfile->id]);
                    }
                }
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

        // StudentProfile will be deleted via cascadeOnDelete foreign keys
        // User account itself will be deleted
        $studentUser->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Student deleted successfully.',
        ]);
    }
}
