<?php
include 'db.php';

// Build filter query
$where = [];
$params = [];

if (!empty($_GET['contractor'])) {
    $where[] = "contractor LIKE ?";
    $params[] = "%" . $_GET['contractor'] . "%";
}
if (!empty($_GET['date_from'])) {
    $where[] = "date >= ?";
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = "date <= ?";
    $params[] = $_GET['date_to'];
}

$sql = "SELECT * FROM maintenance_log";
if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

// Prepare statement and check for errors
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL prepare failed: " . $conn->error . " | Query: " . $sql);
}

// Bind parameters if any
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance History & Contractor Log</title>
    <link rel="stylesheet" href="maintenance_log.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'sidebar.php'; ?>

    <div class="content">

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
            <h1 class="title">Maintenance History & Contractor Log</h1>
            <a href="add_maintenance.php"  class="btn-add"><i class="fa fa-plus"></i> Add Record</a>
            
            
        </div>

        <!-- Filter Form -->
        <form method="GET" class="filter-bar">
            <input type="text" name="contractor" placeholder="Contractor Name" value="<?= htmlspecialchars($_GET['contractor'] ?? '') ?>">
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
            <button type="submit">Filter</button>
            <a href="maintenance_log.php" class="reset-btn">Reset</a>
        </form>

        <!-- Table -->
        <!-- Table -->
        <table class="log-table">
            <thead>
                <tr>
                    <th>Road Segment</th>
                    <th>Work Done</th>
                    <th>Contractor</th>
                    <th>Cost (N$)</th>
                    <th>Date</th>
                    <th>Notes</th>
                    <th>Proof</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['road_segment']) ?></td>
                        <td><?= htmlspecialchars($row['work_done']) ?></td>
                        <td><?= htmlspecialchars($row['contractor']) ?></td>
                        <td><?= number_format($row['cost'], 2) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['notes']) ?></td>
                        <td>
                            <?php if (!empty($row['proof'])): ?>
                                <?php
                                $ext = strtolower(pathinfo($row['proof'], PATHINFO_EXTENSION));
                                $icon = in_array($ext, ['jpg','jpeg','png','gif']) ? '🖼️' : ( $ext === 'pdf' ? '📄' : '📁' );
                                ?>
                                <a href="uploads/<?= htmlspecialchars($row['proof']) ?>" target="_blank"><?= $icon ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>


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

<?php
$stmt->close();
$conn->close();
?>
