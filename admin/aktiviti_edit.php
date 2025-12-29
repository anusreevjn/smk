<?php

require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

$id = $_GET['id'] ?? 0;

// Ambil data aktiviti sedia ada
$stmt = $conn->prepare("SELECT * FROM aktiviti WHERE id = ?");
$stmt->execute([$id]);
$activity = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$activity) { header('Location: aktiviti_list.php'); exit(); }

// Ambil senarai pengguna untuk dropdown Guru Pelapor
$stmt_pengguna = $conn->query("SELECT id, nama_penuh FROM pengguna WHERE peranan IN ('admin', 'guru') AND status = 'aktif' ORDER BY nama_penuh ASC");
$senarai_pengguna = $stmt_pengguna->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_aktiviti = trim($_POST['nama_aktiviti']);
    $jenis_aktiviti = $_POST['jenis_aktiviti'];
    $tarikh_mula = $_POST['tarikh_mula'];
    $tarikh_tamat = $_POST['tarikh_tamat'];
    $masa_mula = $_POST['masa_mula'];
    $masa_tamat = $_POST['masa_tamat'];
    $tempat = trim($_POST['tempat']);
    $guru_penasihat = trim($_POST['guru_penasihat']);
    $max_peserta = $_POST['max_peserta'];
    $deskripsi = trim($_POST['deskripsi']);
    $status = $_POST['status'];
    
    // Field Laporan Baru
    $tajuk_laporan = trim($_POST['tajuk_laporan']);
    $bilangan_ahli_hadir = $_POST['bilangan_ahli_hadir'] ?: 0;
    $perbelanjaan = $_POST['perbelanjaan'] ?: 0.00;
    $guru_pelapor_id = $_POST['guru_pelapor_id'] ?: null;

    if (empty($errors)) {
        try {
            // Update semua field termasuk maklumat laporan ke dalam table 'aktiviti'
            $sql = "UPDATE aktiviti SET 
                    nama_aktiviti = ?, jenis_aktiviti = ?, tarikh_mula = ?, 
                    tarikh_tamat = ?, masa_mula = ?, masa_tamat = ?, tempat = ?, 
                    guru_penasihat = ?, max_peserta = ?, deskripsi = ?, status = ?,
                    tajuk_laporan = ?, bilangan_ahli_hadir = ?, perbelanjaan = ?, guru_pelapor_id = ?
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $nama_aktiviti, $jenis_aktiviti, $tarikh_mula, $tarikh_tamat, 
                $masa_mula, $masa_tamat, $tempat, $guru_penasihat, $max_peserta, 
                $deskripsi, $status, $tajuk_laporan, $bilangan_ahli_hadir, $perbelanjaan, $guru_pelapor_id, 
                $id
            ]);
            
            $success = "Aktiviti & Laporan berjaya dikemaskini!";
            
            // Refresh data selepas update
            $stmt = $conn->prepare("SELECT * FROM aktiviti WHERE id = ?");
            $stmt->execute([$id]);
            $activity = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $errors[] = "Database error: " . $e->getMessage(); }
    }
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aktiviti - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .container-layout { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; transition: all 0.3s ease; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-header h3 { font-size: 1.2rem; font-weight: 600; display: flex; align-items: center; gap: 10px; color: white; }
        .sidebar-menu { list-style: none; padding: 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 13px 17px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }
        .form-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 6px solid #3498db; }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; width: 100%; } }
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

    <div class="container-layout">
        <div class="sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3></div>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-edit text-primary"></i> Kemaskini Aktiviti</h2>
                <a href="aktiviti_list.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST">
                    <h5 class="mb-3 text-success border-bottom pb-2">Maklumat Asas Aktiviti</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Nama Aktiviti</label>
                            <input type="text" name="nama_aktiviti" class="form-control" value="<?php echo htmlspecialchars($activity['nama_aktiviti']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Jenis Aktiviti</label>
                            <select name="jenis_aktiviti" class="form-select">
                                <option value="SUKAN & PERMAINAN" <?php if($activity['jenis_aktiviti']=='SUKAN & PERMAINAN') echo 'selected'; ?>>SUKAN & PERMAINAN</option>
                                <option value="KELAB & PERSATUAN" <?php if($activity['jenis_aktiviti']=='KELAB & PERSATUAN') echo 'selected'; ?>>KELAB & PERSATUAN</option>
                                <option value="BADAN BERUNIFORM" <?php if($activity['jenis_aktiviti']=='BADAN BERUNIFORM') echo 'selected'; ?>>BADAN BERUNIFORM</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tarikh Mula</label>
                            <input type="date" name="tarikh_mula" class="form-control" value="<?php echo $activity['tarikh_mula']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Tarikh Tamat</label>
                            <input type="date" name="tarikh_tamat" class="form-control" value="<?php echo $activity['tarikh_tamat']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Masa Mula</label>
                            <input type="time" name="masa_mula" class="form-control" value="<?php echo $activity['masa_mula']; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Masa Tamat</label>
                            <input type="time" name="masa_tamat" class="form-control" value="<?php echo $activity['masa_tamat']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tempat/Lokasi</label>
                            <input type="text" name="tempat" class="form-control" value="<?php echo htmlspecialchars($activity['tempat']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Guru Penasihat</label>
                            <input type="text" name="guru_penasihat" class="form-control" value="<?php echo htmlspecialchars($activity['guru_penasihat']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Max Peserta</label>
                            <input type="number" name="max_peserta" class="form-control" value="<?php echo $activity['max_peserta']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="AKTIF" <?php if($activity['status']=='AKTIF') echo 'selected'; ?>>AKTIF</option>
                                <option value="TAMAT" <?php if($activity['status']=='TAMAT') echo 'selected'; ?>>TAMAT</option>
                                <option value="BATAL" <?php if($activity['status']=='BATAL') echo 'selected'; ?>>BATAL</option>
                            </select>
                        </div>
                    </div>

                    <h5 class="mb-3 text-primary border-bottom pb-2 mt-4">Maklumat Laporan & Kewangan</h5>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Tajuk Laporan</label>
                            <input type="text" name="tajuk_laporan" class="form-control" value="<?php echo htmlspecialchars($activity['tajuk_laporan']); ?>" placeholder="Contoh: Laporan Perjumpaan Mingguan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Bilangan Ahli Hadir</label>
                            <input type="number" name="bilangan_ahli_hadir" class="form-control" value="<?php echo $activity['bilangan_ahli_hadir']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Kos / Perbelanjaan (RM)</label>
                            <input type="number" step="0.01" name="perbelanjaan" class="form-control" value="<?php echo $activity['perbelanjaan']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Guru Pelapor</label>
                            <select name="guru_pelapor_id" class="form-select">
                                <option value="">-- Pilih Guru Pelapor --</option>
                                <?php foreach($senarai_pengguna as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php if($activity['guru_pelapor_id'] == $p['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($p['nama_penuh']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Deskripsi Aktiviti/Kandungan Laporan</label>
                            <textarea name="deskripsi" class="form-control" rows="4"><?php echo htmlspecialchars($activity['deskripsi']); ?></textarea>
                        </div>
                    </div>

                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i>Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>