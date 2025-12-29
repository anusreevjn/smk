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

// Get activity ID from URL
$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        // Delete activity
        $sql = "DELETE FROM aktiviti WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Aktiviti berjaya dipadam!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

// Redirect back to activities list
header('Location: aktiviti_list.php');
exit();
?>