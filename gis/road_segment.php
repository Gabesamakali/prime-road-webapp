<?php
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Road Segment Detail | Prime Roads</title>
  
  <!-- CSS -->
  <link rel="stylesheet" href="segment.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="container">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="content">

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

      <div style="flex:1; padding:20px;">
        <hr class="section-divider">
      

      <!-- Page Title + Actions -->
      <div class="content">
        <h1 class="title">Road Segment Details</h1>
        <div class="action-buttons">
          <button class="btn btn-primary" id="openMaintenance">Schedule Maintenance</button>
          <button class="btn btn-secondary" id="openNote">Add Note</button>
          <button class="btn btn-secondary" id="exportReport">Export Segment Report</button>
          <button class="btn btn-secondary" id="addDefect">Add Defect</button>
          <button class="btn btn-secondary" id="editSegment">Edit Segment Info</button>
        </div>

        <!-- Segment Info -->
        <section class="segment-info">
          <div class="panel-title">
            Segment Info
            <div class="panel-actions">
              <button id="editSegmentBtn"><i class="fas fa-edit"></i> Edit</button>
            </div>
          </div>
          <div class="info-grid">
            <div><div class="info-label">Segment ID</div><div class="info-value editable" data-field="id">A22</div></div>
            <div><div class="info-label">Length</div><div class="info-value editable" data-field="length">36km</div></div>
            <div><div class="info-label">Width</div><div class="info-value editable" data-field="width">32m</div></div>
            <div><div class="info-label">Surface</div><div class="info-value editable" data-field="surface">Asphalt</div></div>
            <div><div class="info-label">PCI</div><div class="info-value" id="pciValue">34</div></div>
            <div><div class="info-label">Last Updated</div><div class="info-value editable" data-field="lastUpdated">01 January 2024</div></div>
          </div>
        </section>

        <!-- Defects -->
        <section class="defects-list">
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
            <tbody></tbody>
          </table>
        </section>

        <!-- Maintenance -->
        <section class="maintenance-list">
          <div class="panel-title">
            Maintenance History
            <div class="panel-actions">
              <button id="addMaintenanceBtn"><i class="fas fa-plus"></i> Schedule Maintenance</button>
            </div>
          </div>
          <div id="maintenanceContainer"></div>
        </section>

        <!-- PCI -->
        <section class="pci-breakdown">
          <div class="panel-title">PCI Calculation Breakdown</div>
          <div class="pci-score" id="pciScore">34</div>
          <div class="pci-bar"><div class="pci-progress" id="pciBar" style="width:34%; background:#c82333;"></div></div>
          <div class="pci-categories">
            <div><div class="info-label">Cracking</div><div class="info-value">15%</div></div>
            <div><div class="info-label">Rutting</div><div class="info-value">8%</div></div>
            <div><div class="info-label">Patching</div><div class="info-value">6%</div></div>
            <div><div class="info-label">Raveling</div><div class="info-value">5%</div></div>
          </div>
        </section>

        <!-- Notes -->
        <section class="notes-section" id="notesSection">
          <div class="panel-title">
            Notes
            <div class="panel-actions">
              <button id="addNoteBtn"><i class="fas fa-plus"></i> Add Note</button>
            </div>
          </div>
          <div id="notesContainer"></div>
        </section>
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



  // Data storage
  const SEGMENT_STORAGE_KEY = "road_segment_data";
  const DEFECTS_STORAGE_KEY = "road_defects_data";
  const NOTES_STORAGE_KEY = "road_notes_data";
  const MAINTENANCE_STORAGE_KEY = "road_maintenance_data";

  // Initialize data
  let segmentData = JSON.parse(localStorage.getItem(SEGMENT_STORAGE_KEY)) || {
    id: "A22",
    length: "36km",
    width: "32m",
    surface: "Asphalt",
    pci: 34,
    lastUpdated: "01 January 2024"
  };

  let defectsData = JSON.parse(localStorage.getItem(DEFECTS_STORAGE_KEY)) || [
    {id: 1, type: "Crack", severity: "moderate", position: "23m from start", description: "Sample crack description"},
    {id: 2, type: "Particle", severity: "severe", position: "73m center", description: "Sample particle description"},
    {id: 3, type: "Edge break", severity: "minor", position: "200m right", description: "Sample edge break description"}
  ];

  let notesData = JSON.parse(localStorage.getItem(NOTES_STORAGE_KEY)) || [
    {id: 1, author: "John Doe", content: "Scheduled inspection for next week. Need to prioritize edge break repairs.", date: "2023-11-15"},
    {id: 2, author: "Jane Smith", content: "Previous maintenance completed. Crack sealing performed on sections with moderate damage.", date: "2023-10-28"}
  ];

  let maintenanceData = JSON.parse(localStorage.getItem(MAINTENANCE_STORAGE_KEY)) || [
    {id: 1, type: "Crack Sealing", date: "2023-10-20", notes: "Sealed major cracks along the segment"},
    {id: 2, type: "Surface Patching", date: "2023-09-05", notes: "Patched surface damage at multiple locations"}
  ];

  // Save data to localStorage
  function saveData() {
    localStorage.setItem(SEGMENT_STORAGE_KEY, JSON.stringify(segmentData));
    localStorage.setItem(DEFECTS_STORAGE_KEY, JSON.stringify(defectsData));
    localStorage.setItem(NOTES_STORAGE_KEY, JSON.stringify(notesData));
    localStorage.setItem(MAINTENANCE_STORAGE_KEY, JSON.stringify(maintenanceData));
  }

  // Initialize page
  function initializePage() {
    renderSegmentInfo();
    renderDefects();
    renderNotes();
    renderMaintenance();
    updatePciDisplay();
  }

  // Render segment information
  function renderSegmentInfo() {
    document.querySelector('[data-field="id"]').textContent = segmentData.id;
    document.querySelector('[data-field="length"]').textContent = segmentData.length;
    document.querySelector('[data-field="width"]').textContent = segmentData.width;
    document.querySelector('[data-field="surface"]').textContent = segmentData.surface;
    document.querySelector('[data-field="lastUpdated"]').textContent = segmentData.lastUpdated;
    document.getElementById('pciValue').textContent = segmentData.pci;
  }

  // Render defects table
  function renderDefects() {
    const tbody = document.querySelector('#defectsTable tbody');
    tbody.innerHTML = '';
    
    defectsData.forEach(defect => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${defect.id}</td>
        <td>${defect.type}</td>
        <td class="severity-${defect.severity}">${defect.severity.charAt(0).toUpperCase() + defect.severity.slice(1)}</td>
        <td>${defect.position}</td>
        <td>
          <button class="btn btn-view view-defect" data-id="${defect.id}">View</button>
          <button class="btn btn-edit edit-defect" data-id="${defect.id}">Edit</button>
          <button class="btn btn-delete delete-defect" data-id="${defect.id}">Delete</button>
        </td>
      `;
      tbody.appendChild(row);
    });
    
    // Add event listeners
    addDefectEventListeners();
  }

  // Render notes
  function renderNotes() {
    const container = document.getElementById('notesContainer');
    container.innerHTML = '';
    
    notesData.forEach(note => {
      const noteElement = document.createElement('div');
      noteElement.className = 'note-item';
      noteElement.innerHTML = `
        <div class="note-header">
          <strong>${note.author}</strong> (${note.date})
          <div class="note-actions">
            <button class="edit-note" data-id="${note.id}"><i class="fas fa-edit"></i></button>
            <button class="delete-note" data-id="${note.id}"><i class="fas fa-trash"></i></button>
          </div>
        </div>
        <div>${note.content}</div>
      `;
      container.appendChild(noteElement);
    });
    
    // Add event listeners
    addNoteEventListeners();
  }

  // Render maintenance
  function renderMaintenance() {
    const container = document.getElementById('maintenanceContainer');
    container.innerHTML = '';
    
    maintenanceData.forEach(maintenance => {
      const maintenanceElement = document.createElement('div');
      maintenanceElement.className = 'maintenance-item';
      maintenanceElement.innerHTML = `
        <div><strong>${maintenance.type}</strong> - ${maintenance.date}</div>
        <div>${maintenance.notes}</div>
        <div style="margin-top: 5px;">
          <button class="btn-edit edit-maintenance" data-id="${maintenance.id}">Edit</button>
          <button class="btn-delete delete-maintenance" data-id="${maintenance.id}">Delete</button>
        </div>
      `;
      container.appendChild(maintenanceElement);
    });
    
    // Add event listeners
    addMaintenanceEventListeners();
  }

  // Update PCI display
  function updatePciDisplay() {
    const pciScore = segmentData.pci;
    const pciBar = document.getElementById('pciBar');
    
    document.getElementById('pciScore').textContent = pciScore;
    
    if(pciScore >= 80) {
      pciBar.style.background = 'green';
    } else if(pciScore >= 50) {
      pciBar.style.background = 'orange';
    } else {
      pciBar.style.background = 'red';
    }
    
    pciBar.style.width = `${pciScore}%`;
  }

  // Add defect event listeners
  function addDefectEventListeners() {
    document.querySelectorAll('.view-defect').forEach(button => {
      button.addEventListener('click', function() {
        const defectId = parseInt(this.dataset.id);
        viewDefect(defectId);
      });
    });
    
    document.querySelectorAll('.edit-defect').forEach(button => {
      button.addEventListener('click', function() {
        const defectId = parseInt(this.dataset.id);
        editDefect(defectId);
      });
    });
    
    document.querySelectorAll('.delete-defect').forEach(button => {
      button.addEventListener('click', function() {
        const defectId = parseInt(this.dataset.id);
        deleteDefect(defectId);
      });
    });
  }

  // Add note event listeners
  function addNoteEventListeners() {
    document.querySelectorAll('.edit-note').forEach(button => {
      button.addEventListener('click', function() {
        const noteId = parseInt(this.dataset.id);
        editNote(noteId);
      });
    });
    
    document.querySelectorAll('.delete-note').forEach(button => {
      button.addEventListener('click', function() {
        const noteId = parseInt(this.dataset.id);
        deleteNote(noteId);
      });
    });
  }

  // Add maintenance event listeners
  function addMaintenanceEventListeners() {
    document.querySelectorAll('.edit-maintenance').forEach(button => {
      button.addEventListener('click', function() {
        const maintenanceId = parseInt(this.dataset.id);
        editMaintenance(maintenanceId);
      });
    });
    
    document.querySelectorAll('.delete-maintenance').forEach(button => {
      button.addEventListener('click', function() {
        const maintenanceId = parseInt(this.dataset.id);
        deleteMaintenance(maintenanceId);
      });
    });
  }

  // View defect
  function viewDefect(id) {
    const defect = defectsData.find(d => d.id === id);
    if (!defect) return;
    
    alert(`Defect Details:\nType: ${defect.type}\nSeverity: ${defect.severity}\nPosition: ${defect.position}\nDescription: ${defect.description}`);
  }

  // Edit defect
  function editDefect(id) {
    const defect = defectsData.find(d => d.id === id);
    if (!defect) return;
    
    document.getElementById('defectModalTitle').textContent = 'Edit Defect';
    document.getElementById('defectId').value = defect.id;
    document.getElementById('defectType').value = defect.type;
    document.getElementById('defectSeverity').value = defect.severity;
    document.getElementById('defectPosition').value = defect.position;
    document.getElementById('defectDescription').value = defect.description;
    
    document.getElementById('defectModal').classList.add('open');
  }

  // Delete defect
  function deleteDefect(id) {
    if (confirm('Are you sure you want to delete this defect?')) {
      defectsData = defectsData.filter(d => d.id !== id);
      saveData();
      renderDefects();
    }
  }

  // Edit note
  function editNote(id) {
    const note = notesData.find(n => n.id === id);
    if (!note) return;
    
    document.getElementById('noteModalTitle').textContent = 'Edit Note';
    document.getElementById('noteId').value = note.id;
    document.getElementById('noteText').value = note.content;
    
    document.getElementById('noteModal').classList.add('open');
  }

  // Delete note
  function deleteNote(id) {
    if (confirm('Are you sure you want to delete this note?')) {
      notesData = notesData.filter(n => n.id !== id);
      saveData();
      renderNotes();
    }
  }

  // Edit maintenance
  function editMaintenance(id) {
    const maintenance = maintenanceData.find(m => m.id === id);
    if (!maintenance) return;
    
    document.getElementById('maintenanceModalTitle').textContent = 'Edit Maintenance';
    document.getElementById('maintenanceId').value = maintenance.id;
    document.getElementById('maintenanceType').value = maintenance.type;
    document.getElementById('maintenanceDate').value = maintenance.date;
    document.getElementById('maintenanceNotes').value = maintenance.notes;
    
    document.getElementById('maintenanceModal').classList.add('open');
  }

  // Delete maintenance
  function deleteMaintenance(id) {
    if (confirm('Are you sure you want to delete this maintenance record?')) {
      maintenanceData = maintenanceData.filter(m => m.id !== id);
      saveData();
      renderMaintenance();
    }
  }

  // Edit segment info
  function editSegmentInfo() {
    document.getElementById('segmentId').value = segmentData.id;
    document.getElementById('segmentLength').value = segmentData.length.replace('km', '');
    document.getElementById('segmentWidth').value = segmentData.width.replace('m', '');
    document.getElementById('segmentSurface').value = segmentData.surface;
    document.getElementById('segmentLastUpdated').value = segmentData.lastUpdated.includes('-') ? 
      segmentData.lastUpdated : new Date().toISOString().split('T')[0];
    
    document.getElementById('segmentModal').classList.add('open');
  }

  // Event listeners for modal buttons
  document.getElementById('addDefectBtn').addEventListener('click', () => {
    document.getElementById('defectModalTitle').textContent = 'Add Defect';
    document.getElementById('defectForm').reset();
    document.getElementById('defectId').value = '';
    document.getElementById('defectModal').classList.add('open');
  });

  document.getElementById('addNoteBtn').addEventListener('click', () => {
    document.getElementById('noteModalTitle').textContent = 'Add Note';
    document.getElementById('noteForm').reset();
    document.getElementById('noteId').value = '';
    document.getElementById('noteModal').classList.add('open');
  });

  document.getElementById('addMaintenanceBtn').addEventListener('click', () => {
    document.getElementById('maintenanceModalTitle').textContent = 'Schedule Maintenance';
    document.getElementById('maintenanceForm').reset();
    document.getElementById('maintenanceId').value = '';
    document.getElementById('maintenanceModal').classList.add('open');
  });

  document.getElementById('editSegmentBtn').addEventListener('click', editSegmentInfo);

  // Save defect
  document.getElementById('saveDefect').addEventListener('click', () => {
    const id = document.getElementById('defectId').value;
    const type = document.getElementById('defectType').value.trim();
    const severity = document.getElementById('defectSeverity').value;
    const position = document.getElementById('defectPosition').value.trim();
    const description = document.getElementById('defectDescription').value.trim();
    
    if (!type || !severity || !position) {
      alert('Please fill in all required fields');
      return;
    }
    
    if (id) {
      // Update existing defect
      const index = defectsData.findIndex(d => d.id === parseInt(id));
      if (index !== -1) {
        defectsData[index] = {id: parseInt(id), type, severity, position, description};
      }
    } else {
      // Add new defect
      const newId = defectsData.length > 0 ? Math.max(...defectsData.map(d => d.id)) + 1 : 1;
      defectsData.push({id: newId, type, severity, position, description});
    }
    
    saveData();
    renderDefects();
    document.getElementById('defectModal').classList.remove('open');
  });

  // Save note
  document.getElementById('saveNote').addEventListener('click', () => {
    const id = document.getElementById('noteId').value;
    const content = document.getElementById('noteText').value.trim();
    
    if (!content) {
      alert('Please enter note content');
      return;
    }
    
    if (id) {
      // Update existing note
      const index = notesData.findIndex(n => n.id === parseInt(id));
      if (index !== -1) {
        notesData[index].content = content;
      }
    } else {
      // Add new note
      const newId = notesData.length > 0 ? Math.max(...notesData.map(n => n.id)) + 1 : 1;
      notesData.push({
        id: newId,
        author: "Current User",
        content,
        date: new Date().toISOString().split('T')[0]
      });
    }
    
    saveData();
    renderNotes();
    document.getElementById('noteModal').classList.remove('open');
  });

  // Save maintenance
  document.getElementById('saveMaintenance').addEventListener('click', () => {
    const id = document.getElementById('maintenanceId').value;
    const type = document.getElementById('maintenanceType').value.trim();
    const date = document.getElementById('maintenanceDate').value;
    const notes = document.getElementById('maintenanceNotes').value.trim();
    
    if (!type || !date) {
      alert('Please fill in all required fields');
      return;
    }
    
    if (id) {
      // Update existing maintenance
      const index = maintenanceData.findIndex(m => m.id === parseInt(id));
      if (index !== -1) {
        maintenanceData[index] = {id: parseInt(id), type, date, notes};
      }
    } else {
      // Add new maintenance
      const newId = maintenanceData.length > 0 ? Math.max(...maintenanceData.map(m => m.id)) + 1 : 1;
      maintenanceData.push({id: newId, type, date, notes});
    }
    
    saveData();
    renderMaintenance();
    document.getElementById('maintenanceModal').classList.remove('open');
  });

  // Save segment info
  document.getElementById('saveSegment').addEventListener('click', () => {
    const id = document.getElementById('segmentId').value.trim();
    const length = document.getElementById('segmentLength').value.trim();
    const width = document.getElementById('segmentWidth').value.trim();
    const surface = document.getElementById('segmentSurface').value;
    const lastUpdated = document.getElementById('segmentLastUpdated').value;
    
    if (!id || !length || !width || !surface || !lastUpdated) {
      alert('Please fill in all required fields');
      return;
    }
    
    // Update segment data
    segmentData.id = id;
    segmentData.length = `${length}km`;
    segmentData.width = `${width}m`;
    segmentData.surface = surface;
    segmentData.lastUpdated = lastUpdated;
    
    saveData();
    renderSegmentInfo();
    document.getElementById('segmentModal').classList.remove('open');
  });

  // Cancel buttons
  document.getElementById('cancelDefect').addEventListener('click', () => {
    document.getElementById('defectModal').classList.remove('open');
  });

  document.getElementById('cancelNote').addEventListener('click', () => {
    document.getElementById('noteModal').classList.remove('open');
  });

  document.getElementById('cancelMaintenance').addEventListener('click', () => {
    document.getElementById('maintenanceModal').classList.remove('open');
  });

  document.getElementById('cancelSegment').addEventListener('click', () => {
    document.getElementById('segmentModal').classList.remove('open');
  });

  // Search functionality
  document.getElementById('searchInput').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#defectsTable tbody tr');
    
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(filter) ? '' : 'none';
    });
  });

  // Export CSV
  document.getElementById('exportReport').addEventListener('click', () => {
    let csv = 'No,Type,Severity,Position\n';
    defectsData.forEach(defect => {
      csv += `${defect.id},${defect.type},${defect.severity},${defect.position}\n`;
    });
    
    const blob = new Blob([csv], {type: 'text/csv'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'segment_defects.csv';
    link.click();
  });

  // Logout
  document.getElementById('logoutBtn').addEventListener('click', () => {
    if (confirm('Are you sure you want to logout?')) {
      alert('Logout successful. Redirecting to login page.');
      // In a real application, this would redirect to the login page
    }
  });




  
  // Initialize the page
  document.addEventListener('DOMContentLoaded', initializePage);




</script>
</body>
</html>