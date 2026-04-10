<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/koneksi.php';

if (!isset($koneksi)) {
    die("Koneksi database tidak ditemukan");
}

session_start();

$cartCount = 0;
$cartTotalItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartTotalItems += (int)$qty;
    }
}

$totalWishlist = 0;
if (isset($_SESSION['user'])) {
    $uid = $_SESSION['user']['id'];
    $qWish = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM wishlist WHERE user_id = $uid");
    $totalWishlist = mysqli_fetch_assoc($qWish)['total'];
}

$q = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;

$sql = "SELECT buku.*, kategori.nama_kategori 
        FROM buku 
        LEFT JOIN kategori ON buku.kategori_id = kategori.id 
        WHERE 1";

if ($q != '') {
    $sql .= " AND (buku.judul LIKE '%$q%' OR buku.penulis LIKE '%$q%')";
}

if ($kategori_id > 0) {
    $sql .= " AND buku.kategori_id = $kategori_id";
}

$sql .= " ORDER BY buku.judul ASC";
$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Query error: " . mysqli_error($koneksi));
}

$kategoriList = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");

$totalBuku = mysqli_num_rows($query);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Buku | LiteraBooks</title>
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

        .cart-badge {
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.3rem;
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-left: 1rem;
            margin-left: 0.5rem;
            border-left: 1px solid var(--border);
        }

        .username {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-primary);
        }

        .nav-search {
            flex: 1;
            max-width: 400px;
            margin: 0 2rem;
        }

        .search-form-nav {
            display: flex;
            background: #f0f2f6;
            border-radius: 40px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .search-form-nav:focus-within {
            background: white;
            box-shadow: 0 0 0 2px rgba(45,59,94,0.1);
        }

        .search-input-nav {
            flex: 1;
            padding: 0.7rem 1.2rem;
            border: none;
            outline: none;
            font-size: 0.85rem;
            font-family: inherit;
            background: transparent;
        }

        .search-btn-nav {
            background: transparent;
            border: none;
            padding: 0 1rem;
            cursor: pointer;
            color: var(--text-muted);
        }

        .page-header {
            background: linear-gradient(135deg, #f8f9fc 0%, #f0f2f6 100%);
            padding: 3rem 0;
            text-align: center;
            border-bottom: 1px solid var(--border);
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        .filter-bar {
            max-width: 400px;
            margin: 0 auto;
            display: flex;
            gap: 1rem;
            justify-content: center;
            align-items: center;
        }

        .filter-bar form {
            flex: 1;
        }

        .dropdown-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .dropdown-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            z-index: 1;
            pointer-events: none;
        }

        .modern-dropdown {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.2s;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235a6474' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1rem;
        }

        .modern-dropdown:hover {
            border-color: var(--accent);
        }

        .modern-dropdown:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(45,59,94,0.1);
        }

        .wishlist-filter-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.9rem 1.2rem;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .wishlist-filter-btn:hover {
            background: #fee2e2;
            border-color: var(--danger);
            transform: translateY(-2px);
        }

        .wishlist-filter-btn i {
            color: var(--danger);
            font-size: 1rem;
        }

        .wishlist-filter-btn .badge {
            background: var(--danger);
            color: white;
            border-radius: 20px;
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            margin-left: 0.3rem;
        }

        .buku-list {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            padding: 3rem 0;
        }

        .buku-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .buku-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .wishlist-btn {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            z-index: 10;
            background: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 1.2rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
        }

        .wishlist-btn:hover {
            transform: scale(1.1);
        }

        .wishlist-btn.active {
            background: #fee2e2;
        }

        .cover-wrap {
            aspect-ratio: 2 / 2.5;
            overflow: hidden;
            background: #f0f2f6;
        }

        .cover-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .buku-card:hover .cover-wrap img {
            transform: scale(1.05);
        }

        .buku-info {
            padding: 1.25rem;
        }

        .buku-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .penulis {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .kategori-label {
            font-size: 0.7rem;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
            display: inline-block;
            background: #f0f2f6;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
        }

        .stok-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stok-tersedia {
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

        .harga {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.75rem;
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.2s;
            width: 100%;
            justify-content: center;
        }

        .btn-detail:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 16px;
            grid-column: 1/-1;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

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

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-link {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        .social-link:hover {
            background: var(--accent-gold);
            transform: translateY(-2px);
        }

        .social-link i {
            color: white;
            font-size: 0.9rem;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 2rem;
            text-align: center;
            font-size: 0.8rem;
            color: #5a6474;
        }

        @media (max-width: 1024px) {
            .buku-list { grid-template-columns: repeat(3, 1fr); }
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            .container, .footer .container { padding: 0 1.5rem; }
            .navbar .container-nav { flex-direction: column; gap: 1rem; padding: 1rem; }
            .nav-search { max-width: 100%; margin: 0; width: 100%; }
            .nav-links { flex-wrap: wrap; justify-content: center; gap: 1rem; }
            .buku-list { grid-template-columns: repeat(2, 1fr); gap: 1rem; }
            .filter-bar { flex-direction: column; max-width: 100%; }
            .footer-grid { grid-template-columns: 1fr; gap: 2rem; }
        }

        @media (max-width: 480px) {
            .buku-list { grid-template-columns: 1fr; }
            .page-header h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-nav">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">litera<span>books</span></span>
        </a>

        <div class="nav-search">
            <form action="buku.php" method="get" class="search-form-nav">
                <?php if($kategori_id > 0): ?>
                    <input type="hidden" name="kategori_id" value="<?= $kategori_id; ?>">
                <?php endif; ?>
                <input type="text" name="q" class="search-input-nav" placeholder="Cari judul atau penulis..." value="<?= htmlspecialchars($q); ?>">
                <button type="submit" class="search-btn-nav"><i class="fas fa-search"></i></button>
            </form>
        </div>
 <div class="nav-links">
            <a href="index.php" class="nav-link">
                <i class="fas fa-home"></i> Beranda
            </a>
            <a href="buku.php" class="nav-link">
                <i class="fas fa-book"></i> Buku
            </a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Keranjang
                <?php if($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                <?php endif; ?>
            </a>
            <a href="about.php" class="nav-link active">
                <i class="fas fa-info-circle"></i> Tentang Kami
            </a>
            <?php if(isset($_SESSION['user'])): ?>
                <span style="color: var(--text-secondary); font-size:0.85rem;">
                    <i class="fas fa-user"></i> <?= $_SESSION['user']['nama']; ?>
                </span>
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="login.php" class="nav-link">
                    <i class="fas fa-sign-in-alt"></i> Masuk
                </a>
                <a href="register.php" class="nav-link">
                    <i class="fas fa-user-plus"></i> Daftar
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="page-header">
    <div class="container">
        <h1>Koleksi Buku</h1>
        <p>Temukan berbagai macam buku menarik dari berbagai kategori</p>

        <div class="filter-bar">
            <form method="GET">
                <?php if($q): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($q); ?>">
                <?php endif; ?>
                <div class="dropdown-wrapper">
                    <span class="dropdown-icon">📂</span>
                    <select name="kategori_id" onchange="this.form.submit()" class="modern-dropdown">
                        <option value="0">Semua Kategori</option>
                        <?php
                        mysqli_data_seek($kategoriList, 0);
                        while($k = mysqli_fetch_assoc($kategoriList)): ?>
                            <option value="<?= $k['id']; ?>" <?= ($kategori_id == $k['id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($k['nama_kategori']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>

            <?php if(isset($_SESSION['user'])): ?>
                <a href="wishlist.php" class="wishlist-filter-btn">
                    <i class="fas fa-heart"></i> Wishlist
                    <?php if($totalWishlist > 0): ?>
                        <span class="badge"><?= $totalWishlist ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container">
    <div class="buku-list">
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($buku = mysqli_fetch_assoc($query)): 
                $stok = $buku['stok'] ?? 0;
                if($stok >= 10) {
                    $stokClass = 'stok-tersedia';
                    $stokIcon = '✅';
                    $stokText = 'Tersedia';
                } elseif($stok > 0) {
                    $stokClass = 'stok-sedikit';
                    $stokIcon = '⚠️';
                    $stokText = "Sisa $stok";
                } else {
                    $stokClass = 'stok-habis';
                    $stokIcon = '❌';
                    $stokText = 'Habis';
                }
                
                $nama_kategori = $buku['nama_kategori'] ?? 'Tidak Berkategori';

                $isInWishlist = false;
                if(isset($_SESSION['user'])) {
                    $uid = $_SESSION['user']['id'];
                    $cekWish = mysqli_query($koneksi, "SELECT id FROM wishlist WHERE user_id='$uid' AND buku_id='".$buku['id']."'");
                    $isInWishlist = mysqli_num_rows($cekWish) > 0;
                }
            ?>
                <div class="buku-card">
                    <?php if(isset($_SESSION['user'])): ?>
                        <a href="wishlist_toggle.php?id=<?= $buku['id']; ?>" class="wishlist-btn <?= $isInWishlist ? 'active' : ''; ?>">
                            <?= $isInWishlist ? '❤️' : '🤍'; ?>
                        </a>
                    <?php endif; ?>

                    <div class="cover-wrap">
                        <img src="images/<?= $buku['gambar']; ?>" alt="<?= htmlspecialchars($buku['judul']); ?>">
                    </div>

                    <div class="buku-info">
                        <h3><?= htmlspecialchars($buku['judul']); ?></h3>
                        <p class="penulis"><?= htmlspecialchars($buku['penulis']); ?></p>
                        <p class="kategori-label">📂 <?= htmlspecialchars($nama_kategori); ?></p>
                        
                        <div class="stok-badge <?= $stokClass; ?>">
                            <?= $stokIcon; ?> <?= $stokText; ?>
                        </div>
                        
                        <p class="harga">Rp <?= number_format($buku['harga'],0,',','.'); ?></p>
                        
                        <?php if($stok > 0): ?>
                            <a href="detail.php?id=<?= $buku['id']; ?>" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right"></i>
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn-detail btn-disabled" onclick="return false;">
                                <i class="fas fa-times-circle"></i> Stok Habis
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <p>Buku tidak ditemukan</p>
                <small style="color: var(--text-muted);">Coba kata kunci atau kategori lain</small>
            </div>
        <?php endif; ?>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <div class="footer-logo-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <span class="footer-logo-text">litera<span style="font-weight:400;">books</span></span>
                </div>
                <p>Menyediakan koleksi buku berkualitas untuk menemani perjalanan literasimu. Baca, belajar, dan tumbuh bersama kami.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div>
                <h4>Menu</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="buku.php">Semua Buku</a></li>
                    <li><a href="cart.php">Keranjang</a></li>
                    <li><a href="about.php">Tentang Kami</a></li>
                </ul>
            </div>
            
            <div>
                <h4>Bantuan</h4>
                <ul class="footer-links">
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                    <li><a href="kontak.php">Kontak Kami</a></li>
                </ul>
            </div>
            
            <div>
                <h4>Kontak</h4>
                <ul class="footer-links">
                    <li><i class="fas fa-envelope" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> hello@literabooks.com</li>
                    <li><i class="fas fa-phone" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> +62 812 3456 7890</li>
                    <li><i class="fas fa-map-marker-alt" style="margin-right: 0.5rem; color: var(--accent-gold);"></i> Jakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2026 LiteraBooks. All rights reserved. Dibuat dengan <i class="fas fa-heart" style="color: var(--accent-gold);"></i> untuk para pembaca</p>
        </div>
    </div>
</footer>

</body>
</html>