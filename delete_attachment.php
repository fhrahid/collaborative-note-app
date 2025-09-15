<?php
require_once 'config/config.php';

// Require login
require_login();

$user_id = get_current_user_id();
$attachment_id = (int)($_GET['id'] ?? 0);
$note_id = (int)($_GET['note_id'] ?? 0);

if (!$attachment_id || !$note_id) {
    flash_message('Invalid parameters.', 'error');
    redirect('dashboard.php');
}

// Get attachment details and verify access permission
$stmt = $db->prepare("
    SELECT a.*, n.id as note_id_check
    FROM attachments a 
    JOIN notes n ON a.note_id = n.id 
    WHERE a.id = ? AND n.id = ?
");
$stmt->execute([$attachment_id, $note_id]);
$attachment = $stmt->fetch();

if (!$attachment) {
    flash_message('Attachment not found.', 'error');
    redirect('dashboard.php');
}

// Check if user has edit permission for this note
$permission = can_user_access_note($note_id, $user_id, $db);
if ($permission !== 'write' && $permission !== 'owner') {
    flash_message('You do not have permission to delete this attachment.', 'error');
    redirect('dashboard.php');
}

// Delete file from filesystem
$file_path = get_upload_path() . '/' . $attachment['filename'];
if (file_exists($file_path)) {
    unlink($file_path);
}

// Delete from database
$stmt = $db->prepare("DELETE FROM attachments WHERE id = ?");
$success = $stmt->execute([$attachment_id]);

if ($success) {
    flash_message('Attachment deleted successfully.', 'success');
} else {
    flash_message('Failed to delete attachment.', 'error');
}

// Redirect based on permission level
if ($permission === 'owner') {
    redirect('note.php?id=' . $note_id);
} else {
    redirect('edit_shared_note.php?id=' . $note_id);
}
?>