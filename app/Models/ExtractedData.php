<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtractedData extends Model
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
            'data' => 'array',
            'processed_at' => 'datetime',
            'extracted_at' => 'datetime',
            'cleaned_at' => 'datetime',
            'document_date' => 'date',
            'requires_human_review' => 'boolean',
        ];
    }
}