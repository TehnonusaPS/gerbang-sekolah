<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\StudentBiometric;
use App\Models\StudentAttendance;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StudentAttendanceController extends Controller
{
    private function getAiUrl(): string
    {
        return config('services.ai.url');
    }

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
     * Get list of students with today's attendance status and face registration status.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->school_id) {
            return response()->json(['success' => false, 'message' => 'Sekolah tidak teridentifikasi.'], 403);
        }

        $query = StudentProfile::whereHas('user', function ($q) use ($user) {
            $q->where('school_id', $user->school_id);
        })->with(['user', 'classroom']);

        if ($request->has('kelas') && $request->input('kelas') !== 'semua') {
            $kelasInput = $request->input('kelas');
            $kelasNormalized = str_replace(' ', '-', $kelasInput);
            $query->whereHas('classroom', function ($q) use ($kelasInput, $kelasNormalized) {
                $q->where('name', $kelasInput)
                  ->orWhere('name', $kelasNormalized)
                  ->orWhere('name', str_replace('-', ' ', $kelasNormalized));
            });
        }

        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $students = $query->get();
        $today = now()->toDateString();

        $data = $students->map(function ($student) use ($today) {
            // Find today's attendance
            $attendance = StudentAttendance::where('student_profile_id', $student->id)
                ->where('date', $today)
                ->first();

            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'nama' => $student->user->name,
                'kelas' => $student->classroom ? $student->classroom->name : '-',
                'jenisKelamin' => $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-'),
                'gender' => $student->gender === 'male' ? 'L' : ($student->gender === 'female' ? 'P' : '-'),
                'mapel' => 'Semua Mapel', // general label to match frontend view
                'jamMasuk' => $attendance ? $attendance->time_in : null,
                'jamKeluar' => $attendance ? $attendance->time_out : null,
                'status' => $attendance ? strtolower($attendance->status) : 'belum_absen',
                'is_face_registered' => (bool)$student->is_face_registered,
                'rfid_number' => $student->rfid_number,
                'is_rfid_registered' => (bool)$student->is_rfid_registered,
                'foto' => $student->user->photo ? (str_starts_with($student->user->photo, 'http') ? $student->user->photo : asset($student->user->photo)) : null,
            ];
        });

        return response()->json($data);
    }

    /**
     * Get recent logs of today's student attendances.
     */
    public function logs(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->school_id) {
            return response()->json(['success' => false, 'message' => 'Sekolah tidak teridentifikasi.'], 403);
        }

        $today = now()->toDateString();
        $attendances = StudentAttendance::where('date', $today)
            ->whereHas('studentProfile.user', function ($q) use ($user) {
                $q->where('school_id', $user->school_id);
            })
            ->with('studentProfile.user', 'studentProfile.classroom')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $data = $attendances->map(function ($att) {
            $student = $att->studentProfile;
            $initials = collect(explode(' ', $student->user->name))
                ->map(fn($n) => mb_substr($n, 0, 1))
                ->take(2)
                ->join('');

            return [
                'id' => $att->id,
                'nama' => $student->user->name,
                'kelas' => $student->classroom ? $student->classroom->name : '-',
                'nisn' => $student->nisn,
                'inisial' => strtoupper($initials),
                'waktu' => $att->time_in,
                'tipe' => 'Masuk',
            ];
        });

        return response()->json($data);
    }

    /**
     * Process face recognition scan or mock ID scan from kiosk.
     */
    public function scan(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $user->school_id;
        if (!$schoolId) {
            return response()->json(['success' => false, 'message' => 'Sekolah tidak teridentifikasi.'], 403);
        }

        $school = School::find($schoolId);
        $lateThreshold = $school->attendance_late_threshold ?? '07:30:00';

        $studentProfile = null;
        $verificationMethod = 'kamera';
        $similarity = null;

        // Mode 1: Real Face Recognition (using uploaded image)
        if ($request->hasFile('image')) {
            try {
                // Ekstrak embedding vektor wajah dari citra pemindaian
                $response = Http::timeout(60)
                    ->attach('image', file_get_contents($request->file('image')), 'scan.jpg')
                    ->attach('name', 'Scan Absensi Kiosk')
                    ->attach('detector_backend', 'skip')
                    ->post($this->getAiUrl() . '/api/represent');

                if ($response->successful()) {
                    $resData = $response->json();
                    if (isset($resData['success']) && $resData['success'] === true && !empty($resData['embedding'])) {
                        $scanEmbedding = $resData['embedding'];

                        // Bandingkan dengan semua sampel biometrik terdaftar di sekolah ini (Cache RAM)
                        $biometricsData = Cache::remember("student_embeddings:school_{$schoolId}", 3600, function () use ($schoolId) {
                            return StudentBiometric::whereHas('studentProfile.user', function ($q) use ($schoolId) {
                                $q->where('school_id', $schoolId);
                            })->with('studentProfile.user', 'studentProfile.classroom')->get();
                        });

                        $bestMatch = null;
                        $highestSimilarity = 0.0;
                        $similarityThreshold = 0.65;

                        foreach ($biometricsData as $bio) {
                            $sampleEmbedding = $bio->embedding;
                            if (!is_array($sampleEmbedding)) {
                                continue;
                            }
                            
                            $sim = $this->cosineSimilarity($scanEmbedding, $sampleEmbedding);
                            if ($sim > $highestSimilarity) {
                                $highestSimilarity = $sim;
                                $bestMatch = $bio->studentProfile;

                                // Optimasi Early Exit: Jika kemiripan sangat tinggi (>= 80%), langsung hentikan loop pencarian!
                                if ($sim >= 0.80) {
                                    break;
                                }
                            }
                        }

                        // Fallback ke legacy embedding jika ada sampel lama
                        if (!$bestMatch || $highestSimilarity < $similarityThreshold) {
                            $legacyStudents = StudentProfile::whereHas('user', function ($q) use ($schoolId) {
                                $q->where('school_id', $schoolId);
                            })->whereNotNull('embedding')->get();

                            foreach ($legacyStudents as $student) {
                                $legacyEmbedding = $student->embedding;
                                if (!is_array($legacyEmbedding)) continue;
                                $sim = $this->cosineSimilarity($scanEmbedding, $legacyEmbedding);
                                if ($sim > $highestSimilarity) {
                                    $highestSimilarity = $sim;
                                    $bestMatch = $student;
                                }
                            }
                        }

                        if ($bestMatch && $highestSimilarity >= $similarityThreshold) {
                            $studentProfile = $bestMatch;
                            $similarity = $highestSimilarity;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Koneksi AI Terputus pada Scan Absensi: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Koneksi ke server AI terputus.'], 500);
            }

            if (!$studentProfile) {
                return response()->json(['success' => false, 'message' => 'Wajah tidak dikenali.'], 400);
            }
        } 
        // Mode 2: Simulated Scan (RFID / Fingerprint / direct button)
        elseif ($request->has('student_id')) {
            $studentProfile = StudentProfile::find($request->student_id);
            $verificationMethod = $request->input('verification_method', 'rfid');
            if (!$studentProfile) {
                return response()->json(['success' => false, 'message' => 'Siswa tidak ditemukan.'], 404);
            }
        } else {
            return response()->json(['success' => false, 'message' => 'Input pemindaian tidak valid.'], 400);
        }

        // Simpan Log Absensi di student_attendances
        $today = now()->toDateString();
        $timeIn = now()->toTimeString();

        // Cari record absensi hari ini
        $attendance = StudentAttendance::where('student_profile_id', $studentProfile->id)
            ->where('date', $today)
            ->first();

        // Apabila siswa sudah pernah melakukan presensi hari ini, tolak presensi kedua
        if ($attendance) {
            return response()->json([
                'success' => false,
                'message' => "Siswa {$studentProfile->user->name} sudah melakukan presensi masuk hari ini."
            ], 400);
        }

        // Log Absensi Masuk baru
        // Tentukan status: H (Hadir) jika sebelum threshold, T (Terlambat) jika setelah threshold
        $status = (strtotime($timeIn) <= strtotime($lateThreshold)) ? 'H' : 'T';

        $attendance = StudentAttendance::create([
            'student_profile_id' => $studentProfile->id,
            'date' => $today,
            'time_in' => $timeIn,
            'status' => $status,
            'verification_method' => $verificationMethod,
        ]);

        $initials = collect(explode(' ', $studentProfile->user->name))
            ->map(fn($n) => mb_substr($n, 0, 1))
            ->take(2)
            ->join('');

        $newLog = [
            'id' => $attendance->id,
            'nama' => $studentProfile->user->name,
            'kelas' => $studentProfile->classroom ? $studentProfile->classroom->name : '-',
            'nisn' => $studentProfile->nisn,
            'inisial' => strtoupper($initials),
            'waktu' => $timeIn,
            'tipe' => 'Masuk',
        ];

        return response()->json($newLog);
    }

    /**
     * Register student face biometrics (Supports Multi-Angle batch images).
     */
    public function registerFace(Request $request, $id): JsonResponse
    {
        $studentProfile = StudentProfile::findOrFail($id);
        $schoolId = $studentProfile->user->school_id;

        // Ambil file yang dikirim (bisa berupa map/array images[] atau file tunggal image)
        $filesToProcess = [];
        if ($request->hasFile('images')) {
            $filesToProcess = $request->file('images');
        } elseif ($request->hasFile('image')) {
            $filesToProcess = ['front' => $request->file('image')];
        }

        if (empty($filesToProcess)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada berkas sampel biometrik yang dikirim.'
            ], 422);
        }

        // Ambil biometrik milik siswa lain untuk validasi duplikasi
        $otherBiometrics = StudentBiometric::whereHas('studentProfile.user', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        })->where('student_profile_id', '!=', $studentProfile->id)->get();

        $duplicateThreshold = 0.75;
        $extractedSamples = [];

        foreach ($filesToProcess as $labelKey => $file) {
            $label = is_string($labelKey) ? $labelKey : 'front';
            $path = $file->store('student_biometrics', 'public');
            $embedding = null;

            try {
                if ($path) {
                    $response = Http::attach('image', $file->get(), basename($path))
                        ->attach('name', $studentProfile->user->name)
                        ->post($this->getAiUrl() . '/api/represent');

                    if ($response->successful()) {
                        $resData = $response->json();
                        if (isset($resData['success']) && $resData['success'] === true && !empty($resData['embedding'])) {
                            $embedding = $resData['embedding'];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Gagal ekstraksi embedding dari Python AI secara real-time: " . $e->getMessage());
            }

            if (!$embedding) {
                Storage::disk('public')->delete($path);
                return response()->json([
                    'success' => false,
                    'message' => "Gagal mendeteksi wajah pada posisi [{$label}]. Pastikan posisi wajah terlihat jelas di depan kamera."
                ], 422);
            }

            // Validasi Duplikasi Wajah terhadap siswa lain
            foreach ($otherBiometrics as $other) {
                $otherEmbedding = $other->embedding;
                if (is_array($otherEmbedding)) {
                    $sim = $this->cosineSimilarity($embedding, $otherEmbedding);
                    if ($sim >= $duplicateThreshold) {
                        Storage::disk('public')->delete($path);
                        $otherName = $other->studentProfile->user->name ?? 'Siswa Lain';
                        return response()->json([
                            'success' => false,
                            'message' => "Sampel wajah posisi [{$label}] sudah terdaftar atas nama: {$otherName} (Kemiripan: " . round($sim * 100, 1) . "%)."
                        ], 422);
                    }
                }
            }

            $extractedSamples[] = [
                'angle_label' => $label,
                'embedding' => $embedding,
                'face_photo_path' => '/storage/' . $path,
            ];
        }

        // Hapus sampel biometrik lama milik siswa ini
        foreach ($studentProfile->biometrics as $oldBio) {
            if ($oldBio->face_photo_path) {
                $cleanPath = str_replace('/storage/', '', $oldBio->face_photo_path);
                Storage::disk('public')->delete($cleanPath);
            }
        }
        $studentProfile->biometrics()->delete();

        // Simpan sampel biometrik baru ke tabel student_biometrics
        foreach ($extractedSamples as $sample) {
            $studentProfile->biometrics()->create([
                'angle_label' => $sample['angle_label'],
                'embedding' => $sample['embedding'],
                'face_photo_path' => $sample['face_photo_path'],
            ]);
        }

        // Tandai status biometrik terdaftar (User.photo TIDAK DITIMPA agar foto profil sosial tetap utuh!)
        $studentProfile->is_face_registered = true;
        // Simpan embedding sampel pertama sebagai fallback legacy jika diperlukan
        if (!empty($extractedSamples[0]['embedding'])) {
            $studentProfile->embedding = $extractedSamples[0]['embedding'];
        }
        $studentProfile->save();

        // Clear RAM cache
        Cache::forget("student_embeddings:school_{$schoolId}");
        Cache::forget('student_embeddings:global');

        return response()->json([
            'success' => true,
            'message' => 'Registrasi biometrik multi-angle berhasil disimpan!',
            'data' => [
                'is_face_registered' => true,
                'samples_count' => count($extractedSamples)
            ]
        ]);
    }

    /**
     * Get monthly attendance grid for teacher view.
     */
    public function getMonthlyGrid(Request $request): JsonResponse
    {
        $kelasName = $request->input('kelas');
        $tahun = $request->input('tahun');
        $monthIdx = (int)$request->input('monthIdx'); // 0-11

        $kelasNormalized = str_replace(' ', '-', $kelasName);
        $classroom = Classroom::where('name', $kelasName)
            ->orWhere('name', $kelasNormalized)
            ->orWhere('name', str_replace('-', ' ', $kelasNormalized))
            ->first();
        if (!$classroom) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $startYear = (int)explode('/', $tahun)[0];
        $monthsInfo = [
            0 => ['month' => 7, 'yearOffset' => 0],
            1 => ['month' => 8, 'yearOffset' => 0],
            2 => ['month' => 9, 'yearOffset' => 0],
            3 => ['month' => 10, 'yearOffset' => 0],
            4 => ['month' => 11, 'yearOffset' => 0],
            5 => ['month' => 12, 'yearOffset' => 0],
            6 => ['month' => 1, 'yearOffset' => 1],
            7 => ['month' => 2, 'yearOffset' => 1],
            8 => ['month' => 3, 'yearOffset' => 1],
            9 => ['month' => 4, 'yearOffset' => 1],
            10 => ['month' => 5, 'yearOffset' => 1],
            11 => ['month' => 6, 'yearOffset' => 1],
        ];
        $info = $monthsInfo[$monthIdx] ?? ['month' => 7, 'yearOffset' => 0];
        $year = $startYear + $info['yearOffset'];
        $month = $info['month'];

        $students = StudentProfile::where('classroom_id', $classroom->id)->get();
        $studentIds = $students->pluck('id');

        $attendances = StudentAttendance::whereIn('student_profile_id', $studentIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $grid = [];
        foreach ($attendances as $att) {
            $day = (int)date('j', strtotime($att->date));
            $key = "{$kelasName}_{$tahun}_{$monthIdx}_{$att->student_profile_id}_{$day}";
            $grid[$key] = $att->status;
        }

        return response()->json([
            'success' => true,
            'data' => $grid
        ]);
    }

    /**
     * Update a single cell in the monthly grid manually by teacher.
     */
    public function updateMonthlyCell(Request $request): JsonResponse
    {
        $kelasName = $request->input('kelas');
        $tahun = $request->input('tahun');
        $monthIdx = (int)$request->input('monthIdx');
        $studentProfileId = $request->input('studentId');
        $dayNum = (int)$request->input('dayNum');
        $status = $request->input('status'); // 'H', 'T', 'I', 'S', 'A' or null

        $kelasNormalized = str_replace(' ', '-', $kelasName);
        $classroom = Classroom::where('name', $kelasName)
            ->orWhere('name', $kelasNormalized)
            ->orWhere('name', str_replace('-', ' ', $kelasNormalized))
            ->first();
        if (!$classroom) {
            return response()->json(['success' => false, 'message' => 'Kelas tidak ditemukan.'], 404);
        }

        $startYear = (int)explode('/', $tahun)[0];
        $monthsInfo = [
            0 => ['month' => 7, 'yearOffset' => 0],
            1 => ['month' => 8, 'yearOffset' => 0],
            2 => ['month' => 9, 'yearOffset' => 0],
            3 => ['month' => 10, 'yearOffset' => 0],
            4 => ['month' => 11, 'yearOffset' => 0],
            5 => ['month' => 12, 'yearOffset' => 0],
            6 => ['month' => 1, 'yearOffset' => 1],
            7 => ['month' => 2, 'yearOffset' => 1],
            8 => ['month' => 3, 'yearOffset' => 1],
            9 => ['month' => 4, 'yearOffset' => 1],
            10 => ['month' => 5, 'yearOffset' => 1],
            11 => ['month' => 6, 'yearOffset' => 1],
        ];
        $info = $monthsInfo[$monthIdx] ?? ['month' => 7, 'yearOffset' => 0];
        $year = $startYear + $info['yearOffset'];
        $month = $info['month'];

        $dateString = sprintf('%04d-%02d-%02d', $year, $month, $dayNum);

        if ($status === null || $status === '') {
            StudentAttendance::where('student_profile_id', $studentProfileId)
                ->where('date', $dateString)
                ->delete();
        } else {
            StudentAttendance::updateOrCreate(
                [
                    'student_profile_id' => $studentProfileId,
                    'date' => $dateString
                ],
                [
                    'status' => $status,
                    'time_in' => now()->toTimeString(),
                    'verification_method' => 'manual'
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kehadiran berhasil diperbarui.'
        ]);
    }

    /**
     * Override student attendance status manually.
     */
    public function changeStatus(Request $request, $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:hadir,terlambat,izin,sakit,alpa,belum_absen'
        ]);

        $studentProfile = StudentProfile::findOrFail($id);
        $today = now()->toDateString();
        $statusMap = [
            'hadir' => 'H',
            'terlambat' => 'T',
            'izin' => 'I',
            'sakit' => 'S',
            'alpa' => 'A'
        ];

        if ($request->status === 'belum_absen') {
            StudentAttendance::where('student_profile_id', $studentProfile->id)
                ->where('date', $today)
                ->delete();
        } else {
            StudentAttendance::updateOrCreate(
                [
                    'student_profile_id' => $studentProfile->id,
                    'date' => $today
                ],
                [
                    'status' => $statusMap[$request->status],
                    'time_in' => now()->toTimeString(),
                    'verification_method' => 'manual'
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Status absensi berhasil diperbarui.'
        ]);
    }

    private function sanitizeRfidNumber(string $rfid): string
    {
        $str = trim($rfid);
        if (strlen($str) >= 6 && strlen($str) % 2 === 0) {
            $half = (int)(strlen($str) / 2);
            if (substr($str, 0, $half) === substr($str, $half)) {
                return substr($str, 0, $half);
            }
        }
        if (preg_match('/^([a-zA-Z0-9]{8,12})\1+$/', $str, $matches)) {
            return $matches[1];
        }
        for ($len = 10; $len >= 8; $len--) {
            if (strlen($str) >= $len * 2) {
                $chunk = substr($str, 0, $len);
                if (str_replace($chunk, '', $str) === '') {
                    return $chunk;
                }
            }
        }
        if (strlen($str) > 12 && preg_match('/^[a-zA-Z0-9]{8,10}/', $str, $matches)) {
            return $matches[0];
        }
        return $str;
    }

    /**
     * Register or update student RFID card number.
     */
    public function registerRfid(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rfid_number' => 'required|string|max:100',
        ]);

        $rfidNumber = $this->sanitizeRfidNumber($request->rfid_number);

        // Check if RFID card is already assigned to another student
        $existing = StudentProfile::where('rfid_number', $rfidNumber)
            ->where('id', '!=', $id)
            ->with('user')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => "Kartu RFID ini sudah terdaftar atas nama siswa {$existing->user->name}!"
            ], 422);
        }

        $studentProfile = StudentProfile::findOrFail($id);
        $studentProfile->rfid_number = $rfidNumber;
        $studentProfile->save();

        return response()->json([
            'success' => true,
            'message' => "Kartu RFID berhasil didaftarkan untuk {$studentProfile->user->name}.",
            'data' => [
                'id' => $studentProfile->id,
                'rfid_number' => $studentProfile->rfid_number,
                'is_rfid_registered' => true,
            ]
        ]);
    }

    /**
     * Process RFID Scan for Attendance.
     */
    public function scanRfid(Request $request): JsonResponse
    {
        $request->validate([
            'rfid_number' => 'required|string',
        ]);

        $user = $request->user();
        $schoolId = $user ? $user->school_id : null;
        $rfidNumber = $this->sanitizeRfidNumber($request->rfid_number);

        // Find student profile by rfid_number
        $query = StudentProfile::where('rfid_number', $rfidNumber)->with(['user', 'classroom']);
        if ($schoolId) {
            $query->whereHas('user', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }
        $studentProfile = $query->first();

        if (!$studentProfile) {
            return response()->json([
                'success' => false,
                'message' => "Kartu RFID tidak terdaftar / tidak dikenali."
            ], 404);
        }

        $today = now()->toDateString();
        $timeIn = now()->toTimeString();

        // Check if student already checked in today
        $existingAttendance = StudentAttendance::where('student_profile_id', $studentProfile->id)
            ->where('date', $today)
            ->first();

        if ($existingAttendance) {
            return response()->json([
                'success' => false,
                'already_checked_in' => true,
                'message' => "Siswa {$studentProfile->user->name} sudah melakukan presensi masuk hari ini pada jam {$existingAttendance->time_in}.",
                'student' => [
                    'id' => $studentProfile->id,
                    'nama' => $studentProfile->user->name,
                    'kelas' => $studentProfile->classroom ? $studentProfile->classroom->name : '-',
                    'nisn' => $studentProfile->nisn,
                    'waktu' => $existingAttendance->time_in,
                    'foto' => $studentProfile->user->photo ? (str_starts_with($studentProfile->user->photo, 'http') ? $studentProfile->user->photo : asset($studentProfile->user->photo)) : null,
                ]
            ], 400);
        }

        // Attendance Threshold
        $school = $studentProfile->user->school;
        $lateThreshold = ($school && $school->attendance_threshold_time) ? $school->attendance_threshold_time : '07:15:00';

        $status = (strtotime($timeIn) <= strtotime($lateThreshold)) ? 'H' : 'T';

        $attendance = StudentAttendance::create([
            'student_profile_id' => $studentProfile->id,
            'date' => $today,
            'time_in' => $timeIn,
            'status' => $status,
            'verification_method' => 'rfid',
        ]);

        $initials = collect(explode(' ', $studentProfile->user->name))
            ->map(fn($n) => mb_substr($n, 0, 1))
            ->take(2)
            ->join('');

        $newLog = [
            'id' => $attendance->id,
            'nama' => $studentProfile->user->name,
            'kelas' => $studentProfile->classroom ? $studentProfile->classroom->name : '-',
            'nisn' => $studentProfile->nisn,
            'inisial' => strtoupper($initials),
            'waktu' => $timeIn,
            'tipe' => 'Masuk (RFID)',
            'status' => $status,
            'foto' => $studentProfile->user->photo ? (str_starts_with($studentProfile->user->photo, 'http') ? $studentProfile->user->photo : asset($studentProfile->user->photo)) : null,
        ];

        return response()->json([
            'success' => true,
            'message' => "Presensi RFID Berhasil! Selamat Datang, {$studentProfile->user->name}.",
            'log' => $newLog,
            'student' => [
                'id' => $studentProfile->id,
                'nama' => $studentProfile->user->name,
                'kelas' => $studentProfile->classroom ? $studentProfile->classroom->name : '-',
                'nisn' => $studentProfile->nisn,
                'waktu' => $timeIn,
                'status' => $status === 'H' ? 'Hadir' : 'Terlambat',
                'foto' => $studentProfile->user->photo ? (str_starts_with($studentProfile->user->photo, 'http') ? $studentProfile->user->photo : asset($studentProfile->user->photo)) : null,
            ]
        ]);
    }
}
