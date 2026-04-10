<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

if (isset($_GET['hapus'])) {
    $buku_id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM wishlist WHERE user_id = $user_id AND buku_id = $buku_id");
    header("Location: wishlist.php");
    exit;
}

if (isset($_GET['add_to_cart'])) {
    $buku_id = (int)$_GET['add_to_cart'];
 
    $cekStok = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id = $buku_id");
    $stok = mysqli_fetch_assoc($cekStok)['stok'] ?? 0;
    
    if ($stok > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$buku_id])) {
            $_SESSION['cart'][$buku_id]++;
        } else {
            $_SESSION['cart'][$buku_id] = 1;
        }
    }
    
    header("Location: wishlist.php");
    exit;
}

$query = mysqli_query($koneksi, "
    SELECT w.*, b.judul, b.penulis, b.harga, b.gambar, b.stok, k.nama_kategori
    FROM wishlist w
    JOIN buku b ON w.buku_id = b.id
    LEFT JOIN kategori k ON b.kategori_id = k.id
    WHERE w.user_id = $user_id
    ORDER BY w.created_at DESC
");

$totalWishlist = mysqli_num_rows($query);

// Hitung total item di cart untuk badge
$cartTotalItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartTotalItems += (int)$qty;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist | LiteraBooks</title>
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

        .page-header {
            padding: 2rem 0 1rem;
        }

        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 600;
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

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 1rem 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        /* Wishlist Grid */
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .wishlist-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            position: relative;
        }

        .wishlist-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .remove-btn {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            background: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--danger);
            box-shadow: var(--shadow-sm);
            transition: all 0.2s;
            z-index: 10;
        }

        .remove-btn:hover {
            background: var(--danger);
            color: white;
            transform: scale(1.05);
        }

        .book-image {
            aspect-ratio: 2 / 2.5;
            overflow: hidden;
            background: #f0f2f6;
        }

        .book-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .wishlist-card:hover .book-image img {
            transform: scale(1.05);
        }

        .book-info {
            padding: 1rem;
        }

        .book-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-author {
            font-size: 0.75rem;
            color: #8e98a8;
            margin-bottom: 0.5rem;
        }

        .book-category {
            font-size: 0.65rem;
            color: var(--accent-gold);
            background: #f0f2f6;
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            margin-bottom: 0.5rem;
        }

        .book-price {
            font-size: 1rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .stok-badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            margin-bottom: 0.8rem;
        }

        .stok-tersedia {
            background: #d1fae5;
            color: #065f46;
        }

        .stok-habis {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-add-cart {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-add-cart:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-add-cart.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }

        .empty-wishlist {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            margin: 2rem 0;
        }

        .empty-wishlist i {
            font-size: 4rem;
            color: #8e98a8;
            margin-bottom: 1rem;
        }

        .empty-wishlist p {
            color: #5a6474;
            margin-bottom: 1rem;
        }

        .btn-shop {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
        }
.footer {
            background: #0a0e17;
            color: white;
            padding: 4rem 0 2rem;
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
            .team-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .values-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1.5rem;
            }
            
            .navbar .container {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 1rem;
            }
            
            .about-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .vm-grid {
                grid-template-columns: 1fr;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .values-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
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
        <div class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Beranda</a>
            <a href="buku.php" class="nav-link"><i class="fas fa-book"></i> Buku</a>
            <a href="cart.php" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Keranjang
                <?php if($cartTotalItems > 0): ?>
                    <span class="cart-badge"><?= $cartTotalItems ?></span>
                <?php endif; ?>
            </a>
          
            <a href="riwayat.php" class="nav-link"><i class="fas fa-history"></i> Riwayat</a>
            <div class="user-badge">
                <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                <span><?= $_SESSION['user']['nama']; ?></span>
            </div>
            <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>
            <i class="fas fa-heart" style="color: var(--danger);"></i>
            Wishlist Saya
        </h1>
        <p>Koleksi buku favorit yang ingin Anda baca</p>
    </div>

    <div class="stats-card">
        <i class="fas fa-heart" style="color: var(--danger); font-size: 1.2rem;"></i>
        <span>Total <strong><?= $totalWishlist ?></strong> buku dalam wishlist</span>
    </div>

    <?php if ($totalWishlist > 0): ?>
        <div class="wishlist-grid">
            <?php while ($item = mysqli_fetch_assoc($query)): 
                $stok = $item['stok'] ?? 0;
                $stokClass = $stok > 0 ? 'stok-tersedia' : 'stok-habis';
                $stokText = $stok > 0 ? 'Tersedia' : 'Stok Habis';
            ?>
                <div class="wishlist-card">
                    <a href="wishlist.php?hapus=<?= $item['buku_id'] ?>" class="remove-btn" onclick="return confirm('Hapus buku dari wishlist?')">
                        <i class="fas fa-trash"></i>
                    </a>
                    <div class="book-image">
                        <img src="images/<?= $item['gambar']; ?>" alt="<?= htmlspecialchars($item['judul']); ?>" onerror="this.src='https://placehold.co/200x250?text=No+Image'">
                    </div>
                    <div class="book-info">
                        <h3 class="book-title"><?= htmlspecialchars($item['judul']); ?></h3>
                        <p class="book-author"><?= htmlspecialchars($item['penulis']); ?></p>
                        <span class="book-category">📂 <?= htmlspecialchars($item['nama_kategori'] ?? 'Umum'); ?></span>
                        <p class="book-price">Rp <?= number_format($item['harga'], 0, ',', '.'); ?></p>
                        <span class="stok-badge <?= $stokClass ?>">
                            <?= $stok > 0 ? '✅ Tersedia' : '❌ Stok Habis' ?>
                        </span>
                        <?php if ($stok > 0): ?>
                            <a href="wishlist.php?add_to_cart=<?= $item['buku_id'] ?>" class="btn-add-cart">
                                <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                            </a>
                        <?php else: ?>
                            <a href="#" class="btn-add-cart disabled" onclick="return false;">
                                <i class="fas fa-times-circle"></i> Stok Habis
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-wishlist">
            <i class="fas fa-heart-broken"></i>
            <p>Wishlist Anda masih kosong</p>
            <p style="font-size: 0.8rem;">Temukan buku favorit Anda dan klik ikon ❤️ untuk menambah ke wishlist</p>
            <a href="buku.php" class="btn-shop">
                <i class="fas fa-shopping-bag"></i> Jelajahi Buku
            </a>
        </div>
    <?php endif; ?>
</div>
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