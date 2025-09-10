// ---------- Data from PHP ----------
const SEGMENTS = <?php echo $segments_json; ?>;
const CONTRACTORS = <?php echo $contractors_json; ?>;
const TOWNS = <?php echo $towns_json; ?>;

// Debug: Log parsed data to console
console.log('SEGMENTS:', SEGMENTS);
console.log('CONTRACTORS:', CONTRACTORS);
console.log('TOWNS:', TOWNS);

// ---------- New Segment UI Controls ----------
// Note: newSegmentBtn click handler is now setup inside initMap() function

// Setup cancel button and contractor selection handler
window.addEventListener('load', () => {
    const cancelBtn = document.getElementById('cancelNewSegment');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            document.getElementById('newSegmentCard').style.display = 'none';
            document.getElementById('newSegmentBtn').style.display = 'inline-block';
            document.getElementById('newSegmentForm').reset();
            // Hide contractor fields when canceling
            document.getElementById('newSegmentDates').style.display = 'none';
            document.getElementById('newSegmentCost').style.display = 'none';
        });
    }

    // Show/hide contractor assignment fields for new segments
    const contractorSelect = document.getElementById('newSegmentContractor');
    if (contractorSelect) {
        contractorSelect.addEventListener('change', (e) => {
            const isContractorSelected = e.target.value !== '';
            document.getElementById('newSegmentDates').style.display = isContractorSelected ? 'grid' : 'none';
            document.getElementById('newSegmentCost').style.display = isContractorSelected ? 'block' : 'none';
        });
    }
});

// Debug: Log form submission data
document.getElementById('assignForm').addEventListener('submit', (e) => {
    const formData = new FormData(e.target);
    console.log('Assign form data:', Object.fromEntries(formData));
    
    // Enhanced validation with detailed logging
    const segmentId = formData.get('segment_id');
    const contractorId = formData.get('contractor_id');
    const startDate = formData.get('start_date');
    const endDate = formData.get('end_date');
    const estimatedCost = formData.get('estimated_cost');
    
    console.log('Form validation check:', {
        segmentId: { value: segmentId, valid: !!segmentId },
        contractorId: { value: contractorId, valid: !!contractorId },
        startDate: { value: startDate, valid: !!startDate },
        endDate: { value: endDate, valid: !!endDate },
        estimatedCost: { value: estimatedCost, valid: estimatedCost && parseFloat(estimatedCost) > 0 }
    });
            
    if (!segmentId) {
        e.preventDefault();
        alert('Please select a segment first.');
        console.error('No segment selected');
        return;
    }
    if (!contractorId) {
        e.preventDefault();
        alert('Please select a contractor.');
        console.error('No contractor selected');
        return;
    }
    if (!startDate) {
        e.preventDefault();
        alert('Please select a start date.');
        console.error('No start date selected');
        return;
    }
    if (!endDate) {
        e.preventDefault();
        alert('Please select an end date.');
        console.error('No end date selected');
        return;
    }
            
    // Validate dates
    const startDateObj = new Date(startDate);
    const endDateObj = new Date(endDate);
    if (startDateObj > endDateObj) {
        e.preventDefault();
        alert('Start date cannot be after end date.');
        console.error('Invalid date range:', { startDate, endDate });
        return;
    }
            
    if (!estimatedCost || parseFloat(estimatedCost) <= 0) {
        e.preventDefault();
        alert('Please enter a valid estimated cost.');
        console.error('Invalid estimated cost:', estimatedCost);
        return;
    }
            
    console.log('✅ Form validation passed, submitting assignment...');
});

