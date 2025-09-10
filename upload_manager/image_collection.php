<?php
include 'db.php';

// Fetch uploaded records from database
$sql = "SELECT * FROM mobile_uploads ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mobile Data Collection</title>
  <link rel="stylesheet" href="collection.css">
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

                   

            <div style="flex:1; padding:20px;">
                <hr class="section-divider">

            <div class="section-header">
                <h1 class="title"> Mobile Data Collection</h1>



            </div>
            

            <!-- Map Placeholder -->
            <div class="map">
                    <iframe 
                        src="https://maps.google.com/maps?q=Windhoek&t=&z=13&ie=UTF8&iwloc=&output=embed" 
                        
                        width="100%" 
                        height="250" 
                        style="border-radius:10px;" 
                        allowfullscreen>
                    </iframe>
            </div>
            

            <!-- Record Buttons -->
            <div class="record-buttons">
                <button class="record-btn start"><i class="fa fa-play"></i> Start</button>
                <button class="record-btn stop"><i class="fa fa-stop"></i> Stop</button>
            </div>

            <!-- Upload History -->
            <div class="upload-history">
                <h3><i class="fa fa-history"></i> Upload History</h3>

                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="record">
                        <span>
                        <?php
                            $fileType = strtolower(pathinfo($row['file_path'], PATHINFO_EXTENSION));
                            if (in_array($fileType, ['jpg','jpeg','png','gif'])) {
                            echo '<i class="fa fa-image"></i>';
                            } elseif ($fileType === 'pdf') {
                            echo '<i class="fa fa-file-pdf"></i>';
                            } else {
                            echo '<i class="fa fa-file"></i>';
                            }
                        ?>
                        <?= htmlspecialchars($row['file_name']); ?>
                        </span>
                        <small><?= date("d M Y, H:i", strtotime($row['created_at'])); ?></small>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No uploads yet.</p>
                <?php endif; ?>
            </div>
                <!-- Sync Button -->
                <button class="sync-btn"><i class="fa fa-sync"></i> Sync Now</button>
            </div>
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
