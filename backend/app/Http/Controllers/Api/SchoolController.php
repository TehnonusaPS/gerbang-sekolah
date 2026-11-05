<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasCurriculumMappingTrait;
use App\Models\School;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchoolController extends Controller
{
    use HasCurriculumMappingTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $query = School::with('foundation:id,name,code', 'curriculum:id,name,code,level', 'users:id,school_id,email,phone')->withCount('students');

            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                       ->orWhere('npsn', 'ilike', "%{$search}%");
                });
            }

            if ($request->filled('foundation_id')) {
                $query->where('foundation_id', $request->input('foundation_id'));
            }

            if ($request->filled('level') && $request->input('level') !== 'all') {
                $level = strtoupper(trim((string) $request->input('level')));
                $levelMap = [
                    'TK'    => ['TK', 'JP001'],
                    'JP001' => ['TK', 'JP001'],
                    'SD'    => ['SD', 'JP002'],
                    'JP002' => ['SD', 'JP002'],
                    'SMP'   => ['SMP', 'JP003'],
                    'JP003' => ['SMP', 'JP003'],
                    'SMA'   => ['SMA'],
                    'SMK'   => ['SMK'],
                ];
                $matchingLevels = $levelMap[$level] ?? [$level];
                $query->whereIn('level', $matchingLevels);
            }

            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }

            // Sorting:
            // 1. Status: Aktif -> Trial -> Nonaktif
            // 2. Default: Jenjang (TK -> SD -> SMP -> SMA -> SMK), lalu Nama Sekolah (A-Z)
            $sortBy = $request->input('sort_by', 'jenjang');
            $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

            $jenjangOrderSql = "CASE 
                WHEN UPPER(level) IN ('TK', 'JP001') THEN 1 
                WHEN UPPER(level) IN ('SD', 'JP002') THEN 2 
                WHEN UPPER(level) IN ('SMP', 'JP003') THEN 3 
                WHEN UPPER(level) IN ('SMA', 'JP004') THEN 4 
                WHEN UPPER(level) IN ('SMK', 'JP005') THEN 5 
                ELSE 6 END";

            $query->orderByRaw("CASE WHEN status = 'active' THEN 1 WHEN status = 'trial' THEN 2 ELSE 3 END ASC");

            if ($sortBy === 'jenjang' || $sortBy === 'level') {
                $query->orderByRaw("{$jenjangOrderSql} {$sortOrder}")
                      ->orderBy('name', 'asc');
            } elseif ($sortBy === 'nama' || $sortBy === 'name') {
                $query->orderBy('name', $sortOrder)
                      ->orderByRaw("{$jenjangOrderSql} ASC");
            } else {
                $query->orderByRaw("{$jenjangOrderSql} ASC")
                      ->orderBy('name', 'asc');
            }
            $query->orderBy('id', 'asc');

            $statsQuery = School::query();
            if ($request->filled('foundation_id')) {
                $statsQuery->where('foundation_id', $request->input('foundation_id'));
            }
            $total = (clone $statsQuery)->count();
            $active = (clone $statsQuery)->where('status', 'active')->count();
            $trial = (clone $statsQuery)->where('status', 'trial')->count();
            $inactive = (clone $statsQuery)->where('status', 'inactive')->count();

            $rawLevels = (clone $statsQuery)->whereNotNull('level')->where('level', '!=', '')->distinct()->pluck('level')->toArray();
            $availableLevels = $this->formatAvailableLevels($rawLevels);

            $perPage = (int) $request->input('per_page', 15);
            $page    = (int) $request->input('page', 1);

            return response()->json([
                'status'           => 'success',
                'data'             => $query->paginate($request->input('per_page', $perPage), ['*'], 'page', $page),
                'stats'            => [
                    'total'    => $total,
                    'active'   => $active,
                    'trial'    => $trial,
                    'inactive' => $inactive,
                ],
                'available_levels' => $availableLevels,
            ]);
        }

        if ($user->hasRole('admin_yayasan')) {
            $query = School::with('foundation:id,name,code', 'curriculum:id,name,code,level', 'users:id,school_id,email,phone')
                ->withCount('students')
                ->where('foundation_id', $user->foundation_id);

                // dd($query);
            if ($request->has('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                       ->orWhere('npsn', 'ilike', "%{$search}%");
                });
            }

            if ($request->filled('level') && $request->input('level') !== 'all') {
                $level = strtoupper(trim((string) $request->input('level')));
                $levelMap = [
                    'TK'    => ['TK', 'JP001'],
                    'JP001' => ['TK', 'JP001'],
                    'SD'    => ['SD', 'JP002'],
                    'JP002' => ['SD', 'JP002'],
                    'SMP'   => ['SMP', 'JP003'],
                    'JP003' => ['SMP', 'JP003'],
                    'SMA'   => ['SMA'],
                    'SMK'   => ['SMK'],
                ];
                $matchingLevels = $levelMap[$level] ?? [$level];
                $query->whereIn('level', $matchingLevels);
            }

            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }

            // Sorting:
            // 1. Status: Aktif -> Trial -> Nonaktif
            // 2. Default: Jenjang (TK -> SD -> SMP -> SMA -> SMK), lalu Nama Sekolah (A-Z)
            $sortBy = $request->input('sort_by', 'jenjang');
            $sortOrder = strtolower((string) $request->input('sort_order', 'asc')) === 'desc' ? 'desc' : 'asc';

            $jenjangOrderSql = "CASE 
                WHEN UPPER(level) IN ('TK', 'JP001') THEN 1 
                WHEN UPPER(level) IN ('SD', 'JP002') THEN 2 
                WHEN UPPER(level) IN ('SMP', 'JP003') THEN 3 
                WHEN UPPER(level) IN ('SMA', 'JP004') THEN 4 
                WHEN UPPER(level) IN ('SMK', 'JP005') THEN 5 
                ELSE 6 END";

            $query->orderByRaw("CASE WHEN status = 'active' THEN 1 WHEN status = 'trial' THEN 2 ELSE 3 END ASC");

            if ($sortBy === 'jenjang' || $sortBy === 'level') {
                $query->orderByRaw("{$jenjangOrderSql} {$sortOrder}")
                      ->orderBy('name', 'asc');
            } elseif ($sortBy === 'nama' || $sortBy === 'name') {
                $query->orderBy('name', $sortOrder)
                      ->orderByRaw("{$jenjangOrderSql} ASC");
            } else {
                $query->orderByRaw("{$jenjangOrderSql} ASC")
                      ->orderBy('name', 'asc');
            }
            $query->orderBy('id', 'asc');

            $statsQuery = School::where('foundation_id', $user->foundation_id);
            $total = (clone $statsQuery)->count();
            $active = (clone $statsQuery)->where('status', 'active')->count();
            $trial = (clone $statsQuery)->where('status', 'trial')->count();
            $inactive = (clone $statsQuery)->where('status', 'inactive')->count();

            $rawLevels = (clone $statsQuery)->whereNotNull('level')->where('level', '!=', '')->distinct()->pluck('level')->toArray();
            $availableLevels = $this->formatAvailableLevels($rawLevels);

            $perPage = (int) $request->input('per_page', 15);
            $page    = (int) $request->input('page', 1);

            return response()->json([
                'status'           => 'success',
                'data'             => $query->paginate($request->input('per_page', $perPage), ['*'], 'page', $page),
                'stats'            => [
                    'total'    => $total,
                    'active'   => $active,
                    'trial'    => $trial,
                    'inactive' => $inactive,
                ],
                'available_levels' => $availableLevels,
            ]);
        }

        if ($user->school_id) { // admin_sekolah, kepala_sekolah, tata_usaha, guru
            $school = School::with('foundation:id,name,code', 'curriculum:id,name,code,level')->withCount('students')->find($user->school_id);
            if (!$school) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'School not found for your account.',
                ], 404);
            }

            $stats = [
                'total' => 1,
                'active' => $school->status === 'active' ? 1 : 0,
                'trial' => $school->status === 'trial' ? 1 : 0,
                'inactive' => $school->status === 'inactive' ? 1 : 0,
            ];

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'current_page' => 1,
                    'data' => [$school],
                    'total' => 1,
                ],
                'stats' => $stats,
            ]);
        }

        return response()->json([
            'message' => 'Forbidden: You do not have the required access role.',
        ], 403);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $isAdminYayasan = $user->hasRole('admin_yayasan');

        if (!$isSuperAdmin && !$isAdminYayasan) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        $rules = [
            'name'                 => 'required|string|max:255',
            'npsn'                 => 'nullable|string|unique:schools,npsn|max:50',
            'level'                => 'nullable|string|max:50',
            'established_date'     => 'nullable|date',
            'status'               => 'nullable|in:active,inactive,trial',
            'address'              => 'nullable|string',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'website'              => 'nullable|string|max:255',
            'instagram'            => 'nullable|string|max:255',
            'facebook'             => 'nullable|string|max:255',
            'decree_number'        => 'nullable|string|max:255',
            'decree_date'          => 'nullable|date',
            'permit_number'        => 'nullable|string|max:255',
            'permit_date'          => 'nullable|date',
            'accreditation'        => 'nullable|string|max:10',
            'accreditation_date'   => 'nullable|date',
            'accreditation_number' => 'nullable|string|max:255',
            'curriculum_id'        => 'nullable|exists:curriculums,id',
            'logo'                 => 'nullable|image|max:2048',
        ];

        if ($isSuperAdmin) {
            $rules['foundation_id'] = 'required|exists:foundations,id';
        } else {
            // Admin Yayasan cannot choose foundation, it is forced to their own
            $rules['foundation_id'] = 'nullable|in:' . $user->foundation_id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        if (!$isSuperAdmin) {
            $data['foundation_id'] = $user->foundation_id;
        }

        // Inherit curriculum_id from Foundation if not explicitly provided
        if (empty($data['curriculum_id']) && !empty($data['foundation_id'])) {
            $foundation = Foundation::find($data['foundation_id']);
            if ($foundation && $foundation->curriculum_id) {
                $data['curriculum_id'] = $foundation->curriculum_id;
            }
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        } else {
            unset($data['logo']);
        }

        $school = School::create($data);

        // Auto create default active AcademicYear dynamically based on creation date
        \App\Models\AcademicYear::firstOrCreate(
            ['school_id' => $school->id, 'is_active' => true],
            \App\Models\AcademicYear::defaultAttributesForDate($school->created_at)
        );

        // Auto sync mandatory subjects from curriculum
        $this->syncSchoolSubjectsFromCurriculum($school);

        return response()->json([
            'status'  => 'success',
            'message' => 'School created successfully.',
            'data'    => $school->load('curriculum'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $school = School::with('foundation:id,name,code', 'curriculum:id,name,code,level')->find($id);

        if (!$school) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School not found.',
            ], 404);
        }

        if ($user->isSuperAdmin() || 
            ($user->hasRole('admin_yayasan') && $user->foundation_id == $school->foundation_id) ||
            ($user->hasRole('admin_sekolah') && $user->school_id == $school->id)) {
            
            $school->load(['users' => function ($q) {
                $q->whereIn('role_id', function ($sq) {
                    $sq->select('id')->from('roles')->where('name', 'admin_sekolah');
                });
            }]);

            return response()->json([
                'status' => 'success',
                'data'   => $school,
            ]);
        }

        return response()->json([
            'message' => 'Forbidden: You do not have the required access role.',
        ], 403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $school = School::find($id);

        if (!$school) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School not found.',
            ], 404);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $isAdminYayasan = $user->hasRole('admin_yayasan') && $user->foundation_id == $school->foundation_id;
        $isAdminSekolah = $user->hasRole('admin_sekolah') && $user->school_id == $school->id;

        if (!$isSuperAdmin && !$isAdminYayasan && !$isAdminSekolah) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        $rules = [
            'name'                 => 'required|string|max:255',
            'npsn'                 => 'nullable|string|max:50|unique:schools,npsn,' . $school->id,
            'level'                => 'nullable|string|max:50',
            'established_date'     => 'nullable|date',
            'address'              => 'nullable|string',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:50',
            'website'              => 'nullable|string|max:255',
            'instagram'            => 'nullable|string|max:255',
            'facebook'             => 'nullable|string|max:255',
            'decree_number'        => 'nullable|string|max:255',
            'decree_date'          => 'nullable|date',
            'permit_number'        => 'nullable|string|max:255',
            'permit_date'          => 'nullable|date',
            'accreditation'        => 'nullable|string|max:10',
            'accreditation_date'   => 'nullable|date',
            'accreditation_number' => 'nullable|string|max:255',
            'curriculum_id'        => 'nullable|exists:curriculums,id',
            'logo'                 => 'nullable|image|max:2048',
            'password'             => 'nullable|string|min:8',
        ];

        // Specific fields only editable by superadmin or admin yayasan
        if ($isSuperAdmin) {
            $rules['foundation_id'] = 'sometimes|required|exists:foundations,id';
            $rules['status']        = 'sometimes|required|in:active,inactive,trial';
        } elseif ($isAdminYayasan) {
            $rules['status']        = 'sometimes|required|in:active,inactive,trial';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Strip restricted fields based on role
        if ($isAdminYayasan) {
            unset($data['foundation_id']); // Cannot transfer school to another foundation
        } elseif ($isAdminSekolah) {
            unset($data['foundation_id']);
            unset($data['status']);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        } else {
            unset($data['logo']);
        }

        $school->update($data);

        // Auto sync mandatory subjects from curriculum when school is updated
        $this->syncSchoolSubjectsFromCurriculum($school);

        // Update administrator user details if provided in request
        $adminUser = User::where('school_id', $school->id)
            ->whereIn('role_id', function ($sq) {
                $sq->select('id')->from('roles')->where('name', 'admin_sekolah');
            })->first();

        if ($adminUser) {
            $userUpdateData = [];
            if ($request->has('emailLogin') && $request->filled('emailLogin')) {
                $userUpdateData['email'] = $request->input('emailLogin');
            }
            if ($request->has('noHpLogin') && $request->filled('noHpLogin')) {
                $userUpdateData['phone'] = $request->input('noHpLogin');
            }
            if ($request->has('password') && $request->filled('password')) {
                $userUpdateData['password'] = \Illuminate\Support\Facades\Hash::make($request->input('password'));
            }
            if ($request->has('name') && $request->filled('name')) {
                $userUpdateData['name'] = 'Admin ' . $request->input('name');
            }

            if (!empty($userUpdateData)) {
                // Validate email and phone if they are being updated
                $userRules = [];
                if (isset($userUpdateData['email'])) {
                    $userRules['email'] = 'required|email|unique:users,email,' . $adminUser->id;
                }
                if (isset($userUpdateData['phone'])) {
                    $userRules['phone'] = 'required|string|max:50|unique:users,phone,' . $adminUser->id;
                }

                if (!empty($userRules)) {
                    $userValidator = Validator::make($userUpdateData, $userRules);
                    if ($userValidator->fails()) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => 'Validation error on administrator account.',
                            'errors'  => $userValidator->errors(),
                        ], 422);
                    }
                }

                $adminUser->update($userUpdateData);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'School updated successfully.',
            'data'    => $school,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $school = School::find($id);

        if (!$school) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School not found.',
            ], 404);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $isAdminYayasan = $user->hasRole('admin_yayasan') && $user->foundation_id == $school->foundation_id;

        if (!$isSuperAdmin && !$isAdminYayasan) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        // Proteksi: Cek apakah masih ada siswa aktif atau guru/staff aktif
        $activeStudentsCount = $school->users()
            ->where('is_active', true)
            ->whereHas('role', fn($q) => $q->where('name', 'siswa'))
            ->count();

        $activeStaffCount = $school->users()
            ->where('is_active', true)
            ->whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas', 'tata_usaha', 'kepala_sekolah', 'admin_sekolah']))
            ->count();

        if ($activeStudentsCount > 0 || $activeStaffCount > 0) {
            $parts = [];
            if ($activeStudentsCount > 0) {
                $parts[] = "{$activeStudentsCount} siswa aktif";
            }
            if ($activeStaffCount > 0) {
                $parts[] = "{$activeStaffCount} guru/staf aktif";
            }
            $details = implode(' dan ', $parts);

            return response()->json([
                'status'  => 'error',
                'message' => "Sekolah tidak dapat dihapus karena masih memiliki {$details}.",
            ], 422);
        }

        // Nonaktifkan user yang terkait sebelum sekolah dihapus
        User::where('school_id', $school->id)->update([
            'school_id' => null,
            'is_active' => false,
        ]);

        $school->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'School deleted successfully.',
        ]);
    }

    /**
     * Map distinct level strings to friendly options.
     */
    private function formatAvailableLevels(array $rawLevels): array
    {
        $levelLabelMap = [
            'TK'    => ['value' => 'TK', 'label' => 'Taman Kanak-Kanak (TK)'],
            'JP001' => ['value' => 'TK', 'label' => 'Taman Kanak-Kanak (TK)'],
            'SD'    => ['value' => 'SD', 'label' => 'Sekolah Dasar (SD)'],
            'JP002' => ['value' => 'SD', 'label' => 'Sekolah Dasar (SD)'],
            'SMP'   => ['value' => 'SMP', 'label' => 'Sekolah Menengah Pertama (SMP)'],
            'JP003' => ['value' => 'SMP', 'label' => 'Sekolah Menengah Pertama (SMP)'],
            'SMA'   => ['value' => 'SMA', 'label' => 'Sekolah Menengah Atas (SMA)'],
            'SMK'   => ['value' => 'SMK', 'label' => 'Sekolah Menengah Kejuruan (SMK)'],
        ];

        $availableLevels = [];
        foreach ($rawLevels as $rawLvl) {
            $upper = strtoupper(trim((string) $rawLvl));
            $opt = $levelLabelMap[$upper] ?? ['value' => $upper, 'label' => $upper];
            if (!isset($availableLevels[$opt['value']])) {
                $availableLevels[$opt['value']] = $opt;
            }
        }

        $order = ['TK' => 1, 'SD' => 2, 'SMP' => 3, 'SMA' => 4, 'SMK' => 5];
        usort($availableLevels, function ($a, $b) use ($order) {
            $oa = $order[$a['value']] ?? 99;
            $ob = $order[$b['value']] ?? 99;
            return $oa <=> $ob;
        });

        return array_values($availableLevels);
    }
}
