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

// --- 1. KENDALI TINDAKAN PADAM ---
if (isset($_GET['delete'])) {
    try {
        $id = $_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM unit_kokurikulum WHERE id = ?");
        $stmt->execute([$id]);
        header("Location: koku_admin.php?msg=deleted");
        exit();
    } catch (Exception $e) {
        $error = "Gagal memadam unit. Sila pastikan tiada pelajar berdaftar di bawah unit ini.";
    }
}

// --- 2. KENDALI TINDAKAN TAMBAH ---
if (isset($_POST['add_unit'])) {
    try {
        $nama = $_POST['nama_unit'];
        $kod = $_POST['kod_unit'];
        $jenis = $_POST['jenis'];
        $guru_id = $_POST['guru_penasihat_id'];
        $lokasi = $_POST['lokasi_pertemuan'];
        $hari = $_POST['hari_pertemuan'];
        $masa = $_POST['masa_pertemuan'];

        $sql = "INSERT INTO unit_kokurikulum (nama_unit, kod_unit, jenis, guru_penasihat_id, lokasi_pertemuan, hari_pertemuan, masa_pertemuan, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'aktif')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$nama, $kod, $jenis, $guru_id, $lokasi, $hari, $masa]);
        header("Location: koku_admin.php?msg=added");
        exit();
    } catch (Exception $e) {
        $error = "Gagal menambah unit. Kod Unit mungkin sudah wujud.";
    }
}

