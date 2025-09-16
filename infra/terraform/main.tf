terraform {
  required_version = ">= 1.5"
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project     = "telehealth-ai-intake"
      Environment = var.environment
      ManagedBy   = "terraform"
    }
  }
}

# Data sources
data "aws_availability_zones" "available" {
  state = "available"
}

data "aws_caller_identity" "current" {}

# VPC Module
module "vpc" {
  source = "./modules/vpc"

  environment         = var.environment
  availability_zones  = data.aws_availability_zones.available.names
  vpc_cidr           = var.vpc_cidr
  private_subnets    = var.private_subnets
  public_subnets     = var.public_subnets
}

# RDS Module
module "rds" {
  source = "./modules/rds"

  environment          = var.environment
  vpc_id              = module.vpc.vpc_id
  database_subnets    = module.vpc.database_subnets
  vpc_security_group_ids = [module.vpc.database_security_group_id]
  
  db_name     = var.db_name
  db_username = var.db_username
  db_password = var.db_password
  
  backup_retention_period = var.environment == "prod" ? 7 : 1
  backup_window          = "03:00-04:00"
  maintenance_window     = "sun:04:00-sun:05:00"
}

# S3 Module
module "s3" {
  source = "./modules/s3"

  environment = var.environment
  
  # Bucket names
  uploads_bucket_name = "${var.project_name}-${var.environment}-uploads"
  static_bucket_name  = "${var.project_name}-${var.environment}-static"
}

# ECS Module
module "ecs" {
  source = "./modules/ecs"

  environment = var.environment
  vpc_id      = module.vpc.vpc_id
  
  public_subnets  = module.vpc.public_subnets
  private_subnets = module.vpc.private_subnets
  
  # Database connection
  db_host     = module.rds.db_endpoint
  db_name     = var.db_name
  db_username = var.db_username
  db_password = var.db_password
  
  # S3 buckets
  uploads_bucket = module.s3.uploads_bucket_name
  static_bucket  = module.s3.static_bucket_name
  
  # Application configuration
  app_key = var.app_key
  jwt_secret = var.jwt_secret
  
  # Container images
  backend_image    = var.backend_image
  ml_service_image = var.ml_service_image
  frontend_image   = var.frontend_image
}

# CloudFront Distribution
module "cloudfront" {
  source = "./modules/cloudfront"

  environment     = var.environment
  static_bucket   = module.s3.static_bucket_domain_name
  api_domain      = module.ecs.alb_dns_name
  
  # SSL certificate ARN (if using custom domain)
  certificate_arn = var.certificate_arn
  custom_domain   = var.custom_domain
}