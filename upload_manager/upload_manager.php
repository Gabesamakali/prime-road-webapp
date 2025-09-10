<?php
include 'db.php';



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Manager</title>
    <link rel="stylesheet" href="styless.css">
    <!-- Font Awesome for icons -->
    
    <script src="upload_manager.js" defer></script>
</head>
<body>

 
    <div class="container">
        <!-- Sidebar -->
      <?php include 'sidebar.php'; ?>

                <!-- Main Content -->
            <main class="main-content">


                 <!-- User Header -->
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
                
                <!-- Section Header -->
                <div class="section-header">
                    <h1 class="title">Upload Manager</h1>
                    <form action="upload_file.php" method="POST" enctype="multipart/form-data" style="display:inline-block;">
                        <input type="text" name="town_route" placeholder="Town/Route" required>
                        <input type="text" name="uploaded_by" placeholder="Uploaded by" required>
                        <input type="file" name="file" required>
                        <button type="submit" class="upload-btn"><i class="fa fa-plus"></i> Upload New file</button>
                    </form>
                </div>

                <!-- Table Controls -->
                <div class="table-controls">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search...">
                        <i class="fa fa-search"></i>
                    </div>
                    <div class="sort-box">
                        <select id="sortSelect">
                            <option value="">Sort by...</option>
                            <option value="file">File Name</option>
                            <option value="town">Town/Route</option>
                            <option value="status">Status</option>
                            <option value="time">Timestamp</option>
                            <option value="user">Uploaded by</option>
                        </select>
                        <i class="fa fa-sort"></i>
                    </div>
                </div>

                    <!-- Table -->
                <div class="table-container">
                            <table id="uploadTable">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Town/Route</th>
                                        <th>Status</th>
                                        <th>Timestamp</th>
                                        <th>Uploaded by</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $conn->query("SELECT * FROM uploads ORDER BY timestamp DESC");
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>
                                                <td>{$row['file_name']}</td>
                                                <td>{$row['town_route']}</td>
                                                <td class='status " . strtolower($row['status']) . "'>{$row['status']}</td>
                                                <td>" . date("h:i a", strtotime($row['timestamp'])) . "</td>
                                                <td>{$row['uploaded_by']}</td>
                                                <td>
                                                    <button class='action-btn delete' onclick=\"deleteFile({$row['id']})\">Delete</button>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6'>No uploads yet</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                </div>
            </main>
        
    </div>


     <script>
        // Function to fetch user data from the server
        async function fetchUserData() {
            try {
                const response = await fetch('get_user_data.php');
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
