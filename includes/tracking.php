<?php
function getDeviceInfo($user_agent) {
    $device = 'Unknown';
    $os = 'Unknown';
    $browser = 'Unknown';

    // Detect Device Type
    if (strpos($user_agent, 'Mobile') !== false || strpos($user_agent, 'Android') !== false || strpos($user_agent, 'iPhone') !== false) {
        $device = 'Mobile';
    } elseif (strpos($user_agent, 'Tablet') !== false || strpos($user_agent, 'iPad') !== false) {
        $device = 'Tablet';
    } else {
        $device = 'Desktop';
    }

    // Detect Operating System
    if (strpos($user_agent, 'Windows') !== false) {
        $os = 'Windows';
    } elseif (strpos($user_agent, 'Mac OS X') !== false) {
        $os = 'macOS';
    } elseif (strpos($user_agent, 'Android') !== false) {
        $os = 'Android';
    } elseif (strpos($user_agent, 'iPhone') !== false || strpos($user_agent, 'iPad') !== false) {
        $os = 'iOS';
    } elseif (strpos($user_agent, 'Linux') !== false) {
        $os = 'Linux';
    }

    // Detect Browser
    if (strpos($user_agent, 'Chrome') !== false) {
        $browser = 'Chrome';
    } elseif (strpos($user_agent, 'Firefox') !== false) {
        $browser = 'Firefox';
    } elseif (strpos($user_agent, 'Safari') !== false) {
        $browser = 'Safari';
    } elseif (strpos($user_agent, 'Edge') !== false) {
        $browser = 'Edge';
    } elseif (strpos($user_agent, 'MSIE') !== false || strpos($user_agent, 'Trident/') !== false) {
        $browser = 'Internet Explorer';
    }

    return [
        'device' => $device,
        'os' => $os,
        'browser' => $browser
    ];
}

function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

function trackUserLogin($conn, $user_id) {
    $ip = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $today = date('Y-m-d');

    // Try to update existing record for today
    $stmt = $conn->prepare("
        INSERT INTO user_login_summary 
            (user_id, login_date, first_login, last_ip_address, last_user_agent, login_count) 
        VALUES (?, ?, NOW(), ?, ?, 1)
        ON DUPLICATE KEY UPDATE 
            login_count = login_count + 1,
            last_ip_address = VALUES(last_ip_address),
            last_user_agent = VALUES(last_user_agent)
    ");

    $stmt->bind_param("isss", $user_id, $today, $ip, $user_agent);
    $stmt->execute();
}

function trackUserLogout($conn, $user_id) {
    $today = date('Y-m-d');
    
    // Update the last logout time
    $stmt = $conn->prepare("
        UPDATE user_login_summary 
        SET last_logout = NOW()
        WHERE user_id = ? AND login_date = ?
    ");

    $stmt->bind_param("is", $user_id, $today);
    $stmt->execute();
}

function trackFailedLogin($conn, $attempted_user) {
    $ip = getClientIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $today = date('Y-m-d');

    // If we can find the user_id from the attempted login
    $stmt = $conn->prepare("
        SELECT id FROM users WHERE email = ? OR username = ?
        UNION
        SELECT id FROM students WHERE email = ?
    ");
    
    $stmt->bind_param("sss", $attempted_user, $attempted_user, $attempted_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        
        // Update failed attempts
        $stmt = $conn->prepare("
            INSERT INTO user_login_summary 
                (user_id, login_date, last_ip_address, last_user_agent, failed_attempts) 
            VALUES (?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                failed_attempts = failed_attempts + 1,
                last_ip_address = VALUES(last_ip_address),
                last_user_agent = VALUES(last_user_agent)
        ");
        
        $stmt->bind_param("isss", $user_id, $today, $ip, $user_agent);
        $stmt->execute();
    }
}