// New segment form validation
document.getElementById('newSegmentForm').addEventListener('submit', (e) => {
    const formData = new FormData(e.target);
    console.log('New segment form data:', Object.fromEntries(formData));
    
    const location = formData.get('location');
    const severity = formData.get('severity');
    const defectType = formData.get('defect_type');
    const contractorId = formData.get('contractor_id');
    const startDate = formData.get('start_date');
    const endDate = formData.get('end_date');
    const estimatedCost = formData.get('estimated_cost');
    
    if (!location || location.trim() === '') {
        e.preventDefault();
        alert('Please enter a location/address.');
        return;
    }
    if (!severity) {
        e.preventDefault();
        alert('Please select a severity level.');
        return;
    }
    if (!defectType) {
        e.preventDefault();
        alert('Please select a defect type.');
        return;
    }
    
    // If contractor is selected, validate assignment fields
    if (contractorId) {
        if (!startDate) {
            e.preventDefault();
            alert('Please select a start date for the contractor assignment.');
            return;
        }
        if (!endDate) {
            e.preventDefault();
            alert('Please select an end date for the contractor assignment.');
            return;
        }
        
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);
        if (startDateObj > endDateObj) {
            e.preventDefault();
            alert('Start date cannot be after end date.');
            return;
        }
        
        if (!estimatedCost || parseFloat(estimatedCost) <= 0) {
            e.preventDefault();
            alert('Please enter a valid estimated cost.');
            return;
        }
    }
    
    console.log('New segment form validation passed, submitting...');
});

