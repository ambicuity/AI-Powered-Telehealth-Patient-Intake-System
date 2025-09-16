import pytest
from fastapi.testclient import TestClient
from main import app

client = TestClient(app)

def test_health_endpoint():
    """Test health check endpoint"""
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert "version" in data
    assert "timestamp" in data

def test_root_endpoint():
    """Test root endpoint"""
    response = client.get("/")
    assert response.status_code == 200
    data = response.json()
    assert data["service"] == "Telehealth AI Intake ML Service"
    assert data["status"] == "running"

def test_parse_text_intake():
    """Test text-based intake parsing"""
    sample_text = """
    Patient: John Doe
    DOB: 01/15/1985
    Symptoms: headache, nausea, fever
    Medications: ibuprofen
    Allergies: penicillin
    Insurance: Aetna, Member ID: ABC123
    """
    
    request_data = {
        "text": sample_text,
        "source_type": "text",
        "intake_form_id": "test-123"
    }
    
    response = client.post("/parse", json=request_data)
    assert response.status_code == 200
    
    data = response.json()
    assert "data" in data
    assert "confidence" in data
    assert data["source_type"] == "text"
    assert data["intake_form_id"] == "test-123"
    
    # Check extracted data
    extracted = data["data"]
    assert extracted["full_name"] == "John Doe"
    assert extracted["dob"] == "1985-01-15"
    assert "headache" in extracted["symptoms"]
    assert "ibuprofen" in extracted["medications"]
    assert "penicillin" in extracted["allergies"]
    assert extracted["insurance"]["provider"] == "Aetna"
    assert extracted["insurance"]["member_id"] == "ABC123"

def test_parse_invalid_source_type():
    """Test parsing with invalid source type"""
    request_data = {
        "text": "Sample text",
        "source_type": "invalid",
    }
    
    response = client.post("/parse", json=request_data)
    assert response.status_code == 422  # Validation error

def test_parse_missing_text():
    """Test parsing without required text"""
    request_data = {
        "source_type": "text",
    }
    
    response = client.post("/parse", json=request_data)
    assert response.status_code == 400