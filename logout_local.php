<?php
require_once 'config/config_local.php';

// Destroy session and redirect
session_destroy();
flash_message('You have been logged out successfully.', 'success');
redirect('login_local.php');
?>