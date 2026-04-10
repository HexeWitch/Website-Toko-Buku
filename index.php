<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

$keyword = '';
$where   = '';

if (isset($_GET['q']) && $_GET['q'] !== '') {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['q']);
    $where = "WHERE judul LIKE '%$keyword%' OR penulis LIKE '%$keyword%'";
}

$sql = "SELECT * FROM buku $where ORDER BY id DESC LIMIT 8";
$query = mysqli_query($koneksi, $sql);

$cartCount = 0;
$cartTotal = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;

        $q = mysqli_query($koneksi, "SELECT harga FROM buku WHERE id=$id");
        if ($row = mysqli_fetch_assoc($q)) {
            $cartCount += $qty;
            $cartTotal += $row['harga'] * $qty;
        }
    }
}

$sql_random = "SELECT gambar, judul FROM buku ORDER BY RAND() LIMIT 4";
$query_random = mysqli_query($koneksi, $sql_random);
$random_books = [];
while ($row = mysqli_fetch_assoc($query_random)) {
    $random_books[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Litera | Toko Buku Modern</title>
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
    scroll-behavior: smooth;
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
    --border: #e8ecf2;
    --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
    --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
    --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
}

h1, h2, h3, h4 {
    font-weight: 600;
    letter-spacing: -0.02em;
}

/* Layout */
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
}

.nav-link:hover {
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

.hero {
    background: linear-gradient(135deg, #f8f9fc 0%, #f0f2f6 100%);
    padding: 5rem 0;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50%;
    height: 100%;
    background: radial-gradient(circle at 100% 0%, rgba(45,59,94,0.03) 0%, transparent 70%);
    pointer-events: none;
}

.hero .container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.hero-content h1 {
    font-size: 3.5rem;
    line-height: 1.2;
    margin-bottom: 1.5rem;
    color: var(--text-primary);
}

.hero-content h1 .highlight {
    color: var(--accent-gold);
    font-weight: 700;
}

.hero-content p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-bottom: 2rem;
    line-height: 1.6;
}

.search-wrapper {
    display: flex;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border);
    overflow: hidden;
    transition: all 0.2s;
}

.search-wrapper:focus-within {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(45,59,94,0.1);
}

.search-input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: none;
    outline: none;
    font-size: 0.95rem;
    font-family: inherit;
}

.search-btn {
    background: var(--accent);
    border: none;
    padding: 0 1.8rem;
    cursor: pointer;
    color: white;
    transition: background 0.2s;
}

.search-btn:hover {
    background: var(--accent-light);
}

.hero-stats {
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
}

.stat {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--accent);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.hero-images {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
}

.hero-book {
    width: 100%;
    max-width: 800px;
}

.hero-book img {
    width: 110%;
    height: 350px;
    object-fit: cover;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.hero-book:hover {
    transform: translateY(-5px);
}

.features {
    padding: 4rem 0;
    background: white;
    border-bottom: 1px solid var(--border);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1.5rem;
}

.feature-card {
    text-align: center;
    padding: 1.5rem;
}

.feature-icon {
    width: 50px;
    height: 50px;
    background: #f0f2f6;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
}

.feature-icon i {
    font-size: 1.3rem;
    color: var(--accent);
}

.feature-card h4 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.feature-card p {
    font-size: 0.75rem;
    color: var(--text-muted);
}

.categories {
    padding: 4rem 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 2rem;
}

.section-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--text-primary);
}

.section-link {
    text-decoration: none;
    color: var(--accent);
    font-size: 0.9rem;
    font-weight: 500;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1rem;
}

.category-item {
    background: white;
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.category-item:hover {
    border-color: var(--accent);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.category-item i {
    font-size: 1.5rem;
    color: var(--accent);
    margin-bottom: 0.75rem;
}

.category-item span {
    font-size: 0.85rem;
    font-weight: 500;
    color: var(--text-primary);
}

.books-section {
    padding: 4rem 0;
    background: #f8f9fc;
}

.book-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.book-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;
    box-shadow: var(--shadow-sm);
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.book-image {
    position: relative;
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

.book-card:hover .book-image img {
    transform: scale(1.05);
}

.book-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    opacity: 0;
    transition: opacity 0.3s;
}

.book-card:hover .book-overlay {
    opacity: 1;
}

.btn-quick-view {
    background: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
    cursor: pointer;
    width: 100%;
    font-family: inherit;
    font-weight: 500;
}

.book-info {
    padding: 1.25rem;
}

.book-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
    color: var(--text-primary);
}

.book-author {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-bottom: 0.5rem;
}

.book-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 0.75rem;
}

