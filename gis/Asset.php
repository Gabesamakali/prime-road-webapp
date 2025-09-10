<?php
require_once 'db.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Road Asset Inventory | Prime Roads</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets.css">
</head>
<body>
  <div class="container">
    
    <!-- Sidebar -->
       <?php include 'sidebar.php'; ?>

        <!-- Main Section -->
        <div class="main">
          
          <!-- Topbar -->
          <header class="topbar">
            <div class="search">
              <input type="text" id="searchInput" placeholder="Search roads...">
            </div>
            <div class="icons">
              <i class="fa fa-bell"></i>
              <i class="fa fa-user-circle"></i>
            </div>
          </header>

          <!-- Content -->
          <main class="content">
            <h2>Road Asset Inventory</h2>

            <!-- Action Buttons -->
            <div class="buttons">
              <button class="upload-btn" id="uploadBtn"><i class="fa fa-upload"></i> Upload Asset File</button>
              <button class="add-btn" id="addBtn"><i class="fa fa-plus"></i> Add Road Segment</button>
              <button class="upload-btn" id="exportBtn"><i class="fa fa-download"></i> Export Data</button>
              <button class="upload-btn" id="refreshBtn"><i class="fa fa-refresh"></i> Refresh</button>
            </div>

            <!-- Data Table -->
            <div class="table-container">
              <table id="roadTable">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Length</th>
                    <th>Width</th>
                    <th>Surface Type</th>
                    <th>Sidewalks</th>
                    <th>Last Scanned</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="roadTableBody">
                  <!-- Data will be populated dynamically -->
                </tbody>
              </table>

              <!-- Empty State -->
              <div id="emptyState" class="empty-state" style="display: none;">
                <i class="fa fa-road"></i>
                <p>No road assets found. Add your first road segment to get started.</p>
                <button class="add-btn" id="addFirstBtn">Add Road Segment</button>
              </div>

              <!-- Pagination -->
              <div class="pagination" id="pagination"></div>
            </div>
          
        </div>                 
  

      <!-- Add/Edit Road Modal -->
      <div class="modal" id="roadModal">
        <div class="modal-content">
          <h3 id="modalTitle">Add Road Segment</h3>
          <form id="roadForm">
            <input type="hidden" id="roadId">

            <label for="name">Road Name</label>
            <input type="text" id="name" required placeholder="Enter road name">

            <div class="form-row">
              <div>
                <label for="length">Length (km)</label>
                <input type="number" id="length" step="0.01" required placeholder="0.00" min="0">
              </div>
              <div>
                <label for="width">Width (m)</label>
                <input type="number" id="width" step="0.1" required placeholder="0.0" min="0">
              </div>
            </div>

            <label for="surface">Surface Type</label>
            <select id="surface" required>
              <option value="">Select surface type</option>
              <option>Asphalt</option>
              <option>Concrete</option>
              <option>Gravel</option>
              <option>Other</option>
            </select>

            <label for="sidewalks">Sidewalks</label>
            <select id="sidewalks" required>
              <option value="">Select option</option>
              <option>Yes</option>
              <option>No</option>
              <option>Partial</option>
            </select>

            <label for="lastScanned">Last Scanned Date</label>
            <input type="date" id="lastScanned" required>

            <div class="modal-actions">
              <button type="button" class="cancel-btn" id="cancelBtn">Cancel</button>
              <button type="submit" class="save-btn">Save Road</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Upload Modal -->
      <div class="modal" id="uploadModal">
        <div class="modal-content">
          <h3>Upload Asset File</h3>
          <form id="uploadForm">
            <label for="assetFile">Select File</label>
            <input type="file" id="assetFile" required>

            <label for="fileType">File Type</label>
            <select id="fileType" required>
              <option value="">Select file type</option>
              <option>CSV</option>
              <option>Excel</option>
              <option>GeoJSON</option>
            </select>

            <div class="checkbox-row">
              <input type="checkbox" id="overwriteData">
              <label for="overwriteData">Overwrite existing data</label>
            </div>

            <div class="modal-actions">
              <button type="button" class="cancel-btn" id="cancelUploadBtn">Cancel</button>
              <button type="submit" class="save-btn">Upload File</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Detail Modal -->
      <div class="modal" id="detailModal">
        <div class="modal-content">
          <h3 id="detailTitle">Road Segment Details</h3>
          <div id="detailContent"></div>
          <div class="modal-actions">
            <button type="button" class="cancel-btn" id="closeDetailBtn">Close</button>
            <button type="button" class="save-btn" id="editDetailBtn">Edit</button>
          </div>
        </div>
      </div>

      <!-- Toast Notification -->
      <div class="toast" id="toast">
        <i class="fa fa-check-circle"></i>
        <span id="toastMessage">Operation completed successfully</span>
      </div>
  </main>
