<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Get file name to delete from folder
    $res = $conn->query("SELECT file_name FROM uploads WHERE id = $id");
    if ($res->num_rows > 0) {
        $file = $res->fetch_assoc()['file_name'];
        $file_path = "uploads/" . $file;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $conn->query("DELETE FROM uploads WHERE id = $id");
}
header("Location: upload_manager.php");
exit;
?>
