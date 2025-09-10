<?php
$servername = "localhost"; 
$username   = "root";       
$password   = "";           
$dbname     = "prime_roads"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode([]));
}

$term = isset($_GET['term']) ? '%' . $_GET['term'] . '%' : '';

$stmt = $conn->prepare("SELECT email, role FROM user_management WHERE email LIKE ? OR role LIKE ? ORDER BY id DESC");
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
$stmt->close();
$conn->close();
?>
