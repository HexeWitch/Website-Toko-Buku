<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}


$filter = isset($_GET['filter']) ? $_GET['filter'] : 'bulan_ini';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$where = "WHERE 1";
$title = "Semua Waktu";

switch($filter) {
    case 'hari_ini':
        $where = "WHERE DATE(t.created_at) = CURDATE()";
        $title = "Hari Ini";
        break;
    case 'kemarin':
        $where = "WHERE DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        $title = "Kemarin";
        break;
    case 'minggu_ini':
        $where = "WHERE YEARWEEK(t.created_at) = YEARWEEK(CURDATE())";
        $title = "Minggu Ini";
        break;
    case 'minggu_lalu':
        $where = "WHERE YEARWEEK(t.created_at) = YEARWEEK(DATE_SUB(CURDATE(), INTERVAL 1 WEEK))";
        $title = "Minggu Lalu";
        break;
    case 'bulan_ini':
        $where = "WHERE MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())";
        $title = "Bulan Ini";
        break;
    case 'bulan_lalu':
        $where = "WHERE MONTH(t.created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(t.created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
        $title = "Bulan Lalu";
        break;
    case 'tahun_ini':
        $where = "WHERE YEAR(t.created_at) = YEAR(CURDATE())";
        $title = "Tahun Ini";
        break;
    case 'custom':
        if ($start_date && $end_date) {
            $where = "WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'";
            $title = date('d M Y', strtotime($start_date)) . " - " . date('d M Y', strtotime($end_date));
        }
        break;
}

/* ======================
   AMBIL DATA PENDAPATAN
====================== */

