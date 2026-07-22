<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function download(string $dataset): StreamedResponse
    {
        if (! auth()->user()->hasOnceOffDatasetAccess()) {
            abort(403, 'Unauthorized access to dataset download.');
        }

        $format = request()->query('format', 'csv');
        if (! in_array($format, ['csv', 'json'])) {
            $format = 'csv';
        }

        $filename = "8ohm_{$dataset}_dataset.{$format}";
        $path = "datasets/{$filename}";

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Requested dataset file not found.');
        }

        return Storage::disk('local')->download($path, $filename, [
            'Content-Type' => $format === 'json' ? 'application/json' : 'text/csv',
            'Content-Disposition' => 'attachment; filename="8ohm_'.$dataset.'_dataset.'.$format.'"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }
}
