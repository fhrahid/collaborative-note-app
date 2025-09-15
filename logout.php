<?php
require_once 'config/config.php';

// Destroy session and redirect
session_destroy();
flash_message('You have been logged out successfully.', 'success');
redirect('login.php');
?>