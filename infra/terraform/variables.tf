# Project Configuration
variable "project_name" {
  description = "Name of the project"
  type        = string
  default     = "telehealth-ai-intake"
}

variable "environment" {
  description = "Environment name"
  type        = string
  
  validation {
    condition     = contains(["dev", "staging", "prod"], var.environment)
    error_message = "Environment must be dev, staging, or prod."
  }
}

variable "aws_region" {
  description = "AWS region"
  type        = string
  default     = "us-east-1"
}

# Network Configuration
variable "vpc_cidr" {
  description = "CIDR block for VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "private_subnets" {
  description = "CIDR blocks for private subnets"
  type        = list(string)
  default     = ["10.0.1.0/24", "10.0.2.0/24", "10.0.3.0/24"]
}

variable "public_subnets" {
  description = "CIDR blocks for public subnets"
  type        = list(string)
  default     = ["10.0.101.0/24", "10.0.102.0/24", "10.0.103.0/24"]
}

# Database Configuration
variable "db_name" {
  description = "Database name"
  type        = string
  default     = "telehealth_db"
}

variable "db_username" {
  description = "Database username"
  type        = string
  default     = "telehealth_user"
}

variable "db_password" {
  description = "Database password"
  type        = string
  sensitive   = true
}

# Application Configuration
variable "app_key" {
  description = "Laravel application key"
  type        = string
  sensitive   = true
}

variable "jwt_secret" {
  description = "JWT secret key"
  type        = string
  sensitive   = true
}

# Container Images
variable "backend_image" {
  description = "Backend container image"
  type        = string
  default     = "telehealth-backend:latest"
}

variable "ml_service_image" {
  description = "ML service container image"
  type        = string
  default     = "telehealth-ml:latest"
}

variable "frontend_image" {
  description = "Frontend container image"
  type        = string
  default     = "telehealth-frontend:latest"
}

# SSL/Domain Configuration
variable "certificate_arn" {
  description = "SSL certificate ARN for custom domain"
  type        = string
  default     = null
}

variable "custom_domain" {
  description = "Custom domain name"
  type        = string
  default     = null
}