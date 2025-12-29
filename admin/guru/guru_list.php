<?php
// Start session for login check

require_once '../../includes/database.php'; 
require_once '../../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get filter values from URL
$search = $_GET['search'] ?? '';
$peranan_filter = $_GET['peranan'] ?? '';
$status_filter = $_GET['status'] ?? '';

// BUILD SQL QUERY
$query = "SELECT g.*, p.peranan as role, p.password, u.nama_unit 
          FROM guru g 
          LEFT JOIN pengguna p ON g.username = p.username 
          LEFT JOIN unit_kokurikulum u ON g.id = u.guru_penasihat_id
          WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (g.nama_penuh LIKE ? OR g.username LIKE ? OR g.emel LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($peranan_filter)) {
    $query .= " AND p.peranan = ?";
    $params[] = $peranan_filter;
}

if (!empty($status_filter)) {
    $query .= " AND g.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY g.nama_penuh ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$guru_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Guru - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../pelajar/pelajar.css">
    <style>
        /* KEEPING YOUR ORIGINAL CSS 100% */
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

        /* --- UPDATED TABLE STYLES TO PREVENT OVERFLOW --- */
        .students-table {
            overflow-x: auto; /* Enable scroll if needed on very small screens */
            background: white;
            border-radius: 10px;
        }
        .table { 
            font-size: 0.85rem !important; /* Smaller overall font */
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td { 
            padding: 10px 8px !important; /* Tighter padding */
            vertical-align: middle;
        }
        .btn-sm { 
            padding: 5px 10px !important; /* Smaller buttons */
            font-size: 0.75rem !important; 
        }
        .badge {
            font-size: 0.7rem !important;
            padding: 4px 8px !important;
        }
        .action-cell {
            white-space: nowrap; /* Keep buttons on one line */
            width: 1%; /* Shrink column to fit content only */
        }
        /* ----------------------------------------------- */

        .pws-container { display: flex; align-items: center; gap: 8px; font-family: monospace; }
        .toggle-pws { cursor: pointer; color: #3498db; transition: 0.2s; }
        .toggle-pws:hover { color: #2980b9; }
        .pws-hidden { letter-spacing: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="nav">
            <div class="logo"><i class="fas fa-school"></i> SMK KING EDWARD VII - Sistem Pengurusan Kokurikulum</div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo $_SESSION['nama_penuh']; ?> (Pentadbir)</span>
                <a href="../../auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3>
            </div>
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
            <div class="content-header">
                <div>
                    <h1 class="page-title"><i class="fas fa-chalkboard-teacher"></i> Pengurusan Guru</h1>
                </div>
                <div class="action-buttons">
                    <a href="guru_tambah.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Tambah Guru Baru</a>
                    <a href="../dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
                </div>
            </div>

            <div class="filters-section" style="margin-top: 20px;">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search">Cari Guru</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="Nama/Username..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="form-group">
                        <label for="peranan">Peranan</label>
                        <select id="peranan" name="peranan" class="form-control">
                            <option value="">Semua</option>
                            <option value="guru" <?php echo $peranan_filter == 'guru' ? 'selected' : ''; ?>>Guru</option>
                            <option value="admin" <?php echo $peranan_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="">Semua</option>
                            <option value="AKTIF" <?php echo $status_filter == 'AKTIF' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="TIDAK_AKTIF" <?php echo $status_filter == 'TIDAK_AKTIF' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="form-group"><button type="submit" class="btn btn-primary" style="margin-top: 25px;">Tapis</button></div>
                </form>
            </div>

            <div class="students-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Nama Penuh</th>
                            <th>Katalaluan (PWS)</th>
                            <th>Emel</th>
                            <th>Peranan</th>
                            <th>Kelab/Unit</th>
                            <th>Status</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($guru_data) > 0): ?>
                            <?php foreach ($guru_data as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['nama_penuh']); ?></td>
                                <td>
                                    <div class="pws-container">
                                        <span class="pws-text pws-hidden" data-pws="<?php echo htmlspecialchars($row['password']); ?>">••••••</span>
                                        <i class="fas fa-eye toggle-pws" onclick="togglePassword(this)"></i>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['emel']); ?></td>
                                <td><span class="badge badge-primary"><?php echo strtoupper($row['role'] ?? 'GURU'); ?></span></td>
                          
                               <td><?php echo htmlspecialchars($row['nama_unit'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $row['status'] == 'AKTIF' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="action-cell">
                                    <a href="guru_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="guru_process.php?action=delete&id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm" 
                                       onclick="return confirm('Padam guru ini?')">
                                        <i class="fas fa-trash"></i> Padam
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align: center; padding: 20px;">Tiada maklumat guru.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function togglePassword(icon) {
        const container = icon.parentElement;
        const textSpan = container.querySelector('.pws-text');
        const realPassword = textSpan.getAttribute('data-pws');
        
        if (textSpan.classList.contains('pws-hidden')) {
            textSpan.textContent = realPassword;
            textSpan.classList.remove('pws-hidden');
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            textSpan.textContent = '••••••';
            textSpan.classList.add('pws-hidden');
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>