<?php
// download.php — Secure file download for reviewers/admins
require_once __DIR__ . '/includes/config.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'researcher') {
    http_response_code(403);
    exit('Access denied.');
}

$storedName    = basename($_GET['file'] ?? '');
$submissionId  = (int)($_GET['sub'] ?? 0);

if (!$storedName || !$submissionId) {
    http_response_code(400);
    exit('Invalid request.');
}

$pdo  = getPDO();
$stmt = $pdo->prepare('SELECT * FROM submission_files WHERE stored_name = ? AND submission_id = ?');
$stmt->execute([$storedName, $submissionId]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    exit('File not found.');
}

$filePath = UPLOAD_DIR . $submissionId . '/' . $storedName;

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File missing from server.');
}

// Serve the file
$mime = mime_content_type($filePath) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $file['original_name'] . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache');
readfile($filePath);
exit;
