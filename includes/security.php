<?php
session_start();

class Security {
    // CSRF token management
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('Invalid CSRF token');
        }
        return true;
    }

    // Input sanitization
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // File upload validation
    public static function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png'], $maxSize = 5242880) {
        $errors = [];

        if (!isset($file['error']) || is_array($file['error'])) {
            $errors[] = 'Invalid file parameters.';
            return $errors;
        }

        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File size exceeds limit.';
                return $errors;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file uploaded.';
                return $errors;
            default:
                $errors[] = 'Unknown file upload error.';
                return $errors;
        }

        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size (5MB).';
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors[] = 'Invalid file type. Only JPG and PNG allowed.';
        }

        return $errors;
    }

    // Password validation
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if (!preg_match('/[!@#$%^&*()\-_=+{};:,<.>]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return $errors;
    }

    // Session security
    public static function secureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.use_only_cookies', 1);
            session_start();
        }
        
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
        }
        
        if (time() - $_SESSION['last_activity'] > 1800) { // 30 minutes
            session_unset();
            session_destroy();
            header('Location: /login.php?session=expired');
            exit();
        }
        
        $_SESSION['last_activity'] = time();
    }

    // Rate limiting
    private static $attempts = [];
    
    public static function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) { // 5 attempts per 5 minutes
        $now = time();
        
        if (!isset(self::$attempts[$key])) {
            self::$attempts[$key] = ['count' => 0, 'first_attempt' => $now];
        }
        
        if ($now - self::$attempts[$key]['first_attempt'] > $timeWindow) {
            self::$attempts[$key] = ['count' => 0, 'first_attempt' => $now];
        }
        
        self::$attempts[$key]['count']++;
        
        if (self::$attempts[$key]['count'] > $maxAttempts) {
            throw new Exception('Too many attempts. Please try again later.');
        }
        
        return true;
    }

    // XSS Prevention Headers
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\' https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdn.jsdelivr.net; img-src \'self\' data: https:; font-src \'self\' https://cdn.jsdelivr.net');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    }

    // Activity Logging
    public static function logActivity($userId, $action, $details = '') {
        global $conn;
        
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param("issss", 
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );
        
        return $stmt->execute();
    }
}

// Initialize security measures
Security::setSecurityHeaders();
Security::secureSession();
?>