// Total Pendapatan
$qTotal = mysqli_query($koneksi, "
    SELECT SUM(td.subtotal) as total 
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id
    $where
");
$totalPendapatan = mysqli_fetch_assoc($qTotal)['total'] ?? 0;

// Total Transaksi
$qTransaksi = mysqli_query($koneksi, "
    SELECT COUNT(*) as total 
    FROM transaksi t 
    $where
");
$totalTransaksi = mysqli_fetch_assoc($qTransaksi)['total'] ?? 0;

// Total Buku Terjual
$qBukuTerjual = mysqli_query($koneksi, "
    SELECT SUM(td.qty) as total 
    FROM transaksi_detail td
    JOIN transaksi t ON td.transaksi_id = t.id
    $where
");
$totalBukuTerjual = mysqli_fetch_assoc($qBukuTerjual)['total'] ?? 0;

// Rata-rata per Transaksi
$rataRata = $totalTransaksi > 0 ? $totalPendapatan / $totalTransaksi : 0;

// Data untuk grafik (7 hari terakhir atau 12 bulan terakhir)
$labelGrafik = [];
$dataGrafik = [];

if ($filter == 'tahun_ini') {
    // Grafik per bulan
    for ($i = 1; $i <= 12; $i++) {
        $qGrafik = mysqli_query($koneksi, "
            SELECT SUM(td.subtotal) as total 
            FROM transaksi_detail td
            JOIN transaksi t ON td.transaksi_id = t.id
            WHERE MONTH(t.created_at) = $i AND YEAR(t.created_at) = YEAR(CURDATE())
        ");
        $total = mysqli_fetch_assoc($qGrafik)['total'] ?? 0;
        $labelGrafik[] = bulanIndo($i);
        $dataGrafik[] = (int)$total;
    }
} else {
    // Grafik per hari (7 hari terakhir)
    for ($i = 6; $i >= 0; $i--) {
        $tanggal = date('Y-m-d', strtotime("-$i days"));
        $qGrafik = mysqli_query($koneksi, "
            SELECT SUM(td.subtotal) as total 
            FROM transaksi_detail td
            JOIN transaksi t ON td.transaksi_id = t.id
            WHERE DATE(t.created_at) = '$tanggal'
        ");
        $total = mysqli_fetch_assoc($qGrafik)['total'] ?? 0;
        $labelGrafik[] = date('d M', strtotime($tanggal));
        $dataGrafik[] = (int)$total;
    }
}

// Data transaksi untuk tabel
$qTransaksiList = mysqli_query($koneksi, "
    SELECT 
        t.id,
        u.nama AS nama_user,
        t.total,
        t.status,
        t.created_at,
        (SELECT COUNT(*) FROM transaksi_detail WHERE transaksi_id = t.id) as jumlah_item
    FROM transaksi t
    JOIN users u ON t.user_id = u.id
    $where
    ORDER BY t.created_at DESC
    LIMIT 20
");

function bulanIndo($bulan) {
    $namaBulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    ];
    return $namaBulan[$bulan];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan | LiteraBooks Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        /* Stats Cards */
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
        }

        .filter-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 30px;
            background: white;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--text-secondary);
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .custom-date {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }

        .custom-date input {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
        }

        .custom-date button {
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
        }

        /* Chart Card */
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid var(--border);
        }

        .chart-card h3 {
            font-size: 1.1rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        canvas {
            max-height: 300px;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: 20px;
            overflow-x: auto;
            border: 1px solid var(--border);
            margin: 1.5rem 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .data-table th {
            background: #f8f9fc;
            padding: 1rem;
            text-align: left;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-selesai {
            background: #d1fae5;
            color: #065f46;
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

        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
            .navbar .container-nav {
                flex-direction: column;
                gap: 1rem;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
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
            <a href="user.php" class="nav-link"><i class="fas fa-users"></i> User</a>
            <a href="transaksi.php" class="nav-link"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="laporan.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Laporan</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Title -->
    <div class="page-header" style="margin: 2rem 0 0;">
        <h1 style="font-size: 1.8rem;"><i class="fas fa-chart-bar"></i> Laporan Pendapatan</h1>
        <p style="color: var(--text-secondary); margin-top: 0.5rem;">Periode: <strong><?= $title; ?></strong></p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info">
                <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.'); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h3><?= number_format($totalTransaksi); ?></h3>
                <p>Total Transaksi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-info">
                <h3><?= number_format($totalBukuTerjual); ?></h3>
                <p>Buku Terjual</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3>Rp <?= number_format($rataRata, 0, ',', '.'); ?></h3>
                <p>Rata-rata/Transaksi</p>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="filter-bar">
        <div class="filter-title"><i class="fas fa-filter"></i> Filter Periode</div>
        <div class="filter-buttons">
            <a href="?filter=hari_ini" class="filter-btn <?= $filter == 'hari_ini' ? 'active' : '' ?>">Hari Ini</a>
            <a href="?filter=kemarin" class="filter-btn <?= $filter == 'kemarin' ? 'active' : '' ?>">Kemarin</a>
            <a href="?filter=minggu_ini" class="filter-btn <?= $filter == 'minggu_ini' ? 'active' : '' ?>">Minggu Ini</a>
            <a href="?filter=minggu_lalu" class="filter-btn <?= $filter == 'minggu_lalu' ? 'active' : '' ?>">Minggu Lalu</a>
            <a href="?filter=bulan_ini" class="filter-btn <?= $filter == 'bulan_ini' ? 'active' : '' ?>">Bulan Ini</a>
            <a href="?filter=bulan_lalu" class="filter-btn <?= $filter == 'bulan_lalu' ? 'active' : '' ?>">Bulan Lalu</a>
            <a href="?filter=tahun_ini" class="filter-btn <?= $filter == 'tahun_ini' ? 'active' : '' ?>">Tahun Ini</a>
            <a href="?filter=semua" class="filter-btn <?= $filter == 'semua' ? 'active' : '' ?>">Semua</a>
        </div>
        
        <form method="GET" class="custom-date">
            <input type="hidden" name="filter" value="custom">
            <input type="date" name="start_date" value="<?= $start_date ?>">
            <span>s/d</span>
            <input type="date" name="end_date" value="<?= $end_date ?>">
            <button type="submit"><i class="fas fa-calendar"></i> Tampilkan</button>
        </form>
    </div>

    <!-- Chart -->
    <div class="chart-card">
        <h3><i class="fas fa-chart-line"></i> Grafik Pendapatan</h3>
        <canvas id="revenueChart"></canvas>
    </div>

    <!-- Recent Transactions -->
    <div class="chart-card">
        <h3><i class="fas fa-history"></i> Transaksi Terbaru</h3>
        <div class="table-container" style="margin: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pembeli</th>
                        <th>Jumlah Item</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($qTransaksiList) > 0): ?>
                        <?php while ($trx = mysqli_fetch_assoc($qTransaksiList)): ?>
                            <tr>
                                <td>#<?= $trx['id'] ?></td>
                                <td><?= htmlspecialchars($trx['nama_user']) ?></td>
                                <td><?= $trx['jumlah_item'] ?> item</td>
                                <td><strong>Rp <?= number_format($trx['total'], 0, ',', '.') ?></strong></td>
                                <td><span class="status-badge status-<?= strtolower($trx['status']) ?>"><?= $trx['status'] ?></span></td>
                                <td><?= date('d-m-Y H:i', strtotime($trx['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">Belum ada transaksi</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
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
                    <li><a href="user.php">Kelola User</a></li>
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

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labelGrafik) ?>,
        datasets: [{
            label: 'Pendapatan (Rp)',
            data: <?= json_encode($dataGrafik) ?>,
            borderColor: '#2d3b5e',
            backgroundColor: 'rgba(45, 59, 94, 0.05)',
            borderWidth: 3,
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#9b8c6c',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Rp ' + context.raw.toLocaleString('id-ID');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

</body>
</html>