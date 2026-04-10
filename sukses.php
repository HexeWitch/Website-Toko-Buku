<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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
    <title>Pesanan Berhasil | LiteraBooks</title>
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
            background: linear-gradient(135deg, #f8f9fc 0%, #f0f2f6 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            --border: #e8ecf2;
            --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.08);
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

        .success-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1.5rem;
        }

        .success-card {
            background: white;
            border-radius: 32px;
            box-shadow: var(--shadow-lg);
            padding: 3rem;
            width: 100%;
            max-width: 550px;
            text-align: center;
            transition: transform 0.2s;
            animation: fadeInUp 0.6s ease;
        }

        .success-card:hover {
            transform: translateY(-5px);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Success Icon */
        .success-icon {
            width: 100px;
            height: 100px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .success-icon i {
            font-size: 3rem;
            color: var(--success);
        }

        .success-card h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .success-card .order-id {
            background: #f0f2f6;
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .success-card p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .success-card .info-box {
            background: #f8f9fc;
            border-radius: 16px;
            padding: 1rem;
            margin: 1.5rem 0;
            text-align: left;
            border: 1px solid var(--border);
        }

        .info-box p {
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .info-box p:last-child {
            margin-bottom: 0;
        }

        .info-box i {
            width: 25px;
            color: var(--accent-gold);
        }

        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            background: #f8f9fc;
            transform: translateY(-2px);
        }

        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: var(--accent-gold);
            position: absolute;
            animation: confetti-fall 3s linear forwards;
        }

        @keyframes confetti-fall {
            to {
                transform: translateY(100vh);
                opacity: 0;
            }
        }

        .footer {
            background: #0a0e17;
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        .footer .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
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

        .footer-brand-icon i {
            color: white;
            font-size: 0.8rem;
        }

        .footer-brand span {
            font-size: 1rem;
            font-weight: 600;
        }

        .footer p {
            color: #5a6474;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .navbar .container-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.8rem;
            }
            
            .success-card {
                padding: 2rem;
            }
            
            .success-card h2 {
                font-size: 1.5rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-primary, .btn-outline {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-nav">
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
                <?php if($cartTotalItems > 0): ?>
                    <span class="cart-badge"><?= $cartTotalItems ?></span>
                <?php endif; ?>
            </a>
            <?php if(isset($_SESSION['user'])): ?>
                <div class="user-badge">
                    <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                    <span><?= $_SESSION['user']['nama']; ?></span>
                </div>
                <a href="riwayat.php" class="nav-link">
                    <i class="fas fa-history"></i> Riwayat
                </a>
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

<section class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h2>Pesanan Berhasil! 🎉</h2>
        
        <?php if(isset($_GET['order'])): ?>
            <div class="order-id">
                <i class="fas fa-receipt"></i> Order #<?= date('Ymd') . rand(100, 999); ?>
            </div>
        <?php endif; ?>
        
        <p>Terima kasih telah berbelanja di <strong>LiteraBooks</strong>.</p>
        <p>Pesanan Anda telah kami terima dan sedang diproses.</p>
        
        <div class="info-box">
            <p><i class="fas fa-clock"></i> Estimasi pemrosesan: 1x24 jam</p>
            <p><i class="fas fa-truck"></i> Pengiriman: 2-4 hari kerja</p>
            <p><i class="fas fa-envelope"></i> Email konfirmasi akan dikirim ke alamat Anda</p>
        </div>
        
        <div class="button-group">
            <a href="riwayat.php" class="btn-primary">
                <i class="fas fa-history"></i> Lihat Riwayat Pesanan
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-home"></i> Kembali ke Beranda
            </a>
        </div>
        
        <div class="button-group" style="margin-top: 0.5rem;">
            <a href="buku.php" class="btn-outline">
                <i class="fas fa-book"></i> Belanja Lagi
            </a>
        </div>
    </div>
</section>

<footer class="footer">
    <div class="container">
        <div class="footer-brand">
            <div class="footer-brand-icon">
                <i class="fas fa-book-open"></i>
            </div>
            <span>LiteraBooks</span>
        </div>
        <p>&copy; <?= date('Y'); ?> LiteraBooks. Membaca adalah jendela dunia.</p>
    </div>
</footer>

<script>
function createConfetti() {
    const colors = ['#2d3b5e', '#9b8c6c', '#10b981', '#f59e0b', '#ef4444'];
    for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.classList.add('confetti');
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.animationDuration = Math.random() * 2 + 2 + 's';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = Math.random() * 8 + 4 + 'px';
        confetti.style.height = Math.random() * 8 + 4 + 'px';
        confetti.style.position = 'fixed';
        confetti.style.top = '-10px';
        confetti.style.zIndex = '9999';
        document.body.appendChild(confetti);
        
        setTimeout(() => {
            confetti.remove();
        }, 3000);
    }
}
createConfetti();
</script>

</body>
</html>