<?php
function checkStudioAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'studio') {
        header("Location: ../auth/login.php");
        exit();
    }
}

function checkAdminAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit();
    }
}

function checkStudentAccess() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
        header("Location: ../auth/login.php");
        exit();
    }
}

function checkUserAccess() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function isStudio() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'studio';
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}
