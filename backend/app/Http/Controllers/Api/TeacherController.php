<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\School;
use App\Models\Foundation;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /**
     * Map gender string.
     */
    private function mapGenderToDb(?string $gender): ?string
    {
        if (!$gender) return null;
        return in_array(strtolower($gender), ['laki-laki', 'male', 'jk01']) ? 'male' : 'female';
    }

    private function mapGenderToFe(?string $gender): string
    {
        if ($gender === 'male') return 'Laki-laki';
        if ($gender === 'female') return 'Perempuan';
        return '';
    }

    /**
     * Helper to get school and foundation scope (supports single or multiple unit_kerja).
     */
    private function resolveTenantId(Request $request, $user)
    {
        $schoolIds = [];
        $unitIds = [];
        $foundationId = null;

        // Frontend maps "unit_kerja" or "school_id"
        // Can be array (e.g. ["Y0001", "S0001"]) or string ("S0001") or integer
        $rawUnitVal = $request->input('unit_kerja') ?: $request->input('school_id');
        $rawUnits = is_array($rawUnitVal) ? $rawUnitVal : ($rawUnitVal ? [$rawUnitVal] : []);

        foreach ($rawUnits as $unitVal) {
            if (!$unitVal) continue;
            $strVal = trim((string) $unitVal);
            if (str_starts_with(strtoupper($strVal), 'S')) {
                $sId = (int) substr($strVal, 1);
                if ($sId > 0) {
                    $schoolIds[] = $sId;
                    $unitIds[] = 'S' . str_pad((string) $sId, 4, '0', STR_PAD_LEFT);
                }
            } elseif (str_starts_with(strtoupper($strVal), 'Y')) {
                $fId = (int) substr($strVal, 1);
                if ($fId > 0) {
                    $foundationId = $fId;
                    $unitIds[] = 'Y' . str_pad((string) $fId, 4, '0', STR_PAD_LEFT);
                }
            } elseif (is_numeric($strVal)) {
                $sId = (int) $strVal;
                if ($sId > 0) {
                    $schoolIds[] = $sId;
                    $unitIds[] = 'S' . str_pad((string) $sId, 4, '0', STR_PAD_LEFT);
                }
            }
        }

        $schoolIds = array_values(array_unique($schoolIds));
        $unitIds = array_values(array_unique($unitIds));
        $schoolId = $schoolIds[0] ?? null;

        if ($schoolId && !$foundationId) {
            $school = School::find($schoolId);
            if ($school) {
                $foundationId = $school->foundation_id;
            }
        }

        // Scope verification
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if (!empty($schoolIds)) {
                    $validCount = School::whereIn('id', $schoolIds)
                        ->where('foundation_id', $user->foundation_id)
                        ->count();
                    if ($validCount !== count($schoolIds)) {
                        return ['error' => 'Selected school does not belong to your foundation.'];
                    }
                }
                $foundationId = $user->foundation_id;
                if (empty($unitIds) && $foundationId) {
                    $unitIds = ['Y' . str_pad((string) $foundationId, 4, '0', STR_PAD_LEFT)];
                }
            } else { // admin_sekolah etc: unit kerja otomatis mengikuti sekolah milik admin_sekolah
                $schoolId = $user->school_id;
                $schoolIds = $schoolId ? [$schoolId] : [];
                $unitIds = $schoolId ? ['S' . str_pad((string) $schoolId, 4, '0', STR_PAD_LEFT)] : [];
                $school = $schoolId ? School::find($schoolId) : null;
                $foundationId = $school ? $school->foundation_id : $user->foundation_id;
            }
        }

        return [
            'school_id'     => $schoolId,
            'school_ids'    => $schoolIds,
            'foundation_id' => $foundationId,
            'unit_ids'      => $unitIds,
        ];
    }

    /**
     * Format unit_kerja label, unit_id, and unit_ids array for frontend response.
     */
    private function formatUnitData($teacherProfile, $user, $schoolsMap = null, $foundationsMap = null): array
    {
        $unitIds = [];
        if ($teacherProfile && !empty($teacherProfile->unit_ids) && is_array($teacherProfile->unit_ids)) {
            $unitIds = array_values(array_filter($teacherProfile->unit_ids));
        }

        if (empty($unitIds)) {
            if ($user->school_id) {
                $unitIds = ['S' . str_pad((string) $user->school_id, 4, '0', STR_PAD_LEFT)];
            } elseif ($user->foundation_id) {
                $unitIds = ['Y' . str_pad((string) $user->foundation_id, 4, '0', STR_PAD_LEFT)];
            }
        }

        $unitNames = [];
        foreach ($unitIds as $code) {
            $upper = strtoupper((string) $code);
            if (str_starts_with($upper, 'S')) {
                $sId = (int) substr($upper, 1);
                $name = $schoolsMap ? ($schoolsMap[$sId] ?? null) : School::where('id', $sId)->value('name');
                if ($name) $unitNames[] = $name;
            } elseif (str_starts_with($upper, 'Y')) {
                $fId = (int) substr($upper, 1);
                $name = $foundationsMap ? ($foundationsMap[$fId] ?? null) : Foundation::where('id', $fId)->value('name');
                if ($name) $unitNames[] = $name;
            }
        }

        $unitKerjaLabel = !empty($unitNames)
            ? implode(', ', $unitNames)
            : ($user->school ? $user->school->name : ($user->foundation ? $user->foundation->name : '-'));

        return [
            'unit_kerja' => $unitKerjaLabel,
            'unit_id'    => $unitIds[0] ?? null,
            'unit_ids'   => $unitIds,
        ];
    }

    /**
     * Map position/jabatan to Role.
     */
    private function resolveRoleFromPosition(?string $position): string
    {
        if (!$position) return 'guru';
        
        switch (strtolower($position)) {
            case 'kepala yayasan':
            case 'staff yayasan':
            case 'j001':
            case 'j002':
                return 'admin_yayasan';
            case 'kepala sekolah':
            case 'j003':
                return 'kepala_sekolah';
            case 'staff sekolah':
            case 'j005':
                return 'tata_usaha';
            case 'guru':
            case 'j004':
                return 'guru';
            case 'admin sekolah':
            case 'j006':
                return 'admin_sekolah';
            default:
                return 'guru';
        }
    }

    /**
     * Map jabatan value to frontend format.
     */
    private function mapJabatanToFe(?string $position): string
    {
        if (!$position) return '';
        switch (strtoupper($position)) {
            case 'J001': return 'Kepala Yayasan';
            case 'J002': return 'Staff Yayasan';
            case 'J003': return 'Kepala Sekolah';
            case 'J004': return 'Guru';
            case 'J005': return 'Staff Sekolah';
            case 'J006': return 'Admin Sekolah';
            default: return $position;
        }
    }

    /**
     * Map status kepegawaian value to frontend format.
     */
    private function mapStatusKepegawaianToFe(?string $status): string
    {
        if (!$status) return '';
        switch (strtoupper($status)) {
            case 'SK01': return 'Tetap';
            case 'SK02': return 'Kontrak';
            case 'SK03': return 'Honorer';
            default: return $status;
        }
    }

    /**
     * Display a listing of teachers & staff.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = TeacherProfile::with(['user.school', 'user.role']);

        if ($user->isSuperAdmin()) {
            if ($request->has('school_id')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('school_id', $request->input('school_id'));
                });
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('user.school', function ($sq) use ($user) {
                    $sq->where('foundation_id', $user->foundation_id);
                })->orWhereHas('user', function ($sq) use ($user) {
                    $sq->where('foundation_id', $user->foundation_id)->whereNull('school_id');
                });
            });

            if ($request->has('school_id')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('school_id', $request->input('school_id'));
                });
            }
        } else { // admin_sekolah etc.
            $schoolCode = 'S' . str_pad((string) $user->school_id, 4, '0', STR_PAD_LEFT);
            $query->where(function ($q) use ($user, $schoolCode) {
                $q->whereHas('user', function ($uq) use ($user) {
                    $uq->where('school_id', $user->school_id);
                })->orWhereJsonContains('unit_ids', $schoolCode);
            });
        }

        // Apply filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                })->orWhere('nip_nuptk', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status !== 'all') {
            $statusVal = $request->input('status');
            // If SK01, SK02 etc are used
            $query->where(function ($q) use ($statusVal) {
                $q->where('employment_status', $statusVal)
                  ->orWhere('employment_status', $this->mapStatusKepegawaianToFe($statusVal));
            });
        }

        $teachers = $query->latest('updated_at')->get();
        $schoolsMap = School::pluck('name', 'id')->all();
        $foundationsMap = Foundation::pluck('name', 'id')->all();

        $formatted = $teachers->map(function ($item) use ($schoolsMap, $foundationsMap) {
            $u = $item->user;
            if (!$u) return null;

            $masaKerja = '-';
            if ($item->join_date) {
                $diff = $item->join_date->diff(now());
                $years = $diff->y;
                $months = $diff->m;
                $masaKerja = ($years > 0 ? "$years tahun " : '') . ($months > 0 ? "$months bulan" : ($years == 0 ? "0 bulan" : ''));
                if (empty(trim($masaKerja))) {
                    $masaKerja = '0 bulan';
                }
            }

            $unitData = $this->formatUnitData($item, $u, $schoolsMap, $foundationsMap);

            return [
                'id'                 => $u->id,
                'nama'               => $u->name,
                'nama_depan'         => explode(' ', $u->name)[0] ?? '',
                'nama_belakang'      => implode(' ', array_slice(explode(' ', $u->name), 1)) ?: '',
                'email'              => $u->email,
                'no_hp'              => $u->phone,
                'foto'               => $u->photo ?: null,
                'role'               => $u->role ? $u->role->name : null,
                'nik'                => $item->nik,
                'nip'                => $item->nip_nuptk,
                'nip_nuptk'          => $item->nip_nuptk,
                'tempat_lahir'       => $item->birth_place,
                'tanggal_lahir'      => $item->birth_date ? $item->birth_date->format('Y-m-d') : null,
                'jenis_kelamin'      => $this->mapGenderToFe($item->gender),
                'agama'              => $item->religion,
                'status_pernikahan'  => $item->marital_status,
                'pendidikan_terakhir'=> $item->last_education,
                'gelar_depan'        => $item->front_title,
                'gelar_belakang'     => $item->back_title,
                'alamat'             => $item->address,
                'jabatan'            => $this->mapJabatanToFe($item->position),
                'status_kepegawaian' => $this->mapStatusKepegawaianToFe($item->employment_status),
                'unit_kerja'         => $unitData['unit_kerja'],
                'unit_id'            => $unitData['unit_id'],
                'unit_ids'           => $unitData['unit_ids'],
                'status_aktif'       => $u->is_active ? 'Aktif' : 'Nonaktif',
                'emailLogin'         => $u->email,
                'noHpLogin'          => $u->phone,
                'masaKerja'          => $masaKerja,
                'join_date'          => $item->join_date ? $item->join_date->format('Y-m-d') : null,
            ];
        })->filter()->values();

        $statsQuery = TeacherProfile::query();
        if ($user->isSuperAdmin()) {
            // no restriction
        } elseif ($user->hasRole('admin_yayasan')) {
            $statsQuery->where(function ($q) use ($user) {
                $q->whereHas('user.school', function ($sq) use ($user) {
                    $sq->where('foundation_id', $user->foundation_id);
                })->orWhereHas('user', function ($sq) use ($user) {
                    $sq->where('foundation_id', $user->foundation_id)->whereNull('school_id');
                });
            });
        } else { // admin_sekolah etc.
            $statsQuery->whereHas('user', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            });
        }

        $total = (clone $statsQuery)->count();
        $active = (clone $statsQuery)->whereHas('user', function ($q) {
            $q->where('is_active', true);
        })->count();
        $guru = (clone $statsQuery)->where('position', 'J004')->count();
        $staff = (clone $statsQuery)->where('position', '!=', 'J004')->count();

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
            'stats'  => [
                'total' => $total,
                'active' => $active,
                'guru' => $guru,
                'staff' => $staff,
            ]
        ]);
    }

    /**
     * Store a newly created teacher/staff user.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = $this->resolveTenantId($request, $user);

        if (isset($tenant['error'])) {
            return response()->json([
                'status'  => 'error',
                'message' => $tenant['error'],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_depan'         => 'required|string|max:100',
            'nama_belakang'      => 'nullable|string|max:100',
            'emailLogin'         => 'required|email|unique:users,email',
            'noHpLogin'          => 'nullable|string',
            'password'           => 'nullable|string|min:6',
            'nik'                => 'nullable|string|max:50',
            'nip_nuptk'          => 'nullable|string|max:50',
            'tempat_lahir'       => 'nullable|string',
            'tanggal_lahir'      => 'nullable|date',
            'jenis_kelamin'      => 'nullable|string',
            'agama'              => 'nullable|string',
            'status_pernikahan'  => 'nullable|string',
            'pendidikan_terakhir'=> 'nullable|string',
            'gelar_depan'        => 'nullable|string',
            'gelar_belakang'     => 'nullable|string',
            'alamat'             => 'nullable|string',
            'jabatan'            => 'nullable|string',
            'status_kepegawaian' => 'nullable|string',
            'status_aktif'       => 'nullable|string',
            'join_date'          => 'nullable|date',
            'foto'               => 'nullable|image|max:2048',
            'subject_ids' => 'nullable|array',
            'subject_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('subjects', 'id')->where(function ($query) use ($tenant) {
                    if (!empty($tenant['school_ids'])) {
                        $query->whereIn('school_id', $tenant['school_ids']);
                    } elseif ($tenant['school_id']) {
                        $query->where('school_id', $tenant['school_id']);
                    } elseif ($tenant['foundation_id']) {
                        $schoolIds = School::where('foundation_id', $tenant['foundation_id'])->pluck('id');
                        $query->whereIn('school_id', $schoolIds);
                    }
                    $query->where('is_active', true);
                }),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $isTeacher = in_array(
            strtoupper((string) $request->input('jabatan')),
            ['GURU', 'J004'],
            true
        );

        if ($isTeacher && empty($request->input('subject_ids', []))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => [
                    'subject_ids' => [
                        'Mata pelajaran wajib dipilih untuk jabatan guru.'
                    ],
                ],
            ], 422);
        }

        $roleName = $this->resolveRoleFromPosition($request->input('jabatan'));
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Configured role ' . $roleName . ' not found.',
            ], 500);
        }

        $passwordPlain = $request->input('password') ?: '123456';

        DB::beginTransaction();
        try {
            $fullName = trim($request->input('nama_depan') . ' ' . ($request->input('nama_belakang') ?: ''));
            
            // Handle Photo Upload
            $photoPath = null;
            if ($request->hasFile('foto')) {
                $photoPath = $request->file('foto')->store('photos/teachers', 'public');
            }

            $newUser = User::create([
                'name'          => $fullName,
                'email'         => $request->input('emailLogin'),
                'password'      => Hash::make($passwordPlain),
                'role_id'       => $role->id,
                'school_id'     => $tenant['school_id'],
                'foundation_id' => $tenant['foundation_id'],
                'phone'         => $request->input('noHpLogin') ?: $request->input('no_hp'),
                'is_active'     => in_array(strtolower($request->input('status_aktif', 'aktif')), ['aktif', 'active', 'true', '1']),
                'photo'         => $photoPath,
            ]);

            TeacherProfile::create([
                'user_id'           => $newUser->id,
                'nik'               => $request->input('nik'),
                'nip_nuptk'         => $request->input('nip_nuptk') ?: $request->input('nip'),
                'birth_place'       => $request->input('tempat_lahir'),
                'birth_date'        => $request->input('tanggal_lahir'),
                'gender'            => $this->mapGenderToDb($request->input('jenis_kelamin')),
                'religion'          => $request->input('agama'),
                'marital_status'    => $request->input('status_pernikahan'),
                'last_education'    => $request->input('pendidikan_terakhir'),
                'front_title'       => $request->input('gelar_depan'),
                'back_title'        => $request->input('gelar_belakang'),
                'address'           => $request->input('alamat'),
                'position'          => $request->input('jabatan'),
                'employment_status' => $request->input('status_kepegawaian'),
                'unit_ids'          => $tenant['unit_ids'] ?? [],
                'join_date'         => $request->input('join_date') ?: now(),
            ]);

            if ($isTeacher) {
                foreach ($request->input('subject_ids', []) as $subjectId) {
                    $subject = Subject::find($subjectId);
                    $subjSchoolId = $subject ? $subject->school_id : $tenant['school_id'];

                    $academicYearId = AcademicYear::where('school_id', $subjSchoolId)
                        ->where('is_active', true)
                        ->value('id') ?: AcademicYear::where('school_id', $subjSchoolId)->latest('id')->value('id');

                    if (!$academicYearId) {
                        throw new \Exception(
                            'Tahun ajaran aktif belum tersedia untuk sekolah ini.'
                        );
                    }

                    $classroomId = Classroom::where('school_id', $subjSchoolId)->value('id') ?: 1;

                    TeacherSubjectAssignment::create([
                        'school_id'        => $subjSchoolId,
                        'teacher_id'       => $newUser->id,
                        'subject_id'       => $subjectId,
                        'classroom_id'     => $classroomId,
                        'academic_year_id' => $academicYearId,
                        'is_active'        => true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Guru/Staff created successfully.',
                'data'    => [
                    'id'       => $newUser->id,
                    'email'    => $newUser->email,
                    'phone'    => $newUser->phone ?: '-',
                    'password' => $passwordPlain,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display details of a specific teacher.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $teacher = TeacherProfile::with(['user.school', 'user.role', 'subjects'])->where('user_id', $id)->first();

        if (!$teacher) {
            // Check if user exists but has no profile
            $u = User::with(['school', 'role'])->find($id);
            if ($u) {
                // Return dummy/empty profile
                $teacher = new TeacherProfile(['user_id' => $u->id]);
                $teacher->setRelation('user', $u);
                $teacher->setRelation('subjects', collect());
            } else {
                return response()->json(['status' => 'error', 'message' => 'Teacher not found.'], 404);
            }
        }

        // Scope check
        $u = $teacher->user;
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($u->foundation_id != $user->foundation_id && ($u->school && $u->school->foundation_id != $user->foundation_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                $schoolCode = 'S' . str_pad((string) $user->school_id, 4, '0', STR_PAD_LEFT);
                $inUnitIds = is_array($teacher->unit_ids) && in_array($schoolCode, $teacher->unit_ids, true);
                if ($u->school_id != $user->school_id && !$inUnitIds) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $masaKerja = '-';
        if ($teacher->join_date) {
            $diff = $teacher->join_date->diff(now());
            $years = $diff->y;
            $months = $diff->m;
            $masaKerja = ($years > 0 ? "$years tahun " : '') . ($months > 0 ? "$months bulan" : ($years == 0 ? "0 bulan" : ''));
        }

        $unitData = $this->formatUnitData($teacher, $u);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'                 => $u->id,
                'nama'               => $u->name,
                'nama_depan'         => explode(' ', $u->name)[0] ?? '',
                'nama_belakang'      => implode(' ', array_slice(explode(' ', $u->name), 1)) ?: '',
                'email'              => $u->email,
                'no_hp'              => $u->phone,
                'foto'               => $u->photo ?: null,
                'role'               => $u->role ? $u->role->name : null,
                'nik'                => $teacher->nik,
                'nip'                => $teacher->nip_nuptk,
                'nip_nuptk'          => $teacher->nip_nuptk,
                'tempat_lahir'       => $teacher->birth_place,
                'tanggal_lahir'      => $teacher->birth_date ? $teacher->birth_date->format('Y-m-d') : null,
                'jenis_kelamin'      => $this->mapGenderToFe($teacher->gender),
                'agama'              => $teacher->religion,
                'status_pernikahan'  => $teacher->marital_status,
                'pendidikan_terakhir'=> $teacher->last_education,
                'gelar_depan'        => $teacher->front_title,
                'gelar_belakang'     => $teacher->back_title,
                'alamat'             => $teacher->address,
                'jabatan'            => $this->mapJabatanToFe($teacher->position),
                'status_kepegawaian' => $this->mapStatusKepegawaianToFe($teacher->employment_status),
                'unit_kerja'         => $unitData['unit_kerja'],
                'unit_id'            => $unitData['unit_id'],
                'unit_ids'           => $unitData['unit_ids'],
                'status_aktif'       => $u->is_active ? 'Aktif' : 'Nonaktif',
                'emailLogin'         => $u->email,
                'noHpLogin'          => $u->phone,
                'masaKerja'          => $masaKerja,
                'join_date'          => $teacher->join_date ? $teacher->join_date->format('Y-m-d') : null,
                'subject_ids'        => $teacher->subjects->pluck('id')->map(fn($subjectId) => (string) $subjectId)->unique()->values(),
                'subjects'           => $teacher->subjects->unique('id')->values()->map(function ($subject) {
                    return [
                        'id'   => $subject->id,
                        'code' => $subject->code,
                        'name' => $subject->name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update a teacher profile + user.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        $teacher = TeacherProfile::with('user')->where('user_id', $id)->first();
        $newUserCreated = false;

        if (!$teacher) {
            $u = User::find($id);
            if ($u) {
                $teacher = TeacherProfile::create(['user_id' => $u->id]);
                $teacher->setRelation('user', $u);
                $newUserCreated = true;
            } else {
                return response()->json(['status' => 'error', 'message' => 'Teacher not found.'], 404);
            }
        }

        $u = $teacher->user;

        // Scope check
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->hasRole('admin_yayasan')) {
                if ($u->foundation_id != $currentUser->foundation_id && ($u->school && $u->school->foundation_id != $currentUser->foundation_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                $schoolCode = 'S' . str_pad((string) $currentUser->school_id, 4, '0', STR_PAD_LEFT);
                $inUnitIds = is_array($teacher->unit_ids) && in_array($schoolCode, $teacher->unit_ids, true);
                if ($u->school_id != $currentUser->school_id && !$inUnitIds) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        $validator = Validator::make($request->all(), [
            'nama_depan'         => 'sometimes|required|string|max:100',
            'nama_belakang'      => 'nullable|string|max:100',
            'emailLogin'         => 'sometimes|required|email|unique:users,email,' . $u->id,
            'noHpLogin'          => 'nullable|string',
            'password'           => 'nullable|string|min:6',
            'nik'                => 'nullable|string|max:50',
            'nip_nuptk'          => 'nullable|string|max:50',
            'tempat_lahir'       => 'nullable|string',
            'tanggal_lahir'      => 'nullable|date',
            'jenis_kelamin'      => 'nullable|string',
            'agama'              => 'nullable|string',
            'status_pernikahan'  => 'nullable|string',
            'pendidikan_terakhir'=> 'nullable|string',
            'gelar_depan'        => 'nullable|string',
            'gelar_belakang'     => 'nullable|string',
            'alamat'             => 'nullable|string',
            'jabatan'            => 'nullable|string',
            'status_kepegawaian' => 'nullable|string',
            'status_aktif'       => 'nullable|string',
            'join_date'          => 'nullable|date',
            'foto'               => 'nullable|image|max:2048',
            'subject_ids'   => 'nullable|array',
            'subject_ids.*' => 'integer|distinct|exists:subjects,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $finalPosition = $request->input(
            'jabatan',
            $teacher->position
        );

        $isTeacher = in_array(
            strtoupper((string) $finalPosition),
            ['GURU', 'J004'],
            true
        );

        if ($isTeacher && empty($request->input('subject_ids', []))) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => [
                    'subject_ids' => [
                        'Mata pelajaran wajib dipilih untuk jabatan guru.'
                    ],
                ],
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Update User fields
            if ($request->has('nama_depan')) {
                $fullName = trim($request->input('nama_depan') . ' ' . ($request->input('nama_belakang') ?: ''));
                $u->name = $fullName;
            }
            if ($request->has('emailLogin')) {
                $u->email = $request->input('emailLogin');
            }
            if ($request->has('noHpLogin')) {
                $u->phone = $request->input('noHpLogin');
            }
            if ($request->has('password') && $request->input('password')) {
                $u->password = Hash::make($request->input('password'));
            }
            if ($request->has('status_aktif')) {
                $u->is_active = in_array(strtolower($request->input('status_aktif')), ['aktif', 'active', 'true', '1']);
            }
            
            // If position/jabatan is changed, we update the role
            if ($request->has('jabatan')) {
                $roleName = $this->resolveRoleFromPosition($request->input('jabatan'));
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $u->role_id = $role->id;
                }
            }

            $profileData = [];

            if ($request->has('unit_kerja') || !$currentUser->isSuperAdmin() && !$currentUser->hasRole('admin_yayasan')) {
                $tenant = $this->resolveTenantId($request, $currentUser);
                if (isset($tenant['error'])) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => $tenant['error'],
                    ], 403);
                }
                if ($request->has('unit_kerja') || !$u->school_id) {
                    $u->school_id = $tenant['school_id'];
                    $u->foundation_id = $tenant['foundation_id'];
                    if (!empty($tenant['unit_ids'])) {
                        $profileData['unit_ids'] = $tenant['unit_ids'];
                    }
                }
            }

            // Handle Photo Upload
            if ($request->hasFile('foto')) {
                $u->photo = $request->file('foto')->store('photos/teachers', 'public');
            } elseif ($request->has('foto') && $request->foto === null) {
                $u->photo = null;
            }

            $u->save();

            // Update Teacher Profile fields
            if ($request->has('nik')) $profileData['nik'] = $request->input('nik');
            if ($request->has('nip_nuptk')) $profileData['nip_nuptk'] = $request->input('nip_nuptk');
            if ($request->has('tempat_lahir')) $profileData['birth_place'] = $request->input('tempat_lahir');
            if ($request->has('tanggal_lahir')) $profileData['birth_date'] = $request->input('tanggal_lahir');
            if ($request->has('jenis_kelamin')) $profileData['gender'] = $this->mapGenderToDb($request->input('jenis_kelamin'));
            if ($request->has('agama')) $profileData['religion'] = $request->input('agama');
            if ($request->has('status_pernikahan')) $profileData['marital_status'] = $request->input('status_pernikahan');
            if ($request->has('pendidikan_terakhir')) $profileData['last_education'] = $request->input('pendidikan_terakhir');
            if ($request->has('gelar_depan')) $profileData['front_title'] = $request->input('gelar_depan');
            if ($request->has('gelar_belakang')) $profileData['back_title'] = $request->input('gelar_belakang');
            if ($request->has('alamat')) $profileData['address'] = $request->input('alamat');
            if ($request->has('jabatan')) $profileData['position'] = $request->input('jabatan');
            if ($request->has('status_kepegawaian')) $profileData['employment_status'] = $request->input('status_kepegawaian');
            if ($request->has('join_date')) $profileData['join_date'] = $request->input('join_date');

            $teacher->update($profileData);

            if (!$isTeacher) {
                TeacherSubjectAssignment::where('teacher_id', $u->id)->delete();
            } else {
                $subjectIds = array_values(array_unique(array_filter(
                    array_map('intval', $request->input('subject_ids', []))
                )));

                TeacherSubjectAssignment::where('teacher_id', $u->id)
                    ->whereNotIn('subject_id', $subjectIds)
                    ->delete();

                $existingSubjectIds = TeacherSubjectAssignment::where('teacher_id', $u->id)
                    ->pluck('subject_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $newSubjectIds = array_diff($subjectIds, $existingSubjectIds);

                foreach ($newSubjectIds as $subjectId) {
                    $subject = Subject::find($subjectId);
                    $subjSchoolId = $subject ? $subject->school_id : $u->school_id;

                    $academicYearId = AcademicYear::where('school_id', $subjSchoolId)
                        ->where('is_active', true)
                        ->value('id') ?: AcademicYear::where('school_id', $subjSchoolId)->latest('id')->value('id');

                    if (!$academicYearId) {
                        throw new \Exception(
                            'Tahun ajaran aktif belum tersedia untuk sekolah ini.'
                        );
                    }

                    $classroomId = Classroom::where('school_id', $subjSchoolId)->value('id') ?: 1;

                    TeacherSubjectAssignment::create([
                        'school_id'        => $subjSchoolId,
                        'teacher_id'       => $u->id,
                        'subject_id'       => $subjectId,
                        'classroom_id'     => $classroomId,
                        'academic_year_id' => $academicYearId,
                        'is_active'        => true,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Guru/Staff updated successfully.',
                'data'    => $teacher,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Transaction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a teacher profile + user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        $u = User::find($id);

        if (!$u) {
            return response()->json(['status' => 'error', 'message' => 'Teacher/Staff user not found.'], 404);
        }

        // Scope check
        if (!$currentUser->isSuperAdmin()) {
            if ($currentUser->hasRole('admin_yayasan')) {
                if ($u->foundation_id != $currentUser->foundation_id && ($u->school && $u->school->foundation_id != $currentUser->foundation_id)) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($u->school_id != $currentUser->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Delete TeacherProfile first
            TeacherProfile::where('user_id', $u->id)->delete();
            // Delete User
            $u->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Guru/Staff deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Deletion failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
