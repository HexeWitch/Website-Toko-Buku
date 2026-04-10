<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if (isset($_GET['add'])) {
    $id = (int) $_GET['add'];
    
    // Cek stok tersedia
    $q = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id=$id");
    $buku = mysqli_fetch_assoc($q);
    $stokTersedia = $buku['stok'] ?? 0;
    
    $currentQty = $_SESSION['cart'][$id] ?? 0;
    
    if ($currentQty + 1 <= $stokTersedia) {
        $_SESSION['cart'][$id] = $currentQty + 1;
    } else {
        $_SESSION['error_stok'] = "Stok tidak mencukupi! Maksimal $stokTersedia item.";
    }
    
    header("Location: cart.php");
    exit;
}

if (isset($_GET['min'])) {
    $id = (int) $_GET['min'];
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]--;
        if ($_SESSION['cart'][$id] <= 0) {
            unset($_SESSION['cart'][$id]);
        }
    }
    header("Location: cart.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit;
}

if (isset($_POST['update_qty'])) {
    $id = (int) $_POST['id'];
    $new_qty = (int) $_POST['qty'];
    
    if ($new_qty <= 0) {
        unset($_SESSION['cart'][$id]);
    } else {
        // Cek stok
        $q = mysqli_query($koneksi, "SELECT stok FROM buku WHERE id=$id");
        $buku = mysqli_fetch_assoc($q);
        $stokTersedia = $buku['stok'] ?? 0;
        
        if ($new_qty <= $stokTersedia) {
            $_SESSION['cart'][$id] = $new_qty;
        } else {
            $_SESSION['error_stok'] = "Stok tidak mencukupi! Maksimal $stokTersedia item.";
        }
    }
    
    header("Location: cart.php");
    exit;
}

$cartTotalItems = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $qty) {
        $cartTotalItems += (int)$qty;
    }
}

