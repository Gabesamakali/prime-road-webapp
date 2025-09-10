<?php
// admin_login.php - Handle admin login
session_start();

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
        $adminUsername = $_POST['adminUsername'] ?? '';
        $adminPassword = $_POST['adminPassword'] ?? '';
        $adminCode = $_POST['adminCode'] ?? '';
        
        // Validate input
        if (empty($adminUsername) || empty($adminPassword) || empty($adminCode)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit();
        }
        
        // Check admin credentials from database
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, security_code, role 
            FROM admin_users 
            WHERE username = ? AND is_active = 1
        ");
        $stmt->execute([$adminUsername]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $loginSuccess = false;
        
        if ($admin && password_verify($adminPassword, $admin['password_hash']) && $adminCode === $admin['security_code']) {
            $loginSuccess = true;
            $adminId = $admin['id'];
        } else {
            // Fallback to hardcoded credentials for initial setup
            $validCredentials = [
                'username' => 'admin',
                'password' => 'primeroads2024',
                'security_code' => '123456'
            ];
            
            if ($adminUsername === $validCredentials['username'] && 
                $adminPassword === $validCredentials['password'] && 
                $adminCode === $validCredentials['security_code']) {
                $loginSuccess = true;
                $adminId = null; // No database admin record
            }
        }
        
        if ($loginSuccess) {
            // Log successful admin login
            $stmt = $pdo->prepare("
                INSERT INTO admin_login_logs (username, ip_address, user_agent, login_time, status) 
                VALUES (?, ?, ?, NOW(), 'success')
            ");
            $stmt->execute([
                $adminUsername,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Update last login if admin exists in database
            if ($adminId) {
                $stmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $stmt->execute([$adminId]);
            }
            
            // Generate session token
            $sessionToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+8 hours')); // Shorter session for admin
            
            // Store admin session
            $stmt = $pdo->prepare("
                INSERT INTO admin_sessions (username, session_token, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                session_token = VALUES(session_token), 
                expires_at = VALUES(expires_at), 
                created_at = NOW()
            ");
            $stmt->execute([$adminUsername, $sessionToken, $expiresAt]);
            
            // Set PHP session
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $adminUsername;
            $_SESSION['login_time'] = time();
            
            echo json_encode([
                'success' => true,
                'message' => 'Admin login successful',
                'session_token' => $sessionToken,
                'admin' => [
                    'username' => $adminUsername,
                    'role' => $admin['role'] ?? 'admin'
                ]
            ]);
        } else {
            // Log failed login attempt
            $stmt = $pdo->prepare("
                INSERT INTO admin_login_logs (username, ip_address, user_agent, login_time, status) 
                VALUES (?, ?, ?, NOW(), 'failed')
            ");
            $stmt->execute([
                $adminUsername,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            echo json_encode([
                'success' => false,
                'message' => 'Invalid admin credentials'
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Database error in admin_login.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred'
        ]);
    } catch (Exception $e) {
        error_log("General error in admin_login.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Admin login failed'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Only POST method allowed'
    ]);
}
?>