.book-link {
    text-decoration: none;
    font-size: 0.8rem;
    color: var(--accent);
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.book-link:hover {
    gap: 0.5rem;
}

.newsletter {
    background: var(--accent);
    padding: 4rem 0;
}

.newsletter-content {
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.newsletter-content h2 {
    color: white;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.newsletter-content p {
    color: rgba(255,255,255,0.7);
    margin-bottom: 2rem;
}

.newsletter-form {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.newsletter-input {
    padding: 0.9rem 1.5rem;
    border: none;
    border-radius: 12px;
    width: 300px;
    font-family: inherit;
    outline: none;
}

.newsletter-btn {
    background: white;
    border: none;
    padding: 0.9rem 1.8rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    color: var(--accent);
    transition: all 0.2s;
}

.newsletter-btn:hover {
    transform: translateY(-2px);
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
    .book-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .features-grid,
    .category-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .container {
        padding: 0 1.5rem;
    }
    
    .hero .container {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .hero-content h1 {
        font-size: 2.2rem;
    }
    
    .hero-stats {
        justify-content: center;
    }
    
    .hero-images {
        order: -1;
    }
    
    .book-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .features-grid,
    .category-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .newsletter-form {
        flex-direction: column;
        align-items: center;
    }
    
    .newsletter-input {
        width: 90%;
    }
}

@media (max-width: 480px) {
    .book-grid {
        grid-template-columns: 1fr;
    }
    
    .features-grid,
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .nav-links {
        gap: 1rem;
    }
}
</style>
</head>

<body>
<nav class="navbar">
    <div class="container">
        <a href="index.php" class="logo">
            <div class="logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <span class="logo-text">litera<span>books</span></span>
        </a>
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

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h1>Baca lebih,<br><span class="highlight">hidup lebih kaya</span></h1>
            <p>Temukan ribuan buku terbaik dari penulis ternama. Mulai petualangan literasimu hari ini.</p>
            
            <form class="search-wrapper" method="GET" action="">
                <input type="text" class="search-input" name="q" placeholder="Cari judul buku atau penulis..." value="<?= htmlspecialchars($keyword); ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="hero-stats">
                <div class="stat">
                    <span class="stat-number">10k+</span>
                    <span class="stat-label">Koleksi Buku</span>
                </div>
                <div class="stat">
                    <span class="stat-number">500+</span>
                    <span class="stat-label">Penulis</span>
                </div>
                <div class="stat">
                    <span class="stat-number">50k+</span>
                    <span class="stat-label">Pembaca</span>
                </div>
            </div>
        </div>
        
     <div class="hero-images">
    <div class="hero-book">
        <img src="images/read.jpg" alt="Toko Buku">
    </div>
</div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-book"></i></div>
                <h4>Ebooks</h4>
                <p>Akses digital</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-headphones"></i></div>
                <h4>Audio Books</h4>
                <p>Dengarkan di mana saja</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-users"></i></div>
                <h4>Komunitas</h4>
                <p>Diskusi & review</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-building"></i></div>
                <h4>Library</h4>
                <p>Perpustakaan digital</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-heart"></i></div>
                <h4>Wishlist</h4>
                <p>Simpan favorit</p>
            </div>
        </div>
    </div>
</section>

<section class="books-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">
                <?= $keyword ? "Hasil: " . htmlspecialchars($keyword) : "Rekomendasi Hari Ini"; ?>
            </h2>
        </div>
        
        <div class="book-grid">
            <?php 
            $hasBooks = false;
            mysqli_data_seek($query, 0);
            while ($buku = mysqli_fetch_assoc($query)): 
                $hasBooks = true;
            ?>
            <div class="book-card">
                <div class="book-image">
                    <img src="images/<?= $buku['gambar']; ?>" alt="<?= htmlspecialchars($buku['judul']); ?>">
                    <div class="book-overlay">
                        <button class="btn-quick-view" onclick="window.location.href='detail.php?id=<?= $buku['id']; ?>'">
                            Lihat Detail
                        </button>
                    </div>
                </div>
                <div class="book-info">
                    <h3 class="book-title"><?= htmlspecialchars($buku['judul']); ?></h3>
                    <p class="book-author"><?= htmlspecialchars($buku['penulis']); ?></p>
                    <div class="book-price">Rp <?= number_format($buku['harga'],0,',','.'); ?></div>
                    <a href="detail.php?id=<?= $buku['id']; ?>" class="book-link">
                        Selengkapnya <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php if (!$hasBooks): ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem; background: white; border-radius: 16px;">
                <i class="fas fa-search" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
                <p style="color: var(--text-secondary);">Tidak ada buku ditemukan</p>
                <small style="color: var(--text-muted);">Coba kata kunci lain</small>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="newsletter">
    <div class="container">
        <div class="newsletter-content">
            <h2>Jangan lewatkan promo menarik</h2>
            <p>Dapatkan info diskon dan rekomendasi buku terbaru langsung ke emailmu</p>
            <form class="newsletter-form" onsubmit="alert('Terima kasih telah berlangganan!'); return false;">
                <input type="email" class="newsletter-input" placeholder="Email kamu" required>
                <button type="submit" class="newsletter-btn">Berlangganan</button>
            </form>
        </div>
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
<script>
document.querySelectorAll('.category-item').forEach(cat => {
    cat.addEventListener('click', () => {
        const category = cat.querySelector('span').innerText;
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.value = category;
            document.querySelector('.search-wrapper').submit();
        }
    });
});
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.05)';
    } else {
        navbar.style.boxShadow = 'none';
    }
});
</script>
</body>
</html>