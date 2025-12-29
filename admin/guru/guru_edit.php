<?php

require_once '../../includes/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: guru_list.php");
    exit();
}

$guru_id = $_GET['id'];

// UPDATED SQL: Joins with 'pengguna' to retrieve the current password
try {
    $sql = "SELECT g.*, p.peranan as peranan_user, p.password 
            FROM guru g 
            LEFT JOIN pengguna p ON g.username = p.username 
            WHERE g.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$guru_id]);
    $guru = $stmt->fetch(PDO::FETCH_ASSOC);
    // --- ADD THIS HERE ---
// Fetch all available units for the dropdown
$stmt_units = $conn->query("SELECT id, nama_unit FROM unit_kokurikulum ORDER BY nama_unit ASC");
$all_units = $stmt_units->fetchAll(PDO::FETCH_ASSOC);

// Find if this guru is currently assigned to any unit in the unit_kokurikulum table
$stmt_current_unit = $conn->prepare("SELECT id FROM unit_kokurikulum WHERE guru_penasihat_id = ?");
$stmt_current_unit->execute([$guru_id]);
$current_unit = $stmt_current_unit->fetch(PDO::FETCH_ASSOC);
$current_unit_id = $current_unit ? $current_unit['id'] : '';
    if (!$guru) {
        header("Location: guru_list.php");
        exit();
    }
} catch (Exception $e) {
    header("Location: guru_list.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->beginTransaction();
        
        // 1. Update guru table
        $stmt_guru = $conn->prepare("UPDATE guru SET nama_penuh=?, emel=?, no_telefon=?, status=? WHERE id=?");
        $stmt_guru->execute([$_POST['nama_penuh'], $_POST['emel'], $_POST['no_telefon'], $_POST['status'], $guru_id]);
        
        // 2. Update pengguna table (Includes password and peranan updates)
        $stmt_user = $conn->prepare("UPDATE pengguna SET nama_penuh=?, email=?, peranan=?, password=? WHERE username=?");
        $stmt_user->execute([$_POST['nama_penuh'], $_POST['emel'], $_POST['peranan'], $_POST['password'], $guru['username']]);
        // ... after $stmt_user->execute(...) ...

// 3. Update Unit Assignment
$new_unit_id = $_POST['unit_id'];

// First, remove this guru from any unit they currently advise to ensure one-to-one mapping
$stmt_clear = $conn->prepare("UPDATE unit_kokurikulum SET guru_penasihat_id = NULL WHERE guru_penasihat_id = ?");
$stmt_clear->execute([$guru_id]);

// If a unit was selected from the dropdown, assign the guru to it
if (!empty($new_unit_id)) {
    $stmt_assign = $conn->prepare("UPDATE unit_kokurikulum SET guru_penasihat_id = ? WHERE id = ?");
    $stmt_assign->execute([$guru_id, $new_unit_id]);
}

        $conn->commit();
        header("Location: guru_list.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $error = "Ralat: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guru - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; display: flex; align-items: center; gap: 8px; }
        .logout-btn:hover { background: #e74c3c; }
        .container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; transition: all 0.3s ease; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 15px 20px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }
        .form-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 6px solid #3498db; }
        .form-group { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #ecf0f1; border-radius: 8px; transition: all 0.3s; }
        .form-control:focus { outline: none; border-color: #3498db; }
        .form-control:disabled { background-color: #f1f3f5; cursor: not-allowed; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; display: inline-flex; align-items: center; gap: 8px; transition: 0.3s; text-decoration: none; }
        .btn-success { background: #2ecc71; color: white; }
        .btn-primary { background: #3498db; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo"><i class="fas fa-school"></i> SMK KING EDWARD VII - Sistem Pengurusan Kokurikulum</div>
            <div class="user-info">
                <span><i class="fas fa-user"></i> <?php echo $_SESSION['nama_penuh']; ?> (Pentadbir)</span>
                <a href="../../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header"><h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3></div>
            <ul class="sidebar-menu">
                <li><a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru_list.php" class="active"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="../pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="../koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="../aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="../permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="../laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="form-card">
                <h2 style="margin-bottom: 25px;"><i class="fas fa-user-edit"></i> Kemaskini Maklumat Guru</h2>
                
                <?php if(isset($error)): ?>
                    <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label class="form-label">Username (ID Pengguna)</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($guru['username']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Katalaluan (PWS)</label>
                            <input type="text" name="password" class="form-control" value="<?php echo htmlspecialchars($guru['password']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nama Penuh</label>
                            <input type="text" name="nama_penuh" class="form-control" value="<?php echo htmlspecialchars($guru['nama_penuh']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Emel Rasmi</label>
                            <input type="email" name="emel" class="form-control" value="<?php echo htmlspecialchars($guru['emel']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">No Telefon</label>
                            <input type="text" name="no_telefon" class="form-control" value="<?php echo htmlspecialchars($guru['no_telefon'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Peranan Sistem</label>
                            <select name="peranan" class="form-control">
                                <option value="guru" <?php echo ($guru['peranan_user'] == 'guru') ? 'selected' : ''; ?>>Guru</option>
                                <option value="admin" <?php echo ($guru['peranan_user'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Status Guru</label>
                            <select name="status" class="form-control">
                                <option value="AKTIF" <?php echo ($guru['status'] == 'AKTIF') ? 'selected' : ''; ?>>Aktif</option>
                                <option value="TIDAK_AKTIF" <?php echo ($guru['status'] == 'TIDAK_AKTIF') ? 'selected' : ''; ?>>Tidak Aktif</option>
                            </select>
                        </div>
                    </div>
                        <div class="form-group">
                            <label class="form-label">Unit Kokurikulum (Guru Penasihat)</label>
                            <select name="unit_id" class="form-control">
                            <option value="">-- Tiada Unit / Kelab --</option>
                            <?php foreach ($all_units as $unit): ?>
                                <option value="<?php echo $unit['id']; ?>" <?php echo ($current_unit_id == $unit['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($unit['nama_unit']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small style="color: #666;">* Pilih unit jika guru ini adalah penasihat unit tersebut.</small>
                    </div>
                    <div style="margin-top: 25px; display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success"><i class="fas fa-sync"></i> Kemaskini Maklumat</button>
                        <a href="guru_list.php" class="btn btn-primary"><i class="fas fa-times"></i> Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>