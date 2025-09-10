<?php
require_once 'db.php';
requireAuth();

// Get all road segments with their conditions
$stmt = $pdo->query("
    SELECT rs.*, rc.condition, rc.pci_score, rc.latlngs 
    FROM road_segments rs 
    LEFT JOIN road_conditions rc ON rs.id = rc.road_segment_id
    ORDER BY rs.name
");
$roads = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prime Roads - GIS Road Condition Map</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    /* All the CSS from the original GISMAP.html */
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

      --good:#2ecc71;
      --fair:#f39c12;
      --poor:#e74c3c;
      --maint:#3498db;
      --uns:#95a5a6;
    }

    * { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI", sans-serif; }

    body { display:flex; height:100vh; background:var(--gray); overflow: hidden; }

    /* Sidebar */
    .sidebar {
      width:250px; background:var(--black); color:var(--white);
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
    .menu li:hover, .menu li.active { background:var(--sidebar-active); }
    .menu li i { margin-right:10px; }
    .logout { 
      text-align:center; 
      margin-top:20px;
      padding: 0 20px;
    }
    .logout button {
      background:#1c4025; color:var(--white); border:none; padding:12px 20px;
      font-size:14px; border-radius:8px; cursor:pointer; width:100%; font-weight:bold;
    }
    .logout button:hover { background:#184d2e; }

    /* Main Content */
    .main { flex:1; display:flex; flex-direction:column; }

    /* Transparent Topbar */
    .topbar {
      background: rgba(24, 77, 46, 0.85);
      padding:12px 20px;
      display:flex; align-items:center; justify-content:space-between;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .search { flex:1; display:flex; align-items:center; max-width:400px; }
    .search input {
      width:100%; padding:8px 12px; border:none; border-radius:20px; outline:none; font-size:14px;
      background: rgba(255, 255, 255, 0.9);
    }
    .icons { display:flex; align-items:center; gap:15px; margin-left:20px; }
    .icons i { font-size:20px; color:var(--white); cursor:pointer; }

    /* Content */
    .content {
      flex:1; display:flex; flex-direction:column; overflow:hidden;
    }

    /* Chipbar */
    .chipbar {
      display:flex; align-items:center; gap:10px; padding:10px 14px;
      background:#e8eee9; border-bottom:1px solid #d6e0d9;
      flex-wrap: wrap;
    }
    .chip {
      background:white; border:1px solid #e2e8e2; border-radius:12px;
      padding:8px 12px; display:flex; align-items:center; gap:10px; cursor:pointer;
      font-size:14px;
    }
    .chip label { font-weight:700; font-size:14px; color:#2b3f2e; }
    .chip select, .chip input {
      border:none; background:transparent; outline:none; font-size:13px; color:#29402c;
    }
    .chip .caret { margin-left:4px; opacity:.8; }

    /* Map */
    .mapwrap { position:relative; flex:1; min-height:0; }
    #map { position:absolute; inset:0; }
    .leaflet-control-zoom { display:none; }

    /* Custom Zoom */
    .zoomPill {
      position:absolute; right:24px; bottom:80px; z-index:1000;
      background:#1b1b1b; color:white; display:flex; gap:14px;
      padding:8px 12px; border-radius:18px; opacity:.92;
    }
    .zoomPill button {
      width:36px; height:36px; border-radius:12px; border:none;
      background:#2a2a2a; color:white; font-size:20px; cursor:pointer;
      transition: all 0.3s;
    }
    .zoomPill button:hover {
      background: #3a3a3a;
    }

    /* Legend */
    .legendCard {
      position:absolute; left:22px; bottom:22px; z-index:1000;
      background:white; border-radius:12px; padding:12px 14px;
      min-width:220px; box-shadow:0 2px 6px rgba(0,0,0,0.2);
    }
    .legendRow { display:flex; align-items:center; gap:10px; margin:6px 0; }
    .dot { width:14px; height:14px; border-radius:50%; }
    .legendTitle { font-weight:800; font-size:14px; margin-bottom:4px; }
    
    /* Road details panel */
    .road-details {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 1000;
      background: white;
      border-radius: 12px;
      padding: 20px;
      width: 320px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
      display: none;
    }
    .road-details h3 {
      margin-bottom: 15px;
      color: var(--dark-gray);
      border-bottom: 2px solid var(--green);
      padding-bottom: 8px;
    }
    .road-details .close {
      position: absolute;
      top: 15px;
      right: 15px;
      cursor: pointer;
      font-size: 20px;
      color: #777;
    }
    .road-details .close:hover {
      color: var(--red);
    }
    .road-details .info {
      margin-bottom: 10px;
      display: flex;
    }
    .road-details .info-label {
      font-weight: bold;
      margin-right: 5px;
      min-width: 100px;
      color: var(--dark-gray);
    }
    .road-details .info-value {
      flex: 1;
    }
    .road-details .actions {
      margin-top: 15px;
      display: flex;
      gap: 10px;
    }
    .road-details .actions button {
      padding: 8px 15px;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s;
    }
    .road-details .btn-edit {
      background: var(--green);
      color: white;
    }
    .road-details .btn-edit:hover {
      background: #0f5c5c;
    }
    .road-details .btn-delete {
      background: var(--red);
      color: white;
    }
    .road-details .btn-delete:hover {
      background: var(--dark-red);
    }

    /* Modal */
    .modal {
      position:fixed; inset:0; background: rgba(0,0,0,.5);
      display:none; align-items:center; justify-content:center; z-index:1001;
    }
    .modal.open { display:flex; }
    .modal-content {
      background:#fff; padding:25px; border-radius:12px;
      width:450px; max-width:95%; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .modal-content h3 { 
      margin-bottom:20px; 
      color: var(--dark-gray);
      border-bottom: 2px solid var(--green);
      padding-bottom: 10px;
    }
    .modal-content label { display:block; margin-top:15px; font-size:14px; font-weight: 500; }
    .modal-content input, .modal-content select, .modal-content textarea {
      width:100%; padding:10px; margin-top:5px;
      border:1px solid #ddd; border-radius:6px; font-size:14px;
    }
    .modal-content input:focus, .modal-content select:focus, .modal-content textarea:focus {
      border-color: var(--green);
      outline: none;
    }
    .modal-actions { margin-top:20px; display:flex; justify-content:flex-end; gap:10px; }
    .modal-actions button { 
      padding:10px 18px; border:none; border-radius:6px; cursor:pointer; 
      font-weight:600; transition: all 0.3s;
    }
    .save-btn { background: var(--green); color:#fff; }
    .save-btn:hover { background: #0f5c5c; }
    .cancel-btn { background:#e0e0e0; color:#555; }
    .cancel-btn:hover { background:#d0d0d0; }

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
      z-index: 1002;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s;
    }
    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
    .toast.error {
      background: var(--red);
    }

    /* Toolbar */
    .map-toolbar {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 1000;
      display: flex;
      gap: 10px;
    }
    .map-toolbar button {
      background: white;
      border: none;
      border-radius: 8px;
      padding: 10px 15px;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: all 0.3s;
    }
    .map-toolbar button:hover {
      background: #f5f5f5;
      transform: translateY(-2px);
    }
    .btn-add-road {
      color: var(--green);
    }
    .btn-edit-mode {
      color: var(--dark-green);
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar">
    <div class="logo"><img src="logo.png" alt="Prime Roads Logo"></div>
    <div class="menu">
      <ul>
        <li><i class="fa fa-home"></i> Dashboard</li>
        <li class="active"><i class="fa fa-map"></i> GIS Road Condition Map</li>
        <li><i class="fa fa-mobile-alt"></i> Mobile Data Collection</li>
        <li><i class="fa fa-upload"></i> Upload Manager</li>
        <li><i class="fa fa-exclamation-triangle"></i> Defect Detection Review</li>
        <li><i class="fa fa-road"></i> <a href="RoadSegment.php" style="color: inherit; text-decoration: none;">Road Segment</a></li>
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

  <!-- Main -->
  <div class="main">
    <!-- Topbar -->
    <div class="topbar">
      <div class="search"><input type="text" id="mapSearch" placeholder="Search roads..."></div>
      <div class="icons">
        <i class="fa fa-bell"></i>
        <i class="fa fa-user-circle"></i>
        <span><?php echo $_SESSION['username']; ?></span>
      </div>
    </div>

    <!-- Content -->
    <div class="content">
      <!-- Filter chips -->
      <div class="chipbar">
        <div class="chip"><i class="fa fa-filter"></i> <label>Filters</label></div>
        <div class="chip">
          <label>Condition</label>
          <select id="conditionFilter">
            <option value="all">All Conditions</option>
            <option value="good">Good</option>
            <option value="fair">Fair</option>
            <option value="poor">Poor</option>
            <option value="maintenance">Under Maintenance</option>
            <option value="unscanned">Unscanned</option>
          </select>
          <i class="fa fa-caret-down caret"></i>
        </div>
        <div class="chip">
          <label>Date Range</label>
          <input type="date" id="startDate"> – <input type="date" id="endDate">
          <i class="fa fa-caret-down caret"></i>
        </div>
        <div class="chip">
          <label>Town</label>
          <select id="townFilter">
            <option value="all">All Areas</option>
            <option value="dorado">Dorado Park</option>
            <option value="central">Central District</option>
            <option value="northern">Northern Region</option>
            <option value="southern">Southern Region</option>
          </select>
          <i class="fa fa-caret-down caret"></i>
        </div>
        <div class="chip"><label>Street</label><input id="streetFilter" placeholder="Any street"></div>
        <div class="chip">
          <button id="applyFilters" style="background: var(--green); color: white; border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;">
            <i class="fa fa-check"></i> Apply
          </button>
        </div>
        <div class="chip">
          <button id="clearFilters" style="background: var(--gray); color: var(--dark-gray); border: none; padding: 6px 12px; border-radius: 5px; cursor: pointer;">
            <i class="fa fa-times"></i> Clear
          </button>
        </div>
      </div>

      <!-- Map -->
      <div class="mapwrap">
        <div id="map"></div>
        
        <!-- Map toolbar -->
        <div class="map-toolbar">
          <button id="addRoadBtn" class="btn-add-road">
            <i class="fa fa-plus"></i> Add Road
          </button>
          <button id="toggleEditMode" class="btn-edit-mode">
            <i class="fa fa-edit"></i> Edit Mode
          </button>
        </div>
        
        <!-- Road details panel -->
        <div class="road-details" id="roadDetails">
          <span class="close" id="closeDetails">&times;</span>
          <h3>Road Details</h3>
          <div class="info"><span class="info-label">Name:</span> <span id="detailName" class="info-value"></span></div>
          <div class="info"><span class="info-label">Condition:</span> <span id="detailCondition" class="info-value"></span></div>
          <div class="info"><span class="info-label">Length:</span> <span id="detailLength" class="info-value"></span></div>
          <div class="info"><span class="info-label">Last Scanned:</span> <span id="detailLastScanned" class="info-value"></span></div>
          <div class="info"><span class="info-label">PCI Score:</span> <span id="detailPCI" class="info-value"></span></div>
          <div class="actions">
            <button class="btn-edit" id="editRoadBtn">Edit</button>
            <button class="btn-delete" id="deleteRoadBtn">Delete</button>
          </div>
        </div>

        <!-- custom zoom -->
        <div class="zoomPill">
          <button id="zoomOut">−</button>
          <button id="zoomIn">+</button>
        </div>

        <!-- legend -->
        <div class="legendCard">
          <div class="legendTitle">Road Condition Legend</div>
          <div class="legendRow"><span class="dot" style="background:var(--good)"></span> Good (85–100)</div>
          <div class="legendRow"><span class="dot" style="background:var(--fair)"></span> Fair (65–84)</div>
          <div class="legendRow"><span class="dot" style="background:var(--poor)"></span> Poor (0–64)</div>
          <div class="legendRow"><span class="dot" style="background:var(--maint)"></span> Under Maintenance</div>
          <div class="legendRow"><span class="dot" style="background:var(--uns)"></span> Unscanned</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Road Modal -->
  <div class="modal" id="roadModal">
    <div class="modal-content">
      <h3 id="modalTitle">Add New Road</h3>
      <form id="roadForm" method="POST" action="save_road.php">
        <input type="hidden" id="roadId" name="id">
        <label>Road Name</label>
        <input type="text" id="roadName" name="name" required placeholder="Enter road name">
        
        <label>Condition</label>
        <select id="roadCondition" name="condition" required>
          <option value="">Select condition</option>
          <option value="good">Good</option>
          <option value="fair">Fair</option>
          <option value="poor">Poor</option>
          <option value="maintenance">Under Maintenance</option>
          <option value="unscanned">Unscanned</option>
        </select>
        
        <div style="display: flex; gap: 15px;">
          <div style="flex: 1;">
            <label>Length (km)</label>
            <input type="number" id="roadLength" name="length" step="0.1" required placeholder="0.0" min="0">
          </div>
          <div style="flex: 1;">
            <label>PCI Score</label>
            <input type="number" id="roadPCI" name="pci_score" min="0" max="100" placeholder="0-100">
          </div>
        </div>
        
        <label>Last Scanned Date</label>
        <input type="date" id="roadLastScanned" name="last_scanned" required>
        
        <label>Additional Notes</label>
        <textarea id="roadNotes" name="notes" rows="3" placeholder="Add any additional information about this road"></textarea>
        
        <div class="modal-actions">
          <button type="button" class="cancel-btn" id="cancelRoadBtn">Cancel</button>
          <button type="submit" class="save-btn">Save Road</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Toast Notification -->
  <div class="toast" id="toast">
    <i class="fa fa-check-circle"></i>
    <span id="toastMessage">Operation completed successfully</span>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // Data from PHP
    const roadsData = <?php echo json_encode($roads); ?>;
    
    // Color mapping for conditions
    const conditionColors = {
      good: '#2ecc71',
      fair: '#f39c12',
      poor: '#e74c3c',
      maintenance: '#3498db',
      unscanned: '#95a5a6'
    };

    // Initialize map
    const map = L.map('map').setView([-22.57, 17.08], 13); // Windhoek Namibia
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Initialize the application
    function init() {
      renderRoads();
      setupEventListeners();
    }

    // Render all roads on the map
    function renderRoads() {
      // Clear existing layers
      map.eachLayer(layer => {
        if (layer instanceof L.Polyline) {
          map.removeLayer(layer);
        }
      });
      
      // Add roads to map
      roadsData.forEach(road => {
        if (road.latlngs) {
          try {
            const latlngs = JSON.parse(road.latlngs);
            const polyline = L.polyline(latlngs, {
              color: conditionColors[road.condition || 'unscanned'],
              weight: 6,
              opacity: 0.8
            }).addTo(map);
            
            // Add popup
            polyline.bindPopup(`
              <strong>${road.name}</strong><br>
              Condition: ${(road.condition || 'unscanned').toUpperCase()}<br>
              Length: ${road.length}km<br>
              Last Scanned: ${road.last_scanned || 'N/A'}<br>
              PCI: ${road.pci_score > 0 ? road.pci_score : 'N/A'}
            `);
            
            // Add click event to show details
            polyline.on('click', () => {
              showRoadDetails(road);
            });
          } catch (e) {
            console.error('Error parsing latlngs for road:', road.id, e);
          }
        }
      });
    }

    // Show road details
    function showRoadDetails(road) {
      selectedRoad = road;
      
      document.getElementById('detailName').textContent = road.name;
      document.getElementById('detailCondition').textContent = (road.condition || 'unscanned').charAt(0).toUpperCase() + (road.condition || 'unscanned').slice(1);
      document.getElementById('detailLength').textContent = road.length + 'km';
      document.getElementById('detailLastScanned').textContent = road.last_scanned || 'N/A';
      document.getElementById('detailPCI').textContent = road.pci_score > 0 ? road.pci_score : 'N/A';
      document.getElementById('roadDetails').style.display = 'block';
      
      // Set up edit and delete buttons
      document.getElementById('editRoadBtn').onclick = () => editRoad(road);
      document.getElementById('deleteRoadBtn').onclick = () => deleteRoad(road.id);
    }

    // Edit road
    function editRoad(road) {
      document.getElementById('modalTitle').textContent = 'Edit Road';
      document.getElementById('roadId').value = road.id;
      document.getElementById('roadName').value = road.name;
      document.getElementById('roadCondition').value = road.condition || '';
      document.getElementById('roadLength').value = road.length;
      document.getElementById('roadPCI').value = road.pci_score || '';
      document.getElementById('roadLastScanned').value = road.last_scanned;
      document.getElementById('roadNotes').value = road.notes || '';
      
      document.getElementById('roadModal').classList.add('open');
      document.getElementById('roadDetails').style.display = 'none';
    }

    // Delete road
    function deleteRoad(id) {
      if (confirm('Are you sure you want to delete this road?')) {
        // In a real application, this would make an AJAX request
        window.location.href = 'delete_road.php?id=' + id;
      }
    }

    // Setup event listeners
    function setupEventListeners() {
      // Close details panel
      document.getElementById('closeDetails').addEventListener('click', () => {
        document.getElementById('roadDetails').style.display = 'none';
        selectedRoad = null;
      });

      // Add road button
      document.getElementById('addRoadBtn').addEventListener('click', () => {
        document.getElementById('modalTitle').textContent = 'Add New Road';
        document.getElementById('roadForm').reset();
        document.getElementById('roadId').value = '';
        document.getElementById('roadModal').classList.add('open');
      });

      // Cancel road form
      document.getElementById('cancelRoadBtn').addEventListener('click', () => {
        document.getElementById('roadModal').classList.remove('open');
      });

      // Custom zoom
      document.getElementById('zoomIn').addEventListener('click', () => map.zoomIn());
      document.getElementById('zoomOut').addEventListener('click', () => map.zoomOut());
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>