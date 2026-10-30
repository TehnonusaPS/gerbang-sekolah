<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SuperAdmin\FinanceController;
use App\Http\Controllers\Api\FoundationController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\ExtracurricularController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\AcademicYearController;

use App\Http\Controllers\Api\AcademicCalendarController;
use App\Http\Controllers\Api\TeacherAgendaController;
use App\Http\Controllers\Api\CurriculumController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
|
*/


// routes/api.php

use Illuminate\Support\Facades\DB;

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'connected'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Midtrans Public Webhook Notification Callback Route (Unauthenticated)
Route::post('/spp/payments/notification', [\App\Http\Controllers\Api\SppController::class, 'handleNotification']);
Route::post('/finance/spp/payments/notification', [\App\Http\Controllers\Api\SppController::class, 'handleNotification']);

Route::get('/env-check', function () {
    return [
        'DB_CONNECTION' => env('DB_CONNECTION'),
        'DB_HOST' => env('DB_HOST'),
        'DB_PORT' => env('DB_PORT'),
        'DB_DATABASE' => env('DB_DATABASE'),
    ];
});

Route::get('/test-broadcast', function () {
    try {
        $broadcaster = config('broadcasting.default');
        $pusherConfig = config('broadcasting.connections.pusher');
        
        // Find a message to use, or create a mock one
        $message = \App\Models\Message::latest()->first();
        if (!$message) {
            $message = new \App\Models\Message();
            $message->sender_id = 1;
            $message->receiver_id = 2;
            $message->message = "Test Message";
        }
        
        $result = event(new \App\Events\MessageSent($message));
        
        return response()->json([
            'status' => 'success',
            'broadcaster' => $broadcaster,
            'pusher_app_id' => !empty($pusherConfig['app_id']) ? 'configured' : 'NOT configured',
            'pusher_key' => !empty($pusherConfig['key']) ? 'configured' : 'NOT configured',
            'pusher_secret' => !empty($pusherConfig['secret']) ? 'configured' : 'NOT configured',
            'pusher_cluster' => !empty($pusherConfig['options']['cluster']) ? $pusherConfig['options']['cluster'] : 'NOT configured',
            'event_dispatch_result' => $result,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});

// Broadcasting auth via Sanctum token (for Laravel Echo with Bearer token)
Route::post('/broadcasting/auth', [\Illuminate\Broadcasting\BroadcastController::class, 'authenticate'])
    ->middleware('auth:sanctum')
    ->name('broadcasting.auth');

use App\Http\Controllers\Api\LandingPageConfigController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/landing-page/public/{slug}', [LandingPageConfigController::class, 'getPublicLandingPageBySlug']);

// Protected routes (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    // Landing Page Admin Routes
    Route::post('/landing-page/upload', [LandingPageConfigController::class, 'uploadImage']);
    Route::get('/landing-page/foundations', [LandingPageConfigController::class, 'getFoundationsList']);
    Route::get('/landing-page/schools', [LandingPageConfigController::class, 'getSchoolsList']);
    Route::get('/landing-page/foundations/{id}', [LandingPageConfigController::class, 'getFoundationConfig']);
    Route::get('/landing-page/schools/{id}', [LandingPageConfigController::class, 'getSchoolConfig']);
    Route::put('/landing-page/foundations/{id}', [LandingPageConfigController::class, 'updateFoundationConfig']);
    Route::put('/landing-page/schools/{id}', [LandingPageConfigController::class, 'updateSchoolConfig']);

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/profile', [AuthController::class, 'updateProfile']);
    Route::put('/user/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Chat routes
    Route::get('/chat/contacts', [\App\Http\Controllers\Api\ChatController::class, 'getContacts']);
    Route::get('/chat/unread-count', [\App\Http\Controllers\Api\ChatController::class, 'getUnreadCount']);
    Route::get('/chat/messages/{recipient_id}', [\App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/chat/messages', [\App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
    Route::post('/chat/messages/{message_id}/read', [\App\Http\Controllers\Api\ChatController::class, 'markAsRead']);

    // Announcement routes
    Route::apiResource('/announcements', \App\Http\Controllers\Api\AnnouncementController::class);

    // Notification routes
    Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

    // Feedback routes
    Route::apiResource('/feedbacks', \App\Http\Controllers\Api\FeedbackController::class);

    // Active Student Certificate routes (Persuratan Aktif)
    Route::apiResource('/komunikasi/surat-aktif', \App\Http\Controllers\Api\ActiveStudentCertificateController::class);
    Route::apiResource('/komunikasi/surat-dispensasi', \App\Http\Controllers\Api\StudentDispensationCertificateController::class);
    Route::apiResource('/komunikasi/surat-peringatan', \App\Http\Controllers\Api\StudentWarningCertificateController::class);
    Route::apiResource('/komunikasi/berita-kegiatan', \App\Http\Controllers\Api\ActivityNewsController::class);

    // Super Admin Finance Routes
    Route::middleware('role:superadmin')->prefix('superadmin/finance')->group(function () {
        Route::get('/dashboard', [FinanceController::class, 'indexDashboard']);
        Route::get('/foundations', [FinanceController::class, 'getFoundations']);
        
        // Plans CRUD
        Route::get('/plans', [FinanceController::class, 'plansIndex']);
        Route::post('/plans', [FinanceController::class, 'plansStore']);
        Route::get('/plans/{plan}', [FinanceController::class, 'plansShow']);
        Route::put('/plans/{plan}', [FinanceController::class, 'plansUpdate']);
        Route::delete('/plans/{plan}', [FinanceController::class, 'plansDestroy']);

        // Subscriptions
        Route::get('/subscriptions', [FinanceController::class, 'getSubscriptions']);
        Route::get('/subscriptions/{subscription}', [FinanceController::class, 'subscriptionsShow']);
        Route::put('/subscriptions/{subscription}', [FinanceController::class, 'subscriptionsUpdate']);
        Route::delete('/subscriptions/{subscription}', [FinanceController::class, 'subscriptionsDestroy']);

        // Payments / Invoicing
        Route::get('/payments', [FinanceController::class, 'getPayments']);
        Route::post('/payments', [FinanceController::class, 'createInvoice']);
        Route::post('/payments/{id}/verify', [FinanceController::class, 'verifyPayment']);
    });

    // Management Data Routes (Yayasan, Sekolah & Pengguna)
    Route::middleware('role:superadmin,admin_yayasan,admin_sekolah,kepala_sekolah,tata_usaha,wali_kelas')->prefix('management')->group(function () {
        Route::get('/roles', [UserController::class, 'getRoles']);
        Route::apiResource('/foundations', FoundationController::class);
        Route::apiResource('/schools', SchoolController::class);
        Route::apiResource('/users', UserController::class);
        Route::apiResource('/students', StudentController::class);
        Route::post('/classrooms/{id}/assign-students', [ClassroomController::class, 'assignStudents']);
        Route::apiResource('/classrooms', ClassroomController::class);
        Route::apiResource('/teachers', TeacherController::class);
        Route::apiResource('/extracurriculars', ExtracurricularController::class);
        Route::patch('/subjects/{id}/toggle-status', [SubjectController::class, 'toggleStatus']);
        Route::apiResource('/subjects', SubjectController::class);
        Route::apiResource('/curriculums', CurriculumController::class);
        Route::apiResource('/academic-years', AcademicYearController::class);

        // Time slots routes
        Route::post('/time-slots/bulk', [\App\Http\Controllers\Api\TimeSlotController::class, 'bulkStore']);
        Route::apiResource('/time-slots', \App\Http\Controllers\Api\TimeSlotController::class)->only(['index', 'destroy']);

        // Schedule routes
        Route::get('/schedules/unassigned-subjects', [\App\Http\Controllers\Api\ScheduleController::class, 'unassignedSubjects']);
        Route::get('/schedules/teacher-conflicts', [\App\Http\Controllers\Api\ScheduleController::class, 'teacherConflicts']);
        Route::post('/schedules/bulk', [\App\Http\Controllers\Api\ScheduleController::class, 'bulkStore']);
        Route::post('/schedules/publish', [\App\Http\Controllers\Api\ScheduleController::class, 'publish']);
        Route::post('/schedules/unpublish', [\App\Http\Controllers\Api\ScheduleController::class, 'unpublish']);
        Route::apiResource('/schedules', \App\Http\Controllers\Api\ScheduleController::class);
    });

    // Akademik Routes (Guru, Wali Kelas, Admin Sekolah, Kepala Sekolah)
    Route::middleware('role:guru,wali_kelas,admin_sekolah,kepala_sekolah')
        ->prefix('akademik')
        ->group(function () {
             // Data lookup
             Route::get('/my-subjects', [\App\Http\Controllers\Api\AkademikDataController::class, 'getMySubjects']);
             Route::get('/subjects/{subjectId}/my-classrooms', [\App\Http\Controllers\Api\AkademikDataController::class, 'getMyClassrooms']);
             Route::get('/classrooms/{id}/students', [\App\Http\Controllers\Api\AkademikDataController::class, 'getStudentsByClassroom']);
             Route::get('/active-academic-year', [\App\Http\Controllers\Api\AkademikDataController::class, 'getActiveAcademicYear']);
             Route::get('/my-schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'mySchedule']);
 
             // Materi Pelajaran
             Route::apiResource('/materials', \App\Http\Controllers\Api\SubjectMaterialController::class);
             Route::get('/materials/{id}/download', [\App\Http\Controllers\Api\SubjectMaterialController::class, 'download']);
             Route::patch('/materials/{id}/toggle-status', [\App\Http\Controllers\Api\SubjectMaterialController::class, 'toggleStatus']);
 
             // Penilaian (Tugas & Ujian digabung)
             Route::apiResource('/assessments', \App\Http\Controllers\Api\AssessmentController::class);
             Route::patch('/assessments/{id}/toggle-status', [\App\Http\Controllers\Api\AssessmentController::class, 'toggleStatus']);
         });
 
     Route::middleware('role:siswa')
         ->prefix('siswa/akademik')
         ->group(function () {
             Route::get('/my-classrooms', [\App\Http\Controllers\Api\SiswaAkademikController::class, 'getMyClassrooms']);
             Route::get('/subjects', [\App\Http\Controllers\Api\SiswaAkademikController::class, 'getSubjects']);
             Route::get('/overview', [\App\Http\Controllers\Api\SiswaAkademikController::class, 'getSubjectOverview']);
             Route::get('/stats', [\App\Http\Controllers\Api\SiswaAkademikController::class, 'getGlobalStats']);
             Route::get('/schedule', [\App\Http\Controllers\Api\ScheduleController::class, 'studentSchedule']);
             Route::get('/materials/{id}/download', [\App\Http\Controllers\Api\SubjectMaterialController::class, 'download']);
         });

    // Student SPP & Finance routes
    Route::prefix('finance')->group(function () {
        Route::get('/spp/dashboard', [\App\Http\Controllers\Api\SppController::class, 'getDashboard']);
        Route::get('/spp/bills', [\App\Http\Controllers\Api\SppController::class, 'getBills']);
        Route::get('/spp/payments', [\App\Http\Controllers\Api\SppController::class, 'getPayments']);
        Route::get('/spp/payments/{id}', [\App\Http\Controllers\Api\SppController::class, 'getPaymentDetails']);
        Route::post('/spp/payments', [\App\Http\Controllers\Api\SppController::class, 'createPayment']);
        Route::post('/spp/payments/{id}/verify', [\App\Http\Controllers\Api\SppController::class, 'verifyPayment']);
        
        // Tariffs CRUD
        Route::get('/spp/tariffs', [\App\Http\Controllers\Api\SppController::class, 'getTariffs']);
        Route::post('/spp/tariffs', [\App\Http\Controllers\Api\SppController::class, 'storeTariff']);
        Route::put('/spp/tariffs/{id}', [\App\Http\Controllers\Api\SppController::class, 'updateTariff']);
        Route::delete('/spp/tariffs/{id}', [\App\Http\Controllers\Api\SppController::class, 'deleteTariff']);
    });

    // Kalender Akademik - Admin Sekolah & Kepala Sekolah (CRUD + Approval)
    Route::middleware('role:admin_sekolah,kepala_sekolah')
        ->prefix('academic-calendar')
        ->group(function () {
            Route::post('/setup-dates', [AcademicCalendarController::class, 'setupYearDates']);
            Route::get('/events', [AcademicCalendarController::class, 'index']);
            Route::post('/events', [AcademicCalendarController::class, 'store']);
            Route::post('/events/batch', [AcademicCalendarController::class, 'batchStore']);
            Route::put('/events/{id}', [AcademicCalendarController::class, 'update']);
            Route::delete('/events/{id}', [AcademicCalendarController::class, 'destroy']);
            Route::get('/status', [AcademicCalendarController::class, 'calendarStatus']);
            Route::post('/submit', [AcademicCalendarController::class, 'submit']);
            Route::post('/approve', [AcademicCalendarController::class, 'approve']);
            Route::post('/reject', [AcademicCalendarController::class, 'reject']);
            Route::post('/reset', [AcademicCalendarController::class, 'reset']);

            // Exam Schedule routes
            Route::get('/exam-schedules', [\App\Http\Controllers\Api\ExamScheduleController::class, 'index']);
            Route::post('/exam-schedules/bulk', [\App\Http\Controllers\Api\ExamScheduleController::class, 'bulkStore']);
            Route::post('/exam-schedules/publish', [\App\Http\Controllers\Api\ExamScheduleController::class, 'publish']);
            Route::post('/exam-schedules/unpublish', [\App\Http\Controllers\Api\ExamScheduleController::class, 'unpublish']);
            Route::delete('/exam-schedules/sessions/{id}', [\App\Http\Controllers\Api\ExamScheduleController::class, 'destroySession']);
        });

    // Exam Schedule Read-Only / My Schedule for all authenticated roles
    Route::get('/exam-schedules/my-schedule', [\App\Http\Controllers\Api\ExamScheduleController::class, 'mySchedule']);

    // Teacher Agendas CRUD & Read
    Route::middleware('role:guru,wali_kelas')
        ->prefix('teacher-agendas')
        ->group(function () {
            Route::get('/my-classrooms', [TeacherAgendaController::class, 'myClassrooms']);
            Route::post('/', [TeacherAgendaController::class, 'store']);
            Route::put('/{id}', [TeacherAgendaController::class, 'update']);
            Route::delete('/{id}', [TeacherAgendaController::class, 'destroy']);
        });
    Route::get('/teacher-agendas', [TeacherAgendaController::class, 'index']);

    // Kalender Akademik - Read-Only (Guru, Wali Kelas, Siswa, Orang Tua)
    Route::middleware('role:guru,wali_kelas,siswa,orang_tua')
        ->prefix('academic-calendar')
        ->group(function () {
            Route::get('/public-events', [AcademicCalendarController::class, 'publicEvents']);
        });

    // Orang Tua & Siswa - Jadwal Pelajaran Anak & Kalender
    Route::middleware('role:orang_tua,siswa')
        ->prefix('orang-tua')
        ->group(function () {
            Route::get('/schedule', [AcademicCalendarController::class, 'parentSchedule']);
            Route::get('/jadwal-anak', [AcademicCalendarController::class, 'parentDashboard']);
        });
    // Student Attendance & Face/RFID Registration
    Route::get('/absensi/siswa', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'index']);
    Route::get('/absensi/siswa/logs', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'logs']);
    Route::post('/absensi/siswa/scan', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'scan']);
    Route::post('/absensi/siswa/scan-rfid', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'scanRfid']);
    Route::post('/absensi/siswa/{id}/register-face', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'registerFace']);
    Route::post('/absensi/siswa/{id}/register-rfid', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'registerRfid']);
    Route::get('/absensi/siswa/monthly-grid', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'getMonthlyGrid']);
    Route::post('/absensi/siswa/monthly-grid/update', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'updateMonthlyCell']);
    Route::post('/absensi/siswa/{id}/status', [\App\Http\Controllers\Api\StudentAttendanceController::class, 'changeStatus']);

    // Staff Attendance & Leaves
    Route::post('/absensi/clock-in', [\App\Http\Controllers\Api\StaffAttendanceController::class, 'clockIn']);
    Route::get('/absensi/history', [\App\Http\Controllers\Api\StaffAttendanceController::class, 'myHistory']);
    Route::post('/absensi/leaves', [\App\Http\Controllers\Api\StaffAttendanceController::class, 'submitLeaveRequest']);
    Route::get('/absensi/leaves', [\App\Http\Controllers\Api\StaffAttendanceController::class, 'myLeaveRequests']);

    // Admin Sekolah Attendance Control
    Route::middleware('role:admin_sekolah')->group(function () {
        Route::put('/admin/absensi/settings', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'updateThreshold']);
        Route::get('/admin/absensi/settings', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'getSettings']);
        Route::get('/admin/absensi/leaves', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'getPendingLeaves']);
        Route::post('/admin/absensi/leaves/{id}/action', [\App\Http\Controllers\Api\AdminAttendanceController::class, 'approveRejectLeave']);
    });

    // ─── Report Routes ──────────────────────────────────────────────────
    // School-level reports
    Route::middleware('role:admin_sekolah,kepala_sekolah,tata_usaha,guru,wali_kelas,orang_tua')
        ->prefix('reports/school')
        ->group(function () {
            Route::get('/attendance', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'attendance']);
            Route::get('/academic', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'academic']);
            Route::get('/finance', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'finance']);
            Route::get('/grades', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'grades']);
            Route::get('/student-development', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'studentDevelopment']);
            Route::get('/staff', [\App\Http\Controllers\Api\Reports\ReportSchoolController::class, 'staff']);
        });

    // Foundation-level reports
    Route::middleware('role:superadmin,admin_yayasan')
        ->prefix('reports/foundation')
        ->group(function () {
            Route::get('/consolidation', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'consolidation']);
            Route::get('/academic', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'academic']);
            Route::get('/infrastructure', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'infrastructure']);
            Route::get('/finance', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'finance']);
            Route::get('/hr', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'hr']);
            Route::get('/students', [\App\Http\Controllers\Api\Reports\ReportFoundationController::class, 'students']);
        });

    // Perpustakaan & Aset Routes
    Route::middleware('role:superadmin,admin_yayasan,admin_sekolah,kepala_sekolah')->group(function () {
        Route::apiResource('perpustakaans', \App\Http\Controllers\Api\PerpustakaanController::class);
        Route::apiResource('asets', \App\Http\Controllers\Api\AsetController::class);
        Route::apiResource('ruangans', \App\Http\Controllers\RuanganController::class);
    });
});

