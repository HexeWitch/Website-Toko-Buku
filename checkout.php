<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/config/koneksi.php';

/* ======================
   WAJIB LOGIN
====================== */
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

/* ======================
   CART HARUS ADA
====================== */
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

$metode = $_SESSION['metode_bayar'] ?? 'cod';
$user_id = (int) $_SESSION['user']['id'];
$total = 0;
$items = [];

/* ======================
   AMBIL DATA CART
====================== */
foreach ($_SESSION['cart'] as $id => $qty) {
    $id  = (int) $id;
    $qty = (int) $qty;

    $q = mysqli_query($koneksi, "SELECT * FROM buku WHERE id=$id");
    $buku = mysqli_fetch_assoc($q);
    if (!$buku) continue;

    $subtotal = $buku['harga'] * $qty;
    $total += $subtotal;

    $items[] = [
        'id' => $id,
        'judul' => $buku['judul'],
        'harga' => $buku['harga'],
        'qty' => $qty,
        'subtotal' => $subtotal
    ];
}

/* ======================
   AMBIL ALAMAT USER
====================== */
$alamatQuery = mysqli_query($koneksi, "
    SELECT * FROM user_alamat 
    WHERE user_id = $user_id 
    ORDER BY is_default DESC, id DESC 
    LIMIT 1
");
$alamat = mysqli_fetch_assoc($alamatQuery);

/* ======================
   PROSES UPLOAD BUKTI (AJAX)
====================== */
if (isset($_POST['upload_bukti'])) {
    if (!isset($_FILES['bukti_transfer']) || $_FILES['bukti_transfer']['error'] != 0) {
        echo json_encode(['success' => false, 'error' => 'Harap upload bukti pembayaran']);
        exit;
    }
    
    $file = $_FILES['bukti_transfer'];
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Format harus JPG/PNG']);
        exit;
    }
    
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'error' => 'Maksimal ukuran 2MB']);
        exit;
    }
    
    // Buat folder
    $upload_dir = __DIR__ . '/uploads/bukti/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
    
    // Nama file
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'bukti_' . $user_id . '_' . time() . '_' . rand(100, 999) . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'error' => 'Gagal upload file']);
        exit;
    }
    
    $_SESSION['bukti_pembayaran'] = $filename;
    echo json_encode(['success' => true]);
    exit;
}

