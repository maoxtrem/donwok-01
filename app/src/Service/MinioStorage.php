<?php

namespace App\Service;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MinioStorage
{
    private S3Client $client;

    public function __construct(
        #[Autowire('%env(MINIO_ENDPOINT)%')] string $endpoint,
        #[Autowire('%env(MINIO_ROOT_USER)%')] string $accessKey,
        #[Autowire('%env(MINIO_ROOT_PASSWORD)%')] string $secretKey,
        #[Autowire('%env(MINIO_BUCKET)%')] private readonly string $bucket,
    ) {
        $this->client = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => true,
            'credentials' => ['key' => $accessKey, 'secret' => $secretKey],
        ]);
    }

    public function putReceipt(UploadedFile $file, string $key): void
    {
        $this->ensureBucket();
        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);
    }

    public function delete(string $key): void
    {
        $this->client->deleteObject(['Bucket' => $this->bucket, 'Key' => $key]);
    }

    public function read(string $key): array
    {
        $object = $this->client->getObject(['Bucket' => $this->bucket, 'Key' => $key]);

        return [$object['Body']->getContents(), (string) ($object['ContentType'] ?? 'application/octet-stream')];
    }

    private function ensureBucket(): void
    {
        try {
            $this->client->headBucket(['Bucket' => $this->bucket]);
        } catch (S3Exception) {
            $this->client->createBucket(['Bucket' => $this->bucket]);
        }
    }
}
