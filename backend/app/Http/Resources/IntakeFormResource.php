<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntakeFormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'status' => $this->status,
            'source_type' => $this->source_type,
            'source_url' => $this->when(
                $request->user()->canViewIntakeFile($this->resource),
                $this->source_url
            ),
            'extracted_payload' => $this->extracted_payload,
            'confidence' => $this->confidence,
            'confidence_percentage' => $this->confidence_percentage,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Helper properties
            'is_processed' => $this->isProcessed(),
            'is_pending' => $this->isPending(),
            'has_failed' => $this->hasFailed(),
            'is_high_confidence' => $this->isHighConfidence(),
            'is_medium_confidence' => $this->isMediumConfidence(),
            'is_low_confidence' => $this->isLowConfidence(),
            
            // Relationships
            'patient' => new PatientResource($this->whenLoaded('patient')),
        ];
    }
}