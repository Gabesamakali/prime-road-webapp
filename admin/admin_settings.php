<?php
// admin_settings.php

// ==== DATABASE CONNECTION ====
$servername = "localhost";
$username   = "root";
$password   = "gabes221354271";
$dbname     = "road_maintenance";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    http_response_code(500);
    die("DB connection failed");
}

// ==== FETCH SYSTEM SETTINGS ====
$settings_res = $conn->query("SELECT pci_formula, threshold FROM system_settings WHERE id = 1");
$settings = $settings_res && $settings_res->num_rows > 0 ? $settings_res->fetch_assoc() : ['pci_formula' => '', 'threshold' => 0];

// ==== AJAX SEARCH ENDPOINT ====
if (isset($_GET['action']) && $_GET['action'] === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $like = '%' . $conn->real_escape_string($term) . '%';
    $stmt = $conn->prepare("SELECT id, email, role, status FROM user_management WHERE LOWER(email) LIKE LOWER(?) OR LOWER(role) LIKE LOWER(?) ORDER BY id DESC");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $out[] = $row;
    }
    echo json_encode($out);
    $stmt->close();
    $conn->close();
    exit;
}

// ==== AJAX PERMISSIONS ENDPOINT ====
if (isset($_GET['action']) && $_GET['action'] === 'get_permissions') {
    header('Content-Type: application/json; charset=utf-8');
    $role = isset($_GET['role']) ? trim($_GET['role']) : '';
    $stmt = $conn->prepare("SELECT permission FROM role_permissions WHERE role = ? AND enabled = 1");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $res = $stmt->get_result();
    $permissions = [];
    while ($row = $res->fetch_assoc()) {
        $permissions[] = $row['permission'];
    }
    echo json_encode($permissions);
    $stmt->close();
    $conn->close();
    exit;
}

