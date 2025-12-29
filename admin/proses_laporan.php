<?php
session_start();
require_once '../includes/database.php';

// Pastikan hanya admin atau guru yang sah boleh mengakses fail ini
if (!isset($_SESSION['user_id']) || ($_SESSION['peranan'] != 'admin' && $_SESSION['peranan'] != 'guru')) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$action = $_GET['action'] ?? '';

if ($action == 'update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    // FIXED: Match these names with the 'name' attribute in your edit_laporan.php form
    $nama_aktiviti = $_POST['nama_aktiviti']; 
    $deskripsi = $_POST['deskripsi']; 
    $tarikh = $_POST['tarikh_mula'];
    $hadir = $_POST['bilangan_ahli_hadir'];
    $kos = $_POST['perbelanjaan'];
    $guru_id = $_POST['guru_pelapor_id'];
    // 1. Verifikasi hak milik sebelum kemaskini
    $stmt_chk = $conn->prepare("SELECT * FROM aktiviti WHERE id = ?");
    $stmt_chk->execute([$id]);
    $activity = $stmt_chk->fetch(PDO::FETCH_ASSOC);

    // Memetakan pengguna sesi ke jadual guru untuk mencari ID guru dalaman
    $stmt_map = $conn->prepare("SELECT id FROM guru WHERE nama_penuh = ? LIMIT 1");
    $stmt_map->execute([$_SESSION['nama_penuh']]);
    $map = $stmt_map->fetch(PDO::FETCH_ASSOC);
    $current_guru_id = $map['id'] ?? null;

    $owned = false;
    // Admin mempunyai akses penuh, manakala guru hanya boleh mengedit laporan sendiri
    if ($_SESSION['peranan'] == 'admin') {
        $owned = true;
    } elseif ($activity) {
        if (!empty($activity['guru_pelapor_id']) && (
                $activity['guru_pelapor_id'] == $_SESSION['user_id'] ||
                ($current_guru_id && $activity['guru_pelapor_id'] == $current_guru_id)
            )) {
            $owned = true;
        } elseif (empty($activity['guru_pelapor_id']) && !empty($activity['guru_penasihat']) &&
                  strcasecmp(trim($activity['guru_penasihat']), trim($_SESSION['nama_penuh'])) === 0) {
            $owned = true;
        }
    }

    if (!$owned) {
        header('Location: laporan_list.php');
        exit();
    }

    // 2. Proses Muat Naik Fail Resit
    $nama_fail_db = $activity['fail_resit'] ?? null; // Kekalkan nama fail sedia ada jika tiada muat naik baru
    if (isset($_FILES['fail_resit']) && $_FILES['fail_resit']['error'] == 0) {
        $target_dir = "../uploads/resit/";
        
        // Cipta folder jika belum wujud
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['fail_resit']['name'], PATHINFO_EXTENSION);
        $new_file_name = "resit_" . time() . "_" . $id . "." . $file_ext;
        $target_file = $target_dir . $new_file_name;

        if (move_uploaded_file($_FILES['fail_resit']['tmp_name'], $target_file)) {
            // Padam fail lama jika berjaya muat naik fail baru
            if (!empty($activity['fail_resit']) && file_exists($target_dir . $activity['fail_resit'])) {
                unlink($target_dir . $activity['fail_resit']);
            }
            $nama_fail_db = $new_file_name;
        }
    }


    // ... (Keep your ownership verification logic here) ...

    try {
        // 3. Kemaskini Database - UPDATED SQL to include deskripsi
        $sql = "UPDATE aktiviti SET 
                nama_aktiviti = ?, 
                deskripsi = ?, 
                tarikh_mula = ?, 
                bilangan_ahli_hadir = ?, 
                fail_resit = ?, 
                perbelanjaan = ?, 
                guru_pelapor_id = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        // Ensure the order here matches the ? in the SQL string above
        $stmt->execute([$nama_aktiviti, $deskripsi, $tarikh, $hadir, $nama_fail_db, $kos, $guru_id, $id]);

        header('Location: laporan_list.php?msg=success_update');
        exit();
    } catch (PDOException $e) {
        // Log error for debugging: error_log($e->getMessage());
        header('Location: laporan_list.php?msg=error');
        exit();
    }
}

// Bahagian Delete dengan pengesahan hak milik
if ($action == 'delete') {
    $id = $_GET['id'];

    $stmt_chk = $conn->prepare("SELECT * FROM aktiviti WHERE id = ?");
    $stmt_chk->execute([$id]);
    $activity = $stmt_chk->fetch(PDO::FETCH_ASSOC);

    $stmt_map = $conn->prepare("SELECT id FROM guru WHERE nama_penuh = ? LIMIT 1");
    $stmt_map->execute([$_SESSION['nama_penuh']]);
    $map = $stmt_map->fetch(PDO::FETCH_ASSOC);
    $current_guru_id = $map['id'] ?? null;

    $owned = false;
    if ($_SESSION['peranan'] == 'admin') {
        $owned = true;
    } elseif ($activity) {
        if (!empty($activity['guru_pelapor_id']) && (
                $activity['guru_pelapor_id'] == $_SESSION['user_id'] ||
                ($current_guru_id && $activity['guru_pelapor_id'] == $current_guru_id)
            )) {
            $owned = true;
        } elseif (empty($activity['guru_pelapor_id']) && !empty($activity['guru_penasihat']) &&
                  strcasecmp(trim($activity['guru_penasihat']), trim($_SESSION['nama_penuh'])) === 0) {
            $owned = true;
        }
    }

    if ($owned) {
        // Padam fail fizikal sebelum memadam rekod pangkalan data
        if (!empty($activity['fail_resit'])) {
            $file_path = "../uploads/resit/" . $activity['fail_resit'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $stmt = $conn->prepare("DELETE FROM aktiviti WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: laporan_list.php?msg=success_delete');
        exit();
    } else {
        header('Location: laporan_list.php');
        exit();
    }
}
?>