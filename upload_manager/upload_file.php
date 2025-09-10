<?php
include 'db.php';

// Check if file was uploaded
if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    $file_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $upload_dir = "uploads/";

    // Create uploads folder if not exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Move file to uploads folder
    if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
        // Get form data
        $town_route = $_POST['town_route'];
        $uploaded_by = $_POST['uploaded_by'];

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO uploads (file_name, town_route, status, uploaded_by) VALUES (?, ?, 'Pending', ?)");
        $stmt->bind_param("sss", $file_name, $town_route, $uploaded_by);
        $stmt->execute();
        $stmt->close();

        header("Location: upload_manager.php?success=1");
        exit;
    } else {
        echo "File upload failed.";
    }
} else {
    echo "No file uploaded.";
}
?>
