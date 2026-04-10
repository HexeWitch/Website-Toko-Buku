<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

$error = '';
$success = '';
$kategori_data = null;

/* =========================
   CEK ID KATEGORI
========================= */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: kelola_kategori.php");
    exit;
}

/* =========================
   AMBIL DATA KATEGORI
========================= */
$query_kategori = mysqli_query($koneksi, "SELECT * FROM kategori WHERE id = $id");
$kategori_data = mysqli_fetch_assoc($query_kategori);

if (!$kategori_data) {
    header("Location: kelola_kategori.php?error=Kategori tidak ditemukan");
    exit;
}

/* =========================
   CEK BUKU DALAM KATEGORI
========================= */
$query_buku = mysqli_query($koneksi, "
    SELECT id, judul, stok 
    FROM buku 
    WHERE kategori_id = $id
");

$total_buku = mysqli_num_rows($query_buku);
$buku_masih_stok = [];
$buku_stok_habis = [];
$bisa_dihapus = true;

while ($buku = mysqli_fetch_assoc($query_buku)) {
    if ($buku['stok'] > 0) {
        $buku_masih_stok[] = $buku;
        $bisa_dihapus = false;
    } else {
        $buku_stok_habis[] = $buku;
    }
}

/* =========================
   PROSES HAPUS KATEGORI
========================= */
if (isset($_POST['confirm_delete']) && $bisa_dihapus) {
    // Hapus kategori
    $query_hapus = mysqli_query($koneksi, "DELETE FROM kategori WHERE id = $id");
    
    if ($query_hapus) {
        header("Location: kelola_kategori.php?success=Kategori berhasil dihapus");
        exit;
    } else {
        $error = "Gagal menghapus kategori: " . mysqli_error($koneksi);
    }
}

// Hitung total kategori untuk sidebar
$total_kategori = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM kategori"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Kategori | LiteraBooks Admin</title>
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
            background: #f8f9fc;
            color: #1a1f2e;
        }

        :root {
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --success: #10b981;
            --danger: #dc2626;
            --warning: #f59e0b;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* NAVBAR */
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
            color: #1a1f2e;
        }

        .logo-text span {
            font-weight: 400;
            color: #8e98a8;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: #5a6474;
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

        .admin-badge {
            background: var(--accent-gold);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        /* MAIN CONTENT */
        .page-header {
            margin: 2rem 0 1rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 i {
            color: var(--danger);
        }

        .page-header p {
            color: #5a6474;
            margin-top: 0.5rem;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            margin: 1.5rem 0;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .warning-box {
            background: #fef3c7;
            border-left: 4px solid var(--warning);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .warning-box i {
            font-size: 1.5rem;
            color: var(--warning);
        }

        .warning-box p {
            color: #92400e;
            font-size: 0.9rem;
        }

        .info-kategori {
            background: #f0f2f6;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #5a6474;
        }

        .info-value {
            font-weight: 600;
            color: var(--accent);
        }

        /* Buku List */
        .buku-list {
            margin: 1rem 0;
        }

        .buku-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem;
            border-bottom: 1px solid var(--border);
        }

        .buku-item:last-child {
            border-bottom: none;
        }

        .buku-info h4 {
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .buku-info p {
            font-size: 0.7rem;
            color: #8e98a8;
        }

        .stok-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .stok-habis {
            background: #d1fae5;
            color: #065f46;
        }

        .stok-ada {
            background: #fef3c7;
            color: #92400e;
        }

        /* Buttons */
        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-delete:hover:not(:disabled) {
            background: #b91c1c;
            transform: translateY(-2px);
        }

        .btn-delete:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        .alert-success {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        /* Footer */
        .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }

        .footer .container {
            text-align: center;
        }

        .footer p {
            color: #8e98a8;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            .navbar .container-nav {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .btn-group {
                flex-direction: column;
            }
            .btn-delete, .btn-cancel {
                text-align: center;
                justify-content: center;
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
            <a href="total_buku.php" class="nav-link"><i class="fas fa-book"></i> Buku</a>
            <a href="kelola_kategori.php" class="nav-link active"><i class="fas fa-tags"></i> Kategori</a>
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-trash-alt"></i>
            Hapus Kategori
        </h1>
        <p>Hapus kategori dari sistem</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <!-- Warning Box -->
        <div class="warning-box">
            <i class="fas fa-exclamation-triangle"></i>
            <p><strong>Perhatian!</strong> Kategori hanya bisa dihapus jika TIDAK ADA buku dengan stok > 0 dalam kategori ini.</p>
        </div>

        <!-- Info Kategori -->
        <div class="info-kategori">
            <div class="info-row">
                <span class="info-label"><i class="fas fa-tag"></i> Nama Kategori</span>
                <span class="info-value"><?= htmlspecialchars($kategori_data['nama_kategori']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-book"></i> Total Buku</span>
                <span class="info-value"><?= $total_buku ?> buku</span>
            </div>
            <div class="info-row">
                <span class="info-label"><i class="fas fa-box"></i> Buku dengan Stok > 0</span>
                <span class="info-value <?= !$bisa_dihapus ? 'text-danger' : '' ?>">
                    <?= count($buku_masih_stok) ?> buku
                </span>
            </div>
        </div>

        <!-- Daftar Buku -->
        <?php if ($total_buku > 0): ?>
        <div class="buku-list">
            <h4 style="margin-bottom: 1rem; font-size: 0.9rem;">
                <i class="fas fa-list"></i> Daftar Buku dalam Kategori Ini
            </h4>
            
            <?php foreach ($buku_masih_stok as $buku): ?>
            <div class="buku-item">
                <div class="buku-info">
                    <h4><?= htmlspecialchars($buku['judul']) ?></h4>
                    <p>ID Buku: #<?= $buku['id'] ?></p>
                </div>
                <div>
                    <span class="stok-badge stok-ada">
                        <i class="fas fa-box"></i> Stok: <?= $buku['stok'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php foreach ($buku_stok_habis as $buku): ?>
            <div class="buku-item">
                <div class="buku-info">
                    <h4><?= htmlspecialchars($buku['judul']) ?></h4>
                    <p>ID Buku: #<?= $buku['id'] ?></p>
                </div>
                <div>
                    <span class="stok-badge stok-habis">
                        <i class="fas fa-check-circle"></i> Stok: 0 (Sudah Habis)
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="btn-group">
            <?php if ($bisa_dihapus): ?>
                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori <?= htmlspecialchars($kategori_data['nama_kategori']) ?>?')">
                    <button type="submit" name="confirm_delete" class="btn-delete">
                        <i class="fas fa-trash-alt"></i> Hapus Kategori
                    </button>
                </form>
            <?php else: ?>
                <button class="btn-delete" disabled>
                    <i class="fas fa-ban"></i> Tidak Bisa Dihapus (Masih Ada Stok)
                </button>
            <?php endif; ?>
            
            <a href="kelola_kategori.php" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!$bisa_dihapus): ?>
        <div class="warning-box" style="margin-top: 1.5rem; background: #fef2f2; border-left-color: var(--danger);">
            <i class="fas fa-info-circle" style="color: var(--danger);"></i>
            <p style="color: var(--danger);">
                <strong>Kategori tidak dapat dihapus!</strong> Masih terdapat <?= count($buku_masih_stok) ?> buku dengan stok > 0. 
                Silakan kosongkan stok buku terlebih dahulu sebelum menghapus kategori ini.
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel. All rights reserved.</p>
    </div>
</footer>

</body>
</html>