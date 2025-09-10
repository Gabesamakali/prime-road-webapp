<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Road Defect Detection System</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Navigation -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">

            <div class="topbar">
                
                  
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
        
    
            <div style="flex:1; padding:20px;">
            <hr class="section-divider">

            

            <!-- Upload Manager Section -->
            <section id="upload-section" class="content-section" style="display: none;">
                <h1 class="page-title">Upload Manager</h1>
                
                <div class="upload-container">
                    <div class="upload-area" id="upload-area">
                        <img src="Upload.png" alt="Upload" class="upload-icon">
                        <h3>Drag & Drop Images or Videos Here</h3>
                        <p>or click to select files</p>
                        <input type="file" id="file-input" multiple accept="image/*,video/*" hidden>
                    </div>
                    
                    <div class="video-settings" id="video-settings">
                        <div class="setting-item">
                            <label for="fps-input">FPS sample</label>
                            <input type="number" id="fps-input" min="1" max="10" step="1" value="2">
                        </div>
                        <div class="setting-item">
                            <label for="maxframes-input">Max frames</label>
                            <input type="number" id="maxframes-input" min="1" max="100" step="1" value="20">
                        </div>
                        <div class="setting-item toggle">
                            <label for="timeline-toggle">Show timeline</label>
                            <input type="checkbox" id="timeline-toggle" checked>
                        </div>
                    </div>
                    
                    <div class="upload-progress" id="upload-progress" style="display: none;">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progress-fill"></div>
                        </div>
                        <p id="progress-text">Uploading...</p>
                    </div>
                </div>

                <div class="uploaded-files" id="uploaded-files"></div>
            </section>

            <!-- Defect Detection Review Section -->
            <section id="defect-review-section" class="content-section">
                <div class="section-header">
                    <h1 class="page-title">Defect Detection Review</h1>
                    
                    <div class="header-controls">
                        <div class="filter-controls">
                            <span class="filter-label">filter by:</span>
                            <select id="defect-type-filter" class="filter-select">
                                <option value="">Defect type</option>
                                <option value="crack">Crack</option>
                                <option value="pothole">Pothole</option>
                                <option value="rutting">Rutting</option>
                                <option value="patching">Patching failure</option>
                            </select>
                            <select id="severity-filter" class="filter-select">
                                <option value="">Severity level</option>
                                <option value="severe">Severe</option>
                                <option value="moderate">Moderate</option>
                                <option value="minor">Minor</option>
                            </select>
                        </div>
                        
                        <button class="download-btn" id="download-dataset">
                            <img src="download.png" alt="Download" style="width: 16px; height: 16px; margin-right: 8px;"> Download annotated dataset
                        </button>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="defects-grid" id="defects-grid">
                        <!-- Defect images will be populated here -->
                    </div>
                    
                    <div class="info-panel">
                        <div class="analysis-form-card">
                            <div class="pci-score-card" style="margin-bottom: 20px;">
                                <h3 class="card-title pci-title">PCI Score</h3>
                                <div class="score-value" id="pci-score">62</div>
                                <div class="score-details">
                                    <div class="score-condition">Condition: <span id="pci-condition">Fair</span></div>
                                    <div class="score-indicator">Indicator: <span id="pci-indicator">Yellow</span></div>
                                </div>
                                <div class="score-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="pci-progress" style="width: 62%"></div>
                                    </div>
                                    <span class="progress-text" id="pci-progress-text">76% complete</span>
                                </div>
                            </div>

                            <div class="segment-info-card">
                                <h3 class="card-title segment-title">Segment Info</h3>
                                <div class="segment-details">
                                    <div class="segment-item">
                                        <span class="segment-label">Segment ID:</span>
                                        <span class="segment-value" id="segment-id">A-22</span>
                                    </div>
                                    <div class="segment-item">
                                        <span class="segment-label">Segment Name:</span>
                                        <span class="segment-value" id="segment-name">Sam Nujoma Drive</span>
                                    </div>
                                    <div class="segment-item">
                                        <span class="segment-label">Segment Length:</span>
                                        <span class="segment-value" id="segment-length">350m</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Image Analysis Modal -->
    <div id="analysis-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Analyzing Image...</h3>
                <span class="close-modal" id="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <div class="analysis-progress">
                    <div class="spinner"></div>
                    <p id="analysis-status">Processing image for defect detection...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
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

    // Simple navigation handler for sidebar links
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Navigation script loaded');
        
        // Function to switch between sections
        function switchToSection(sectionId) {
            console.log('Switching to section:', sectionId);
            
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.style.display = 'none';
            });
            
            // Remove active class from all sidebar links
            const sidebarLinks = document.querySelectorAll('.sidebar a');
            sidebarLinks.forEach(link => {
                link.classList.remove('active');
            });
            
            // Show target section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.style.display = 'block';
            }
            
            // Add active class to appropriate link
            if (sectionId === 'upload-section') {
                const uploadLink = document.getElementById('upload-manager-link');
                if (uploadLink) {
                    uploadLink.classList.add('active');
                    console.log('Upload link activated');
                }
            } else if (sectionId === 'defect-review-section') {
                const defectLink = document.getElementById('defect-review-link');
                if (defectLink) {
                    defectLink.classList.add('active');
                    console.log('Defect link activated');
                }
            }
        }
        
        // Handle initial page load based on hash
        function handleInitialLoad() {
            const hash = window.location.hash;
            console.log('Initial hash:', hash);
            
            if (hash === '#upload') {
                switchToSection('upload-section');
            } else if (hash === '#defect-review') {
                switchToSection('defect-review-section');
            } else {
                // Default to defect-review
                switchToSection('defect-review-section');
            }
        }
        
        // Add click handlers to sidebar links
        const uploadLink = document.getElementById('upload-manager-link');
        const defectLink = document.getElementById('defect-review-link');
        
        if (uploadLink) {
            uploadLink.addEventListener('click', function(e) {
                e.preventDefault();
                switchToSection('upload-section');
                history.pushState(null, null, '#upload');
            });
        }
        
        if (defectLink) {
            defectLink.addEventListener('click', function(e) {
                e.preventDefault();
                switchToSection('defect-review-section');
                history.pushState(null, null, '#defect-review');
            });
        }
        
        // Handle browser back/forward buttons
        window.addEventListener('hashchange', function() {
            handleInitialLoad();
        });
        
        // Initialize on page load
        handleInitialLoad();
    });
    </script>
</body>
</html>