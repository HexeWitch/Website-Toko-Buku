<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$error = '';

// Ambil daftar kategori dari database
$queryKategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
$kategoriList = [];
while ($row = mysqli_fetch_assoc($queryKategori)) {
    $kategoriList[] = $row;
}

if (isset($_POST['simpan'])) {
    $judul   = trim($_POST['judul']);
    $penulis = trim($_POST['penulis']);
    $harga   = (int) $_POST['harga'];
    $stok    = (int) $_POST['stok'];
    $kategori_id = (int) $_POST['kategori_id'];
    
    $gambar = $_FILES['gambar']['name'];
    $tmp    = $_FILES['gambar']['tmp_name'];
    $errorUpload = $_FILES['gambar']['error'];

    // Validasi
    if ($judul === '' || $penulis === '' || $harga <= 0 || $kategori_id <= 0) {
        $error = "Semua field wajib diisi dengan benar";
    } elseif ($errorUpload !== 0) {
        $error = "Gagal upload gambar";
    } else {
        $ext = pathinfo($gambar, PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'jfif'];
        
        if (!in_array($ext, $allowed)) {
            $error = "Format gambar harus JPG, JPEG, atau PNG";
        } else {
            $namaBaru = 'buku_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $folder = __DIR__ . '/../images/';
            
            // Buat folder jika belum ada
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            
            $pathSimpan = $folder . $namaBaru;

            if (move_uploaded_file($tmp, $pathSimpan)) {
                $judul = mysqli_real_escape_string($koneksi, $judul);
                $penulis = mysqli_real_escape_string($koneksi, $penulis);

                $query = mysqli_query(
                    $koneksi,
                    "INSERT INTO buku (judul, penulis, harga, stok, gambar, kategori_id)
                     VALUES ('$judul', '$penulis', $harga, $stok, '$namaBaru', $kategori_id)"
                );

                if ($query) {
                    header("Location: total_buku.php?success=Buku berhasil ditambahkan");
                    exit;
                } else {
                    $error = "Gagal menambahkan buku: " . mysqli_error($koneksi);
                }
            } else {
                $error = "Gagal menyimpan file gambar";
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
    <title>Tambah Buku | LiteraBooks Admin</title>
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

        :root {
            --bg-dark: #0a0e17;
            --bg-card: #ffffff;
            --text-primary: #1a1f2e;
            --text-secondary: #5a6474;
            --text-muted: #8e98a8;
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --danger: #dc2626;
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

        .navbar .container-nav {
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

        .nav-link:hover, .nav-link.active {
            color: var(--accent);
        }

        .admin-badge {
            background: var(--accent-gold);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .form-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            width: 100%;
            max-width: 560px;
            transition: transform 0.2s;
        }

        .form-card:hover {
            transform: translateY(-4px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-header p {
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

        .input-icon input,
        .input-icon select {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8f9fc;
        }

        .input-icon input:focus,
        .input-icon select:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(45,59,94,0.1);
        }

        .form-input-file {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            background: #f8f9fc;
        }

        .form-input-file:focus {
            outline: none;
            border-color: var(--accent);
        }

        .error-message {
            background: #fee2e2;
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .back-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-link a:hover {
            color: var(--accent-light);
        }

        .hint-text {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 0.3rem;
            display: block;
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

        .footer-brand span {
            font-size: 1rem;
            font-weight: 600;
        }

        .footer p {
            color: #5a6474;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .navbar .container-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            .form-card {
                padding: 1.5rem;
            }
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-nav">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">litera<span>admin</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="total_buku.php" class="nav-link active"><i class="fas fa-book"></i> Kelola Buku</a>
            <a href="kelola_kategori.php" class="nav-link"><i class="fas fa-tags"></i> Kategori</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<section class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2><i class="fas fa-plus-circle" style="color: var(--accent-gold);"></i> Tambah Buku Baru</h2>
            <p>Masukkan informasi buku yang akan ditambahkan</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Judul Buku</label>
                <div class="input-icon">
                    <i class="fas fa-book"></i>
                    <input type="text" name="judul" placeholder="Masukkan judul buku" value="<?= isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Penulis</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="penulis" placeholder="Nama penulis" value="<?= isset($_POST['penulis']) ? htmlspecialchars($_POST['penulis']) : ''; ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Kategori</label>
                <div class="input-icon">
                    <i class="fas fa-tag"></i>
                    <select name="kategori_id" class="form-input" required>
                        <option value="">Pilih Kategori</option>
                        <?php foreach ($kategoriList as $kategori): ?>
                            <option value="<?= $kategori['id']; ?>" <?= (isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kategori['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kategori['nama_kategori']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (empty($kategoriList)): ?>
                    <span class="hint-text" style="color: var(--danger);">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Belum ada kategori. <a href="kelola_kategori.php" style="color: var(--accent);">Buat kategori dulu</a>
                    </span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Harga</label>
                <div class="input-icon">
                    <i class="fas fa-rupiah-sign"></i>
                    <input type="number" name="harga" placeholder="Harga buku" value="<?= isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''; ?>" required min="0">
                </div>
            </div>

            <div class="form-group">
                <label>Stok</label>
                <div class="input-icon">
                    <i class="fas fa-boxes"></i>
                    <input type="number" name="stok" placeholder="Jumlah stok buku" value="<?= isset($_POST['stok']) ? htmlspecialchars($_POST['stok']) : '0'; ?>" required min="0">
                </div>
                <span class="hint-text">
                    <i class="fas fa-info-circle"></i> 
                    Masukkan jumlah stok yang tersedia
                </span>
            </div>

            <div class="form-group">
                <label>Gambar Buku</label>
                <div class="input-icon">
                    <i class="fas fa-image"></i>
                    <input type="file" name="gambar" accept="image/*" class="form-input-file" required>
                </div>
                <span class="hint-text">
                    <i class="fas fa-info-circle"></i> 
                    Format: JPG, PNG, JPEG. Maksimal 2MB
                </span>
            </div>

            <button type="submit" name="simpan" class="btn-submit" <?= empty($kategoriList) ? 'disabled' : ''; ?>>
                <i class="fas fa-save"></i> Simpan Buku
            </button>
        </form>

        <div class="back-link">
            <a href="total_buku.php">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar Buku
            </a>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-brand">
            <div class="footer-brand-icon"><i class="fas fa-book-open"></i></div>
            <span>LiteraBooks Admin</span>
        </div>
        <p>&copy; <?= date('Y'); ?> LiteraBooks. All rights reserved.</p>
    </div>
</footer>

</body>
</html>