// ==== ADD USER FORM HANDLER ====
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $email = trim($_POST['email'] ?? '');
    $role  = trim($_POST['role'] ?? '');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠ Invalid email format. Please use a valid email address (e.g., user@example.com).";
    } elseif ($email === '' || $role === '') {
        $message = "⚠ Please fill all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO user_management (email, role, status) VALUES (?, ?, 'active')");
        $stmt->bind_param("ss", $email, $role);
        if ($stmt->execute()) {
            $message = "✅ User added successfully!";
        } else {
            $message = "❌ Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// ==== EDIT USER FORM HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = intval($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠ Invalid email format. Please use a valid email address (e.g., user@example.com).";
    } elseif ($id === 0 || $email === '' || $role === '') {
        $message = "⚠ Please fill all fields.";
    } else {
        $stmt = $conn->prepare("UPDATE user_management SET email = ?, role = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $role, $id);
        if ($stmt->execute()) {
            $message = "✅ User updated successfully!";
        } else {
            $message = "❌ Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}

// ==== DEACTIVATE/ACTIVATE USER HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = intval($_POST['id'] ?? 0);
    $status = $_POST['status'] === 'active' ? 'inactive' : 'active';
    $stmt = $conn->prepare("UPDATE user_management SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    if ($stmt->execute()) {
        $message = "✅ User status updated!";
    } else {
        $message = "❌ Error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ==== SYSTEM SETTINGS HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_system_settings'])) {
    $pci_formula = trim($_POST['pci_formula'] ?? '');
    $threshold = floatval($_POST['threshold'] ?? 0);
    $stmt = $conn->prepare("INSERT INTO system_settings (id, pci_formula, threshold) VALUES (1, ?, ?) ON DUPLICATE KEY UPDATE pci_formula = ?, threshold = ?");
    $stmt->bind_param("sdsd", $pci_formula, $threshold, $pci_formula, $threshold);
    if ($stmt->execute()) {
        $message = "✅ System settings updated!";
    } else {
        $message = "❌ Error: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// ==== UPLOAD BASE MAPS HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_map'])) {
    if (isset($_FILES['shapefile']) && $_FILES['shapefile']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $filename = basename($_FILES['shapefile']['name']);
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['shapefile']['tmp_name'], $target)) {
            $message = "✅ Shapefile uploaded successfully!";
        } else {
            $message = "❌ Failed to upload shapefile.";
        }
    } else {
        $message = "⚠ Please select a valid shapefile.";
    }
}

// ==== ROLE PERMISSIONS HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $role = trim($_POST['role'] ?? '');
    $permissions = $_POST['permissions'] ?? [];
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $stmt->close();
    foreach ($permissions as $perm) {
        $stmt = $conn->prepare("INSERT INTO role_permissions (role, permission, enabled) VALUES (?, ?, 1)");
        $stmt->bind_param("ss", $role, $perm);
        $stmt->execute();
        $stmt->close();
    }
    $message = "✅ Permissions updated for $role!";
}

// ==== AI FEEDBACK HANDLER ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $feedback = trim($_POST['feedback'] ?? '');
    if ($feedback !== '') {
        $stmt = $conn->prepare("INSERT INTO ai_feedback (feedback) VALUES (?)");
        $stmt->bind_param("s", $feedback);
        if ($stmt->execute()) {
            $message = "✅ Feedback submitted!";
        } else {
            $message = "❌ Error: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    } else {
        $message = "⚠ Please enter feedback.";
    }
}

// ==== INITIAL USER FETCH ====
$users_res = $conn->query("SELECT id, email, role, status FROM user_management ORDER BY id DESC");
$initial_users = [];
if ($users_res) {
    while ($r = $users_res->fetch_assoc()) $initial_users[] = $r;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Prime Roads - Admin Settings</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<!-- Custom Styles -->
<link rel="stylesheet" href="admin_settings.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<!-- Main content -->
<div class="main">
  <!-- Top green bar -->
  <div class="topbar" role="region" aria-label="Top search">
    <div class="search-pill" aria-hidden="false">
      <input id="searchInput" type="text" placeholder="Search users..." aria-label="Search users by email or role">
      <img src="gray.png" alt="Search Icon" class="search-icon">
    </div>
    <div class="top-actions" role="toolbar" aria-label="Top actions">
      <div class="icon-wrap" title="Notifications">
        <img id="bellIcon" src="bell.png" alt="Notifications Icon">
        <div id="notifDropdown" class="dropdown-menu" style="display:none;">
          
        </div>
      </div>
      <div class="icon-wrap" title="Account">
        <img id="userIcon" src="user.png" alt="User Icon">
        
      </div>
    </div>
  </div>

  <!-- Page title + Add button -->
  <div class="page-head">
    <h1>Admin Settings</h1>
  </div>

  <!-- Display message -->
  <?php if ($message): ?>
    <div style="padding: 10px 34px; color: #fff; background: <?php echo strpos($message, '✅') === 0 ? '#28a745' : '#dc3545'; ?>;">
      <?php echo $message; ?>
    </div>
  <?php endif; ?>

  <!-- Content grid -->
  <div class="content-grid">
    <!-- Left column -->
    <div style="flex:1; max-width:780px;">
      <div class="card-plain">
        <div class="card-body">
          <table class="ux-table" id="usersTable" aria-describedby="User management table">
            <thead>
              <tr><th>User Management</th><th>Role</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="usersTbody">
              <?php foreach ($initial_users as $u): ?>
                <tr>
                  <td><?php echo htmlspecialchars($u['email']); ?></td>
                  <td><?php echo htmlspecialchars($u['role']); ?></td>
                  <td><?php echo htmlspecialchars($u['status']); ?></td>
                  <td class="action-buttons">
                    <button class="btn btn-custom-edit" onclick="openEditModal(<?php echo $u['id']; ?>, '<?php echo addslashes(htmlspecialchars($u['email'], ENT_QUOTES)); ?>', '<?php echo addslashes(htmlspecialchars($u['role'], ENT_QUOTES)); ?>')" aria-label="Edit user <?php echo htmlspecialchars($u['email']); ?>">
                      <i class="bi bi-pencil-square"></i> Edit
                    </button>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="toggle_status" value="1">
                      <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                      <input type="hidden" name="status" value="<?php echo $u['status']; ?>">
                      <button type="submit" class="btn btn-custom-toggle" aria-label="<?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> user <?php echo htmlspecialchars($u['email']); ?>">
                        <i class="bi <?php echo $u['status'] === 'active' ? 'bi-person-dash' : 'bi-person-check'; ?>"></i>
                        <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <aside class="right-col" aria-label="Right column settings">
      <div class="small-card">
        <button class="add-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">Add User</button>
        <div class="card-head">System Settings</div>
        <div class="body">
          <form method="POST">
            <input type="hidden" name="update_system_settings" value="1">
            <div style="margin-bottom:12px;">
              <label class="form-label">PCI Formula</label>
              <input type="text" name="pci_formula" class="form-control" placeholder="e.g., 100 - 5 * defects" value="<?php echo htmlspecialchars($settings['pci_formula']); ?>">
            </div>
            <div style="margin-bottom:12px;">
              <label class="form-label">Threshold</label>
              <input type="number" step="0.1" name="threshold" class="form-control" value="<?php echo htmlspecialchars($settings['threshold']); ?>">
            </div>
            <button type="submit" class="btn btn-success btn-sm">Save</button>
          </form>
        </div>
      </div>

      <div class="small-card">
        <div class="card-head">Upload Base Maps</div>
        <div class="body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="upload_map" value="1">
            <div style="margin-bottom:12px;">
              <label class="form-label">GIS Shapefile</label>
              <input type="file" name="shapefile" class="form-control" accept=".shp,.dbf,.shx" required>
            </div>
            <button type="submit" class="btn btn-success btn-sm">Upload</button>
          </form>
        </div>
      </div>

      <div class="small-card">
        <div class="card-head">Role Permissions</div>
        <div class="body">
          <form method="POST">
            <input type="hidden" name="update_permissions" value="1">
            <div style="margin-bottom:12px;">
              <label class="form-label">Role</label>
              <select name="role" class="form-select" onchange="loadPermissions(this.value)" required>
                <option value="">Select Role</option>
                <option>Admin</option>
                <option>Engineer</option>
                <option>Viewer</option>
              </select>
            </div>
            <div id="permissions-checkboxes" style="margin-bottom:12px;">
              <!-- Populated by JavaScript -->
            </div>
            <button type="submit" class="btn btn-success btn-sm">Save Permissions</button>
          </form>
        </div>
      </div>

      <div class="small-card">
        <div class="card-head">AI Model Feedback</div>
        <div class="body">
          <form method="POST">
            <input type="hidden" name="submit_feedback" value="1">
            <div style="margin-bottom:12px;">
              <label class="form-label">Feedback</label>
              <textarea name="feedback" class="form-control" rows="3" placeholder="Enter AI model feedback"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-sm">Submit</button>
          </form>
        </div>
      </div>
    </aside>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="add_user" value="1">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="">Select Role</option>
            <option>Admin</option>
            <option>Engineer</option>
            <option>Viewer</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" type="submit">Save User</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="edit_user" value="1">
        <input type="hidden" name="id" id="editUserId">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" id="editUserEmail" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role" id="editUserRole" class="form-select" required>
            <option value="">Select Role</option>
            <option value="Admin">Admin</option>
            <option value="Engineer">Engineer</option>
            <option value="Viewer">Viewer</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" type="submit">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Bootstrap JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Client JS -->
<script>
(function(){
  // Helper to render users into tbody
  function renderUsers(rows) {
    const tbody = document.getElementById('usersTbody');
    tbody.innerHTML = '';
    if (!rows || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#666;padding:18px;">No users found</td></tr>';
      return;
    }
    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${escapeHtml(r.email)}</td>
        <td>${escapeHtml(r.role)}</td>
        <td>${escapeHtml(r.status)}</td>
        <td class="action-buttons">
          <button class="btn btn-custom-edit" onclick="openEditModal(${r.id}, '${escapeHtml(r.email)}', '${escapeHtml(r.role)}')" aria-label="Edit user ${escapeHtml(r.email)}">
            <i class="bi bi-pencil-square"></i> Edit
          </button>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="toggle_status" value="1">
            <input type="hidden" name="id" value="${r.id}">
            <input type="hidden" name="status" value="${r.status}">
            <button type="submit" class="btn btn-custom-toggle" aria-label="${r.status === 'active' ? 'Deactivate' : 'Activate'} user ${escapeHtml(r.email)}">
              <i class="bi ${r.status === 'active' ? 'bi-person-dash' : 'bi-person-check'}"></i>
              ${r.status === 'active' ? 'Deactivate' : 'Activate'}
            </button>
          </form>
        </td>`;
      tbody.appendChild(tr);
    });
  }

  // Escape helper
  function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/[&<>"']/g, function(m) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]; });
  }

  // Open edit user modal
  window.openEditModal = function(id, email, role) {
    const modal = document.getElementById('editUserModal');
    const userIdInput = document.getElementById('editUserId');
    const emailInput = document.getElementById('editUserEmail');
    const roleSelect = document.getElementById('editUserRole');

    userIdInput.value = id;
    emailInput.value = email;
    roleSelect.value = role;

    // Ensure the modal is shown
    new bootstrap.Modal(modal).show();
  }

  // Load permissions for a role
  function loadPermissions(role) {
    if (!role) {
      document.getElementById('permissions-checkboxes').innerHTML = '';
      return;
    }
    fetch(`?action=get_permissions&role=${encodeURIComponent(role)}`)
      .then(r => r.json())
      .then(data => {
        const container = document.getElementById('permissions-checkboxes');
        container.innerHTML = `
          <div><input type="checkbox" name="permissions[]" value="manage_users" ${data.includes('manage_users') ? 'checked' : ''}> Manage Users</div>
          <div><input type="checkbox" name="permissions[]" value="configure_system" ${data.includes('configure_system') ? 'checked' : ''}> Configure System</div>
          <div><input type="checkbox" name="permissions[]" value="view_reports" ${data.includes('view_reports') ? 'checked' : ''}> View Reports</div>
        `;
      })
      .catch(err => console.error(err));
  }

  // Live search (debounced)
  const enter = document.getElementById('searchInput');
  let timeout = null;
  enter.addEventListener('input', function() {
    clearTimeout(timeout);
    const tbody = document.getElementById('usersTbody');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#666;padding:18px;">Loading...</td></tr>';
    timeout = setTimeout(() => {
      const term = enter.value.trim();
      if (term === '') {
        fetch('?action=search&term=')
          .then(r => r.json())
          .then(data => renderUsers(data))
          .catch(err => {
            console.error(err);
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#c62828;padding:18px;">Error loading users</td></tr>';
          });
        return;
      }
      fetch(`?action=search&term=${encodeURIComponent(term)}`)
        .then(r => r.json())
        .then(data => renderUsers(data))
        .catch(err => {
          console.error(err);
          tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#c62828;padding:18px;">Error loading users</td></tr>';
        });
    }, 300);
  });

  // Dropdown hover handling
  const bell = document.getElementById('bellIcon');
  const bellDropdown = document.getElementById('notifDropdown');
  const user = document.getElementById('userIcon');
  const userDropdown = document.getElementById('userDropdown');

  function attachHover(icon, dropdown) {
    if (!icon || !dropdown) return;
    icon.addEventListener('mouseenter', () => dropdown.style.display = 'block');
    dropdown.addEventListener('mouseenter', () => dropdown.style.display = 'block');
    icon.addEventListener('mouseleave', () => setTimeout(() => { if (!dropdown.matches(':hover')) dropdown.style.display = 'none'; }, 100));
    dropdown.addEventListener('mouseleave', () => dropdown.style.display = 'none');
  }

  attachHover(bell, bellDropdown);
  attachHover(user, userDropdown);

  // Close dropdowns on outside click
  document.addEventListener('click', function(e) {
    if (!bell.contains(e.target) && !bellDropdown.contains(e.target)) bellDropdown.style.display = 'none';
    if (!user.contains(e.target) && !userDropdown.contains(e.target)) userDropdown.style.display = 'none';
  });

  // Initial load
  fetch('?action=search&term=')
    .then(r => r.json())
    .then(data => renderUsers(data))
    .catch(err => console.error(err));
})();
</script>
</body>
</html>