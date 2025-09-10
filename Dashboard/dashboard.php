<?php
// dashboard.php - Fetch dashboard data
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Get total road segments
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM road_segments");
        $totalSegments = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Get segments in poor condition
        $stmt = $pdo->query("SELECT COUNT(*) as poor FROM road_segments WHERE condition_rating = 'poor'");
        $poorSegments = $stmt->fetch(PDO::FETCH_ASSOC)['poor'] ?? 0;
        
        // Calculate percentage
        $poorPercentage = $totalSegments > 0 ? round(($poorSegments / $totalSegments) * 100) : 0;
        
        // Get active maintenance projects
        $stmt = $pdo->query("SELECT COUNT(*) as active FROM maintenance_projects WHERE status = 'active'");
        $activeProjects = $stmt->fetch(PDO::FETCH_ASSOC)['active'] ?? 0;
        
        // Get last update date
        $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM road_segments");
        $lastUpdateResult = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastUpdate = $lastUpdateResult['last_update'] ? 
                     date('F j, Y', strtotime($lastUpdateResult['last_update'])) : 
                     date('F j, Y');
        
        // Get recent alerts (road segments due for inspection)
        $stmt = $pdo->query("
            SELECT segment_name, last_inspection 
            FROM road_segments 
            WHERE last_inspection < DATE_SUB(NOW(), INTERVAL 6 MONTH) 
            OR last_inspection IS NULL 
            ORDER BY last_inspection ASC 
            LIMIT 5
        ");
        $alertSegments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $alerts = [];
        foreach ($alertSegments as $segment) {
            $alerts[] = [
                'message' => $segment['segment_name'] . ' needs inspection',
                'type' => 'inspection_due'
            ];
        }
        
        // Get recent maintenance projects
        $stmt = $pdo->query("
            SELECT project_name, status, start_date 
            FROM maintenance_projects 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $recentProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format project dates
        foreach ($recentProjects as &$project) {
            if ($project['start_date']) {
                $project['start_date'] = date('M j, Y', strtotime($project['start_date']));
            }
        }
        
        // Get condition distribution for additional stats
        $stmt = $pdo->query("
            SELECT 
                condition_rating, 
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM road_segments)), 1) as percentage
            FROM road_segments 
            GROUP BY condition_rating
        ");
        $conditionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'total_segments' => $totalSegments,
                'poor_percentage' => $poorPercentage,
                'active_projects' => $activeProjects,
                'last_update' => $lastUpdate,
                'alerts' => $alerts,
                'recent_projects' => $recentProjects,
                'condition_stats' => $conditionStats
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Database error in dashboard.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Database error occurred'
        ]);
    } catch (Exception $e) {
        error_log("General error in dashboard.php: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to fetch dashboard data'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Only GET method allowed'
    ]);
}
?>