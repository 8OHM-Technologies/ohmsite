<?php

namespace App\Http\Middleware;

use App\Models\Dataset;
use App\Models\ScrubbedRecord;
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
                        $summary = DB::connection('pgsql_coeus')->table('scrubbed_records')
                            ->join('extracted_records', 'extracted_records.id', '=', 'scrubbed_records.extracted_record_id')
                            ->selectRaw("
                                COUNT(*) as total_records,
                                COUNT(*) FILTER (
                                    WHERE extracted_records.record_type = 'sabinet_ccma'
                                       OR extracted_records.data->>'category' = 'cases'
                                       OR (
                                           extracted_records.data->>'category' IS NULL
                                           AND extracted_records.record_type NOT ILIKE '%journal%'
                                           AND extracted_records.record_type NOT ILIKE '%gaz%'
                                           AND extracted_records.record_type NOT ILIKE '%roll%'
                                       )
                                ) as total_cases,
                                COUNT(*) FILTER (
                                    WHERE extracted_records.data->>'category' IN ('journals', 'gaz')
                                       OR extracted_records.record_type ILIKE '%journal%'
                                       OR extracted_records.record_type ILIKE '%gaz%'
                                ) as total_gazettes,
                                COUNT(*) FILTER (
                                    WHERE extracted_records.data->>'category' IN ('other', 'court_rolls')
                                       OR extracted_records.record_type ILIKE '%roll%'
                                ) as total_court_rolls,
                                MIN(EXTRACT(YEAR FROM extracted_records.document_date)::int) as min_year,
                                MAX(EXTRACT(YEAR FROM extracted_records.document_date)::int) as max_year
                            ")
                            ->first();

                        $minYear = $summary->min_year ?? null;
                        $maxYear = $summary->max_year ?? null;
                        $dateRange = $minYear && $maxYear
                            ? ($minYear === $maxYear ? (string) $minYear : "{$minYear} – {$maxYear}")
                            : 'N/A';

                        return [
                            'total_records'     => (int) ($summary->total_records ?? 0),
                            'total_cases'       => (int) ($summary->total_cases ?? 0),
                            'total_gazettes'    => (int) ($summary->total_gazettes ?? 0),
                            'total_court_rolls' => (int) ($summary->total_court_rolls ?? 0),
                            'date_range'        => $dateRange,
                        ];
                    });
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
