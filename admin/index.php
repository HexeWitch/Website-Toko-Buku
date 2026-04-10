<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

/* ================= STATISTIK DASHBOARD ================= */

// Total Buku
$totalBuku = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku"))['total'];

// Total Stok
$totalStok = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(stok) as total FROM buku"))['total'];
$totalStok = $totalStok ? $totalStok : 0;

// Total User
$totalUser = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users"))['total'];

// Total Transaksi
$totalTransaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi"))['total'];

// Total Pendapatan
$totalPendapatan = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT SUM(subtotal) as total FROM transaksi_detail
"))['total'];
$totalPendapatan = $totalPendapatan ? $totalPendapatan : 0;

// Buku dengan Stok Habis
$stokHabis = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku WHERE stok <= 0");
$stokHabisCount = mysqli_fetch_assoc($stokHabis)['total'];

// Buku dengan Stok Menipis (< 5)
$stokMenipis = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM buku WHERE stok > 0 AND stok < 5");
$stokMenipisCount = mysqli_fetch_assoc($stokMenipis)['total'];

// Produk Terlaris (Top 5)
$produkTerlaris = mysqli_query($koneksi, "
    SELECT b.id, b.judul, b.gambar, SUM(td.qty) as terjual
    FROM transaksi_detail td
    JOIN buku b ON td.buku_id = b.id
    GROUP BY b.id
    ORDER BY terjual DESC
    LIMIT 5
");

// User Baru (7 hari terakhir)
$userBaru = mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$userBaruCount = mysqli_fetch_assoc($userBaru)['total'];

// Transaksi Terbaru (5 terakhir)
$transaksiTerbaru = mysqli_query($koneksi, "
    SELECT t.id, u.nama, t.total, t.status, t.created_at
    FROM transaksi t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LiteraBooks</title>
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
            --danger: #dc2626;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
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

        .dashboard-header {
            padding: 2rem 0 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
            border: 1px solid var(--border);
            text-decoration: none;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #f0f2f6 0%, #e8ecf2 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
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

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .info-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-gold);
            display: inline-block;
        }

        .warning-list {
            list-style: none;
        }

        .warning-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border);
        }

        .warning-list li:last-child {
            border-bottom: none;
        }

        .warning-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .badge-habis {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-menipis {
            background: #fed7aa;
            color: #92400e;
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border);
        }

        .product-item:last-child {
            border-bottom: none;
        }

        .product-thumb {
            width: 40px;
            height: 55px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f2f6;
        }

        .product-info {
            flex: 1;
        }

        .product-title {
            font-weight: 600;
            font-size: 0.85rem;
        }

        .product-sold {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .product-price {
            font-weight: 600;
            color: var(--accent);
            font-size: 0.8rem;
        }

        .transaction-item {
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-info .id {
            font-weight: 600;
            font-size: 0.85rem;
        }

        .transaction-info .user {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .transaction-status {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        .status-selesai {
            background: #d1fae5;
            color: #065f46;
        }

        .status-diproses {
            background: #fed7aa;
            color: #92400e;
        }

        .status-menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-link {
            display: inline-block;
            margin-top: 1rem;
            color: var(--accent);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .btn-link:hover {
            text-decoration: underline;
        }

        .welcome-section {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .welcome-text p {
            opacity: 0.8;
            font-size: 0.85rem;
        }

        .welcome-date {
            text-align: right;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
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

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
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
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .welcome-date {
                text-align: center;
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
            <a href="index.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="total_buku.php" class="nav-link"><i class="fas fa-book"></i> Kelola Buku</a>
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> Kelola User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <div class="welcome-text">
            <h2>Selamat datang, <?= htmlspecialchars($_SESSION['admin']['nama']); ?>!</h2>
            <p>Berikut adalah ringkasan aktivitas toko buku Anda</p>
        </div>
        <div class="welcome-date">
            <i class="fas fa-calendar-alt"></i> <?= date('d F Y'); ?>
        </div>
    </div>

    <!-- STATISTIK GRID -->
    <div class="stats-grid">
        <a href="total_buku.php" class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-info">
                <h3><?= number_format($totalBuku); ?></h3>
                <p>Total Buku</p>
            </div>
        </a>
        <a href="total_buku.php" class="stat-card">
            <div class="stat-icon">📦</div>
            <div class="stat-info">
                <h3><?= number_format($totalStok); ?></h3>
                <p>Total Stok</p>
            </div>
        </a>
        <a href="user.php" class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <h3><?= number_format($totalUser); ?></h3>
                <p>Total User</p>
            </div>
        </a>
        <a href="laporan.php" class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-info">
                <h3>Rp <?= number_format($totalPendapatan,0,',','.'); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </a>
    </div>

    <!-- INFO GRID -->
    <div class="info-grid">
        <!-- Peringatan Stok -->
        <div class="info-card">
            <h3><i class="fas fa-exclamation-triangle"></i> Peringatan Stok</h3>
            <ul class="warning-list">
                <li>
                    <span>📚 Buku Stok Habis</span>
                    <span class="warning-badge badge-habis"><?= $stokHabisCount ?> buku</span>
                </li>
                <li>
                    <span>⚠️ Buku Stok Menipis (&lt;5)</span>
                    <span class="warning-badge badge-menipis"><?= $stokMenipisCount ?> buku</span>
                </li>
            </ul>
            <?php if($stokHabisCount > 0 || $stokMenipisCount > 0): ?>
                <a href="total_buku.php" class="btn-link">Kelola Stok →</a>
            <?php endif; ?>
        </div>

        <!-- Produk Terlaris -->
        <div class="info-card">
            <h3><i class="fas fa-fire"></i> Produk Terlaris</h3>
            <?php if(mysqli_num_rows($produkTerlaris) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($produkTerlaris)): ?>
                <div class="product-item">
                    <img src="../images/<?= $row['gambar']; ?>" class="product-thumb" onerror="this.src='https://placehold.co/40x55?text=No+Image'">
                    <div class="product-info">
                        <div class="product-title"><?= htmlspecialchars($row['judul']); ?></div>
                        <div class="product-sold">Terjual: <?= $row['terjual']; ?> eksemplar</div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--text-muted); padding: 1rem 0; text-align: center;">Belum ada data penjualan</p>
            <?php endif; ?>
            <a href="transaksi.php" class="btn-link">Lihat Semua Transaksi →</a>
        </div>

        <!-- User Baru -->
        <div class="info-card">
            <h3><i class="fas fa-user-plus"></i> User Baru</h3>
            <div style="padding: 1rem 0; text-align: center;">
                <div style="font-size: 2.5rem; font-weight: 700; color: var(--accent);"><?= $userBaruCount; ?></div>
                <p style="color: var(--text-muted);">User baru dalam 7 hari terakhir</p>
            </div>
            <a href="user.php" class="btn-link">Kelola User →</a>
        </div>

        <!-- Transaksi Terbaru -->
        <div class="info-card">
            <h3><i class="fas fa-clock"></i> Transaksi Terbaru</h3>
            <?php if(mysqli_num_rows($transaksiTerbaru) > 0): ?>
                <?php while($trx = mysqli_fetch_assoc($transaksiTerbaru)): 
                    $statusClass = '';
                    if($trx['status'] == 'Selesai') $statusClass = 'status-selesai';
                    elseif($trx['status'] == 'Diproses') $statusClass = 'status-diproses';
                    else $statusClass = 'status-menunggu';
                ?>
                <div class="transaction-item">
                    <div class="transaction-info">
                        <div class="id">#<?= $trx['id']; ?> - <?= htmlspecialchars($trx['nama']); ?></div>
                        <div class="user">Rp <?= number_format($trx['total'],0,',','.'); ?></div>
                    </div>
                    <span class="transaction-status <?= $statusClass; ?>"><?= $trx['status']; ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: var(--text-muted); padding: 1rem 0; text-align: center;">Belum ada transaksi</p>
            <?php endif; ?>
            <a href="transaksi.php" class="btn-link">Lihat Semua Transaksi →</a>
        </div>
    </div>
</div>

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