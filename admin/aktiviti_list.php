<?php

require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// FILTER LOGIC - Adjusted for 'aktiviti' table columns
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$jenis = $_GET['jenis'] ?? '';

// SQL QUERY: Primary source is the 'aktiviti' table
$sql = "SELECT * FROM aktiviti WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nama_aktiviti LIKE ? OR tempat LIKE ? OR guru_penasihat LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm; $params[] = $searchTerm; $params[] = $searchTerm;
}

if (!empty($jenis) && $jenis != 'Semua Jenis') {
    $sql .= " AND jenis_aktiviti = ?";
    $params[] = $jenis;
}

if (!empty($status) && $status != 'Semua Status') {
    $sql .= " AND status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY tarikh_mula DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Aktiviti - SMK King Edward VII</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* DASHBOARD NAVBAR & SIDEBAR CSS - EXACTLY FROM DASHBOARD.PHP */
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
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .layout-container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; transition: all 0.3s ease; }
        .sidebar-header {
    padding: 0 2rem 1rem;
    border-bottom: 1px solid #34495e;
    margin-bottom: 1rem;
        }
    .sidebar-header h3 {
    font-size: 1.2rem; /* Matches the visual scale of the screenshot */
    font-weight: 600;
    margin-bottom: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 13px 17px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }

        /* ACTIVITY SPECIFIC STYLES */
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; transition: transform 0.2s; border: none; }
        .card:hover { transform: translateY(-2px); }
        .status-aktif { color: #28a745; font-weight: bold; }
        .status-tamat { color: #dc3545; font-weight: bold; }
        .status-batal { color: #6c757d; font-weight: bold; }
        
        .content-header-card { 
            background: white; padding: 1.5rem; border-radius: 15px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 2rem; 
            border-left: 6px solid #3498db; 
        }

        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; width: 100%; } }
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

    <div class="layout-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" ><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php" class="active"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="content-header-card d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">Pengurusan Aktiviti</h2>
                    <p class="text-muted mb-0">Urus maklumat aktiviti kokurikulum SMK King Edward VII</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="aktiviti_create.php" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i>Tambah Aktiviti Baharu
                    </a>
                    <a href="dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-1"></i>Dashboard
                    </a>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Cari Aktiviti</label>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Nama aktiviti atau tempat..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Jenis Aktiviti</label>
                                <select name="jenis" class="form-select">
                                    <option value="Semua Jenis">Semua Jenis</option>
                                    <option value="SUKAN & PERMAINAN" <?php echo ($jenis == 'SUKAN & PERMAINAN') ? 'selected' : ''; ?>>SUKAN & PERMAINAN</option>
                                    <option value="KELAB & PERSATUAN" <?php echo ($jenis == 'KELAB & PERSATUAN') ? 'selected' : ''; ?>>KELAB & PERSATUAN</option>
                                    <option value="BADAN BERUNIFORM" <?php echo ($jenis == 'BADAN BERUNIFORM') ? 'selected' : ''; ?>>BADAN BERUNIFORM</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="Semua Status">Semua Status</option>
                                    <option value="AKTIF" <?php echo ($status == 'AKTIF') ? 'selected' : ''; ?>>AKTIF</option>
                                    <option value="TAMAT" <?php echo ($status == 'TAMAT') ? 'selected' : ''; ?>>TAMAT</option>
                                    <option value="BATAL" <?php echo ($status == 'BATAL') ? 'selected' : ''; ?>>BATAL</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Tapis</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (count($activities) > 0): ?>
                <div class="row">
                    <?php foreach ($activities as $row): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white py-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($row['nama_aktiviti']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <strong>Jenis:</strong> <?php echo htmlspecialchars($row['jenis_aktiviti']); ?><br>
                                        <strong>Tarikh:</strong> <?php echo date('d/m/Y', strtotime($row['tarikh_mula'])); ?> - 
                                        <?php echo date('d/m/Y', strtotime($row['tarikh_tamat'])); ?><br>
                                        <strong>Masa:</strong> <?php echo date('h:i A', strtotime($row['masa_mula'])); ?> - 
                                        <?php echo date('h:i A', strtotime($row['masa_tamat'])); ?><br>
                                        <strong>Tempat:</strong> <?php echo htmlspecialchars($row['tempat']); ?><br>
                                        <strong>Guru Penasihat:</strong> <?php echo htmlspecialchars($row['guru_penasihat']); ?><br>
                                        <strong>Max Peserta:</strong> <?php echo $row['max_peserta']; ?><br>
                                        <strong>Status:</strong> 
                                        <span class="status-<?php echo strtolower($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </p>
                                    <?php if (!empty($row['deskripsi'])): ?>
                                        <p class="card-text border-top pt-2"><small class="text-muted"><?php echo htmlspecialchars($row['deskripsi']); ?></small></p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white border-top-0 pb-3">
    <div class="btn-group w-100">
        <a href="aktiviti_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
            <i class="fas fa-edit"></i> Edit
        </a>
        
        <a href="aktiviti_delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" 
           onclick="return confirm('Adakah anda pasti ingin padam aktiviti ini?')">
            <i class="fas fa-trash"></i> Padam
        </a>
    </div>
</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-info-circle fa-2x mb-3"></i><br>
                    <?php echo (empty($search) && empty($jenis) && empty($status)) ? 
                        'Tiada aktiviti dijumpai. <a href="aktiviti_create.php" class="alert-link">Sila tambah aktiviti baharu</a>.' : 
                        'Tiada aktiviti dijumpai berdasarkan tapisan anda.'; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>