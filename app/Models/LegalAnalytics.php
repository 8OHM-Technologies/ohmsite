<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class LegalAnalytics extends Model
{
    use HasFactory;

    protected $table = 'legal_analytics';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'data' => 'array',
        ];
    }

    /**
     * Get the applicant or plaintiff.
     */
    protected function applicant(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $val = $this->data['applicant_plaintiff'] ?? $this->data['employee'] ?? null;
                return is_array($val) ? implode(', ', $val) : $val;
            }
        );
    }

    /**
     * Get the respondent or defendant.
     */
    protected function respondent(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $val = $this->data['respondent_defendant'] ?? $this->data['employer'] ?? null;
                return is_array($val) ? implode(', ', $val) : $val;
            }
        );
    }

    /**
     * Get the court location.
     */
    protected function courtLocation(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                return $this->data['court_location'] ?? null;
            }
        );
    }

    /**
     * Get the dispute subjects or reason.
     */
    protected function subjects(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $val = $this->data['reason_for_dismissal'] ?? $this->data['subjects'] ?? $this->data['subject'] ?? null;
                return is_array($val) ? implode(', ', $val) : $val;
            }
        );
    }

    /**
     * Get the case outcome or final holding.
     */
    protected function outcome(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $val = $this->data['result'] ?? $this->data['order'] ?? $this->data['holding'] ?? null;
                return is_array($val) ? implode(', ', $val) : $val;
            }
        );
    }
}
