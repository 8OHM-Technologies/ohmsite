<?php

namespace App\Http\Middleware;

use App\Models\Dataset;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'unread_notifications_count' => $user ? ($user->unreadNotifications()->count() ?? 0) : 0,
                'notifications' => $user ? $user->notifications()->take(5)->get() : [],
            ],
            'cart_count' => function () {
                try {
                    return app(CartService::class)
                        ->getCart()
                        ->items
                        ->sum('quantity');
                } catch (\Throwable $e) {
                    return 0;
                }
            },
            'datasets' => function () {
                try {
                    return Dataset::where('is_active', true)->get()->map(fn ($d) => [
                        'name' => $d->name,
                        'slug' => $d->slug,
                        'description' => $d->description,
                    ]);
                } catch (\Throwable $e) {
                    return [];
                }
            },
            'app_url' => config('app.url'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'dataset_summary' => function () {
                try {
                    return Cache::remember('dataset_summary', 3600, function () {
                        $isPgsql = DB::connection('pgsql_coeus')->getDriverName() === 'pgsql';

                        if ($isPgsql) {
                            $totalRecords = DB::connection('pgsql_coeus')->table('scrubbed_records')->count();
                            $totalCases = DB::connection('pgsql_coeus')->table('scrubbed_records')
                                ->whereRaw("get_scrubbed_record_category(data) = 'cases'")
                                ->count();
                            $totalJournals = DB::connection('pgsql_coeus')->table('scrubbed_records')
                                ->whereRaw("get_scrubbed_record_category(data) = 'journals'")
                                ->count();
                            $totalCourtRolls = DB::connection('pgsql_coeus')->table('scrubbed_records')
                                ->whereRaw("get_scrubbed_record_category(data) = 'court_rolls'")
                                ->count();
                            $totalGazettes = DB::connection('pgsql_coeus')->table('scrubbed_records')
                                ->whereRaw("get_scrubbed_record_category(data) = 'gazettes'")
                                ->count();

                            $yearRange = DB::connection('pgsql_coeus')->table('extracted_records')
                                ->whereNotNull('scrubbed_at')
                                ->selectRaw('MIN(EXTRACT(YEAR FROM document_date)::int) as min_year, MAX(EXTRACT(YEAR FROM document_date)::int) as max_year')
                                ->first();

                            $minYear = $yearRange->min_year ?? null;
                            $maxYear = $yearRange->max_year ?? null;
                        } else {
                            $totalRecords = DB::connection('pgsql_coeus')->table('scrubbed_records')->count();
                            $totalCases = $totalRecords;
                            $totalJournals = 0;
                            $totalGazettes = 0;
                            $totalCourtRolls = 0;
                            $minYear = 2020;
                            $maxYear = (int) date('Y');
                        }

                        $dateRange = $minYear && $maxYear
                            ? ($minYear === $maxYear ? (string) $minYear : "{$minYear} – {$maxYear}")
                            : 'N/A';

                        return [
                            'total_records' => (int) $totalRecords,
                            'total_cases' => (int) $totalCases,
                            'total_journals' => (int) $totalJournals,
                            'total_gazettes' => (int) $totalGazettes,
                            'total_court_rolls' => (int) $totalCourtRolls,
                            'date_range' => $dateRange,
                        ];
                    });
                } catch (\Throwable $e) {
                    return [
                        'total_records' => 0,
                        'total_cases' => 0,
                        'total_journals' => 0,
                        'total_gazettes' => 0,
                        'total_court_rolls' => 0,
                        'date_range' => 'N/A',
                    ];
                }
            },
        ];
    }
}
