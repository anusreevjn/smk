<?php
// Start session for login check
session_start();
require_once '../../includes/database.php'; // Standardized path

// Check if user is logged in and is admin using the correct 'peranan' key
if (!isset($_SESSION['user_id']) || $_SESSION['peranan'] != 'admin') {
    header("Location: ../../auth/login.php"); // Path corrected to avoid 404
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Check if action is delete and ID is provided
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $guru_id = $_GET['id'];
    
    try {
        // Start transaction to ensure both deletes succeed or both fail
        $conn->beginTransaction();
        
        // 1. Get the username first to delete the linked account in 'pengguna'
        $stmt_get = $conn->prepare("SELECT username FROM guru WHERE id = ?");
        $stmt_get->execute([$guru_id]);
        $guru = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if ($guru) {
            $username = $guru['username'];
            
            // 2. Delete from 'pengguna' table (login account)
            $stmt_del_user = $conn->prepare("DELETE FROM pengguna WHERE username = ?");
            $stmt_del_user->execute([$username]);
            
            // 3. Delete from 'guru' table
            $stmt_del_guru = $conn->prepare("DELETE FROM guru WHERE id = ?");
            $stmt_del_guru->execute([$guru_id]);
            
            $conn->commit();
            $_SESSION['success'] = "Rekod guru dan akaun pengguna berjaya dipadam.";
        } else {
            $_SESSION['error'] = "Rekod guru tidak ditemui.";
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Gagal memadam rekod: " . $e->getMessage();
    }
}

// Redirect back to list
header("Location: guru_list.php");
exit();
?>