<?php
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Semak log masuk admin - Logik tepat daripada dashboard.php
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$unit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$message = "";

// Kendali Logik Padam
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("SELECT file_path FROM gambar_aktiviti WHERE id = ?");
    $stmt->execute([$delete_id]);
    $img_to_delete = $stmt->fetch();

    if ($img_to_delete) {
        $full_path = "../uploads/koku/" . $img_to_delete['file_path'];
        if (file_exists($full_path)) { unlink($full_path); }
        $stmt = $conn->prepare("DELETE FROM gambar_aktiviti WHERE id = ?");
        $stmt->execute([$delete_id]);
        $message = "<div class='activity-item' style='background:#d4edda; border-left-color:#27ae60;'>Gambar berjaya dipadam.</div>";
    }
}

// Kendali Logik Muat Naik
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
    $target_dir = "../uploads/koku/";
    if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

    foreach ($_FILES['images']['name'] as $key => $name) {
        if($_FILES['images']['error'][$key] == 0) {
            $file_name = time() . "_" . basename($name);
            if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $target_dir . $file_name)) {
                $stmt = $conn->prepare("INSERT INTO gambar_aktiviti (unit_id, file_path) VALUES (?, ?)");
                $stmt->execute([$unit_id, $file_name]);
            }
        }
    }
    $message = "<div class='activity-item' style='background:#d4edda; border-left-color:#27ae60;'>Gambar berjaya dimuat naik!</div>";
}

