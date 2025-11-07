<?php
require_once 'security.php';
require_once '../config/database.php';

class Notifications {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // Send notification
    public function send($userId, $type, $message, $link = '') {
        $stmt = $this->conn->prepare("
            INSERT INTO notifications (user_id, type, message, link) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->bind_param("isss", $userId, $type, $message, $link);
        return $stmt->execute();
    }
    
    // Send email notification
    public function sendEmail($to, $subject, $message) {
        // Configure email headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Graduation Pictorial System <noreply@gradpics.com>',
            'Reply-To: support@gradpics.com'
        ];
        
        // HTML email template
        $htmlMessage = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Graduation Pictorial System</h2>
                    </div>
                    <div class='content'>
                        $message
                    </div>
                    <div class='footer'>
                        This is an automated message, please do not reply.
                        <br>© " . date('Y') . " Graduation Pictorial System
                    </div>
                </div>
            </body>
            </html>
        ";
        
        // Send email
        return mail($to, $subject, $htmlMessage, implode("\r\n", $headers));
    }
    
    // Get user's notifications
    public function getForUser($userId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Mark notification as read
    public function markAsRead($notificationId, $userId) {
        $stmt = $this->conn->prepare("
            UPDATE notifications 
            SET read_at = CURRENT_TIMESTAMP 
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->bind_param("ii", $notificationId, $userId);
        return $stmt->execute();
    }
    
    // Get unread count
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND read_at IS NULL
        ");
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
    
    // Send bulk notifications
    public function sendBulk($userIds, $type, $message, $link = '') {
        $this->conn->begin_transaction();
        
        try {
            foreach ($userIds as $userId) {
                $this->send($userId, $type, $message, $link);
            }
            
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }
    
    // Delete old notifications
    public function cleanOldNotifications($days = 30) {
        $stmt = $this->conn->prepare("
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            AND read_at IS NOT NULL
        ");
        
        $stmt->bind_param("i", $days);
        return $stmt->execute();
    }
}

// Example usage:
/*
$notifications = new Notifications($conn);

// Send notification
$notifications->send(
    $userId,
    'appointment_approved',
    'Your appointment has been approved!',
    '/student/appointments.php'
);

// Send email
$notifications->sendEmail(
    'student@example.com',
    'Appointment Approved',
    'Your graduation photo appointment has been approved.'
);

// Get notifications
$userNotifications = $notifications->getForUser($userId);

// Mark as read
$notifications->markAsRead($notificationId, $userId);

// Get unread count
$unreadCount = $notifications->getUnreadCount($userId);
*/
?>
