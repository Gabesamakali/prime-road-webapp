<?php
require_once 'db.php';
requireAuth();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_road'])) {
        // Add new road segment
        $name = $_POST['name'];
        $length = $_POST['length'];
        $width = $_POST['width'];
        $surface_type = $_POST['surface'];
        $sidewalks = $_POST['sidewalks'];
        $last_scanned = $_POST['last_scanned'];
        
        $stmt = $pdo->prepare("INSERT INTO road_segments (name, length, width, surface_type, sidewalks, last_scanned) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $length, $width, $surface_type, $sidewalks, $last_scanned]);
        
        header("Location: AssetInventory.php?success=Road segment added successfully");
        exit();
    } elseif (isset($_POST['update_road'])) {
        // Update road segment
        $id = $_POST['id'];
        $name = $_POST['name'];
        $length = $_POST['length'];
        $width = $_POST['width'];
        $surface_type = $_POST['surface'];
        $sidewalks = $_POST['sidewalks'];
        $last_scanned = $_POST['last_scanned'];
        
        $stmt = $pdo->prepare("UPDATE road_segments SET name=?, length=?, width=?, surface_type=?, sidewalks=?, last_scanned=? WHERE id=?");
        $stmt->execute([$name, $length, $width, $surface_type, $sidewalks, $last_scanned, $id]);
        
        header("Location: AssetInventory.php?success=Road segment updated successfully");
        exit();
    } elseif (isset($_POST['delete_road'])) {
        // Delete road segment
        $id = $_POST['id'];
        
        $stmt = $pdo->prepare("DELETE FROM road_segments WHERE id=?");
        $stmt->execute([$id]);
        
        header("Location: AssetInventory.php?success=Road segment deleted successfully");
        exit();
    }
}

// Get all road segments
$stmt = $pdo->query("SELECT * FROM road_segments ORDER BY name");
$roads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search
$search = isset($_GET['search']) ? $_GET['search'] : '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM road_segments WHERE name LIKE ? OR surface_type LIKE ? OR sidewalks LIKE ? ORDER BY name");
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $roads = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pagination
$itemsPerPage = 5;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

$totalItems = count($roads);
$totalPages = ceil($totalItems / $itemsPerPage);

