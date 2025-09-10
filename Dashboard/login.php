<?php
// login.php - Handle user login
session_start();

// CORS headers for cross-origin requests
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
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
            exit();
        }
        
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        
        // Validate input
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email and password are required']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit();
        }
        
        // Get user from database
        $stmt = $pdo->prepare("SELECT id, email, password, profile_picture, first_name, last_name FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);                                                                                                                             
        
        if (!$user || !password_verify($password, $user['password'])) {
            // Log failed login attempt
            $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
            
            echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
            exit();
        }
        
        // Generate session token
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Store session in database
        $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Log successful login
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success, created_at) VALUES (?, ?, 1, NOW())");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        
        // Set PHP session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'session_token' => $sessionToken,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'profile_picture' => $user['profile_picture']
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in login.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("General error in login.php: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST method allowed']);
}
?>