<?php
session_start();
require_once '../config/database.php';
require_once '../includes/tracking.php';

// Track logout for admins and studio staff
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'studio') {
        trackUserLogout($conn, $_SESSION['user_id']);
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to index page
header('Location: ../index.php');
exit();
