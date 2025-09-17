import os
import time
import logging
from typing import Optional
from datetime import datetime

from app.models import ParseRequest, ParseResponse, ExtractedData
from app.services.nlp_service import OCRService, NLPProcessor

class IntakeProcessor:
    def __init__(self):
        self.ocr_service = OCRService()
        self.nlp_processor = NLPProcessor()
        self.logger = logging.getLogger(__name__)
        
    async def process_intake(self, request: ParseRequest) -> ParseResponse:
        """Process intake form and extract structured data"""
        start_time = time.time()
        
        try:
            # Extract text based on source type
            if request.source_type == "text":
                text = request.text
                if not text:
                    raise ValueError("Text is required for text source type")
            
            elif request.source_type in ["pdf", "image"]:
                if not request.file_path:
                    raise ValueError("File path is required for file-based source types")
                
                # For now, we'll simulate file processing
                # In a real implementation, you'd handle actual file processing
                text = await self._extract_text_from_file(request.file_path, request.source_type)
            
            else:
                raise ValueError(f"Unsupported source type: {request.source_type}")
            
            # Process text with NLP
            extracted_data = self.nlp_processor.extract_structured_data(text)
            
            # Calculate confidence score
            confidence = self.nlp_processor.calculate_confidence(extracted_data, text)
            
            # Calculate processing time
            processing_time_ms = int((time.time() - start_time) * 1000)
            
            response = ParseResponse(
                data=extracted_data,
                confidence=confidence,
                processing_time_ms=processing_time_ms,
                source_type=request.source_type,
                intake_form_id=request.intake_form_id
            )
            
            self.logger.info(f"Successfully processed intake form {request.intake_form_id} in {processing_time_ms}ms with confidence {confidence}")
            
            return response
            
        except Exception as e:
            self.logger.error(f"Failed to process intake form {request.intake_form_id}: {e}")
            raise Exception(f"Processing failed: {str(e)}")
    
    async def _extract_text_from_file(self, file_path: str, source_type: str) -> str:
        """Extract text from uploaded file"""
        try:
            if source_type == "image":
                return self.ocr_service.extract_text_from_image(file_path)
            
            elif source_type == "pdf":
                # For PDF processing, you'd typically use libraries like PyPDF2 or pdfplumber
                # For this demo, we'll return a sample text
                return self._get_sample_pdf_text()
            
            else:
                raise ValueError(f"Unsupported file type: {source_type}")
                
        except Exception as e:
            raise Exception(f"Failed to extract text from {source_type}: {e}")
    
    def _get_sample_pdf_text(self) -> str:
        """Return sample PDF text for demonstration"""
        return """
        Patient Intake Form
        
        Name: Jane Doe
        DOB: 05/01/1990
        Insurance: Aetna, Member ID: ABC123456
        
        Chief Complaint: Recurring headaches and nausea
        
        Current Symptoms:
        - Severe headaches (daily for past week)
        - Nausea and vomiting
        - Sensitivity to light
        - Fatigue
        
        Current Medications:
        - Ibuprofen 400mg as needed
        - Birth control (daily)
        
        Allergies:
        - Penicillin (rash)
        - Shellfish (anaphylaxis)
        
        Medical History:
        - Migraines (diagnosed 2018)
        - No surgeries
        - No chronic conditions
        """
    
    def get_health_status(self) -> dict:
        """Get health status of the ML service"""
        return {
            "status": "healthy",
            "version": "1.0.0",
            "timestamp": datetime.utcnow().isoformat(),
            "ocr_available": self.ocr_service.is_available(),
            "model_loaded": self.nlp_processor.is_model_loaded(),
            "supported_formats": ["pdf", "image", "text"],
        }