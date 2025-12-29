<?php
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

/// Get stats for dashboard - using simple queries since functions might not exist
try {
    // Total students
    $stmt = $conn->query("SELECT COUNT(*) as total FROM pelajar WHERE status = 'aktif'");
    $total_pelajar = $stmt->fetch()['total'];

    // Total teachers
    $stmt = $conn->query("SELECT COUNT(*) as total FROM guru");
    $total_guru = $stmt->fetch()['total'];

    // Total activities
    $stmt = $conn->query("SELECT COUNT(*) as total FROM aktiviti_kokurikulum");
    $total_aktiviti = $stmt->fetch()['total'];

    // Total units
    $stmt = $conn->query("SELECT COUNT(*) as total FROM unit_kokurikulum");
    $total_unit = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM permohonan_peruntukan");
    $total_permohonan = $stmt->fetch()['total'];
    // Get recent activities
    // Get recent activities from the 'aktiviti' table
$stmt = $conn->query("
    SELECT 
        nama_aktiviti, 
        tarikh_mula, 
        jenis_aktiviti, 
        tempat 
    FROM aktiviti 
    ORDER BY tarikh_mula DESC 
    LIMIT 5
");
$recent_activities = $stmt->fetchAll();

    // Get pending applications (if table exists, otherwise set to 0)
    $pending_applications = 0;
    // Uncomment if you have permohonan_peruntukan table
    // $stmt = $conn->query("SELECT COUNT(*) as total FROM permohonan_peruntukan WHERE status_permohonan = 'dihantar'");
    // $pending_applications = $stmt->fetch()['total'];

} catch(Exception $e) {
    // Handle errors gracefully
    $total_pelajar = 0;
    $total_guru = 0;
    $total_aktiviti = 0;
    $total_unit = 0;
    $recent_activities = [];
    $pending_applications = 0;
    $total_permohonan = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pentadbir - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       /* 1. CSS Reset to fill space to the brim */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f8f9fa;
    color: #333;
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    overflow-x: hidden; /* Prevents horizontal scroll */
}

/* 2. Fixed Header Styling */
.header {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 0 2rem;
    height: 70px; /* Standardized height */
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: fixed;
    width: 100%;
    top: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
}

.nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.logo {
    font-size: 1.5rem;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.logout-btn {
    background: #e74c3c;
    color: white;
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #c0392b;
}

/* 3. Layout Container */
.container {
    display: flex;
    width: 100%;
    min-height: 100vh;
    padding-top: 70px; /* Offset for fixed header */
}

/* 4. Sidebar Navigation - Fixed and Full Height */
.sidebar {
    width: 280px;
    background: #2c3e50;
    color: white;
    padding: 1rem 0;
    position: fixed;
    height: calc(100vh - 70px);
    overflow-y: auto;
    transition: all 0.3s ease;
}

.sidebar-header {
    padding: 0 2rem 1rem;
    border-bottom: 1px solid #34495e;
    margin-bottom: 1rem;
}

.sidebar-menu {
    list-style: none;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    color: white;
    text-decoration: none;
    padding: 15px 20px;
    transition: all 0.3s;
    border-left: 4px solid transparent;
}

.sidebar-menu a:hover, .sidebar-menu a.active {
    background: #34495e;
    border-left-color: #3498db;
}

/* 5. Main Content - Responsive and Flexible */
.main-content {
    flex: 1;
    padding: 2rem;
    margin-left: 280px; /* Pushes content past the fixed sidebar */
    width: calc(100% - 280px); /* Ensures content doesn't overflow screen */
    min-height: calc(100vh - 70px);
    transition: all 0.3s ease;
}

.welcome-section {
    background: white;
    padding: 2rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    border-left: 6px solid #3498db;
}

/* 6. Dynamic Stats Grid */
.stats-grid {
    display: grid;
    /* Automatically creates columns based on available width */
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    border-top: 4px solid #3498db;
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-card.warning { border-top-color: #e74c3c; }
.stat-card.success { border-top-color: #27ae60; }
.stat-card.purple { border-top-color: #9b59b6; }

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    opacity: 0.8;
}

.stat-number {
    font-size: clamp(1.8rem, 3vw, 2.5rem); /* Responsive font sizing */
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #7f8c8d;
    font-size: 0.9rem;
    font-weight: 500;
}

/* 7. Dashboard Content Grid */
.dashboard-grid {
    display: grid;
    /* Stacks on mobile, 2 columns on desktop */
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

@media (min-width: 1024px) {
    .dashboard-grid {
        grid-template-columns: 2fr 1fr;
    }
}

.recent-activities, .quick-actions {
    background: white;
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.section-title {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #ecf0f1;
    display: flex;
    align-items: center;
    gap: 10px;
}

.activity-list { list-style: none; }
.activity-item {
    padding: 1rem;
    border-left: 4px solid #3498db;
    background: #f8f9fa;
    margin-bottom: 0.8rem;
    border-radius: 8px;
}

/* 8. Action Buttons */
.action-buttons { display: grid; gap: 1rem; }
.action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #3498db;
    color: white;
    padding: 12px 20px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s;
    font-weight: 500;
}

.action-btn:hover {
    background: #2980b9;
    transform: translateX(5px);
}

.action-btn.success { background: #27ae60; }
.action-btn.warning { background: #e74c3c; }

/* 9. Mobile Responsiveness Adjustments */
@media (max-width: 992px) {
    .sidebar {
        width: 80px; /* Mini sidebar for tablets */
    }
    .sidebar-header h3, .sidebar-menu a span {
        display: none;
    }
    .main-content {
        margin-left: 80px;
        width: calc(100% - 80px);
    }
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%); /* Hide sidebar on small mobile */
    }
    .main-content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
    }
    .header .logo { font-size: 1.1rem; }
    .user-info span { display: none; }
}
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo">
                <i class="fas fa-school"></i>
                SMK KING EDWARD VII - Sistem Pengurusan Kokurikulum
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo $_SESSION['nama_penuh']; ?> (Pentadbir)</span>
                <a href="../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1><i class="fas fa-graduation-cap"></i> Selamat Datang, Pentadbir Sistem</h1>
                <p>Sistem Pengurusan Maklumat Kokurikulum SMK King Edward VII - Dashboard Utama</p>
            </div>

            <!-- Statistics Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
                    <div class="stat-number"><?php echo $total_pelajar; ?></div>
                    <div class="stat-label">Jumlah Pelajar Aktif</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="stat-number"><?php echo $total_guru; ?></div>
                    <div class="stat-label">Guru Penasihat</div>
                </div>
                <div class="stat-card purple">
                    <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                    <div class="stat-number"><?php echo $total_unit; ?></div>
                    <div class="stat-label">Unit Kokurikulum</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-number"><?php echo $total_permohonan; ?></div>
                    <div class="stat-label">Permohonan Peruntukan</div>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Recent Activities -->
                <div class="recent-activities">
    <h3 class="section-title"><i class="fas fa-history"></i> Aktiviti Terkini</h3>
    
    <?php if(count($recent_activities) > 0): ?>
        <ul class="activity-list">
            <?php foreach($recent_activities as $activity): ?>
                <li class="activity-item">
                    <strong><?php echo htmlspecialchars($activity['nama_aktiviti']); ?></strong><br>
                    <small>
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($activity['jenis_aktiviti']); ?> | 
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($activity['tempat']); ?> |
                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($activity['tarikh_mula'])); ?>
                    </small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="empty-state" style="text-align: center; padding: 2rem; color: #7f8c8d;">
            <i class="fas fa-calendar-times" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.3;"></i>
            <p>Tiada aktiviti terkini direkodkan.</p>
        </div>
    <?php endif; ?>
</div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3 class="section-title"><i class="fas fa-bolt"></i> Tindakan Pantas</h3>
                    <div class="action-buttons">
                        <a href="guru/guru_tambah.php" class="action-btn">
                            <i class="fas fa-plus-circle"></i> Daftar Guru Baharu
                        </a>
                        <a href="pelajar/tambah.php" class="action-btn success">
                            <i class="fas fa-user-plus"></i> Daftar Pelajar Baharu
                        </a>
                        <a href="permohonan_peruntukan.php" class="action-btn">
                            <i class="fas fa-clipboard-check"></i> Semak Permohonan
                        </a>
                        <a href="aktiviti_create.php" class="action-btn success">
                            <i class="fas fa-file-pdf"></i> Jana Laporan
                        </a>
                        <a href="../auth/logout.php" class="action-btn warning">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple animation for stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
                card.classList.add('animate_animated', 'animate_fadeInUp');
            });
        });
    </script>
</body>
</html>