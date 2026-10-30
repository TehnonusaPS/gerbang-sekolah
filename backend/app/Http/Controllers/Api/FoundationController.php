<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoundationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            $query = Foundation::with(['users' => function ($q) {
                $q->whereIn('role_id', function ($sq) {
                    $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
                })->select('id', 'foundation_id', 'email', 'phone');
            }])->withCount('schools');

            // Search by name or code
            if ($request->filled('search')) {
                $search = $request->input('search');
                $likeOperator = \DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $query->where(function ($q) use ($search, $likeOperator) {
                    $q->where('name', $likeOperator, "%{$search}%")
                      ->orWhere('code', $likeOperator, "%{$search}%");
                });
            }

            // Filter by status
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('status', $request->input('status'));
            }
 
            $foundations = $query->latest()->paginate($request->input('per_page', 15));

            $foundationIds = $foundations->getCollection()->pluck('id')->all();
            if (!empty($foundationIds)) {
                $schoolFoundationMap = \App\Models\School::whereIn('foundation_id', $foundationIds)
                    ->pluck('foundation_id', 'id');
                $schoolIds = $schoolFoundationMap->keys()->all();

                $users = \App\Models\User::whereIn('foundation_id', $foundationIds)
                    ->when(!empty($schoolIds), function ($q) use ($schoolIds) {
                        $q->orWhereIn('school_id', $schoolIds);
                    })
                    ->select('id', 'foundation_id', 'school_id')
                    ->get();

                $countByFoundation = [];
                foreach ($users as $u) {
                    $fId = $u->foundation_id ?: ($schoolFoundationMap[$u->school_id] ?? null);
                    if ($fId) {
                        $countByFoundation[$fId] = ($countByFoundation[$fId] ?? 0) + 1;
                    }
                }

                $foundations->getCollection()->transform(function ($f) use ($countByFoundation) {
                    $f->users_count = $countByFoundation[$f->id] ?? 0;
                    return $f;
                });
            }

            $total = Foundation::count();
            $active = Foundation::where('status', 'active')->count();
            $trial = Foundation::where('status', 'trial')->count();
            $inactive = Foundation::where('status', 'inactive')->count();

            return response()->json([
                'status' => 'success',
                'data'   => $foundations,
                'stats'  => [
                    'total' => $total,
                    'active' => $active,
                    'trial' => $trial,
                    'inactive' => $inactive,
                ]
            ]);
        }

        if ($user->hasRole('admin_yayasan')) {
            $foundation = Foundation::with(['users' => function ($q) {
                $q->whereIn('role_id', function ($sq) {
                    $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
                })->select('id', 'foundation_id', 'email', 'phone');
            }])->withCount('schools')
                            ->find($user->foundation_id);

            if (!$foundation) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Foundation not found for your account.',
                ], 404);
            }

            $schoolIds = $foundation->schools()->pluck('id');
            $usersCount = \App\Models\User::where('foundation_id', $foundation->id)
                ->orWhereIn('school_id', $schoolIds)
                ->count();

            $foundation->users_count = $usersCount;

            $stats = [
                'total' => 1,
                'active' => $foundation->status === 'active' ? 1 : 0,
                'trial' => $foundation->status === 'trial' ? 1 : 0,
                'inactive' => $foundation->status === 'inactive' ? 1 : 0,
            ];

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'current_page' => 1,
                    'data' => [$foundation],
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
     * Generate foundation code based on name initials and a 3-digit sequence.
     * Example: "Yayasan Cahaya Ilmu" -> "YCI001"
     */
    public static function generateFoundationCode(string $name): string
    {
        // Clean name to letters and numbers
        $cleaned = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        $words = preg_split('/\s+/', trim($cleaned));

        $initials = '';
        foreach ($words as $word) {
            $word = trim($word);
            // Ignore generic word "Yayasan"
            if ($word === '' || strcasecmp($word, 'yayasan') === 0) {
                continue;
            }
            $initials .= mb_substr($word, 0, 1);
        }

        $prefix = strtoupper('Y' . $initials);
        if ($prefix === 'Y' || $prefix === '') {
            $prefix = 'Y';
        }

        // Lock matching rows in database to prevent concurrent race conditions
        $existingCodes = Foundation::withTrashed()
            ->where('code', 'like', "{$prefix}%")
            ->lockForUpdate()
            ->pluck('code')
            ->toArray();

        $maxNumber = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d{3,})$/', $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        $nextNumber = $maxNumber + 1;
        $candidateCode = sprintf('%s%03d', $prefix, $nextNumber);

        while (in_array($candidateCode, $existingCodes) || Foundation::withTrashed()->where('code', $candidateCode)->exists()) {
            $nextNumber++;
            $candidateCode = sprintf('%s%03d', $prefix, $nextNumber);
        }

        return $candidateCode;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        $rules = [
            'name'             => 'required|string|max:255',
            'code'             => 'nullable|string|max:255|unique:foundations,code',
            'established_date' => 'nullable|date',
            'status'           => 'nullable|in:active,inactive,trial',
            'address'          => 'nullable|string',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'website'          => 'nullable|string|max:255',
            'deed_number'      => 'nullable|string|max:255',
            'deed_date'        => 'nullable|date',
            'decree_number'    => 'nullable|string|max:255',
            'decree_date'      => 'nullable|date',
            'curriculum_id'    => 'nullable|exists:curriculums,id',
            'logo'             => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        if ($request->has('emailLogin')) {
            $rules['emailLogin'] = 'required|email|max:255|unique:users,email';
        }

        if ($request->filled('noHpLogin')) {
            $rules['noHpLogin']  = 'nullable|string|max:50|unique:users,phone';
        }

        $customMessages = [
            'name.required'         => 'Nama yayasan wajib diisi.',
            'name.max'              => 'Nama yayasan tidak boleh lebih dari 255 karakter.',
            'emailLogin.unique'     => 'Email login administrator sudah digunakan.',
            'emailLogin.required'   => 'Email login administrator wajib diisi.',
            'emailLogin.email'      => 'Format email login administrator tidak valid.',
            'noHpLogin.unique'      => 'No. HP login administrator sudah digunakan.',
            'noHpLogin.required'    => 'No. HP login administrator wajib diisi.',
            'code.unique'           => 'Kode yayasan sudah digunakan.',
            'logo.required'         => 'Logo yayasan wajib diunggah.',
            'logo.image'            => 'Logo yayasan harus berupa berkas gambar JPG, JPEG, PNG, atau WEBP.',
            'logo.mimes'            => 'Logo yayasan harus berupa berkas gambar JPG, JPEG, PNG, atau WEBP.',
            'logo.max'              => 'Ukuran logo yayasan tidak boleh lebih dari 2 MB.',
            'email.email'           => 'Format email kontak yayasan tidak valid.',
            'status.in'             => 'Status operasional yang dipilih tidak valid.',
            'established_date.date' => 'Format tanggal berdiri tidak valid.',
            'deed_date.date'        => 'Format tanggal akta pendirian tidak valid.',
            'decree_date.date'      => 'Format tanggal SK Kemenkumham tidak valid.',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first() ?: 'Validasi data yayasan gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        } else {
            unset($data['logo']);
        }

        $result = \DB::transaction(function () use ($request, $data) {
            $foundationData = $data;
            unset($foundationData['emailLogin'], $foundationData['noHpLogin']);

            if (empty($foundationData['code'])) {
                $foundationData['code'] = self::generateFoundationCode($foundationData['name']);
            }

            $foundation = Foundation::create($foundationData);

            $adminAccount = null;
            if ($request->filled('emailLogin')) {
                $role = \App\Models\Role::where('name', 'admin_yayasan')->first();
                $roleId = $role ? $role->id : 2;
                $generatedPassword = \Illuminate\Support\Str::random(10) . 'A1!';

                $adminUser = User::create([
                    'name'          => 'Admin ' . $foundation->name,
                    'email'         => $request->input('emailLogin'),
                    'phone'         => $request->input('noHpLogin'),
                    'password'      => \Illuminate\Support\Facades\Hash::make($generatedPassword),
                    'role_id'       => $roleId,
                    'foundation_id' => $foundation->id,
                    'is_active'     => true,
                ]);

                $adminAccount = [
                    'email'    => $adminUser->email,
                    'phone'    => $adminUser->phone,
                    'password' => $generatedPassword,
                ];
            }

            return ['foundation' => $foundation, 'admin_account' => $adminAccount];
        });

        $foundation = $result['foundation'];
        $foundation->load(['users' => function ($q) {
            $q->whereIn('role_id', function ($sq) {
                $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
            })->select('id', 'foundation_id', 'email', 'phone');
        }]);

        return response()->json([
            'status'        => 'success',
            'message'       => 'Foundation created successfully.',
            'data'          => $foundation,
            'admin_account' => $result['admin_account'],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $foundation = Foundation::find($id);

        if (!$foundation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foundation not found.',
            ], 404);
        }

        if ($user->isSuperAdmin() || ($user->hasRole('admin_yayasan') && $user->foundation_id == $foundation->id)) {
            $foundation->load([
                'users' => function ($q) {
                    $q->whereIn('role_id', function ($sq) {
                        $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
                    });
                },
                'activeSubscription.plan'
            ]);

            return response()->json([
                'status' => 'success',
                'data'   => $foundation,
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
        $foundation = Foundation::find($id);

        if (!$foundation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foundation not found.',
            ], 404);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $isAdminYayasan = $user->hasRole('admin_yayasan') && $user->foundation_id == $foundation->id;

        if (!$isSuperAdmin && !$isAdminYayasan) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        $rules = [
            'name'             => 'required|string|max:255',
            'established_date' => 'nullable|date',
            'address'          => 'nullable|string',
            'email'            => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:50',
            'website'          => 'nullable|string|max:255',
            'deed_number'      => 'nullable|string|max:255',
            'deed_date'        => 'nullable|date',
            'decree_number'    => 'nullable|string|max:255',
            'decree_date'      => 'nullable|date',
            'curriculum_id'    => 'nullable|exists:curriculums,id',
            'logo'             => empty($foundation->logo)
                ? 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
                : 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];

        // Superadmin optional updates for code and status
        if ($isSuperAdmin) {
            if ($request->has('code') && $request->filled('code')) {
                $rules['code'] = 'string|max:255|unique:foundations,code,' . $foundation->id;
            }
            if ($request->has('status') && $request->filled('status')) {
                $rules['status'] = 'in:active,inactive,trial';
            }
        }

        $adminUser = User::where('foundation_id', $foundation->id)
            ->whereIn('role_id', function ($sq) {
                $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
            })->first();

        if ($request->has('emailLogin')) {
            $adminUserId = $adminUser ? $adminUser->id : 'NULL';
            $rules['emailLogin'] = 'required|email|max:255|unique:users,email,' . $adminUserId;
        }

        if ($request->filled('noHpLogin')) {
            $adminUserId = $adminUser ? $adminUser->id : 'NULL';
            $rules['noHpLogin'] = 'nullable|string|max:50|unique:users,phone,' . $adminUserId;
        }

        $customMessages = [
            'name.required'         => 'Nama yayasan wajib diisi.',
            'name.max'              => 'Nama yayasan tidak boleh lebih dari 255 karakter.',
            'emailLogin.unique'     => 'Email login administrator sudah digunakan.',
            'emailLogin.required'   => 'Email login administrator wajib diisi.',
            'emailLogin.email'      => 'Format email login administrator tidak valid.',
            'noHpLogin.unique'      => 'No. HP login administrator sudah digunakan.',
            'noHpLogin.required'    => 'No. HP login administrator wajib diisi.',
            'code.unique'           => 'Kode yayasan sudah digunakan.',
            'logo.required'         => 'Logo yayasan wajib diunggah.',
            'logo.image'            => 'Logo yayasan harus berupa berkas gambar JPG, JPEG, PNG, atau WEBP.',
            'logo.mimes'            => 'Logo yayasan harus berupa berkas gambar JPG, JPEG, PNG, atau WEBP.',
            'logo.max'              => 'Ukuran logo yayasan tidak boleh lebih dari 2 MB.',
            'email.email'           => 'Format email kontak yayasan tidak valid.',
            'status.in'             => 'Status operasional yang dipilih tidak valid.',
            'established_date.date' => 'Format tanggal berdiri tidak valid.',
            'deed_date.date'        => 'Format tanggal akta pendirian tidak valid.',
            'decree_date.date'      => 'Format tanggal SK Kemenkumham tidak valid.',
        ];

        $validator = Validator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => $validator->errors()->first() ?: 'Validasi data yayasan gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $request->all();

        // Strip code and status if admin_yayasan, or if code is not explicitly provided (preserve existing code)
        if (!$isSuperAdmin) {
            unset($data['code']);
            unset($data['status']);
        } elseif (empty($data['code'])) {
            unset($data['code']);
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        } else {
            unset($data['logo']);
        }

        \DB::transaction(function () use ($foundation, $data, $request, $adminUser) {
            $foundationData = $data;
            unset($foundationData['emailLogin'], $foundationData['noHpLogin']);
            $foundation->update($foundationData);

            if ($adminUser) {
                $userUpdateData = [];
                if ($request->has('emailLogin') && $request->filled('emailLogin')) {
                    $userUpdateData['email'] = $request->input('emailLogin');
                }
                if ($request->has('noHpLogin') && $request->filled('noHpLogin')) {
                    $userUpdateData['phone'] = $request->input('noHpLogin');
                }
                if ($request->has('name') && $request->filled('name')) {
                    $userUpdateData['name'] = 'Admin ' . $request->input('name');
                }

                if (!empty($userUpdateData)) {
                    $adminUser->update($userUpdateData);
                }
            } elseif ($request->filled('emailLogin')) {
                $role = \App\Models\Role::where('name', 'admin_yayasan')->first();
                $roleId = $role ? $role->id : 2;
                $generatedPassword = \Illuminate\Support\Str::random(10) . 'A1!';

                User::create([
                    'name'          => 'Admin ' . $foundation->name,
                    'email'         => $request->input('emailLogin'),
                    'phone'         => $request->input('noHpLogin'),
                    'password'      => \Illuminate\Support\Facades\Hash::make($generatedPassword),
                    'role_id'       => $roleId,
                    'foundation_id' => $foundation->id,
                    'is_active'     => true,
                ]);
            }
        });

        $foundation->load(['users' => function ($q) {
            $q->whereIn('role_id', function ($sq) {
                $sq->select('id')->from('roles')->where('name', 'admin_yayasan');
            })->select('id', 'foundation_id', 'email', 'phone');
        }]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Foundation updated successfully.',
            'data'    => $foundation,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        if (!$user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Forbidden: You do not have the required access role.',
            ], 403);
        }

        $foundation = Foundation::find($id);

        if (!$foundation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Foundation not found.',
            ], 404);
        }

        $foundation->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Foundation deleted successfully.',
        ]);
    }
}
