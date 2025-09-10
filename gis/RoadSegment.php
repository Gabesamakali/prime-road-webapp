<?php
require_once 'db.php';
requireAuth();

$segment_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// Get segment data
$stmt = $pdo->prepare("SELECT * FROM road_segments WHERE id = ?");
$stmt->execute([$segment_id]);
$segment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$segment) {
    die("Road segment not found");
}

// Get defects
$stmt = $pdo->prepare("SELECT * FROM defects WHERE road_segment_id = ? ORDER BY created_at DESC");
$stmt->execute([$segment_id]);
$defects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notes
$stmt = $pdo->prepare("SELECT * FROM notes WHERE road_segment_id = ? ORDER BY created_at DESC");
$stmt->execute([$segment_id]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get maintenance records
$stmt = $pdo->prepare("SELECT * FROM maintenance_records WHERE road_segment_id = ? ORDER BY scheduled_date DESC");
$stmt->execute([$segment_id]);
$maintenance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get condition data
$stmt = $pdo->prepare("SELECT * FROM road_conditions WHERE road_segment_id = ?");
$stmt->execute([$segment_id]);
$condition = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_defect'])) {
        $type = $_POST['type'];
        $severity = $_POST['severity'];
        $position = $_POST['position'];
        $description = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO defects (road_segment_id, type, severity, position, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$segment_id, $type, $severity, $position, $description]);
        
        header("Location: RoadSegment.php?id=$segment_id&success=Defect added successfully");
        exit();
    } elseif (isset($_POST['add_note'])) {
        $content = $_POST['content'];
        $author = $_SESSION['username'];
        
        $stmt = $pdo->prepare("INSERT INTO notes (road_segment_id, author, content) VALUES (?, ?, ?)");
        $stmt->execute([$segment_id, $author, $content]);
        
        header("Location: RoadSegment.php?id=$segment_id&success=Note added successfully");
        exit();
    } elseif (isset($_POST['add_maintenance'])) {
        $type = $_POST['type'];
        $scheduled_date = $_POST['scheduled_date'];
        $notes = $_POST['notes'];
        
        $stmt = $pdo->prepare("INSERT INTO maintenance_records (road_segment_id, type, scheduled_date, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$segment_id, $type, $scheduled_date, $notes]);
        
        header("Location: RoadSegment.php?id=$segment_id&success=Maintenance scheduled successfully");
        exit();
    } elseif (isset($_POST['update_segment'])) {
        $name = $_POST['name'];
        $length = $_POST['length'];
        $width = $_POST['width'];
        $surface_type = $_POST['surface_type'];
        
        $stmt = $pdo->prepare("UPDATE road_segments SET name=?, length=?, width=?, surface_type=? WHERE id=?");
        $stmt->execute([$name, $length, $width, $surface_type, $segment_id]);
        
        header("Location: RoadSegment.php?id=$segment_id&success=Segment updated successfully");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Road Segment Detail | Prime Roads</title>
  <style>
    /* All the CSS from the original RoadSegment.html */
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
      --yellow: #f39c12;
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", sans-serif; }
    body { display:flex; height:100vh; background: var(--gray); overflow: hidden; }

    /* Sidebar */
    .sidebar { 
      width:250px; background: var(--black); color: var(--white); 
      display:flex; flex-direction:column; justify-content:space-between; padding:20px 0; 
    }
    .sidebar .logo { 
      text-align:center; 
      margin-bottom: 20px;
      padding: 0 10px;
    }
    .sidebar .logo img { 
      height:60px;
      max-width: 100%;
    }
    .menu ul { list-style:none; }
    .menu li { 
      padding:12px 25px; display:flex; align-items:center; 
      cursor:pointer; font-size:14px; transition:0.2s; 
    }
    .menu li:hover, .menu li.active { background: var(--sidebar-active); }
    .menu li i { margin-right:10px; }
    .logout { 
      text-align:center; 
      margin-top:20px;
      padding: 0 20px;
    }
    .logout button { 
      background: #1c4025; color: var(--white); border: none; padding:12px 20px; 
      font-size:14px; border-radius: 8px; cursor:pointer; width:100%; font-weight:bold; 
    }
    .logout button:hover { background:#184d2e; }

    /* Main Content */
    .main { flex:1; display:flex; flex-direction:column; overflow-y:auto; }
    
    /* Transparent Topbar */
    .topbar { 
      background: rgba(24, 77, 46, 0.85); 
      padding:12px 20px; 
      display:flex; 
      align-items:center; 
      justify-content:space-between; 
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .search { flex:1; display:flex; align-items:center; max-width:400px; }
    .search input { 
      width:100%; 
      padding:8px 12px; 
      border:none; 
      border-radius:20px; 
      outline:none; 
      font-size:14px; 
      background: rgba(255, 255, 255, 0.9);
    }
    .icons { display:flex; align-items:center; gap:15px; margin-left:20px; }
    .icons i { font-size:20px; color: var(--white); cursor:pointer; }

    .content {
      flex: 1;
      padding: 30px;
      overflow-y: auto;
      background: url('background.jpg') no-repeat center center;
      background-size: cover;
      position: relative;
    }
    .content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 0;
    }
    .content > * {
      position: relative;
      z-index: 1;
    }
    .content h2 { font-size:24px; margin-bottom:20px; font-weight:bold; color: var(--dark-gray); }

    /* Buttons */
    .action-buttons { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; }
    .btn { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; display:inline-flex; align-items:center; gap:8px; transition:0.3s; }
    .btn-primary { background: var(--green); color:#fff; }
    .btn-primary:hover { background:#0f5c5c; }
    .btn-secondary { background: var(--gray); color: var(--dark-gray); }
    .btn-secondary:hover { background:#d5dbdb; }
    .btn-view { background: var(--red); color: white; padding: 5px 10px; font-size: 12px; }
    .btn-edit { background: var(--green); color: white; padding: 5px 10px; font-size: 12px; }
    .btn-delete { background: var(--red); color: white; padding: 5px 10px; font-size: 12px; }

    /* Panels */
    .segment-info, .defects-list, .pci-breakdown, .notes-section, .maintenance-list { 
      background: var(--white); 
      border-radius:12px; 
      padding:20px; 
      margin-bottom:20px; 
      box-shadow:0 2px 6px rgba(0,0,0,0.2); 
    }
    .panel-title { 
      font-weight:bold; 
      margin-bottom:15px; 
      color: var(--dark-gray); 
      font-size:18px; 
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .panel-actions {
      display: flex;
      gap: 10px;
    }
    .panel-actions button {
      background: var(--dark-green);
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 12px;
    }
    .info-grid, .pci-categories { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:15px; }
    .info-label { font-size:14px; color: var(--dark-gray); margin-bottom:5px; }
    .info-value { font-size:16px; font-weight:bold; }
    .editable {
      cursor: pointer;
      border-bottom: 1px dashed var(--dark-gray);
    }
    .editable:hover {
      background-color: #f0f0f0;
    }

    /* Table */
    table { width:100%; border-collapse:collapse; }
    th { padding:12px 15px; background: var(--red); color: #fff; text-align:left; font-size:14px; }
    td { padding:12px 15px; border-bottom:1px solid #ddd; font-size:14px; }
    tr:hover { background:#fafafa; }
    .severity-moderate { color: var(--yellow); font-weight:bold; }
    .severity-severe { color: var(--red); font-weight:bold; }
    .severity-minor { color: var(--green); font-weight:bold; }

    /* PCI */
    .pci-score { font-size:24px; font-weight:bold; color: var(--dark-gray); margin-bottom:15px; }
    .pci-bar { height:20px; background: var(--gray); border-radius:10px; overflow:hidden; margin-bottom:15px; }
    .pci-progress { height:100%; transition:0.5s; }

    /* Modal */
    .modal { position:fixed; inset:0; background: rgba(0,0,0,.5); display:none; align-items:center; justify-content:center; z-index:1000; }
    .modal.open { display:flex; }
    .modal-content { background:#fff; padding:20px; border-radius:10px; width:400px; max-width:95%; }
    .modal-content input, .modal-content textarea, .modal-content select { width:100%; padding:8px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc; }
    .modal-actions { margin-top:15px; display:flex; justify-content:flex-end; gap:10px; }
    .save-btn { background: var(--green); color:#fff; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; }
    .cancel-btn { background:#ccc; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; }
    
    /* Defect modal */
    .defect-modal { width: 500px; }
    .defect-images { display: flex; gap: 10px; margin: 10px 0; }
    .defect-images img { width: 100px; height: 100px; object-fit: cover; border-radius: 5px; }
    
    /* Note item */
    .note-item { 
      margin-bottom: 10px; 
      padding: 10px; 
      border-left: 3px solid var(--green);
      background-color: #f9f9f9;
      border-radius: 0 5px 5px 0;
    }
    .note-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 5px;
    }
    .note-actions {
      display: flex;
      gap: 5px;
    }
    .note-actions button {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--dark-gray);
    }
    .note-actions button:hover {
      color: var(--red);
    }
    
    /* Maintenance item */
    .maintenance-item {
      padding: 10px;
      border-bottom: 1px solid #eee;
    }
    .maintenance-item:last-child {
      border-bottom: none;
    }
    
    /* Success message */
    .success-message {
      background: var(--dark-green);
      color: white;
      padding: 10px 15px;
      border-radius: 5px;
      margin-bottom: 20px;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="sidebar">
  <div class="logo"><img src="logo.png" alt="Prime Roads Logo"></div>
  <div class="menu">
    <ul>
      <li><i class="fa fa-home"></i> Dashboard</li>
      <li><i class="fa fa-map"></i> <a href="GISMAP.php" style="color: inherit; text-decoration: none;">GIS Road Condition Map</a></li>
      <li><i class="fa fa-mobile-alt"></i> Mobile Data Collection</li>
      <li><i class="fa fa-upload"></i> Upload Manager</li>
      <li><i class="fa fa-exclamation-triangle"></i> Defect Detection Review</li>
      <li class="active"><i class="fa fa-road"></i> Road Segment</li>
      <li><i class="fa fa-wrench"></i> Maintenance Planning</li>
      <li><i class="fa fa-clipboard-list"></i> Maintenance Log</li>
      <li><i class="fa fa-file-alt"></i> Report Center</li>
      <li><i class="fa fa-database"></i> <a href="AssetInventory.php" style="color: inherit; text-decoration: none;">Road Asset Inventory</a></li>
    </ul>
  </div>
  <div class="logout">
      <form method="POST" action="logout.php">
          <button type="submit" name="logout"><i class="fa fa-sign-out-alt"></i> LOGOUT</button>
      </form>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div class="search"><input type="text" id="searchInput" placeholder="Search defects..."></div>
    <div class="icons">
      <i class="fa fa-bell"></i>
      <i class="fa fa-user-circle"></i>
      <span><?php echo $_SESSION['username']; ?></span>
    </div>
  </div>

  <div class="content">
    <h2>Road Segment Detail: <?php echo htmlspecialchars($segment['name']); ?></h2>
    
    <?php if (isset($_GET['success'])): ?>
      <div class="success-message">
        <?php echo htmlspecialchars($_GET['success']); ?>
      </div>
    <?php endif; ?>
    
    <div class="action-buttons">
      <button class="btn btn-primary" id="openMaintenance">Schedule Maintenance</button>
      <button class="btn btn-secondary" id="openNote">Add Note</button>
      <a href="export_segment.php?id=<?php echo $segment_id; ?>" class="btn btn-secondary">Export Segment Report</a>
      <button class="btn btn-secondary" id="addDefect">Add Defect</button>
      <button class="btn btn-secondary" id="editSegment">Edit Segment Info</button>
    </div>

    <!-- Segment Info -->
    <div class="segment-info">
      <div class="panel-title">
        Segment Info
        <div class="panel-actions">
          <button id="editSegmentBtn"><i class="fas fa-edit"></i> Edit</button>
        </div>
      </div>
      <div class="info-grid">
        <div><div class="info-label">Segment ID</div><div class="info-value"><?php echo $segment['id']; ?></div></div>
        <div><div class="info-label">Length</div><div class="info-value"><?php echo $segment['length']; ?>km</div></div>
        <div><div class="info-label">Width</div><div class="info-value"><?php echo $segment['width']; ?>m</div></div>
        <div><div class="info-label">Surface</div><div class="info-value"><?php echo $segment['surface_type']; ?></div></div>
        <div><div class="info-label">PCI</div><div class="info-value" id="pciValue"><?php echo $condition['pci_score'] ?? 'N/A'; ?></div></div>
        <div><div class="info-label">Last Updated</div><div class="info-value"><?php echo date('M j, Y', strtotime($segment['updated_at'])); ?></div></div>
      </div>
    </div>

    <!-- Defects Table -->
    <div class="defects-list">
      <div class="panel-title">
        Defect List
        <div class="panel-actions">
          <button id="addDefectBtn"><i class="fas fa-plus"></i> Add Defect</button>
        </div>
      </div>
      <table id="defectsTable">
        <thead>
          <tr><th>No.</th><th>Type</th><th>Severity</th><th>Position</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php foreach ($defects as $defect): ?>
            <tr>
              <td><?php echo $defect['id']; ?></td>
              <td><?php echo htmlspecialchars($defect['type']); ?></td>
              <td class="severity-<?php echo $defect['severity']; ?>">
                <?php echo ucfirst($defect['severity']); ?>
              </td>
              <td><?php echo htmlspecialchars($defect['position']); ?></td>
              <td>
                <button class="btn btn-view view-defect" data-id="<?php echo $defect['id']; ?>">View</button>
                <button class="btn btn-edit edit-defect" data-id="<?php echo $defect['id']; ?>">Edit</button>
                <form method="POST" action="delete_defect.php" style="display: inline;">
                  <input type="hidden" name="id" value="<?php echo $defect['id']; ?>">
                  <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
                  <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this defect?')">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          
          <?php if (count($defects) === 0): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 20px;">
                No defects found for this segment.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Maintenance List -->
    <div class="maintenance-list">
      <div class="panel-title">
        Maintenance History
        <div class="panel-actions">
          <button id="addMaintenanceBtn"><i class="fas fa-plus"></i> Schedule Maintenance</button>
        </div>
      </div>
      <div id="maintenanceContainer">
        <?php foreach ($maintenance as $record): ?>
          <div class="maintenance-item">
            <div><strong><?php echo htmlspecialchars($record['type']); ?></strong> - <?php echo date('M j, Y', strtotime($record['scheduled_date'])); ?></div>
            <div><?php echo htmlspecialchars($record['notes']); ?></div>
            <div style="margin-top: 5px;">
              <button class="btn-edit edit-maintenance" data-id="<?php echo $record['id']; ?>">Edit</button>
              <form method="POST" action="delete_maintenance.php" style="display: inline;">
                <input type="hidden" name="id" value="<?php echo $record['id']; ?>">
                <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
                <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this maintenance record?')">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
        
        <?php if (count($maintenance) === 0): ?>
          <div style="text-align: center; padding: 20px;">
            No maintenance records found for this segment.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- PCI -->
    <div class="pci-breakdown">
      <div class="panel-title">PCI Calculation Breakdown</div>
      <div class="pci-score" id="pciScore"><?php echo $condition['pci_score'] ?? 0; ?></div>
      <div class="pci-bar"><div class="pci-progress" id="pciBar" style="width:<?php echo ($condition['pci_score'] ?? 0); ?>%; background: #c82333;"></div></div>
      <div class="pci-categories">
        <div><div class="info-label">Cracking</div><div class="info-value">15%</div></div>
        <div><div class="info-label">Rutting</div><div class="info-value">8%</div></div>
        <div><div class="info-label">Patching</div><div class="info-value">6%</div></div>
        <div><div class="info-label">Raveling</div><div class="info-value">5%</div></div>
      </div>
    </div>

    <!-- Notes -->
    <div class="notes-section" id="notesSection">
      <div class="panel-title">
        Notes
        <div class="panel-actions">
          <button id="addNoteBtn"><i class="fas fa-plus"></i> Add Note</button>
        </div>
      </div>
      <div id="notesContainer">
        <?php foreach ($notes as $note): ?>
          <div class="note-item">
            <div class="note-header">
              <strong><?php echo htmlspecialchars($note['author']); ?></strong> (<?php echo date('M j, Y', strtotime($note['created_at'])); ?>)
              <div class="note-actions">
                <button class="edit-note" data-id="<?php echo $note['id']; ?>"><i class="fas fa-edit"></i></button>
                <form method="POST" action="delete_note.php" style="display: inline;">
                  <input type="hidden" name="id" value="<?php echo $note['id']; ?>">
                  <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
                  <button type="submit" class="delete-note" onclick="return confirm('Are you sure you want to delete this note?')"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </div>
            <div><?php echo nl2br(htmlspecialchars($note['content'])); ?></div>
          </div>
        <?php endforeach; ?>
        
        <?php if (count($notes) === 0): ?>
          <div style="text-align: center; padding: 20px;">
            No notes found for this segment.
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- Schedule Maintenance Modal -->
<div class="modal" id="maintenanceModal">
  <div class="modal-content">
    <h3 id="maintenanceModalTitle">Schedule Maintenance</h3>
    <form method="POST" action="">
      <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
      <input type="text" name="type" placeholder="Maintenance Type" required>
      <input type="date" name="scheduled_date" required>
      <textarea name="notes" placeholder="Maintenance notes..."></textarea>
      <div class="modal-actions">
        <button type="button" class="cancel-btn" id="cancelMaintenance">Cancel</button>
        <button type="submit" class="save-btn" name="add_maintenance">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Note Modal -->
<div class="modal" id="noteModal">
  <div class="modal-content">
    <h3 id="noteModalTitle">Add Note</h3>
    <form method="POST" action="">
      <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
      <textarea name="content" placeholder="Write a note..." required></textarea>
      <div class="modal-actions">
        <button type="button" class="cancel-btn" id="cancelNote">Cancel</button>
        <button type="submit" class="save-btn" name="add_note">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Defect Modal -->
<div class="modal" id="defectModal">
  <div class="modal-content defect-modal">
    <h3 id="defectModalTitle">Add Defect</h3>
    <form method="POST" action="">
      <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
      <input type="text" name="type" placeholder="Defect Type" required>
      <select name="severity" required>
        <option value="">Select Severity</option>
        <option value="minor">Minor</option>
        <option value="moderate">Moderate</option>
        <option value="severe">Severe</option>
      </select>
      <input type="text" name="position" placeholder="Position" required>
      <textarea name="description" placeholder="Description"></textarea>
      <div class="modal-actions">
        <button type="button" class="cancel-btn" id="cancelDefect">Cancel</button>
        <button type="submit" class="save-btn" name="add_defect">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Segment Modal -->
<div class="modal" id="segmentModal">
  <div class="modal-content">
    <h3>Edit Segment Information</h3>
    <form method="POST" action="">
      <input type="hidden" name="segment_id" value="<?php echo $segment_id; ?>">
      <input type="text" name="name" value="<?php echo htmlspecialchars($segment['name']); ?>" required>
      <input type="number" name="length" step="0.01" value="<?php echo $segment['length']; ?>" required>
      <input type="number" name="width" step="0.1" value="<?php echo $segment['width']; ?>" required>
      <select name="surface_type" required>
        <option value="Asphalt" <?php echo $segment['surface_type'] == 'Asphalt' ? 'selected' : ''; ?>>Asphalt</option>
        <option value="Concrete" <?php echo $segment['surface_type'] == 'Concrete' ? 'selected' : ''; ?>>Concrete</option>
        <option value="Gravel" <?php echo $segment['surface_type'] == 'Gravel' ? 'selected' : ''; ?>>Gravel</option>
        <option value="Other" <?php echo $segment['surface_type'] == 'Other' ? 'selected' : ''; ?>>Other</option>
      </select>
      <div class="modal-actions">
        <button type="button" class="cancel-btn" id="cancelSegment">Cancel</button>
        <button type="submit" class="save-btn" name="update_segment">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  // JavaScript functionality
  document.addEventListener('DOMContentLoaded', function() {
    // Modal elements
    const maintenanceModal = document.getElementById('maintenanceModal');
    const noteModal = document.getElementById('noteModal');
    const defectModal = document.getElementById('defectModal');
    const segmentModal = document.getElementById('segmentModal');
    
    // Open modal buttons
    document.getElementById('openMaintenance').addEventListener('click', function() {
      maintenanceModal.classList.add('open');
    });
    
    document.getElementById('openNote').addEventListener('click', function() {
      noteModal.classList.add('open');
    });
    
    document.getElementById('addDefect').addEventListener('click', function() {
      defectModal.classList.add('open');
    });
    
    document.getElementById('editSegment').addEventListener('click', function() {
      segmentModal.classList.add('open');
    });
    
    document.getElementById('editSegmentBtn').addEventListener('click', function() {
      segmentModal.classList.add('open');
    });
    
    // Close modal buttons
    document.getElementById('cancelMaintenance').addEventListener('click', function() {
      maintenanceModal.classList.remove('open');
    });
    
    document.getElementById('cancelNote').addEventListener('click', function() {
      noteModal.classList.remove('open');
    });
    
    document.getElementById('cancelDefect').addEventListener('click', function() {
      defectModal.classList.remove('open');
    });
    
    document.getElementById('cancelSegment').addEventListener('click', function() {
      segmentModal.classList.remove('open');
    });
    
    // View defect functionality
    document.querySelectorAll('.view-defect').forEach(button => {
      button.addEventListener('click', function() {
        const defectId = this.dataset.id;
        // In a real application, this would show a modal with defect details
        alert('Viewing defect ' + defectId);
      });
    });
    
    // Edit defect functionality
    document.querySelectorAll('.edit-defect').forEach(button => {
      button.addEventListener('click', function() {
        const defectId = this.dataset.id;
        // In a real application, this would open an edit modal
        window.location.href = 'edit_defect.php?id=' + defectId + '&segment_id=<?php echo $segment_id; ?>';
      });
    });
    
    // Edit maintenance functionality
    document.querySelectorAll('.edit-maintenance').forEach(button => {
      button.addEventListener('click', function() {
        const maintenanceId = this.dataset.id;
        window.location.href = 'edit_maintenance.php?id=' + maintenanceId + '&segment_id=<?php echo $segment_id; ?>';
      });
    });
    
    // Edit note functionality
    document.querySelectorAll('.edit-note').forEach(button => {
      button.addEventListener('click', function() {
        const noteId = this.dataset.id;
        window.location.href = 'edit_note.php?id=' + noteId + '&segment_id=<?php echo $segment_id; ?>';
      });
    });
    
    // Update PCI display
    const pciScore = <?php echo $condition['pci_score'] ?? 0; ?>;
    const pciBar = document.getElementById('pciBar');
    
    if (pciScore >= 80) {
      pciBar.style.background = 'green';
    } else if (pciScore >= 50) {
      pciBar.style.background = 'orange';
    } else {
      pciBar.style.background = 'red';
    }
  });
</script>
</body>
</html>