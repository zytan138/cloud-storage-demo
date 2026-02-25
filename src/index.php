<?php
require 'vendor/autoload.php';
use App\StorageService;

$storage = new StorageService();
$message = "";

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $content = $_POST['content'] ?? 'Default Content';
    $name = $_POST['name'] ?? 'data.txt';

    if ($type === 'object') {
        $message = $storage->uploadObject($name, $content);
    } elseif ($type === 'file') {
        $message = $storage->writeFile($name, $content);
    } elseif ($type === 'block') {
        $message = $storage->writeBlock($name, $content);
    }
}

// Fetch Data for Display
$objects = $storage->listObjects();
$files = $storage->listFiles();
$blockStats = $storage->getBlockStats();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cloud Storage Demo</title>
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 2rem auto; line-height: 1.6; }
        .card { border: 1px solid #ddd; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; }
        .object { border-top: 5px solid #3498db; }
        .file { border-top: 5px solid #2ecc71; }
        .block { border-top: 5px solid #e74c3c; }
        h2 { margin-top: 0; }
        input, textarea { width: 100%; margin-bottom: 0.5rem; padding: 8px; box-sizing: border-box;}
        button { background: #333; color: #fff; border: none; padding: 10px 20px; cursor: pointer; }
        button:hover { background: #555; }
        .log { background: #f4f4f4; padding: 10px; font-family: monospace; font-size: 0.9em; }
        .alert { background: #dff0d8; color: #3c763d; padding: 10px; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <h1>☁️ Cloud Storage Types Demo</h1>
    <p>This PHP app demonstrates how to interact with Object, File, and Block storage within a Docker container.</p>
    
    <?php if ($message): ?>
        <div class="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        
        <!-- OBJECT STORAGE -->
        <div class="card object">
            <h2>1. Object Storage (MinIO/S3)</h2>
            <p><strong>Access:</strong> HTTP API</p>
            <p><strong>Use:</strong> Images, Backups, Static Assets</p>
            <form method="POST">
                <input type="hidden" name="type" value="object">
                <input type="text" name="name" placeholder="Key (e.g., images/logo.png)" required>
                <textarea name="content" placeholder="File content..." rows="3"></textarea>
                <button type="submit">Upload Object</button>
            </form>
            <h3>Stored Objects:</h3>
            <ul class="log">
                <?php foreach ($objects as $obj): ?>
                    <li><?= htmlspecialchars($obj) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- FILE STORAGE -->
        <div class="card file">
            <h2>2. File Storage (EFS/NFS)</h2>
            <p><strong>Access:</strong> POSIX Path (`/mnt/efs`)</p>
            <p><strong>Use:</strong> Shared configs, CMS uploads</p>
            <form method="POST">
                <input type="hidden" name="type" value="file">
                <input type="text" name="name" placeholder="Filename (e.g., config.txt)" required>
                <textarea name="content" placeholder="File content..." rows="3"></textarea>
                <button type="submit">Write File</button>
            </form>
            <h3>Stored Files:</h3>
            <ul class="log">
                <?php foreach ($files as $file): ?>
                    <li><?= htmlspecialchars($file) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <!-- BLOCK STORAGE -->
    <div class="card block">
        <h2>3. Block Storage (EBS/Disks)</h2>
        <p><strong>Access:</strong> Mounted Device (`/dev/xvdf` -> `/mnt/data`)</p>
        <p><strong>Use:</strong> Databases, High IOPS, Single Instance</p>
        <div style="background: #eee; padding: 10px; margin-bottom: 10px;">
            <strong>Disk Stats:</strong> 
            Total: <?= $blockStats['total_mb'] ?> MB | 
            Free: <?= $blockStats['free_mb'] ?> MB | 
            Used: <?= $blockStats['used_percent'] ?>%
        </div>
        <form method="POST" style="display: inline-block; width: 48%;">
            <input type="hidden" name="type" value="block">
            <input type="text" name="name" placeholder="DB File (e.g., data.db)" required>
            <textarea name="content" placeholder="Binary/Data content..." rows="3"></textarea>
            <button type="submit">Write Block Data</button>
        </form>
        <p><em>Note: In Docker, Block and File volumes look similar. The difference is in the Orchestrator (K8s/ECS) configuration. Block volumes are typically NOT shared between containers.</em></p>
    </div>

</body>
</html>
