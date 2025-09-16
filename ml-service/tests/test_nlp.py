import pytest
from app.services.nlp_service import NLPProcessor

@pytest.fixture
def nlp_processor():
    return NLPProcessor()

def test_extract_name(nlp_processor):
    """Test name extraction from text"""
    text = "Patient: Jane Smith, DOB: 05/01/1990"
    name = nlp_processor._extract_name(text)
    assert name == "Jane Smith"

def test_extract_dob(nlp_processor):
    """Test date of birth extraction"""
    text = "DOB: 05/01/1990"
    dob = nlp_processor._extract_dob(text)
    assert dob == "1990-05-01"

def test_extract_symptoms(nlp_processor):
    """Test symptom extraction"""
    text = "Symptoms: headache, nausea, fever"
    symptoms = nlp_processor._extract_symptoms(text)
    assert "headache" in symptoms
    assert "nausea" in symptoms
    assert "fever" in symptoms

def test_extract_medications(nlp_processor):
    """Test medication extraction"""
    text = "Medications: ibuprofen, acetaminophen"
    medications = nlp_processor._extract_medications(text)
    assert "ibuprofen" in medications
    assert "acetaminophen" in medications

def test_extract_allergies(nlp_processor):
    """Test allergy extraction"""
    text = "Allergies: penicillin, shellfish"
    allergies = nlp_processor._extract_allergies(text)
    assert "penicillin" in allergies
    assert "shellfish" in allergies

def test_extract_insurance(nlp_processor):
    """Test insurance information extraction"""
    text = "Insurance: Aetna, Member ID: ABC123456"
    insurance = nlp_processor._extract_insurance(text)
    assert insurance.provider == "Aetna"
    assert insurance.member_id == "ABC123456"

def test_confidence_calculation(nlp_processor):
    """Test confidence score calculation"""
    from app.models import ExtractedData, InsuranceInfo
    
    extracted_data = ExtractedData(
        full_name="John Doe",
        dob="1985-01-15",
        symptoms=["headache", "nausea"],
        medications=["ibuprofen"],
        allergies=["penicillin"],
        insurance=InsuranceInfo(provider="Aetna", member_id="ABC123")
    )
    
    text = "A comprehensive medical intake form with detailed information"
    confidence = nlp_processor.calculate_confidence(extracted_data, text)
    
    assert 0.0 <= confidence <= 1.0
    assert confidence > 0.8  # Should be high confidence with all fields