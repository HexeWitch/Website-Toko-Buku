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
   HAPUS USER
====================== */
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    
    // Cek jangan sampai admin menghapus dirinya sendiri
    if ($id != $_SESSION['admin']['id']) {
        mysqli_query($koneksi, "DELETE FROM users WHERE id = $id");
    }
    
    header("Location: user.php");
    exit;
}

/* ======================
   UPDATE USER (EDIT)
====================== */
if (isset($_POST['update'])) {
    $id = (int) $_POST['id'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    
    if (!empty($password)) {
        // Jika password diisi, update dengan password baru
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "UPDATE users SET nama='$nama', email='$email', password='$hash' WHERE id=$id");
    } else {
        // Jika password kosong, update tanpa mengubah password
        mysqli_query($koneksi, "UPDATE users SET nama='$nama', email='$email' WHERE id=$id");
    }
    
    header("Location: user.php");
    exit;
}

/* ======================
   TAMBAH USER BARU
====================== */
if (isset($_POST['tambah'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    
    // Cek email sudah terdaftar
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($cek) == 0 && !empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        mysqli_query($koneksi, "INSERT INTO users (nama, email, password) VALUES ('$nama', '$email', '$hash')");
    }
    
    header("Location: user.php");
    exit;
}

/* ======================
   AMBIL SEMUA USER
====================== */
$keyword = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';

$sql = "SELECT * FROM users WHERE 1";
if ($keyword != '') {
    $sql .= " AND (nama LIKE '%$keyword%' OR email LIKE '%$keyword%')";
}
$sql .= " ORDER BY id DESC";
$query = mysqli_query($koneksi, $sql);

$totalUser = mysqli_num_rows($query);

// Ambil user yang sedang diedit (untuk modal edit)
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editQuery = mysqli_query($koneksi, "SELECT * FROM users WHERE id = $editId");
    $editUser = mysqli_fetch_assoc($editQuery);
}

// Hitung total user yang pernah komplain (bukan total chat)
$userPernahKomplain = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT user_id) as total FROM komplain
"))['total'];

// Hitung total komplain belum dibaca
$totalBelumDibaca = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM komplain WHERE status = 'belum_dibaca' AND pengirim = 'user'
"))['total'];

// Hitung total pesan/messages (semua chat)
$totalPesan = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM komplain
"))['total'];

