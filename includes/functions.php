<?php
/**
 * Collection of helper functions for the SOCMED application
 */

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Format date to a readable format
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('F d, Y', strtotime($date));
}

/**
 * Format time to 12-hour format
 * @param string $time
 * @return string
 */
function format_time($time) {
    return date('h:i A', strtotime($time));
}

/**
 * Generate a random string
 * @param int $length
 * @return string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $random_string;
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 * @return bool
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect user with a message
 * @param string $location
 * @param string $message
 * @param string $type (success/error)
 */
function redirect_with_message($location, $message, $type = 'success') {
    $_SESSION[$type] = $message;
    header("Location: $location");
    exit();
}

/**
 * Get user's profile picture URL
 * @param string $profile_picture
 * @return string
 */
function get_profile_picture_url($profile_picture) {
    if (empty($profile_picture)) {
        return '../assets/images/default-avatar.png';
    }
    return $profile_picture;
}

/**
 * Format file size
 * @param int $size
 * @return string
 */
function format_file_size($size) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = 0;
    while ($size >= 1024 && $i < 4) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

/**
 * Create notification
 * @param int $user_id
 * @param string $type
 * @param string $message
 * @param string $link
 * @return bool
 */
function create_notification($user_id, $type, $message, $link = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $type, $message, $link);
    return $stmt->execute();
}

/**
 * Get user's unread notifications count
 * @param int $user_id
 * @return int
 */
function get_unread_notifications_count($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

/**
 * Mark notification as read
 * @param int $notification_id
 * @return bool
 */
function mark_notification_as_read($notification_id) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Log user activity
 * @param int $user_id
 * @param string $action
 * @param string $details
 * @return bool
 */
function log_activity($user_id, $action, $details = null) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $action, $details, $ip_address, $user_agent);
    return $stmt->execute();
}

/**
 * Validate date format
 * @param string $date
 * @param string $format
 * @return bool
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate time format
 * @param string $time
 * @param string $format
 * @return bool
 */
function validate_time($time, $format = 'H:i') {
    $d = DateTime::createFromFormat($format, $time);
    return $d && $d->format($format) === $time;
}

/**
 * Check if appointment time is available
 * @param string $date
 * @param string $time
 * @param int $exclude_appointment_id
 * @return bool
 */
function is_appointment_time_available($date, $time, $exclude_appointment_id = null) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM appointments WHERE preferred_date = ? AND preferred_time = ? AND status != 'rejected'";
    $params = [$date, $time];
    $types = "ss";
    
    if ($exclude_appointment_id) {
        $query .= " AND id != ?";
        $params[] = $exclude_appointment_id;
        $types .= "i";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'] === 0;
}

/**
 * Get appointment status badge class
 * @param string $status
 * @return string
 */
function get_status_badge_class($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'completed' => 'primary',
        'rejected' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}
?>
