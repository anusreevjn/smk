<?php

require_once '../includes/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

try {
    // Bahagian Query SQL di atas fail
$query = "SELECT 
            a.id as activity_id,
            a.tarikh_mula, 
            IFNULL(a.tajuk_laporan, 'TIADA LAPORAN') as tajuk_laporan, 
            a.nama_aktiviti, 
            a.tempat, 
            a.bilangan_ahli_hadir, 
            a.fail_resit,  /* Tambah ini */
            a.perbelanjaan, 
            a.deskripsi,
            IFNULL(p.nama_penuh, 'Belum Dilapor') as nama_guru_pelapor 
          FROM aktiviti a
          LEFT JOIN pengguna p ON a.guru_pelapor_id = p.id
          ORDER BY a.tarikh_mula DESC";
          
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $laporan = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $laporan = []; }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Analisis - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ORIGINAL CSS */
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

        .report-card { background: white; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.1); overflow: hidden; margin-top: 20px; border-top: 5px solid #27ae60; }
        .table { width: 100%; border-collapse: collapse; }
        .table thead tr { background-color: #f8f9fa; border-bottom: 2px solid #dee2e6; } 
        .table thead th { color: #2c3e50; padding: 15px; font-weight: 600; font-size: 0.85rem; text-align: center; }
        .table tbody td { padding: 12px; border-bottom: 1px solid #eee; color: #555; font-size: 0.85rem; text-align: center; }
        
        .action-container { display: flex; justify-content: center; gap: 8px; }
        .btn-action { padding: 6px 12px; border-radius: 5px; font-size: 0.75rem; text-decoration: none; color: white; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .btn-edit { background: #3498db; } 
        .btn-padam { background: #e74c3c; }
.resit-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        /* PRINT STYLING */
        @media print {
            .header, .sidebar, .logout-btn, .btn-print-hidden {
                display: none !important;
            }

            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 0 !important;
            }

            /* Hide the "Tindakan" column (now the 8th column) */
            .table th:nth-child(8), 
            .table td:nth-child(8) {
                display: none !important;
            }

            .report-card {
                box-shadow: none !important;
                border: none !important;
            }
            
            body {
                background: white !important;
            }

            .table {
                border: 1px solid #ddd;
            }

            .resit-preview {
                display: block !important;
                max-width: 120px !important;
                height: auto !important;
            }
            
            .no-print {
                display: none !important;
            }
             }     /* Search Bar Styling */
.search-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-wrapper i {
    position: absolute;
    left: 12px;
    color: #888;
}

.search-wrapper input {
    padding: 10px 15px 10px 35px;
    border: 1px solid #ddd;
    border-radius: 8px;
    width: 100%; /* Changed to 100% to fill the centered container */
    font-size: 0.9rem;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.search-wrapper input:focus {
    outline: none;
    border-color: #165d4d;
    box-shadow: 0 0 8px rgba(22, 93, 77, 0.2);
    /* Removed the width: 300px expansion to keep it stable in the center */
}

/* Hide search bar during print */
@media print {
    .search-wrapper {
        display: none !important;
    }
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
            <div class="sidebar-header"><h3><i class="fas fa-tachometer-alt"></i> Menu Utama</h3></div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" ><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php" class="active"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
           <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 2rem; width: 100%; gap: 20px;">
        <h1><i class="fas fa-file-invoice"></i> Laporan Aktiviti Kokurikulum</h1>
    
    <div style="display: flex; gap: 10px; align-items: center; justify-content: center; width: 100%; max-width: 600px;">
            <div class="search-wrapper" style="flex-grow: 1;">
            <i class="fas fa-search"></i>
            <input type="text" id="tableSearch" placeholder="Cari laporan..." onkeyup="filterTable()">
        </div>
        
        <button onclick="window.print()" class="logout-btn btn-print-hidden" style="background: #3498db;">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

            <div class="report-card">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tarikh</th>
                            <th>Tajuk Laporan</th>
                            <th>Aktiviti/Deskripsi</th>
                            <th>Hadir</th>
                            <th>Resit</th>
                            <th>Kos (RM)</th>
                            <th>Guru Pelapor</th>
                            <th>Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php foreach($laporan as $row): ?>
    <tr>
        <td><?php echo date('d/m/Y', strtotime($row['tarikh_mula'])); ?></td>
        <td style="text-align: left;"><strong><?php echo htmlspecialchars($row['nama_aktiviti']); ?></strong></td>
                            <td style="text-align: left;"><?php echo htmlspecialchars($row['deskripsi']); ?></td>
        <td><?php echo $row['bilangan_ahli_hadir']; ?></td>
        
        <td>
            <?php if (!empty($row['fail_resit'])): ?>
                <img src="../uploads/resit/<?php echo $row['fail_resit']; ?>" class="resit-thumb resit-preview">
            <?php else: ?>
                <span style="color: #ccc;">Tiada</span>
            <?php endif; ?>
        </td>

        <td><?php echo number_format($row['perbelanjaan'], 2); ?></td>
        <td><?php echo htmlspecialchars($row['nama_guru_pelapor']); ?></td>
        
        <td class="btn-print-hidden">
            <div class="action-container">
                <a href="edit_laporan.php?id=<?php echo $row['activity_id']; ?>" class="btn-action btn-edit"><i class="fas fa-edit"></i> Edit</a>
                <a href="proses_laporan.php?action=delete&id=<?php echo $row['activity_id']; ?>" class="btn-action btn-padam" onclick="return confirm('Padam rekod ini?')"><i class="fas fa-trash"></i> Padam</a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
function filterTable() {
    // Declare variables
    var input, filter, table, tr, td, i, j, txtValue, found;
    input = document.getElementById("tableSearch");
    filter = input.value.toUpperCase();
    table = document.querySelector(".table");
    tr = table.getElementsByTagName("tr");

    // Loop through all table rows (skipping the header)
    for (i = 1; i < tr.length; i++) {
        td = tr[i].getElementsByTagName("td");
        found = false;
        
        // Loop through each cell in the row
        for (j = 0; j < td.length; j++) {
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break; 
                }
            }
        }
        
        // Show or hide the row based on the search
        if (found) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
</body>
</html>