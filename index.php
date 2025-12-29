<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengurusan Kokurikulum SMK King Edward VII</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php 
        session_start();

// Check for login errors
if (isset($_SESSION['login_error'])) {
    echo '<script>alert("' . $_SESSION['login_error'] . '");</script>';
    unset($_SESSION['login_error']); 
}

// FIX: Change 'user_role' to 'peranan'
if (isset($_SESSION['peranan'])) {
    if ($_SESSION['peranan'] == 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($_SESSION['peranan'] == 'guru') {
        header('Location: guru/dashboard.php');
    }
    exit();
}
    ?>

    <header class="main-navbar">
    <div class="nav-container-full">
        <div class="nav-logo-centered">
            <img src="smkevii_logo.png" alt="SMK KE VII Logo" class="school-logo">
            <div class="nav-text-centered">
                <span class="nav-title">SMK KING EDWARD VII</span>
                <span class="nav-subtitle">SISTEM PENGURUSAN MAKLUMAT KOKURIKULUM</span>
            </div>
        </div>
    </div>
</header>


<script src="js/script.js"></script>
    <div class="login-container">
        

        <div class="login-options">
    <div class="login-card">
        <div class="login-icon">👨‍🏫</div>
        <h2>Guru Penasihat</h2>
        <p>Akses sistem sebagai Guru Penasihat untuk mengurus aktiviti kokurikulum</p>
        <button class="login-btn guru-btn" onclick="showLoginForm('guru')">Akses sebagai Guru</button>
    </div>
           <div class="login-card">
        <div class="login-icon">👨‍💼</div>
        <h2>Pentadbir Sistem</h2>
        <p>Akses sebagai admin sistem sedia ada <a> FDFDDFDFDFDFDFDFFFFFFFFFFFFFFFFFF.</a></p> 
        <button class="login-btn admin-btn" onclick="showLoginForm('admin')">Akses sebagai Pentadbir</button>
    </div>
</div>

        <div id="loginForm" class="login-form-container">
            <form id="actualLoginForm" method="POST" action="auth/login.php">
                <div class="form-header">
                    <h3 id="formTitle">Login</h3>
                    <button type="button" class="close-btn" onclick="hideLoginForm()">×</button>
                </div>
                
                <input type="hidden" id="user_role" name="user_role" value="">
                
                <div class="form-group">
                    <label for="username">ID Pengguna:</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan ID pengguna">
                </div>
                
                <div class="form-group">
                    <label for="password">Kata Laluan:</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan kata laluan">
                </div>
                
                <button type="submit" class="submit-btn">Log Masuk</button>
                
                <div class="form-footer">
                    <p>Hubungi pentadbir jika terlupa kata laluan</p>
                </div>
            </form>
        </div>

        <div class="footer-info">
            <p><strong>SMK KING EDWARD VII</strong></p>
            <p id="liveClock"></p>
        </div>
    </div>

    <script src="js/script.js"></script>
<footer class="site-footer">
    <div class="footer-container">
        <p>© <span id="year"></span> Nur Syamimi Aida Binti Suhairi · FSKTM · BIT FYP 2025/2026</p>
        <p style="margin-top: 10px; opacity: 0.8;">
        </p>
    </div>
</footer>

</body>
</html>