<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScrapingPipelineMetrics extends Model
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
    protected $table = 'pipelines_scrapingpipelinemetrics';

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
     * Disable Eloquent's automatic timestamp management.
     * The updated_at column is written by Django; we only read it.
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
            'pipeline_name' => 'string',
            'metrics' => 'array',
            'updated_at' => 'datetime',
        ];
    }
}
