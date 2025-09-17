import os
import logging
import time
from datetime import datetime
from typing import Dict, Any

import pytesseract
from PIL import Image
import spacy
from spacy.matcher import Matcher
import re

from app.models import ExtractedData, InsuranceInfo

class OCRService:
    def __init__(self):
        self.tesseract_cmd = os.getenv('TESSERACT_CMD', '/usr/bin/tesseract')
        self.language = os.getenv('OCR_LANGUAGE', 'eng')
        
        # Configure tesseract
        if os.path.exists(self.tesseract_cmd):
            pytesseract.pytesseract.tesseract_cmd = self.tesseract_cmd
        
    def extract_text_from_image(self, image_path: str) -> str:
        """Extract text from image using OCR"""
        try:
            image = Image.open(image_path)
            text = pytesseract.image_to_string(image, lang=self.language)
            return text.strip()
        except Exception as e:
            logging.error(f"OCR extraction failed: {e}")
            raise Exception(f"Failed to extract text from image: {e}")
    
    def is_available(self) -> bool:
        """Check if OCR service is available"""
        try:
            pytesseract.get_tesseract_version()
            return True
        except:
            return False

class NLPProcessor:
    def __init__(self):
        self.nlp = None
        self.matcher = None
        self._load_model()
        
    def _load_model(self):
        """Load spaCy model and setup matchers"""
        try:
            # Try to load English model
            self.nlp = spacy.load("en_core_web_sm")
        except OSError:
            # Fallback to blank model if not available
            logging.warning("spaCy model not found, using blank model")
            self.nlp = spacy.blank("en")
            
        self._setup_matchers()
    
    def _setup_matchers(self):
        """Setup pattern matchers for medical information"""
        if not self.nlp:
            return
            
        self.matcher = Matcher(self.nlp.vocab)
        
        # Name patterns
        name_patterns = [
            [{"LOWER": "name"}, {"IS_PUNCT": True, "OP": "?"}, {"IS_ALPHA": True}, {"IS_ALPHA": True, "OP": "?"}],
            [{"LOWER": "patient"}, {"IS_PUNCT": True, "OP": "?"}, {"IS_ALPHA": True}, {"IS_ALPHA": True, "OP": "?"}],
        ]
        self.matcher.add("NAME", name_patterns)
        
        # DOB patterns
        dob_patterns = [
            [{"LOWER": {"IN": ["dob", "birthday", "birth"]}}, {"IS_PUNCT": True, "OP": "?"}, {"LIKE_NUM": True}],
            [{"LOWER": "date"}, {"LOWER": "of"}, {"LOWER": "birth"}, {"IS_PUNCT": True, "OP": "?"}, {"LIKE_NUM": True}],
        ]
        self.matcher.add("DOB", dob_patterns)
        
        # Insurance patterns
        insurance_patterns = [
            [{"LOWER": "insurance"}, {"IS_PUNCT": True, "OP": "?"}, {"IS_ALPHA": True}],
            [{"LOWER": "provider"}, {"IS_PUNCT": True, "OP": "?"}, {"IS_ALPHA": True}],
        ]
        self.matcher.add("INSURANCE", insurance_patterns)
    
    def extract_structured_data(self, text: str) -> ExtractedData:
        """Extract structured medical data from text"""
        start_time = time.time()
        
        try:
            # Basic extraction using regex and NLP
            extracted = ExtractedData()
            
            # Extract name
            name = self._extract_name(text)
            if name:
                extracted.full_name = name
            
            # Extract DOB
            dob = self._extract_dob(text)
            if dob:
                extracted.dob = dob
            
            # Extract symptoms
            symptoms = self._extract_symptoms(text)
            if symptoms:
                extracted.symptoms = symptoms
            
            # Extract medications
            medications = self._extract_medications(text)
            if medications:
                extracted.medications = medications
            
            # Extract allergies
            allergies = self._extract_allergies(text)
            if allergies:
                extracted.allergies = allergies
            
            # Extract insurance
            insurance = self._extract_insurance(text)
            if insurance:
                extracted.insurance = insurance
            
            # Extract chief complaint
            complaint = self._extract_chief_complaint(text)
            if complaint:
                extracted.chief_complaint = complaint
            
            processing_time = int((time.time() - start_time) * 1000)
            logging.info(f"NLP processing completed in {processing_time}ms")
            
            return extracted
            
        except Exception as e:
            logging.error(f"NLP processing failed: {e}")
            raise Exception(f"Failed to process text: {e}")
    
    def _extract_name(self, text: str) -> str:
        """Extract patient name from text"""
        # Look for common name patterns
        patterns = [
            r"(?:name|patient)[\s:]+([A-Z][a-z]+\s+[A-Z][a-z]+)",
            r"([A-Z][a-z]+\s+[A-Z][a-z]+)(?:\s*,?\s*(?:DOB|Age))",
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()
        
        return None
    
    def _extract_dob(self, text: str) -> str:
        """Extract date of birth from text"""
        # Common date patterns
        patterns = [
            r"(?:DOB|birth|birthday)[\s:]+(\d{1,2}[/-]\d{1,2}[/-]\d{4})",
            r"(?:DOB|birth|birthday)[\s:]+(\d{4}[/-]\d{1,2}[/-]\d{1,2})",
            r"(\d{1,2}[/-]\d{1,2}[/-]\d{4})",
        ]
        
        for pattern in patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                date_str = match.group(1)
                # Normalize date format
                try:
                    # Try different date formats
                    for fmt in ["%m/%d/%Y", "%m-%d-%Y", "%Y/%m/%d", "%Y-%m-%d"]:
                        try:
                            date_obj = datetime.strptime(date_str, fmt)
                            return date_obj.strftime("%Y-%m-%d")
                        except ValueError:
                            continue
                except:
                    pass
                return date_str
        
        return None
    
    def _extract_symptoms(self, text: str) -> list:
        """Extract symptoms from text"""
        symptom_keywords = [
            "headache", "nausea", "fever", "pain", "cough", "fatigue", 
            "dizziness", "vomiting", "shortness of breath", "chest pain",
            "abdominal pain", "back pain", "sore throat", "runny nose"
        ]
        
        symptoms = []
        text_lower = text.lower()
        
        for symptom in symptom_keywords:
            if symptom in text_lower:
                symptoms.append(symptom)
        
        # Look for symptom sections
        symptom_section = re.search(r"symptoms?[\s:]+([^\.]+)", text, re.IGNORECASE)
        if symptom_section:
            symptom_text = symptom_section.group(1)
            # Split by common delimiters
            additional_symptoms = re.split(r'[,;]', symptom_text)
            for symptom in additional_symptoms:
                symptom = symptom.strip()
                if symptom and len(symptom) > 2:
                    symptoms.append(symptom)
        
        return list(set(symptoms)) if symptoms else None
    
    def _extract_medications(self, text: str) -> list:
        """Extract medications from text"""
        med_patterns = [
            r"(?:medications?|meds|drugs?)[\s:]+([^\.]+)",
            r"(?:taking|prescribed)[\s:]+([^\.]+)",
        ]
        
        medications = []
        
        for pattern in med_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                med_text = match.group(1)
                # Split by common delimiters
                meds = re.split(r'[,;]', med_text)
                for med in meds:
                    med = med.strip()
                    if med and len(med) > 2:
                        medications.append(med)
        
        # Common medication patterns
        common_meds = ["ibuprofen", "tylenol", "aspirin", "advil", "motrin"]
        text_lower = text.lower()
        
        for med in common_meds:
            if med in text_lower:
                medications.append(med)
        
        return list(set(medications)) if medications else None
    
    def _extract_allergies(self, text: str) -> list:
        """Extract allergies from text"""
        allergy_patterns = [
            r"(?:allergies?|allergic)[\s:]+([^\.]+)",
            r"allergic to[\s:]+([^\.]+)",
        ]
        
        allergies = []
        
        for pattern in allergy_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                allergy_text = match.group(1)
                # Split by common delimiters
                allergy_list = re.split(r'[,;]', allergy_text)
                for allergy in allergy_list:
                    allergy = allergy.strip()
                    if allergy and len(allergy) > 2:
                        allergies.append(allergy)
        
        # Common allergies
        common_allergies = ["penicillin", "shellfish", "nuts", "dairy", "eggs"]
        text_lower = text.lower()
        
        for allergy in common_allergies:
            if allergy in text_lower:
                allergies.append(allergy)
        
        return list(set(allergies)) if allergies else None
    
    def _extract_insurance(self, text: str) -> InsuranceInfo:
        """Extract insurance information from text"""
        insurance_info = InsuranceInfo()
        
        # Provider patterns
        provider_patterns = [
            r"(?:insurance|provider)[\s:]+([A-Za-z\s]+?)(?:member|id|\d)",
            r"(Aetna|Cigna|Humana|Anthem|BCBS|Kaiser)",
        ]
        
        for pattern in provider_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                insurance_info.provider = match.group(1).strip()
                break
        
        # Member ID patterns
        id_patterns = [
            r"(?:member|id|policy)[\s#:]+([A-Z0-9]+)",
            r"([A-Z]{3}\d{3,})",
        ]
        
        for pattern in id_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                insurance_info.member_id = match.group(1).strip()
                break
        
        return insurance_info if insurance_info.provider or insurance_info.member_id else None
    
    def _extract_chief_complaint(self, text: str) -> str:
        """Extract chief complaint from text"""
        complaint_patterns = [
            r"(?:chief complaint|complaint|reason for visit)[\s:]+([^\.]+)",
            r"(?:presenting|problem)[\s:]+([^\.]+)",
        ]
        
        for pattern in complaint_patterns:
            match = re.search(pattern, text, re.IGNORECASE)
            if match:
                return match.group(1).strip()
        
        return None
    
    def calculate_confidence(self, extracted_data: ExtractedData, text: str) -> float:
        """Calculate confidence score based on extracted data quality"""
        confidence_factors = []
        
        # Check for presence of key fields
        if extracted_data.full_name:
            confidence_factors.append(0.2)
        
        if extracted_data.dob:
            confidence_factors.append(0.15)
        
        if extracted_data.symptoms:
            confidence_factors.append(0.2)
        
        if extracted_data.medications:
            confidence_factors.append(0.15)
        
        if extracted_data.allergies:
            confidence_factors.append(0.1)
        
        if extracted_data.insurance:
            confidence_factors.append(0.1)
        
        if extracted_data.chief_complaint:
            confidence_factors.append(0.1)
        
        # Text quality factors
        if len(text) > 100:
            confidence_factors.append(0.05)
        
        if len(text) > 500:
            confidence_factors.append(0.05)
        
        base_confidence = sum(confidence_factors)
        
        # Ensure confidence is between 0 and 1
        return min(max(base_confidence, 0.1), 1.0)
    
    def is_model_loaded(self) -> bool:
        """Check if NLP model is loaded"""
        return self.nlp is not None