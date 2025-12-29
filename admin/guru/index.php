<?php
require_once '../../includes/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Search and filter functionality
$search = $_GET['search'] ?? '';
$kelas = $_GET['kelas'] ?? '';
$status = $_GET['status'] ?? 'aktif';

// Build query with filters
$query = "SELECT * FROM pelajar WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (nama_penuh LIKE ? OR no_matrik LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($kelas)) {
    $query .= " AND kelas = ?";
    $params[] = $kelas;
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY nama_penuh ASC";

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->execute($params);
} else {
    $stmt->execute();
}
$pelajar = $stmt->fetchAll();

// Get unique classes for filter dropdown
$kelas_list = $conn->query("SELECT DISTINCT kelas FROM pelajar WHERE kelas IS NOT NULL ORDER BY kelas")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Pelajar - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Include Dashboard Base CSS -->
    <style>
        /* Dashboard Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .container {
            display: flex;
            min-height: 100vh;
            padding-top: 80px;
        }

        .sidebar {
            width: 280px;
            background: #2c3e50;
            color: white;
            padding: 2rem 0;
            position: fixed;
            height: calc(100vh - 80px);
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 2rem 1rem;
            border-bottom: 1px solid #34495e;
            margin-bottom: 1rem;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            padding: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            padding: 15px 30px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover {
            background: #34495e;
            border-left-color: #3498db;
        }

        .sidebar-menu a.active {
            background: #34495e;
            border-left-color: #3498db;
            font-weight: bold;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            margin-left: 280px;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
    <!-- Include Pelajar Specific CSS -->
    <link rel="stylesheet" href="guru.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="nav">
            <div class="logo">
                <i class="fas fa-school"></i>
                SMK KING EDWARD VII - Pengurusan Pelajar
            </div>
            <div class="user-info">
                <span><i class="fas fa-user-shield"></i> <?php echo $_SESSION['nama_penuh']; ?> (Pentadbir)</span>
                <a href="../../auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Include Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Content Header -->
            <div class="content-header">
                <div>
                    <h1 class="page-title"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</h1>
                    <p>Urus maklumat pelajar SMK King Edward VII</p>
                </div>
                <div class="action-buttons">
                    <a href="tambah.php" class="btn btn-success">
                        <i class="fas fa-user-plus"></i> Daftar Pelajar Baharu
                    </a>
                    <a href="../dashboard.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="filters-section">
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label for="search"><i class="fas fa-search"></i> Cari Pelajar</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               placeholder="Nama atau No Matrik..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="kelas"><i class="fas fa-door-open"></i> Kelas</label>
                        <select id="kelas" name="kelas" class="form-control">
                            <option value="">Semua Kelas</option>
                            <?php foreach($kelas_list as $k): ?>
                                <option value="<?php echo $k['kelas']; ?>" 
                                    <?php echo $kelas == $k['kelas'] ? 'selected' : ''; ?>>
                                    <?php echo $k['kelas']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="status"><i class="fas fa-circle"></i> Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="aktif" <?php echo $status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="tamat" <?php echo $status == 'tamat' ? 'selected' : ''; ?>>Tamat</option>
                            <option value="berpindah" <?php echo $status == 'berpindah' ? 'selected' : ''; ?>>Berpindah</option>
                            <option value="" <?php echo $status == '' ? 'selected' : ''; ?>>Semua Status</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="height: fit-content; padding: 12px 24px;">
                            <i class="fas fa-filter"></i> Tapis
                        </button>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="students-table">
                <?php if(count($pelajar) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No Matrik</th>
                                <th>Nama Penuh</th>
                                <th>No KP</th>
                                <th>Kelas</th>
                                <th>Jantina</th>
                                <th>Status</th>
                                <th>Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pelajar as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['no_matrik']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nama_penuh']); ?></td>
                                    <td><?php echo htmlspecialchars($p['ic_number']); ?></td>
                                    <td><?php echo htmlspecialchars($p['kelas']); ?></td>
                                    <td><?php echo $p['jantina'] == 'L' ? 'Lelaki' : 'Perempuan'; ?></td>
                                    <td>
                                        <?php if($p['status'] == 'aktif'): ?>
                                            <span class="badge badge-success">Aktif</span>
                                        <?php elseif($p['status'] == 'tamat'): ?>
                                            <span class="badge badge-warning">Tamat</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Berpindah</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-cell">
                                        <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="proses.php?action=delete&id=<?php echo $p['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Adakah anda pasti ingin padam pelajar ini?')">
                                            <i class="fas fa-trash"></i> Padam
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-user-slash"></i>
                        <h3>Tiada Pelajar Ditemui</h3>
                        <p><?php echo !empty($search) ? 'Cubalah menukar kata kunci carian atau tapisan' : 'Belum ada pelajar didaftarkan dalam sistem'; ?></p>
                        <?php if(empty($search)): ?>
                            <a href="tambah.php" class="btn btn-success mt-2">
                                <i class="fas fa-user-plus"></i> Daftar Pelajar Pertama
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple confirmation for delete actions
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-danger');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Adakah anda pasti ingin padam rekod pelajar ini?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>