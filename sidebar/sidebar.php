<!-- sidebar.php -->

<?php
include '../upload_manager/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="sidebar.css">
<body>




    <div class="container">

        <!-- Main Content -->
        <main class="main-contents">
            <!-- User Header -->
                     
            <div class="user-header">
                <!-- User Info (stacked vertically) -->
                <div class="user-info">
                    <div class="user-name" id="userName">Loading...</div>
                    <div class="user-role" id="userRole">Administrator</div>
                </div>

                <!-- User Avatar -->
                <div class="user-avatar" id="userAvatar">
                    
                </div>
            </div>

            <hr class="section-divider">


        </main>

        <aside class="sidebar">
            

            <div class="logo">
                <img src="logo11.png" alt="Logo">
            </div>

            

            <ul class="menu">
                <li>
                    <a href="../Dashboard/dash_board.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dash_board.php' ? 'active' : '' ?>">
                        <i class="fa fa-home"></i> Dashboard
                    </a>
                </li>

                <li>
                    <a href="gis_map.php" class="<?= basename($_SERVER['PHP_SELF']) == 'gis_map.php' ? 'active' : '' ?>">
                        <i class="fa-regular fa-map"></i> Gis Road Condition Map
                    </a>
                </li>
                <li>
                    <a href="../upload_manager/image_collection.php" class="<?= basename($_SERVER['PHP_SELF']) == 'image_collection.php' ? 'active' : '' ?>">
                        <i class="fa fa-database"></i> Mobile Data Collection
                    </a>
                </li>
                <li>
                    <a href="../upload_manager/upload_manager.php" class="<?= basename($_SERVER['PHP_SELF']) == 'upload_manager.php' ? 'active' : '' ?>">
                        <i class="fa fa-wrench"></i> Upload Manager
                    </a>
                </li>
                <li>
                    <a href="defect_review.php" class="<?= basename($_SERVER['PHP_SELF']) == 'defect_review.php' ? 'active' : '' ?>">
                        <i class="fa fa-exclamation-triangle"></i> Defect Detection Review
                    </a>
                </li>
                <li>
                    <a href="road_segment.php" class="<?= basename($_SERVER['PHP_SELF']) == 'road_segment.php' ? 'active' : '' ?>">
                        <i class="fa fa-scissors"></i> Road Segment
                    </a>
                </li>
                <li>
                    <a href="maintenance_planning.php" class="<?= basename($_SERVER['PHP_SELF']) == 'maintenance_planning.php' ? 'active' : '' ?>">
                        <i class="fa fa-toolbox"></i> Maintenance Planning
                    </a>
                </li>
                <li>
                    <a href="../upload_manager/maintenance_log.php" class="<?= basename($_SERVER['PHP_SELF']) == 'maintenance_log.php' ? 'active' : '' ?>">
                        <i class="fa fa-font-awesome"></i> Maintenance Log
                    </a>
                </li>
                <li>
                    <a href="reports_center.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports_center.php' ? 'active' : '' ?>">
                        <i class="fa-regular fa-file"></i> Reports Center
                    </a>
                </li>
                <li>
                    <a href="../gis/Asset.php" class="<?= basename($_SERVER['PHP_SELF']) == 'Asset.php' ? 'active' : '' ?>">
                        <i class="fa fa-warehouse"></i> Road Asset Inventory
                    </a>
                </li>
            </ul>

            <button class="logout" onclick="window.location.href='../Dashboard/login.html'">
                <i class="fa fa-sign-out-alt"></i> LOGOUT
            </button>
        </aside>

        
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
