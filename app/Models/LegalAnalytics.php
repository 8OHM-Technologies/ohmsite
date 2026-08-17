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
                $ext = $this->data['extracted_data'] ?? [];
                $val = $ext['applicant_plaintiff'] ?? $this->data['applicant_plaintiff'] ?? $ext['employee'] ?? $this->data['employee'] ?? null;
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
                $ext = $this->data['extracted_data'] ?? [];
                $val = $ext['respondent_defendant'] ?? $this->data['respondent_defendant'] ?? $ext['employer'] ?? $this->data['employer'] ?? null;
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
                $ext = $this->data['extracted_data'] ?? [];
                return $ext['court_location'] ?? $this->data['court_location'] ?? null;
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
                $ext = $this->data['extracted_data'] ?? [];
                $val = $ext['reason_for_dismissal'] ?? $this->data['reason_for_dismissal'] ?? $ext['subjects'] ?? $this->data['subjects'] ?? $ext['keywords'] ?? $this->data['keywords'] ?? null;
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
                $ext = $this->data['extracted_data'] ?? [];
                $val = $ext['result'] ?? $this->data['result'] ?? $ext['order'] ?? $this->data['order'] ?? $ext['holding'] ?? $this->data['holding'] ?? null;
                return is_array($val) ? implode(', ', $val) : $val;
            }
        );
    }

    /**
     * Get summary.
     */
    protected function summary(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $ext = $this->data['extracted_data'] ?? [];
                return $ext['summary'] ?? $this->data['summary'] ?? null;
            }
        );
    }

    /**
     * Get ratio decidendi.
     */
    protected function ratioDecidendi(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $ext = $this->data['extracted_data'] ?? [];
                return $ext['ratio_decidendi'] ?? $this->data['ratio_decidendi'] ?? null;
            }
        );
    }

    /**
     * Get obiter dicta.
     */
    protected function obiterDicta(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $ext = $this->data['extracted_data'] ?? [];
                return $ext['obiter_dicta'] ?? $this->data['obiter_dicta'] ?? null;
            }
        );
    }

    /**
     * Get order.
     */
    protected function order(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return $value;
                }
                $ext = $this->data['extracted_data'] ?? [];
                return $ext['order'] ?? $this->data['order'] ?? null;
            }
        );
    }

    /**
     * Get judges array.
     */
    protected function judges(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return is_array($value) ? $value : [$value];
                }
                $ext = $this->data['extracted_data'] ?? [];
                $j = $ext['judges'] ?? $this->data['judges'] ?? [];
                return is_array($j) ? $j : ($j ? [$j] : []);
            }
        );
    }

    /**
     * Get precedents cited.
     */
    protected function precedentsCited(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (!empty($value)) {
                    return is_array($value) ? $value : [];
                }
                $ext = $this->data['extracted_data'] ?? [];
                $p = $ext['precedents_cited'] ?? $this->data['precedents_cited'] ?? [];
                return is_array($p) ? $p : [];
            }
        );
    }

    /**
     * Get reportable flag.
     */
    protected function reportable(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value !== null) {
                    return (bool) $value;
                }
                $ext = $this->data['extracted_data'] ?? [];
                if (isset($ext['reportable'])) {
                    return (bool) $ext['reportable'];
                }
                if (isset($this->data['reportable'])) {
                    return (bool) $this->data['reportable'];
                }
                return true;
            }
        );
    }
}
