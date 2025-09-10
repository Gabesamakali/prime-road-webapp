<?php
// Database test script
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "gabes221354271";
$DB_NAME = "road_maintenance";

echo "<h2>Database Connection Test</h2>";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "✅ Connected successfully to database: $DB_NAME<br><br>";

// Check if tables exist
echo "<h3>Table Structure Check</h3>";
$tables = ['road_segments', 'contractors', 'maintenance_assignments'];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
        
        // Show table structure
        $structure = $conn->query("DESCRIBE $table");
        if ($structure) {
            echo "<strong>Structure of $table:</strong><br>";
            echo "<ul>";
            while ($row = $structure->fetch_assoc()) {
                echo "<li>{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}</li>";
            }
            echo "</ul><br>";
        }
        
        // Show sample data
        $sample = $conn->query("SELECT * FROM $table LIMIT 3");
        if ($sample && $sample->num_rows > 0) {
            echo "<strong>Sample data from $table:</strong><br>";
            while ($row = $sample->fetch_assoc()) {
                echo "<pre>" . print_r($row, true) . "</pre>";
            }
        } else {
            echo "⚠️ No data in table '$table'<br>";
        }
        echo "<hr>";
    } else {
        echo "❌ Table '$table' does NOT exist<br>";
    }
}

$conn->close();
?>