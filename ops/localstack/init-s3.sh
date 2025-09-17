#!/bin/bash

# Create S3 bucket for file uploads
awslocal s3api create-bucket --bucket telehealth-uploads --region us-east-1

# Set bucket policy for private access
awslocal s3api put-bucket-policy --bucket telehealth-uploads --policy '{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Effect": "Deny",
            "Principal": "*",
            "Action": "s3:*",
            "Resource": [
                "arn:aws:s3:::telehealth-uploads/*",
                "arn:aws:s3:::telehealth-uploads"
            ],
            "Condition": {
                "Bool": {
                    "aws:SecureTransport": "false"
                }
            }
        }
    ]
}'

# Enable versioning
awslocal s3api put-bucket-versioning --bucket telehealth-uploads --versioning-configuration Status=Enabled

# Set server-side encryption
awslocal s3api put-bucket-encryption --bucket telehealth-uploads --server-side-encryption-configuration '{
    "Rules": [
        {
            "ApplyServerSideEncryptionByDefault": {
                "SSEAlgorithm": "AES256"
            }
        }
    ]
}'

echo "LocalStack S3 setup completed"