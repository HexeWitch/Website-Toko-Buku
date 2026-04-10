<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/koneksi.php';

$error = '';
$success = false;

if (isset($_POST['register'])) {
    $nama     = trim($_POST['nama']);
    $email    = trim($_POST['email']);
    $pass     = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($nama === '' || $email === '' || $pass === '' || $confirm === '') {
        $error = "Semua field wajib diisi";
    } elseif ($pass !== $confirm) {
        $error = "Password dan konfirmasi password tidak sama";
    } elseif (strlen($pass) < 6) {
        $error = "Password minimal 6 karakter";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid";
    } else {
        $email = mysqli_real_escape_string($koneksi, $email);
        $nama = mysqli_real_escape_string($koneksi, $nama);

        $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $query = mysqli_query(
                $koneksi,
                "INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$hash')"
            );

            if ($query) {
                // Redirect ke halaman sukses
                header("Location: register_success.php");
                exit;
            } else {
                $error = "Pendaftaran gagal, silakan coba lagi";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar | LiteraBooks</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fc 0%, #f0f2f6 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Color Palette - SAMA DENGAN INDEX.PHP */
        :root {
            --bg-dark: #0a0e17;
            --bg-card: #ffffff;
            --text-primary: #1a1f2e;
            --text-secondary: #5a6474;
            --text-muted: #8e98a8;
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-icon i {
            color: white;
            font-size: 1rem;
        }

        .logo-text {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.02em;
        }

        .logo-text span {
            font-weight: 400;
            color: var(--text-muted);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-link:hover {
            color: var(--accent);
        }

        .auth-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1.5rem;
        }

        .auth-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            width: 100%;
            max-width: 480px;
            transition: transform 0.2s;
        }

        .auth-card:hover {
            transform: translateY(-4px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .input-icon input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8f9fc;
        }

        .input-icon input:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(45,59,94,0.1);
        }

         .password-hint {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
            display: block;
        }

        .auth-error {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auth-error i {
            font-size: 1rem;
        }

         .btn-register {
            width: 100%;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-register:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

         .auth-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .auth-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        .auth-link a:hover {
            color: var(--accent-light);
        }

         .terms {
            margin-top: 1rem;
            text-align: center;
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .terms a {
            color: var(--accent);
            text-decoration: none;
        }

         .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            text-align: center;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-brand-icon {
            width: 30px;
            height: 30px;
            background: var(--accent-gold);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-brand-icon i {
            color: white;
            font-size: 0.8rem;
        }

        .footer-brand span {
            font-size: 1rem;
            font-weight: 600;
        }

        .footer p {
            color: #5a6474;
            font-size: 0.8rem;
        }

         @media (max-width: 768px) {
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                gap: 1rem;
            }
            
            .auth-card {
                padding: 1.5rem;
            }
            
            .auth-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

 <nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">
            <div class="logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <span class="logo-text">litera<span>books</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="buku.php" class="nav-link">
                <i class="fas fa-book"></i> Buku
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Keranjang
            </a>
            <a href="login.php" class="nav-link">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>
    </div>
</nav>

 <section class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Daftar Akun Baru</h2>
            <p>Bergabunglah dengan ribuan pembaca lainnya</p>
        </div>

        <?php if ($error): ?>
            <div class="auth-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="contoh@email.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                </div>
                <span class="password-hint">
                    <i class="fas fa-info-circle"></i> Password minimal 6 karakter
                </span>
            </div>

            <div class="form-group">
                <label>Konfirmasi Password</label>
                <div class="input-icon">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" placeholder="Masukkan password lagi" required>
                </div>
            </div>

            <button type="submit" name="register" class="btn-register">
                <i class="fas fa-user-plus"></i>
                Daftar Sekarang
            </button>
        </form>

        <div class="auth-link">
            Sudah punya akun?
            <a href="login.php">Login di sini</a>
        </div>

        <div class="terms">
            Dengan mendaftar, Anda menyetujui 
            <a href="#">Syarat & Ketentuan</a> dan 
            <a href="#">Kebijakan Privasi</a> kami
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-brand">
            <div class="footer-brand-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <span>LiteraBooks</span>
        </div>
        <p>&copy; <?= date('Y'); ?> LiteraBooks. Membaca adalah jendela dunia.</p>
    </div>
</footer>

</body>
</html>