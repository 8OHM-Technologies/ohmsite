<?php

namespace App\Http\Middleware;

use App\Models\CcmaAnalytics;
use App\Models\Dataset;
use App\Models\LegalAnalytics;
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

                    $totalCases = LegalAnalytics::where('target_type', 'cases')->count()
                        + CcmaAnalytics::count();

                    $totalGazettes = LegalAnalytics::whereIn('target_type', ['gaz', 'journals'])->count();

                    $totalCourtRolls = LegalAnalytics::where('target_type', 'other')->count();

                    // Date range from legal_analytics document_date and ccma_analytics award_date
                    $minYear = LegalAnalytics::whereNotNull('document_date')
                        ->selectRaw('MIN(EXTRACT(YEAR FROM document_date::date)::int) as yr')
                        ->value('yr');
                    $maxYear = LegalAnalytics::whereNotNull('document_date')
                        ->selectRaw('MAX(EXTRACT(YEAR FROM document_date::date)::int) as yr')
                        ->value('yr');

                    $ccmaMin = CcmaAnalytics::whereNotNull('award_date')
                        ->selectRaw('MIN(EXTRACT(YEAR FROM award_date::date)::int) as yr')
                        ->value('yr');
                    $ccmaMax = CcmaAnalytics::whereNotNull('award_date')
                        ->selectRaw('MAX(EXTRACT(YEAR FROM award_date::date)::int) as yr')
                        ->value('yr');

                    $globalMin = collect(array_filter([$minYear, $ccmaMin]))->min();
                    $globalMax = collect(array_filter([$maxYear, $ccmaMax]))->max();

                    $dateRange = $globalMin && $globalMax
                        ? ($globalMin === $globalMax ? (string) $globalMin : "{$globalMin} – {$globalMax}")
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