/* ======================
   PROSES CHECKOUT
====================== */
if (isset($_POST['checkout'])) {
    // Cek apakah QRIS harus upload bukti
    if ($metode == 'qris' && !isset($_SESSION['bukti_pembayaran'])) {
        echo json_encode(['need_upload' => true]);
        exit;
    }
    
    $status = ($metode == 'cod') ? 'Diproses' : 'Menunggu Konfirmasi';
    $bukti = ($metode == 'qris' && isset($_SESSION['bukti_pembayaran'])) ? "'" . $_SESSION['bukti_pembayaran'] . "'" : "NULL";
    
    $insert = mysqli_query(
        $koneksi,
        "INSERT INTO transaksi (user_id, total, status, metode_bayar, bukti_pembayaran, created_at)
         VALUES ($user_id, $total, '$status', '$metode', $bukti, NOW())"
    );

    if (!$insert) {
        echo json_encode(['success' => false, 'error' => 'Gagal menyimpan transaksi']);
        exit;
    }

    $transaksi_id = mysqli_insert_id($koneksi);

    foreach ($items as $item) {
        mysqli_query(
            $koneksi,
            "INSERT INTO transaksi_detail
            (transaksi_id, buku_id, harga, qty, subtotal)
            VALUES
            ($transaksi_id, {$item['id']},
             {$item['harga']}, {$item['qty']},
             {$item['subtotal']})"
        );
    }

    unset($_SESSION['cart']);
    unset($_SESSION['metode_bayar']);
    unset($_SESSION['bukti_pembayaran']);
    
    echo json_encode(['success' => true, 'redirect' => 'sukses.php?order=success&metode=' . $metode]);
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
    <title>Checkout | LiteraBooks</title>
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

        /* CHECKOUT */
        .checkout-page {
            padding: 3rem 0;
            min-height: calc(100vh - 400px);
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 2rem;
        }

        .checkout-left, .checkout-right {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--accent-gold);
            display: inline-block;
        }

        .user-info {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info i {
            font-size: 2rem;
            color: var(--accent-gold);
        }

        .alamat-box {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .alamat-box p {
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .payment-method {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .payment-icon {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .cart-items {
            margin-bottom: 1.5rem;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border);
        }

        .total-section {
            background: #f8f9fc;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
        }

        .total-row.grand {
            border-top: 1px solid var(--border);
            margin-top: 0.5rem;
            padding-top: 0.8rem;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .total-row.grand .amount {
            color: var(--accent);
            font-size: 1.3rem;
        }

        .btn-checkout {
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

        .btn-checkout:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-primary);
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.85rem;
        }

        /* MODAL POPUP */
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
            max-width: 450px;
            padding: 2rem;
            text-align: center;
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-icon {
            width: 70px;
            height: 70px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }

        .modal-icon i {
            font-size: 2rem;
            color: var(--accent-gold);
        }

        .modal h2 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }

        .modal p {
            color: #5a6474;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .modal .total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--accent);
            margin: 0.5rem 0 1rem;
        }

        .upload-area-modal {
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            margin: 1rem 0;
            transition: all 0.2s;
        }

        .upload-area-modal:hover {
            border-color: var(--accent-gold);
            background: #fefcf8;
        }

        .upload-area-modal i {
            font-size: 2rem;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
        }

        .preview-modal {
            margin: 1rem 0;
        }

        .preview-modal img {
            max-width: 150px;
            border-radius: 8px;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-confirm {
            flex: 1;
            background: var(--accent);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-cancel {
            flex: 1;
            background: #f1f3f5;
            color: #5a6474;
            border: none;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            padding: 0.8rem;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .qris-img {
            max-width: 200px;
            margin: 1rem auto;
            display: block;
            border-radius: 12px;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 1rem;
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
            <div class="logo-icon">
                <i class="fas fa-book-open"></i>
            </div>
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
            <?php if(isset($_SESSION['user'])): ?>
                <div class="user-badge">
                    <i class="fas fa-user-circle" style="color: var(--accent-gold);"></i>
                    <span><?= $_SESSION['user']['nama']; ?></span>
                </div>
                <a href="riwayat.php" class="nav-link"><i class="fas fa-history"></i> Riwayat</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
    <div class="checkout-page">
        <div class="checkout-grid">
            <!-- Left Column -->
            <div class="checkout-left">
                <h3 class="section-title"><i class="fas fa-truck"></i> Info Pengiriman</h3>
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <div>
                        <p>Dikirim kepada</p>
                        <strong><?= htmlspecialchars($_SESSION['user']['nama']); ?></strong>
                    </div>
                </div>

                <?php if($alamat): ?>
                <div class="alamat-box">
                    <p><i class="fas fa-map-marker-alt"></i> Alamat Pengiriman</p>
                    <strong><?= htmlspecialchars($alamat['nama_penerima']); ?></strong><br>
                    <p><?= htmlspecialchars($alamat['alamat']); ?></p>
                    <p><?= htmlspecialchars($alamat['kota']); ?>, <?= htmlspecialchars($alamat['provinsi']); ?></p>
                    <p>📞 <?= htmlspecialchars($alamat['no_hp']); ?></p>
                </div>
                <?php else: ?>
                <div class="alamat-box">
                    <p><i class="fas fa-exclamation-triangle"></i> Belum ada alamat</p>
                    <p style="margin-top: 0.5rem;">
                        <a href="datadiri.php" style="color: var(--accent);">Klik disini</a> untuk menambahkan alamat
                    </p>
                </div>
                <?php endif; ?>

                <div class="payment-method">
                    <div class="payment-icon">
                        <?php if($metode == 'qris'): ?>
                            <i class="fas fa-qrcode"></i>
                        <?php else: ?>
                            <i class="fas fa-money-bill-wave"></i>
                        <?php endif; ?>
                    </div>
                    <div class="payment-info">
                        <h4>Metode Pembayaran</h4>
                        <p><?= $metode == 'qris' ? 'QRIS (Scan QR Code)' : 'Bayar di Tempat (COD)' ?></p>
                    </div>
                </div>

                <?php if($metode == 'qris'): ?>
                <div class="qris-box">
                    <img src="images/qris.png" class="qris-img" alt="QRIS" onerror="this.src='https://placehold.co/200x200?text=QRIS+Code'">
                    <p style="font-size: 0.7rem; color: var(--text-muted);">Scan QRIS menggunakan mobile banking, OVO, DANA, GoPay, atau ShopeePay</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="checkout-right">
                <h3 class="section-title"><i class="fas fa-shopping-bag"></i> Ringkasan Pesanan</h3>

                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                    <div class="cart-item">
                        <div class="item-info">
                            <h4><?= htmlspecialchars($item['judul']); ?></h4>
                            <p><?= $item['qty']; ?> x Rp <?= number_format($item['harga'],0,',','.'); ?></p>
                        </div>
                        <div class="item-price">
                            <div class="price">Rp <?= number_format($item['subtotal'],0,',','.'); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="total-section">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($total,0,',','.'); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Biaya Pengiriman</span>
                        <span>Gratis</span>
                    </div>
                    <div class="total-row grand">
                        <span>Total</span>
                        <span class="amount">Rp <?= number_format($total,0,',','.'); ?></span>
                    </div>
                </div>

                <button class="btn-checkout" id="btnCheckout">
                    <i class="fas fa-check-circle"></i> Konfirmasi Pesanan
                </button>

                <div class="text-center mt-2">
                    <a href="cart.php" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali ke Keranjang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="uploadModal" class="modal">
    <div class="modal-content">
        <div class="modal-icon">
            <i class="fas fa-qrcode"></i>
        </div>
        <h2>Upload Bukti Pembayaran</h2>
        <p>Silakan upload bukti transfer QRIS kamu</p>
        <div class="total">Rp <?= number_format($total, 0, ',', '.') ?></div>
        
        <div id="modalError" class="alert-error" style="display: none;"></div>
        
        <div class="upload-area-modal" id="uploadArea">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Klik untuk upload bukti transfer</p>
            <p style="font-size: 0.7rem; margin-top: 0.3rem;">JPG, PNG, JPEG (Max 2MB)</p>
        </div>
        <input type="file" id="buktiFile" accept="image/*" style="display: none;">
        
        <div class="preview-modal" id="previewModal"></div>
        
        <div class="modal-buttons">
            <button class="btn-cancel" id="cancelUpload">Batal</button>
            <button class="btn-confirm" id="confirmUpload">Konfirmasi</button>
        </div>
    </div>
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

<script>
    const metode = '<?= $metode ?>';
    let selectedFile = null;

    document.getElementById('btnCheckout').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Memproses...';
        
        try {
            if (metode === 'qris') {
                document.getElementById('uploadModal').style.display = 'flex';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Konfirmasi Pesanan';
                return;
            }
            
            const formData = new FormData();
            formData.append('checkout', '1');
            
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                window.location.href = result.redirect;
            } else if (result.need_upload) {
                document.getElementById('uploadModal').style.display = 'flex';
            } else {
                alert('Terjadi kesalahan: ' + (result.error || 'Unknown error'));
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Konfirmasi Pesanan';
        }
    });
    
    document.getElementById('uploadArea').addEventListener('click', function() {
        document.getElementById('buktiFile').click();
    });
    
    document.getElementById('buktiFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            selectedFile = file;
            const preview = document.getElementById('previewModal');
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview">';
            }
            reader.readAsDataURL(file);
            document.getElementById('modalError').style.display = 'none';
        }
    });
    
    document.getElementById('confirmUpload').addEventListener('click', async function() {
        if (!selectedFile) {
            document.getElementById('modalError').innerHTML = '<i class="fas fa-exclamation-circle"></i> Harap pilih file bukti transfer';
            document.getElementById('modalError').style.display = 'block';
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="loading"></span> Mengupload...';
        
        const formData = new FormData();
        formData.append('upload_bukti', '1');
        formData.append('bukti_transfer', selectedFile);
        
        try {
            const uploadResponse = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const uploadResult = await uploadResponse.json();
            
            if (!uploadResult.success) {
                throw new Error(uploadResult.error);
            }
            
            const checkoutFormData = new FormData();
            checkoutFormData.append('checkout', '1');
            
            const checkoutResponse = await fetch(window.location.href, {
                method: 'POST',
                body: checkoutFormData
            });
            
            const checkoutResult = await checkoutResponse.json();
            
            if (checkoutResult.success) {
                window.location.href = checkoutResult.redirect;
            } else {
                throw new Error(checkoutResult.error || 'Gagal checkout');
            }
            
        } catch (error) {
            document.getElementById('modalError').innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + error.message;
            document.getElementById('modalError').style.display = 'block';
            btn.disabled = false;
            btn.innerHTML = 'Konfirmasi';
        }
    });
    
    document.getElementById('cancelUpload').addEventListener('click', function() {
        document.getElementById('uploadModal').style.display = 'none';
        selectedFile = null;
        document.getElementById('previewModal').innerHTML = '';
        document.getElementById('buktiFile').value = '';
        document.getElementById('modalError').style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('uploadModal');
        if (e.target === modal) {
            modal.style.display = 'none';
            selectedFile = null;
            document.getElementById('previewModal').innerHTML = '';
            document.getElementById('buktiFile').value = '';
        }
    });
</script>

</body>
</html>