<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

/* ======================
   UPDATE STOK
====================== */
if (isset($_POST['update_stok'])) {
    $id = (int)$_POST['id'];
    $stok = (int)$_POST['stok'];
    mysqli_query($koneksi, "UPDATE buku SET stok = $stok WHERE id = $id");
    header("Location: total_buku.php");
    exit;
}

/* ======================
   AMBIL SEMUA BUKU
====================== */
$keyword = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';
$kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($koneksi, $_GET['kategori']) : '';

$sql = "SELECT * FROM buku WHERE 1";

if ($keyword != '') {
    $sql .= " AND (judul LIKE '%$keyword%' OR penulis LIKE '%$keyword%')";
}

if ($kategori != '') {
    $sql .= " AND kategori = '$kategori'";
}

$sql .= " ORDER BY id DESC";
$query = mysqli_query($koneksi, $sql);

// Hitung total buku
$totalBuku = mysqli_num_rows($query);

// Ambil daftar kategori untuk filter
$kategoriList = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM buku ORDER BY kategori ASC");

// Hitung total harga semua buku
$totalHarga = 0;
$totalStok = 0;
mysqli_data_seek($query, 0);
while ($buku = mysqli_fetch_assoc($query)) {
    $totalHarga += $buku['harga'];
    $totalStok += $buku['stok'] ?? 0;
}
mysqli_data_seek($query, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Buku | Admin LiteraBooks</title>
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
            --bg-dark: #0a0e17;
            --bg-card: #ffffff;
            --text-primary: #1a1f2e;
            --text-secondary: #5a6474;
            --text-muted: #8e98a8;
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --success: #10b981;
            --danger: #dc2626;
            --warning: #f59e0b;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* ========== NAVBAR ========== */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar .container-nav {
            max-width: 1400px;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 55px;
            height: 55px;
            background: #f0f2f6;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }

        .filter-group input, .filter-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.85rem;
            background: #f8f9fc;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
        }

        .btn-reset {
            background: #f0f2f6;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-reset:hover {
            background: var(--border);
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .btn-add:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            transition: 0.2s;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .data-table th {
            background: #f8f9fc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:hover {
            background: #fafbfc;
        }

        .book-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f2f6;
        }

        .category-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: #f0f2f6;
            border-radius: 20px;
            font-size: 0.7rem;
            color: var(--accent-gold);
        }

        /* Stok Badge */
        .stok-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .stok-banyak {
            background: #d1fae5;
            color: #065f46;
        }

        .stok-sedikit {
            background: #fed7aa;
            color: #92400e;
        }

        .stok-habis {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Stok Form */
        .stok-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stok-input {
            width: 70px;
            padding: 0.3rem 0.5rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-align: center;
            font-size: 0.8rem;
        }

        .stok-btn {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.7rem;
        }

        .stok-btn:hover {
            background: var(--accent-light);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--accent);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--danger);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        /* Footer */
        .footer {
            background: #0a0e17;
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1.5fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .footer-logo-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-gold);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer-logo-text {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .footer-about p {
            color: #8e98a8;
            font-size: 0.85rem;
            line-height: 1.6;
        }

        .footer h4 {
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: #8e98a8;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .footer-links a:hover {
            color: var(--accent-gold);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #5a6474;
        }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .container { padding: 0 1.5rem; }
            .navbar .container-nav { flex-direction: column; gap: 1rem; }
            .nav-links { flex-wrap: wrap; justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
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
            <a href="total_buku.php" class="nav-link"><i class="fas fa-book"></i> Kelola Buku</a>
          <a href="user.php" class="nav-link"><i class="fas fa-users"></i> Kelola User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-database"></i> Semua Buku</h1>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3><?= $totalBuku; ?></h3>
                <p>Total Buku</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-tag"></i></div>
            <div class="stat-info">
                <h3><?= mysqli_num_rows($kategoriList); ?></h3>
                <p>Kategori</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
            <div class="stat-info">
                <h3><?= number_format($totalStok); ?></h3>
                <p>Total Stok</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info">
                <h3>Rp <?= number_format($totalHarga, 0, ',', '.'); ?></h3>
                <p>Total Nilai Buku</p>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Cari Buku</label>
            <form method="GET" action="" style="width: 100%;">
                <input type="text" name="q" placeholder="Judul atau penulis..." value="<?= htmlspecialchars($keyword); ?>">
            </form>
        </div>
        <div class="filter-group">
            <label><i class="fas fa-filter"></i> Filter Kategori</label>
            <form method="GET" action="" style="width: 100%;">
                <?php if($keyword): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($keyword); ?>">
                <?php endif; ?>
                <select name="kategori" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php 
                    mysqli_data_seek($kategoriList, 0);
                    while($k = mysqli_fetch_assoc($kategoriList)): ?>
                        <option value="<?= $k['kategori']; ?>" <?= ($kategori == $k['kategori']) ? 'selected' : ''; ?>>
                            <?= $k['kategori']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </form>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <a href="total_buku.php" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset Filter</a>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <a href="buku_tambah.php" class="btn-add"><i class="fas fa-plus"></i> Tambah Buku</a>
        </div>
    </div>

    <!-- TABLE -->
    <?php if ($totalBuku > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($query, 0);
                    while ($buku = mysqli_fetch_assoc($query)): 
                        $stok = $buku['stok'] ?? 0;
                        if($stok >= 20) $stokClass = 'stok-banyak';
                        elseif($stok > 0) $stokClass = 'stok-sedikit';
                        else $stokClass = 'stok-habis';
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><img src="../images/<?= $buku['gambar']; ?>" class="book-thumb" onerror="this.src='https://placehold.co/50x70?text=No+Image'"></td>
                        <td><strong><?= htmlspecialchars($buku['judul']); ?></strong></td>
                        <td><?= htmlspecialchars($buku['penulis']); ?></td>
                        <td><span class="category-badge"><?= htmlspecialchars($buku['kategori']); ?></span></td>
                        <td><strong style="color: var(--accent);">Rp <?= number_format($buku['harga'], 0, ',', '.'); ?></strong></td>
                        <td>
                            <form method="post" class="stok-form">
                                <input type="hidden" name="id" value="<?= $buku['id']; ?>">
                                <input type="number" name="stok" value="<?= $stok; ?>" class="stok-input" min="0">
                                <button type="submit" name="update_stok" class="stok-btn">
                                    <i class="fas fa-save"></i>
                                </button>
                            </form>
                            <span class="stok-badge <?= $stokClass; ?>" style="margin-top: 0.3rem; display: inline-block;">
                                <?php if($stok == 0): ?>
                                    <i class="fas fa-times-circle"></i> Habis
                                <?php elseif($stok < 10): ?>
                                    <i class="fas fa-exclamation-triangle"></i> Sisa <?= $stok; ?>
                                <?php else: ?>
                                    <i class="fas fa-check-circle"></i> <?= $stok; ?> tersedia
                                <?php endif; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="buku_edit.php?id=<?= $buku['id']; ?>" class="btn-edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="buku_hapus.php?id=<?= $buku['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus buku ini?')">
                                    <i class="fas fa-trash"></i> Hapus
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <p>Belum ada buku dalam database</p>
            <a href="buku_tambah.php" class="btn-add" style="margin-top: 1rem;"><i class="fas fa-plus"></i> Tambah Buku Pertama</a>
        </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon"><i class="fas fa-book-open"></i></div>
                    <span class="footer-logo-text">litera<span style="font-weight:400;">admin</span></span>
                </div>
                <p>Sistem manajemen toko buku LiteraBooks. Kelola buku, transaksi, dan pantau penjualan dengan mudah.</p>
            </div>
            <div>
                <h4>Menu</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="total_buku.php">Kelola Buku</a></li>
                    <li><a href="total_buku.php">Semua Buku</a></li>
                    <li><a href="transaksi.php">Transaksi</a></li>
                </ul>
            </div>
            <div>
                <h4>Bantuan</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ Admin</a></li>
                    <li><a href="#">Panduan</a></li>
                    <li><a href="#">Kontak Support</a></li>
                </ul>
            </div>
            <div>
                <h4>Kontak</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope"></i> admin@literabooks.com</li>
                    <li><i class="fas fa-phone"></i> +62 812 3456 7890</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel.</p>
        </div>
    </div>
</footer>

</body>
</html>