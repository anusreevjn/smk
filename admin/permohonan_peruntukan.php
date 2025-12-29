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
$message = "";
$current_guru_id = $_SESSION['user_id'] ?? null;
// 1. HANDLE DELETE
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM permohonan_peruntukan WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "Permohonan berjaya dipadam!";
    }
}

// 2. HANDLE CREATE / UPDATE (Includes Admin Status Edit)
if (isset($_POST['save_permohonan'])) {
    $id = $_POST['request_id'];
    $guru_id = $_POST['guru_id'];
    $unit_id = $_POST['unit_id'];
    $tarikh = $_POST['tarikh'];
    $minggu = $_POST['minggu'];
    $tajuk = $_POST['tajuk'];
    $objektif = $_POST['objektif'];
    $kos = $_POST['kos'];
    
    // Admin fields
    $status = $_POST['status_permohonan'] ?? 'dihantar';
    $ulasan = $_POST['ulasan_admin'] ?? '';

    if (empty($id)) {
        // Create
        $sql = "INSERT INTO permohonan_peruntukan (guru_id, unit_id, tarikh, minggu, tajuk, objektif, kos, status_permohonan, ulasan_admin) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$guru_id, $unit_id, $tarikh, $minggu, $tajuk, $objektif, $kos, $status, $ulasan]);
    } else {
        // Update
        $sql = "UPDATE permohonan_peruntukan SET guru_id=?, unit_id=?, tarikh=?, minggu=?, tajuk=?, objektif=?, kos=?, status_permohonan=?, ulasan_admin=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$guru_id, $unit_id, $tarikh, $minggu, $tajuk, $objektif, $kos, $status, $ulasan, $id]);
    }
    header("Location: permohonan_peruntukan.php");
    exit();
}

// 3. FETCH DATA FOR LIST
$query = "
    SELECT p.*, g.nama_penuh as nama_guru, u.nama_unit 
    FROM permohonan_peruntukan p
    LEFT JOIN guru g ON p.guru_id = g.id
    LEFT JOIN unit_kokurikulum u ON p.unit_id = u.id
    ORDER BY p.created_at DESC";
$requests = $conn->query($query)->fetchAll();

// 4. FETCH DROPDOWN DATA
$all_guru = $conn->query("SELECT id, nama_penuh FROM guru ORDER BY nama_penuh")->fetchAll();
$all_units = $conn->query("SELECT id, nama_unit FROM unit_kokurikulum ORDER BY nama_unit")->fetchAll();

