<?php
// test_connection.php
echo "<h2>Testing Database Connection</h2>";

try {
    $host = "localhost";
    $dbname = "smk_kokurikulum";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database Connected Successfully!<br><br>";
    
    // Check if users table exists and has data
    $stmt = $conn->query("SELECT username, password, peranan FROM pengguna");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "✅ Users found in database:<br>";
        foreach ($users as $user) {
            echo "Username: <strong>{$user['username']}</strong> | Role: {$user['peranan']} | Password Hash: {$user['password']}<br>";
        }
    } else {
        echo "❌ No users found in database!";
    }
    
} catch(PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
}
?>