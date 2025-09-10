<?php





$host = "localhost";
$user = "root"; // change if needed
$pass = "gabes221354271";     // change if needed
$dbname = "road_cleaning";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Filter parameters
$where = [];
if (!empty($_GET['contractor'])) {
    $where[] = "contractor LIKE '%" . $conn->real_escape_string($_GET['contractor']) . "%'";
}
if (!empty($_GET['date_from']) && !empty($_GET['date_to'])) {
    $where[] = "date BETWEEN '" . $conn->real_escape_string($_GET['date_from']) . "' 
                AND '" . $conn->real_escape_string($_GET['date_to']) . "'";
}

$where_sql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

// Fetch records
$sql = "SELECT * FROM maintenance_logs $where_sql ORDER BY date DESC";
$result = $conn->query($sql);





?>
