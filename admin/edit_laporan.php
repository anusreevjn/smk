<?php

require_once '../includes/database.php';
require_once '../includes/functions.php';

// Allow both admin and guru to access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['peranan'], ['admin', 'guru'])) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$id = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT * FROM aktiviti WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) { 
    header('Location: laporan_list.php'); 
    exit(); 
}

// Logic fix: Admin can edit all. Guru only their own.
$owned = false;
if ($_SESSION['peranan'] == 'admin') {
    $owned = true;
} else {
    $stmt_map = $conn->prepare("SELECT id FROM guru WHERE nama_penuh = ? LIMIT 1");
    $stmt_map->execute([$_SESSION['nama_penuh']]);
    $map = $stmt_map->fetch(PDO::FETCH_ASSOC);
    $current_guru_id = $map['id'] ?? null;

    if (!empty($data['guru_pelapor_id']) && (
            $data['guru_pelapor_id'] == $_SESSION['user_id'] ||
            ($current_guru_id && $data['guru_pelapor_id'] == $current_guru_id)
        )) {
        $owned = true;
    } elseif (empty($data['guru_pelapor_id']) && !empty($data['guru_penasihat']) &&
              strcasecmp(trim($data['guru_penasihat']), trim($_SESSION['nama_penuh'])) === 0) {
        $owned = true;
    }
}

if (!$owned) {
    header('Location: laporan_list.php');
    exit();
}

// Ambil senarai guru untuk dropdown Guru Pelapor daripada jadual 'guru' sahaja
$stmt_guru = $conn->query("SELECT id, nama_penuh FROM guru ORDER BY nama_penuh ASC");
$senarai_guru = $stmt_guru->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Laporan - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS EXACTLY FROM DASHBOARD.PHP */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; transition: all 0.3s ease; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 15px 20px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }
        
        /* FORM STYLING */
        .form-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 6px solid #3498db; }
        .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }
        .form-group { margin-bottom: 1rem; }
        .form-label { font-weight: 600; color: #2c3e50; margin-bottom: 8px; display: block; }
        .form-control, .form-select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
        .btn-save { background: #27ae60; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 1rem; transition: 0.3s; }
        .btn-save:hover { background: #219150; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo"><i class="fas fa-school"></i> SMK KING EDWARD VII - Sistem Pengurusan Kokurikulum</div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo $_SESSION['nama_penuh']; ?> (Pentadbir)</span>
                <a href="../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"  class="active"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-edit"></i> Kemaskini Laporan Aktiviti</h2>
                <a href="laporan_list.php" class="logout-btn" style="background: #7f8c8d;"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <div class="form-card">
    <form method="POST" action="proses_laporan.php?action=update" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
        <div class="grid-container">
            <div class="form-group full-width">
                <label class="form-label">Tajuk Laporan</label>
                <input type="text" name="nama_aktiviti" class="form-control" value="<?php echo htmlspecialchars($data['nama_aktiviti']); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Aktiviti/Deskripsi</label>
                <input type="text" name="deskripsi" class="form-control" value="<?php echo htmlspecialchars($data['deskripsi']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Tarikh Aktiviti</label>
                <input type="date" name="tarikh_mula" class="form-control" value="<?php echo $data['tarikh_mula']; ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Bilangan Ahli Hadir</label>
                <input type="number" name="bilangan_ahli_hadir" class="form-control" value="<?php echo $data['bilangan_ahli_hadir']; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Kos / Perbelanjaan (RM)</label>
                <input type="number" step="0.01" name="perbelanjaan" class="form-control" value="<?php echo number_format($data['perbelanjaan'], 2, '.', ''); ?>">
            </div>

            <div class="form-group">
    <label class="form-label">Bukti Resit (Imej/PDF)</label>
    <input type="file" name="fail_resit" class="form-control" accept="image/*,application/pdf">
    
    <?php if (!empty($data['fail_resit'])): ?>
        <div style="margin-top: 8px; font-size: 0.9rem;">
            <span>Fail sedia ada: </span>
            <a href="../uploads/resit/<?php echo $data['fail_resit']; ?>" target="_blank" style="color: #3498db; text-decoration: none; font-weight: 600;">
                <i class="fas fa-external-link-alt"></i> Lihat Resit
            </a>
        </div>
    <?php endif; ?>
</div>
            <div class="form-group">
                <label class="form-label">Guru Pelapor</label>
                <select name="guru_pelapor_id" class="form-select">
                    <?php foreach($senarai_guru as $g): ?>
                        <option value="<?php echo $g['id']; ?>" <?php echo ($g['id'] == $data['guru_pelapor_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['nama_penuh']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="text-align: right; margin-top: 2rem;">
            <button type="submit" class="btn-save"><i class="fas fa-save"></i> Simpan Segala Perubahan</button>
        </div>
    </form>
</div>
            </div>
        </div>
    </div>
</body>
</html>