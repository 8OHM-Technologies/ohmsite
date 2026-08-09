<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtractedRecord extends Model
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'pgsql_coeus';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'extracted_records';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_url' => 'string',
            'document_date' => 'date',
            'record_type' => 'string',
            'data' => 'array',
            'requires_human_review' => 'boolean',
            'review_reason' => 'string',
            'scraped_at' => 'datetime',
            'cleaned_at' => 'datetime',
            'detailed_at' => 'datetime',
            'status' => 'string',
        ];
    }
}