$error_stok = $_SESSION['error_stok'] ?? '';
unset($_SESSION['error_stok']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja | LiteraBooks</title>
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

        .navbar .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
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
            border-left: 1px solid var(--border);
        }

        .cart-page {
            padding: 3rem 0;
            min-height: calc(100vh - 400px);
        }

        .cart-page h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .cart-page h2 i {
            color: var(--accent-gold);
        }

        .error-message {
            background: #fee2e2;
            color: var(--danger);
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-table {
            width: 100%;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border-collapse: collapse;
        }

        .cart-table th {
            background: #f8f9fc;
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .cart-table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .cart-table tr:hover {
            background: #fafbfc;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .book-cart-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .book-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f2f6;
        }

        .book-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .stok-info {
            font-size: 0.7rem;
            margin-top: 0.2rem;
        }

        .stok-tersedia {
            color: #10b981;
        }

        .stok-habis {
            color: var(--danger);
        }

        .stok-sedikit {
            color: var(--warning);
        }

        .qty-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .qty-form {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .qty-input {
            width: 50px;
            padding: 0.3rem;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .qty-cell a, .qty-btn {
            text-decoration: none;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f0f2f6;
            border-radius: 6px;
            color: var(--text-primary);
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .qty-cell a:hover, .qty-btn:hover {
            background: var(--accent);
            color: white;
        }

        .btn-hapus {
            text-decoration: none;
            color: #dc2626;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-hapus:hover {
            background: #fee2e2;
        }

        .cart-summary {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .cart-summary strong {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
        }

        .cart-empty {
            text-align: center;
            padding: 4rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .cart-empty i {
            font-size: 3rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

        .btn-detail {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-detail:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
        }

        .btn-outline:hover {
            background: #f8f9fc;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            justify-content: flex-end;
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
    <div class="container">
        <a href="index.php" class="logo">
            <div class="logo-icon"><i class="fas fa-book-open"></i></div>
            <span class="logo-text">litera<span>books</span></span>
        </a>
        <div class="nav-links">
            <a href="index.php" class="nav-link"><i class="fas fa-home"></i> Beranda</a>
            <a href="buku.php" class="nav-link"><i class="fas fa-book"></i> Buku</a>
            <a href="cart.php" class="nav-link active"><i class="fas fa-shopping-cart"></i> Keranjang
                <?php if($cartTotalItems > 0): ?>
                    <span class="cart-badge"><?= $cartTotalItems ?></span>
                <?php endif; ?>
            </a>
            <a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> Tentang</a>
            <?php if(isset($_SESSION['user'])): ?>
                <div class="user-badge">
                    <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                    <span><?= $_SESSION['user']['nama']; ?></span>
                </div>
                <a href="riwayat.php" class="nav-link"><i class="fas fa-history"></i> Riwayat</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Masuk</a>
                <a href="register.php" class="nav-link"><i class="fas fa-user-plus"></i> Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="container cart-page">
    <h2><i class="fas fa-shopping-bag"></i> Keranjang Belanja</h2>

    <?php if ($error_stok): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= $error_stok; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <div class="cart-empty">
            <i class="fas fa-shopping-cart"></i>
            <p>Keranjang belanjamu masih kosong</p>
            <a href="buku.php" class="btn-detail"><i class="fas fa-book"></i> Mulai Belanja</a>
        </div>
    <?php else: ?>
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Buku</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $total = 0;
            foreach ($_SESSION['cart'] as $id => $qty):
                $id = (int)$id;
                $qty = (int)$qty;

                $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE id=$id");
                $buku = mysqli_fetch_assoc($q);
                if (!$buku) continue;

                $subtotal = $buku['harga'] * $qty;
                $total += $subtotal;
                $stok = $buku['stok'] ?? 0;
                
                if($stok == 0) $stokClass = 'stok-habis';
                elseif($stok < 5) $stokClass = 'stok-sedikit';
                else $stokClass = 'stok-tersedia';
            ?>
                <tr>
                    <td data-label="Buku">
                        <div class="book-cart-info">
                            <img src="images/<?= $buku['gambar']; ?>" class="book-thumb" onerror="this.src='https://placehold.co/50x70?text=No+Image'">
                            <div>
                                <div class="book-title"><?= htmlspecialchars($buku['judul']); ?></div>
                                <div class="stok-info <?= $stokClass; ?>">
                                    <i class="fas fa-box"></i> Stok: <?= $stok; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td data-label="Harga">Rp <?= number_format($buku['harga'],0,',','.'); ?></td>
                    <td data-label="Stok">
                        <?php if($stok == 0): ?>
                            <span style="color: var(--danger);"><i class="fas fa-times-circle"></i> Habis</span>
                        <?php elseif($stok < 5): ?>
                            <span style="color: var(--warning);"><i class="fas fa-exclamation-triangle"></i> Sisa <?= $stok; ?></span>
                        <?php else: ?>
                            <span style="color: #10b981;"><i class="fas fa-check-circle"></i> <?= $stok; ?></span>
                        <?php endif; ?>
                    </td>
                    <td data-label="Jumlah" class="text-center">
                        <div class="qty-cell">
                            <a href="cart.php?min=<?= $id; ?>">−</a>
                            <form method="post" class="qty-form" style="display: inline-flex; align-items: center;">
                                <input type="hidden" name="id" value="<?= $id; ?>">
                                <input type="number" name="qty" value="<?= $qty; ?>" class="qty-input" min="1" max="<?= $stok; ?>">
                                <button type="submit" name="update_qty" class="qty-btn">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <a href="cart.php?add=<?= $id; ?>">+</a>
                        </div>
                    </td>
                    <td data-label="Subtotal" class="text-right">
                        <strong style="color: var(--accent);">Rp <?= number_format($subtotal,0,',','.'); ?></strong>
                    </td>
                    <td data-label="Aksi" class="cart-aksi">
                        <a href="cart.php?hapus=<?= $id; ?>" class="btn-hapus" onclick="return confirm('Hapus item ini dari keranjang?')">
                            <i class="fas fa-trash-alt"></i> Hapus
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <span>Total Belanja</span>
            <strong>Rp <?= number_format($total,0,',','.'); ?></strong>
        </div>

        <div class="action-buttons">
            <a href="buku.php" class="btn-detail btn-outline"><i class="fas fa-arrow-left"></i> Lanjut Belanja</a>
            <a href="datadiri.php" class="btn-detail"><i class="fas fa-credit-card"></i> Checkout</a>
        </div>
    <?php endif; ?>
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