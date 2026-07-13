<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function download(string $dataset): StreamedResponse
    {
        if (! auth()->user()->hasOnceOffDatasetAccess()) {
            abort(403, 'Unauthorized access to dataset download.');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="8ohm_'.$dataset.'_dataset.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add BOM for Excel compatibility
            fwrite($file, "\xEF\xBB\xBF");

            // CSV Columns
            fputcsv($file, ['ID', 'Case Reference', 'Title', 'Employer', 'Employee', 'Court Location', 'Dismissal Reason', 'Outcome', 'Date Decision']);

            // Add high-quality mock data
            fputcsv($file, ['1', 'GAJB821-26', 'Mahlangu v Super Logistics', 'Super Logistics (Pty) Ltd', 'S Mahlangu', 'Gauteng [Johannesburg]', 'MISCONDUCT', 'Dismissal fair', '2026-02-14']);
            fputcsv($file, ['2', 'KNDB302-26', 'Naidoo v Coastal Retailers', 'Coastal Retailers CC', 'R Naidoo', 'KwaZulu-Natal [Durban]', 'INCAPACITY', 'Dismissal unfair - reinstated', '2026-03-01']);
            fputcsv($file, ['3', 'WCEB904-26', 'Smith v Cape Tech Solutions', 'Cape Tech Solutions', 'J Smith', 'Western Cape [Cape Town]', 'OPERATIONAL REQUIREMENTS', 'Retrenchment fair, compensation adjusted', '2026-04-18']);
            fputcsv($file, ['4', 'JR1102/25', 'NUMSA obo Members v Steelworks Ltd', 'Steelworks Ltd', 'NUMSA Members', 'Labour Court [Johannesburg]', 'STRIKE', 'Review application dismissed', '2026-01-20']);
            fputcsv($file, ['5', 'D724/25', 'SATAWU obo Khumalo v Port Authority', 'Port Authority', 'S Khumalo', 'Labour Court [Durban]', 'MISCONDUCT', 'Dismissal upheld', '2026-05-11']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