</div>

  <script>
    const STORAGE_KEY = "road_assets";
    let roads = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
    let editingId = null;
    let currentPage = 1;
    const itemsPerPage = 5;
    let filteredRoads = [];

    const roadTableBody = document.getElementById("roadTableBody");
    const roadModal = document.getElementById("roadModal");
    const uploadModal = document.getElementById("uploadModal");
    const detailModal = document.getElementById("detailModal");
    const modalTitle = document.getElementById("modalTitle");
    const roadForm = document.getElementById("roadForm");
    const uploadForm = document.getElementById("uploadForm");
    const pagination = document.getElementById("pagination");
    const emptyState = document.getElementById("emptyState");
    const toast = document.getElementById("toast");

    // Initialize the application
    function init() {
      if (roads.length === 0) {
        // Add sample data if empty
        roads = [
          { id: 1, name: "Main Street", length: "2.5", width: "12", surface: "Asphalt", sidewalks: "Yes", lastScanned: "2023-10-15" },
          { id: 2, name: "Oak Avenue", length: "3.2", width: "10", surface: "Concrete", sidewalks: "No", lastScanned: "2023-09-22" },
          { id: 3, name: "Pine Road", length: "1.8", width: "8", surface: "Gravel", sidewalks: "No", lastScanned: "2023-11-05" },
          { id: 4, name: "Elm Boulevard", length: "4.5", width: "16", surface: "Asphalt", sidewalks: "Yes", lastScanned: "2023-08-17" },
          { id: 5, name: "Maple Drive", length: "2.1", width: "9", surface: "Asphalt", sidewalks: "Yes", lastScanned: "2023-12-01" },
          { id: 6, name: "Cedar Lane", length: "1.2", width: "7", surface: "Gravel", sidewalks: "Partial", lastScanned: "2023-11-20" },
          { id: 7, name: "Birch Street", length: "3.5", width: "11", surface: "Concrete", sidewalks: "Yes", lastScanned: "2023-10-05" }
        ];
        saveRoads();
      }
      
      filteredRoads = [...roads];
      renderTable();
      setupEventListeners();
    }

    // Save roads to localStorage
    function saveRoads() {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(roads));
    }

    // Render the table with current data
    function renderTable() {
      roadTableBody.innerHTML = "";
      
      if (filteredRoads.length === 0) {
        emptyState.style.display = "block";
        document.getElementById("roadTable").style.display = "none";
        pagination.innerHTML = "";
        return;
      }
      
      emptyState.style.display = "none";
      document.getElementById("roadTable").style.display = "table";
      
      // Calculate pagination
      const totalPages = Math.ceil(filteredRoads.length / itemsPerPage);
      const startIndex = (currentPage - 1) * itemsPerPage;
      const endIndex = Math.min(startIndex + itemsPerPage, filteredRoads.length);
      
      // Render current page items
      for (let i = startIndex; i < endIndex; i++) {
        const road = filteredRoads[i];
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${road.name}</td>
          <td>${road.length} km</td>
          <td>${road.width} m</td>
          <td>${road.surface}</td>
          <td>${road.sidewalks}</td>
          <td>${formatDate(road.lastScanned)}</td>
          <td class="actions">
            <button class="view" onclick="viewRoad(${road.id})" title="View details"><i class="fa fa-eye"></i></button>
            <button class="edit" onclick="editRoad(${road.id})" title="Edit"><i class="fa fa-edit"></i></button>
            <button class="delete" onclick="deleteRoad(${road.id})" title="Delete"><i class="fa fa-trash"></i></button>
          </td>
        `;
        roadTableBody.appendChild(tr);
      }
      
      // Render pagination
      renderPagination(totalPages);
    }

    // Format date for display
    function formatDate(dateString) {
      const options = { year: 'numeric', month: 'short', day: 'numeric' };
      return new Date(dateString).toLocaleDateString(undefined, options);
    }

    // Render pagination controls
    function renderPagination(totalPages) {
      pagination.innerHTML = "";
      
      if (totalPages <= 1) return;
      
      // Previous button
      if (currentPage > 1) {
        const prevBtn = document.createElement("button");
        prevBtn.innerHTML = "&laquo; Prev";
        prevBtn.addEventListener("click", () => {
          currentPage--;
          renderTable();
        });
        pagination.appendChild(prevBtn);
      }
      
      // Page buttons
      const startPage = Math.max(1, currentPage - 2);
      const endPage = Math.min(totalPages, startPage + 4);
      
      for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement("button");
        pageBtn.textContent = i;
        if (i === currentPage) {
          pageBtn.classList.add("active");
        }
        pageBtn.addEventListener("click", () => {
          currentPage = i;
          renderTable();
        });
        pagination.appendChild(pageBtn);
      }
      
      // Next button
      if (currentPage < totalPages) {
        const nextBtn = document.createElement("button");
        nextBtn.innerHTML = "Next &raquo;";
        nextBtn.addEventListener("click", () => {
          currentPage++;
          renderTable();
        });
        pagination.appendChild(nextBtn);
      }
    }

    // Show toast notification
    function showToast(message, isError = false) {
      const toast = document.getElementById("toast");
      const toastMessage = document.getElementById("toastMessage");
      
      toastMessage.textContent = message;
      
      if (isError) {
        toast.style.background = "var(--red)";
        toast.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + toast.innerHTML;
      } else {
        toast.style.background = "var(--dark-green)";
        toast.innerHTML = '<i class="fa fa-check-circle"></i> ' + toast.innerHTML;
      }
      
      toast.classList.add("show");
      
      setTimeout(() => {
        toast.classList.remove("show");
      }, 3000);
    }

    // View road details
    function viewRoad(id) {
      const road = roads.find(r => r.id === id);
      if (!road) return;
      
      document.getElementById("detailTitle").textContent = road.name;
      document.getElementById("detailContent").innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
          <div><strong>Length:</strong> ${road.length} km</div>
          <div><strong>Width:</strong> ${road.width} m</div>
          <div><strong>Surface Type:</strong> ${road.surface}</div>
          <div><strong>Sidewalks:</strong> ${road.sidewalks}</div>
          <div><strong>Last Scanned:</strong> ${formatDate(road.lastScanned)}</div>
          <div><strong>ID:</strong> ${road.id}</div>
        </div>
        <div>
          <strong>Additional Information:</strong>
          <p style="margin-top: 5px; color: #666;">No additional information available. Add notes in the edit view.</p>
        </div>
      `;
      
      document.getElementById("editDetailBtn").onclick = () => {
        detailModal.classList.remove("open");
        editRoad(id);
      };
      
      detailModal.classList.add("open");
    }

    // Edit road
    function editRoad(id) {
      const road = roads.find(r => r.id === id);
      if (!road) return;
      
      editingId = id;
      modalTitle.textContent = "Edit Road Segment";
      document.getElementById("roadId").value = road.id;
      document.getElementById("name").value = road.name;
      document.getElementById("length").value = road.length;
      document.getElementById("width").value = road.width;
      document.getElementById("surface").value = road.surface;
      document.getElementById("sidewalks").value = road.sidewalks;
      document.getElementById("lastScanned").value = road.lastScanned;
      
      roadModal.classList.add("open");
    }

    // Delete road
    function deleteRoad(id) {
      const road = roads.find(r => r.id === id);
      if (!road) return;
      
      if (confirm(`Are you sure you want to delete "${road.name}"? This action cannot be undone.`)) {
        roads = roads.filter(r => r.id !== id);
        filteredRoads = filteredRoads.filter(r => r.id !== id);
        saveRoads();
        renderTable();
        showToast(`"${road.name}" has been deleted successfully`);
      }
    }

    // Export data to CSV
    function exportData() {
      if (roads.length === 0) {
        showToast("No data to export", true);
        return;
      }
      
      let csv = "Name,Length,Width,Surface Type,Sidewalks,Last Scanned\n";
      roads.forEach(road => {
        csv += `"${road.name}",${road.length},${road.width},${road.surface},${road.sidewalks},${road.lastScanned}\n`;
      });
      
      const blob = new Blob([csv], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "road_assets_export.csv";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      
      showToast("Data exported successfully");
    }

    // Setup event listeners
    function setupEventListeners() {
      // Open modal for Add
      document.getElementById("addBtn").addEventListener("click", () => {
        editingId = null;
        modalTitle.textContent = "Add Road Segment";
        roadForm.reset();
        roadModal.classList.add("open");
      });
      
      // Add first road button
      document.getElementById("addFirstBtn").addEventListener("click", () => {
        editingId = null;
        modalTitle.textContent = "Add Road Segment";
        roadForm.reset();
        roadModal.classList.add("open");
      });

      // Open upload modal
      document.getElementById("uploadBtn").addEventListener("click", () => {
        uploadModal.classList.add("open");
      });
      
      // Export button
      document.getElementById("exportBtn").addEventListener("click", exportData);
      
      // Refresh button
      document.getElementById("refreshBtn").addEventListener("click", () => {
        filteredRoads = [...roads];
        currentPage = 1;
        document.getElementById("searchInput").value = "";
        renderTable();
        showToast("Data refreshed");
      });

      // Cancel buttons
      document.getElementById("cancelBtn").addEventListener("click", () => {
        roadModal.classList.remove("open");
      });
      
      document.getElementById("cancelUploadBtn").addEventListener("click", () => {
        uploadModal.classList.remove("open");
      });
      
      document.getElementById("closeDetailBtn").addEventListener("click", () => {
        detailModal.classList.remove("open");
      });

      // Save form
      roadForm.addEventListener("submit", e => {
        e.preventDefault();
        const newRoad = {
          id: editingId || Date.now(),
          name: document.getElementById("name").value,
          length: document.getElementById("length").value,
          width: document.getElementById("width").value,
          surface: document.getElementById("surface").value,
          sidewalks: document.getElementById("sidewalks").value,
          lastScanned: document.getElementById("lastScanned").value
        };
        
        if (editingId) {
          // Update existing road
          roads = roads.map(r => r.id === editingId ? newRoad : r);
          showToast(`"${newRoad.name}" updated successfully`);
        } else {
          // Add new road
          roads.push(newRoad);
          showToast(`"${newRoad.name}" added successfully`);
        }
        
        saveRoads();
        filteredRoads = [...roads];
        renderTable();
        roadModal.classList.remove("open");
      });

      // Upload form
      uploadForm.addEventListener("submit", e => {
        e.preventDefault();
        // Simulate file upload
        setTimeout(() => {
          uploadModal.classList.remove("open");
          showToast("File uploaded successfully. Data will be processed shortly.");
        }, 1500);
      });

      // Search filter
      document.getElementById("searchInput").addEventListener("input", function() {
        const filter = this.value.toLowerCase();
        if (filter) {
          filteredRoads = roads.filter(road => 
            road.name.toLowerCase().includes(filter) ||
            road.surface.toLowerCase().includes(filter) ||
            road.sidewalks.toLowerCase().includes(filter)
          );
        } else {
          filteredRoads = [...roads];
        }
        currentPage = 1;
        renderTable();
      });

      // Logout
      document.getElementById("logoutBtn").addEventListener("click", () => {
        if (confirm("Are you sure you want to logout?")) {
          alert("Logout successful. Redirecting to login page.");
          // In a real application, this would redirect to the login page
        }
      });
    }

    // Initialize the application
    document.addEventListener("DOMContentLoaded", init);
  </script>

</body>
</html>