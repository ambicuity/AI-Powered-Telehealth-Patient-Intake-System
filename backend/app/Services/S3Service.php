<?php

namespace App\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class S3Service
{
    private S3Client $s3Client;
    private string $bucket;

    public function __construct()
    {
        $this->bucket = config('filesystems.disks.s3.bucket');
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.s3.region'),
            'endpoint' => config('filesystems.disks.s3.endpoint'),
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint', false),
            'credentials' => [
                'key' => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
        ]);
    }

    public function uploadIntakeFile(UploadedFile $file, string $patientId): string
    {
        $fileName = $this->generateIntakeFileName($file, $patientId);
        $path = "intake-forms/{$patientId}/{$fileName}";

        // Store file in S3
        Storage::disk('s3')->put($path, file_get_contents($file->getPathname()), [
            'ContentType' => $file->getMimeType(),
            'ServerSideEncryption' => 'AES256',
            'Metadata' => [
                'patient-id' => $patientId,
                'original-name' => $file->getClientOriginalName(),
                'uploaded-at' => now()->toISOString(),
            ],
        ]);

        return $path;
    }

    public function getSignedUrl(string $path, int $expiresInMinutes = 60): string
    {
        $command = $this->s3Client->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $path,
        ]);

        $request = $this->s3Client->createPresignedRequest(
            $command,
            "+{$expiresInMinutes} minutes"
        );

        return (string) $request->getUri();
    }

    public function deleteFile(string $path): bool
    {
        try {
            Storage::disk('s3')->delete($path);
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    public function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    public function getFileSize(string $path): ?int
    {
        try {
            return Storage::disk('s3')->size($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function copyFile(string $fromPath, string $toPath): bool
    {
        try {
            return Storage::disk('s3')->copy($fromPath, $toPath);
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    private function generateIntakeFileName(UploadedFile $file, string $patientId): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $uuid = Str::uuid();
        $extension = $file->getClientOriginalExtension();
        
        return "intake_{$timestamp}_{$uuid}.{$extension}";
    }

    public function createBucketIfNotExists(): void
    {
        try {
            $this->s3Client->headBucket(['Bucket' => $this->bucket]);
        } catch (\Exception $e) {
            // Bucket doesn't exist, create it
            $this->s3Client->createBucket([
                'Bucket' => $this->bucket,
                'CreateBucketConfiguration' => [
                    'LocationConstraint' => config('filesystems.disks.s3.region'),
                ],
            ]);

            // Set bucket policy for private access
            $this->s3Client->putBucketPolicy([
                'Bucket' => $this->bucket,
                'Policy' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Effect' => 'Deny',
                            'Principal' => '*',
                            'Action' => 's3:*',
                            'Resource' => [
                                "arn:aws:s3:::{$this->bucket}/*",
                                "arn:aws:s3:::{$this->bucket}",
                            ],
                            'Condition' => [
                                'Bool' => [
                                    'aws:SecureTransport' => 'false',
                                ],
                            ],
                        ],
                    ],
                ]),
            ]);
        }
    }
}