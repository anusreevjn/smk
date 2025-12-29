<?php
$host = "localhost";
$username = "root";
$password = "admin1234";
$database = "smk_kokurikulum";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");
?>