// Hitung total pesan belum dibaca admin
$totalPesanBelumDibaca = mysqli_fetch_assoc(mysqli_query($koneksi, "
    SELECT COUNT(*) as total FROM komplain WHERE status = 'belum_dibaca' AND pengirim = 'user'
"))['total'];

// Hitung komplain per user untuk ditampilkan di tabel
$komplainStats = [];
$komplainQuery = mysqli_query($koneksi, "
    SELECT 
        u.id as user_id,
        u.nama as user_nama,
        COUNT(k.id) as total_komplain,
        SUM(CASE WHEN k.status = 'belum_dibaca' AND k.pengirim = 'user' THEN 1 ELSE 0 END) as belum_dibaca
    FROM users u
    LEFT JOIN komplain k ON u.id = k.user_id
    GROUP BY u.id
");
while ($row = mysqli_fetch_assoc($komplainQuery)) {
    $komplainStats[$row['user_id']] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User | LiteraBooks Admin</title>
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

        /* Color Palette */
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
            --danger: #dc2626;
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
        }

        /* Container */
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

        .nav-link.active {
            color: var(--accent);
        }

        .admin-badge {
            background: var(--accent-gold);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        /* ========== PAGE HEADER ========== */
        .page-header {
            padding: 2rem 0 1rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-header h1 i {
            color: var(--accent-gold);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
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
            text-decoration: none;
            color: inherit;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .stat-info p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .badge-stat {
            font-size: 0.7rem;
            background: var(--danger);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
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
            justify-content: space-between;
        }

        .filter-group {
            flex: 1;
            min-width: 250px;
        }

        .filter-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }

        .search-wrapper {
            display: flex;
            gap: 0.5rem;
        }

        .search-wrapper input {
            flex: 1;
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.85rem;
            font-family: inherit;
            background: #f8f9fc;
        }

        .search-wrapper input:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
        }

        .search-wrapper button {
            background: var(--accent);
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .search-wrapper button:hover {
            background: var(--accent-light);
        }

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-add:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-reset {
            background: #f0f2f6;
            border: none;
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-reset:hover {
            background: var(--border);
            color: var(--text-primary);
        }

        /* ========== TABLE STYLES ========== */
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
            min-width: 800px;
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

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: #fafbfc;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
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
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-edit:hover {
            background: var(--accent-light);
            transform: translateY(-1px);
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
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-delete:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-komplain {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: var(--accent-gold);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-komplain:hover {
            background: #8a7b5e;
            transform: translateY(-1px);
        }

        .badge-komplain {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: #fef3c7;
            color: #92400e;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
            margin-left: 0.5rem;
        }

        .badge-komplain-new {
            background: var(--danger);
            color: white;
        }

        /* Badge */
        .badge-admin {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            background: var(--accent-gold);
            border-radius: 20px;
            font-size: 0.7rem;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-secondary);
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1001;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .modal-close:hover {
            color: var(--danger);
        }

        .modal .form-group {
            margin-bottom: 1rem;
        }

        .modal .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
            color: var(--text-primary);
        }

        .modal .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .modal .form-group input:focus {
            outline: none;
            border-color: var(--accent);
        }

        .modal .btn-submit {
            width: 100%;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
        }

        /* ========== FOOTER ========== */
        .footer {
            background: #0a0e17;
            color: white;
            padding: 3rem 0 2rem;
            margin-top: 3rem;
        }

        .footer .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
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
            margin-top: 1rem;
        }

        .footer h4 {
            font-size: 1rem;
            margin-bottom: 1.2rem;
            color: white;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.7rem;
        }

        .footer-links a {
            text-decoration: none;
            color: #8e98a8;
            font-size: 0.85rem;
            transition: color 0.2s;
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

        /* ========== RESPONSIVE ========== */
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
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filter-bar {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
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

<!-- NAVBAR -->
<nav class="navbar">
    <div class="container-nav">
        <a href="index.php" class="logo">
            <div class="logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <span class="logo-text">litera<span>admin</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="total_buku.php" class="nav-link">
                <i class="fas fa-book"></i> Kelola Buku
            </a>
            <a href="user.php" class="nav-link active">
                <i class="fas fa-users"></i> Kelola User
            </a>
            <a href="transaksi.php" class="nav-link">
                <i class="fas fa-receipt"></i> Transaksi
            </a>
            <a href="komplain.php" class="nav-link">
                <i class="fas fa-comment-dots"></i> Komplain
            </a>
            <a href="logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
            <span class="admin-badge">
                <i class="fas fa-shield-alt"></i> Admin
            </span>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-users"></i>
            Kelola User
        </h1>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalUser; ?></h3>
                <p>Total User</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-info">
                <h3><?php 
                    $newUsers = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $newCount = mysqli_fetch_assoc($newUsers)['total'];
                    echo $newCount;
                ?></h3>
                <p>User Baru (30 hari)</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?php
                    $activeUsers = mysqli_query($koneksi, "SELECT COUNT(DISTINCT user_id) as total FROM transaksi WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
                    $activeCount = mysqli_fetch_assoc($activeUsers)['total'];
                    echo $activeCount;
                ?></h3>
                <p>User Aktif (30 hari)</p>
            </div>
        </div>
        <a href="komplain.php" class="stat-card" style="text-decoration: none;">
            <div class="stat-icon">
                <i class="fas fa-comment-dots"></i>
            </div>
            <div class="stat-info">
                <h3><?= $userPernahKomplain; ?>
                    <?php if($totalBelumDibaca > 0): ?>
                        <span class="badge-stat"><?= $totalBelumDibaca ?> baru</span>
                    <?php endif; ?>
                </h3>
                <p>User Pernah Komplain</p>
            </div>
        </a>
        <a href="chat.php" class="stat-card" style="text-decoration: none;">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-info">
                <h3><?= $totalPesan; ?>
                    <?php if($totalPesanBelumDibaca > 0): ?>
                        <span class="badge-stat"><?= $totalPesanBelumDibaca ?> baru</span>
                    <?php endif; ?>
                </h3>
                <p>Total Pesan Masuk</p>
            </div>
        </a>
    </div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
        <div class="filter-group">
            <label><i class="fas fa-search"></i> Cari User</label>
            <form method="GET" action="" class="search-wrapper">
                <input type="text" name="q" placeholder="Cari nama atau email..." value="<?= htmlspecialchars($keyword); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
        <div class="filter-group">
            <label>&nbsp;</label>
            <button class="btn-add" onclick="openTambahModal()">
                <i class="fas fa-plus"></i> Tambah User
            </button>
            <?php if($keyword): ?>
                <a href="user.php" class="btn-reset" style="margin-left: 0.5rem;">
                    <i class="fas fa-undo-alt"></i> Reset
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABLE -->
    <?php if ($totalUser > 0): ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Bergabung</th>
                        <th>Total Transaksi</th>
                        <th>Komplain</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    mysqli_data_seek($query, 0);
                    while ($user = mysqli_fetch_assoc($query)): 
                        // Hitung total transaksi user ini
                        $transCount = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM transaksi WHERE user_id = " . $user['id']);
                        $totalTrans = mysqli_fetch_assoc($transCount)['total'];
                        
                        $userKomplain = isset($komplainStats[$user['id']]) ? $komplainStats[$user['id']] : ['total_komplain' => 0, 'belum_dibaca' => 0];
                    ?>
                    <tr>
                        <td data-label="ID"><?= $no++; ?></td>
                        <td data-label="Nama">
                            <strong><?= htmlspecialchars($user['nama']); ?></strong>
                        </td>
                        <td data-label="Email"><?= htmlspecialchars($user['email']); ?></td>
                        <td data-label="Bergabung">
                            <i class="fas fa-calendar-alt" style="color: var(--text-muted); margin-right: 0.3rem;"></i>
                            <?= date('d-m-Y', strtotime($user['created_at'])); ?>
                        </td>
                        <td data-label="Total Transaksi">
                            <span class="badge-admin"><?= $totalTrans; ?> transaksi</span>
                        </td>
                        <td data-label="Komplain">
                            <?php if ($userKomplain['total_komplain'] > 0): ?>
                                <a href="komplain.php?user_id=<?= $user['id'] ?>" class="btn-komplain">
                                    <i class="fas fa-comment-dots"></i> <?= $userKomplain['total_komplain'] ?> pesan
                                    <?php if ($userKomplain['belum_dibaca'] > 0): ?>
                                        <span class="badge-komplain badge-komplain-new"><?= $userKomplain['belum_dibaca'] ?> baru</span>
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 0.7rem;">
                                    <i class="fas fa-comment"></i> Tidak ada
                                </span>
                            <?php endif; ?>
                         </td>
                        <td data-label="Aksi">
                            <div class="action-buttons">
                                <button class="btn-edit" onclick="openEditModal(<?= $user['id']; ?>, '<?= htmlspecialchars($user['nama']); ?>', '<?= htmlspecialchars($user['email']); ?>')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($user['id'] != 1): // Cegah hapus admin utama ?>
                                    <a href="user.php?hapus=<?= $user['id']; ?>" class="btn-delete" onclick="return confirm('Yakin ingin menghapus user ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                <?php endif; ?>
                            </div>
                         </td>
                     </tr>
                    <?php endwhile; ?>
                </tbody>
             </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>Belum ada user terdaftar</p>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL TAMBAH USER -->
<div id="tambahModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Tambah User Baru</h3>
            <button class="modal-close" onclick="closeTambahModal()">&times;</button>
        </div>
        <form method="post">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" required placeholder="Masukkan nama lengkap">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="contoh@email.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Minimal 6 karakter">
            </div>
            <button type="submit" name="tambah" class="btn-submit">
                <i class="fas fa-save"></i> Simpan User
            </button>
        </form>
    </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Edit User</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="post">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" id="edit_nama" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="form-group">
                <label>Password (Kosongkan jika tidak diubah)</label>
                <input type="password" name="password" placeholder="Minimal 6 karakter">
            </div>
            <button type="submit" name="update" class="btn-submit">
                <i class="fas fa-save"></i> Update User
            </button>
        </form>
    </div>
</div>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <span class="footer-logo-text">litera<span style="font-weight:400;">admin</span></span>
                </div>
                <p>Sistem manajemen toko buku LiteraBooks. Kelola user, buku, transaksi, dan pantau penjualan dengan mudah.</p>
            </div>
            
            <div>
                <h4>Menu</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Dashboard</a></li>
                    <li><a href="total_buku.php">Kelola Buku</a></li>
                    <li><a href="user.php">Kelola User</a></li>
                    <li><a href="transaksi.php">Transaksi</a></li>
                    <li><a href="komplain.php">Komplain</a></li>
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
                    <li><i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> admin@literabooks.com</li>
                    <li><i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> +62 812 3456 7890</li>
                    <li><i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> Jakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; <?= date('Y'); ?> LiteraBooks Admin Panel. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    // Modal Tambah
    function openTambahModal() {
        document.getElementById('tambahModal').classList.add('show');
    }
    
    function closeTambahModal() {
        document.getElementById('tambahModal').classList.remove('show');
    }
    
    // Modal Edit
    function openEditModal(id, nama, email) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_email').value = email;
        document.getElementById('editModal').classList.add('show');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
    }
    
    // Tutup modal klik di luar
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('show');
        }
    }
</script>

</body>
</html>