// 5. FETCH SINGLE FOR EDIT
$edit_data = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM permohonan_peruntukan WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_data = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permohonan Peruntukan - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS 100% COPIED FROM YOUR DASHBOARD.PHP */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .logout-btn:hover { background: #c0392b; }
        .container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 15px 20px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }
        .welcome-section { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 2rem; border-left: 6px solid #3498db; }
        
        /* Table and Form Styling from previous page */
        .content-card { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .data-table th { background: #f8f9fa; padding: 12px; text-align: left; border-bottom: 2px solid #eee; }
        .data-table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 0.9rem; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; font-weight: bold; }
        .badge-dihantar { background: #fff3cd; color: #856404; }
        .badge-diluluskan { background: #d4edda; color: #155724; }
        .badge-tidak_diluluskan { background: #f8d7da; color: #721c24; }
        
        .btn-action { padding: 5px 8px; border-radius: 4px; color: white; text-decoration: none; font-size: 0.8rem; margin-right: 5px; border: none; cursor: pointer; }
        .btn-edit { background: #3498db; }
        .btn-delete { background: #e74c3c; }

        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; font-family: inherit; }
        .btn-save { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-top: 10px; }
        
        /* Admin highlight */
        .admin-edit-section { background: #fff9db; padding: 1rem; border-radius: 8px; border: 1px dashed #f1c40f; margin-top: 10px; }

        /* Tablet/Mobile Adjustments from Dashboard */
        @media (max-width: 992px) {
            .sidebar { width: 80px; }
            .sidebar-header h3, .sidebar-menu a span { display: none; }
            .main-content { margin-left: 80px; width: calc(100% - 80px); }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; padding: 1rem; }
            .user-info span { display: none; }
        }
        /* Add this to your existing CSS section */
.action-container {
    display: flex;
    gap: 10px; /* Adds space between buttons */
    align-items: center;
    white-space: nowrap; /* Prevents buttons from wrapping to a second line */
}

.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 0.8rem;
    white-space: nowrap; /* Prevents text from wrapping */
}

.btn-action:hover {
    opacity: 0.8;
}
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
            <div class="sidebar-header">
                <h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="welcome-section">
                <h1><i class="fas fa-hand-holding-usd"></i>Permohonan Peruntukan</h1>
                <p>Kemas kini status kelulusan dana untuk aktiviti kokurikulum sekolah.</p>
            </div>

            <div class="content-card">
                <h3><i class="fas fa-edit"></i> <?php echo $edit_data ? 'Kemaskini Permohonan' : 'Daftar Baru'; ?></h3><br>
                <form method="POST">
                    <input type="hidden" name="request_id" value="<?php echo $edit_data['id'] ?? ''; ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Unit Kokurikulum</label>
                            <select name="unit_id" required>
                                <option value="">-- Pilih Unit --</option>
                                <?php foreach($all_units as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo (isset($edit_data['unit_id']) && $edit_data['unit_id'] == $u['id']) ? 'selected' : ''; ?>>
                                        <?php echo $u['nama_unit']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Guru Pemohon</label>
                            <select name="guru_id" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach($all_guru as $g): ?>
                                    <option value="<?php echo $g['id']; ?>" <?php echo (isset($edit_data['guru_id']) && $edit_data['guru_id'] == $g['id']) ? 'selected' : ''; ?>>
                                        <?php echo $g['nama_penuh']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tarikh Aktiviti</label>
                            <input type="date" name="tarikh" value="<?php echo $edit_data['tarikh'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Minggu</label>
                            <input type="text" name="minggu" value="<?php echo $edit_data['minggu'] ?? ''; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tajuk Permohonan</label>
                        <select name="tajuk" required>
                        <option value="">-- Pilih Tajuk --</option>
                        <option value="Pertandingan" <?php echo (isset($edit_data['tajuk']) && $edit_data['tajuk'] == 'Pertandingan') ? 'selected' : ''; ?>>Pertandingan</option>
                        <option value="Aktiviti" <?php echo (isset($edit_data['tajuk']) && $edit_data['tajuk'] == 'Aktiviti') ? 'selected' : ''; ?>>Aktiviti</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Kos (RM)</label>
                        <input type="number" step="0.01" name="kos" value="<?php echo $edit_data['kos'] ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Objektif / Deskripsi</label>
                        <textarea name="objektif" rows="3" required><?php echo $edit_data['objektif'] ?? ''; ?></textarea>
                    </div>

                    <?php if($edit_data): ?>
                    <div class="admin-edit-section">
                        <h4><i class="fas fa-user-check"></i>Perlulusan Pentadbir</h4><br>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status_permohonan">
                                    <option value="dihantar" <?php echo $edit_data['status_permohonan'] == 'dihantar' ? 'selected' : ''; ?>>DIHANTAR</option>
                                    <option value="diluluskan" <?php echo $edit_data['status_permohonan'] == 'diluluskan' ? 'selected' : ''; ?>>LULUS</option>
                                    <option value="tidak_diluluskan" <?php echo $edit_data['status_permohonan'] == 'tidak_diluluskan' ? 'selected' : ''; ?>>GAGAL</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ulasan Admin (Remarks)</label>
                                <textarea name="ulasan_admin" rows="1"><?php echo htmlspecialchars($edit_data['ulasan_admin'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" name="save_permohonan" class="btn-save">
                        <i class="fas fa-save"></i> Simpan Maklumat
                    </button>
                    <?php if($edit_data): ?>
                        <a href="permohonan_peruntukan.php" style="text-decoration:none; color:grey; margin-left:10px;">Batal</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="content-card">
                <h3><i class="fas fa-list"></i> Senarai Permohonan</h3>
                <table class="data-table">
                    <thead>
    <tr>
        <th>Tarikh/Minggu</th>
        <th>Unit</th>
        <th>Tajuk</th>
        <th>Objektif/Ulasan Guru</th> <th>Kos (RM)</th>
        <th>Status</th>
        <th>Tindakan</th>
    </tr>
</thead>
                    <tbody>
    <?php foreach($requests as $row): ?>
    <tr>
        <td><?php echo date('d/m/y', strtotime($row['tarikh'])); ?><br><small><?php echo htmlspecialchars($row['minggu'] ?? ''); ?></small></td>
        <td><strong><?php echo htmlspecialchars($row['nama_unit'] ?? ''); ?></strong></td>
        <td><?php echo htmlspecialchars($row['tajuk'] ?? ''); ?></td>
        
        <td>
            <small><?php echo htmlspecialchars($row['objektif'] ?? ''); ?></small>
            <?php if(!empty($row['ulasan_admin'])): ?>
                <br><small style="color: #e67e22;"><em>Nota Admin: <?php echo htmlspecialchars($row['ulasan_admin'] ?? ''); ?></em></small>
            <?php endif; ?>
        </td>

        <td><?php echo number_format($row['kos'], 2); ?></td>
        <td>
            <span class="badge badge-<?php echo $row['status_permohonan']; ?>">
                <?php 
                    if($row['status_permohonan'] == 'tidak_diluluskan') echo "GAGAL";
                    else echo strtoupper($row['status_permohonan']); 
                ?>
            </span>
        </td>
        <td>
            
    <div class="action-container">
        <a href="?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit">
            <i class="fas fa-edit"></i> Edit
        </a>

        <a href="?delete=<?php echo $row['id']; ?>" 
           class="btn-action btn-delete" 
           onclick="return confirm('Adakah anda pasti mahu memadam permohonan ini?')">
            <i class="fas fa-trash"></i> Padam
        </a>
    </div>
</td>
        
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>