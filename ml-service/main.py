import os
import logging
from contextlib import asynccontextmanager
from fastapi import FastAPI, HTTPException, File, UploadFile, Form, Depends
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import JSONResponse
import uvicorn

from app.models import ParseRequest, ParseResponse, HealthResponse, ErrorResponse
from app.services.intake_processor import IntakeProcessor

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s - %(name)s - %(levelname)s - %(message)s"
)
logger = logging.getLogger(__name__)

# Global processor instance
processor = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan events"""
    global processor
    logger.info("Starting ML Service...")
    
    # Initialize processor
    processor = IntakeProcessor()
    logger.info("ML Service initialized successfully")
    
    yield
    
    logger.info("Shutting down ML Service...")

# Create FastAPI app
app = FastAPI(
    title="Telehealth AI Intake ML Service",
    description="AI-powered medical intake form processing service",
    version="1.0.0",
    lifespan=lifespan
)

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify allowed origins
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Exception handler
@app.exception_handler(Exception)
async def general_exception_handler(request, exc):
    logger.error(f"Unhandled exception: {exc}")
    return JSONResponse(
        status_code=500,
        content=ErrorResponse(
            error="Internal server error",
            detail=str(exc)
        ).dict()
    )

@app.get("/health", response_model=HealthResponse)
async def health_check():
    """Health check endpoint"""
    if not processor:
        raise HTTPException(status_code=503, detail="Service not ready")
    
    health_status = processor.get_health_status()
    return HealthResponse(**health_status)

@app.post("/parse", response_model=ParseResponse)
async def parse_intake(request: ParseRequest):
    """Parse intake form and extract structured data"""
    if not processor:
        raise HTTPException(status_code=503, detail="Service not ready")
    
    try:
        logger.info(f"Processing intake request for {request.source_type}")
        result = await processor.process_intake(request)
        return result
        
    except ValueError as e:
        logger.warning(f"Validation error: {e}")
        raise HTTPException(status_code=400, detail=str(e))
        
    except Exception as e:
        logger.error(f"Processing error: {e}")
        raise HTTPException(status_code=500, detail=f"Processing failed: {str(e)}")

@app.post("/parse-file", response_model=ParseResponse)
async def parse_intake_file(
    file: UploadFile = File(...),
    source_type: str = Form(...),
    intake_form_id: str = Form(None)
):
    """Parse intake form from uploaded file"""
    if not processor:
        raise HTTPException(status_code=503, detail="Service not ready")
    
    # Validate file type
    if source_type not in ["pdf", "image"]:
        raise HTTPException(status_code=400, detail="Invalid source type for file upload")
    
    # Check file size (10MB limit)
    max_size = 10 * 1024 * 1024  # 10MB in bytes
    file_size = 0
    content = await file.read()
    file_size = len(content)
    
    if file_size > max_size:
        raise HTTPException(status_code=413, detail="File too large")
    
    # Validate file format
    allowed_types = {
        "pdf": ["application/pdf"],
        "image": ["image/jpeg", "image/jpg", "image/png"]
    }
    
    if file.content_type not in allowed_types.get(source_type, []):
        raise HTTPException(status_code=400, detail="Invalid file format")
    
    try:
        # Save file temporarily (in production, you might want to use cloud storage)
        import tempfile
        with tempfile.NamedTemporaryFile(delete=False, suffix=f".{file.filename.split('.')[-1]}") as temp_file:
            temp_file.write(content)
            temp_file_path = temp_file.name
        
        # Create request object
        request = ParseRequest(
            source_type=source_type,
            file_path=temp_file_path,
            intake_form_id=intake_form_id
        )
        
        # Process the file
        result = await processor.process_intake(request)
        
        # Clean up temporary file
        os.unlink(temp_file_path)
        
        return result
        
    except Exception as e:
        logger.error(f"File processing error: {e}")
        # Clean up temporary file on error
        try:
            if 'temp_file_path' in locals():
                os.unlink(temp_file_path)
        except:
            pass
        
        raise HTTPException(status_code=500, detail=f"File processing failed: {str(e)}")

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "service": "Telehealth AI Intake ML Service",
        "version": "1.0.0",
        "status": "running",
        "endpoints": {
            "health": "/health",
            "parse": "/parse",
            "parse_file": "/parse-file"
        }
    }

if __name__ == "__main__":
    host = os.getenv("API_HOST", "0.0.0.0")
    port = int(os.getenv("API_PORT", 8001))
    
    uvicorn.run(
        "main:app",
        host=host,
        port=port,
        reload=True,
        log_level="info"
    )