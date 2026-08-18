<?php

namespace App\Http\Middleware;

use App\Models\Dataset;
use App\Models\ScrubbedRecord;
use App\Services\CartService;
use Illuminate\Http\Request;
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
                    $totalRecords = ScrubbedRecord::count();

                    $coeusQuery = DB::connection('pgsql_coeus')->table('scrubbed_records')
                        ->join('extracted_records', 'extracted_records.id', '=', 'scrubbed_records.extracted_record_id');

                    $totalCases = (clone $coeusQuery)->where(function ($q) {
                        $q->where('extracted_records.record_type', 'sabinet_ccma')
                          ->orWhereRaw("extracted_records.data->>'category' = 'cases'");
                    })->count();

                    $totalGazettes = (clone $coeusQuery)
                        ->whereRaw("extracted_records.data->>'category' IN ('journals', 'gaz')")
                        ->count();

                    $totalCourtRolls = (clone $coeusQuery)
                        ->whereRaw("extracted_records.data->>'category' = 'other'")
                        ->count();

                    $minYear = (clone $coeusQuery)->whereNotNull('extracted_records.document_date')
                        ->selectRaw('MIN(EXTRACT(YEAR FROM extracted_records.document_date::date)::int) as yr')
                        ->value('yr');

                    $maxYear = (clone $coeusQuery)->whereNotNull('extracted_records.document_date')
                        ->selectRaw('MAX(EXTRACT(YEAR FROM extracted_records.document_date::date)::int) as yr')
                        ->value('yr');

                    $dateRange = $minYear && $maxYear
                        ? ($minYear === $maxYear ? (string) $minYear : "{$minYear} – {$maxYear}")
                        : 'N/A';

                    return [
                        'total_records' => $totalRecords,
                        'total_cases'   => $totalCases,
                        'total_gazettes' => $totalGazettes,
                        'total_court_rolls' => $totalCourtRolls,
                        'date_range'    => $dateRange,
                    ];
                } catch (\Throwable $e) {
                    return [
                        'total_records'     => 0,
                        'total_cases'       => 0,
                        'total_gazettes'    => 0,
                        'total_court_rolls' => 0,
                        'date_range'        => 'N/A',
                    ];
                }
            },
        ];
    }
}
