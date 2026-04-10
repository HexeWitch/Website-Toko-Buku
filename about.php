<?php
session_start();
require_once __DIR__ . '/config/koneksi.php';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | LiteraBooks</title>
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

        /* Container */
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

        .cart-badge {
            background: var(--accent);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.3rem;
        }

        .page-header {
            background: linear-gradient(135deg, #f8f9fc 0%, #f0f2f6 100%);
            padding: 4rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: radial-gradient(circle at 100% 0%, rgba(45,59,94,0.03) 0%, transparent 70%);
            pointer-events: none;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .page-header p {
            color: var(--text-secondary);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .about-section {
            padding: 4rem 0;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-image {
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .about-text h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .about-text h2 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .about-text p {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        .vision-mission {
            background: white;
            padding: 4rem 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-title h2 {
            font-size: 2rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .section-title p {
            color: var(--text-muted);
        }

        .vm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .vm-card {
            background: #f8f9fc;
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s;
            border: 1px solid var(--border);
        }

        .vm-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent);
        }

        .vm-icon {
            width: 60px;
            height: 60px;
            background: var(--accent);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .vm-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .vm-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .vm-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .team-section {
            padding: 4rem 0;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            text-align: center;
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .team-image {
            width: 100%;
            aspect-ratio: 1;
            overflow: hidden;
            background: #f0f2f6;
        }

        .team-image img {
            width: 100%;
            height: 150%;
            object-fit: cover;
        }

        .team-info {
            padding: 1.5rem;
        }

        .team-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-primary);
        }

        .team-info p {
            font-size: 0.8rem;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
        }

        .team-info small {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .values-section {
            background: white;
            padding: 4rem 0;
            border-top: 1px solid var(--border);
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .value-card {
            text-align: center;
            padding: 2rem;
            border-radius: 20px;
            transition: all 0.3s;
            border: 1px solid var(--border);
            background: #f8f9fc;
        }

        .value-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: var(--accent);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .value-icon i {
            font-size: 2rem;
            color: white;
        }

        .value-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .value-card p {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        .stats-section {
            background: var(--accent);
            padding: 4rem 0;
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }

        .stat-item h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 0.9rem;
            opacity: 0.8;
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

<section class="page-header">
    <div class="container">
        <h1>Tentang LiteraBooks</h1>
        <p>Menginspirasi dunia melalui literasi dan pengetahuan</p>
    </div>
</section>

<section class="about-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <img src="images/library.jfif" alt="Perpustakaan Modern">
            </div>
            <div class="about-text">
                <h2><i class="fas fa-quote-left"></i> Cerita Kami</h2>
                <p>LiteraBooks didirikan pada tahun 2023 dengan misi untuk memberikan akses mudah bagi masyarakat terhadap buku-buku berkualitas. Kami percaya bahwa membaca adalah jendela dunia dan kunci untuk membuka potensi diri.</p>
                <p>Berawal dari toko buku kecil, kini LiteraBooks telah tumbuh menjadi platform digital yang melayani ribuan pembaca setia di seluruh Indonesia. Kami menyediakan koleksi buku terkurasi dari berbagai genre dan penulis terbaik.</p>
                <p>Komitmen kami adalah memberikan pengalaman berbelanja buku yang menyenangkan, cepat, dan terpercaya. Setiap buku yang kami jual adalah hasil seleksi ketat untuk memastikan kualitas dan manfaat bagi pembaca.</p>
            </div>
        </div>
    </div>
</section>

<section class="vision-mission">
    <div class="container">
        <div class="section-title">
            <h2>Visi & Misi</h2>
            <p>Tujuan dan komitmen kami untuk masa depan literasi</p>
        </div>
        <div class="vm-grid">
            <div class="vm-card">
                <div class="vm-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Visi</h3>
                <p>Menjadi platform buku digital terdepan di Indonesia yang menginspirasi dan memberdayakan masyarakat melalui budaya membaca.</p>
            </div>
            <div class="vm-card">
                <div class="vm-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Misi</h3>
                <p>Menyediakan akses mudah ke ribuan buku berkualitas, mendukung penulis lokal, dan membangun komunitas pembaca yang aktif dan berbagi pengetahuan.</p>
            </div>
        </div>
    </div>
</section>

<section class="team-section">
    <div class="container">
        <div class="section-title">
            <h2>Tim Kami</h2>
            <p>Orang-orang di balik LiteraBooks</p>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-image">
                    <img src="images/louis.jfif" alt="Founder"> alt="Founder">
                </div>
                <div class="team-info">
                    <h3>Louis Partidge</h3>
                    <p>Founder & CEO</p>
                    <small>Mengawali mimpi LiteraBooks</small>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="images/lizzie.jfif" alt="Editor">
                </div>
                <div class="team-info">
                    <h3>Elizabeth Olsen</h3>
                    <p>Head of Editorial</p>
                    <small>Memastikan kualitas setiap buku</small>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="images/jadie.jfif">
                </div>
                <div class="team-info">
                    <h3>Jacob Elordi</h3>
                    <p>Marketing Director</p>
                    <small>Menjangkau lebih banyak pembaca</small>
                </div>
            </div>
            <div class="team-card">
                <div class="team-image">
                    <img src="images/angie.jfif">
                </div>
                <div class="team-info">
                    <h3>Angelina Jolie</h3>
                    <p>Customer Happiness</p>
                    <small>Melayani dengan sepenuh hati</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <div class="section-title">
            <h2>Nilai Kami</h2>
            <p>Prinsip yang menjadi fondasi LiteraBooks</p>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Integritas</h3>
                <p>Kami berkomitmen untuk jujur dan transparan dalam setiap aspek bisnis.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Inovasi</h3>
                <p>Terus berkembang dan beradaptasi dengan teknologi terbaru.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Kolaborasi</h3>
                <p>Bekerja sama dengan penulis, penerbit, dan komunitas.</p>
            </div>
        </div>
    </div>
</section>

<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3>10k+</h3>
                <p>Buku Tersedia</p>
            </div>
            <div class="stat-item">
                <h3>500+</h3>
                <p>Penulis</p>
            </div>
            <div class="stat-item">
                <h3>50k+</h3>
                <p>Pembaca Aktif</p>
            </div>
            <div class="stat-item">
                <h3>4.9</h3>
                <p>Rating Pelanggan</p>
            </div>
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

</body>
</html>