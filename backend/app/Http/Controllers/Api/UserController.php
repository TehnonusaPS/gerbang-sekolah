<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Helper to classify roles.
     */
    private function getRoleCategory(string $roleName): string
    {
        $teacherRoles = ['guru', 'wali_kelas', 'kepala_sekolah', 'tata_usaha', 'admin_sekolah'];
        if (in_array($roleName, $teacherRoles)) {
            return 'teacher';
        }
        if ($roleName === 'siswa') {
            return 'student';
        }
        if ($roleName === 'orang_tua') {
            return 'parent';
        }
        return 'none';
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = User::with(['role', 'foundation:id,name', 'school:id,name']);

        if ($user->isSuperAdmin()) {
            // No restriction, apply filters
            if ($request->has('foundation_id')) {
                $query->where('foundation_id', $request->input('foundation_id'));
            }
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            // Limit to users belonging to their foundation OR schools in their foundation
            $query->where(function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id)
                  ->orWhereHas('school', function ($sq) use ($user) {
                      $sq->where('foundation_id', $user->foundation_id);
                  });
            });

            if ($request->has('school_id')) {
                $schoolId = $request->input('school_id');
                // Ensure school belongs to foundation
                $schoolExists = School::where('id', $schoolId)
                    ->where('foundation_id', $user->foundation_id)
                    ->exists();
                if ($schoolExists) {
                    $query->where('school_id', $schoolId);
                } else {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'School does not belong to your foundation.',
                    ], 400);
                }
            }
        } elseif ($user->hasRole('admin_sekolah')) {
            // Limit to users in their school
            $query->where('school_id', $user->school_id);
        } else {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        // Common search and filters
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role_id')) {
            $query->where('role_id', $request->input('role_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $statsQuery = User::query();
        if ($user->isSuperAdmin()) {
            // No restriction
        } elseif ($user->hasRole('admin_yayasan')) {
            $statsQuery->where(function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id)
                  ->orWhereHas('school', function ($sq) use ($user) {
                      $sq->where('foundation_id', $user->foundation_id);
                  });
            });
        } elseif ($user->hasRole('admin_sekolah')) {
            $statsQuery->where('school_id', $user->school_id);
        }

        $total = (clone $statsQuery)->count();
        $active = (clone $statsQuery)->where('is_active', true)->count();
        $inactive = (clone $statsQuery)->where('is_active', false)->count();

        $perPage = (int) $request->input('per_page', 15);
        $page    = (int) $request->input('page', 1);

        return response()->json([
            'status' => 'success',
'data'   => $query->latest()->paginate($request->input('per_page', $perPage), ['*'], 'page', $page),
            'stats'  => [
                'total'    => $total,
                'active'   => $active,
                'inactive' => $inactive,
            ]
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $isSuperAdmin = $currentUser->isSuperAdmin();
        $isAdminYayasan = $currentUser->hasRole('admin_yayasan');
        $isAdminSekolah = $currentUser->hasRole('admin_sekolah');

        if (!$isSuperAdmin && !$isAdminYayasan && !$isAdminSekolah) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        // Determine base validation rules
        $rules = [
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255|unique:users,email',
            'password'      => 'required|string|min:6',
            'role_id'       => 'required|exists:roles,id',
            'phone'         => 'nullable|string|max:50',
            'photo'         => 'nullable|max:2048',
            'is_active'     => 'boolean',
            'profile'       => 'nullable|array',
        ];

        // Scopes and role assignment validation
        if ($isSuperAdmin) {
            $rules['foundation_id'] = 'nullable|exists:foundations,id';
            $rules['school_id']     = 'nullable|exists:schools,id';
        } elseif ($isAdminYayasan) {
            $rules['foundation_id'] = 'nullable|in:' . $currentUser->foundation_id;
            $rules['school_id']     = 'nullable|exists:schools,id';
        } else { // admin_sekolah
            $rules['foundation_id'] = 'nullable|in:' . ($currentUser->school ? $currentUser->school->foundation_id : '0');
            $rules['school_id']     = 'nullable|in:' . $currentUser->school_id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Retrieve the role to assign
        $roleToAssign = Role::findOrFail($request->input('role_id'));

        // Hierarchy validations
        if ($isAdminYayasan) {
            if ($roleToAssign->name === 'superadmin') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You cannot assign the superadmin role.',
                ], 403);
            }
            // Ensure chosen school belongs to the foundation
            if ($request->has('school_id') && $request->input('school_id')) {
                $school = School::find($request->input('school_id'));
                if (!$school || $school->foundation_id != $currentUser->foundation_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'The selected school must belong to your foundation.',
                    ], 400);
                }
            }
        } elseif ($isAdminSekolah) {
            if (in_array($roleToAssign->name, ['superadmin', 'admin_yayasan'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You cannot assign the selected role.',
                ], 403);
            }
        }

        // Validate profiles fields dynamically
        $profileCategory = $this->getRoleCategory($roleToAssign->name);
        $profileRules = [];

        if ($profileCategory === 'teacher') {
            $profileRules = [
                'profile.nik'               => 'nullable|string|max:50',
                'profile.nip_nuptk'         => 'nullable|string|max:50',
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.religion'          => 'nullable|string|max:100',
                'profile.marital_status'    => 'nullable|string|max:50',
                'profile.last_education'    => 'nullable|string|max:100',
                'profile.front_title'       => 'nullable|string|max:50',
                'profile.back_title'        => 'nullable|string|max:50',
                'profile.address'           => 'nullable|string',
                'profile.position'          => 'nullable|string|max:100',
                'profile.employment_status' => 'nullable|string|max:100',
                'profile.join_date'         => 'nullable|date',
            ];
        } elseif ($profileCategory === 'student') {
            $profileRules = [
                'profile.classroom_id'      => 'nullable|exists:classrooms,id',
                'profile.nisn'              => 'nullable|string|max:50|unique:student_profiles,nisn',
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.address'           => 'nullable|string',
                'profile.enrollment_date'   => 'nullable|date',
                'profile.status'            => 'nullable|in:active,alumni,transferred,expelled',
            ];
        } elseif ($profileCategory === 'parent') {
            $profileRules = [
                'profile.nik'               => 'nullable|string|max:50',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.religion'          => 'nullable|string|max:100',
                'profile.last_education'    => 'nullable|string|max:100',
                'profile.marital_status'    => 'nullable|string|max:50',
                'profile.relationship'      => 'nullable|in:father,mother,guardian',
                'profile.occupation'        => 'nullable|string|max:100',
                'profile.address'           => 'nullable|string',
            ];
        }

        if (!empty($profileRules)) {
            $profileValidator = Validator::make($request->all(), $profileRules);
            if ($profileValidator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Profile validation error.',
                    'errors'  => $profileValidator->errors(),
                ], 422);
            }
        }

        // Prepare userData
        $userData = [
            'name'          => $request->input('name'),
            'email'         => strtolower(trim($request->input('email'))),
            'password'      => Hash::make($request->input('password')),
            'role_id'       => $roleToAssign->id,
            'phone'         => $request->input('phone'),
            'is_active'     => $request->input('is_active', true),
        ];

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $userData['photo'] = Storage::disk('public')->url($path);
        } else {
            $userData['photo'] = $request->input('photo');
        }

        // Set scoped ids
        if ($isSuperAdmin) {
            $userData['foundation_id'] = $request->input('foundation_id');
            $userData['school_id']     = $request->input('school_id');
        } elseif ($isAdminYayasan) {
            $userData['foundation_id'] = $currentUser->foundation_id;
            $userData['school_id']     = $request->input('school_id');
        } else { // admin_sekolah
            $school = School::find($currentUser->school_id);
            $userData['foundation_id'] = $school ? $school->foundation_id : null;
            $userData['school_id']     = $currentUser->school_id;
        }

        // Create user and profile in transaction
        DB::beginTransaction();
        try {
            $newUser = User::create($userData);

            if ($request->has('profile') && !empty($request->input('profile'))) {
                $profileData = $request->input('profile');
                $profileData['user_id'] = $newUser->id;

                if ($profileCategory === 'teacher') {
                    TeacherProfile::create($profileData);
                } elseif ($profileCategory === 'student') {
                    StudentProfile::create($profileData);
                } elseif ($profileCategory === 'parent') {
                    ParentProfile::create($profileData);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'User created successfully.',
                'data'    => $newUser->load(['role', 'teacherProfile', 'studentProfile', 'parentProfile']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display details of a user.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        $targetUser = User::with(['role', 'teacherProfile', 'studentProfile', 'parentProfile', 'school'])->find($id);

        if (!$targetUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        if ($currentUser->isSuperAdmin()) {
            return response()->json([
                'status' => 'success',
                'data'   => $targetUser,
            ]);
        }

        if ($currentUser->hasRole('admin_yayasan')) {
            $belongsToYayasan = $targetUser->foundation_id == $currentUser->foundation_id ||
                ($targetUser->school && $targetUser->school->foundation_id == $currentUser->foundation_id);

            if ($belongsToYayasan) {
                return response()->json([
                    'status' => 'success',
                    'data'   => $targetUser,
                ]);
            }
        }

        if ($currentUser->hasRole('admin_sekolah') && $targetUser->school_id == $currentUser->school_id) {
            return response()->json([
                'status' => 'success',
                'data'   => $targetUser,
            ]);
        }

        return response()->json([
            'message' => 'Forbidden: You do not have the required access role.',
        ], 403);
    }

    /**
     * Update an existing user and their profile.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $isSuperAdmin = $currentUser->isSuperAdmin();
        $isAdminYayasan = $currentUser->hasRole('admin_yayasan');
        $isAdminSekolah = $currentUser->hasRole('admin_sekolah');

        if (!$isSuperAdmin && !$isAdminYayasan && !$isAdminSekolah) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        // Scope verification
        if ($isAdminYayasan) {
            $belongsToYayasan = $targetUser->foundation_id == $currentUser->foundation_id ||
                ($targetUser->school && $targetUser->school->foundation_id == $currentUser->foundation_id);

            if (!$belongsToYayasan) {
                return response()->json([
                    'message' => 'Forbidden: You do not have the required access role.',
                ], 403);
            }
        } elseif ($isAdminSekolah) {
            if ($targetUser->school_id != $currentUser->school_id) {
                return response()->json([
                    'message' => 'Forbidden: You do not have the required access role.',
                ], 403);
            }
        }

        // Validation rules
        $rules = [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email,' . $targetUser->id,
            'password'  => 'nullable|string|min:6',
            'role_id'   => 'required|exists:roles,id',
            'phone'     => 'nullable|string|max:50',
            'photo'     => 'nullable|max:2048',
            'is_active' => 'boolean',
            'profile'   => 'nullable|array',
        ];

        // Location constraints on update
        if ($isSuperAdmin) {
            $rules['foundation_id'] = 'nullable|exists:foundations,id';
            $rules['school_id']     = 'nullable|exists:schools,id';
        } elseif ($isAdminYayasan) {
            $rules['foundation_id'] = 'nullable|in:' . $currentUser->foundation_id;
            $rules['school_id']     = 'nullable|exists:schools,id';
        } else {
            $rules['foundation_id'] = 'nullable|in:' . ($currentUser->school ? $currentUser->school->foundation_id : '0');
            $rules['school_id']     = 'nullable|in:' . $currentUser->school_id;
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Verify role transition
        $roleToAssign = Role::findOrFail($request->input('role_id'));

        if ($isAdminYayasan) {
            if ($roleToAssign->name === 'superadmin') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You cannot assign the superadmin role.',
                ], 403);
            }
            if ($request->has('school_id') && $request->input('school_id')) {
                $school = School::find($request->input('school_id'));
                if (!$school || $school->foundation_id != $currentUser->foundation_id) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'The selected school must belong to your foundation.',
                    ], 400);
                }
            }
        } elseif ($isAdminSekolah) {
            if (in_array($roleToAssign->name, ['superadmin', 'admin_yayasan'])) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'You cannot assign the selected role.',
                ], 403);
            }
        }

        // Profile category calculation
        $oldRole = Role::find($targetUser->role_id);
        $oldCategory = $oldRole ? $this->getRoleCategory($oldRole->name) : 'none';
        $newCategory = $this->getRoleCategory($roleToAssign->name);

        // Validate profiles fields dynamically
        $profileRules = [];
        if ($newCategory === 'teacher') {
            $profileRules = [
                'profile.nik'               => 'nullable|string|max:50',
                'profile.nip_nuptk'         => 'nullable|string|max:50',
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.religion'          => 'nullable|string|max:100',
                'profile.marital_status'    => 'nullable|string|max:50',
                'profile.last_education'    => 'nullable|string|max:100',
                'profile.front_title'       => 'nullable|string|max:50',
                'profile.back_title'        => 'nullable|string|max:50',
                'profile.address'           => 'nullable|string',
                'profile.position'          => 'nullable|string|max:100',
                'profile.employment_status' => 'nullable|string|max:100',
                'profile.join_date'         => 'nullable|date',
            ];
        } elseif ($newCategory === 'student') {
            $profileRules = [
                'profile.classroom_id'      => 'nullable|exists:classrooms,id',
                'profile.nisn'              => 'nullable|string|max:50|unique:student_profiles,nisn,' . ($targetUser->studentProfile ? $targetUser->studentProfile->id : 'NULL'),
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.address'           => 'nullable|string',
                'profile.enrollment_date'   => 'nullable|date',
                'profile.status'            => 'nullable|in:active,alumni,transferred,expelled',
            ];
        } elseif ($newCategory === 'parent') {
            $profileRules = [
                'profile.nik'               => 'nullable|string|max:50',
                'profile.gender'            => 'nullable|in:male,female',
                'profile.birth_place'       => 'nullable|string|max:255',
                'profile.birth_date'        => 'nullable|date',
                'profile.religion'          => 'nullable|string|max:100',
                'profile.last_education'    => 'nullable|string|max:100',
                'profile.marital_status'    => 'nullable|string|max:50',
                'profile.relationship'      => 'nullable|in:father,mother,guardian',
                'profile.occupation'        => 'nullable|string|max:100',
                'profile.address'           => 'nullable|string',
            ];
        }

        if (!empty($profileRules)) {
            $profileValidator = Validator::make($request->all(), $profileRules);
            if ($profileValidator->fails()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Profile validation error.',
                    'errors'  => $profileValidator->errors(),
                ], 422);
            }
        }

        // Prepare userData
        $userData = [
            'name'      => $request->input('name'),
            'email'     => strtolower(trim($request->input('email'))),
            'role_id'   => $roleToAssign->id,
            'phone'     => $request->input('phone'),
            'is_active' => $request->input('is_active', $targetUser->is_active),
        ];

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('photos', 'public');
            $userData['photo'] = Storage::disk('public')->url($path);
        } else {
            $userData['photo'] = $request->input('photo');
        }

        if ($request->has('password') && !empty($request->input('password'))) {
            $userData['password'] = Hash::make($request->input('password'));
        }

        // Assign foundation/school IDs based on role updates
        if ($isSuperAdmin) {
            $userData['foundation_id'] = $request->input('foundation_id');
            $userData['school_id']     = $request->input('school_id');
        } elseif ($isAdminYayasan) {
            $userData['foundation_id'] = $currentUser->foundation_id;
            $userData['school_id']     = $request->input('school_id');
        } else {
            $school = School::find($currentUser->school_id);
            $userData['foundation_id'] = $school ? $school->foundation_id : null;
            $userData['school_id']     = $currentUser->school_id;
        }

        // Run DB Transaction
        DB::beginTransaction();
        try {
            $targetUser->update($userData);

            // Handle Profile transition / updates
            if ($oldCategory !== $newCategory) {
                // Delete old profile
                if ($oldCategory === 'teacher') {
                    $targetUser->teacherProfile()->delete();
                } elseif ($oldCategory === 'student') {
                    $targetUser->studentProfile()->delete();
                } elseif ($oldCategory === 'parent') {
                    $targetUser->parentProfile()->delete();
                }
            }

            if ($request->has('profile') && !empty($request->input('profile'))) {
                $profileData = $request->input('profile');
                $profileData['user_id'] = $targetUser->id;

                if ($newCategory === 'teacher') {
                    TeacherProfile::updateOrCreate(['user_id' => $targetUser->id], $profileData);
                } elseif ($newCategory === 'student') {
                    StudentProfile::updateOrCreate(['user_id' => $targetUser->id], $profileData);
                } elseif ($newCategory === 'parent') {
                    ParentProfile::updateOrCreate(['user_id' => $targetUser->id], $profileData);
                }
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'User updated successfully.',
                'data'    => $targetUser->load(['role', 'teacherProfile', 'studentProfile', 'parentProfile']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $currentUser = $request->user();
        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        $isSuperAdmin = $currentUser->isSuperAdmin();
        $isAdminYayasan = $currentUser->hasRole('admin_yayasan');
        $isAdminSekolah = $currentUser->hasRole('admin_sekolah');

        if (!$isSuperAdmin && !$isAdminYayasan && !$isAdminSekolah) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        // Scope verification
        if ($isAdminYayasan) {
            $belongsToYayasan = $targetUser->foundation_id == $currentUser->foundation_id ||
                ($targetUser->school && $targetUser->school->foundation_id == $currentUser->foundation_id);

            if (!$belongsToYayasan) {
                return response()->json([
                    'message' => 'Forbidden: You do not have the required access role.',
                ], 403);
            }
        } elseif ($isAdminSekolah) {
            if ($targetUser->school_id != $currentUser->school_id) {
                return response()->json([
                    'message' => 'Forbidden: You do not have the required access role.',
                ], 403);
            }
        }

        // Prevent self deletion
        if ($currentUser->id == $targetUser->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot delete your own account.',
            ], 400);
        }

        // Deleting user will automatically cascade delete profile because of cascadeOnDelete() foreign key
        $targetUser->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User deleted successfully.',
        ]);
    }

    /**
     * List of available roles for the dropdown.
     */
    public function getRoles(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->isSuperAdmin()) {
            $roles = Role::all();
        } elseif ($currentUser->hasRole('admin_yayasan')) {
            // Can assign any role except superadmin
            $roles = Role::where('name', '!=', 'superadmin')->get();
        } elseif ($currentUser->hasRole('admin_sekolah')) {
            // Can assign roles except superadmin and admin_yayasan
            $roles = Role::whereNotIn('name', ['superadmin', 'admin_yayasan'])->get();
        } else {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $roles,
        ]);
    }
}