try {
    // 3. Ambil data unit
    $query = "
        SELECT 
            u.*, 
            g.nama_penuh as nama_guru,
            COUNT(kp.pelajar_id) as bil_pelajar
        FROM unit_kokurikulum u
        LEFT JOIN guru g ON u.guru_penasihat_id = g.id
        LEFT JOIN keahlian_pelajar kp ON u.id = kp.unit_id AND kp.status = 'aktif'
        WHERE u.status = 'aktif'
        GROUP BY u.id
        ORDER BY u.jenis, u.nama_unit";
    
    $stmt_units = $conn->prepare($query);
    $stmt_units->execute();
    $units = $stmt_units->fetchAll(PDO::FETCH_ASSOC);

    // 4. Ambil Senarai Nama Pelajar untuk setiap unit (Dropdown)
    $student_list_query = "
        SELECT kp.unit_id, p.nama_penuh, p.kelas 
        FROM keahlian_pelajar kp 
        JOIN pelajar p ON kp.pelajar_id = p.id 
        WHERE kp.status = 'aktif'
        ORDER BY p.nama_penuh ASC";
    $stmt_students = $conn->prepare($student_list_query);
    $stmt_students->execute();
    $all_students = $stmt_students->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

    $teachers = $conn->query("SELECT id, nama_penuh FROM guru ORDER BY nama_penuh")->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e) { 
    $units = [];
    $teachers = [];
    $all_students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengurusan Kokurikulum - SMK King Edward VII</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS 100% IDENTIKAL DENGAN dashboard.php */
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
        
        /* Kad & Grid */
        .content-card { background: white; padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 2rem; border-top: 4px solid #3498db; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 5px; font-size: 0.9rem; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .btn-submit { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; }
        
        .koku-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .koku-card { position: relative; background: white; padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; border-top: 6px solid #3498db; transition: 0.3s; }
        .card-uniform { border-top-color: #e67e22; }
        .card-kelab { border-top-color: #9b59b6; }
        .card-sukan { border-top-color: #27ae60; }
        
        .koku-icon { font-size: 3rem; margin-bottom: 1.5rem; color: #34495e; opacity: 0.8; }
        
        /* New Dropdown Styles */
        .student-count { cursor: pointer; padding: 10px; background: #f8f9fa; border-radius: 8px; transition: 0.2s; }
        .student-count:hover { background: #e9ecef; }
        .student-count span { color: #2980b9; font-weight: 800; font-size: 1.4rem; }
        
        .student-dropdown { display: none; margin-top: 15px; text-align: left; max-height: 200px; overflow-y: auto; background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 10px; }
        .student-dropdown.active { display: block; }
        .student-row { font-size: 0.85rem; padding: 5px 0; border-bottom: 1px solid #f1f1f1; display: flex; justify-content: space-between; }
        .student-row:last-child { border-bottom: none; }
        .student-row .s-name { font-weight: 600; }
        .student-row .s-class { color: #7f8c8d; }

        .btn-delete { position: absolute; top: 15px; right: 15px; color: #e74c3c; background: #fdf2f2; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; }
        .btn-delete:hover { background: #e74c3c; color: white; }
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
                <li><a href="koku_admin.php" class="active"><i class="fas fa-football-ball"></i> Pengurusan Kokurikulum</a></li>
                <li><a href="aktiviti_list.php"><i class="fas fa-calendar-alt"></i> Pengurusan Aktiviti</a></li>
                <li><a href="permohonan_peruntukan.php"><i class="fas fa-file-invoice-dollar"></i> Permohonan Peruntukan</a></li>
                <li><a href="laporan_list.php"><i class="fas fa-chart-bar"></i> Laporan & Analisis</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="content-card">
                <h2><i class="fas fa-folder-plus"></i> Pendaftaran Unit Baharu</h2>
                <?php if(isset($error)): ?>
                    <p style="color: red; margin-bottom: 10px;"><?php echo $error; ?></p>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="form-grid">
                        <div class="form-group"><label>Nama Unit</label><input type="text" name="nama_unit" placeholder="Cth: Kelab Robotik" required></div>
                        <div class="form-group"><label>Kod Unit</label><input type="text" name="kod_unit" placeholder="Cth: KROB01" required></div>
                        <div class="form-group">
                            <label>Jenis Unit</label>
                            <select name="jenis" required>
                                <option value="kelab_persatuan">Kelab & Persatuan</option>
                                <option value="unit_beruniform">Badan Beruniform</option>
                                <option value="sukan_permainan">Sukan & Permainan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Guru Penasihat</label>
                            <select name="guru_penasihat_id">
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach($teachers as $t): ?>
                                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['nama_penuh']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 1fr 200px;">
                        <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi_pertemuan"></div>
                        <div class="form-group"><label>Hari</label><input type="text" name="hari_pertemuan"></div>
                        <div class="form-group"><label>Masa</label><input type="time" name="masa_pertemuan"></div>
                        <div class="form-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" name="add_unit" class="btn-submit"><i class="fas fa-save"></i> Daftar</button>
                        </div>
                    </div>
                </form>
            </div>

            <h2 style="margin-bottom: 20px;"><i class="fas fa-list"></i> Senarai Unit Kokurikulum</h2>
            <div class="koku-grid">
                <?php if (!empty($units)): ?>
                    <?php foreach($units as $unit): 
                        $unit_id = $unit['id'];
                        if($unit['jenis'] == 'kelab_persatuan') {
                            $cardType = 'card-kelab';
                            $icon = 'fa-users';
                        } elseif ($unit['jenis'] == 'unit_beruniform') {
                            $cardType = 'card-uniform';
                            $icon = 'fa-medal';
                        } else {
                            $cardType = 'card-sukan';
                            $icon = 'fa-futbol';
                        }
                    ?>
                    <div class="koku-card <?php echo $cardType; ?>">
                        <a href="?delete=<?php echo $unit_id; ?>" class="btn-delete" onclick="return confirm('Adakah anda pasti mahu memadam unit ini?')"><i class="fas fa-trash"></i></a>
                        <div class="koku-icon"><i class="fa-solid <?php echo $icon; ?>"></i></div>
                        <h3><?php echo htmlspecialchars($unit['nama_unit']); ?></h3>
                        <p style="margin: 5px 0;"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($unit['nama_guru'] ?? 'Tiada Guru'); ?></p>
                        
                        <div class="student-count">
    <a href="koku_members.php?id=<?php echo $unit_id; ?>" style="text-decoration: none; color: inherit;">
        Bil. Pelajar: <span><?php echo $unit['bil_pelajar']; ?></span>
        <br><small style="color: #3498db;">Klik untuk lihat senarai penuh</small>
    </a>
</div>

                        <div id="dropdown-<?php echo $unit_id; ?>" class="student-dropdown">
                            <?php if(isset($all_students[$unit_id])): ?>
                                <?php foreach($all_students[$unit_id] as $student): ?>
                                    <div class="student-row">
                                        <span class="s-name"><?php echo htmlspecialchars($student['nama_penuh']); ?></span>
                                        <span class="s-class"><?php echo htmlspecialchars($student['kelas']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.8rem; text-align: center; color: #999;">Tiada pelajar berdaftar.</p>
                            <?php endif; ?>
                        </div>

                        <hr style="margin: 15px 0; opacity: 0.1;">
                        <a href="koku_gallery.php?id=<?php echo $unit_id; ?>" style="text-decoration:none; color: #3498db; font-size: 0.9rem;"><i class="fas fa-images"></i> Galeri Aktiviti</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1;">Tiada unit kokurikulum dijumpai.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function toggleStudentList(id) {
        // Cari dropdown berdasarkan ID
        const dropdown = document.getElementById('dropdown-' + id);
        
        // Tutup dropdown lain yang sedang dibuka (optional)
        document.querySelectorAll('.student-dropdown').forEach(d => {
            if(d.id !== 'dropdown-' + id) d.classList.remove('active');
        });

        // Toggle dropdown yang dipilih
        dropdown.classList.toggle('active');
    }
    </script>
</body>
</html>