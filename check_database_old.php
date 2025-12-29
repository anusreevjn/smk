<?php
// check_database.php - FOR WAMP WITH EXISTING DATABASE
echo "<h1>🔧 WAMP DATABASE CHECK - SISTEM DATABASE</h1>";

// WAMP credentials
$host = "localhost";
$user = "root"; 
$pass = "";
$db   = "sistem";  // Guna database 'sistem'

echo "<h2>Testing Connection...</h2>";

$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("❌ MYSQL CONNECTION FAILED: " . $conn->connect_error);
}

echo "✅ MySQL Connected!<br>";

// Check if database exists
if (!$conn->select_db($db)) {
    die("❌ Database '$db' not found! Please import your SQL file to phpMyAdmin.");
} else {
    echo "✅ Database '$db' exists!<br>";
}

// Check table ahli
$table_check = $conn->query("SHOW TABLES LIKE 'ahli'");
if ($table_check->num_rows == 0) {
    die("❌ Table 'ahli' not found!");
} else {
    echo "✅ Table 'ahli' exists!<br>";
}

// Show current users from ahli table WITH PASSWORD ANALYSIS
echo "<h2>📊 CURRENT USERS FROM AHLI TABLE:</h2>";
$result = $conn->query("SELECT username, password, status, LENGTH(password) as pass_length FROM ahli");
if ($result->num_rows > 0) {
    while($user = $result->fetch_assoc()) {
        echo "👤 <strong>" . $user['username'] . "</strong><br>";
        echo "   Password Hash: " . $user['password'] . "<br>";
        echo "   Password Length: " . $user['pass_length'] . " characters<br>";
        echo "   Status: " . $user['status'] . "<br>";
        
        // Check if password is hashed
        if ($user['pass_length'] == 60) {
            echo "   🔐 Password Type: <strong>HASHED</strong><br>";
        } else {
            echo "   🔐 Password Type: <strong>PLAIN TEXT</strong><br>";
        }
        echo "   ---<br>";
    }
} else {
    echo "❌ No users found!<br>";
}

$conn->close();

echo "<hr><h3>🎯 TEST LOGIN CREDENTIALS:</h3>";
echo "<strong>Pentadbir:</strong><br>Username: Mimie<br>Password: (check password hash)<br><br>";
echo "<strong>Guru:</strong><br>Username: Tinie<br>Password: (check password hash)";

echo "<hr><h3>🚀 NEXT STEP:</h3>";
echo "1. Update auth/login.php to use 'ahli' table<br>";
echo "2. Try login with existing users!";
?>