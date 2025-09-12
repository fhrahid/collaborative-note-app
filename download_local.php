<?php
require_once 'config/config_local.php';

// Require login
require_login();

$user_id = get_current_user_id();
$attachment_id = (int)($_GET['id'] ?? 0);

if (!$attachment_id) {
    flash_message('Invalid attachment ID.', 'error');
    redirect('dashboard_local.php');
}

// Get attachment details and verify access permission
$stmt = $db->prepare("
    SELECT a.*, n.id as note_id
    FROM attachments a 
    JOIN notes n ON a.note_id = n.id 
    WHERE a.id = ?
");
$stmt->execute([$attachment_id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    flash_message('Attachment not found.', 'error');
    redirect('dashboard_local.php');
}

// Check if user has access to this note
$permission = can_user_access_note($attachment['note_id'], $user_id, $db);
if (!$permission) {
    flash_message('You do not have permission to download this attachment.', 'error');
    redirect('dashboard_local.php');
}

// Build file path
$file_path = get_upload_path() . '/' . $attachment['filename'];

if (!file_exists($file_path)) {
    flash_message('File not found on server.', 'error');
    redirect('dashboard_local.php');
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