<?php
// debug_login.php
session_start();
echo "<h2>🔧 Debug Login System</h2>";

// Test database connection
try {
    require_once 'includes/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "✅ Database Connected<br>";
    
    // Test specific user
    $username = 'admin';
    $query = "SELECT * FROM pengguna WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found: " . $user['username'] . "<br>";
        echo "📝 Stored password: '" . $user['password'] . "'<br>";
        echo "👤 Role: " . $user['peranan'] . "<br>";
        echo "🔍 Password length: " . strlen($user['password']) . " characters<br>";
        
        // Test password match
        $input_password = 'password';
        echo "🔑 Input password: '$input_password'<br>";
        
        if ($input_password === $user['password']) {
            echo "🎉 PASSWORD MATCHES!<br>";
        } else {
            echo "❌ PASSWORD DOES NOT MATCH!<br>";
            echo "🔍 Comparing: '$input_password' vs '" . $user['password'] . "'<br>";
        }
    } else {
        echo "❌ User not found!<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test session
echo "<br><h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
?>