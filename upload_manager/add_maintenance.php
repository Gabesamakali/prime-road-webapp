<?php
include 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $road_segment = $_POST['road_segment'] ?? '';
    $work_done = $_POST['work_done'] ?? '';
    $contractor = $_POST['contractor'] ?? '';
    $cost = $_POST['cost'] ?? 0;
    $date = $_POST['date'] ?? '';
    $notes = $_POST['notes'] ?? '';

    $proofFileName = '';

    // Handle file upload
    if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['proof']['tmp_name'];
        $fileName = $_FILES['proof']['name'];
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $allowedExt = ['jpg','jpeg','png','gif','pdf'];

        if (in_array(strtolower($fileExt), $allowedExt)) {
            $proofFileName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $fileName);
            $destPath = 'uploads/' . $proofFileName;
            if (!move_uploaded_file($fileTmpPath, $destPath)) {
                $error = 'Error moving uploaded file.';
            }
        } else {
            $error = 'Invalid file type. Allowed: jpg, jpeg, png, gif, pdf.';
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("INSERT INTO maintenance_log (road_segment, work_done, contractor, cost, date, notes, proof) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssisss", $road_segment, $work_done, $contractor, $cost, $date, $notes, $proofFileName);
        if ($stmt->execute()) {
            $success = "Maintenance log added successfully.";
        } else {
            $error = "Error inserting record: " . $stmt->error;
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Maintenance Log</title>
    <link rel="stylesheet" href="maintenance_log.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="user-header">
                <!-- User Info (stacked vertically) -->
                <div class="user-info">
                    <div class="user-name" id="userName">Loading...</div>
                    <div class="user-role" id="userRole">Administrator</div>
                </div>

                <!-- User Avatar -->
                <div class="user-avatar" id="userAvatar">
                    <!-- Optional profile picture -->
                    <!-- Example: <img src="path/to/avatar.jpg" alt="User Avatar"> -->
                     <!-- Fallback initials if no image -->
                </div>
            </div>
        <hr class="section-divider">

        <div class="section-header">
            <h1 class="title">Add Maintenance Log</h1>
            <a href="maintenance_log.php" class="btn-add"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <?php if ($success): ?>
            <p class="success-msg"><?= $success ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error-msg"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="filter-bar">
            <input type="text" name="road_segment" placeholder="Road Segment" required>
            <input type="text" name="work_done" placeholder="Work Done" required>
            <input type="text" name="contractor" placeholder="Contractor" required>
            <input type="number" step="0.01" name="cost" placeholder="Cost (N$)" required>
            <input type="date" name="date" required>
            <input type="text" name="notes" placeholder="Notes">
            <input type="file" name="proof" accept=".jpg,.jpeg,.png,.gif,.pdf">
            <button type="submit">Add Log</button>
        </form>
    </div>
</div>


    <script>
        // Function to fetch user data from the server
        async function fetchUserData() {
            try {
                const response = await fetch('../Dashboard/get_user_data.php');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('userName').textContent = result.user.first_name + ' ' + result.user.last_name;
                    
                    // Set profile picture
                    const userAvatar = document.getElementById('userAvatar');
                    if (result.user.profile_picture) {
                        userAvatar.innerHTML = `<img src="${result.user.profile_picture}" alt="Profile">`;
                    } else {
                        // Use initials if no profile picture
                        const initials = result.user.first_name.charAt(0) + result.user.last_name.charAt(0);
                        userAvatar.textContent = initials;
                    }
                } else {
                    console.error('Failed to fetch user data:', result.message);
                    // Set default values if user data fetch fails
                    document.getElementById('userName').textContent = 'Admin User';
                    document.getElementById('userAvatar').textContent = 'AU';
                }
            } catch (error) {
                console.error('Error fetching user data:', error);
                // Set default values if user data fetch fails
                document.getElementById('userName').textContent = 'Admin User';
                document.getElementById('userAvatar').textContent = 'AU';
            }
        }

        // Function to handle logout
        function logout() {
            // Clear session data
            localStorage.removeItem('sessionToken');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userId');
            sessionStorage.removeItem('sessionToken');
            sessionStorage.removeItem('userEmail');
            sessionStorage.removeItem('userId');
            
            // Redirect to login page
            window.location.href = '../Dashboard/login.html';
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchUserData();
        });
    </script>

</body>
</html>

<?php $conn->close(); ?>
