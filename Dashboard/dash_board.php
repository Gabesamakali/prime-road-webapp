<?php
include 'db_connection.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prime Roads Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">

</head>
<body>
    <div class="container">
        <!-- Sidebar -->
       <?php include 'sidebar.php'; ?>

      

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="search-container">
                    <svg class="search-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <input type="text" class="search-input" placeholder="Search...">
                </div>
                
                <div class="header-right">
                    <div class="header-icon">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                        </svg>
                    </div>
                    
                    <div class="user-info">
                        <div class="user-details">
                            <div class="user-name" id="userName">Loading...</div>
                            <div class="user-role" id="userRole">Administrator</div>
                        </div>
                        <div class="user-avatar" id="userAvatar">
                            <!-- Profile picture will be inserted here -->
                        </div>
                    </div>
                   
                </div>
               
            </div>

            <div style="flex:1; padding:20px;">
            <hr class="section-divider">

            <div class="dashboard-content">
                <h1 class="dashboard-title">Dashboard</h1>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon green-icon">🧩</div>
                        <div class="stat-info">
                            <h3>Total Road Segments</h3>
                            <div class="stat-value green-text" id="totalSegments">Loading...</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon red-icon">📊</div>
                        <div class="stat-info">
                            <h3>% in Poor Condition</h3>
                            <div class="stat-value red-text" id="poorCondition">Loading...</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon blue-icon">📈</div>
                        <div class="stat-info">
                            <h3>Active Maintenance Projects</h3>
                            <div class="stat-value blue-text" id="activeProjects">Loading...</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon gray-icon">🕐</div>
                        <div class="stat-info">
                            <h3>Last Update</h3>
                            <div class="stat-value gray-text" id="lastUpdate">Loading...</div>
                        </div>
                    </div>
                </div>

                <div class="bottom-section">
                    <div class="alert-card">
                        <div class="alert-content">
                            <div class="alert-icon">⚠️</div>
                            <div class="alert-text">Road Segments Due for Inspection</div>
                        </div>
                        <div id="inspectionList" class="loading">Loading inspection data...</div>
                    </div>

                    <div class="quick-links-card">
                        <div class="quick-links-header">
                            <div class="quick-links-title">Quick Links</div>
                            <div class="arrow-icon">›</div>
                        </div>
                        <div class="quick-link-item">
                            <div class="quick-link-text">Reports Overview</div>
                            <div class="arrow-icon">›</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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

        // Function to fetch dashboard data from the server
        async function fetchDashboardData() {
            try {
                const response = await fetch('dashboard.php');
                const result = await response.json();
                
                if (result.success) {
                    // Update dashboard with real data
                    document.getElementById('totalSegments').textContent = result.data.total_segments || '0';
                    document.getElementById('poorCondition').textContent = (result.data.poor_percentage || '0') + '%';
                    document.getElementById('activeProjects').textContent = result.data.active_projects || '0';
                    document.getElementById('lastUpdate').textContent = result.data.last_update || 'N/A';
                    
                    // Update inspection list
                    const inspectionList = document.getElementById('inspectionList');
                    if (result.data.alerts && result.data.alerts.length > 0) {
                        let inspectionHTML = '';
                        result.data.alerts.forEach(alert => {
                            inspectionHTML += `<div style="padding: 10px; border-bottom: 1px solid #eee; color: #2a2a2a;">${alert.message}</div>`;
                        });
                        inspectionList.innerHTML = inspectionHTML;
                    } else {
                        inspectionList.innerHTML = '<div style="padding: 10px; color: #2a2a2a;">No road segments due for inspection</div>';
                    }
                } else {
                    console.error('Failed to fetch dashboard data:', result.message);
                    // Use sample data as fallback
                    loadSampleData();
                }
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                // Use sample data as fallback
                loadSampleData();
            }
        }

        // Function to load sample data when server is not available
        function loadSampleData() {
            document.getElementById('totalSegments').textContent = '2350';
            document.getElementById('poorCondition').textContent = '20.5%';
            document.getElementById('activeProjects').textContent = '8';
            document.getElementById('lastUpdate').textContent = 'April 24, 2025';
            
            // Sample inspection data
            const inspectionList = document.getElementById('inspectionList');
            const sampleInspections = [
                'Main Street Section A',
                'Independence Avenue',
                'Hosea Kutako Drive',
                'Sam Nujoma Drive',
                'Nelson Mandela Avenue'
            ];
            
            let inspectionHTML = '';
            sampleInspections.forEach(item => {
                inspectionHTML += `<div style="padding: 10px; border-bottom: 1px solid #eee; color: #2a2a2a;">${item} needs inspection</div>`;
            });
            inspectionList.innerHTML = inspectionHTML;
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
            window.location.href = 'login.html';
        }

        // Load data when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetchUserData();
            fetchDashboardData();
        });
    </script>
</body>
</html>