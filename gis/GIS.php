<?php
require_once 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prime Roads - GIS Road Condition Map</title>

  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    /* Root Colors (same as AssetInventory) */
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

.container {
    display: flex;
    height: 100vh;
    width: 100%;
}

.section-divider {
    border: none;
    height: 4px;
    background-color: orange;
    margin: 0 0 15px 0;
    z-index: 100;
}

/* Content with background */
.main {
    flex: 1;
    display: flex;
    position: relative;
    flex-direction: column;
    overflow: auto;
    padding: 20px;

    /* Background image */
    background-image: url('background.jpg'); /* replace with your image path */
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
}

/* White transparent overlay */
.main::before {
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
.main > * {
    position: relative;
    z-index: 1;
}

    /* Transparent Topbar */
    .topbar {
     
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

  </style>
</head>
<body>

  <!-- Sidebar -->
   <?php include 'sidebar.php'; ?>

  <!-- Main -->
  <div class="main">
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

      
        <hr class="section-divider">
      

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
      <form id="roadForm">
        <input type="hidden" id="roadId">
        <label>Road Name</label>
        <input type="text" id="roadName" required placeholder="Enter road name">
        
        <label>Condition</label>
        <select id="roadCondition" required>
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
            <input type="number" id="roadLength" step="0.1" required placeholder="0.0" min="0">
          </div>
          <div style="flex: 1;">
            <label>PCI Score</label>
            <input type="number" id="roadPCI" min="0" max="100" placeholder="0-100">
          </div>
        </div>
        
        <label>Last Scanned Date</label>
        <input type="date" id="roadLastScanned" required>
        
        <label>Additional Notes</label>
        <textarea id="roadNotes" rows="3" placeholder="Add any additional information about this road"></textarea>
        
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

   
     // Data storage
    const ROADS_STORAGE_KEY = "gis_road_data";
    let roads = JSON.parse(localStorage.getItem(ROADS_STORAGE_KEY)) || [];
    let editingRoadId = null;
    let isEditMode = false;
    let selectedRoad = null;

    // Initialize map
    const map = L.map('map').setView([-22.57, 17.08], 13); // Windhoek Namibia
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution:'&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Color mapping for conditions
    const conditionColors = {
      good: '#2ecc71',
      fair: '#f39c12',
      poor: '#e74c3c',
      maintenance: '#3498db',
      unscanned: '#95a5a6'
    };

    // Initialize the application
    function init() {
      if (roads.length === 0) {
        // Add sample data if empty
        roads = [
          {id: 1, name: "Main Street", latlngs: [[-22.56, 17.07], [-22.57, 17.09], [-22.58, 17.11]], condition: "good", length: "2.5", lastScanned: "2023-10-15", pci: 87, notes: ""},
          {id: 2, name: "Oak Avenue", latlngs: [[-22.55, 17.06], [-22.56, 17.08], [-22.57, 17.10]], condition: "fair", length: "3.2", lastScanned: "2023-09-22", pci: 72, notes: ""},
          {id: 3, name: "Pine Road", latlngs: [[-22.59, 17.08], [-22.58, 17.10], [-22.57, 17.12]], condition: "poor", length: "1.8", lastScanned: "2023-11-05", pci: 45, notes: "Needs urgent repair"},
          {id: 4, name: "Elm Boulevard", latlngs: [[-22.54, 17.09], [-22.55, 17.11], [-22.56, 17.13]], condition: "maintenance", length: "4.5", lastScanned: "2023-08-17", pci: 0, notes: "Maintenance scheduled for next week"},
          {id: 5, name: "Maple Drive", latlngs: [[-22.60, 17.05], [-22.59, 17.07], [-22.58, 17.09]], condition: "unscanned", length: "2.1", lastScanned: "", pci: 0, notes: "Scheduled for scanning next month"}
        ];
        saveRoads();
      }
      
      renderRoads();
      setupEventListeners();
    }

    // Save roads to localStorage
    function saveRoads() {
      localStorage.setItem(ROADS_STORAGE_KEY, JSON.stringify(roads));
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
      roads.forEach(road => {
        const polyline = L.polyline(road.latlngs, {
          color: conditionColors[road.condition],
          weight: 6,
          opacity: 0.8
        }).addTo(map);
        
        // Add popup
        polyline.bindPopup(`
          <strong>${road.name}</strong><br>
          Condition: ${road.condition.toUpperCase()}<br>
          Length: ${road.length}km<br>
          Last Scanned: ${road.lastScanned || 'N/A'}<br>
          PCI: ${road.pci > 0 ? road.pci : 'N/A'}
        `);
        
        // Add click event to show details
        polyline.on('click', () => {
          showRoadDetails(road);
        });
      });
    }

    // Show road details
    function showRoadDetails(road) {
      selectedRoad = road;
      
      document.getElementById('detailName').textContent = road.name;
      document.getElementById('detailCondition').textContent = road.condition.charAt(0).toUpperCase() + road.condition.slice(1);
      document.getElementById('detailLength').textContent = road.length + 'km';
      document.getElementById('detailLastScanned').textContent = road.lastScanned || 'N/A';
      document.getElementById('detailPCI').textContent = road.pci > 0 ? road.pci : 'N/A';
      document.getElementById('roadDetails').style.display = 'block';
    }

    // Show toast notification
    function showToast(message, isError = false) {
      const toast = document.getElementById('toast');
      const toastMessage = document.getElementById('toastMessage');
      
      toastMessage.textContent = message;
      
      if (isError) {
        toast.classList.add('error');
        toast.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + toast.innerHTML;
      } else {
        toast.classList.remove('error');
        toast.innerHTML = '<i class="fa fa-check-circle"></i> ' + toast.innerHTML;
      }
      
      toast.classList.add('show');
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, 3000);
    }

    // Setup event listeners
    function setupEventListeners() {
      // Close details panel
      document.getElementById('closeDetails').addEventListener('click', () => {
        document.getElementById('roadDetails').style.display = 'none';
        selectedRoad = null;
      });

      // Edit road button
      document.getElementById('editRoadBtn').addEventListener('click', () => {
        if (selectedRoad) {
          editRoad(selectedRoad);
        }
      });

      // Delete road button
      document.getElementById('deleteRoadBtn').addEventListener('click', () => {
        if (selectedRoad) {
          deleteRoad(selectedRoad.id);
        }
      });

      // Add road button
      document.getElementById('addRoadBtn').addEventListener('click', () => {
        editingRoadId = null;
        document.getElementById('modalTitle').textContent = 'Add New Road';
        document.getElementById('roadForm').reset();
        document.getElementById('roadModal').classList.add('open');
      });

      // Toggle edit mode
      document.getElementById('toggleEditMode').addEventListener('click', () => {
        isEditMode = !isEditMode;
        const button = document.getElementById('toggleEditMode');
        
        if (isEditMode) {
          button.innerHTML = '<i class="fa fa-check"></i> Save Edits';
          button.style.background = 'var(--green)';
          button.style.color = 'white';
          showToast('Edit mode enabled. Click on roads to edit them.');
        } else {
          button.innerHTML = '<i class="fa fa-edit"></i> Edit Mode';
          button.style.background = 'white';
          button.style.color = 'var(--dark-green)';
          showToast('Edit mode disabled.');
        }
      });

      // Save road form
      document.getElementById('roadForm').addEventListener('submit', e => {
        e.preventDefault();
        
        const name = document.getElementById('roadName').value;
        const condition = document.getElementById('roadCondition').value;
        const length = document.getElementById('roadLength').value;
        const pci = document.getElementById('roadPCI').value;
        const lastScanned = document.getElementById('roadLastScanned').value;
        const notes = document.getElementById('roadNotes').value;
        
        if (!name || !condition || !length || !lastScanned) {
          showToast('Please fill in all required fields', true);
          return;
        }
        
        if (editingRoadId) {
          // Update existing road
          const index = roads.findIndex(r => r.id === editingRoadId);
          if (index !== -1) {
            roads[index] = {
              ...roads[index],
              name,
              condition,
              length,
              pci: parseInt(pci) || 0,
              lastScanned,
              notes
            };
            
            showToast(`"${name}" updated successfully`);
          }
        } else {
          // Add new road
          const newId = roads.length > 0 ? Math.max(...roads.map(r => r.id)) + 1 : 1;
          const newRoad = {
            id: newId,
            name,
            condition,
            length,
            pci: parseInt(pci) || 0,
            lastScanned,
            notes,
            latlngs: [[-22.57, 17.08], [-22.58, 17.09]] // Default coordinates
          };
          
          roads.push(newRoad);
          showToast(`"${name}" added successfully`);
        }
        
        saveRoads();
        renderRoads();
        document.getElementById('roadModal').classList.remove('open');
      });

      // Cancel road form
      document.getElementById('cancelRoadBtn').addEventListener('click', () => {
        document.getElementById('roadModal').classList.remove('open');
      });

      // Apply filters
      document.getElementById('applyFilters').addEventListener('click', () => {
        const condition = document.getElementById('conditionFilter').value;
        const startDate = document.getElementById('startDate').value;
        const endDate = document.getElementById('endDate').value;
        const town = document.getElementById('townFilter').value;
        const street = document.getElementById('streetFilter').value;
        
        // In a real application, this would filter the roads based on the selected criteria
        showToast(`Filters applied: ${condition !== 'all' ? 'Condition=' + condition : ''} ${town !== 'all' ? 'Town=' + town : ''} ${street ? 'Street=' + street : ''}`);
      });

      // Clear filters
      document.getElementById('clearFilters').addEventListener('click', () => {
        document.getElementById('conditionFilter').value = 'all';
        document.getElementById('startDate').value = '';
        document.getElementById('endDate').value = '';
        document.getElementById('townFilter').value = 'all';
        document.getElementById('streetFilter').value = '';
        showToast('Filters cleared');
      });

      // Search functionality
      document.getElementById('mapSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        if (searchTerm.length > 2) {
          const foundRoads = roads.filter(road => 
            road.name.toLowerCase().includes(searchTerm)
          );
          
          if (foundRoads.length > 0) {
            // In a real application, this would highlight the found roads
            showToast(`Found ${foundRoads.length} road(s) matching "${searchTerm}"`);
          }
        }
      });

      // Custom zoom
      document.getElementById('zoomIn').addEventListener('click', () => map.zoomIn());
      document.getElementById('zoomOut').addEventListener('click', () => map.zoomOut());

      // Logout
      document.getElementById('logoutBtn').addEventListener('click', () => {
        if (confirm('Are you sure you want to logout?')) {
          alert('Logout successful. Redirecting to login page.');
          // In a real application, this would redirect to the login page
        }
      });
    }

    // Edit road
    function editRoad(road) {
      editingRoadId = road.id;
      document.getElementById('modalTitle').textContent = 'Edit Road';
      document.getElementById('roadId').value = road.id;
      document.getElementById('roadName').value = road.name;
      document.getElementById('roadCondition').value = road.condition;
      document.getElementById('roadLength').value = road.length;
      document.getElementById('roadPCI').value = road.pci;
      document.getElementById('roadLastScanned').value = road.lastScanned;
      document.getElementById('roadNotes').value = road.notes || '';
      
      document.getElementById('roadModal').classList.add('open');
      document.getElementById('roadDetails').style.display = 'none';
    }

    // Delete road
    function deleteRoad(id) {
      const road = roads.find(r => r.id === id);
      if (!road) return;
      
      if (confirm(`Are you sure you want to delete "${road.name}"? This action cannot be undone.`)) {
        roads = roads.filter(r => r.id !== id);
        saveRoads();
        renderRoads();
        document.getElementById('roadDetails').style.display = 'none';
        showToast(`"${road.name}" has been deleted`);
      }
    }

    // Initialize the application
    document.addEventListener('DOMContentLoaded', init);
  </script>
</body>
</html>