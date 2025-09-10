<?php
require_once 'db.php';
requireAuth();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="road_assets_export.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Length', 'Width', 'Surface Type', 'Sidewalks', 'Last Scanned']);

$stmt = $pdo->query("SELECT * FROM road_segments ORDER BY name");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['name'],
        $row['length'],
        $row['width'],
        $row['surface_type'],
        $row['sidewalks'],
        $row['last_scanned']
    ]);
}

fclose($output);
exit();
?>