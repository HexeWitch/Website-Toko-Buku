<?php
session_start();
require_once "config/koneksi.php";

if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

/* =========================
   AMBIL ALAMAT UTAMA USER
========================= */
$alamatUtama = mysqli_query($koneksi,
    "SELECT * FROM user_alamat 
     WHERE user_id='$user_id' 
     ORDER BY id DESC 
     LIMIT 1");
$dataAlamat = mysqli_fetch_assoc($alamatUtama);

/* =========================
   SIMPAN DATA
========================= */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $nama   = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $hp     = mysqli_real_escape_string($koneksi, $_POST['hp']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    $prov   = mysqli_real_escape_string($koneksi, $_POST['provinsi']);
    $kota   = mysqli_real_escape_string($koneksi, $_POST['kota']);
    $kec    = mysqli_real_escape_string($koneksi, $_POST['kecamatan']);
    $kode   = mysqli_real_escape_string($koneksi, $_POST['kodepos']);
    $metode = mysqli_real_escape_string($koneksi, $_POST['metode_bayar']);

    $_SESSION['metode_bayar'] = $metode;

    $kota_lengkap = $kota . " - " . $kec;

    // Cek apakah sudah punya alamat default
    $cek = mysqli_query($koneksi,
        "SELECT id FROM user_alamat 
         WHERE user_id='$user_id' LIMIT 1"
    );

    if(mysqli_num_rows($cek) > 0){
        $row = mysqli_fetch_assoc($cek);
        $alamat_id = $row['id'];

        mysqli_query($koneksi, "UPDATE user_alamat SET
            nama_penerima='$nama',
            no_hp='$hp',
            alamat='$alamat',
            kota='$kota_lengkap',
            provinsi='$prov',
            kode_pos='$kode'
            WHERE id='$alamat_id'
        ");
    } else {
        mysqli_query($koneksi, "INSERT INTO user_alamat
            (user_id, nama_penerima, no_hp, alamat, kota, provinsi, kode_pos, is_default)
            VALUES
            ('$user_id','$nama','$hp','$alamat','$kota_lengkap','$prov','$kode',1)
        ");
    }

    header("Location: checkout.php");
    exit;
}

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
    <title>Data Pengiriman | LiteraBooks</title>
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

        .form-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 3rem 1.5rem;
        }

        .form-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            padding: 2.5rem;
            width: 100%;
            max-width: 700px;
            transition: transform 0.2s;
        }

        .form-card:hover {
            transform: translateY(-4px);
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-header h2 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
        }

        .input-icon input,
        .input-icon textarea {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 2.8rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8f9fc;
        }

        .input-icon textarea {
            padding-top: 0.9rem;
            resize: vertical;
            min-height: 80px;
        }

        .input-icon input:focus,
        .input-icon textarea:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(45,59,94,0.1);
        }

        /* Input biasa tanpa icon */
        .form-input {
            width: 100%;
            padding: 0.9rem 1rem;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: all 0.2s;
            background: #f8f9fc;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            background: white;
            box-shadow: 0 0 0 3px rgba(45,59,94,0.1);
        }

        .row-2cols {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .row-2cols > div {
            flex: 1;
        }

        .metode-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .metode-wrapper {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .metode-item {
            flex: 1;
            background: #f8f9fc;
            border: 2px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .metode-item:hover {
            border-color: var(--accent);
            background: white;
        }

        .metode-item input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--accent);
        }

        .metode-text {
            flex: 1;
        }

        .metode-title {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            display: block;
        }

        .metode-desc {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        .btn-secondary {
            width: 100%;
            background: #f0f2f6;
            color: var(--text-primary);
            border: 1px solid var(--border);
            padding: 0.8rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1rem;
        }

        .btn-secondary:hover {
            background: var(--border);
        }

        .btn-submit {
            width: 100%;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.9rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: var(--accent-light);
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
            <a href="cart.php" class="nav-link"><i class="fas fa-shopping-cart"></i> Keranjang
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

<section class="form-container">
    <div class="form-card">
        <div class="form-header">
            <h2><i class="fas fa-truck"></i> Data Pengiriman</h2>
            <p>Isi alamat lengkap untuk pengiriman pesanan</p>
        </div>

        <?php if($dataAlamat): ?>
            <button type="button" id="isiAlamat" class="btn-secondary">
                <i class="fas fa-undo-alt"></i> Gunakan Alamat Sebelumnya
            </button>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nama Penerima</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="nama" placeholder="Nama lengkap penerima" required>
                </div>
            </div>

            <div class="form-group">
                <label>No HP / WhatsApp</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="text" name="hp" placeholder="081234567890" required>
                </div>
            </div>

            <div class="form-group">
                <label>Alamat Lengkap</label>
                <div class="input-icon">
                    <i class="fas fa-map-marker-alt"></i>
                    <textarea name="alamat" placeholder="Nama jalan, nomor rumah, patokan" required></textarea>
                </div>
            </div>

            <div class="row-2cols">
                <div class="form-group">
                    <label>Provinsi</label>
                    <div class="input-icon">
                        <i class="fas fa-map-pin"></i>
                        <input type="text" name="provinsi" placeholder="Contoh: DKI Jakarta" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kota/Kabupaten</label>
                    <div class="input-icon">
                        <i class="fas fa-city"></i>
                        <input type="text" name="kota" placeholder="Contoh: Jakarta Selatan" required>
                    </div>
                </div>
            </div>

            <div class="row-2cols">
                <div class="form-group">
                    <label>Kecamatan</label>
                    <div class="input-icon">
                        <i class="fas fa-location-dot"></i>
                        <input type="text" name="kecamatan" placeholder="Contoh: Kebayoran Baru" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Kode Pos</label>
                    <div class="input-icon">
                        <i class="fas fa-mail-bulk"></i>
                        <input type="text" name="kodepos" placeholder="Contoh: 12120" required>
                    </div>
                </div>
            </div>

            <h3 class="metode-title" style="margin-top: 1rem;">
                <i class="fas fa-credit-card"></i> Metode Pembayaran
            </h3>

            <div class="metode-wrapper">
                <label class="metode-item">
                    <input type="radio" name="metode_bayar" value="qris" required>
                    <div class="metode-text">
                        <span class="metode-title">QRIS</span>
                        <span class="metode-desc">OVO, DANA, GoPay, ShopeePay, Mobile Banking</span>
                    </div>
                </label>

                <label class="metode-item">
                    <input type="radio" name="metode_bayar" value="cod" required>
                    <div class="metode-text">
                        <span class="metode-title">Bayar di Tempat (COD)</span>
                        <span class="metode-desc">Bayar tunai saat barang diterima</span>
                    </div>
                </label>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-arrow-right"></i> Simpan & Lanjut Checkout
            </button>
        </form>
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
<?php if($dataAlamat): ?>
document.getElementById('isiAlamat').addEventListener('click', function(){
    document.querySelector('[name="nama"]').value = "<?= addslashes($dataAlamat['nama_penerima']) ?>";
    document.querySelector('[name="hp"]').value = "<?= $dataAlamat['no_hp'] ?>";
    document.querySelector('[name="alamat"]').value = "<?= addslashes($dataAlamat['alamat']) ?>";
    document.querySelector('[name="kodepos"]').value = "<?= $dataAlamat['kode_pos'] ?>";

    let kotaLengkap = "<?= $dataAlamat['kota'] ?>";
    let provinsi    = "<?= $dataAlamat['provinsi'] ?>";
    let parts = kotaLengkap.split(" - ");
    let kota = parts[0] ?? '';
    let kec  = parts[1] ?? '';

    document.querySelector('[name="provinsi"]').value = provinsi;
    document.querySelector('[name="kota"]').value = kota;
    document.querySelector('[name="kecamatan"]').value = kec;
});
<?php endif; ?>
</script>

</body>
</html>