// ---------- Map ----------
let map;
function initMap() {
    try {
        map = L.map('map').setView([-22.5597, 17.0832], 10); // Windhoek, Namibia
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        let currentMarker = null;

        function placeMarker(lat, lng, html, isAssigned = false) {
            if (currentMarker) { map.removeLayer(currentMarker); }
            
            // Create custom icon based on assignment status
            const iconOptions = {
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            };
            
            let customIcon;
            if (isAssigned) {
                // Green icon for assigned segments
                customIcon = L.icon({
                    ...iconOptions,
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png'
                });
            } else {
                // Red icon for unassigned segments
                customIcon = L.icon({
                    ...iconOptions,
                    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png'
                });
            }
            
            currentMarker = L.marker([lat, lng], { icon: customIcon }).addTo(map)
                .bindPopup(html)
                .openPopup();
            map.setView([lat, lng], 14);
        }

        async function geocodeAndHighlight(segment) {
            // Enhanced predefined locations for Windhoek with more precise coordinates
            const windhoekLocations = {
                // Independence Avenue locations (more comprehensive)
                'independence avenue': [-22.5609, 17.0658],
                'independence ave': [-22.5609, 17.0658],
                '23 independence ave': [-22.5609, 17.0658],
                '23 independence avenue': [-22.5609, 17.0658],
                '23 independence ave,windhoek': [-22.5609, 17.0658],
                '23 independence avenue,windhoek': [-22.5609, 17.0658],
                '25 independence ave': [-22.5615, 17.0662],
                '25 independence avenue': [-22.5615, 17.0662],
                '30 independence ave': [-22.5620, 17.0665],
                '35 independence ave': [-22.5625, 17.0668],
                '40 independence ave': [-22.5630, 17.0671],
                
                // Major roads and streets
                'sam nujoma drive': [-22.5570, 17.0836],
                'sam nujoma drive,windhoek': [-22.5570, 17.0836],
                'hosea kutako drive': [-22.5400, 17.0500],
                'hosea kutako drive,windhoek': [-22.5400, 17.0500],
                'mandume street': [-22.5650, 17.0680],
                'robert mugabe avenue': [-22.5580, 17.0720],
                'robert mugabe avenue,windhoek': [-22.5580, 17.0720],
                'dr kwame nkrumah street': [-22.5595, 17.0845],
                'tal street': [-22.5605, 17.0670],
                'post street mall': [-22.5612, 17.0651],
                'fidel castro street': [-22.5587, 17.0698],
                'beethoven street': [-22.5625, 17.0755],
                'werner list street': [-22.5640, 17.0680],
                
                // Suburbs and areas
                'windhoek central': [-22.5597, 17.0832],
                'windhoek central,windhoek': [-22.5597, 17.0832],
                'katutura': [-22.5300, 17.0400],
                'katutura,windhoek': [-22.5300, 17.0400],
                'klein windhoek': [-22.5800, 17.1000],
                'olympia': [-22.5200, 17.1200],
                'olympia,windhoek': [-22.5200, 17.1200],
                'academia': [-22.6000, 17.1100],
                'eros': [-22.5450, 17.0950],
                'eros,windhoek': [-22.5450, 17.0950],
                'pioneers park': [-22.5750, 17.0800],
                'auasblick': [-22.5350, 17.1050],
                'dorado park': [-22.5150, 17.0750],
                'rocky crest': [-22.5950, 17.0650],
                
                // Common address patterns
                'windhoek': [-22.5597, 17.0832],
                'windhoek,namibia': [-22.5597, 17.0832],
                'windhoek, khomas, namibia': [-22.5597, 17.0832]
            };

            // Try to find a predefined location first with enhanced fuzzy matching
            const locationKey = segment.display_location.toLowerCase().trim();
            let coords = null;
            let matchedLocation = '';
            
            console.log(`Looking for predefined location for: "${segment.display_location}" (normalized: "${locationKey}")`);
            
            // Direct match first
            if (windhoekLocations[locationKey]) {
                coords = windhoekLocations[locationKey];
                matchedLocation = locationKey;
                console.log(`✅ Direct match found: ${matchedLocation}`);
            } else {
                // Enhanced fuzzy matching for partial matches
                for (const [key, value] of Object.entries(windhoekLocations)) {
                    // Remove common suffixes and prefixes for better matching
                    const cleanKey = key.replace(/,\s*windhoek.*$/i, '').trim();
                    const cleanLocation = locationKey.replace(/,\s*windhoek.*$/i, '').trim();
                    
                    // Check multiple match patterns
                    if (cleanLocation === cleanKey || 
                        cleanLocation.includes(cleanKey) || 
                        cleanKey.includes(cleanLocation) ||
                        locationKey.includes(key) || 
                        key.includes(locationKey.replace(/\d+\s*/g, '').trim())) {
                        coords = value;
                        matchedLocation = key;
                        console.log(`✅ Fuzzy match found: "${cleanLocation}" matched "${cleanKey}" (original: ${key})`);
                        break;
                    }
                }
            }

            if (coords) {
                console.log(`✅ Using predefined location for "${segment.display_location}" (matched: ${matchedLocation}):`, coords);
                const isAssigned = segment.contractor_name && segment.contractor_name !== 'None';
                const popupContent = `<div style="font-family: Arial, sans-serif; min-width: 200px;">
                    <h4 style="margin: 0 0 8px 0; color: #1a7a3a;">Segment ID: ${segment.segment_id}</h4>
                    <p style="margin: 4px 0; font-size: 13px;"><strong>Location:</strong> ${segment.display_location}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: #27ae60;"><em>✓ Exact location found (predefined)</em></p>
                    <p style="margin: 4px 0; font-size: 13px;"><strong>Contractor:</strong> <span style="color: ${isAssigned ? '#27ae60' : '#e74c3c'}">${segment.contractor_name}</span></p>
                    <p style="margin: 4px 0; font-size: 13px;"><strong>Status:</strong> ${segment.status}</p>
                    <p style="margin: 4px 0; font-size: 13px;"><strong>Severity:</strong> ${segment.severity}</p>
                    <p style="margin: 4px 0; font-size: 13px;"><strong>Defect Type:</strong> ${segment.defect_type}</p>
                </div>`;
                placeMarker(coords[0], coords[1], popupContent, isAssigned);
                return;
            } else {
                console.log(`⚠️ No predefined location found for "${segment.display_location}", trying geocoding...`);
            }

            // Enhanced geocoding with multiple query strategies
            const baseUrl = 'https://nominatim.openstreetmap.org/search';
            let queries = [
                `${segment.display_location}, Windhoek, Khomas, Namibia`,
                `${segment.display_location.replace('Ave', 'Avenue').replace('St', 'Street')}, Windhoek, Namibia`,
                `${segment.display_location}, Windhoek Central, Namibia`,
                `${segment.display_location}, Windhoek`,
                `Windhoek, ${segment.display_location}`,
                `Windhoek, Namibia`
            ];

            for (let queryIndex = 0; queryIndex < queries.length; queryIndex++) {
                const query = encodeURIComponent(queries[queryIndex]);
                const url = `${baseUrl}?format=json&q=${query}&limit=5&addressdetails=1&countrycodes=na&bounded=1&viewbox=16.8,22.3,17.3,22.8`;
                
                try {
                    await new Promise(resolve => setTimeout(resolve, 1200)); // Rate limiting
                    console.log(`Geocoding attempt ${queryIndex + 1}: ${queries[queryIndex]}`);
                    
                    const response = await fetch(url, {
                        headers: {
                            'User-Agent': 'RoadMaintenanceApp/1.0 (windhoek-roads@example.com)'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    console.log(`Geocoding response for "${queries[queryIndex]}": ${data.length} results`);
                    
                    if (data && data.length > 0) {
                        // Find the best result (prefer results within Windhoek bounds)
                        let bestResult = data[0];
                        for (const result of data) {
                            const lat = parseFloat(result.lat);
                            const lon = parseFloat(result.lon);
                            
                            // Check if within Windhoek bounds
                            if (lat >= -22.8 && lat <= -22.3 && lon >= 16.8 && lon <= 17.3) {
                                bestResult = result;
                                break;
                            }
                        }
                        
                        console.log(`✅ Best geocoding result for "${segment.display_location}": ${bestResult.display_name}`);
                        const isAssigned = segment.contractor_name && segment.contractor_name !== 'None';
                        const popupContent = `<div style="font-family: Arial, sans-serif; min-width: 200px;">
                            <h4 style="margin: 0 0 8px 0; color: #1a7a3a;">Segment ID: ${segment.segment_id}</h4>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Location:</strong> ${segment.display_location}</p>
                            <p style="margin: 4px 0; font-size: 12px; color: #27ae60;"><em>✓ Location found via geocoding</em></p>
                            <p style="margin: 4px 0; font-size: 12px; color: #666;"><strong>Found Address:</strong> ${bestResult.display_name}</p>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Contractor:</strong> <span style="color: ${isAssigned ? '#27ae60' : '#e74c3c'}">${segment.contractor_name}</span></p>
                            <p style="margin: 4px 0; font-size: 13px;"><strong>Status:</strong> ${segment.status}</p>
                        </div>`;
                        placeMarker(bestResult.lat, bestResult.lon, popupContent, isAssigned);
                        return;
                    }
                } catch (error) {
                    console.warn(`Geocoding attempt ${queryIndex + 1} failed for "${queries[queryIndex]}": ${error.message}`);
                }
            }

            // Final fallback to Windhoek center with clear indication
            console.log(`⚠️ All geocoding attempts failed for "${segment.display_location}", using Windhoek center`);
            console.log('Suggestion: Add this location to the predefined locations list for faster and more accurate mapping.');
            const isAssigned = segment.contractor_name && segment.contractor_name !== 'None';
            const popupContent = `<div style="font-family: Arial, sans-serif; min-width: 200px;">
                <h4 style="margin: 0 0 8px 0; color: #1a7a3a;">Segment ID: ${segment.segment_id}</h4>
                <p style="margin: 4px 0; font-size: 13px;"><strong>Location:</strong> ${segment.display_location}</p>
                <p style="margin: 4px 0; font-size: 12px; color: #e74c3c;"><em>⚠️ Exact location not found - showing approximate Windhoek center</em></p>
                <p style="margin: 4px 0; font-size: 11px; color: #666;"><em>Tip: Contact admin to add this location to the database for accurate mapping</em></p>
                <p style="margin: 4px 0; font-size: 13px;"><strong>Contractor:</strong> <span style="color: ${isAssigned ? '#27ae60' : '#e74c3c'}">${segment.contractor_name}</span></p>
                <p style="margin: 4px 0; font-size: 13px;"><strong>Status:</strong> ${segment.status}</p>
            </div>`;
            placeMarker(-22.5597, 17.0832, popupContent, isAssigned);
        }

        // ---------- UI helpers ----------
        const segListEl = document.getElementById('segList');
        const selNone = document.getElementById('sel_none');
        const selBlock = document.getElementById('sel_block');
        const selSeg = document.getElementById('sel_seg');
        const selLoc = document.getElementById('sel_loc');
        const selSev = document.getElementById('sel_sev');
        const selDef = document.getElementById('sel_def');
        const selContractor = document.getElementById('sel_contractor');
        const selInd = document.getElementById('sel_ind');
        const selIndBar = document.getElementById('sel_ind_bar');
        const assignSegId = document.getElementById('assignSegId');

        // populate filter selects
        (function fillFilters() {
            const cond = [...new Set(SEGMENTS.map(s => s.severity))].filter(Boolean).sort();
            const defs = [...new Set(SEGMENTS.map(s => s.defect_type))].filter(Boolean).sort();

            const condSel = document.getElementById('condFilter');
            cond.forEach(v => condSel.insertAdjacentHTML('beforeend', `<option>${v}</option>`));

            const townSel = document.getElementById('townFilter');
            TOWNS.forEach(v => townSel.insertAdjacentHTML('beforeend', `<option>${v}</option>`));

            const defSel = document.getElementById('defFilter');
            defs.forEach(v => defSel.insertAdjacentHTML('beforeend', `<option>${v}</option>`));
        })();

        // filter logic
        function applyFilters() {
            const fCond = (document.getElementById('condFilter').value || '').toLowerCase();
            const fTown = (document.getElementById('townFilter').value || '').toLowerCase();
            const fDef = (document.getElementById('defFilter').value || '').toLowerCase();

            [...segListEl.children].forEach(card => {
                const c = (card.dataset.cond || '').toLowerCase();
                const t = (card.dataset.town || '').toLowerCase();
                const d = (card.dataset.def || '').toLowerCase();
                const show = (!fCond || c === fCond) && (!fTown || t === fTown) && (!fDef || d === fDef);
                card.style.display = show ? 'block' : 'none';
            });
        }
        ['condFilter', 'townFilter', 'defFilter'].forEach(id => {
            document.getElementById(id).addEventListener('change', applyFilters);
        });
        document.getElementById('clearFilters').addEventListener('click', e => {
            e.preventDefault();
            ['condFilter', 'townFilter', 'defFilter'].forEach(id => document.getElementById(id).value = "");
            applyFilters();
        });

        // bind click on cards -> select + map + selected panel
        segListEl.addEventListener('click', async (e) => {
            const card = e.target.closest('.seg-card'); if (!card) return;
            const segmentId = parseInt(card.dataset.segmentId);
            const seg = SEGMENTS.find(s => parseInt(s.segment_id) === segmentId);
            if (!seg) {
                console.error('Segment not found for ID:', segmentId);
                return;
            }

            selNone.classList.add('hidden');
            selBlock.classList.remove('hidden');
            selSeg.textContent = seg.segment_id;
            selLoc.textContent = seg.display_location;
            selSev.textContent = seg.severity;
            selDef.textContent = seg.defect_type;
            selContractor.textContent = seg.contractor_name;
            selInd.textContent = seg.indicator;
            selIndBar.style.width = seg.severity === 'Severe' ? '100%' : (seg.severity === 'Moderate' ? '76%' : '50%');
            assignSegId.value = seg.segment_id;

            setActiveStatus(seg.status, 'statusSteps');

            // Add a small delay to avoid rate-limiting
            await new Promise(resolve => setTimeout(resolve, 1000));
            await geocodeAndHighlight(seg);
        });

        // selected-segment status tracker
        const stepsEl = document.getElementById('statusSteps');
        const formSt = document.getElementById('statusForm');
        function setActiveStatus(status, elementId) {
            const steps = document.getElementById(elementId).querySelectorAll('.step');
            [...steps].forEach(s => {
                s.classList.toggle('active', s.dataset.status === status);
            });
            document.getElementById('statusSegId').value = (SEGMENTS.find(s => parseInt(s.segment_id) === parseInt(document.getElementById('sel_seg').textContent))?.segment_id || '');
        }
        stepsEl.addEventListener('click', (e) => {
            const step = e.target.closest('.step'); if (!step) return;
            const segId = SEGMENTS.find(s => parseInt(s.segment_id) === parseInt(document.getElementById('sel_seg').textContent))?.segment_id;
            if (!segId) { alert('Select a segment first.'); return; }
            document.getElementById('statusSegId').value = segId;
            document.getElementById('statusNew').value = step.dataset.status;
            formSt.submit();
        });

        // ---------- Date Picker ----------
        const startDateBtn = document.getElementById('startDateBtn');
        const endDateBtn = document.getElementById('endDateBtn');
        const startDateHidden = document.getElementById('startDateHidden');
        const endDateHidden = document.getElementById('endDateHidden');
        const datePicker = document.getElementById('datePicker');
        const monthYear = document.getElementById('monthYear');
        const dateGrid = document.getElementById('dateGrid');
        const prevMonth = document.getElementById('prevMonth');
        const nextMonth = document.getElementById('nextMonth');

        let currentDate = new Date();
        let selectedField = null;

        function renderCalendar() {
            dateGrid.innerHTML = '';
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthYear.textContent = `${currentDate.toLocaleString('default', { month: 'long' })} ${year}`;

            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDay = firstDay.getDay();
            const totalDays = lastDay.getDate();

            let row = document.createElement('tr');
            for (let i = 0; i < startDay; i++) {
                row.appendChild(document.createElement('td'));
            }
            for (let day = 1; day <= totalDays; day++) {
                const td = document.createElement('td');
                td.textContent = day;
                if (new Date(year, month, day).toDateString() === new Date().toDateString()) {
                    td.classList.add('today');
                }
                td.addEventListener('click', () => {
                    const selectedDate = new Date(year, month, day).toISOString().split('T')[0];
                    if (selectedField === 'start') {
                        startDateHidden.value = selectedDate;
                        startDateBtn.textContent = selectedDate;
                    } else if (selectedField === 'end') {
                        endDateHidden.value = selectedDate;
                        endDateBtn.textContent = selectedDate;
                    } else if (selectedField === 'newStart') {
                        document.getElementById('newStartDateHidden').value = selectedDate;
                        document.getElementById('newStartDateBtn').textContent = selectedDate;
                    } else if (selectedField === 'newEnd') {
                        document.getElementById('newEndDateHidden').value = selectedDate;
                        document.getElementById('newEndDateBtn').textContent = selectedDate;
                    }
                    datePicker.classList.remove('active');
                });
                row.appendChild(td);
                if ((startDay + day) % 7 === 0 || day === totalDays) {
                    dateGrid.appendChild(row);
                    row = document.createElement('tr');
                }
            }
        }

        startDateBtn.addEventListener('click', () => {
            selectedField = 'start';
            datePicker.classList.add('active');
            renderCalendar();
        });

        endDateBtn.addEventListener('click', () => {
            selectedField = 'end';
            datePicker.classList.add('active');
            renderCalendar();
        });

        prevMonth.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        nextMonth.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        document.addEventListener('click', (e) => {
            const newStartBtn = document.getElementById('newStartDateBtn');
            const newEndBtn = document.getElementById('newEndDateBtn');
            
            if (!datePicker.contains(e.target) && 
                !startDateBtn.contains(e.target) && 
                !endDateBtn.contains(e.target) &&
                !(newStartBtn && newStartBtn.contains(e.target)) &&
                !(newEndBtn && newEndBtn.contains(e.target))) {
                datePicker.classList.remove('active');
            }
        });

        // Initialize with current date
        renderCalendar();
        
        // ---------- Setup New Segment Date Pickers ----------
        function setupNewSegmentDatePickers() {
            const newStartBtn = document.getElementById('newStartDateBtn');
            const newEndBtn = document.getElementById('newEndDateBtn');
            
            console.log('Setting up new segment date pickers:', { newStartBtn, newEndBtn });
            
            if (newStartBtn && !newStartBtn.hasEventListener) {
                newStartBtn.addEventListener('click', () => {
                    console.log('New start date button clicked');
                    selectedField = 'newStart';
                    datePicker.classList.add('active');
                    renderCalendar();
                });
                newStartBtn.hasEventListener = true;
                console.log('New start date button listener added');
            }
            
            if (newEndBtn && !newEndBtn.hasEventListener) {
                newEndBtn.addEventListener('click', () => {
                    console.log('New end date button clicked');
                    selectedField = 'newEnd';
                    datePicker.classList.add('active');
                    renderCalendar();
                });
                newEndBtn.hasEventListener = true;
                console.log('New end date button listener added');
            }
        }
        
        // Override the new segment button click handler to setup date pickers
        const originalNewSegmentBtn = document.getElementById('newSegmentBtn');
        if (originalNewSegmentBtn) {
            // Remove existing event listeners
            const newBtn = originalNewSegmentBtn.cloneNode(true);
            originalNewSegmentBtn.parentNode.replaceChild(newBtn, originalNewSegmentBtn);
            
            newBtn.addEventListener('click', () => {
                document.getElementById('newSegmentCard').style.display = 'block';
                document.getElementById('newSegmentBtn').style.display = 'none';
                // Setup date pickers after the form is visible
                setTimeout(setupNewSegmentDatePickers, 100);
            });
        }
    } catch (e) {
        console.error('Map initialization error:', e);
        alert('Map initialization failed. Check console for details.');
    }
}

// Ensure map initializes after DOM is fully loaded
window.addEventListener('load', initMap);
