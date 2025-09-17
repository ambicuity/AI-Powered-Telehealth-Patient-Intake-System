from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any, Union
from enum import Enum

class SourceType(str, Enum):
    PDF = "pdf"
    IMAGE = "image"
    TEXT = "text"

class ParseRequest(BaseModel):
    text: Optional[str] = None
    source_type: SourceType
    intake_form_id: Optional[str] = None
    file_path: Optional[str] = None

class InsuranceInfo(BaseModel):
    provider: Optional[str] = None
    member_id: Optional[str] = None

class ExtractedData(BaseModel):
    full_name: Optional[str] = None
    dob: Optional[str] = None
    symptoms: Optional[List[str]] = None
    medications: Optional[List[str]] = None
    allergies: Optional[List[str]] = None
    insurance: Optional[InsuranceInfo] = None
    medical_history: Optional[List[str]] = None
    chief_complaint: Optional[str] = None
    
    class Config:
        extra = "allow"  # Allow additional fields

class ParseResponse(BaseModel):
    data: ExtractedData
    confidence: float = Field(ge=0.0, le=1.0)
    processing_time_ms: Optional[int] = None
    source_type: SourceType
    intake_form_id: Optional[str] = None

class HealthResponse(BaseModel):
    status: str
    version: str
    timestamp: str
    ocr_available: bool
    model_loaded: bool

class ErrorResponse(BaseModel):
    error: str
    detail: Optional[str] = None
    code: Optional[str] = None