// Get current page items
$currentItems = array_slice($roads, $offset, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Road Asset Inventory | Prime Roads</title>
  <style>
    /* All the CSS from the original AssetInventory.html */
    :root {
      --black: #101613;
      --dark-green: #184d2e;
      --green: #178586;
      --sidebar-active: #1c4025;
      --red: #c82333;
      --dark-red: #b01e2b;
      --white: #ffffff;
      --gray: #ebeff4;
      --dark-gray: #2a323d;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Segoe UI", sans-serif;
    }

    body {
      display: flex;
      height: 100vh;
      background: var(--gray);
      overflow: hidden;
    }


    /* Main Content */
    .main { flex: 1; display: flex; flex-direction: column; }

    /* Transparent Topbar */
    .topbar {
     
      padding:12px 20px; 
      display:flex; 
      align-items:center; 
      justify-content:space-between; 
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .search { flex: 1; display: flex; align-items: center; max-width: 400px; }
    .search input {
      width: 100%; 
      padding: 8px 12px;
      border: none; 
      border-radius: 20px; 
      outline: none; 
      font-size: 14px;
      background: rgba(255, 255, 255, 0.9);
    }
    .icons { display: flex; align-items: center; gap: 15px; margin-left: 20px; }
    .icons i { font-size: 20px; color: var(--white); cursor: pointer; }

/* Content with background */
.content {
    flex: 1;
    display: flex;
    flex-direction: column;
    padding: 20px;

    /* Background image */
    background-image: url('background.jpg'); /* replace with your image path */
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

/* White transparent overlay */
.content::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(255, 255, 255, 0.6); /* white with 60% opacity */
    z-index: 0;
}

/* Make content appear above overlay */
.content > * {
    position: relative;
    z-index: 1;
}
    .buttons { 
      display: flex; 
      gap: 15px; 
      margin-bottom: 20px; 
      flex-wrap: wrap;
    }
    .upload-btn {
      background: transparent; 
      border: 1px solid var(--dark-gray);
      padding: 10px 15px; 
      border-radius: 8px; 
      cursor: pointer;
      transition: all 0.3s;
    }
    .upload-btn:hover {
      background: var(--dark-green);
      color: white;
    }
    .add-btn {
      background: var(--red); 
      border: none;
      padding: 10px 20px; 
      border-radius: 8px;
      color: var(--white); 
      font-weight: bold; 
      cursor: pointer;
      transition: all 0.3s;
    }
    .add-btn:hover { 
      background: var(--dark-red); 
      transform: translateY(-2px);
    }

    /* Table */
    .table-container {
      background: var(--white);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
    }
    thead { 
      background: var(--red); 
      color: var(--white); 
    }
    thead th { 
      text-align: left; 
      padding: 12px 15px; 
      font-size: 14px; 
    }
    tbody td { 
      padding: 12px 15px; 
      border-bottom: 1px solid #ddd; 
      font-size: 14px; 
    }
    tbody tr:hover { 
      background: #f5f9ff; 
    }
    .view-more {
      text-align: right; 
      padding: 10px 15px;
      font-weight: bold; 
      color: var(--red); 
      cursor: pointer;
    }
    .view-more:hover { 
      text-decoration: underline; 
    }

    /* Modal */
    .modal {
      position: fixed; 
      inset: 0; 
      background: rgba(0,0,0,.5);
      display: none; 
      align-items: center; 
      justify-content: center; 
      z-index: 1000;
    }
    .modal.open { 
      display: flex; 
    }
    .modal-content {
      background: #fff; 
      padding: 25px; 
      border-radius: 12px;
      width: 450px; 
      max-width: 95%;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .modal-content h3 { 
      margin-bottom: 20px; 
      color: var(--dark-gray);
      font-size: 20px;
    }
    .modal-content label { 
      display: block; 
      margin-top: 15px; 
      font-size: 14px; 
      font-weight: 500;
      color: var(--dark-gray);
    }
    .modal-content input, .modal-content select {
      width: 100%; 
      padding: 10px; 
      margin-top: 5px;
      border: 1px solid #ddd; 
      border-radius: 6px;
      font-size: 14px;
      transition: border 0.3s;
    }
    .modal-content input:focus, .modal-content select:focus {
      border-color: var(--green);
      outline: none;
    }
    .modal-actions { 
      margin-top: 20px; 
      display: flex; 
      justify-content: flex-end; 
      gap: 10px; 
    }
    .modal-actions button { 
      padding: 10px 18px; 
      border: none; 
      border-radius: 6px; 
      cursor: pointer; 
      font-weight: 600;
      transition: all 0.3s;
    }
    .save-btn { 
      background: var(--red); 
      color: #fff; 
    }
    .save-btn:hover {
      background: var(--dark-red);
    }
    .cancel-btn { 
      background: #e0e0e0; 
      color: #555;
    }
    .cancel-btn:hover {
      background: #d0d0d0;
    }

    .actions {
      display: flex;
      gap: 10px;
    }
    .actions button {
      background: none; 
      border: none; 
      cursor: pointer; 
      font-size: 16px;
      padding: 5px;
      border-radius: 4px;
      transition: all 0.3s;
    }
    .actions button.edit { 
      color: var(--green); 
    }
    .actions button.edit:hover {
      background: rgba(23, 133, 134, 0.1);
    }
    .actions button.delete { 
      color: var(--red); 
    }
    .actions button.delete:hover {
      background: rgba(200, 35, 51, 0.1);
    }
    
    /* Pagination */
    .pagination {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 8px;
    }
    .pagination button {
      padding: 8px 15px;
      background: var(--white);
      border: 1px solid #ddd;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.3s;
    }
    .pagination button:hover {
      background: #f0f0f0;
    }
    .pagination button.active {
      background: var(--red);
      color: white;
      border-color: var(--red);
    }
    
    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #777;
    }
    .empty-state i {
      font-size: 48px;
      margin-bottom: 15px;
      color: #ccc;
    }
    .empty-state p {
      margin-bottom: 20px;
    }
    
    /* Toast notification */
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--dark-green);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 10px;
      z-index: 1001;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s;
    }
    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
    
    /* Success message */
    .success-message {
      background: var(--dark-green);
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 20px;
    }

    
.section-divider {
    border: none;
    height: 4px;
    background-color: orange;
    margin: 0 0 15px 0;
    z-index: 100;
}



.header-right {
    display: flex;
    align-items: center;
    gap: 20px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    
}

.user-details {
    text-align: right;
}

.user-name {
    font-weight: 600;
    font-size: 14px;
    color: black;
}

