<?php

namespace App\Models;

use Database\Factories\AnalyticsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analytics extends Model
{
    /** @use HasFactory<AnalyticsFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'award_date' => 'date',
            'hearing_start' => 'date',
            'hearing_end' => 'date',
            'date_modified' => 'date',
            'details_scraped_at' => 'datetime',
        ];
    }
}
