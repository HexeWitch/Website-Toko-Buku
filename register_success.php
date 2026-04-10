<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Berhasil | LiteraBooks</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <meta http-equiv="refresh" content="5;url=login.php">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        :root {
            --accent: #2d3b5e;
            --accent-light: #3a4a6e;
            --accent-gold: #9b8c6c;
            --success: #10b981;
            --border: #e8ecf2;
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
            color: #1a1f2e;
            letter-spacing: -0.02em;
        }

        .logo-text span {
            font-weight: 400;
            color: #8e98a8;
        }

        .success-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .success-card {
            background: white;
            border-radius: 32px;
            padding: 3rem 2.5rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            animation: fadeInUp 0.5s ease;
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

        .success-icon {
            width: 90px;
            height: 90px;
            background: #d1fae5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .success-icon i {
            font-size: 3rem;
            color: var(--success);
        }

        .success-card h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
        }

        .success-card h2 {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .success-card p {
            color: #5a6474;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .countdown {
            font-size: 0.9rem;
            color: var(--accent-gold);
            margin-bottom: 1.5rem;
        }

        .countdown span {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 0.9rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }

        .btn-login:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(45, 59, 94, 0.2);
        }

        .btn-login i {
            font-size: 0.9rem;
        }

        .redirect-note {
            margin-top: 1.5rem;
            font-size: 0.8rem;
            color: #8e98a8;
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

        .footer p {
            color: #8e98a8;
            font-size: 0.8rem;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .success-card {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            .success-icon {
                width: 70px;
                height: 70px;
            }
            
            .success-icon i {
                font-size: 2rem;
            }
            
            .success-card h1 {
                font-size: 1.5rem;
            }
            
            .navbar .container-nav {
                padding: 0.8rem 1.5rem;
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
    </div>
</nav>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Registrasi Berhasil! 🎉</h1>
        <h2>Selamat datang di LiteraBooks</h2>
        <p>Akun Anda telah berhasil dibuat. Silakan login untuk mulai menjelajahi koleksi buku kami.</p>
        
        <div class="countdown">
            <i class="fas fa-hourglass-half"></i> Mengarahkan ke halaman login dalam <span id="counter">5</span> detik...
        </div>
        
        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login Sekarang
        </a>
        
        <div class="redirect-note">
            <i class="fas fa-arrow-right"></i> Atau klik tombol di atas untuk langsung login
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <p>&copy; <?= date('Y'); ?> LiteraBooks. Temukan buku favoritmu bersama kami.</p>
    </div>
</footer>

<script>
    let seconds = 5;
    const counterElement = document.getElementById('counter');
    
    const countdown = setInterval(function() {
        seconds--;
        counterElement.textContent = seconds;
        
        if (seconds <= 0) {
            clearInterval(countdown);
            window.location.href = 'login.php';
        }
    }, 1000);
</script>

</body>
</html>