<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntakeForm extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'patient_id',
        'status',
        'source_type',
        'source_url',
        'extracted_payload',
        'confidence',
        'processed_at',
    ];

    protected $casts = [
        'extracted_payload' => 'array',
        'confidence' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'extracted');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['uploaded', 'processing']);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function isProcessed(): bool
    {
        return $this->status === 'extracted';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['uploaded', 'processing']);
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getConfidencePercentageAttribute(): ?int
    {
        return $this->confidence ? (int)($this->confidence * 100) : null;
    }

    public function isHighConfidence(): bool
    {
        return $this->confidence && $this->confidence >= 0.8;
    }

    public function isMediumConfidence(): bool
    {
        return $this->confidence && $this->confidence >= 0.6 && $this->confidence < 0.8;
    }

    public function isLowConfidence(): bool
    {
        return $this->confidence && $this->confidence < 0.6;
    }

    public function getExtractedDataAttribute($key = null)
    {
        if ($key) {
            return $this->extracted_payload[$key] ?? null;
        }
        
        return $this->extracted_payload;
    }
}