<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================
   UPDATE STATUS PESANAN
========================= */
if (isset($_GET['aksi'], $_GET['id'])) {
    $id = (int) $_GET['id'];

    if ($_GET['aksi'] == 'kirim') {
        mysqli_query($koneksi, "UPDATE transaksi SET status='Dikirim' WHERE id=$id");
    }

    if ($_GET['aksi'] == 'selesai') {
        mysqli_query($koneksi, "UPDATE transaksi SET status='Selesai' WHERE id=$id");
    }

    header("Location: transaksi.php");
    exit;
}

/* =========================
   FILTER STATUS
========================= */
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'semua';
$status_where = "";
if ($filter_status != 'semua') {
    $status_where = "AND t.status = '$filter_status'";
}

/* =========================
   AMBIL DATA TRANSAKSI
========================= */
$q = mysqli_query(
    $koneksi,
    "SELECT 
        t.id,
        t.user_id,
        u.nama AS nama_user,
        u.email AS email_user,
        t.total,
        t.status,
        t.metode_bayar,
        t.bukti_pembayaran,
        t.created_at,
        GROUP_CONCAT(
            CONCAT(b.judul, ' (', d.qty, ')')
            SEPARATOR ', '
        ) AS buku_dibeli
     FROM transaksi t
     JOIN users u ON t.user_id = u.id
     LEFT JOIN transaksi_detail d ON d.transaksi_id = t.id
     LEFT JOIN buku b ON d.buku_id = b.id
     WHERE 1 $status_where
     GROUP BY t.id
     ORDER BY t.created_at DESC"
);

if (!$q) {
    die('Query error: ' . mysqli_error($koneksi));
}

