<?php
require_once __DIR__ . '/../auth_check.php';
require_role('admin');
require_once __DIR__ . '/../db.php';

$reportId = (int)($_GET['report_id'] ?? 0);
if ($reportId <= 0) {
    http_response_code(400);
    exit('Invalid request.');
}

$stmt = $pdo->prepare("SELECT photo_path FROM waste_reports WHERE id = ? LIMIT 1");
$stmt->execute([$reportId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['photo_path'])) {
    http_response_code(404);
    exit('No photo for this report.');
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$filePath   = realpath(__DIR__ . '/../' . $row['photo_path']);

if ($filePath === false || strpos($filePath, $uploadsDir . DIRECTORY_SEPARATOR) !== 0) {
    http_response_code(403);
    exit('Access denied.');
}

if (!is_file($filePath)) {
    http_response_code(404);
    exit('Photo file not found.');
}

$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);
$allowed  = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($mimeType, $allowed, true)) {
    http_response_code(403);
    exit('Invalid file type.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');
readfile($filePath);