try {
    // Ambil Maklumat Unit & Bilangan Ahli
    $stmt = $conn->prepare("
        SELECT u.nama_unit, u.jenis, COUNT(kp.id) as bil_pelajar 
        FROM unit_kokurikulum u 
        LEFT JOIN keahlian_pelajar kp ON u.id = kp.unit_id AND kp.status = 'aktif'
        WHERE u.id = ? GROUP BY u.id");
    $stmt->execute([$unit_id]);
    $unit = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ambil Gambar dari pangkalan data
    $stmt_img = $conn->prepare("SELECT id, file_path FROM gambar_aktiviti WHERE unit_id = ? ORDER BY tarikh_muat_naik DESC");
    $stmt_img->execute([$unit_id]);
    $images = $stmt_img->fetchAll(PDO::FETCH_ASSOC);

    if (!$unit) { header('Location: koku_admin.php'); exit(); }
} catch(Exception $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri <?php echo $unit['nama_unit']; ?> - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* 100% IDENTICAL CSS FROM dashboard.php & koku_admin.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; color: #333; width: 100%; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
        .header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 0 2rem; height: 70px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: fixed; width: 100%; top: 0; z-index: 1000; display: flex; align-items: center; }
        .nav { display: flex; justify-content: space-between; align-items: center; width: 100%; }
        .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
        .user-info { display: flex; align-items: center; gap: 1rem; }
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; transition: background 0.3s; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
        .logout-btn:hover { background: #c0392b; }
        
        .container { display: flex; width: 100%; min-height: 100vh; padding-top: 70px; }
        .sidebar { width: 280px; background: #2c3e50; color: white; padding: 1rem 0; position: fixed; height: calc(100vh - 70px); overflow-y: auto; transition: all 0.3s ease; }
        .sidebar-header { padding: 0 2rem 1rem; border-bottom: 1px solid #34495e; margin-bottom: 1rem; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: white; text-decoration: none; padding: 15px 20px; transition: all 0.3s; border-left: 4px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #34495e; border-left-color: #3498db; }
        
        .main-content { flex: 1; padding: 2rem; margin-left: 280px; width: calc(100% - 280px); min-height: calc(100vh - 70px); transition: all 0.3s ease; }
        .section-title { color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #ecf0f1; display: flex; align-items: center; gap: 10px; }
        .activity-item { padding: 1rem; border-left: 4px solid #3498db; background: #f8f9fa; margin-bottom: 0.8rem; border-radius: 8px; }

        .gallery-header-box { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 2rem; border-left: 6px solid #3498db; display: flex; justify-content: space-between; align-items: center; }
        .member-badge { background: #e1f0fa; color: #2980b9; padding: 8px 16px; border-radius: 50px; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; }
        
        /* Gaya Kad Muat Naik */
        .upload-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 2rem; text-align: center; border: 2px dashed #3498db; }
        
        /* GAYA BUTANG HIJAU CUSTOM UNTUK PEMILIHAN FAIL */
        .custom-file-upload {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            transition: background 0.3s;
            margin-bottom: 10px;
        }
        .custom-file-upload:hover { background: #219150; }

        .gallery-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .gallery-item { position: relative; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1); height: 250px; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }

        .delete-btn { position: absolute; top: 10px; right: 10px; background: rgba(231, 76, 60, 0.9); color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s; z-index: 10; }
        .delete-btn:hover { background: #c0392b; transform: scale(1.1); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; width: 100%; }
            .gallery-grid { grid-template-columns: 1fr; }
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
                <li><a href="dashboard.php" ><i class="fas fa-home"></i> Dashboard Utama</a></li>
                <li><a href="guru/guru_list.php"><i class="fas fa-users"></i> Pengurusan Guru</a></li>
                <li><a href="pelajar/index.php"><i class="fas fa-user-graduate"></i> Pengurusan Pelajar</a></li>
                <li><a href="koku_admin.php" class="active"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <a href="koku_admin.php" style="text-decoration:none; color:#3498db; font-weight:500; display:inline-block; margin-bottom:1rem;">
                <i class="fas fa-arrow-left"></i> Kembali ke Senarai
            </a>

            <?php echo $message; ?>

            <div class="gallery-header-box">
                <div>
                    <h1 style="font-size: 1.8rem;"><?php echo $unit['nama_unit']; ?></h1>
                    <p style="color: #7f8c8d;">Galeri Aktiviti Rasmi</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    
                    <div class="member-badge">
                        <i class="fas fa-users"></i> <?php echo $unit['bil_pelajar']; ?> Ahli Aktif
                    </div>
                </div>
            </div>

            <div class="upload-card">
                <form action="" method="POST" enctype="multipart/form-data">
                    <p style="margin-bottom:15px; font-weight:600; color:#2c3e50;">Pilih Gambar Aktiviti Untuk Dimuat Naik</p>
                    
                    <input type="file" id="image-upload" name="images[]" multiple required accept="image/*" style="display: none;" onchange="displayFileCount(this)">
                    
                    <label for="image-upload" class="custom-file-upload">
                        <i class="fas fa-plus-circle"></i> Pilih Gambar Aktiviti
                    </label>
                    
                    <p id="file-count" style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 15px;">Tiada fail dipilih</p>

                    <button type="submit" class="logout-btn" style="background:#3498db; border-radius:8px; border:none; width: 200px; justify-content: center;">
                        <i class="fas fa-cloud-upload-alt"></i> Mula Muat Naik
                    </button>
                </form>
            </div>

            <div class="gallery-grid">
                <?php if (count($images) > 0): foreach ($images as $img): ?>
                    <div class="gallery-item">
                        <img src="../uploads/koku/<?php echo $img['file_path']; ?>" alt="Activity Photo">
                        <a href="?id=<?php echo $unit_id; ?>&delete_id=<?php echo $img['id']; ?>" 
                           class="delete-btn" 
                           onclick="return confirm('Adakah anda pasti mahu memadam gambar ini?')">
                            <i class="fas fa-trash-alt"></i>
                        </a>
                    </div>
                <?php endforeach; else: ?>
                    <div style="grid-column: span 2; text-align: center; padding: 3rem; color: #bdc3c7;">
                        <i class="fas fa-image" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Tiada gambar dijumpai. Sila pilih gambar di atas.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Fungsi untuk memaparkan bilangan fail yang telah dipilih
        function displayFileCount(input) {
            const countLabel = document.getElementById('file-count');
            if (input.files.length > 0) {
                countLabel.innerText = input.files.length + " fail telah dipilih";
                countLabel.style.color = "#27ae60";
                countLabel.style.fontWeight = "bold";
            } else {
                countLabel.innerText = "Tiada fail dipilih";
                countLabel.style.color = "#7f8c8d";
                countLabel.style.fontWeight = "normal";
            }
        }
    </script>
</body>
</html>