<?php
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Semak jika pengguna log masuk sebagai admin
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ambil ID unit dari URL
$unit_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$unit_id) {
    header('Location: koku_admin.php');
    exit();
}

try {
    // 1. Ambil maklumat unit
    $stmt_unit = $conn->prepare("SELECT nama_unit, jenis FROM unit_kokurikulum WHERE id = ?");
    $stmt_unit->execute([$unit_id]);
    $unit_info = $stmt_unit->fetch(PDO::FETCH_ASSOC);

    // 2. Ambil senarai ahli (Tanpa kolum Kelab/Persatuan)
    $query = "
        SELECT 
            p.no_matrik, 
            p.nama_penuh, 
            p.kelas, 
            p.jantina
        FROM pelajar p
        JOIN keahlian_pelajar kp ON p.id = kp.pelajar_id
        WHERE kp.unit_id = ? AND kp.status = 'aktif'
        ORDER BY p.nama_penuh ASC";
    
    $stmt_members = $conn->prepare($query);
    $stmt_members->execute([$unit_id]);
    $members = $stmt_members->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) { 
    $members = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senarai Ahli - <?php echo htmlspecialchars($unit_info['nama_unit']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS IDENTIKAL DENGAN koku_admin.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; }
        .container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 15px 20px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); }
        
        .content-card { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-top: 4px solid #3498db; }
        
        /* Table Styling matching image_6157e6.png */
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 10px 10px 0 0; overflow: hidden; }
        .styled-table thead tr { background-color: #2c3e50; color: #ffffff; text-align: left; font-weight: bold; }
        .styled-table th, .styled-table td { padding: 15px 20px; }
        .styled-table tbody tr { border-bottom: 1px solid #dddddd; }
        .styled-table tbody tr:nth-of-type(even) { background-color: #f3f3f3; }
        .styled-table tbody tr:last-of-type { border-bottom: 2px solid #3498db; }

        .btn-action-group { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-back { background: #7f8c8d; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-gallery { background: #3498db; color: white; padding: 10px 15px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px; font-size: 0.9rem; }
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
                <li><a href="koku_admin.php" class="active"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="btn-action-group">
                <a href="koku_admin.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <a href="koku_gallery.php?id=<?php echo $unit_id; ?>" class="btn-gallery"><i class="fas fa-images"></i> Galeri Aktiviti</a>
            </div>

            <div class="content-card">
                <h2><i class="fas fa-users"></i> Senarai Ahli: <?php echo htmlspecialchars($unit_info['nama_unit']); ?></h2>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th>No Matrik</th>
                            <th>Nama Penuh</th>
                            <th>Kelas</th>
                            <th>Jantina</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($members)): ?>
                            <?php foreach($members as $m): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($m['no_matrik']); ?></strong></td>
                                <td><?php echo htmlspecialchars($m['nama_penuh']); ?></td>
                                <td><?php echo htmlspecialchars($m['kelas']); ?></td>
                                <td><?php echo ($m['jantina'] == 'L') ? 'Lelaki' : 'Perempuan'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Tiada ahli berdaftar untuk unit ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>