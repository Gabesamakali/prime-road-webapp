<?php
// Maintenance Planning - Fixed Version
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB connection
$DB_HOST = "localhost";
$DB_USER = "root"; 
$DB_PASS = "gabes221354271";
$DB_NAME = "road_maintenance";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Database connection error: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Handle assignment submit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["assign"])) {
    $segmentId = (int)($_POST["segment_id"] ?? 0);
    $contractorId = (int)($_POST["contractor_id"] ?? 0);
    $startDate = trim($_POST["start_date"] ?? '');
    $endDate = trim($_POST["end_date"] ?? '');
    $estCost = (float)($_POST["estimated_cost"] ?? 0);

    // Simple validation
    if ($segmentId > 0 && $contractorId > 0 && $startDate && $endDate && $estCost > 0) {
        $stmt = $conn->prepare("INSERT INTO maintenance_assignments (segment_id, contractor_id, start_date, end_date, estimated_cost, status) VALUES (?, ?, ?, ?, ?, 'Planned')");
        if ($stmt) {
            $stmt->bind_param("iisds", $segmentId, $contractorId, $startDate, $endDate, $estCost);
            if ($stmt->execute()) {
                // Update segment status
                $updateStmt = $conn->prepare("UPDATE road_segments SET status='Assigned' WHERE segment_id=?");
                if ($updateStmt) {
                    $updateStmt->bind_param("i", $segmentId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }
                echo "<script>alert('Contractor assigned successfully'); window.location.reload();</script>";
            } else {
                echo "<script>alert('Assignment failed: " . addslashes($stmt->error) . "');</script>";
            }
            $stmt->close();
        }
    } else {
        echo "<script>alert('Please fill all required fields');</script>";
    }
}

// Handle new segment creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_segment"])) {
    $location = trim($_POST["location"] ?? '');
    $severity = trim($_POST["severity"] ?? '');
    $defectType = trim($_POST["defect_type"] ?? '');
    $indicator = trim($_POST["indicator"] ?? 'yellow');

    if ($location && $severity && $defectType) {
        $stmt = $conn->prepare("INSERT INTO road_segments (location, severity, defect_type, indicator, status) VALUES (?, ?, ?, ?, 'Planned')");
        if ($stmt) {
            $stmt->bind_param("ssss", $location, $severity, $defectType, $indicator);
            if ($stmt->execute()) {
                echo "<script>alert('New segment created successfully'); window.location.reload();</script>";
            }
            $stmt->close();
        }
    }
}

// Fetch data
$segRes = $conn->query("
    SELECT rs.*, c.name AS contractor_name
    FROM road_segments rs
    LEFT JOIN maintenance_assignments ma ON rs.segment_id = ma.segment_id
    LEFT JOIN contractors c ON ma.contractor_id = c.id
    ORDER BY rs.segment_id ASC
");

$ctrRes = $conn->query("SELECT * FROM contractors ORDER BY name ASC");

$segments = [];
$contractors = [];

if ($segRes) {
    while ($r = $segRes->fetch_assoc()) {
        $r["display_location"] = $r["location"] ?? "Segment " . $r["segment_id"];
        $r["contractor_name"] = $r["contractor_name"] ?? "None";
        $segments[] = $r;
    }
}

if ($ctrRes) {
    while ($c = $ctrRes->fetch_assoc()) {
        $contractors[] = $c;
    }
}

$conn->close();

$segments_json = json_encode($segments);
$contractors_json = json_encode($contractors);

ob_end_clean();
?>