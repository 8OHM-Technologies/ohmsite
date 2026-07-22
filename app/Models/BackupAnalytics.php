<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupAnalytics extends Model
{
    protected $table = 'backup_analytics';

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
