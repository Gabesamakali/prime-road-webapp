<?php
// db_connection.php - Database connection configuration

// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'road_cleaning',
    'username' => 'root',  // Change this to your database username
    'password' => 'gabes221354271', // Change this to your database password
    'charset' => 'utf8mb4'
];

try {
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];

    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);

} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());

    // Return JSON error response for AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please try again later.'
        ]);
        exit();
    } else {
        // For regular page requests, show error page or redirect
        die('Database connection failed. Please contact the administrator.');
    }
}

// Function to test database connection
function testDatabaseConnection() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>
