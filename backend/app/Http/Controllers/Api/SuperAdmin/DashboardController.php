<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Foundation;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get aggregate stats, growth trend, and activity logs for Superadmin Dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $startOfThisMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth   = now()->subMonth()->endOfMonth();

        // 1. Yayasan Stats
        $yayasanTotal     = Foundation::count();
        $yayasanAktif     = Foundation::where('status', 'active')->count();
        $yayasanNonAktif  = Foundation::where(function ($q) {
            $q->where('status', '!=', 'active')->orWhereNull('status');
        })->count();
        $yayasanThisMonth = Foundation::where('created_at', '>=', $startOfThisMonth)->count();
        $yayasanLastMonth = Foundation::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $yayasanGrowth    = $this->calculateGrowth($yayasanThisMonth, $yayasanLastMonth);

        // 2. Sekolah Stats
        $sekolahTotal     = School::count();
        $sekolahAktif     = School::where('status', 'active')->count();
        $sekolahNonAktif  = School::where(function ($q) {
            $q->where('status', '!=', 'active')->orWhereNull('status');
        })->count();
        $sekolahThisMonth = School::where('created_at', '>=', $startOfThisMonth)->count();
        $sekolahLastMonth = School::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $sekolahGrowth    = $this->calculateGrowth($sekolahThisMonth, $sekolahLastMonth);

        // 3. Guru Stats
        $guruQuery        = User::whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas']));
        $guruTotal        = (clone $guruQuery)->count();
        $guruThisMonth    = (clone $guruQuery)->where('created_at', '>=', $startOfThisMonth)->count();
        $guruLastMonth    = (clone $guruQuery)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $guruGrowth       = $this->calculateGrowth($guruThisMonth, $guruLastMonth);

        // 4. Siswa Stats
        $siswaQuery       = User::whereHas('role', fn($q) => $q->where('name', 'siswa'));
        $siswaTotal       = (clone $siswaQuery)->count();
        $siswaThisMonth   = (clone $siswaQuery)->where('created_at', '>=', $startOfThisMonth)->count();
        $siswaLastMonth   = (clone $siswaQuery)->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();
        $siswaGrowth      = $this->calculateGrowth($siswaThisMonth, $siswaLastMonth);

        // 5. Query Tahun Dinamis (Opsi B: DB records + fallback tahun berjalan & sebelumnya)
        $yearsFromDb = Foundation::selectRaw('DISTINCT EXTRACT(YEAR FROM created_at)::int as yr')
            ->union(School::selectRaw('DISTINCT EXTRACT(YEAR FROM created_at)::int as yr'))
            ->pluck('yr')
            ->filter()
            ->map(fn($y) => (int) $y)
            ->toArray();

        $currentYear    = (int) now()->format('Y');
        $availableYears = array_unique(array_merge($yearsFromDb, [$currentYear, $currentYear - 1]));
        rsort($availableYears);
        $availableYears = array_values($availableYears);

        // Tahun terpilih
        $requestedYear = $request->query('year');
        $selectedYear  = $requestedYear && in_array((int) $requestedYear, $availableYears)
            ? (int) $requestedYear
            : $availableYears[0];

        // 6. Tren Pertumbuhan Jan - Des untuk Tahun Terpilih
        $monthNames = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $growthChart   = [];
        $yearlyYayasan = 0;
        $yearlySekolah = 0;

        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::create($selectedYear, $m, 1)->startOfMonth();
            $end   = Carbon::create($selectedYear, $m, 1)->endOfMonth();

            $yCount = Foundation::whereBetween('created_at', [$start, $end])->count();
            $sCount = School::whereBetween('created_at', [$start, $end])->count();

            $yearlyYayasan += $yCount;
            $yearlySekolah += $sCount;

            $growthChart[] = [
                'bulan'   => $monthNames[$m],
                'yayasan' => $yCount,
                'sekolah' => $sCount,
            ];
        }

        $prevYearStart = Carbon::create($selectedYear - 1, 1, 1)->startOfYear();
        $prevYearEnd   = Carbon::create($selectedYear - 1, 12, 31)->endOfYear();
        $prevYearTotal = Foundation::whereBetween('created_at', [$prevYearStart, $prevYearEnd])->count()
                       + School::whereBetween('created_at', [$prevYearStart, $prevYearEnd])->count();
        $thisYearTotal = $yearlyYayasan + $yearlySekolah;
        $yearGrowth    = $this->calculateGrowth($thisYearTotal, $prevYearTotal);

        $growthSummary = [
            'year'           => (string) $selectedYear,
            'trendValue'     => ($yearGrowth['percent'] >= 0 ? '+' : '') . $yearGrowth['percent'] . '%',
            'trendDirection' => $yearGrowth['direction'],
            'footerTitle'    => $yearGrowth['percent'] >= 0
                ? "Pertumbuhan positif dibanding tahun sebelumnya"
                : "Sedikit penurunan dibanding tahun sebelumnya",
            'footerTrend'    => $yearGrowth['direction'],
            'footerSubtext'  => "Menampilkan total yayasan dan sekolah baru selama tahun {$selectedYear}",
        ];

        // Jika request hanya meminta data grafik tahunan
        if ($request->boolean('chart_only')) {
            return response()->json([
                'status' => 'success',
                'data'   => [
                    'growth_chart'    => $growthChart,
                    'growth_summary'  => $growthSummary,
                    'selected_year'   => $selectedYear,
                    'available_years' => $availableYears,
                ]
            ]);
        }

        // 7. Aktivitas Sistem (Agregasi peristiwa terbaru)
        $activities = $this->getRecentActivities();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'stats' => [
                    'yayasan' => [
                        'total'          => $yayasanTotal,
                        'aktif'          => $yayasanAktif,
                        'nonAktif'       => $yayasanNonAktif,
                        'newThisMonth'   => $yayasanThisMonth,
                        'growthPercent'  => $yayasanGrowth['percent'],
                        'trendDirection' => $yayasanGrowth['direction'],
                    ],
                    'sekolah' => [
                        'total'          => $sekolahTotal,
                        'aktif'          => $sekolahAktif,
                        'nonAktif'       => $sekolahNonAktif,
                        'newThisMonth'   => $sekolahThisMonth,
                        'growthPercent'  => $sekolahGrowth['percent'],
                        'trendDirection' => $sekolahGrowth['direction'],
                    ],
                    'guru' => [
                        'total'          => $guruTotal,
                        'newThisMonth'   => $guruThisMonth,
                        'growthPercent'  => $guruGrowth['percent'],
                        'trendDirection' => $guruGrowth['direction'],
                    ],
                    'siswa' => [
                        'total'          => $siswaTotal,
                        'newThisMonth'   => $siswaThisMonth,
                        'growthPercent'  => $siswaGrowth['percent'],
                        'trendDirection' => $siswaGrowth['direction'],
                    ],
                ],
                'growth_chart'    => $growthChart,
                'growth_summary'  => $growthSummary,
                'selected_year'   => $selectedYear,
                'available_years' => $availableYears,
                'activities'      => $activities,
            ]
        ]);
    }

    private function calculateGrowth(int $current, int $previous): array
    {
        if ($previous == 0) {
            return [
                'percent'   => $current > 0 ? 100.0 : 0.0,
                'direction' => 'up',
            ];
        }

        $diff = $current - $previous;
        $pct  = round(($diff / $previous) * 100, 1);

        return [
            'percent'   => abs($pct),
            'direction' => $pct >= 0 ? 'up' : 'down',
        ];
    }

    private function getRecentActivities(): array
    {
        $items = collect();

        // 1. Sekolah terbaru
        $recentSchools = School::with('foundation:id,name')->latest()->take(3)->get();
        foreach ($recentSchools as $sch) {
            $fName = $sch->foundation ? $sch->foundation->name : 'Yayasan';
            $items->push([
                'title'      => $fName,
                'desc'       => "Menambahkan entitas sekolah baru: {$sch->name}",
                'type'       => 'add',
                'created_at' => $sch->created_at ?? now(),
            ]);
        }

        // 2. Yayasan terbaru
        $recentFoundations = Foundation::latest()->take(2)->get();
        foreach ($recentFoundations as $fnd) {
            $items->push([
                'title'      => 'Superadmin',
                'desc'       => "Mendaftarkan yayasan baru: {$fnd->name}",
                'type'       => 'success',
                'created_at' => $fnd->created_at ?? now(),
            ]);
        }

        // 3. User guru terbaru
        $recentTeachers = User::whereHas('role', fn($q) => $q->whereIn('name', ['guru', 'wali_kelas']))
            ->with('school:id,name')
            ->latest()
            ->take(2)
            ->get();
        foreach ($recentTeachers as $t) {
            $sName = $t->school ? $t->school->name : 'Sekolah';
            $items->push([
                'title'      => "Admin {$sName}",
                'desc'       => "Memperbarui data guru: {$t->name}",
                'type'       => 'update',
                'created_at' => $t->created_at ?? now(),
            ]);
        }

        $sorted = $items->sortByDesc('created_at')->take(5)->values();

        return $sorted->map(function ($item, $idx) {
            $timeString = 'Baru saja';
            if ($item['created_at']) {
                $c = Carbon::parse($item['created_at']);
                $diffMinutes = (int) $c->diffInMinutes();
                $diffHours   = (int) $c->diffInHours();
                $diffDays    = (int) $c->diffInDays();

                if ($diffMinutes < 1) {
                    $timeString = 'Baru saja';
                } elseif ($diffMinutes < 60) {
                    $timeString = "{$diffMinutes} menit lalu";
                } elseif ($diffHours < 24) {
                    $timeString = "{$diffHours} jam lalu";
                } elseif ($diffDays === 1) {
                    $timeString = "Kemarin, " . $c->format('H:i');
                } else {
                    $timeString = "{$diffDays} hari lalu";
                }
            }

            return [
                'id'    => $idx + 1,
                'title' => $item['title'],
                'desc'  => $item['desc'],
                'time'  => $timeString,
                'type'  => $item['type'],
            ];
        })->toArray();
    }
}

