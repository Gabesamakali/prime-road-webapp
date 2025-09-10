<?php
// forgot_password.php - Handle password reset requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit();
        }
        
        $email = trim($input['email'] ?? '');
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }
        
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Don't reveal if email exists for security, but return success
            echo json_encode([
                'success' => true, 
                'message' => 'If this email is registered, a reset link has been sent'
            ]);
            exit();
        }
        
        // Generate secure reset token
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store or update reset token in database
        $stmt = $pdo->prepare("
            INSERT INTO password_resets (user_id, reset_token, expires_at) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            reset_token = VALUES(reset_token), 
            expires_at = VALUES(expires_at), 
            created_at = NOW()
        ");
        $stmt->execute([$user['id'], $resetToken, $expiresAt]);
        
        // In production, you would send an email here
        // Example email sending code (uncomment and configure):
        /*
        $resetLink = "https://yourdomain.com/reset_password.html?token=" . $resetToken;
        $subject = "Password Reset Request - Prime Roads";
        $message = "Hello " . $user['first_name'] . ",\n\n";
        $message .= "You requested a password reset. Click the link below to reset your password:\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you didn't request this reset, please ignore this email.\n\n";
        $message .= "Best regards,\nPrime Roads Team";
        
        $headers = "From: noreply@primeroads.com\r\n";
        $headers .= "Reply-To: support@primeroads.com\r\n";
        
        mail($email, $subject, $message, $headers);
        */
        
        // For development/testing, return the token (REMOVE IN PRODUCTION)
        $response = [
            'success' => true,
            'message' => 'Reset link has been sent to your email'
        ];
        
        // Only include token in development mode
        if (defined('DEVELOPMENT_MODE') || $_SERVER['SERVER_NAME'] === 'localhost') {
            $response['reset_token'] = $resetToken;
        }
        
        echo json_encode($response);
        
    } catch (PDOException $e) {
        error_log("Database error in forgot_password.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred'
        ]);
    } catch (Exception $e) {
        error_log("General error in forgot_password.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to process reset request'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Only POST method allowed'
    ]);
}
?>