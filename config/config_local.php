<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include MySQL database connection
require_once __DIR__ . '/database.php';

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}


function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

function generate_share_token() {
    return bin2hex(random_bytes(16));
}

function generate_share_url($note_id, $share_token = null) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remove trailing slash if not root
    $path = $path === '/' ? '' : rtrim($path, '/');
    $base_url = $protocol . '://' . $host . $path;
    
    if ($share_token) {
        return $base_url . '/shared_note_local.php?token=' . $share_token;
    } else {
        return $base_url . '/view_note_local.php?id=' . $note_id;
    }
}

function can_user_access_note($note_id, $user_id, $db) {
    // Check if user owns the note
    $stmt = $db->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    if ($stmt->rowCount() > 0) {
        return 'owner';
    }
    
    // Check if note is shared with user
    $stmt = $db->prepare("SELECT permission FROM shared_notes WHERE note_id = ? AND shared_with_user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $shared = $stmt->fetch();
    if ($shared) {
        return $shared['permission'];
    }
    
    // Check if user is a collaborator
    $stmt = $db->prepare("SELECT permission FROM collaborators WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$note_id, $user_id]);
    $collaborator = $stmt->fetch();
    if ($collaborator) {
        // Map collaborator permissions to shared permissions
        if ($collaborator['permission'] === 'admin' || $collaborator['permission'] === 'write') {
            return 'write';
        } else {
            return 'read';
        }
    }
    
    return false;
}

function search_users($query, $current_user_id, $db) {
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE (username LIKE ? OR email LIKE ?) AND id != ? LIMIT 10");
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm, $current_user_id]);
    return $stmt->fetchAll();
}

// File upload functions
function get_allowed_file_types() {
    return [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'document' => ['doc', 'docx', 'txt', 'rtf'],
        'pdf' => ['pdf'],
        'spreadsheet' => ['xls', 'xlsx', 'csv']
    ];
}

function get_allowed_mime_types() {
    return [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain', 'application/rtf',
        'application/pdf',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/csv'
    ];
}

function validate_file_upload($file) {
    $errors = [];
    $max_size = 10 * 1024 * 1024; // 10MB
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed'];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File size must be less than 10MB'];
    }
    
    // Check file extension
    $filename = strtolower($file['name']);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $allowed_types = get_allowed_file_types();
    $allowed_extensions = array_merge(...array_values($allowed_types));
    
    if (!in_array($extension, $allowed_extensions)) {
        return ['valid' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed_extensions)];
    }
    
    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, get_allowed_mime_types())) {
        return ['valid' => false, 'error' => 'Invalid file format'];
    }
    
    return ['valid' => true, 'error' => null];
}

function save_uploaded_file($file, $note_id) {
    global $db;
    
    // Create uploads directory if it doesn't exist
    $upload_dir = get_upload_path();
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create upload directory: " . $upload_dir);
            return false;
        }
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $stored_filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . '/' . $stored_filename;
    
    // Debug: Log the file upload attempt
    error_log("Attempting to upload file: " . $file['tmp_name'] . " to " . $file_path);
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Verify file was actually created
        if (!file_exists($file_path)) {
            error_log("File upload reported success but file doesn't exist: " . $file_path);
            return false;
        }
        
        // Get MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $file_type = get_file_category($extension);
        
        // Save to database
        $stmt = $db->prepare("
            INSERT INTO attachments (note_id, original_filename, filename, file_size, mime_type) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$note_id, $file['name'], $stored_filename, $file['size'], $mime_type])) {
            error_log("File upload successful: " . $file_path);
            return [
                'filename' => $stored_filename,
                'original_filename' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $mime_type
            ];
        } else {
            // Database insert failed, clean up file
            unlink($file_path);
            error_log("Database insert failed, removed file: " . $file_path);
        }
    } else {
        error_log("move_uploaded_file failed: " . $file['tmp_name'] . " to " . $file_path . " (error: " . error_get_last()['message'] . ")");
    }
    
    return false;
}

function get_file_category($extension) {
    $types = get_allowed_file_types();
    foreach ($types as $category => $extensions) {
        if (in_array(strtolower($extension), $extensions)) {
            return $category;
        }
    }
    return 'unknown';
}



function get_upload_path() {
    return realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'uploads';
}
?>