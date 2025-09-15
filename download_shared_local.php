<?php
require_once 'config/config_local.php';

$attachment_id = (int)($_GET['id'] ?? 0);
$share_token = $_GET['token'] ?? '';

if (!$attachment_id || empty($share_token)) {
    http_response_code(404);
    exit('Invalid parameters');
}

// Get attachment details and verify it belongs to a publicly shared note
$stmt = $db->prepare("
    SELECT a.*, n.share_token, n.is_public 
    FROM attachments a 
    JOIN notes n ON a.note_id = n.id 
    WHERE a.id = ? AND n.share_token = ? AND n.is_public = 1
");
$stmt->execute([$attachment_id, $share_token]);
$attachment = $stmt->fetch();

if (!$attachment) {
    http_response_code(404);
    exit('Attachment not found');
}

// Build file path
$file_path = get_upload_path() . '/' . $attachment['filename'];

if (!file_exists($file_path)) {
    http_response_code(404);
    exit('File not found on server');
}

// Send file
$mime_type = $attachment['mime_type'];

// Ensure proper MIME type for common file types
$ext = strtolower(pathinfo($attachment['original_filename'], PATHINFO_EXTENSION));
switch ($ext) {
    case 'pdf':
        $mime_type = 'application/pdf';
        break;
    case 'doc':
        $mime_type = 'application/msword';
        break;
    case 'docx':
        $mime_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'xls':
        $mime_type = 'application/vnd.ms-excel';
        break;
    case 'xlsx':
        $mime_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        break;
    case 'txt':
        $mime_type = 'text/plain';
        break;
    case 'jpg':
    case 'jpeg':
        $mime_type = 'image/jpeg';
        break;
    case 'png':
        $mime_type = 'image/png';
        break;
    case 'gif':
        $mime_type = 'image/gif';
        break;
}

// Set appropriate headers
header('Content-Type: ' . $mime_type);
header('Content-Disposition: attachment; filename="' . addslashes($attachment['original_filename']) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Expires: 0');

// Clear any output buffers
while (ob_get_level()) {
    ob_end_clean();
}

readfile($file_path);
exit;
?>