/* =========================
   AMBIL DETAIL TRANSAKSI (AJAX)
========================= */
if (isset($_GET['detail'])) {
    $id = (int) $_GET['detail'];
    
    $transaksi = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT t.*, u.nama, u.email 
        FROM transaksi t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = $id
    "));
    
    $alamat = mysqli_fetch_assoc(mysqli_query($koneksi, "
        SELECT * FROM user_alamat 
        WHERE user_id = {$transaksi['user_id']} 
        ORDER BY is_default DESC, id DESC 
        LIMIT 1
    "));
    
    $details = mysqli_query($koneksi, "
        SELECT d.*, b.judul, b.gambar 
        FROM transaksi_detail d 
        JOIN buku b ON d.buku_id = b.id 
        WHERE d.transaksi_id = $id
    ");
    
    $items = [];
    while ($row = mysqli_fetch_assoc($details)) {
        $items[] = $row;
    }
    
    echo json_encode([
        'transaksi' => $transaksi,
        'alamat' => $alamat,
        'items' => $items
    ]);
    exit;
}

// Hitung statistik
$totalTransaksi = 0;
$totalPendapatan = 0;
$diproses = 0;
$dikirim = 0;
$selesai = 0;
$menunggu = 0;

mysqli_data_seek($q, 0);
while ($t = mysqli_fetch_assoc($q)) {
    $totalTransaksi++;
    $totalPendapatan += $t['total'];
    $status = strtolower($t['status']);
    if ($status == 'diproses') $diproses++;
    elseif ($status == 'dikirim') $dikirim++;
    elseif ($status == 'selesai') $selesai++;
    elseif ($status == 'menunggu pembayaran' || $status == 'menunggu konfirmasi') $menunggu++;
}
mysqli_data_seek($q, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Transaksi | LiteraBooks Admin</title>
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
            --warning: #f59e0b;
            --info: #3b82f6;
            --danger: #dc2626;
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

        /* Page Header */
        .page-header {
            margin: 2rem 0 0;
        }

        .page-header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 i {
            color: var(--accent-gold);
        }

        .page-header p {
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
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
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-info p {
            font-size: 0.75rem;
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

        /* Table Container */
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
            min-width: 900px;
        }

        .data-table th {
            background: #f8f9fc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .data-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: #fafbfc;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-selesai {
            background: #d1fae5;
            color: #065f46;
        }

        .status-diproses {
            background: #fed7aa;
            color: #92400e;
        }

        .status-dikirim {
            background: #bfdbfe;
            color: #1e40af;
        }

        .status-menunggu-pembayaran, .status-menunggu-konfirmasi {
            background: #fef3c7;
            color: #92400e;
        }

        /* Method Badge */
        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .method-qris {
            background: #fef3c7;
            color: #92400e;
        }

        .method-cod {
            background: #d1fae5;
            color: #065f46;
        }

        /* Buttons */
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.4rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
        }

        .btn-detail {
            background: #e5e7eb;
            color: var(--text-primary);
            margin-right: 0.5rem;
        }

        .btn-detail:hover {
            background: #d1d5db;
        }

        .btn-kirim {
            background: var(--info);
            color: white;
        }

        .btn-kirim:hover {
            background: #2563eb;
        }

        .btn-selesai {
            background: var(--success);
            color: white;
        }

        .btn-selesai:hover {
            background: #059669;
        }

        .btn-disabled {
            background: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }

        /* Modal Detail */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            border-radius: 24px 24px 0 0;
        }

        .modal-header h2 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-header .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .detail-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .detail-section h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
            color: var(--accent-gold);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.8rem;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-item .value {
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.2rem;
        }

        .alamat-box {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1rem;
        }

        .alamat-box p {
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th, .items-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .items-table th {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        .bukti-img {
            max-width: 200px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .loading {
            text-align: center;
            padding: 2rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
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

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
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
            .detail-grid {
                grid-template-columns: 1fr;
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
            <a href="transaksi.php" class="nav-link active"><i class="fas fa-receipt"></i> Transaksi</a>
            <a href="laporan.php" class="nav-link"><i class="fas fa-chart-bar"></i> Laporan</a>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-receipt"></i> Data Transaksi</h1>
        <p>Kelola dan pantau semua transaksi yang terjadi</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <h3><?= number_format($totalTransaksi); ?></h3>
                <p>Total Transaksi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info">
                <h3>Rp <?= number_format($totalPendapatan, 0, ',', '.'); ?></h3>
                <p>Total Pendapatan</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h3><?= $menunggu ?></h3>
                <p>Menunggu Konfirmasi</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-spinner"></i></div>
            <div class="stat-info">
                <h3><?= $diproses ?></h3>
                <p>Diproses</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-truck"></i></div>
            <div class="stat-info">
                <h3><?= $dikirim ?></h3>
                <p>Dikirim</p>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-title"><i class="fas fa-filter"></i> Filter Status</div>
        <div class="filter-buttons">
            <a href="?status=semua" class="filter-btn <?= $filter_status == 'semua' ? 'active' : '' ?>">Semua</a>
            <a href="?status=Menunggu Pembayaran" class="filter-btn <?= $filter_status == 'Menunggu Pembayaran' ? 'active' : '' ?>">Menunggu</a>
            <a href="?status=Diproses" class="filter-btn <?= $filter_status == 'Diproses' ? 'active' : '' ?>">Diproses</a>
            <a href="?status=Dikirim" class="filter-btn <?= $filter_status == 'Dikirim' ? 'active' : '' ?>">Dikirim</a>
            <a href="?status=Selesai" class="filter-btn <?= $filter_status == 'Selesai' ? 'active' : '' ?>">Selesai</a>
        </div>
    </div>

    <?php if (mysqli_num_rows($q) > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pembeli</th>
                        <th>Buku</th>
                        <th>Total</th>
                        <th>Metode</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($t = mysqli_fetch_assoc($q)): ?>
                    <tr>
                        <td><strong>#<?= $t['id']; ?></strong></td>
                        <td>
                            <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                            <?= htmlspecialchars($t['nama_user']); ?>
                        </td>
                        <td class="buku-list" style="max-width: 250px; font-size: 0.8rem; color: var(--text-secondary);">
    <?= $t['buku_dibeli'] ? htmlspecialchars($t['buku_dibeli']) : '-'; ?>
</td>
                        <td><strong style="color: var(--accent);">Rp <?= number_format($t['total'], 0, ',', '.'); ?></strong></td>
                        <td>
                            <?php if($t['metode_bayar'] == 'qris'): ?>
                                <span class="method-badge method-qris"><i class="fas fa-qrcode"></i> QRIS</span>
                            <?php else: ?>
                                <span class="method-badge method-cod"><i class="fas fa-money-bill-wave"></i> COD</span>
                            <?php endif; ?>
                         </td>
                        <td>
                            <?php
                                $status = strtolower($t['status']);
                                $icon = '';
                                if ($status == 'selesai') $icon = 'fa-check-circle';
                                elseif ($status == 'diproses') $icon = 'fa-spinner';
                                elseif ($status == 'dikirim') $icon = 'fa-truck';
                                else $icon = 'fa-clock';
                            ?>
                            <span class="status-badge status-<?= str_replace(' ', '-', $status); ?>">
                                <i class="fas <?= $icon; ?>"></i> <?= $t['status']; ?>
                            </span>
                         </td>
                        <td><i class="fas fa-calendar-alt" style="color: var(--text-muted); margin-right: 0.3rem;"></i><?= date('d-m-Y H:i', strtotime($t['created_at'])); ?></td>
                        <td>
                            <button class="btn-action btn-detail" onclick="showDetail(<?= $t['id']; ?>)">
                                <i class="fas fa-eye"></i> Detail
                            </button>
                            <?php if ($t['status'] == 'Menunggu Pembayaran' || $t['status'] == 'Menunggu Konfirmasi'): ?>
                                <span class="btn-action btn-disabled"><i class="fas fa-clock"></i> Menunggu</span>
                            <?php elseif ($t['status'] == 'Diproses'): ?>
                                <a class="btn-action btn-kirim" href="?aksi=kirim&id=<?= $t['id']; ?>" onclick="return confirm('Kirim pesanan ini?')">
                                    <i class="fas fa-truck"></i> Kirim
                                </a>
                            <?php elseif ($t['status'] == 'Dikirim'): ?>
                                <a class="btn-action btn-selesai" href="?aksi=selesai&id=<?= $t['id']; ?>" onclick="return confirm('Selesaikan pesanan ini?')">
                                    <i class="fas fa-check-circle"></i> Selesai
                                </a>
                            <?php else: ?>
                                <span class="btn-action btn-disabled"><i class="fas fa-check"></i> Selesai</span>
                            <?php endif; ?>
                         </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>Belum ada transaksi</p>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL DETAIL TRANSAKSI -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-receipt"></i> Detail Transaksi</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>
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
                    <li><i class="fas fa-envelope" style="margin-right: 0.5rem;"></i> admin@literabooks.com</li>
                    <li><i class="fas fa-phone" style="margin-right: 0.5rem;"></i> +62 812 3456 7890</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel.</p>
        </div>
    </div>
</footer>

<script>
    let currentDetailId = null;

    async function showDetail(id) {
        const modal = document.getElementById('detailModal');
        const body = document.getElementById('modalBody');
        modal.style.display = 'flex';
        body.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Memuat data...</div>';
        
        try {
            const response = await fetch(`?detail=${id}`);
            const data = await response.json();
            
            let buktiHtml = '';
            if (data.transaksi.metode_bayar == 'qris' && data.transaksi.bukti_pembayaran) {
                buktiHtml = `
                    <div class="detail-section">
                        <h3><i class="fas fa-image"></i> Bukti Pembayaran</h3>
                        <img src="../uploads/bukti/${data.transaksi.bukti_pembayaran}" class="bukti-img" alt="Bukti Pembayaran" onerror="this.src='https://placehold.co/200x200?text=Bukti+Tidak+Ditemukan'">
                    </div>
                `;
            }
            
            let alamatHtml = '';
            if (data.alamat) {
                alamatHtml = `
                    <div class="detail-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                        <div class="alamat-box">
                            <p><strong>${escapeHtml(data.alamat.nama_penerima)}</strong></p>
                            <p>${escapeHtml(data.alamat.alamat)}</p>
                            <p>${escapeHtml(data.alamat.kota)}, ${escapeHtml(data.alamat.provinsi)}</p>
                            <p>📞 ${escapeHtml(data.alamat.no_hp)}</p>
                            ${data.alamat.kode_pos ? `<p>📮 Kode Pos: ${escapeHtml(data.alamat.kode_pos)}</p>` : ''}
                        </div>
                    </div>
                `;
            } else {
                alamatHtml = `
                    <div class="detail-section">
                        <h3><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</h3>
                        <p style="color: var(--text-muted);">Belum ada alamat tersimpan</p>
                    </div>
                `;
            }
            
            let itemsHtml = `
                <div class="detail-section">
                    <h3><i class="fas fa-shopping-bag"></i> Daftar Buku</h3>
                    <table class="items-table">
                        <thead>
                            <tr><th>Buku</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
            `;
            
            data.items.forEach(item => {
                itemsHtml += `<tr>
                    <td>${escapeHtml(item.judul)}</td>
                    <td>Rp ${formatNumber(item.harga)}</td>
                    <td>${item.qty}</td>
                    <td>Rp ${formatNumber(item.subtotal)}</td>
                </tr>`;
            });
            
            itemsHtml += `</tbody></table></div>`;
            
            body.innerHTML = `
                <div class="detail-section">
                    <h3><i class="fas fa-info-circle"></i> Informasi Transaksi</h3>
                    <div class="detail-grid">
                        <div class="detail-item"><label>ID Transaksi</label><div class="value">#${data.transaksi.id}</div></div>
                        <div class="detail-item"><label>Tanggal</label><div class="value">${new Date(data.transaksi.created_at).toLocaleString('id-ID')}</div></div>
                        <div class="detail-item"><label>Pembeli</label><div class="value">${escapeHtml(data.transaksi.nama)}</div></div>
                        <div class="detail-item"><label>Email</label><div class="value">${escapeHtml(data.transaksi.email)}</div></div>
                        <div class="detail-item"><label>Metode Bayar</label><div class="value">${data.transaksi.metode_bayar == 'qris' ? 'QRIS' : 'COD (Bayar di Tempat)'}</div></div>
                        <div class="detail-item"><label>Status</label><div class="value">${data.transaksi.status}</div></div>
                        <div class="detail-item"><label>Total</label><div class="value" style="font-size: 1.1rem; color: var(--accent);">Rp ${formatNumber(data.transaksi.total)}</div></div>
                    </div>
                </div>
                ${alamatHtml}
                ${buktiHtml}
                ${itemsHtml}
            `;
        } catch (error) {
            body.innerHTML = '<div class="loading" style="color: var(--danger);"><i class="fas fa-exclamation-circle"></i> Gagal memuat data</div>';
        }
    }
    
    function closeModal() {
        document.getElementById('detailModal').style.display = 'none';
    }
    
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    window.onclick = function(e) {
        const modal = document.getElementById('detailModal');
        if (e.target === modal) closeModal();
    }
</script>

</body>
</html>