.user-role {
    font-size: 12px;
    opacity: 0.8;
    color: black;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #c53030;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.title {
    color: #00b33c;
    font-size: 24px;
    font-weight: bold;
}

  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- Main -->
  <div class="main">
    <!-- Topbar -->
    <!-- Topbar -->
      <div class="topbar">
        <div class="search">
          <input type="text" id="searchInput" placeholder="Search defects...">
        </div>
        <div class="header-right">
                  
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

      <!-- <div style="flex:1; padding:20px;"> -->
        <hr class="section-divider">
    

    <!-- Content -->
    <div class="content">
      <h1 class="title">Road Asset Inventory</h1>
      
      
      
      
      <div class="buttons">
        <button class="upload-btn" id="uploadBtn"><i class="fa fa-upload"></i> Upload asset file</button>
        <button class="add-btn" id="addBtn"><i class="fa fa-plus"></i> Add Road Segment</button>
        <a href="export.php?type=csv" class="upload-btn"><i class="fa fa-download"></i> Export Data</a>
        <a href="AssetInventory.php" class="upload-btn"><i class="fa fa-refresh"></i> Refresh</a>
      </div>

      <div class="table-container">
        <table id="roadTable">
          <thead>
            <tr>
              <th>NAME</th>
              <th>LENGTH</th>
              <th>WIDTH</th>
              <th>SURFACE TYPE</th>
              <th>SIDEWALKS</th>
              <th>LAST SCANNED</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody id="roadTableBody">
            <?php if (count($currentItems) > 0): ?>
              <?php foreach ($currentItems as $road): ?>
                <tr>
                  <td><?php echo htmlspecialchars($road['name']); ?></td>
                  <td><?php echo htmlspecialchars($road['length']); ?> km</td>
                  <td><?php echo htmlspecialchars($road['width']); ?> m</td>
                  <td><?php echo htmlspecialchars($road['surface_type']); ?></td>
                  <td><?php echo htmlspecialchars($road['sidewalks']); ?></td>
                  <td><?php echo date('M j, Y', strtotime($road['last_scanned'])); ?></td>
                  <td class="actions">
                    <a href="RoadSegment.php?id=<?php echo $road['id']; ?>" class="view" title="View details"><i class="fa fa-eye"></i></a>
                    <button class="edit" onclick="editRoad(<?php echo $road['id']; ?>)" title="Edit"><i class="fa fa-edit"></i></button>
                    <form method="POST" action="" style="display: inline;">
                      <input type="hidden" name="id" value="<?php echo $road['id']; ?>">
                      <button type="submit" name="delete_road" class="delete" title="Delete" onclick="return confirm('Are you sure you want to delete this road segment?')"><i class="fa fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 20px;">
                  No road assets found. Add your first road segment to get started.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
          <div class="pagination">
            <?php if ($currentPage > 1): ?>
              <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="<?php echo $i == $currentPage ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            
            <?php if ($currentPage < $totalPages): ?>
              <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next &raquo;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Add/Edit Modal -->
  <div class="modal" id="roadModal">
    <div class="modal-content">
      <h3 id="modalTitle">Add Road Segment</h3>
      <form method="POST" action="" id="roadForm">
        <input type="hidden" name="id" id="roadId">
        <input type="hidden" name="update_road" id="updateFlag" value="0">
        
        <label>Road Name</label>
        <input type="text" name="name" id="name" required placeholder="Enter road name">
        
        <div style="display: flex; gap: 15px;">
          <div style="flex: 1;">
            <label>Length (km)</label>
            <input type="number" name="length" id="length" step="0.01" required placeholder="0.00" min="0">
          </div>
          <div style="flex: 1;">
            <label>Width (m)</label>
            <input type="number" name="width" id="width" step="0.1" required placeholder="0.0" min="0">
          </div>
        </div>
        
        <label>Surface Type</label>
        <select name="surface" id="surface" required>
          <option value="">Select surface type</option>
          <option value="Asphalt">Asphalt</option>
          <option value="Concrete">Concrete</option>
          <option value="Gravel">Gravel</option>
          <option value="Other">Other</option>
        </select>
        
        <label>Sidewalks</label>
        <select name="sidewalks" id="sidewalks" required>
          <option value="">Select option</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
          <option value="Partial">Partial</option>
        </select>
        
        <label>Last Scanned Date</label>
        <input type="date" name="last_scanned" id="lastScanned" required>
        
        <div class="modal-actions">
          <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
          <button type="submit" class="save-btn" name="add_road" id="saveBtn">Save Road</button>
        </div>
      </form>
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


    // JavaScript functionality
    document.addEventListener('DOMContentLoaded', function() {
      const modal = document.getElementById('roadModal');
      const addBtn = document.getElementById('addBtn');
      const cancelBtn = document.getElementById('cancelBtn');
      const form = document.getElementById('roadForm');
      const modalTitle = document.getElementById('modalTitle');
      const roadId = document.getElementById('roadId');
      const updateFlag = document.getElementById('updateFlag');
      const saveBtn = document.getElementById('saveBtn');
      
      // Open modal for Add
      addBtn.addEventListener('click', function() {
        modalTitle.textContent = 'Add Road Segment';
        form.reset();
        roadId.value = '';
        updateFlag.value = '0';
        saveBtn.name = 'add_road';
        modal.classList.add('open');
      });
      
      // Close modal
      cancelBtn.addEventListener('click', function() {
        modal.classList.remove('open');
      });
      
      // Handle search input
      const searchInput = document.getElementById('searchInput');
      searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
          this.form.submit();
        }
      });
    });
    
    // Edit road function
    function editRoad(id) {
      // In a real application, this would fetch data via AJAX
      // For simplicity, we'll redirect to an edit page
      window.location.href = 'edit_road.php?id=' + id;
    }
  </script>

</body>
</html>