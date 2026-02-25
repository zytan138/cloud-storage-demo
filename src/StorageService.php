<?php
namespace App;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class StorageService {
    private $s3Client;
    private $bucket;
    private $fileMountPath;
    private $blockMountPath;

    public function __construct() {
        // --- OBJECT STORAGE CONFIG (MinIO) ---
        $this->bucket = getenv('MINIO_BUCKET');
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => 'us-east-1',
            'endpoint' => 'http://' . getenv('MINIO_ENDPOINT'),
            'credentials' => [
                'key'    => getenv('MINIO_ACCESS_KEY'),
                'secret' => getenv('MINIO_SECRET_KEY'),
            ],
            'use_path_style_endpoint' => true // Required for MinIO
        ]);

        // --- FILE & BLOCK PATHS ---
        // In code, both look like paths. The difference is the Docker Volume configuration.
        $this->fileMountPath = '/var/www/html/storage/file';
        $this->blockMountPath = '/var/www/html/storage/block';

        // Ensure directories exist
        if (!file_exists($this->fileMountPath)) mkdir($this->fileMountPath, 0777, true);
        if (!file_exists($this->blockMountPath)) mkdir($this->blockMountPath, 0777, true);
        
        // Ensure Bucket exists
        $this->ensureBucket();
    }

    private function ensureBucket() {
        if (!$this->s3Client->doesBucketExistV2($this->bucket)) {
            $this->s3Client->createBucket(['Bucket' => $this->bucket]);
        }
    }

    // 1. OBJECT STORAGE OPERATIONS
    public function uploadObject($key, $content) {
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $key,
                'Body'   => $content,
            ]);
            return "✅ Object '$key' uploaded to MinIO.";
        } catch (AwsException $e) {
            return "❌ Object Error: " . $e->getMessage();
        }
    }

    public function listObjects() {
        try {
            $objects = $this->s3Client->listObjects(['Bucket' => $this->bucket]);
            $keys = [];
            foreach ($objects['Contents'] ?? [] as $obj) {
                $keys[] = $obj['Key'];
            }
            return $keys;
        } catch (AwsException $e) {
            return ["Error: " . $e->getMessage()];
        }
    }

    // 2. FILE STORAGE OPERATIONS (Simulating EFS/NFS)
    // Use Case: Shared configs, user uploads accessible by multiple pods
    public function writeFile($filename, $content) {
        $path = $this->fileMountPath . '/' . $filename;
        // Add timestamp to prevent overwrite in demo
        $safeName = time() . "_" . $filename; 
        file_put_contents($path . $safeName, $content);
        return "✅ File '$safeName' written to File Storage (Shared).";
    }

    public function listFiles() {
        $files = glob($this->fileMountPath . '/*');
        return array_map('basename', $files);
    }

    // 3. BLOCK STORAGE OPERATIONS (Simulating EBS)
    // Use Case: Database files, high IOPS, single instance lock
    public function writeBlock($filename, $content) {
        $path = $this->blockMountPath . '/' . $filename;
        // Simulate binary write for DB-like behavior
        file_put_contents($path, $content);
        return "✅ Block data written to '$filename' (Local Disk).";
    }

    public function getBlockStats() {
        // Simulate checking disk usage (Block level metric)
        $total = disk_total_space($this->blockMountPath);
        $free = disk_free_space($this->blockMountPath);
        return [
            'total_mb' => round($total / 1024 / 1024, 2),
            'free_mb' => round($free / 1024 / 1024, 2),
            'used_percent' => round((($total - $free) / $total) * 100, 2)
        ];
    }
}
