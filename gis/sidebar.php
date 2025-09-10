<!-- sidebar.php -->
<aside class="sidebar">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="manager.css">

    <div class="logo">
        <img src="logo11.png" alt="Logo">
    </div>

   

    <ul class="menu">
        <li>
            <a href="../Dashboard/dash_board.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dash_board.php' ? 'active' : '' ?>">
                <i class="fa fa-home"></i> Dashboard
            </a>
        </li>

        <li>
            <a href="GIS.php" class="<?= basename($_SERVER['PHP_SELF']) == 'GIS.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-map"></i> Gis Road Condition Map
            </a>
        </li>
        <li>
            <a href="../upload_manager/image_collection.php" class="<?= basename($_SERVER['PHP_SELF']) == 'image_collection.php' ? 'active' : '' ?>">
                <i class="fa fa-database"></i> Mobile Data Collection
            </a>
        </li>
        <li>
            <a href="../upload_manager/upload_manager.php" class="<?= basename($_SERVER['PHP_SELF']) == 'upload_manager.php' ? 'active' : '' ?>">
                <i class="fa fa-wrench"></i> Upload Manager
            </a>
        </li>
        <li>
            <a href="../admin/index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fa fa-exclamation-triangle"></i> Defect Detection Review
            </a>
        </li>
        <li>
            <a href="road_segment.php" class="<?= basename($_SERVER['PHP_SELF']) == 'road_segment.php' ? 'active' : '' ?>">
                <i class="fa fa-scissors"></i> Road Segment
            </a>
        </li>
        <li>
            <a href="maintenance_planning.php" class="<?= basename($_SERVER['PHP_SELF']) == 'maintenance_planning.php' ? 'active' : '' ?>">
                <i class="fa fa-toolbox"></i> Maintenance Planning
            </a>
        </li>
        <li>
            <a href="../upload_manager/maintenance_log.php" class="<?= basename($_SERVER['PHP_SELF']) == 'maintenance_log.php' ? 'active' : '' ?>">
                <i class="fa fa-font-awesome"></i> Maintenance Log
            </a>
        </li>
        <li>
            <a href="reports_center.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports_center.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-file"></i> Reports Center
            </a>
        </li>
        <li>
            <a href="AssetInventory.php" class="<?= basename($_SERVER['PHP_SELF']) == 'AssetInventory.php' ? 'active' : '' ?>">
                <i class="fa fa-warehouse"></i> Road Asset Inventory
            </a>
        </li>
    </ul>

    <button class="logout" onclick="window.location.href='../Dashboard/login.html'">
    <i class="fa fa-sign-out-alt"></i> LOGOUT
    </button>
</aside>


