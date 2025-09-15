<?php
require_once 'config/config.php';

$attachment_id = (int)($_GET['id'] ?? 0);
$share_token = $_GET['token'] ?? '';

if (!$attachment_id) {
    if ($share_token) {
        http_response_code(404);
        echo "Invalid attachment ID.";
        exit;
    } else {
        flash_message('Invalid attachment ID.', 'error');
        redirect('dashboard.php');
    }
}

// Get attachment details
$stmt = $db->prepare("
    SELECT a.*, n.id as note_id, n.share_token, n.is_public, n.user_id
    FROM attachments a 
    JOIN notes n ON a.note_id = n.id 
    WHERE a.id = ?
");
$stmt->execute([$attachment_id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    if ($share_token) {
        http_response_code(404);
        echo "Attachment not found.";
        exit;
    } else {
        flash_message('Attachment not found.', 'error');
        redirect('dashboard.php');
    }
}

// Check access permission
$has_access = false;

if ($share_token) {
    // Token-based access for public shared notes
    if ($attachment['is_public'] && $attachment['share_token'] === $share_token) {
        $has_access = true;
    }
} else {
    // Regular logged-in user access
    require_login();
    $user_id = get_current_user_id();
    $permission = can_user_access_note($attachment['note_id'], $user_id, $db);
    if ($permission) {
        $has_access = true;
    }
}

if (!$has_access) {
    if ($share_token) {
        http_response_code(403);
        echo "You do not have permission to download this attachment.";
        exit;
    } else {
        flash_message('You do not have permission to download this attachment.', 'error');
        redirect('dashboard.php');
    }
}

// Build file path
$file_path = get_upload_path() . '/' . $attachment['filename'];

if (!file_exists($file_path)) {
    flash_message('File not found on server.', 'error');
    redirect('dashboard.php');
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
    case 'webp':
        $mime_type = 'image/webp';
        break;
    case 'mp3':
        $mime_type = 'audio/mpeg';
        break;
    case 'wav':
        $mime_type = 'audio/wav';
        break;
    case 'mp4':
        $mime_type = 'video/mp4';
        break;
    case 'avi':
        $mime_type = 'video/x-msvideo';
        break;
    case 'zip':
        $mime_type = 'application/zip';
        break;
    case 'rar':
        $mime_type = 'application/x-rar-compressed';
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