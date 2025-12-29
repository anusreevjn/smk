<?php
// password_recovery.php - AUTO PASSWORD DETECTION
echo "<h1>🔓 PASSWORD RECOVERY SYSTEM</h1>";

$host = "localhost";
$user = "root"; 
$pass = "";
$db   = "sistem";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "✅ Connected to database!<br>";

// Common passwords to try
$common_passwords = [
    'mimie', 'Mimie', 'mimie123', 'Mimie123', '123456', 'password', 
    'admin', 'admin123', 'guru', 'guru123', 'pentadbir', 'sistem',
    '12345', '12345678', '123456789', 'abc123', 'password1',
    'test', 'test123', 'hello', 'welcome', 'letmein'
];

echo "<h2>🔍 TRYING TO RECOVER PASSWORDS...</h2>";

$users = $conn->query("SELECT username, password FROM ahli");

while($user = $users->fetch_assoc()) {
    echo "<h3>👤 User: " . $user['username'] . "</h3>";
    echo "Password Hash: " . $user['password'] . "<br>";
    
    $found = false;
    
    // Try each common password
    foreach($common_passwords as $common) {
        if (password_verify($common, $user['password'])) {
            echo "🎉 <strong style='color: green;'>PASSWORD FOUND: '$common'</strong><br>";
            $found = true;
            
            // Update to known password
            $new_pass = "mimie123";
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE ahli SET password = '$new_hash' WHERE username = '" . $user['username'] . "'");
            echo "✅ Password reset to: <strong>$new_pass</strong><br>";
            break;
        }
    }
    
    if (!$found) {
        echo "❌ Password not found in common list<br>";
        
        // Force reset to known password
        $new_pass = "mimie123";
        $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        $conn->query("UPDATE ahli SET password = '$new_hash' WHERE username = '" . $user['username'] . "'");
        echo "🔄 Password forced reset to: <strong>$new_pass</strong><br>";
    }
    echo "<hr>";
}

echo "<h2>🎯 NEW LOGIN CREDENTIALS:</h2>";
echo "<strong>All users now have password:</strong> <span style='color: red; font-size: 20px;'>mimie123</span><br><br>";

$result = $conn->query("SELECT username, status FROM ahli");
while($row = $result->fetch_assoc()) {
    echo "👤 <strong>" . $row['username'] . "</strong> (" . $row['status'] . ")<br>";
}

$conn->close();

echo "<hr><h3>🚀 NEXT STEP:</h3>";
echo "1. Login dengan username dan password: <strong>mimie123</strong><br>";
echo "2. Buka: <a href='http://localhost/TESTKOKOBARU/'>http://localhost/TESTKOKOBARU/</a>";
?>